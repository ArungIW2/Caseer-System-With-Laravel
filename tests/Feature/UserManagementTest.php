<?php

namespace Tests\Feature;

use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_scoped_role_requires_store_assignment(): void
    {
        $owner = User::create(['name' => 'Owner', 'email' => 'owner@example.test', 'password' => Hash::make('secret123'), 'role' => 'owner', 'is_active' => true]);
        $response = $this->actingAs($owner)->post('/admin/users', [
            'name' => 'Cashier', 'email' => 'cashier@example.test', 'password' => 'secret123', 'role' => 'cashier', 'is_active' => '1',
        ]);
        $response->assertSessionHasErrors('store_id');
        $this->assertDatabaseMissing('users', ['email' => 'cashier@example.test']);
    }

    public function test_user_creation_records_audit_log(): void
    {
        $owner = User::create(['name' => 'Owner', 'email' => 'owner@example.test', 'password' => Hash::make('secret123'), 'role' => 'owner', 'is_active' => true]);
        $store = Store::create(['code' => 'TST', 'name' => 'Test Store', 'is_active' => true]);
        $this->actingAs($owner)->post('/admin/users', [
            'name' => 'Cashier', 'email' => 'cashier@example.test', 'password' => 'secret123', 'role' => 'cashier', 'store_id' => $store->id, 'is_active' => '1',
        ])->assertRedirect('/admin/users');
        $this->assertDatabaseHas('audit_logs', ['event' => 'user.created']);
    }
}
