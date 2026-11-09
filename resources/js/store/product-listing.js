/**
 * "Load more" for product listings: fetches the next page and appends its cards.
 * Without JavaScript the button is a normal link to the next page.
 */
export default (nextUrl) => ({
    nextUrl,
    loading: false,

    async loadMore() {
        if (this.loading || !this.nextUrl) {
            return;
        }

        this.loading = true;

        try {
            const response = await fetch(this.nextUrl, { headers: { Accept: 'text/html' } });
            const page = new DOMParser().parseFromString(await response.text(), 'text/html');

            page.querySelectorAll('#product-grid > *').forEach((card) => {
                this.$refs.grid.append(document.importNode(card, true));
            });

            this.nextUrl = page.querySelector('[data-load-more]')?.getAttribute('href') ?? null;
        } catch {
            window.location.href = this.nextUrl;
        } finally {
            this.loading = false;
        }
    },
});
