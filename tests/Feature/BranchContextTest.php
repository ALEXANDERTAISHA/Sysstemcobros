<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\CashBoxInitial;
use App\Models\Client;
use App\Models\Company;
use App\Models\Credit;
use App\Models\OtherIncome;
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

    public function test_dashboard_does_not_mix_other_income_or_cash_box_between_branches(): void
    {
        $ownBranch = Branch::create([
            'name' => 'Maluvariedades',
            'code' => 'MALU',
            'is_active' => true,
        ]);
        $otherBranch = Branch::create([
            'name' => 'Otra Sucursal',
            'code' => 'OTHER',
            'is_active' => true,
        ]);
        $admin = User::factory()->create([
            'role' => 'admin',
            'branch_id' => $ownBranch->id,
        ]);
        $client = Client::create([
            'name' => 'Cliente Otra Sucursal',
            'is_active' => true,
        ]);
        $credit = Credit::create([
            'branch_id' => $otherBranch->id,
            'client_id' => $client->id,
            'concept' => 'Fiado anterior',
            'total_amount' => 55,
            'paid_amount' => 55,
            'granted_date' => '2026-05-06',
            'status' => 'paid',
        ]);

        CashBoxInitial::create([
            'branch_id' => $otherBranch->id,
            'date' => '2026-05-07',
            'initial_amount' => 3002,
            'notes' => 'Caja de otra sucursal',
        ]);
        OtherIncome::create([
            'branch_id' => $otherBranch->id,
            'income_date' => '2026-05-07',
            'description' => 'Cobro otra sucursal',
            'amount' => 55,
            'client_id' => $client->id,
            'credit_id' => $credit->id,
        ]);

        $response = $this->actingAs($admin)->get(route('dashboard', ['date' => '2026-05-07']));

        $response->assertOk();
        $this->assertSame(0.0, (float) $response->viewData('totalOtherIncomes'));
        $this->assertSame(0.0, (float) $response->viewData('existingValue'));
        $this->assertSame(0.0, (float) $response->viewData('sumTotal'));
    }
}
