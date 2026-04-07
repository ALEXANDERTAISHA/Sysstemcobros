<?php

namespace App\Services;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class EmailDeliveryService
{
    /**
     * Send plain text email. Tries SMTP first, then Brevo API over HTTPS if configured.
     */
    public function sendText(string $toEmail, string $subject, string $body): array
    {
        try {
            Mail::raw($body, function ($mail) use ($toEmail, $subject) {
                $mail->to($toEmail)->subject($subject);
            });

            return ['sent' => true, 'provider' => 'smtp', 'error' => null];
        } catch (\Throwable $smtpError) {
            $fallback = $this->sendViaBrevo($toEmail, $subject, $body);
            if ($fallback['sent']) {
                return $fallback;
            }

            return [
                'sent' => false,
                'provider' => 'smtp',
                'error' => $smtpError->getMessage(),
            ];
        }
    }

    public function sendDebtReminderCard(
        string $toEmail,
        string $clientName,
        float $totalDebt,
        ?string $dueDate,
        string $message,
        int $debtsCount,
        array $debtsDetail = []
    ): array {
        $subject = 'Recordatorio de pago pendiente';
        $appName = (string) config('app.name', 'Systemcobros');
        $amountText = '$' . number_format($totalDebt, 2);
        $safeDueDate = htmlspecialchars($dueDate ?: 'Sin fecha definida', ENT_QUOTES, 'UTF-8');

        $safeAppName = htmlspecialchars($appName, ENT_QUOTES, 'UTF-8');
        $safeClient = htmlspecialchars($clientName, ENT_QUOTES, 'UTF-8');
        $safeMessage = nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));
        $logoInline = $this->systemLogoInlinePayload();
        $logoBlock = $logoInline
            ? "<img src=\"__SYSTEM_LOGO_SRC__\" alt=\"{$safeAppName}\" style=\"width:34px;height:34px;border-radius:7px;object-fit:cover;border:1px solid rgba(255,255,255,.35);background:#fff;\">"
            : "<span style=\"width:34px;height:34px;border-radius:7px;border:1px solid rgba(255,255,255,.35);display:inline-flex;align-items:center;justify-content:center;background:rgba(255,255,255,.18);font-weight:700;\">$</span>";

        [$breakdownHtml, $breakdownText] = $this->buildDebtBreakdown($debtsDetail, $totalDebt, $debtsCount);

        $html = <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$safeAppName} - Recordatorio</title>
