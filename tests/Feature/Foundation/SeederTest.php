<?php

namespace Tests\Feature\Foundation;

use App\Models\PlatformSetting;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_creates_foundation_identity_data(): void
    {
        $this->seed();
        $this->seed();

        foreach (['admin', 'mentor', 'user'] as $role) {
            $this->assertDatabaseHas('roles', ['name' => $role]);
        }

        foreach ([
            'access_admin_panel',
            'manage_users',
            'manage_mentors',
            'manage_courses',
            'manage_categories',
            'manage_orders',
            'manage_wallets',
            'manage_settings',
            'manage_reviews',
            'create_course',
            'edit_own_course',
            'delete_own_course',
            'upload_material',
            'buy_course',
            'topup_wallet',
            'review_course',
        ] as $permission) {
            $this->assertDatabaseHas('permissions', ['name' => $permission]);
        }

        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $mentor = User::where('email', 'mentor@example.com')->firstOrFail();
        $user = User::where('email', 'user@example.com')->firstOrFail();

        $this->assertTrue($admin->hasRole('admin'));
        $this->assertTrue($mentor->hasRole('mentor'));
        $this->assertTrue($user->hasRole('user'));
        $this->assertTrue(Hash::check('Scr@pper', $admin->password));
        $this->assertTrue(Hash::check('Scr@pper', $mentor->password));
        $this->assertTrue(Hash::check('Scr@pper', $user->password));

        $this->assertTrue(Role::findByName('admin')->hasPermissionTo('access_admin_panel'));
        $this->assertTrue(Permission::where('name', 'buy_course')->exists());

        $this->assertDatabaseHas('mentor_profiles', ['user_id' => $mentor->id, 'slug' => 'mentor-demo']);
        $this->assertDatabaseHas('wallets', ['owner_type' => 'user', 'owner_id' => $mentor->id, 'balance' => 0]);
        $this->assertDatabaseHas('wallets', ['owner_type' => 'user', 'owner_id' => $user->id, 'balance' => 500000]);
        $this->assertDatabaseHas('wallets', ['owner_type' => 'platform', 'owner_id' => 0, 'balance' => 0]);
        $this->assertDatabaseHas('platform_settings', [
            'key' => 'mentor_commission_rate',
            'value' => '60',
            'type' => 'integer',
        ]);

        $this->assertSame(3, User::count());
        $this->assertSame(3, Wallet::count());
        $this->assertSame(1, PlatformSetting::count());
    }
}
