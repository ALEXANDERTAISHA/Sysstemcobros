<?php

namespace Tests\Feature;

use App\Models\AccountPayable;
use App\Models\AccountPayablePayment;
use App\Models\CashBoxInitial;
use App\Models\Client;
use App\Models\Company;
use App\Models\Credit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountPayableTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_payable_is_independent_from_expense_debits(): void
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        CashBoxInitial::create([
            'date' => today()->toDateString(),
            'initial_amount' => 1,
            'notes' => 'Habilitar operaciones',
        ]);
        $client = Client::create([
            'name' => 'Proveedor Demo',
            'is_active' => true,
        ]);
        $company = Company::create([
            'name' => 'CUENTA PROVEEDOR',
            'code' => 'CP',
            'color' => '#123456',
            'is_active' => true,
            'company_type' => Company::TYPE_EXPENSE_DEBIT,
        ]);

        $response = $this->actingAs($user)->post(route('accounts-payable.store'), [
            'client_id' => $client->id,
            'company_id' => $company->id,
            'concept' => 'Factura proveedor',
            'total_amount' => 100,
            'issued_date' => '2026-05-26',
            'notes' => 'Compra independiente',
        ]);

        $account = AccountPayable::firstOrFail();
        $response->assertRedirect(route('accounts-payable.show', $account));
        $this->assertDatabaseCount('credits', 0);
        $this->assertSame('2026-06-02', $account->due_date->toDateString());

        $this->post(route('accounts-payable.payments.store', $account), [
            'amount' => 40,
            'payment_date' => '2026-05-26',
            'notes' => 'Abono inicial',
        ])->assertRedirect(route('accounts-payable.show', $account));

        $account->refresh();
        $this->assertSame('partial', $account->status);
        $this->assertSame(60.0, $account->balance);
        $this->assertSame('40.00', AccountPayablePayment::firstOrFail()->amount);
        $this->assertDatabaseCount('credits', 0);
    }
}
