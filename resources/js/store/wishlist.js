import { readIds, writeIds } from './local-list';

const KEY = 'wishlist';
const LIMIT = 50;

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

/**
 * The wishlist, shared by every heart button on the page through Alpine.store('wishlist').
 *
 * It lives in the browser. For a signed-in shopper it also merges with the wishlist saved on the
 * account (handed over by the #wishlist-account element) and saves every change there, so it
 * follows them to other devices.
 */
export default () => ({
    ids: readIds(KEY),
    syncUrl: null,

    init() {
        const account = document.getElementById('wishlist-account');

        if (!account) {
            return;
        }

        this.syncUrl = account.dataset.url;

        let saved = [];

        try {
            saved = JSON.parse(account.dataset.ids ?? '[]').filter(Number.isInteger);
        } catch {
            saved = [];
        }

        const merged = [...new Set([...this.ids, ...saved])].slice(0, LIMIT);
        const changed = merged.length !== saved.length || merged.some((id) => !saved.includes(id));

        this.ids = merged;
        writeIds(KEY, merged);

        if (changed) {
            this.save();
        }
    },

    has(id) {
        return this.ids.includes(id);
    },

    toggle(id) {
        this.ids = this.has(id) ? this.ids.filter((savedId) => savedId !== id) : [id, ...this.ids].slice(0, LIMIT);
        writeIds(KEY, this.ids);
        this.save();
    },

    /**
     * Save the list on the account. When it fails, the browser keeps it and the next change sends it again.
     */
    async save() {
        if (!this.syncUrl) {
            return;
        }

        try {
            await fetch(this.syncUrl, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken() },
                body: JSON.stringify({ ids: this.ids }),
            });
        } catch {
            // Offline or the session ended; the list stays in the browser.
        }
    },
});
