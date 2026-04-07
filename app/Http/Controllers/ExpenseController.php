<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Company;
use App\Models\Credit;
use App\Models\CreditPayment;
use App\Services\EmailDeliveryService;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExpenseController extends Controller
{
    public function __construct(
        private WhatsAppService $whatsApp,
        private EmailDeliveryService $emailDelivery
    ) {}

    public function index(Request $request)
    {
        $search = $request->get('search');
        $status = $request->get('status', 'all');
        $date = $request->get('date');

        $creditsQuery = Credit::with('client', 'company')
            ->when($search, fn($query) => $query->whereHas('client', fn($clientQuery) => $clientQuery->where('name', 'like', "%{$search}%")))
            ->when($status !== 'all', fn($query) => $query->where('status', $status))
            ->when($date, fn($query) => $query->whereDate('granted_date', $date))
            ->latest();

        $credits = $creditsQuery->paginate(20);

        return view('expenses.index', compact('credits', 'search', 'status', 'date'));
    }

    public function create()
    {
        $clients = Client::where('is_active', true)->orderBy('name')->get();
        $companies = Company::where('is_active', true)->orderBy('name')->get();

        return view('expenses.create', compact('clients', 'companies'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'company_id' => 'required|exists:companies,id',
            'concept' => 'required|string|max:250',
            'total_amount' => 'required|numeric|min:0.01',
            'granted_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:granted_date',
            'notes' => 'nullable|string',
        ], [
            'required' => 'El campo :attribute es obligatorio.',
            'date' => 'El campo :attribute debe ser una fecha valida.',
            'after_or_equal' => 'La :attribute debe ser una fecha posterior o igual a la fecha de otorgamiento.',
            'numeric' => 'El campo :attribute debe ser numerico.',
            'min' => 'El campo :attribute debe ser al menos :min.',
            'exists' => 'El :attribute seleccionado no es valido.',
            'max' => 'El campo :attribute no debe superar :max caracteres.',
        ], [
            'client_id' => 'cliente',
            'company_id' => 'empresa',
            'concept' => 'concepto',
            'total_amount' => 'monto total',
            'granted_date' => 'fecha de otorgamiento',
            'due_date' => 'fecha limite de pago',
            'notes' => 'notas',
        ]);

        $data['paid_amount'] = 0;
        $data['status'] = 'active';

        $credit = Credit::create($data);
        $client = $credit->client;

        if ($client->whatsapp) {
            $message = "Hola {$client->name}, se registró un débito por $" . number_format($credit->total_amount, 2) . " por concepto de: {$credit->concept}. Sistema Cobros.";
            $this->whatsApp->send($client->whatsapp, $message, $client->name, Credit::class, $credit->id);
        }

        return redirect()->route('expenses.index')->with('success', 'Débito registrado correctamente.');
    }

    public function show(Credit $credit)
    {
        $credit->load('client', 'payments');

        return view('expenses.show', compact('credit'));
    }

    public function edit(Credit $credit)
    {
        $clients = Client::where('is_active', true)->orderBy('name')->get();
        $companies = Company::where('is_active', true)->orderBy('name')->get();

        return view('expenses.edit', compact('credit', 'clients', 'companies'));
    }

    public function update(Request $request, Credit $credit)
    {
        $data = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'company_id' => 'nullable|exists:companies,id',
            'concept' => 'required|string|max:250',
            'total_amount' => 'required|numeric|min:0.01',
            'granted_date' => 'required|date',
            'due_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $credit->update($data);

        return redirect()->route('expenses.index')->with('success', 'Débito actualizado.');
    }

    public function storePayment(Request $request, Credit $credit)
    {
        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $remaining = $credit->total_amount - $credit->paid_amount;
        if ($data['amount'] > $remaining) {
            return back()->withErrors(['amount' => 'El pago excede el saldo pendiente de $' . number_format($remaining, 2)]);
        }

        CreditPayment::create(array_merge($data, ['credit_id' => $credit->id]));

        $newPaid = $credit->paid_amount + $data['amount'];
        $newStatus = $newPaid >= $credit->total_amount ? 'paid' : 'partial';
        $credit->update(['paid_amount' => $newPaid, 'status' => $newStatus]);

        $client = $credit->client;
        $balance = $credit->total_amount - $newPaid;

        $channels = [];

        if (!empty($client->email)) {
            $statusLabel = $newStatus === 'paid' ? 'Pagado' : 'Pago parcial';
            $thankYouMessage = $newStatus === 'paid'
                ? 'Gracias por cancelar su deuda. Su pago fue aplicado correctamente y su saldo está en cero.'
                : 'Gracias por su abono. Su pago fue aplicado correctamente y este es su saldo restante.';

            $emailResult = $this->emailDelivery->sendPaymentThankYouCard(
                $client->email,
                $client->name,
                $credit->concept,
                (float) $data['amount'],
                (float) $balance,
                \Carbon\Carbon::parse($data['payment_date'])->format('d/m/Y'),
                $statusLabel,
                $thankYouMessage
            );

            if ($emailResult['sent']) {
                $channels[] = 'correo';
            }
        }

        if ($client->whatsapp) {
            $message = "Hola {$client->name}, recibimos su pago de $" . number_format($data['amount'], 2) . ". Saldo pendiente: $" . number_format($balance, 2) . ". Gracias!";
            $whatsApp = $this->whatsApp->send($client->whatsapp, $message, $client->name, Credit::class, $credit->id);
            if ($whatsApp->status === 'sent') {
                $channels[] = 'WhatsApp';
            }
        }

        $successMessage = 'Pago registrado correctamente.';
        if (!empty($channels)) {
            $successMessage .= ' Notificación enviada por ' . implode(' y ', $channels) . '.';
        }

        return redirect()->route('expenses.show', $credit)->with('success', $successMessage);
    }

    public function sendReminder(Credit $credit)
    {
        $credit->load('client');
        $client = $credit->client;

        if (!$client || empty($client->whatsapp)) {
            return back()->withErrors(['whatsapp' => 'El cliente no tiene número de WhatsApp registrado.']);
        }

        if ((float) $credit->balance <= 0) {
            return back()->with('info', 'Este débito ya no tiene saldo pendiente.');
        }

        $message = "Hola {$client->name}, le recordamos que tiene un pago pendiente de $" . number_format((float) $credit->balance, 2) . ". "
            . "Concepto: {$credit->concept}. "
            . "Por favor regularice su pago a la brevedad. Gracias.";

        $notification = $this->whatsApp->send(
            $client->whatsapp,
            $message,
            $client->name,
            Credit::class,
            $credit->id
        );

        if ($notification->status === 'sent') {
            return back()->with('success', 'Recordatorio enviado automáticamente por WhatsApp.');
        }

        if ($notification->status === 'pending') {
            return back()->with('warning', 'No se pudo enviar automáticamente: falta configurar CALLMEBOT_API_KEY en .env.');
        }

        return back()->withErrors(['whatsapp' => 'No se pudo enviar el recordatorio por WhatsApp. Revisa la configuración/API.']);
    }

    public function destroy(Credit $credit)
    {
        $credit->payments()->delete();
        $credit->delete();

        return redirect()->route('expenses.index')->with('success', 'Débito eliminado.');
    }
}
