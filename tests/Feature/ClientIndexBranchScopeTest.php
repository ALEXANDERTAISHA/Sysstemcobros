<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientIndexBranchScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_only_sees_clients_from_own_branch(): void
    {
        $branchA = Branch::create([
            'name' => 'Sucursal A',
            'code' => 'A',
            'is_active' => true,
        ]);

        $branchB = Branch::create([
            'name' => 'Sucursal B',
            'code' => 'B',
            'is_active' => true,
        ]);

        $admin = User::factory()->create([
            'role' => 'admin',
            'branch_id' => $branchA->id,
        ]);

        Client::create([
            'name' => 'Cliente Sucursal A',
            'branch_id' => $branchA->id,
            'is_active' => true,
        ]);

        Client::create([
            'name' => 'Cliente Sucursal B',
            'branch_id' => $branchB->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('clients.index'));

        $response->assertOk();
        $response->assertSee('Cliente Sucursal A');
        $response->assertDontSee('Cliente Sucursal B');
    }

    public function test_super_admin_can_view_all_clients_and_filter_by_branch(): void
    {
        $branchA = Branch::create([
            'name' => 'Sucursal A',
            'code' => 'A',
            'is_active' => true,
        ]);

        $branchB = Branch::create([
            'name' => 'Sucursal B',
            'code' => 'B',
            'is_active' => true,
        ]);

        $superAdmin = User::factory()->create(['role' => 'super_admin']);

        Client::create([
            'name' => 'Cliente Sucursal A',
            'branch_id' => $branchA->id,
            'is_active' => true,
        ]);

        Client::create([
            'name' => 'Cliente Sucursal B',
            'branch_id' => $branchB->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($superAdmin)->get(route('clients.index'));
        $response->assertOk();
        $response->assertSee('Cliente Sucursal A');
        $response->assertSee('Cliente Sucursal B');

        $response = $this->actingAs($superAdmin)->get(route('clients.index', ['branch_id' => $branchA->id]));
        $response->assertOk();
        $response->assertSee('Cliente Sucursal A');
        $response->assertDontSee('Cliente Sucursal B');
    }
}
