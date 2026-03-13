<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\DataTransferObjects\CreateUserData;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

interface UserRepositoryInterface
{
    public function create(CreateUserData $data): User;

    /** @return LengthAwarePaginator<User> */
    public function findActiveUsers(array $filters): LengthAwarePaginator;
}
