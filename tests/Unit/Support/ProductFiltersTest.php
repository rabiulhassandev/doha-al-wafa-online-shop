<?php

use App\Support\ProductFilters;
use Illuminate\Http\Request;

it('reads comma lists and form arrays of colors and sizes', function () {
    $fromLink = ProductFilters::fromRequest(Request::create('/', 'GET', ['color' => 'navy,olive', 'size' => '56']));
    $fromForm = ProductFilters::fromRequest(Request::create('/', 'GET', ['color' => ['navy', 'olive'], 'size' => ['56']]));

    expect($fromLink->colors)->toBe(['navy', 'olive']);
    expect($fromForm->colors)->toBe(['navy', 'olive']);
    expect($fromForm->sizes)->toBe(['56']);
});

it('ignores values that are not slugs', function () {
    $filters = ProductFilters::fromRequest(Request::create('/', 'GET', ['color' => ['navy', "' OR 1=1", ['nested']], 'size' => 'XL!']));

    expect($filters->colors)->toBe(['navy']);
    expect($filters->sizes)->toBe([]);
});

it('reads prices in riyals as dirhams and ignores invalid ones', function () {
    $filters = ProductFilters::fromRequest(Request::create('/', 'GET', ['min' => '100', 'max' => 'cheap']));

    expect($filters->minPrice)->toBe(10000);
    expect($filters->maxPrice)->toBeNull();
});

it('falls back to the featured order for an unknown sort', function () {
    expect(ProductFilters::fromRequest(Request::create('/', 'GET', ['sort' => 'random']))->sort)->toBe('featured');
});

it('builds short query strings and removes single filters', function () {
    $filters = new ProductFilters(colors: ['navy', 'olive'], sizes: ['56'], minPrice: 10000, inStockOnly: true, sort: 'newest');

    expect($filters->toQuery())->toBe(['color' => 'navy,olive', 'size' => '56', 'min' => '100', 'in_stock' => '1', 'sort' => 'newest']);
    expect($filters->without('color', 'navy')->toQuery())->toBe(['color' => 'olive', 'size' => '56', 'min' => '100', 'in_stock' => '1', 'sort' => 'newest']);
    expect($filters->activeCount())->toBe(5);
});
