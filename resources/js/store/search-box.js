const EMPTY_RESULTS = { products: [], categories: [] };

/**
 * Search overlay with live suggestions while the shopper types.
 */
export default (suggestionsUrl) => ({
    open: false,
    query: '',
    loading: false,
    results: EMPTY_RESULTS,
    request: null,

    show() {
        this.open = true;
        this.$nextTick(() => this.$refs.input.focus());
    },

    close() {
        this.open = false;
    },

    async search() {
        const query = this.query.trim();

        this.request?.abort();

        if (query.length < 2) {
            this.results = EMPTY_RESULTS;
            this.loading = false;

            return;
        }

        this.request = new AbortController();
        this.loading = true;

        try {
            const response = await fetch(`${suggestionsUrl}?q=${encodeURIComponent(query)}`, {
                headers: { Accept: 'application/json' },
                signal: this.request.signal,
            });
            this.results = response.ok ? await response.json() : EMPTY_RESULTS;
            this.loading = false;
        } catch (error) {
            if (error.name !== 'AbortError') {
                this.results = EMPTY_RESULTS;
                this.loading = false;
            }
        }
    },
});
