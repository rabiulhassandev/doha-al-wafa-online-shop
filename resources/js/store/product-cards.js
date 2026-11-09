import { recentlyViewedIds } from './recently-viewed';

/**
 * Loads server-rendered product cards for IDs kept in the browser (wishlist or recently viewed).
 */
export default ({ url, source, exclude = null, limit = 8 }) => ({
    loading: true,
    empty: false,

    async init() {
        const ids = (source === 'wishlist' ? this.$store.wishlist.ids : recentlyViewedIds())
            .filter((id) => id !== exclude)
            .slice(0, limit);

        if (ids.length === 0) {
            this.finish(true);

            return;
        }

        try {
            const response = await fetch(`${url}?ids=${ids.join(',')}`, { headers: { Accept: 'text/html' } });
            this.$refs.grid.innerHTML = response.ok ? await response.text() : '';
        } catch {
            this.$refs.grid.innerHTML = '';
        }

        this.finish(this.$refs.grid.children.length === 0);
    },

    finish(empty) {
        this.empty = empty;
        this.loading = false;
    },
});
