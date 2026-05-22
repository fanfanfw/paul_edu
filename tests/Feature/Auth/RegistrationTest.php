<?php

namespace Tests\Feature\Auth;

use App\Models\MentorProfile;
use App\Models\User;
use App\Models\Wallet;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response
            ->assertOk()
            ->assertSeeVolt('pages.auth.register');
    }

    public function test_new_users_can_register(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $component = Volt::test('pages.auth.register')
            ->set('name', 'Test User')
            ->set('email', 'test@example.com')
            ->set('role', 'user')
            ->set('password', 'password')
            ->set('password_confirmation', 'password');

        $component->call('register');

        $component->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticated();
    }

    public function test_public_register_can_choose_user_role(): void
    {
        $this->seed(RolePermissionSeeder::class);

        Volt::test('pages.auth.register')
            ->set('name', 'User Register')
            ->set('email', 'user-register@example.com')
            ->set('role', 'user')
            ->set('password', 'password')
            ->set('password_confirmation', 'password')
            ->call('register')
            ->assertRedirect(route('dashboard', absolute: false));

        $user = User::where('email', 'user-register@example.com')->firstOrFail();

        $this->assertTrue($user->hasRole('user'));
        $this->assertDatabaseHas('wallets', [
            'owner_type' => 'user',
            'owner_id' => $user->id,
            'balance' => 0,
        ]);
    }

    public function test_public_register_can_choose_mentor_role(): void
    {
        $this->seed(RolePermissionSeeder::class);

        Volt::test('pages.auth.register')
            ->set('name', 'Mentor Register')
            ->set('email', 'mentor-register@example.com')
            ->set('role', 'mentor')
            ->set('password', 'password')
            ->set('password_confirmation', 'password')
            ->call('register')
            ->assertRedirect(route('dashboard', absolute: false));

        $user = User::where('email', 'mentor-register@example.com')->firstOrFail();

        $this->assertTrue($user->hasRole('mentor'));
        $this->assertDatabaseHas('wallets', [
            'owner_type' => 'user',
            'owner_id' => $user->id,
            'balance' => 0,
        ]);
        $this->assertDatabaseHas('mentor_profiles', [
            'user_id' => $user->id,
            'display_name' => 'Mentor Register',
            'slug' => 'mentor-register',
        ]);
    }

    public function test_public_register_cannot_choose_admin_role(): void
    {
        $this->seed(RolePermissionSeeder::class);

        Volt::test('pages.auth.register')
            ->set('name', 'Bad Admin')
            ->set('email', 'bad-admin@example.com')
            ->set('role', 'admin')
            ->set('password', 'password')
            ->set('password_confirmation', 'password')
            ->call('register')
            ->assertHasErrors(['role']);

        $this->assertDatabaseMissing('users', ['email' => 'bad-admin@example.com']);
    }

    public function test_register_user_creates_user_role_and_wallet(): void
    {
        $this->seed(RolePermissionSeeder::class);

        Volt::test('pages.auth.register')
            ->set('name', 'Wallet User')
            ->set('email', 'wallet-user@example.com')
            ->set('role', 'user')
            ->set('password', 'password')
            ->set('password_confirmation', 'password')
            ->call('register');

        $user = User::where('email', 'wallet-user@example.com')->firstOrFail();

        $this->assertTrue($user->hasRole('user'));
        $this->assertInstanceOf(Wallet::class, $user->wallet);
        $this->assertSame('user', $user->wallet->owner_type);
    }

    public function test_register_mentor_creates_role_wallet_and_mentor_profile(): void
    {
        $this->seed(RolePermissionSeeder::class);

        Volt::test('pages.auth.register')
            ->set('name', 'Profile Mentor')
            ->set('email', 'profile-mentor@example.com')
            ->set('role', 'mentor')
            ->set('password', 'password')
            ->set('password_confirmation', 'password')
            ->call('register');

        $user = User::where('email', 'profile-mentor@example.com')->firstOrFail();

        $this->assertTrue($user->hasRole('mentor'));
        $this->assertInstanceOf(Wallet::class, $user->wallet);
        $this->assertInstanceOf(MentorProfile::class, $user->mentorProfile);
    }
}
