<?php

declare(strict_types=1);

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class GetUsersRequest extends FormRequest
{
    /**
     * All authenticated users are permitted to hit this endpoint.
     * Gate/Policy checks are handled inside the Service layer.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules for the user listing filters.
     * Rules will be populated in the feature implementation branch.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'search' => ['sometimes', 'nullable', 'string', 'max:100'],
            'page'   => ['sometimes', 'integer', 'min:1'],
            'sortBy' => ['sometimes', 'string', 'in:name,email,created_at'],
        ];
    }
}
