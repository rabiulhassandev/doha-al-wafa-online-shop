<?php

namespace App\Enums;

enum CollectionStatus: string
{
    case Live = 'live';
    case Scheduled = 'scheduled';
    case Ended = 'ended';
    case Hidden = 'hidden';

    /**
     * Get the label shown in the admin panel.
     */
    public function label(): string
    {
        return match ($this) {
            self::Live => 'Live',
            self::Scheduled => 'Scheduled',
            self::Ended => 'Ended',
            self::Hidden => 'Hidden',
        };
    }

    /**
     * Get the admin badge color for the status.
     */
    public function badgeColor(): string
    {
        return match ($this) {
            self::Live => 'success',
            self::Scheduled => 'maroon',
            self::Ended, self::Hidden => 'neutral',
        };
    }
}