</head>
<body style="margin:0;padding:20px;background:#f3f4f6;font-family:Arial,sans-serif;color:#111827;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:720px;margin:0 auto;background:#ffffff;border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;">
        <tr>
            <td style="padding:16px 20px;background:#0f766e;color:#ffffff;font-weight:700;font-size:18px;">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                    <tr>
                        <td style="width:40px;vertical-align:middle;">{$logoBlock}</td>
                        <td style="vertical-align:middle;padding-left:8px;">{$safeAppName} | Recordatorio de pago pendiente</td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td style="padding:20px;">
                <p style="margin:0 0 14px 0;font-size:16px;">Estimado/a <strong>{$safeClient}</strong>,</p>
                <p style="margin:0 0 16px 0;color:#374151;">Le enviamos el detalle actualizado de su(s) obligación(es) pendiente(s):</p>

                {$breakdownHtml}

                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border:1px solid #d1d5db;border-radius:8px;background:#f9fafb;">
                    <tr>
                        <td style="padding:12px 14px;border-bottom:1px solid #e5e7eb;width:38%;color:#6b7280;"><strong>Cliente</strong></td>
                        <td style="padding:12px 14px;border-bottom:1px solid #e5e7eb;">{$safeClient}</td>
                    </tr>
                    <tr>
                        <td style="padding:12px 14px;border-bottom:1px solid #e5e7eb;color:#6b7280;"><strong>Primer vencimiento</strong></td>
                        <td style="padding:12px 14px;border-bottom:1px solid #e5e7eb;">{$safeDueDate}</td>
                    </tr>
                    <tr>
                        <td style="padding:12px 14px;color:#6b7280;"><strong>Mensaje</strong></td>
                        <td style="padding:12px 14px;line-height:1.5;">{$safeMessage}</td>
                    </tr>
                </table>

                <p style="margin:16px 0 0 0;color:#374151;">Gracias por su atención.</p>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;

        $text = "{$appName} - Recordatorio de pago pendiente\n"
            . "Cliente: {$clientName}\n"
            . "Primer vencimiento: " . ($dueDate ?: 'Sin fecha definida') . "\n"
            . $breakdownText
            . "Mensaje: {$message}";

        try {
            Mail::send([], [], function ($mail) use ($toEmail, $subject, $html, $logoInline) {
                $mail->to($toEmail)->subject($subject);

                $finalHtml = $html;
                if ($logoInline) {
                    $cid = $mail->embedData($logoInline['data'], $logoInline['name'], $logoInline['mime']);
                    $finalHtml = str_replace('__SYSTEM_LOGO_SRC__', $cid, $finalHtml);
                }

                $mail->html($finalHtml);
            });

            return ['sent' => true, 'provider' => 'smtp', 'error' => null];
        } catch (\Throwable $smtpError) {
            $fallbackHtml = $html;
            if ($logoInline) {
                $fallbackHtml = str_replace('__SYSTEM_LOGO_SRC__', $logoInline['data_uri'], $fallbackHtml);
            }

            $fallback = $this->sendViaBrevo($toEmail, $subject, $text, $fallbackHtml);
            if ($fallback['sent']) {
                return $fallback;
            }

            return [
                'sent' => false,
                'provider' => 'smtp',
                'error' => $smtpError->getMessage(),
            ];
        }
    }

    private function buildDebtBreakdown(array $debtsDetail, float $totalDebt, int $debtsCount): array
    {
        $safeAmount = htmlspecialchars('$' . number_format($totalDebt, 2), ENT_QUOTES, 'UTF-8');

        if (empty($debtsDetail)) {
            $html = "<table role='presentation' width='100%' cellspacing='0' cellpadding='0' style='border:1px solid #d1d5db;border-radius:8px;background:#f9fafb;margin-bottom:14px;'>"
                . "<tr><td style='padding:12px 14px;border-bottom:1px solid #e5e7eb;width:38%;color:#6b7280;'><strong>Monto pendiente</strong></td>"
                . "<td style='padding:12px 14px;border-bottom:1px solid #e5e7eb;color:#b91c1c;'><strong>{$safeAmount}</strong></td></tr>"
                . "<tr><td style='padding:12px 14px;color:#6b7280;'><strong>Deudas registradas</strong></td>"
                . "<td style='padding:12px 14px;'>{$debtsCount}</td></tr>"
                . "</table>";

            $text = "Total deudas registradas: {$debtsCount}\nTotal pendiente: $" . number_format($totalDebt, 2) . "\n";

            return [$html, $text];
        }

        $statusLabels = ['active' => 'Activo', 'partial' => 'Pago parcial'];
        $statusColors = ['active' => '#b91c1c', 'partial' => '#0f766e'];
        $rowsHtml = '';
        $rowsText = '';

        foreach ($debtsDetail as $debt) {
            $concept = htmlspecialchars((string) ($debt['concept'] ?? ''), ENT_QUOTES, 'UTF-8');
            $statusKey = (string) ($debt['status'] ?? 'active');
            $statusLabel = $statusLabels[$statusKey] ?? ucfirst($statusKey);
            $statusColor = $statusColors[$statusKey] ?? '#475569';
            $total = '$' . number_format((float) ($debt['total_amount'] ?? 0), 2);
            $paid = '$' . number_format((float) ($debt['paid_amount'] ?? 0), 2);
            $balance = '$' . number_format((float) ($debt['balance'] ?? 0), 2);
            $dueDate = htmlspecialchars((string) ($debt['due_date'] ?? 'Sin fecha'), ENT_QUOTES, 'UTF-8');

            $rowsHtml .= "<tr>"
                . "<td style='padding:9px 10px;border-bottom:1px solid #e5e7eb;'>{$concept}</td>"
                . "<td style='padding:9px 10px;border-bottom:1px solid #e5e7eb;text-align:center;'><span style='background:{$statusColor};color:#fff;padding:2px 8px;border-radius:4px;font-size:12px;'>{$statusLabel}</span></td>"
                . "<td style='padding:9px 10px;border-bottom:1px solid #e5e7eb;text-align:right;'>{$total}</td>"
                . "<td style='padding:9px 10px;border-bottom:1px solid #e5e7eb;text-align:right;color:#166534;'>{$paid}</td>"
                . "<td style='padding:9px 10px;border-bottom:1px solid #e5e7eb;text-align:right;color:#b91c1c;font-weight:700;'>{$balance}</td>"
                . "<td style='padding:9px 10px;border-bottom:1px solid #e5e7eb;text-align:center;'>{$dueDate}</td>"
                . "</tr>";

            $rowsText .= '- ' . ($debt['concept'] ?? '')
                . ' | ' . $statusLabel
                . ' | Monto: $' . number_format((float) ($debt['total_amount'] ?? 0), 2)
                . ' | Pagado: $' . number_format((float) ($debt['paid_amount'] ?? 0), 2)
                . ' | Saldo: $' . number_format((float) ($debt['balance'] ?? 0), 2)
                . ' | Vence: ' . ($debt['due_date'] ?? 'Sin fecha') . "\n";
        }

        $html = "<p style='margin:0 0 8px 0;color:#374151;font-weight:700;'>Detalle de deudas ({$debtsCount}):</p>"
            . "<table role='presentation' width='100%' cellspacing='0' cellpadding='0' style='border:1px solid #d1d5db;border-radius:8px;background:#f9fafb;margin-bottom:14px;'>"
            . "<thead><tr style='background:#e5e7eb;'>"
            . "<th style='padding:9px 10px;text-align:left;font-size:12px;color:#374151;border-bottom:1px solid #d1d5db;'>Concepto</th>"
            . "<th style='padding:9px 10px;text-align:center;font-size:12px;color:#374151;border-bottom:1px solid #d1d5db;'>Estado</th>"
            . "<th style='padding:9px 10px;text-align:right;font-size:12px;color:#374151;border-bottom:1px solid #d1d5db;'>Monto</th>"
            . "<th style='padding:9px 10px;text-align:right;font-size:12px;color:#374151;border-bottom:1px solid #d1d5db;'>Pagado</th>"
            . "<th style='padding:9px 10px;text-align:right;font-size:12px;color:#374151;border-bottom:1px solid #d1d5db;'>Saldo</th>"
            . "<th style='padding:9px 10px;text-align:center;font-size:12px;color:#374151;border-bottom:1px solid #d1d5db;'>Vence</th>"
            . "</tr></thead>"
            . "<tbody>{$rowsHtml}"
            . "<tr style='background:#fef2f2;'>"
            . "<td colspan='4' style='padding:10px 10px;font-weight:700;color:#374151;'>Total pendiente</td>"
            . "<td style='padding:10px 10px;text-align:right;font-weight:700;color:#b91c1c;font-size:15px;'>{$safeAmount}</td>"
            . "<td></td></tr>"
            . "</tbody></table>";

        $text = "Detalle de deudas ({$debtsCount}):\n{$rowsText}Total pendiente: $" . number_format($totalDebt, 2) . "\n";

        return [$html, $text];
    }

    public function sendPaymentThankYouCard(
        string $toEmail,
        string $clientName,
        string $concept,
        float $paidAmount,
        float $remainingBalance,
        string $paymentDate,
        string $status,
        string $message
    ): array {
        $subject = 'Confirmación de pago recibido';
        $appName = (string) config('app.name', 'Systemcobros');

        $safeAppName = htmlspecialchars($appName, ENT_QUOTES, 'UTF-8');
        $safeClient = htmlspecialchars($clientName, ENT_QUOTES, 'UTF-8');
        $safeConcept = htmlspecialchars($concept, ENT_QUOTES, 'UTF-8');
        $safePaid = htmlspecialchars('$' . number_format($paidAmount, 2), ENT_QUOTES, 'UTF-8');
        $safeRemaining = htmlspecialchars('$' . number_format($remainingBalance, 2), ENT_QUOTES, 'UTF-8');
        $safePaymentDate = htmlspecialchars($paymentDate, ENT_QUOTES, 'UTF-8');
        $safeStatus = htmlspecialchars($status, ENT_QUOTES, 'UTF-8');
        $safeMessage = nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));
        $logoInline = $this->systemLogoInlinePayload();
        $logoBlock = $logoInline
            ? "<img src=\"__SYSTEM_LOGO_SRC__\" alt=\"{$safeAppName}\" style=\"width:34px;height:34px;border-radius:7px;object-fit:cover;border:1px solid rgba(255,255,255,.35);background:#fff;\">"
            : "<span style=\"width:34px;height:34px;border-radius:7px;border:1px solid rgba(255,255,255,.35);display:inline-flex;align-items:center;justify-content:center;background:rgba(255,255,255,.18);font-weight:700;\">$</span>";

        $html = <<<HTML
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$safeAppName} - Confirmación de pago</title>
</head>
<body style="margin:0;padding:20px;background:#f3f4f6;font-family:Arial,sans-serif;color:#111827;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:680px;margin:0 auto;background:#ffffff;border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;">
        <tr>
            <td style="padding:16px 20px;background:#166534;color:#ffffff;font-weight:700;font-size:18px;">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                    <tr>
                        <td style="width:40px;vertical-align:middle;">{$logoBlock}</td>
                        <td style="vertical-align:middle;padding-left:8px;">{$safeAppName} | Gracias por su pago</td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td style="padding:20px;">
                <p style="margin:0 0 14px 0;font-size:16px;">Estimado/a <strong>{$safeClient}</strong>,</p>
                <p style="margin:0 0 16px 0;color:#374151;">Hemos registrado correctamente su pago. Compartimos el detalle:</p>

                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border:1px solid #d1d5db;border-radius:8px;background:#f9fafb;">
                    <tr>
                        <td style="padding:12px 14px;border-bottom:1px solid #e5e7eb;width:40%;color:#6b7280;"><strong>Cliente</strong></td>
                        <td style="padding:12px 14px;border-bottom:1px solid #e5e7eb;">{$safeClient}</td>
                    </tr>
                    <tr>
                        <td style="padding:12px 14px;border-bottom:1px solid #e5e7eb;color:#6b7280;"><strong>Concepto</strong></td>
                        <td style="padding:12px 14px;border-bottom:1px solid #e5e7eb;">{$safeConcept}</td>
                    </tr>
                    <tr>
                        <td style="padding:12px 14px;border-bottom:1px solid #e5e7eb;color:#6b7280;"><strong>Monto cancelado</strong></td>
                        <td style="padding:12px 14px;border-bottom:1px solid #e5e7eb;color:#166534;"><strong>{$safePaid}</strong></td>
                    </tr>
                    <tr>
                        <td style="padding:12px 14px;border-bottom:1px solid #e5e7eb;color:#6b7280;"><strong>Fecha de pago</strong></td>
                        <td style="padding:12px 14px;border-bottom:1px solid #e5e7eb;">{$safePaymentDate}</td>
                    </tr>
                    <tr>
                        <td style="padding:12px 14px;border-bottom:1px solid #e5e7eb;color:#6b7280;"><strong>Saldo restante</strong></td>
                        <td style="padding:12px 14px;border-bottom:1px solid #e5e7eb;">{$safeRemaining}</td>
                    </tr>
                    <tr>
                        <td style="padding:12px 14px;border-bottom:1px solid #e5e7eb;color:#6b7280;"><strong>Estado actual</strong></td>
                        <td style="padding:12px 14px;border-bottom:1px solid #e5e7eb;">{$safeStatus}</td>
                    </tr>
                    <tr>
                        <td style="padding:12px 14px;color:#6b7280;"><strong>Mensaje</strong></td>
                        <td style="padding:12px 14px;line-height:1.5;">{$safeMessage}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;

        $text = "{$appName} - Gracias por su pago\n"
            . "Cliente: {$clientName}\n"
            . "Concepto: {$concept}\n"
            . "Monto cancelado: $" . number_format($paidAmount, 2) . "\n"
            . "Fecha de pago: {$paymentDate}\n"
            . "Saldo restante: $" . number_format($remainingBalance, 2) . "\n"
            . "Estado actual: {$status}\n"
            . "Mensaje: {$message}";

        try {
            Mail::send([], [], function ($mail) use ($toEmail, $subject, $html, $logoInline) {
                $mail->to($toEmail)->subject($subject);

                $finalHtml = $html;
                if ($logoInline) {
                    $cid = $mail->embedData($logoInline['data'], $logoInline['name'], $logoInline['mime']);
                    $finalHtml = str_replace('__SYSTEM_LOGO_SRC__', $cid, $finalHtml);
                }

                $mail->html($finalHtml);
            });

            return ['sent' => true, 'provider' => 'smtp', 'error' => null];
        } catch (\Throwable $smtpError) {
            $fallbackHtml = $html;
            if ($logoInline) {
                $fallbackHtml = str_replace('__SYSTEM_LOGO_SRC__', $logoInline['data_uri'], $fallbackHtml);
            }

            $fallback = $this->sendViaBrevo($toEmail, $subject, $text, $fallbackHtml);
            if ($fallback['sent']) {
                return $fallback;
            }

            return [
                'sent' => false,
                'provider' => 'smtp',
                'error' => $smtpError->getMessage(),
            ];
        }
    }

    private function sendViaBrevo(string $toEmail, string $subject, string $body, ?string $htmlBody = null): array
    {
        $apiKey = (string) config('services.brevo.api_key', '');
        if ($apiKey === '') {
            return ['sent' => false, 'provider' => 'brevo', 'error' => 'BREVO_API_KEY no configurado'];
        }

        $senderEmail = (string) config('services.brevo.sender_email', config('mail.from.address'));
        $senderName = (string) config('services.brevo.sender_name', config('mail.from.name', 'Systemcobros'));

        $payload = [
            'sender' => [
                'name' => $senderName,
                'email' => $senderEmail,
            ],
            'to' => [
                ['email' => $toEmail],
            ],
            'subject' => $subject,
            'textContent' => $body,
        ];

        if (!empty($htmlBody)) {
            $payload['htmlContent'] = $htmlBody;
        }

        $response = Http::withHeaders([
            'accept' => 'application/json',
            'api-key' => $apiKey,
            'content-type' => 'application/json',
        ])->post('https://api.brevo.com/v3/smtp/email', $payload);

        if ($response->successful()) {
            return ['sent' => true, 'provider' => 'brevo', 'error' => null];
        }

        return [
            'sent' => false,
            'provider' => 'brevo',
            'error' => 'Brevo HTTP ' . $response->status() . ': ' . $response->body(),
        ];
    }

    private function systemLogoInlinePayload(): ?array
    {
        $logoPath = AppSetting::systemLogoPath();

        if (!$logoPath || !Storage::disk('public')->exists($logoPath)) {
            return null;
        }

        $absolutePath = Storage::disk('public')->path($logoPath);
        $content = @file_get_contents($absolutePath);
        if ($content === false) {
            return null;
        }

        $mime = @mime_content_type($absolutePath) ?: 'image/png';
        $extension = pathinfo($absolutePath, PATHINFO_EXTENSION) ?: 'png';

        return [
            'data' => $content,
            'mime' => $mime,
            'name' => 'system-logo.' . $extension,
            'data_uri' => 'data:' . $mime . ';base64,' . base64_encode($content),
        ];
    }
}
