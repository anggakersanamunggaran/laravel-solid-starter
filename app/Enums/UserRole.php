<?php

declare(strict_types=1);

namespace App\Enums;

enum UserRole: string
{
    case Admin   = 'admin';
    case Manager = 'manager';
    case User    = 'user';

    /**
     * Returns a human-readable label for the role.
     */
    public function label(): string
    {
        return match($this) {
            UserRole::Admin   => 'Administrator',
            UserRole::Manager => 'Manager',
            UserRole::User    => 'User',
        };
    }

    /**
     * Returns all case values as a plain array — useful for validation rules.
     *
     * @return string[]
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
