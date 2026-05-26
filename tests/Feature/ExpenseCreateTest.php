<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\CashBoxInitial;
use App\Models\Company;
use App\Models\Credit;
use App\Models\OtherIncome;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseCreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_productos_de_la_tienda_credit_can_be_created_without_due_date_and_with_notes(): void
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        CashBoxInitial::create([
            'date' => today()->toDateString(),
            'initial_amount' => 1,
            'notes' => 'Habilitar operaciones',
        ]);
        $client = Client::create([
            'name' => 'Cliente Tienda',
            'is_active' => true,
        ]);
        $company = Company::create([
            'name' => 'PRODUCTOS DE LA TIENDA',
            'code' => 'PDT',
            'color' => '#123456',
            'is_active' => true,
            'company_type' => Company::TYPE_EXPENSE_DEBIT,
        ]);

        $response = $this->actingAs($user)->post(route('expenses.store'), [
            'client_id' => $client->id,
            'company_id' => $company->id,
            'concept' => 'Compra de tienda',
            'total_amount' => 25,
            'granted_date' => '2026-05-25',
            'notes' => 'Nota visible de prueba',
        ]);

        $response->assertRedirect(route('dashboard'));

        $credit = Credit::firstOrFail();
        $this->assertNull($credit->due_date);
        $this->assertSame('Nota visible de prueba', $credit->notes);

        $income = OtherIncome::where('credit_id', $credit->id)->firstOrFail();
        $this->assertSame('Nota visible de prueba', $income->notes);

        $this->get(route('other-incomes.index'))
            ->assertOk()
            ->assertSee('Nota: Nota visible de prueba');
    }

    public function test_regular_company_credit_gets_automatic_due_date_when_hidden_field_is_not_sent(): void
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        CashBoxInitial::create([
            'date' => today()->toDateString(),
            'initial_amount' => 1,
            'notes' => 'Habilitar operaciones',
        ]);
        $client = Client::create([
            'name' => 'Cliente Regular',
            'is_active' => true,
        ]);
        $company = Company::create([
            'name' => 'VIA AMERICAS',
            'code' => 'VIA',
            'color' => '#123456',
            'is_active' => true,
            'company_type' => Company::TYPE_EXPENSE_DEBIT,
        ]);

        $response = $this->actingAs($user)->post(route('expenses.store'), [
            'client_id' => $client->id,
            'company_id' => $company->id,
            'concept' => 'Debito regular',
            'total_amount' => 40,
            'granted_date' => '2026-05-25',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertSame('2026-06-01', Credit::firstOrFail()->due_date->toDateString());
    }
}
