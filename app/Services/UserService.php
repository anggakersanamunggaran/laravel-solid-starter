<?php

declare(strict_types=1);

namespace App\Services;

use App\Mail\AdminNewUserMail;
use App\Mail\WelcomeUserMail;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Mail;

class UserService
{
    public function __construct(
        private readonly UserRepository $userRepository
    ) {}

    /**
     * Create a new user and dispatch welcome and admin notification emails
     * onto the queue. Mail is a side-effect of creation, not business logic,
     * so it belongs here in the Service rather than the Repository or Controller.
     *
     * @param  array<string, mixed>  $data
     */
    public function createUser(array $data): User
    {
        $user = $this->userRepository->create($data);

        Mail::to($user->email)->queue(new WelcomeUserMail($user));
        Mail::to(config('mail.admin_address', 'admin@example.com'))->queue(new AdminNewUserMail($user));

        return $user;
    }

    /**
     * Return a paginated, filtered list of active users.
     *
     * The can_edit flag is intentionally NOT resolved here — that is a
     * presentation-time concern resolved in the Controller via Gate/Policy,
     * keeping authorization checks out of the Service layer.
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<User>
     */
    public function getActiveUsers(array $filters): LengthAwarePaginator
    {
        return $this->userRepository->findActiveUsers($filters);
    }
}
