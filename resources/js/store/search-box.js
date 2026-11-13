const EMPTY_RESULTS = { products: [], categories: [], collections: [] };

/**
 * Search overlay with live suggestions while the shopper types.
 */
export default (suggestionsUrl) => ({
    open: false,
    query: '',
    loading: false,
    failed: false,
    searchedQuery: '',
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
        this.results = EMPTY_RESULTS;
        this.failed = false;

        if (query.length < 2) {
            this.loading = false;
            this.request = null;
            return;
        }

        const request = new AbortController();
        this.request = request;
        this.loading = true;

        try {
            const response = await fetch(`${suggestionsUrl}?q=${encodeURIComponent(query)}`, {
                headers: { Accept: 'application/json' },
                signal: request.signal,
            });
            const results = response.ok ? await response.json() : EMPTY_RESULTS;
            if (request === this.request && query === this.query.trim()) {
                this.results = { ...EMPTY_RESULTS, ...results };
                this.failed = !response.ok;
            }
        } catch (error) {
            if (request === this.request && error.name !== 'AbortError') {
                this.failed = true;
            }
        } finally {
            if (request === this.request) {
                this.loading = false;
                this.searchedQuery = query;
            }
        }
    },
});
