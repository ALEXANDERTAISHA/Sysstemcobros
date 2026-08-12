<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\CashBoxInitial;
use App\Models\Client;
use App\Models\Company;
use App\Models\Credit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OtherIncomeWhatsAppReminderTest extends TestCase
{
    use RefreshDatabase;

    public function test_bulk_reminder_prefers_whatsapp_number_and_counts_confirmed_send(): void
    {
        [$user, $client] = $this->createPendingDebt();
        $client->update([
            'phone' => '+593999000111',
            'whatsapp' => '+593988000222',
        ]);
        $this->configureWhatChimp();

        Http::fake([
            'app.whatchimp.com/*' => Http::response([
                'status' => '1',
                'wa_message_id' => 'wamid.bulk-test',
            ]),
        ]);

        $this->actingAs($user)
            ->post(route('other-incomes.send-overdue-reminders'))
            ->assertRedirect(route('other-incomes.index'))
            ->assertSessionHas('success', fn (string $message) => str_contains($message, '1 WhatsApp(s) de 1'));

        Http::assertSent(fn ($request) => $request['phone_number'] === '593988000222');
    }

    public function test_bulk_reminder_reports_whatchimp_rejection_instead_of_counting_it_as_sent(): void
    {
        [$user, $client] = $this->createPendingDebt();
        $client->update(['whatsapp' => '+593988000222']);
        $this->configureWhatChimp();

        Http::fake([
            'app.whatchimp.com/*' => Http::response([
                'status' => '0',
                'message' => 'WhatsApp account not found.',
            ]),
        ]);

        $this->actingAs($user)
            ->post(route('other-incomes.send-overdue-reminders'))
            ->assertRedirect(route('other-incomes.index'))
            ->assertSessionHas('error', fn (string $message) => str_contains($message, 'WhatsApp account not found.'));
    }

    private function createPendingDebt(): array
    {
        $branch = Branch::create([
            'name' => 'Sucursal WhatsApp',
            'code' => 'WAPP',
            'is_active' => true,
        ]);
        $company = Company::create([
            'name' => 'Empresa recordatorios',
            'code' => 'REM',
            'color' => '#123456',
            'is_active' => true,
            'company_type' => Company::TYPE_GENERAL,
        ]);
        $client = Client::create([
            'branch_id' => $branch->id,
            'name' => 'Cliente WhatsApp',
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
        ]);
        Credit::create([
            'branch_id' => $branch->id,
            'client_id' => $client->id,
            'company_id' => $company->id,
            'concept' => 'Deuda de prueba',
            'total_amount' => 25,
            'paid_amount' => 0,
            'granted_date' => today()->toDateString(),
            'due_date' => today()->addDay()->toDateString(),
            'status' => 'active',
        ]);

        return [$user, $client];
    }

    private function configureWhatChimp(): void
    {
        config()->set('services.whatchimp', [
            'api_token' => 'test-token',
            'phone_number_id' => '123456789',
            'template_name' => 'notificacion_sistema',
            'template_language' => 'es',
            'endpoint' => 'https://app.whatchimp.com/api/v1/whatsapp/send',
        ]);
        config()->set('services.meta_whatsapp', ['token' => '', 'phone_number_id' => '']);
        config()->set('services.twilio_whatsapp', ['account_sid' => '', 'auth_token' => '', 'from' => '']);
        config()->set('services.callmebot.api_key', '');
    }
}
