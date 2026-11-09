<?php

use App\Models\Collection;

it('lists the products in a live collection', function () {
    $eid = Collection::factory()->create(['slug' => 'eid', 'name_en' => 'Eid collection']);
    $inEid = listedProduct(['name_en' => 'Eid kaftan']);
    $inEid->collections()->attach($eid);
    listedProduct(['name_en' => 'Everyday jelabiya']);

    $this->get(route('collection.show', ['locale' => 'en', 'collection' => 'eid']))
        ->assertOk()
        ->assertSee('Eid collection')
        ->assertSee('Eid kaftan')
        ->assertDontSee('Everyday jelabiya');
});

it('returns 404 for collections that are not live', function (Closure $makeCollection) {
    $collection = $makeCollection();

    $this->get(route('collection.show', ['locale' => 'en', 'collection' => $collection]))->assertNotFound();
})->with([
    'hidden' => fn () => Collection::factory()->hidden()->create(),
    'scheduled' => fn () => Collection::factory()->scheduled()->create(),
    'ended' => fn () => Collection::factory()->ended()->create(),
]);
