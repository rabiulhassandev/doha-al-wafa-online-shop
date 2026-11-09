<?php

namespace App\Enums;

/**
 * Where an order came from: the website, or a sale the team enters by hand.
 */
enum OrderChannel: string
{
    case Web = 'web';
    case WhatsApp = 'whatsapp';
    case Instagram = 'instagram';
    case Phone = 'phone';

    /**
     * Get the label shown in the admin panel.
     */
    public function label(): string
    {
        return match ($this) {
            self::Web => 'Website',
            self::WhatsApp => 'WhatsApp',
            self::Instagram => 'Instagram',
            self::Phone => 'Phone call',
        };
    }

    /**
     * Get the channels the team can enter orders for, as select options keyed by value.
     *
     * @return array<string, string>
     */
    public static function manualOptions(): array
    {
        return collect(self::cases())
            ->reject(fn (self $channel): bool => $channel === self::Web)
            ->mapWithKeys(fn (self $channel): array => [$channel->value => $channel->label()])
            ->all();
    }
}
