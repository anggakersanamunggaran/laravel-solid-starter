<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * Bind interface → implementation pairs here when the Repository Pattern
     * is expanded to use contracts (see architecture decision record).
     */
    public function register(): void
    {
        $this->app->bind(
            \App\Contracts\Repositories\UserRepositoryInterface::class,
            \App\Repositories\UserRepository::class,
        );
    }

    /**
     * Bootstrap any application services.
     *
     * Policy registration is done here rather than via the $policies array on
     * AuthServiceProvider because Laravel 12 ships without a dedicated
     * AuthServiceProvider by default — Gate::policy() in boot() is the
     * idiomatic alternative.
     */
    public function boot(): void
    {
        Gate::policy(User::class, UserPolicy::class);
    }
}
