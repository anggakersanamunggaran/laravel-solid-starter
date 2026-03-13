<?php

declare(strict_types=1);

namespace App\Actions;

use App\Contracts\Repositories\UserRepositoryInterface;
use App\DataTransferObjects\CreateUserData;
use App\Mail\AdminNewUserMail;
use App\Mail\WelcomeUserMail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

final class CreateUserAction
{
    public function __construct(
        private readonly UserRepositoryInterface $repository,
    ) {}

    public function execute(CreateUserData $data): User
    {
        $user = $this->repository->create($data);

        Mail::to($user->email)->queue(new WelcomeUserMail($user));
        Mail::to(config('mail.admin_address', 'admin@example.com'))
            ->queue(new AdminNewUserMail($user));

        return $user;
    }
}
