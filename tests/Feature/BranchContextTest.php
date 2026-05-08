<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Transfer;
use App\Models\User;
use App\Support\BranchContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchContextTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_is_scoped_to_own_branch(): void
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
        $company = Company::create([
            'name' => 'V. AMERICA',
            'code' => 'VIA',
            'color' => '#123456',
            'is_active' => true,
            'company_type' => Company::TYPE_GENERAL,
        ]);

        Transfer::create([
            'branch_id' => $branchA->id,
            'company_id' => $company->id,
            'transfer_date' => '2026-05-07',
            'sender_name' => 'Sucursal A',
            'receiver_name' => 'N/A',
            'amount' => 100,
            'status' => 'sent',
        ]);
        Transfer::create([
            'branch_id' => $branchB->id,
            'company_id' => $company->id,
            'transfer_date' => '2026-05-07',
            'sender_name' => 'Sucursal B',
            'receiver_name' => 'N/A',
            'amount' => 200,
            'status' => 'sent',
        ]);

        $this->actingAs($admin);

        $this->assertFalse(BranchContext::isPrivileged());
        $this->assertSame(1, BranchContext::scope(Transfer::query())->count());
        $this->assertSame(100.0, (float) BranchContext::scope(Transfer::query())->sum('amount'));
    }

    public function test_super_admin_keeps_global_scope(): void
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
        $company = Company::create([
            'name' => 'V. AMERICA',
            'code' => 'VIA',
            'color' => '#123456',
            'is_active' => true,
            'company_type' => Company::TYPE_GENERAL,
        ]);

        Transfer::create([
            'branch_id' => $branchA->id,
            'company_id' => $company->id,
            'transfer_date' => '2026-05-07',
            'sender_name' => 'Sucursal A',
            'receiver_name' => 'N/A',
            'amount' => 100,
            'status' => 'sent',
        ]);
        Transfer::create([
            'branch_id' => $branchB->id,
            'company_id' => $company->id,
            'transfer_date' => '2026-05-07',
            'sender_name' => 'Sucursal B',
            'receiver_name' => 'N/A',
            'amount' => 200,
            'status' => 'sent',
        ]);

        $this->actingAs($superAdmin);

        $this->assertTrue(BranchContext::isPrivileged());
        $this->assertSame(2, BranchContext::scope(Transfer::query())->count());
        $this->assertSame(300.0, (float) BranchContext::scope(Transfer::query())->sum('amount'));
    }
}
