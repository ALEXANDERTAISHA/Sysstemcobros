<?php

namespace Tests\Feature;

use App\Services\WhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsAppServiceMetaTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_sends_an_approved_template_directly_through_meta(): void
    {
        config()->set('services.meta_whatsapp', [
            'token' => 'meta-test-token',
            'phone_number_id' => '680604811801737',
            'template_name' => 'notificacion_sistema_nueva',
            'template_language' => 'es',
            'api_version' => 'v23.0',
        ]);
        config()->set('services.whatchimp', [
            'api_token' => '',
            'phone_number_id' => '',
            'template_name' => '',
        ]);
        config()->set('services.twilio_whatsapp', ['account_sid' => '', 'auth_token' => '', 'from' => '']);
        config()->set('services.callmebot.api_key', '');

        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'messages' => [['id' => 'wamid.meta-test-123']],
            ]),
        ]);

        $notification = app(WhatsAppService::class)->send(
            '+593 98 200 9468',
            'Tiene un pago pendiente de $2.00.',
            'Paul Taisha',
        );

        $this->assertSame('sent', $notification->status);
        $this->assertSame('meta', $notification->provider);
        $this->assertSame('wamid.meta-test-123', $notification->provider_message_id);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://graph.facebook.com/v23.0/680604811801737/messages'
                && $request->hasHeader('Authorization', 'Bearer meta-test-token')
                && $request['to'] === '593982009468'
                && $request['type'] === 'template'
                && data_get($request->data(), 'template.name') === 'notificacion_sistema_nueva'
                && data_get($request->data(), 'template.language.code') === 'es'
                && data_get($request->data(), 'template.components.0.parameters.0.text') === 'Paul Taisha'
                && str_contains(
                    data_get($request->data(), 'template.components.0.parameters.1.text'),
                    'Tiene un pago pendiente de $2.00.'
                );
        });
    }
}
