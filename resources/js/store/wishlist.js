import { readIds, writeIds } from './local-list';

const KEY = 'wishlist';
const LIMIT = 50;

/**
 * The wishlist, shared by every heart button on the page through Alpine.store('wishlist').
 */
export default () => ({
    ids: readIds(KEY),

    has(id) {
        return this.ids.includes(id);
    },

    toggle(id) {
        this.ids = this.has(id) ? this.ids.filter((savedId) => savedId !== id) : [id, ...this.ids].slice(0, LIMIT);
        writeIds(KEY, this.ids);
    },
});
