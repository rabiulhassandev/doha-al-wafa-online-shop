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
    lightboxIndex: 0,
    lightboxOpener: null,
    lightboxOverflow: null,
    lightboxTouch: null,
    sizeGuideOpen: false,
    sizeUnit: 'cm',
    copied: false,

    init() {
        rememberViewed(this.productId);
        this.$nextTick(() => this.syncVideoPlayback());
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

        this.pauseVideos();
        this.color = colorSlug;
        this.imageIndex = 0;

        if (this.size && !this.isAvailable(this.size)) {
            this.size = null;
        }

        this.$nextTick(() => this.selectPhoto(0));
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

    get gallery() {
        return this.$root.querySelector(`[data-product-gallery="${CSS.escape(this.color)}"]`);
    },

    selectPhoto(index) {
        if (index < 0 || index >= this.current.mediaCount) {
            return;
        }

        const track = this.gallery.querySelector('[data-photo-track]');
        const slide = track.children[index].getBoundingClientRect();
        const bounds = track.getBoundingClientRect();
        const offset = (slide.left + slide.right - bounds.left - bounds.right) / 2;
        track.scrollBy({ left: offset });
        this.imageIndex = index;
        this.syncVideoPlayback();
        this.revealThumbnail();
    },

    onGalleryScroll(track, colorSlug) {
        if (colorSlug !== this.color || !track.clientWidth) {
            return;
        }

        const index = Math.min(this.current.mediaCount - 1, Math.round(Math.abs(track.scrollLeft) / track.clientWidth));
        if (index !== this.imageIndex) {
            this.imageIndex = index;
            this.syncVideoPlayback();
            this.revealThumbnail();
        }
    },

    revealThumbnail() {
        const strip = this.gallery.querySelector('[data-photo-thumbnails]');
        if (!strip) {
            return;
        }

        const button = strip.children[this.imageIndex].getBoundingClientRect();
        const bounds = strip.getBoundingClientRect();
        const offset = button.left < bounds.left ? button.left - bounds.left : Math.max(0, button.right - bounds.right);
        if (offset) {
            strip.scrollBy({ left: offset });
        }
    },

    openLightbox(index) {
        if (this.lightboxOpen || index < 0 || index >= this.current.images.length) {
            return;
        }

        this.pauseVideos();
        this.lightboxIndex = index;
        this.lightboxOpener = document.activeElement;
        this.lightboxOverflow = {
            root: document.documentElement.style.overflow,
            body: document.body.style.overflow,
        };
        document.documentElement.style.overflow = 'hidden';
        document.body.style.overflow = 'hidden';
        this.lightboxOpen = true;
        this.$nextTick(() => {
            if (this.lightboxOpen) {
                this.$refs.photoDialog.showModal();
                this.$refs.closePhotos.focus({ preventScroll: true });
                this.revealLightboxThumbnail();
            }
        });
    },

    closeLightbox() {
        if (!this.lightboxOpen) {
            return;
        }

        this.lightboxOpen = false;
        this.lightboxTouch = null;
        if (this.$refs.photoDialog.open) {
            this.$refs.photoDialog.close();
        }

        document.documentElement.style.overflow = this.lightboxOverflow.root;
        document.body.style.overflow = this.lightboxOverflow.body;
        this.lightboxOpener?.focus({ preventScroll: true });
    },

    selectLightboxPhoto(index) {
        if (index < 0 || index >= this.current.images.length) {
            return;
        }

        this.lightboxIndex = index;
        this.$nextTick(() => this.revealLightboxThumbnail());
    },

    lightboxArrow(step) {
        this.selectLightboxPhoto(this.lightboxIndex + (document.documentElement.dir === 'rtl' ? -step : step));
    },

    revealLightboxThumbnail() {
        const strip = this.$refs.lightboxThumbnails;
        const thumbnail = strip?.children[this.lightboxIndex];
        if (!thumbnail) {
            return;
        }

        const button = thumbnail.getBoundingClientRect();
        const bounds = strip.getBoundingClientRect();
        strip.scrollBy({ left: (button.left + button.right - bounds.left - bounds.right) / 2 });
    },

    startLightboxSwipe(event) {
        this.lightboxTouch = event.touches.length === 1
            ? { x: event.touches[0].clientX, y: event.touches[0].clientY }
            : null;
    },

    endLightboxSwipe(event) {
        if (!this.lightboxTouch) {
            return;
        }

        const deltaX = event.changedTouches[0].clientX - this.lightboxTouch.x;
        const deltaY = event.changedTouches[0].clientY - this.lightboxTouch.y;
        this.lightboxTouch = null;

        if (Math.abs(deltaX) > 60 && Math.abs(deltaX) > Math.abs(deltaY) * 2) {
            this.lightboxArrow(deltaX < 0 ? 1 : -1);
        }
    },

    pauseVideos() {
        this.$root.querySelectorAll('[data-video-slide] video').forEach((video) => video.pause());
    },

    syncVideoPlayback() {
        const activeGallery = this.gallery;

        this.$root.querySelectorAll('[data-video-slide]').forEach((slide) => {
            const video = slide.querySelector('video');
            const isActive = slide.closest('[data-product-gallery]') === activeGallery
                && Number(slide.dataset.videoSlide) === this.imageIndex
                && !this.lightboxOpen;

            if (isActive) {
                video.muted = true;
                video.play().catch(() => {
                    // Playback controls remain available when the browser blocks autoplay.
                });
            } else {
                video.pause();
            }
        });
    },

    get shareUrl() {
        const url = new URL(this.url);
        if (this.color) {
            url.searchParams.set('color', this.color);
        }

        if (this.size) {
            url.searchParams.set('size', this.size);
        }

        return url.toString();
    },

    get whatsAppUrl() {
        const details = [this.current.name, this.sizeName(this.size)].filter(Boolean).join(', ');
        const text = (details ? this.messages.whatsApp : this.messages.whatsAppProduct)
            .replace(':product', this.productName)
            .replace(':details', details)
            .replace(':url', this.shareUrl);

        return `https://wa.me/${this.whatsApp}?text=${encodeURIComponent(text)}`;
    },

    updateAddress() {
        const url = new URL(window.location.href);
        if (this.color) {
            url.searchParams.set('color', this.color);
        } else {
            url.searchParams.delete('color');
        }

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
