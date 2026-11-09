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
    case WalkIn = 'walk_in';

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
            self::WalkIn => 'Walk-in',
        };
    }
}
