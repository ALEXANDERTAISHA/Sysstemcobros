<?php

namespace App\Services;

use App\Models\WhatsappNotification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    /**
     * Send a WhatsApp message using CallMeBot API (free, no account needed).
     * User must first add the bot: https://www.callmebot.com/blog/free-api-whatsapp-messages/
     */
    public function send(string $phone, string $message, ?string $name = null, ?string $relatedType = null, ?int $relatedId = null): WhatsappNotification
    {
        $normalizedPhone = $this->normalizePhone($phone);
        $appName = (string) config('app.name', 'Systemcobros');
        $finalMessage = "[{$appName}]\n" . $message;

        $notification = WhatsappNotification::create([
            'recipient_phone' => $normalizedPhone,
            'recipient_name'  => $name,
            'message'         => $finalMessage,
            'status'          => 'pending',
            'related_type'    => $relatedType,
            'related_id'      => $relatedId,
        ]);

        // 1) Try Meta WhatsApp Cloud API first if configured.
        $metaToken = (string) config('services.meta_whatsapp.token', '');
        $metaPhoneNumberId = (string) config('services.meta_whatsapp.phone_number_id', '');
        if ($metaToken !== '' && $metaPhoneNumberId !== '') {
            $metaResult = $this->sendViaMetaCloud($normalizedPhone, $finalMessage, $metaToken, $metaPhoneNumberId);
            if ($metaResult['ok']) {
                $notification->update([
                    'status'  => 'sent',
                    'sent_at' => now(),
                    'error_message' => null,
                ]);

                return $notification;
            }

            Log::warning('Meta WhatsApp send failed, trying CallMeBot fallback.', [
                'phone' => $normalizedPhone,
                'error' => $metaResult['error'],
            ]);
        }

        // 2) Fallback to CallMeBot if configured.
        $apiKey = config('services.callmebot.api_key');
        if (!empty($apiKey)) {
            try {
                $response = Http::timeout(15)->get('https://api.callmebot.com/whatsapp.php', [
                    'phone'   => $this->providerPhone($normalizedPhone),
                    'text'    => $finalMessage,
                    'apikey'  => $apiKey,
                ]);

                if ($response->successful()) {
                    $notification->update([
                        'status'  => 'sent',
                        'sent_at' => now(),
                        'error_message' => null,
                    ]);
                } else {
                    $notification->update([
                        'status'        => 'failed',
                        'error_message' => $response->body(),
                    ]);
                }
            } catch (\Throwable $e) {
                Log::error('WhatsApp send error: ' . $e->getMessage());
                $notification->update([
                    'status'        => 'failed',
                    'error_message' => $e->getMessage(),
                ]);
            }

            return $notification;
        }

        // No provider configured.
        Log::info('No WhatsApp provider configured. Message queued for manual send.', [
            'phone'   => $normalizedPhone,
            'message' => $finalMessage,
        ]);

        return $notification;
    }

    private function sendViaMetaCloud(string $normalizedPhone, string $message, string $token, string $phoneNumberId): array
    {
        $version = (string) config('services.meta_whatsapp.api_version', 'v21.0');
        $url = "https://graph.facebook.com/{$version}/{$phoneNumberId}/messages";

        try {
            $response = Http::withToken($token)
                ->timeout(20)
                ->post($url, [
                    'messaging_product' => 'whatsapp',
                    'to' => $this->providerPhone($normalizedPhone),
                    'type' => 'text',
                    'text' => ['body' => $message],
                ]);

            if ($response->successful()) {
                return ['ok' => true, 'error' => null];
            }

            return ['ok' => false, 'error' => $response->body()];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Generate a WhatsApp click-to-chat URL.
     */
    public function chatUrl(string $phone, string $message): string
    {
        $cleanPhone = $this->providerPhone($this->normalizePhone($phone));
        return 'https://wa.me/' . $cleanPhone . '?text=' . urlencode($message);
    }

    private function normalizePhone(string $phone): string
    {
        $raw = trim($phone);
        $clean = preg_replace('/\D+/', '', $raw);

        if (empty($clean)) {
            return $phone;
        }

        // Preserve explicit international numbers like +15551234567.
        if (str_starts_with($raw, '+')) {
            return '+' . $clean;
        }

        // Support international prefix format like 00593123456789.
        if (str_starts_with($clean, '00')) {
            return '+' . substr($clean, 2);
        }

        $countryCode = preg_replace('/\D+/', '', (string) config('services.callmebot.default_country_code', ''));

        // Convert local numbers (starting with 0) using configured default country code.
        if ($countryCode !== '' && str_starts_with($clean, '0')) {
            $clean = $countryCode . ltrim($clean, '0');
        }

        return '+' . $clean;
    }

    private function providerPhone(string $phone): string
    {
        return preg_replace('/\D+/', '', $phone);
    }
}
