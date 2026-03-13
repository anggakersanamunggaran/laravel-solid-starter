<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

final class DuplicateEmailException extends RuntimeException
{
    public function __construct(string $email)
    {
        parent::__construct("The email address [{$email}] is already registered.");
    }
}
