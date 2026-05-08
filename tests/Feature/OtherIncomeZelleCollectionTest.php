<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\CashBoxInitial;
use App\Models\Client;
use App\Models\Company;
use App\Models\Credit;
use App\Models\CreditPayment;
use App\Models\OtherIncome;
use App\Models\User;
use App\Services\FinancialSummaryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OtherIncomeZelleCollectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_partial_zelle_collection_keeps_remaining_balance_in_pending_followup(): void
    {
        [$branch, $company, $client, $user] = $this->baseData();
        $credit = Credit::create([
            'branch_id' => $branch->id,
            'client_id' => $client->id,
            'company_id' => $company->id,
            'concept' => 'Debito anterior',
            'total_amount' => 150,
            'paid_amount' => 0,
            'granted_date' => '2026-05-06',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->post(route('other-incomes.collect-debit-zelle'), [
            'credit_id' => $credit->id,
            'payment_date' => '2026-05-07',
            'amount' => 50,
            'notes' => 'Pago parcial',
        ]);

        $response->assertRedirect(route('other-incomes.index', ['date' => '2026-05-07']));
        $credit->refresh();

        $this->assertSame('partial', $credit->status);
        $this->assertSame('50.00', $credit->paid_amount);
        $this->assertSame(100.0, $credit->balance);
        $this->assertSame($company->id, $credit->company_id);
        $this->assertSame('50.00', OtherIncome::where('credit_id', $credit->id)->firstOrFail()->amount);

        $summary = app(FinancialSummaryService::class)->summarizeRange('2026-05-07', '2026-05-07', null, $branch->id, true, true);
        $this->assertSame(0.0, $summary['total_expenses']);
        $this->assertSame(0.0, $summary['total_other_incomes']);
    }

    public function test_same_day_zelle_collection_still_counts_as_daily_debit_expense(): void
    {
        [$branch, $company, $client, $user] = $this->baseData();
        $credit = Credit::create([
            'branch_id' => $branch->id,
            'client_id' => $client->id,
            'company_id' => $company->id,
            'concept' => 'Debito del dia',
            'total_amount' => 150,
            'paid_amount' => 0,
            'granted_date' => '2026-05-07',
            'status' => 'active',
        ]);

        $this->actingAs($user)->post(route('other-incomes.collect-debit-zelle'), [
            'credit_id' => $credit->id,
            'payment_date' => '2026-05-07',
            'amount' => 150,
        ])->assertRedirect(route('other-incomes.index', ['date' => '2026-05-07']));

        $credit->refresh();
        $this->assertSame('paid', $credit->status);
        $this->assertStringContainsString('ZELLE', CreditPayment::where('credit_id', $credit->id)->firstOrFail()->notes);

        $summary = app(FinancialSummaryService::class)->summarizeRange('2026-05-07', '2026-05-07', null, $branch->id, true, true);
        $this->assertSame(150.0, $summary['total_expenses']);
        $this->assertSame(0.0, $summary['total_other_incomes']);
    }

    private function baseData(): array
    {
        $branch = Branch::create([
            'name' => 'Sucursal Premium',
            'code' => 'PREM',
            'is_active' => true,
        ]);
        $company = Company::create([
            'name' => 'V. AMERICA',
            'code' => 'VIA',
            'color' => '#123456',
            'is_active' => true,
            'company_type' => Company::TYPE_GENERAL,
        ]);
        $client = Client::create([
            'name' => 'Cesar Guzman',
            'is_active' => true,
        ]);
        $user = User::factory()->create([
            'role' => 'admin',
            'branch_id' => $branch->id,
        ]);

        CashBoxInitial::create([
            'branch_id' => $branch->id,
            'date' => today()->toDateString(),
            'initial_amount' => 1,
            'notes' => 'Habilitar operaciones',
        ]);

        return [$branch, $company, $client, $user];
    }
}
