<?php

namespace App\Console\Commands;

use App\Models\Credit;
use App\Services\EmailDeliveryService;
use App\Services\WhatsAppService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendOverdueDebtReminders extends Command
{
    protected $signature = 'debts:send-overdue-reminders';

    protected $description = 'Send email and WhatsApp reminders to clients with overdue debts';

    public function __construct(
        private WhatsAppService $whatsApp,
        private EmailDeliveryService $emailDelivery
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $overdueCredits = Credit::with('client')
            ->whereIn('status', ['active', 'partial'])
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', today())
            ->whereRaw('total_amount > paid_amount')
            ->orderBy('due_date')
            ->get();

        if ($overdueCredits->isEmpty()) {
            $this->info('No overdue debts found.');
            return self::SUCCESS;
        }

        $groups = $overdueCredits->groupBy('client_id');
        $sentEmails = 0;
        $sentWhatsapp = 0;

        foreach ($groups as $clientCredits) {
            $client = $clientCredits->first()->client;
            if (!$client) {
                continue;
            }

            $totalDebt = $clientCredits->sum(fn ($credit) => (float) $credit->balance);
            $creditsCount = $clientCredits->count();
            $oldestDueDate = $clientCredits->min('due_date');

            $message = "Estimado/a {$client->name}, registramos {$creditsCount} débito(s) vencido(s). "
                . "Total pendiente: $" . number_format($totalDebt, 2) . ". "
                . "Primer vencimiento: " . optional($oldestDueDate)->format('d/m/Y') . ". "
                . "Por favor regularice su pago a la brevedad. Gracias.";

            if (!empty($client->email)) {
                $debtsDetail = $clientCredits->map(fn($c) => [
                    'concept'      => $c->concept,
                    'total_amount' => (float) $c->total_amount,
                    'paid_amount'  => (float) $c->paid_amount,
                    'balance'      => (float) $c->balance,
                    'status'       => $c->status,
                    'due_date'     => $c->due_date?->format('d/m/Y'),
                ])->all();

                $emailResult = $this->emailDelivery->sendDebtReminderCard(
                    $client->email,
                    $client->name,
                    $totalDebt,
                    optional($oldestDueDate)->format('d/m/Y'),
                    $message,
                    $creditsCount,
                    $debtsDetail
                );

                if ($emailResult['sent']) {
                    $sentEmails++;
                } else {
                    Log::error('Error sending overdue reminder email', [
                        'client_id' => $client->id,
                        'email' => $client->email,
                        'error' => $emailResult['error'],
                        'provider' => $emailResult['provider'],
                    ]);
                }
            }

            $waPhone = $client->whatsapp ?? $client->phone ?? null;
            if (!empty($waPhone)) {
                try {
                    $result = $this->whatsApp->send(
                        $waPhone,
                        $message,
                        $client->name,
                        'credit',
                        $clientCredits->first()->id
                    );

                    if ($result->status === 'sent') {
                        $sentWhatsapp++;
                    }
                } catch (\Throwable $e) {
                    Log::error('Error sending overdue reminder WhatsApp', [
                        'client_id' => $client->id,
                        'phone'     => $waPhone,
                        'error'     => $e->getMessage(),
                    ]);
                }
            }
        }

        $this->info("Overdue reminders done. Emails: {$sentEmails} | WhatsApp: {$sentWhatsapp}");

        return self::SUCCESS;
    }
}
