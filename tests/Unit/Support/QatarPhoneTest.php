<?php

use App\Support\QatarPhone;

it('normalizes Qatar numbers to E.164', function (string $input, string $expected) {
    $phone = QatarPhone::tryParse($input);

    expect($phone?->e164())->toBe($expected);
})->with([
    'local mobile' => ['55551234', '+97455551234'],
    'local mobile with spaces' => ['5555 1234', '+97455551234'],
    'international with plus' => ['+974 5555 1234', '+97455551234'],
    'international with 00' => ['00974 3312 3456', '+97433123456'],
    'country code without plus' => ['97466123456', '+97466123456'],
    'landline' => ['4444 1234', '+97444441234'],
    'eastern arabic digits' => ['٥٥٥٥١٢٣٤', '+97455551234'],
    'persian digits' => ['۷۷۱۲۳۴۵۶', '+97477123456'],
]);

it('returns null for numbers that are not Qatar numbers', function (?string $input) {
    expect(QatarPhone::tryParse($input))->toBeNull();
})->with([
    'empty' => [''],
    'null' => [null],
    'seven digits' => ['5555123'],
    'nine digits' => ['555512345'],
    'unused first digit' => ['85551234'],
    'another country' => ['+971 50 123 4567'],
    'letters' => ['phone'],
]);

it('detects mobile numbers by their first digit', function (string $input, bool $isMobile) {
    expect(QatarPhone::tryParse($input)->isMobile())->toBe($isMobile);
})->with([
    'starts with 3' => ['33123456', true],
    'starts with 5' => ['55123456', true],
    'starts with 6' => ['66123456', true],
    'starts with 7' => ['77123456', true],
    'landline starting with 4' => ['44123456', false],
]);

it('formats the number for display', function () {
    expect(QatarPhone::tryParse('55551234')->formatted())->toBe('+974 5555 1234');
});

it('formats the number for WhatsApp links', function () {
    expect(QatarPhone::tryParse('+974 5555 1234')->whatsAppNumber())->toBe('97455551234');
});
