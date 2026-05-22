<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Seed roles and permissions.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
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
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $mentor = Role::firstOrCreate(['name' => 'mentor', 'guard_name' => 'web']);
        $user = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);

        $admin->syncPermissions($permissions);

        $mentor->syncPermissions([
            'create_course',
            'edit_own_course',
            'delete_own_course',
            'upload_material',
            'buy_course',
            'topup_wallet',
            'review_course',
        ]);

        $user->syncPermissions([
            'buy_course',
            'topup_wallet',
            'review_course',
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
