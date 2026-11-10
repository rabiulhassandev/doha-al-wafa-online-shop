/**
 * The visitor's answer about ad trackers, kept in a cookie the server can read too.
 * Trackers added later listen for the "tracking-consent" window event, or check trackingConsent().
 */
const COOKIE = 'tracking_consent';
const ONE_YEAR_SECONDS = 60 * 60 * 24 * 365;

export function trackingConsent() {
    const match = document.cookie.match(new RegExp(`(?:^|; )${COOKIE}=([^;]*)`));

    return match ? decodeURIComponent(match[1]) : null;
}

export default () => ({
    open: false,

    init() {
        this.open = trackingConsent() === null;
    },

    choose(answer) {
        document.cookie = `${COOKIE}=${answer}; max-age=${ONE_YEAR_SECONDS}; path=/; SameSite=Lax`;
        this.open = false;
        window.dispatchEvent(new CustomEvent('tracking-consent', { detail: answer }));
    },
});
