<?php

namespace App\Enums;

enum UserRole: string
{
    case Owner = 'owner';
    case Staff = 'staff';
    case Customer = 'customer';

    /**
     * Get the label shown in the admin panel.
     */
    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Owner',
            self::Staff => 'Staff',
            self::Customer => 'Customer',
        };
    }

    /**
     * Determine whether this role may sign in to the admin panel.
     */
    public function canAccessAdmin(): bool
    {
        return $this !== self::Customer;
    }

    /**
     * Get the roles held by admin team members.
     *
     * @return list<self>
     */
    public static function staffRoles(): array
    {
        return [self::Owner, self::Staff];
    }
}
