<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_cannot_access_user_management(): void
    {
        $user = User::create(['name' => 'Cashier', 'email' => 'cashier@example.test', 'password' => 'secret123', 'role' => 'cashier', 'is_active' => true]);
        $this->actingAs($user)->get('/admin/users')->assertForbidden();
    }

    public function test_owner_can_access_user_management(): void
    {
        $user = User::create(['name' => 'Owner', 'email' => 'owner@example.test', 'password' => 'secret123', 'role' => 'owner', 'is_active' => true]);
        $this->actingAs($user)->get('/admin/users')->assertOk();
    }
}
