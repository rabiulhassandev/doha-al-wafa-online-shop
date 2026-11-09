/**
 * The slide-in bag, shared as Alpine.store('cart'). The server renders the bag's contents;
 * this store only fetches them and sends changes. URLs and the starting count come from the
 * #cart-drawer element.
 */
function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

async function firstErrorMessage(response) {
    try {
        const data = await response.json();

        return Object.values(data.errors ?? {})[0]?.[0] ?? data.message ?? null;
    } catch {
        return null;
    }
}

export default () => ({
    open: false,
    loading: false,
    html: '',
    count: 0,
    drawerUrl: null,
    addUrl: null,

    init() {
        const drawer = document.getElementById('cart-drawer');

        this.count = Number(drawer?.dataset.count ?? 0);
        this.drawerUrl = drawer?.dataset.drawerUrl ?? null;
        this.addUrl = drawer?.dataset.addUrl ?? null;
    },

    async show() {
        this.open = true;
        await this.refresh();
    },

    hide() {
        this.open = false;
    },

    async refresh() {
        if (!this.drawerUrl) {
            return;
        }

        this.loading = true;

        try {
            const response = await fetch(this.drawerUrl, { headers: { Accept: 'text/html' } });
            this.html = await response.text();
        } finally {
            this.loading = false;
        }
    },

    /**
     * Add a color and size, then open the bag. Resolves to { ok, message }.
     */
    async add(variantId, quantity = 1) {
        const body = new FormData();
        body.append('variant_id', variantId);
        body.append('quantity', quantity);

        try {
            const response = await fetch(this.addUrl, {
                method: 'POST',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken() },
                body,
            });

            if (!response.ok) {
                return { ok: false, message: await firstErrorMessage(response) };
            }

            this.apply(await response.json());
            this.open = true;

            return { ok: true };
        } catch {
            return { ok: false, message: null };
        }
    },

    /**
     * Send a form from inside the bag (quantity or remove) and show the updated bag.
     */
    async submit(form) {
        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        this.loading = true;

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: { Accept: 'application/json' },
                body: new FormData(form),
            });

            if (response.ok) {
                this.apply(await response.json());
            } else {
                await this.refresh();
            }
        } catch {
            form.submit();
        } finally {
            this.loading = false;
        }
    },

    apply(data) {
        this.count = data.count;
        this.html = data.drawer;
    },
});
