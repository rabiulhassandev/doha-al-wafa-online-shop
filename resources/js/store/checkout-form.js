/**
 * Checkout form: the delivery fee and total follow the chosen area, the shopper can share their
 * location, and a draft is kept in the browser because in-app browsers often reload pages.
 * The server works out every amount again when the order is placed.
 */
const DRAFT_KEY = 'checkout-draft';
const DRAFT_MAX_AGE_MS = 7 * 24 * 60 * 60 * 1000;
const DRAFT_FIELDS = ['name', 'phone', 'email', 'delivery_zone_id', 'zone_number', 'street_number', 'building_number', 'unit', 'landmark', 'note'];

function readDraft() {
    try {
        const draft = JSON.parse(window.localStorage.getItem(DRAFT_KEY) ?? 'null');

        if (!draft || typeof draft.fields !== 'object' || Date.now() - draft.savedAt > DRAFT_MAX_AGE_MS) {
            return null;
        }

        return draft.fields;
    } catch {
        return null;
    }
}

/**
 * Drop the saved draft once the order is placed.
 */
export function forgetCheckoutDraft() {
    try {
        window.localStorage.removeItem(DRAFT_KEY);
    } catch {
        // Storage is unavailable, so there is no draft to drop.
    }
}

export default (data) => ({
    ...data,
    submitting: false,
    locating: false,
    locationError: null,

    init() {
        if (this.restoreDraft) {
            this.applyDraft();
        }

        this.$watch('zoneId', () => {
            if (!this.dates.some((day) => day.value === this.preferredDate)) {
                this.preferredDate = '';
            }
        });
    },

    get zone() {
        return this.zones[this.zoneId] ?? null;
    },

    get dates() {
        return this.zone?.dates ?? [];
    },

    get deliveryText() {
        return this.zone?.feeText ?? this.messages.chooseArea;
    },

    get totalText() {
        return this.zone?.totalText ?? this.subtotalText;
    },

    /**
     * Fill empty fields from the saved draft. Fields the page already filled are left alone.
     */
    applyDraft() {
        const fields = readDraft();

        if (!fields) {
            return;
        }

        for (const name of DRAFT_FIELDS) {
            const field = this.$root.elements.namedItem(name);
            const value = fields[name];

            if (!field || typeof value !== 'string' || value === '' || field.value !== '') {
                continue;
            }

            if (name === 'delivery_zone_id') {
                if (value in this.zones) {
                    this.zoneId = value;
                }

                continue;
            }

            field.value = value;
        }
    },

    saveDraft() {
        const fields = {};

        for (const name of DRAFT_FIELDS) {
            const field = this.$root.elements.namedItem(name);

            if (field) {
                fields[name] = field.value;
            }
        }

        try {
            window.localStorage.setItem(DRAFT_KEY, JSON.stringify({ savedAt: Date.now(), fields }));
        } catch {
            // Storage is unavailable; the form just won't be remembered.
        }
    },

    /**
     * Send the form once, even when "Place order" is tapped twice.
     */
    submit(event) {
        if (this.submitting) {
            event.preventDefault();

            return;
        }

        this.submitting = true;
    },

    locate() {
        if (!('geolocation' in navigator)) {
            this.locationError = this.messages.locationUnsupported;

            return;
        }

        this.locating = true;
        this.locationError = null;

        navigator.geolocation.getCurrentPosition(
            (position) => {
                this.latitude = position.coords.latitude.toFixed(6);
                this.longitude = position.coords.longitude.toFixed(6);
                this.locating = false;
            },
            () => {
                this.locationError = this.messages.locationFailed;
                this.locating = false;
            },
            { enableHighAccuracy: true, timeout: 15000, maximumAge: 60000 },
        );
    },

    clearLocation() {
        this.latitude = '';
        this.longitude = '';
    },
});
