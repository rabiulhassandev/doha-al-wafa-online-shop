<?php

namespace App\Services;

use App\Models\Order;
use App\Support\Money;
use App\Support\QatarPhone;

/**
 * Ready-written WhatsApp messages the team sends customers about their order, in the customer's language.
 * The owner can reword each one in Settings; the built-in wording is used otherwise.
 */
class WhatsAppMessages
{
    /**
     * The built-in messages, keyed by the name of the settings that can replace them.
     */
    public const DEFAULTS = [
        'confirm' => [
            'label' => 'Ask to confirm the order',
            'text' => 'Hello :name, this is :store. We received your order :number for :total, paid in cash on delivery. Can you confirm it, please?',
        ],
        'on_the_way' => [
            'label' => 'Say it is on the way',
            'text' => 'Hello :name, your order :number from :store is on its way to you today. Please have :total ready in cash.',
        ],
        'missed_delivery' => [
            'label' => 'Arrange a missed delivery',
            'text' => 'Hello :name, our driver could not reach you with your order :number from :store. When is a good time to deliver it?',
        ],
    ];

    public function __construct(private readonly Settings $settings) {}

    /**
     * Get each message for an order as a WhatsApp link that opens a chat with the text filled in.
     *
     * @return list<array{label: string, url: string}>
     */
    public function forOrder(Order $order): array
    {
        $phone = QatarPhone::tryParse($order->customer_phone);

        if ($phone === null) {
            return [];
        }

        $replace = [
            ':name' => $order->customer_name,
            ':number' => $order->number,
            ':store' => (string) $this->settings->get('store_name'),
            ':total' => Money::format($order->grand_total, $order->locale),
        ];

        return collect(self::DEFAULTS)
            ->map(fn (array $message, string $key): array => [
                'label' => $message['label'],
                'url' => 'https://wa.me/'.$phone->whatsAppNumber().'?text='.rawurlencode(strtr($this->template($key, $order->locale), $replace)),
            ])
            ->values()
            ->all();
    }

    /**
     * Get the built-in wording of a message in a language, with its placeholders.
     */
    public static function defaultText(string $key, string $locale): string
    {
        return __(self::DEFAULTS[$key]['text'], [], $locale);
    }

    private function template(string $key, string $locale): string
    {
        $custom = $this->settings->get("whatsapp_message_{$key}_{$locale}");

        return filled($custom) ? $custom : self::defaultText($key, $locale);
    }
}
