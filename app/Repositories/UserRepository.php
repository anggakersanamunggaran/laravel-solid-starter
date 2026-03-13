<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class UserRepository
{
    public function __construct(
        private readonly User $model
    ) {}

    /**
     * Persist a new user record and return the created model instance.
     *
     * The password is hashed here at the persistence boundary so the Service
     * layer never needs to be aware of the hashing strategy — a clean
     * application of the Single Responsibility Principle.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): User
    {
        return $this->model->create([
            ...$data,
            'password' => bcrypt($data['password']),
        ])->fresh();
    }

    /**
     * Retrieve a paginated list of active users, optionally filtered by the
     * provided criteria.
     *
     * The query stays here in the Repository and never leaks into the Service
     * or Controller layers.
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<User>
     */
    public function findActiveUsers(array $filters): LengthAwarePaginator
    {
        return User::query()
            ->where('active', true)
            ->when(
                $filters['search'] ?? null,
                fn ($q, $s) => $q->where(function ($q) use ($s) {
                    $q->where('name', 'like', "%{$s}%")
                      ->orWhere('email', 'like', "%{$s}%");
                })
            )
            ->withCount('orders')
            ->orderBy($filters['sortBy'] ?? 'created_at')
            ->paginate(15);
    }
}
