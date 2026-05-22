<?php

namespace Tests\Feature\Foundation;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActiveUserMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_suspended_user_cannot_access_protected_route(): void
    {
        $user = User::factory()->create([
            'status' => UserStatus::Suspended,
        ]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertForbidden();

        $this->assertGuest();
    }
}
