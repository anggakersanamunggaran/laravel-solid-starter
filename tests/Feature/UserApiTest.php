<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Mail\AdminNewUserMail;
use App\Mail\WelcomeUserMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Feature tests for the User API endpoints.
 *
 * Coverage:
 *   POST /api/users — create user (success, validation failures, mail dispatch)
 *   GET  /api/users — paginated active-user list (filtering, sorting, can_edit)
 *
 * Uses RefreshDatabase so every test gets a clean, migrated schema.
 */
class UserApiTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // POST /api/users
    // -------------------------------------------------------------------------

    /** @test */
    public function test_create_user_successfully(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/users', [
            'name'     => 'Jane Doe',
            'email'    => 'jane@example.com',
            'password' => 'secret123',
        ]);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'id',
                     'name',
                     'email',
                     'role',
                     'created_at',
                 ])
                 ->assertJsonFragment([
                     'name'  => 'Jane Doe',
                     'email' => 'jane@example.com',
                 ]);

        $this->assertDatabaseHas('users', ['email' => 'jane@example.com']);
    }

    /** @test */
    public function test_create_user_fails_with_invalid_email(): void
    {
        $response = $this->postJson('/api/users', [
            'name'     => 'Bad Email User',
            'email'    => 'not-an-email',
            'password' => 'secret123',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function test_create_user_fails_with_short_password(): void
    {
        $response = $this->postJson('/api/users', [
            'name'     => 'Short Pass',
            'email'    => 'short@example.com',
            'password' => '1234567',   // 7 chars — one below the 8-char minimum
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['password']);
    }

    /** @test */
    public function test_emails_are_dispatched_after_user_creation(): void
    {
        Mail::fake();

        $this->postJson('/api/users', [
            'name'     => 'Mail Test User',
            'email'    => 'mailtest@example.com',
            'password' => 'secret123',
        ])->assertStatus(201);

        Mail::assertQueued(WelcomeUserMail::class, function (WelcomeUserMail $mail) {
            return $mail->user->email === 'mailtest@example.com';
        });

        Mail::assertQueued(AdminNewUserMail::class, function (AdminNewUserMail $mail) {
            return $mail->user->email === 'mailtest@example.com';
        });
    }

    // -------------------------------------------------------------------------
    // GET /api/users
    // -------------------------------------------------------------------------

    /** @test */
    public function test_get_users_returns_paginated_active_users(): void
    {
        $active   = User::factory()->count(3)->create(['active' => true]);
        $inactive = User::factory()->count(2)->create(['active' => false]);

        $auth = $active->first();

        $response = $this->actingAs($auth, 'sanctum')
                         ->getJson('/api/users');

        $response->assertStatus(200)
                 ->assertJsonStructure(['page', 'users'])
                 ->assertJsonPath('page', 1);

        // Only active users should appear — confirm none of the inactive IDs are present
        $returnedIds = collect($response->json('users'))->pluck('id')->all();
        foreach ($inactive as $inactiveUser) {
            $this->assertNotContains($inactiveUser->id, $returnedIds);
        }

        // All 3 active users must be present
        foreach ($active as $activeUser) {
            $this->assertContains($activeUser->id, $returnedIds);
        }
    }

    /** @test */
    public function test_search_filter_works(): void
    {
        $john  = User::factory()->create(['name' => 'John Smith', 'active' => true]);
        $alice = User::factory()->create(['name' => 'Alice Johnson', 'active' => true]);
        $bob   = User::factory()->create(['name' => 'Bob Williams', 'active' => true]);

        $auth = User::factory()->create(['active' => true]);

        $response = $this->actingAs($auth, 'sanctum')
                         ->getJson('/api/users?search=john');

        $response->assertStatus(200);

        $returnedIds = collect($response->json('users'))->pluck('id')->all();

        // "john" matches both "John Smith" (name) and "Alice Johnson" (name contains "john")
        $this->assertContains($john->id, $returnedIds);
        $this->assertContains($alice->id, $returnedIds);
        // "Bob Williams" contains no "john"
        $this->assertNotContains($bob->id, $returnedIds);
    }

    /** @test */
    public function test_sort_by_name_works(): void
    {
        // Create in reverse alphabetical order so default (created_at) ordering
        // would differ from name ordering, making the assertion meaningful.
        User::factory()->create(['name' => 'Zara Adams', 'active' => true]);
        User::factory()->create(['name' => 'Amy Baker',  'active' => true]);
        User::factory()->create(['name' => 'Mike Chen',  'active' => true]);

        $auth = User::factory()->create(['active' => true]);

        $response = $this->actingAs($auth, 'sanctum')
                         ->getJson('/api/users?sortBy=name');

        $response->assertStatus(200);

        $names = collect($response->json('users'))->pluck('name')->values()->all();

        // Verify the returned list is sorted alphabetically by name
        $sorted = $names;
        sort($sorted);
        $this->assertSame($sorted, $names);
    }

    /** @test */
    public function test_can_edit_resolves_correctly_per_role(): void
    {
        $admin   = User::factory()->admin()->create(['active' => true]);
        $manager = User::factory()->manager()->create(['active' => true]);
        $user    = User::factory()->create(['active' => true]);   // role = User

        // --- Admin: can edit everyone ---
        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/users');
        $response->assertStatus(200);

        $byId = collect($response->json('users'))->keyBy('id');

        $this->assertTrue((bool) $byId[$admin->id]['can_edit'],   'Admin should be able to edit themselves');
        $this->assertTrue((bool) $byId[$manager->id]['can_edit'], 'Admin should be able to edit a Manager');
        $this->assertTrue((bool) $byId[$user->id]['can_edit'],    'Admin should be able to edit a User');

        // --- Manager: can only edit role=User ---
        $response = $this->actingAs($manager, 'sanctum')->getJson('/api/users');
        $response->assertStatus(200);

        $byId = collect($response->json('users'))->keyBy('id');

        $this->assertFalse((bool) $byId[$admin->id]['can_edit'],   'Manager should NOT edit an Admin');
        $this->assertFalse((bool) $byId[$manager->id]['can_edit'], 'Manager should NOT edit another Manager');
        $this->assertTrue((bool) $byId[$user->id]['can_edit'],     'Manager SHOULD edit a User');

        // --- User: can only edit themselves ---
        $response = $this->actingAs($user, 'sanctum')->getJson('/api/users');
        $response->assertStatus(200);

        $byId = collect($response->json('users'))->keyBy('id');

        $this->assertFalse((bool) $byId[$admin->id]['can_edit'],   'User should NOT edit an Admin');
        $this->assertFalse((bool) $byId[$manager->id]['can_edit'], 'User should NOT edit a Manager');
        $this->assertTrue((bool) $byId[$user->id]['can_edit'],     'User SHOULD edit themselves');
    }
}
