<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\UserRepositoryInterface;
use App\DataTransferObjects\CreateUserData;
use App\Exceptions\DuplicateEmailException;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Pagination\LengthAwarePaginator;

class UserRepository implements UserRepositoryInterface
{
    public function __construct(
        private readonly User $model
    ) {}

    /**
     * Persist a new user record and return the created model instance.
     *
     * The password is hashed here at the persistence boundary so the Action
     * layer never needs to be aware of the hashing strategy — a clean
     * application of the Single Responsibility Principle.
     */
    public function create(CreateUserData $data): User
    {
        try {
            return $this->model->create([
                'name'     => $data->name,
                'email'    => $data->email,
                'password' => bcrypt($data->password),
                'role'     => $data->role,
            ])->fresh();
        } catch (QueryException $e) {
            if ($e->errorInfo[1] === 1062) {
                throw new DuplicateEmailException($data->email);
            }

            throw $e;
        }
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
            ->orderBy($filters['sortBy'] ?? 'created_at', $filters['sortOrder'] ?? 'asc')
            ->paginate(15);
    }
}
