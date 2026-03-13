<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;

/**
 * Used exclusively for the POST /api/users 201 response.
 *
 * Extends UserResource and adds email so the caller can confirm
 * the address they registered with. Email is intentionally absent
 * from UserResource (the list view) to avoid bulk-exposing PII.
 */
class UserCreatedResource extends UserResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return array_merge(parent::toArray($request), [
            'email' => $this->email,
        ]);
    }
}
