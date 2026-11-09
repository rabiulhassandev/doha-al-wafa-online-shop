<?php

use App\Models\Size;

it('returns the attribute in the requested language', function () {
    $size = Size::factory()->make(['name_en' => 'Free size', 'name_ar' => 'مقاس واحد']);

    expect($size->translated('name', 'ar'))->toBe('مقاس واحد');
});

it('falls back to English when the translation is empty', function () {
    $size = Size::factory()->make(['name_en' => '56', 'name_ar' => null]);

    expect($size->translated('name', 'ar'))->toBe('56');
});

it('uses the current language by default', function () {
    app()->setLocale('ar');
    $size = Size::factory()->make(['name_en' => 'Free size', 'name_ar' => 'مقاس واحد']);

    expect($size->translated('name'))->toBe('مقاس واحد');
});
