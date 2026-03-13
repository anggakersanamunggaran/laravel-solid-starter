<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // 1 Admin
        User::factory()->admin()->create([
            'name'  => 'Super Admin',
            'email' => 'admin@example.com',
        ]);

        // 2 Managers
        User::factory()->manager()->create([
            'name'  => 'Alice Manager',
            'email' => 'alice@example.com',
        ]);

        User::factory()->manager()->create([
            'name'  => 'Bob Manager',
            'email' => 'bob@example.com',
        ]);

        // 10 Regular users
        User::factory()->count(10)->create();

        // 3 Inactive users
        User::factory()->inactive()->count(3)->create();
    }
}
