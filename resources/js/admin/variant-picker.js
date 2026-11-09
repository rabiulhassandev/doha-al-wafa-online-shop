/**
 * Order lines picker: search colors and sizes on sale and keep a quantity for each.
 * Adding one that is already listed raises its quantity instead.
 */
export default ({ searchUrl, inputName, lines = [] }) => ({
    query: '',
    results: [],
    searched: false,
    inputName,
    lines,

    async search() {
        const query = this.query.trim();

        if (query.length < 2) {
            this.results = [];
            this.searched = false;

            return;
        }

        try {
            const response = await fetch(`${searchUrl}?q=${encodeURIComponent(query)}`, { headers: { Accept: 'application/json' } });
            const data = await response.json();

            // Ignore answers to an older search the person has typed past.
            if (query === this.query.trim()) {
                this.results = data.variants ?? [];
                this.searched = true;
            }
        } catch {
            this.results = [];
        }
    },

    add(variant) {
        const line = this.lines.find((existing) => existing.id === variant.id);

        if (line) {
            line.quantity += 1;
        } else {
            this.lines.push({ ...variant, quantity: 1 });
        }

        this.query = '';
        this.results = [];
        this.searched = false;
    },

    remove(index) {
        this.lines.splice(index, 1);
    },
});
