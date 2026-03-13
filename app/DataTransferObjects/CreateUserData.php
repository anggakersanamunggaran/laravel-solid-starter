<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

use App\Enums\UserRole;

final readonly class CreateUserData
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
        public UserRole $role = UserRole::User,
    ) {}

    public static function fromRequest(array $validated): self
    {
        return new self(
            name:     $validated['name'],
            email:    $validated['email'],
            password: $validated['password'],
            role:     UserRole::tryFrom($validated['role'] ?? '') ?? UserRole::User,
        );
    }
}
