<?php

declare(strict_types=1);

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class CreateUserRequest extends FormRequest
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
     * Validation rules for creating a user.
     * Rules will be populated in the feature implementation branch.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email'    => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'name'     => ['required', 'string', 'between:3,50'],
        ];
    }
}
