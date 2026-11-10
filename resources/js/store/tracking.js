/**
 * Ad pixels (Meta, TikTok, Snapchat) and Google Analytics.
 *
 * The server lists the IDs set up in Settings in #tracking-config. When the store asks visitors first,
 * nothing loads until they accept in the cookie bar ("tracking-consent" event). Events come from:
 *  - <script type="application/json" data-track-event> on the page (product, checkout, thank-you), and
 *  - "store:track" window events (adding to the bag).
 * Each event has an ID; purchases use the same ID the server sends, so platforms count them once.
 */
import { trackingConsent } from './consent-bar';

const LOADERS = {
    meta(pixelId) {
        if (!window.fbq) {
            const fbq = function (...args) {
                fbq.callMethod ? fbq.callMethod(...args) : fbq.queue.push(args);
            };
            fbq.push = fbq;
            fbq.loaded = true;
            fbq.version = '2.0';
            fbq.queue = [];
            window.fbq = fbq;
            window._fbq = window._fbq || fbq;
            addScript('https://connect.facebook.net/en_US/fbevents.js');
        }

        window.fbq('init', pixelId);
        window.fbq('track', 'PageView');
    },

    tiktok(pixelId) {
        const methods = ['page', 'track', 'identify', 'instances', 'debug', 'on', 'off', 'once', 'ready', 'alias', 'group', 'enableCookie', 'disableCookie', 'holdConsent', 'revokeConsent', 'grantConsent'];
        const ttq = (window.ttq = window.ttq || []);
        window.TiktokAnalyticsObject = 'ttq';
        const defer = (target, method) => {
            target[method] = (...args) => target.push([method, ...args]);
        };
        ttq.methods = methods;
        ttq.setAndDefer = defer;
        methods.forEach((method) => defer(ttq, method));
        ttq.instance = (id) => {
            const instance = ttq._i[id] || [];
            methods.forEach((method) => defer(instance, method));

            return instance;
        };
        ttq.load = (id, options) => {
            const src = 'https://analytics.tiktok.com/i18n/pixel/events.js';
            ttq._i = ttq._i || {};
            ttq._i[id] = [];
            ttq._i[id]._u = src;
            ttq._t = ttq._t || {};
            ttq._t[id] = Date.now();
            ttq._o = ttq._o || {};
            ttq._o[id] = options || {};
            addScript(`${src}?sdkid=${id}&lib=ttq`);
        };

        ttq.load(pixelId);
        ttq.page();
    },

    snap(pixelId) {
        if (!window.snaptr) {
            const snaptr = function (...args) {
                snaptr.handleRequest ? snaptr.handleRequest(...args) : snaptr.queue.push(args);
            };
            snaptr.queue = [];
            window.snaptr = snaptr;
            addScript('https://sc-static.net/scevent.min.js');
        }

        window.snaptr('init', pixelId, {});
        window.snaptr('track', 'PAGE_VIEW');
    },

    ga4(measurementId) {
        window.dataLayer = window.dataLayer || [];
        // gtag must push the arguments object itself, not an array.
        window.gtag = window.gtag || function () {
            window.dataLayer.push(arguments);
        };
        addScript(`https://www.googletagmanager.com/gtag/js?id=${encodeURIComponent(measurementId)}`);
        window.gtag('js', new Date());
        window.gtag('config', measurementId);
    },
};

/**
 * How each platform names and shapes our events (ViewContent, AddToCart, InitiateCheckout, Purchase).
 */
const SENDERS = {
    meta(event) {
        window.fbq('track', event.event, {
            value: event.value,
            currency: event.currency,
            content_type: 'product',
            content_ids: event.contents.map((item) => item.id),
            contents: event.contents.map((item) => ({ id: item.id, quantity: item.quantity, item_price: item.price })),
            num_items: totalQuantity(event),
            ...(event.content_name ? { content_name: event.content_name } : {}),
            ...(event.order_id ? { order_id: event.order_id } : {}),
        }, { eventID: event.id });
    },

    tiktok(event) {
        const names = { ViewContent: 'ViewContent', AddToCart: 'AddToCart', InitiateCheckout: 'InitiateCheckout', Purchase: 'PlaceAnOrder' };

        window.ttq.track(names[event.event], {
            value: event.value,
            currency: event.currency,
            contents: event.contents.map((item) => ({ content_id: item.id, content_type: 'product', quantity: item.quantity, price: item.price })),
            ...(event.content_name ? { content_name: event.content_name } : {}),
        }, { event_id: event.id });
    },

    snap(event) {
        const names = { ViewContent: 'VIEW_CONTENT', AddToCart: 'ADD_CART', InitiateCheckout: 'START_CHECKOUT', Purchase: 'PURCHASE' };

        window.snaptr('track', names[event.event], {
            price: event.value,
            currency: event.currency,
            item_ids: event.contents.map((item) => item.id),
            number_items: totalQuantity(event),
            client_dedup_id: event.id,
            ...(event.order_id ? { transaction_id: event.order_id } : {}),
        });
    },

    ga4(event) {
        const names = { ViewContent: 'view_item', AddToCart: 'add_to_cart', InitiateCheckout: 'begin_checkout', Purchase: 'purchase' };

        window.gtag('event', names[event.event], {
            value: event.value,
            currency: event.currency,
            items: event.contents.map((item) => ({ item_id: item.id, quantity: item.quantity, price: item.price, ...(event.content_name ? { item_name: event.content_name } : {}) })),
            ...(event.order_id ? { transaction_id: event.order_id } : {}),
        });
    },
};

function addScript(src) {
    const script = document.createElement('script');
    script.async = true;
    script.src = src;
    document.head.appendChild(script);
}

function totalQuantity(event) {
    return event.contents.reduce((sum, item) => sum + item.quantity, 0);
}

function readJson(element) {
    try {
        return JSON.parse(element?.textContent ?? 'null');
    } catch {
        return null;
    }
}

export default function startTracking() {
    const config = readJson(document.getElementById('tracking-config'));

    if (!config?.pixels) {
        return;
    }

    const platforms = Object.keys(config.pixels).filter((platform) => LOADERS[platform]);
    const waiting = [];
    let started = false;

    const send = (event) => {
        if (!event?.event || !Array.isArray(event.contents)) {
            return;
        }

        if (!started) {
            waiting.push(event);

            return;
        }

        platforms.forEach((platform) => {
            try {
                SENDERS[platform](event);
            } catch (error) {
                console.warn(`Tracking: ${platform} could not send ${event.event}`, error);
            }
        });
    };

    const start = () => {
        if (started) {
            return;
        }

        started = true;
        platforms.forEach((platform) => {
            try {
                LOADERS[platform](config.pixels[platform]);
            } catch (error) {
                console.warn(`Tracking: ${platform} did not load`, error);
            }
        });
        waiting.splice(0).forEach(send);
    };

    document.querySelectorAll('script[data-track-event]').forEach((element) => send(readJson(element)));
    window.addEventListener('store:track', (event) => send(event.detail));

    if (!config.consentRequired || trackingConsent() === 'accepted') {
        start();
    } else {
        window.addEventListener('tracking-consent', (event) => {
            if (event.detail === 'accepted') {
                start();
            }
        });
    }
}
