<?php

it('renders cards in the order the IDs were saved, skipping hidden products', function () {
    $first = listedProduct(['name_en' => 'First saved']);
    $hidden = listedProduct(['name_en' => 'Now hidden', 'status' => 'archived']);
    $second = listedProduct(['name_en' => 'Second saved']);

    $this->get(route('product-cards', ['locale' => 'en', 'ids' => "{$second->id},{$hidden->id},{$first->id},abc,999"]))
        ->assertOk()
        ->assertSeeInOrder(['Second saved', 'First saved'])
        ->assertDontSee('Now hidden')
        ->assertDontSee('<html', false);
});

it('renders nothing without IDs', function () {
    listedProduct(['name_en' => 'Anything']);

    $this->get(route('product-cards', ['locale' => 'en']))
        ->assertOk()
        ->assertDontSee('Anything');
});
