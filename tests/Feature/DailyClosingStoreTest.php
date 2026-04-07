<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Company;
use App\Models\Credit;
use App\Models\DailyClosing;
use App\Models\OtherIncome;
use App\Models\Transfer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DailyClosingStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_daily_closing_is_recalculated_from_real_data(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $company = Company::create([
            'name' => 'Intermex',
            'code' => 'INT',
            'color' => '#123456',
            'is_active' => true,
        ]);

        $client = Client::create([
            'name' => 'Cliente Demo',
            'phone' => '0990000000',
            'whatsapp' => '+593990000000',
            'is_active' => true,
        ]);

        Transfer::create([
            'company_id' => $company->id,
            'transfer_date' => '2026-04-04',
            'sender_name' => 'Remitente',
            'receiver_name' => 'Destinatario',
            'amount' => 100,
            'status' => 'sent',
        ]);

        Credit::create([
            'client_id' => $client->id,
            'concept' => 'Prestamo diario',
            'total_amount' => 30,
            'paid_amount' => 0,
            'granted_date' => '2026-04-04',
            'status' => 'active',
        ]);

        OtherIncome::create([
            'income_date' => '2026-04-04',
            'description' => 'Cobro de fiado',
            'amount' => 20,
            'client_id' => $client->id,
        ]);

        $response = $this->post(route('daily-closings.store'), [
            'closing_date' => '2026-04-04',
            'existing_value' => 10,
            'notes' => 'cierre de prueba',
            'total_incomes' => 9999,
            'total_expenses' => 9999,
            'value_total' => 9999,
            'other_incomes_total' => 9999,
            'sum_total' => 9999,
            'difference' => 9999,
            'final_total' => 9999,
        ]);

        $response->assertRedirect(route('daily-closings.index'));

        $closing = DailyClosing::firstOrFail();

        $this->assertSame('2026-04-04', $closing->closing_date->toDateString());
        $this->assertSame('100.00', $closing->total_incomes);
        $this->assertSame('30.00', $closing->total_expenses);
        $this->assertSame('70.00', $closing->value_total);
        $this->assertSame('20.00', $closing->other_incomes_total);
        $this->assertSame('90.00', $closing->sum_total);
        $this->assertSame('10.00', $closing->existing_value);
        $this->assertSame('80.00', $closing->difference);
        $this->assertSame('90.00', $closing->final_total);
    }
}
