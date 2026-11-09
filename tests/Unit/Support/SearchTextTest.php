<?php

use App\Support\SearchText;

it('folds Arabic spelling variations into one form', function (string $input, string $expected) {
    expect(SearchText::normalize($input))->toBe($expected);
})->with([
    'alef with hamza above' => ['أحمر', 'احمر'],
    'alef with hamza below' => ['إسدال', 'اسدال'],
    'alef with madda' => ['آمنة', 'امنه'],
    'taa marbuta' => ['جلابية', 'جلابيه'],
    'alef maqsura' => ['مستشفى', 'مستشفي'],
    'diacritics' => ['جَلَّابِيَّة', 'جلابيه'],
    'tatweel' => ['جـلابـية', 'جلابيه'],
    'arabic digits' => ['مقاس ٥٦', 'مقاس 56'],
]);

it('lowercases English and tidies spaces', function () {
    expect(SearchText::normalize('  Navy   KAFTAN '))->toBe('navy kaftan');
});
