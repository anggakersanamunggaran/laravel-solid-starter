<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Repositories\UserRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class UserService
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository
    ) {}

    /**
     * Return a paginated, filtered list of active users.
     *
     * The can_edit flag is intentionally NOT resolved here — that is a
     * presentation-time concern resolved in the Controller via Gate/Policy,
     * keeping authorization checks out of the Service layer.
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<\App\Models\User>
     */
    public function getActiveUsers(array $filters): LengthAwarePaginator
    {
        return $this->userRepository->findActiveUsers($filters);
    }
}
