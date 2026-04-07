<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Credit;
use App\Models\CreditPayment;
use App\Models\OtherIncome;
use App\Services\EmailDeliveryService;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OtherIncomeController extends Controller
{
    public function __construct(
        private EmailDeliveryService $emailDelivery,
        private WhatsAppService $whatsApp
    )
    {
    }
    public function index(Request $request)
    {
        $date = $request->get('date', today()->toDateString());
        $selectedClientId = $request->filled('client_id') ? (int) $request->get('client_id') : null;

        $incomesQuery = OtherIncome::with('client', 'credit')
            ->whereDate('income_date', $date)
            ->orderByDesc('income_date')
            ->orderByDesc('id');

        if (!is_null($selectedClientId)) {
            $incomesQuery->where('client_id', $selectedClientId);
        }

        $incomes = $incomesQuery->get();
        $total = $incomes->sum('amount');
        $clients = Client::where('is_active', true)->orderBy('name')->get();

        $pendingDebtsQuery = Credit::with('client')
            ->whereIn('status', ['active', 'partial'])
            ->whereDate('granted_date', '<', today()->toDateString())
            ->whereRaw('total_amount > paid_amount')
            ->orderByRaw('CASE WHEN due_date IS NULL THEN 1 ELSE 0 END')
            ->orderBy('due_date')
            ->orderBy('id');

        if (!is_null($selectedClientId)) {
            $pendingDebtsQuery->where('client_id', $selectedClientId);
        }

        $pendingDebts = $pendingDebtsQuery->get();

        $pendingDebtTotal = $pendingDebts->sum(fn($credit) => $credit->balance);
        $debtTotals = [
            'active' => (float) $pendingDebts->where('status', 'active')->sum(fn($credit) => $credit->balance),
            'partial' => (float) $pendingDebts->where('status', 'partial')->sum(fn($credit) => $credit->balance),
            'pending' => (float) $pendingDebtTotal,
        ];

        return view('other-incomes.index', compact(
            'incomes',
            'date',
            'total',
            'clients',
            'selectedClientId',
            'pendingDebts',
            'pendingDebtTotal',
            'debtTotals'
        ));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'income_date' => 'required|date',
            'description' => 'required|string|max:250',
            'amount'      => 'required|numeric|min:0.01',
            'client_id'   => 'nullable|exists:clients,id',
            'notes'       => 'nullable|string',
        ]);
        OtherIncome::create($data);
        return redirect()->route('other-incomes.index', ['date' => $data['income_date']])->with('success', 'Ingreso registrado.');
    }

    public function update(Request $request, OtherIncome $otherIncome)
    {
        $data = $request->validate([
            'description' => 'required|string|max:250',
            'amount'      => 'required|numeric|min:0.01',
            'client_id'   => 'nullable|exists:clients,id',
            'notes'       => 'nullable|string',
        ]);

        $otherIncome->update($data);

        return redirect()
            ->route('other-incomes.index', ['date' => $otherIncome->income_date->toDateString()])
            ->with('success', 'Ingreso actualizado.');
    }

    public function destroy(OtherIncome $otherIncome)
    {
        $date = $otherIncome->income_date->toDateString();
        $otherIncome->delete();
        return redirect()->route('other-incomes.index', ['date' => $date])->with('success', 'Ingreso eliminado.');
    }

    public function collectDebit(Request $request)
    {
        $data = $request->validate([
            'credit_id' => 'required|exists:credits,id',
            'payment_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string',
        ]);

        $credit = Credit::with('client')->findOrFail($data['credit_id']);
        $remaining = (float) $credit->balance;

        if ($remaining <= 0) {
            return back()->withErrors(['amount' => 'Este débito ya no tiene saldo pendiente.']);
        }

        if ((float) $data['amount'] > $remaining) {
            return back()->withErrors(['amount' => 'El cobro no puede superar el saldo pendiente de $' . number_format($remaining, 2)]);
        }

        $newPaid = 0.0;
        $newBalance = 0.0;
        $newStatus = 'partial';

        DB::transaction(function () use ($credit, $data, &$newPaid, &$newBalance, &$newStatus) {
            CreditPayment::create([
                'credit_id' => $credit->id,
                'amount' => $data['amount'],
                'payment_date' => $data['payment_date'],
                'notes' => $data['notes'] ?? null,
            ]);

            $newPaid = (float) $credit->paid_amount + (float) $data['amount'];
            $newStatus = $newPaid >= (float) $credit->total_amount ? 'paid' : 'partial';
            $newBalance = max((float) $credit->total_amount - $newPaid, 0);
            $credit->update([
                'paid_amount' => $newPaid,
                'status' => $newStatus,
            ]);

            OtherIncome::create([
                'income_date' => $data['payment_date'],
                'description' => 'Cobro de débito: ' . $credit->concept,
                'amount' => $data['amount'],
                'client_id' => $credit->client_id,
                'credit_id' => $credit->id,
                'notes' => $data['notes'] ?? 'Cobro aplicado desde seguimiento de deudas.',
            ]);
        });

        $client = $credit->client;
        $notifiedChannels = [];

        if ($client && !empty($client->email)) {
            $statusLabel = $newStatus === 'paid' ? 'Pagado' : 'Pago parcial';
            $paymentDate = \Carbon\Carbon::parse($data['payment_date'])->format('d/m/Y');
            $message = $newStatus === 'paid'
                ? 'Gracias por cancelar su deuda. Su pago fue aplicado correctamente y su saldo está en cero.'
                : 'Gracias por su pago. Hemos aplicado el cobro y su deuda continúa activa con saldo pendiente.';

            $emailResult = $this->emailDelivery->sendPaymentThankYouCard(
                $client->email,
                $client->name,
                $credit->concept,
                (float) $data['amount'],
                $newBalance,
                $paymentDate,
                $statusLabel,
                $message
            );

            if ($emailResult['sent']) {
                $notifiedChannels[] = 'correo';
            } else {
                Log::warning('No se pudo enviar correo de confirmación de pago', [
                    'client_id' => $client->id,
                    'email' => $client->email,
                    'error' => $emailResult['error'],
                    'provider' => $emailResult['provider'],
                ]);
            }
        }

        if ($client && !empty($client->whatsapp)) {
            $statusText = $newStatus === 'paid' ? 'PAGADO' : 'PAGO PARCIAL';
            $waMessage = "Estimado/a {$client->name}, gracias por su pago de $" . number_format((float) $data['amount'], 2)
                . " correspondiente a '{$credit->concept}'. "
                . "Saldo restante: $" . number_format($newBalance, 2) . ". "
                . "Estado actual: {$statusText}.";

            $waNotification = $this->whatsApp->send(
                $client->whatsapp,
                $waMessage,
                $client->name,
                Credit::class,
                $credit->id
            );

            if ($waNotification->status === 'sent') {
                $notifiedChannels[] = 'WhatsApp';
            }
        }

        $successMessage = 'Cobro registrado y aplicado al débito correctamente.';
        if (!empty($notifiedChannels)) {
            $successMessage .= ' Notificación enviada por ' . implode(' y ', $notifiedChannels) . '.';
        }

        return redirect()->route('other-incomes.index', ['date' => $data['payment_date']])
            ->with('success', $successMessage);
    }

    public function sendOverdueReminders(Request $request)
    {
        $pendingCredits = Credit::with('client')
            ->whereIn('status', ['active', 'partial'])
            ->whereRaw('total_amount > paid_amount')
            ->orderBy('due_date')
            ->get();

        if ($pendingCredits->isEmpty()) {
            return redirect()->route('other-incomes.index')
                ->with('success', 'No hay deudores pendientes con saldo.');
        }

        $groups       = $pendingCredits->groupBy('client_id');
        $sentCount    = 0;
        $waSentCount  = 0;
        $errors       = 0;
        $firstError   = null;

        foreach ($groups as $clientCredits) {
            $client = $clientCredits->first()->client;
            if (!$client) {
                continue;
            }

            $totalDebt = $clientCredits->sum(fn($c) => (float) $c->balance);
            $count     = $clientCredits->count();
            $oldestDue = $clientCredits->whereNotNull('due_date')->min('due_date');

            $dueDateForEmail = $oldestDue
                ? \Carbon\Carbon::parse($oldestDue)->format('d/m/Y')
                : null;

            $body = "Estimado/a {$client->name},\n\n"
                . "Le recordamos que tiene {$count} deuda(s) pendiente(s) por un total de "
                . '$' . number_format($totalDebt, 2) . ". "
                . ($dueDateForEmail ? "Fecha de vencimiento: {$dueDateForEmail}." : '') . "\n\n"
                . "Por favor regularice su pago a la brevedad posible.\n\n"
                . "Gracias por su atención.";

            // Enviar correo si tiene email
            if (!empty($client->email)) {
                $debtsDetail = $clientCredits->map(fn($c) => [
                    'concept'      => $c->concept,
                    'total_amount' => (float) $c->total_amount,
                    'paid_amount'  => (float) $c->paid_amount,
                    'balance'      => (float) $c->balance,
                    'status'       => $c->status,
                    'due_date'     => $c->due_date?->format('d/m/Y'),
                ])->all();

                $result = $this->emailDelivery->sendDebtReminderCard(
                    $client->email,
                    $client->name,
                    $totalDebt,
                    $dueDateForEmail,
                    $body,
                    $count,
                    $debtsDetail
                );

                if ($result['sent']) {
                    $sentCount++;
                } else {
                    \Illuminate\Support\Facades\Log::error('Error enviando recordatorio por correo', [
                        'client_id' => $client->id,
                        'email'     => $client->email,
                        'error'     => $result['error'],
                        'provider'  => $result['provider'],
                    ]);

                    if ($firstError === null) {
                        $firstError = $result['error'];
                    }
                    $errors++;
                }
            }

            // Enviar WhatsApp si tiene teléfono
            if (!empty($client->phone)) {
                $waMessage = "Estimado/a {$client->name}, le recordamos que tiene {$count} deuda(s) pendiente(s) por un total de $"
                    . number_format($totalDebt, 2)
                    . ($dueDateForEmail ? ". Vencimiento: {$dueDateForEmail}" : '')
                    . ". Por favor regularice su pago. Gracias.";

                try {
                    $this->whatsApp->send($client->phone, $waMessage, $client->name, 'credit', $clientCredits->first()->id);
                    $waSentCount++;
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error('Error enviando recordatorio por WhatsApp', [
                        'client_id' => $client->id,
                        'phone'     => $client->phone,
                        'error'     => $e->getMessage(),
                    ]);
                }
            }
        }

        $clientsWithEmail = $groups->filter(fn($g) => !empty($g->first()->client?->email))->count();
        $clientsWithPhone = $groups->filter(fn($g) => !empty($g->first()->client?->phone))->count();

        if ($errors > 0 && $sentCount === 0 && $waSentCount === 0) {
            return redirect()->route('other-incomes.index')
                ->with('error', 'No se pudo enviar ningún recordatorio. Detalle: ' . ($firstError ?: 'SMTP no disponible. Configura BREVO_API_KEY para fallback por HTTPS.'));
        }

        $msg = "Recordatorios enviados: {$sentCount} correo(s) de {$clientsWithEmail} deudor(es) con email";
        if ($clientsWithPhone > 0) {
            $msg .= ", {$waSentCount} WhatsApp(s) de {$clientsWithPhone} con teléfono";
        }
        $msg .= '.';

        return redirect()->route('other-incomes.index')
            ->with('success', $msg);
    }
}
