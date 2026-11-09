import { rememberViewed } from './recently-viewed';

/**
 * Product page: color and size choice, photo galleries, stock message, sharing and WhatsApp.
 *
 * Every color's gallery is in the page already, so switching color is instant. The address bar
 * keeps "?color=…&size=…" up to date so a copied or shared link opens the same choice.
 */
export default (data) => ({
    ...data,
    imageIndex: 0,
    quantity: 1,
    sizeError: false,
    adding: false,
    notice: null,
    lightboxOpen: false,
    sizeGuideOpen: false,
    sizeUnit: 'cm',
    copied: false,

    init() {
        rememberViewed(this.productId);
    },

    get current() {
        return this.colors[this.color];
    },

    get selected() {
        return this.size ? (this.current.sizes[this.size] ?? null) : null;
    },

    isOffered(sizeSlug) {
        return sizeSlug in this.current.sizes;
    },

    isAvailable(sizeSlug) {
        return this.current.sizes[sizeSlug]?.available === true;
    },

    sizeName(sizeSlug) {
        return this.sizes.find((size) => size.slug === sizeSlug)?.name ?? null;
    },

    get colorInStock() {
        return Object.values(this.current.sizes).some((size) => size.available);
    },

    get statusText() {
        if (!this.selected) {
            return this.colorInStock ? '' : this.messages.colorSoldOut;
        }

        if (!this.selected.available) {
            return this.messages.sizeSoldOut;
        }

        return this.selected.lowStock
            ? this.messages.onlyLeft.replace(':count', this.selected.lowStock)
            : this.messages.inStock;
    },

    get statusTone() {
        return !this.selected || !this.selected.available || this.selected.lowStock ? 'warning' : 'good';
    },

    selectColor(colorSlug) {
        if (colorSlug === this.color) {
            return;
        }

        this.color = colorSlug;
        this.imageIndex = 0;

        if (this.size && !this.isAvailable(this.size)) {
            this.size = null;
        }

        this.$nextTick(() => document.querySelector(`[data-gallery="${colorSlug}"]`)?.scrollTo({ left: 0 }));
        this.updateAddress();
    },

    selectSize(sizeSlug) {
        if (!this.isAvailable(sizeSlug)) {
            return;
        }

        this.size = sizeSlug;
        this.sizeError = false;
        this.notice = null;
        this.updateAddress();
    },

    changeQuantity(step) {
        this.quantity = Math.min(this.maxQuantity, Math.max(1, this.quantity + step));
    },

    /**
     * Make sure a size is chosen before adding or ordering; otherwise point the shopper to the sizes.
     */
    requireSize() {
        if (this.selected?.available) {
            return true;
        }

        this.sizeError = true;
        document.getElementById('size-picker')?.scrollIntoView({ behavior: 'smooth', block: 'center' });

        return false;
    },

    async addToBag() {
        if (!this.requireSize() || this.adding) {
            return;
        }

        this.adding = true;
        this.notice = null;
        const result = await this.$store.cart.add(this.selected.variantId, this.quantity);
        this.adding = false;

        if (!result.ok) {
            this.notice = result.message ?? this.messages.addFailed;
        }
    },

    onGalleryScroll(gallery) {
        this.imageIndex = Math.round(Math.abs(gallery.scrollLeft) / gallery.clientWidth);
    },

    openLightbox(index) {
        this.lightboxOpen = true;
        this.$nextTick(() => this.$refs.lightboxTrack.children[index]?.scrollIntoView({ inline: 'center' }));
    },

    get shareUrl() {
        const url = new URL(this.url);
        url.searchParams.set('color', this.color);

        if (this.size) {
            url.searchParams.set('size', this.size);
        }

        return url.toString();
    },

    get whatsAppUrl() {
        const details = [this.current.name, this.sizeName(this.size)].filter(Boolean).join(', ');
        const text = this.messages.whatsApp
            .replace(':product', this.productName)
            .replace(':details', details)
            .replace(':url', this.shareUrl);

        return `https://wa.me/${this.whatsApp}?text=${encodeURIComponent(text)}`;
    },

    updateAddress() {
        const url = new URL(window.location.href);
        url.searchParams.set('color', this.color);

        if (this.size) {
            url.searchParams.set('size', this.size);
        } else {
            url.searchParams.delete('size');
        }

        window.history.replaceState(window.history.state, '', url);
    },

    async share() {
        if (navigator.share) {
            try {
                await navigator.share({ title: this.productName, url: this.shareUrl });
            } catch {
                // The shopper closed the share sheet.
            }

            return;
        }

        try {
            await navigator.clipboard.writeText(this.shareUrl);
            this.copied = true;
            setTimeout(() => (this.copied = false), 2000);
        } catch {
            window.prompt('', this.shareUrl);
        }
    },
});
