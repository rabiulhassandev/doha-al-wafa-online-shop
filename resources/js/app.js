import Alpine from 'alpinejs';
import cart from './store/cart';
import checkoutForm, { forgetCheckoutDraft } from './store/checkout-form';
import consentBar from './store/consent-bar';
import productCards from './store/product-cards';
import productListing from './store/product-listing';
import productPage from './store/product-page';
import searchBox from './store/search-box';
import wishlist from './store/wishlist';

Alpine.store('wishlist', wishlist());
Alpine.store('cart', cart());

// The phone menu sits outside the header (see the store layout), so its state is shared here.
Alpine.store('mobileMenu', {
    open: false,

    show() {
        this.open = true;
    },

    hide() {
        this.open = false;
    },
});

Alpine.data('checkoutForm', checkoutForm);
Alpine.data('consentBar', consentBar);
// The thank-you page: the order went through, so the checkout draft is no longer needed.
Alpine.data('orderPlaced', () => ({
    init() {
        forgetCheckoutDraft();
    },
}));
Alpine.data('productCards', productCards);
Alpine.data('productListing', productListing);
Alpine.data('productPage', productPage);
Alpine.data('searchBox', searchBox);

window.Alpine = Alpine;

Alpine.start();
