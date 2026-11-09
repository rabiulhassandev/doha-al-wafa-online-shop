<?php

it('renders the wishlist page, which loads saved items in the browser', function () {
    $this->get(route('wishlist', ['locale' => 'ar']))
        ->assertOk()
        ->assertSee('المفضلة')
        ->assertSee(str_replace('/', '\/', route('product-cards', ['locale' => 'ar'])), false);
});
