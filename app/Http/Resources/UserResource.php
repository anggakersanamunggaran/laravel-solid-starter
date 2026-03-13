<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the User model into an array suitable for API responses.
     *
     * email         — omitted from list responses; present only on POST /api/users
     *                 (create) so the caller can confirm what was registered.
     *                 Bulk-exposing emails in a paginated list is a privacy risk.
     * orders_count  — only included when the relationship was eager-loaded via
     *                 withCount(), keeping the contract explicit.
     * can_edit      — only included when the Controller has resolved and stamped
     *                 the value on the model instance (dynamic property set
     *                 after Policy check). Absent from POST /users responses.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'name'         => $this->name,
            'role'         => $this->role,
            'created_at'   => $this->created_at->toIso8601String(),
            'orders_count' => $this->whenNotNull($this->orders_count),
            'can_edit'     => $this->when(isset($this->resource->can_edit), $this->resource->can_edit),
        ];
    }
}
