<?php

namespace Tests\Feature;

use App\Services\WhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsAppServiceWhatChimpTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_sends_an_approved_template_through_whatchimp(): void
    {
        $this->configureWhatChimp();

        Http::fake([
            'app.whatchimp.com/*' => Http::response([
                'status' => '1',
                'message' => 'Message sent successfully.',
                'wa_message_id' => 'wamid.test-123',
            ]),
        ]);

        $notification = app(WhatsAppService::class)->send(
            '+57 300 123 4567',
            'Tu pago fue recibido.',
            'Ana',
        );

        $this->assertSame('sent', $notification->status);
        $this->assertSame('whatchimp', $notification->provider);
        $this->assertSame('wamid.test-123', $notification->provider_message_id);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://app.whatchimp.com/api/v1/whatsapp/send/template'
                && $request['apiToken'] === 'test-token'
                && $request['phone_number_id'] === '123456789'
                && $request['phone_number'] === '573001234567'
                && $request['template_id'] === '424860'
                && $request['templateVariable-nombreCliente-1'] === 'Ana'
                && str_contains($request['templateVariable-detalleNotificacion-2'], 'Tu pago fue recibido.');
        });
    }

    public function test_http_200_with_whatchimp_error_is_not_marked_as_sent(): void
    {
        $this->configureWhatChimp();

        Http::fake([
            'app.whatchimp.com/*' => Http::response([
                'status' => '0',
                'message' => 'Template not approved.',
            ]),
        ]);

        $notification = app(WhatsAppService::class)->send(
            '+573001234567',
            'Recordatorio de pago.',
            'Ana',
        );

        $this->assertSame('failed', $notification->status);
        $this->assertSame('whatchimp', $notification->provider);
        $this->assertSame('Template not approved.', $notification->error_message);
    }

    public function test_a_whatchimp_rejection_is_not_hidden_by_a_fallback_provider(): void
    {
        $this->configureWhatChimp();
        config()->set('services.callmebot.api_key', 'fallback-key');

        Http::fake([
            'app.whatchimp.com/*' => Http::response([
                'status' => '0',
                'message' => 'WhatsApp account not found.',
            ]),
            'api.callmebot.com/*' => Http::response('Message queued.'),
        ]);

        $notification = app(WhatsAppService::class)->send(
            '+593988000222',
            'Recordatorio de pago.',
            'Ana',
        );

        $this->assertSame('failed', $notification->status);
        $this->assertSame('whatchimp', $notification->provider);
        $this->assertSame('WhatsApp account not found.', $notification->error_message);
        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => str_contains($request->url(), 'app.whatchimp.com'));
    }

    public function test_whatchimp_success_without_message_id_is_not_marked_as_sent(): void
    {
        $this->configureWhatChimp();

        Http::fake([
            'app.whatchimp.com/*' => Http::response([
                'status' => '1',
                'message' => 'Message accepted.',
            ]),
        ]);

        $notification = app(WhatsAppService::class)->send(
            '+573001234567',
            'Recordatorio de pago.',
            'Ana',
        );

        $this->assertSame('failed', $notification->status);
        $this->assertNull($notification->provider_message_id);
        $this->assertStringContainsString('wa_message_id', $notification->error_message);
    }

    private function configureWhatChimp(): void
    {
        config()->set('services.whatchimp', [
            'api_token' => 'test-token',
            'phone_number_id' => '123456789',
            'template_name' => 'notificacion_sistema',
            'template_id' => '424860',
            'template_language' => 'es',
            'endpoint' => 'https://app.whatchimp.com/api/v1/whatsapp/send',
            'template_endpoint' => 'https://app.whatchimp.com/api/v1/whatsapp/send/template',
        ]);
        config()->set('services.meta_whatsapp', ['token' => '', 'phone_number_id' => '']);
        config()->set('services.twilio_whatsapp', ['account_sid' => '', 'auth_token' => '', 'from' => '']);
        config()->set('services.callmebot.api_key', '');
    }
}
