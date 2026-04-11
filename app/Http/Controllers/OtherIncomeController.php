<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Client;
use App\Models\Credit;
use App\Models\CreditPayment;
use App\Models\OtherIncome;
use App\Support\BranchContext;
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
        $clientSearch = trim((string) $request->get('client_search', ''));
        $branchId = BranchContext::isPrivileged() ? ($request->integer('branch_id') ?: null) : BranchContext::branchId();

        $incomesQuery = OtherIncome::with('client', 'credit.company', 'branch')
            ->whereDate('income_date', $date)
            ->orderByDesc('income_date')
            ->orderByDesc('id');

        if (!is_null($selectedClientId)) {
            $incomesQuery->where('client_id', $selectedClientId);
        } elseif ($clientSearch !== '' && mb_strlen($clientSearch) >= 2) {
            $incomesQuery->whereHas('client', fn($query) => $query->where('name', 'like', "%{$clientSearch}%"));
        }

        if (BranchContext::isPrivileged() && $branchId) {
            $incomesQuery->where('branch_id', $branchId);
        } else {
            BranchContext::scope($incomesQuery);
        }

        $incomes = $incomesQuery->get();
        $total = $incomes->sum('amount');
        $clients = Client::where('is_active', true)->orderBy('name')->get();

        $pendingDebtsQuery = Credit::with('client', 'company', 'branch')
            ->whereIn('status', ['active', 'partial'])
            ->whereDate('granted_date', '<=', today()->toDateString())
            ->whereRaw('total_amount > paid_amount')
            ->orderByRaw('CASE WHEN due_date IS NULL THEN 1 ELSE 0 END')
            ->orderBy('due_date')
            ->orderBy('id');

        if (BranchContext::isPrivileged() && $branchId) {
            $pendingDebtsQuery->where('branch_id', $branchId);
        } else {
            BranchContext::scope($pendingDebtsQuery);
        }

        if (!is_null($selectedClientId)) {
            $pendingDebtsQuery->where('client_id', $selectedClientId);
        } elseif ($clientSearch !== '' && mb_strlen($clientSearch) >= 2) {
            $pendingDebtsQuery->whereHas('client', fn($query) => $query->where('name', 'like', "%{$clientSearch}%"));
        }

        $pendingDebts = $pendingDebtsQuery->get();

        $pendingDebtTotal = $pendingDebts->sum(fn($credit) => $credit->balance);
        $debtTotals = [
            'active' => (float) $pendingDebts->where('status', 'active')->sum(fn($credit) => $credit->balance),
            'partial' => (float) $pendingDebts->where('status', 'partial')->sum(fn($credit) => $credit->balance),
            'pending' => (float) $pendingDebtTotal,
        ];

        $today = now()->startOfDay();
        $clientDebtBreakdown = [
            'overdue' => (float) $pendingDebts
                ->filter(fn($credit) => $credit->due_date && $credit->due_date->copy()->startOfDay()->lt($today))
                ->sum(fn($credit) => $credit->balance),
            'pending' => (float) $pendingDebts
                ->filter(fn($credit) => !$credit->due_date || $credit->due_date->copy()->startOfDay()->gte($today))
                ->sum(fn($credit) => $credit->balance),
        ];
        $clientDebtBreakdown['total'] = $clientDebtBreakdown['overdue'] + $clientDebtBreakdown['pending'];

        $branches = Branch::where('is_active', true)->orderBy('name')->get();

        return view('other-incomes.index', compact(
            'incomes',
            'date',
            'total',
            'clients',
            'branches',
            'branchId',
            'selectedClientId',
            'clientSearch',
            'pendingDebts',
            'pendingDebtTotal',
            'debtTotals',
            'clientDebtBreakdown'
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
        OtherIncome::create(BranchContext::assign($data));
        return redirect()->route('other-incomes.index', ['date' => $data['income_date']])->with('success', 'Ingreso registrado.');
    }

    public function update(Request $request, OtherIncome $otherIncome)
    {
        BranchContext::abortIfForbidden($otherIncome->branch_id);

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
        BranchContext::abortIfForbidden($otherIncome->branch_id);

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
        BranchContext::abortIfForbidden($credit->branch_id);
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
                'branch_id' => $credit->branch_id,
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

    public function collectClientDebts(Request $request)
    {
        $data = $request->validate([
            'date' => 'required|date',
            'client_search' => 'required|string|max:150',
            'branch_id' => 'nullable|integer',
        ], [
            'client_search.required' => 'Escribe el cliente que deseas cobrar.',
        ]);

        $branchId = BranchContext::isPrivileged() ? ($request->integer('branch_id') ?: null) : BranchContext::branchId();
        $clientSearch = trim((string) $data['client_search']);

        $matchedClients = Client::query()
            ->where('name', 'like', "%{$clientSearch}%")
            ->orderBy('name')
            ->get(['id', 'name']);

        if ($matchedClients->isEmpty()) {
            return redirect()->route('other-incomes.index', [
                'date' => $data['date'],
                'client_search' => $clientSearch,
                'branch_id' => $branchId,
            ])->with('error', 'No se encontró un cliente con ese nombre.');
        }

        $client = $matchedClients->first(function ($candidate) use ($clientSearch) {
            return strcasecmp((string) $candidate->name, $clientSearch) === 0;
        }) ?? ($matchedClients->count() === 1 ? $matchedClients->first() : null);

        if (!$client) {
            return redirect()->route('other-incomes.index', [
                'date' => $data['date'],
                'client_search' => $clientSearch,
                'branch_id' => $branchId,
            ])->with('error', 'Hay varios clientes similares. Escribe el nombre completo para cobrar.');
        }

        $creditsQuery = Credit::with('client')
            ->where('client_id', $client->id)
            ->whereIn('status', ['active', 'partial'])
            ->whereRaw('total_amount > paid_amount')
            ->orderBy('id');

        if (BranchContext::isPrivileged() && $branchId) {
            $creditsQuery->where('branch_id', $branchId);
        } else {
            BranchContext::scope($creditsQuery);
        }

        $credits = $creditsQuery->get();

        if ($credits->isEmpty()) {
            return redirect()->route('other-incomes.index', [
                'date' => $data['date'],
                'client_search' => $client->name,
                'branch_id' => $branchId,
            ])->with('info', 'El cliente seleccionado no tiene deudas pendientes para cobrar.');
        }

        $paymentDate = $data['date'];
        $totalCollected = 0.0;
        $creditsPaid = 0;

        DB::transaction(function () use ($credits, $paymentDate, &$totalCollected, &$creditsPaid) {
            foreach ($credits as $credit) {
                $amount = (float) $credit->balance;

                if ($amount <= 0) {
                    continue;
                }

                CreditPayment::create([
                    'credit_id' => $credit->id,
                    'amount' => $amount,
                    'payment_date' => $paymentDate,
                    'notes' => 'Cobro total aplicado automáticamente desde seguimiento de deudas.',
                ]);

                $credit->update([
                    'paid_amount' => (float) $credit->total_amount,
                    'status' => 'paid',
                ]);

                OtherIncome::create([
                    'income_date' => $paymentDate,
                    'description' => 'Cobro total de débito: ' . $credit->concept,
                    'amount' => $amount,
                    'client_id' => $credit->client_id,
                    'branch_id' => $credit->branch_id,
                    'credit_id' => $credit->id,
                    'notes' => 'Cobro total aplicado automáticamente desde seguimiento de deudas.',
                ]);

                $totalCollected += $amount;
                $creditsPaid++;
            }
        });

        return redirect()->route('dashboard')
            ->with('success', 'Cobro total aplicado: ' . $creditsPaid . ' débito(s), monto $' . number_format($totalCollected, 2) . '.');
    }

    public function sendOverdueReminders(Request $request)
    {
        $branchId = BranchContext::isPrivileged() ? ($request->integer('branch_id') ?: null) : BranchContext::branchId();

        $pendingCreditsQuery = Credit::with('client')
            ->whereIn('status', ['active', 'partial'])
            ->whereRaw('total_amount > paid_amount')
            ->orderBy('due_date');

        if (BranchContext::isPrivileged() && $branchId) {
            $pendingCreditsQuery->where('branch_id', $branchId);
        } else {
            BranchContext::scope($pendingCreditsQuery);
        }

        $pendingCredits = $pendingCreditsQuery->get();

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
