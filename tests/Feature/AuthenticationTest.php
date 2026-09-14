<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_with_valid_credentials(): void
    {
        User::create(['name' => 'Admin', 'email' => 'admin@example.test', 'password' => Hash::make('secret123'), 'role' => 'super_admin', 'is_active' => true]);
        $response = $this->post('/login', ['email' => 'admin@example.test', 'password' => 'secret123']);
        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
    }

    public function test_inactive_user_cannot_login(): void
    {
        User::create(['name' => 'Inactive', 'email' => 'inactive@example.test', 'password' => Hash::make('secret123'), 'role' => 'cashier', 'is_active' => false]);
        $this->post('/login', ['email' => 'inactive@example.test', 'password' => 'secret123']);
        $this->assertGuest();
    }
}
