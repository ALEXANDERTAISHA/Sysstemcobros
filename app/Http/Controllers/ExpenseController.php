<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Client;
use App\Models\Company;
use App\Models\Credit;
use App\Models\CreditPayment;
use App\Support\BranchContext;
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
        $branchId = BranchContext::isPrivileged() ? ($request->integer('branch_id') ?: null) : BranchContext::branchId();

        $creditsQuery = Credit::with('client', 'company', 'branch')
            ->when($search, fn($query) => $query->whereHas('client', fn($clientQuery) => $clientQuery->where('name', 'like', "%{$search}%")))
            ->when($status !== 'all', fn($query) => $query->where('status', $status))
            ->when($date, fn($query) => $query->whereDate('granted_date', $date))
            ->latest();

        if (BranchContext::isPrivileged() && $branchId) {
            $creditsQuery->where('branch_id', $branchId);
        } else {
            BranchContext::scope($creditsQuery);
        }

        $credits = $creditsQuery->paginate(20)->withQueryString();
        $branches = Branch::where('is_active', true)->orderBy('name')->get();

        return view('expenses.index', compact('credits', 'search', 'status', 'date', 'branches', 'branchId'));
    }

    public function create()
    {
        $clients = Client::where('is_active', true)->orderBy('name')->get();
        $companies = Company::where('is_active', true)->ofType(Company::TYPE_EXPENSE_DEBIT)->orderByBusinessList()->get();

        return view('expenses.create', compact('clients', 'companies'));
    }

    public function quickStoreClient(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'phone' => ['nullable', 'regex:/^\d+$/', 'max:30'],
            'email' => 'nullable|email|max:150',
            'whatsapp' => ['nullable', 'regex:/^\d+$/', 'max:30'],
            'address' => 'nullable|string|max:250',
            'notes' => 'nullable|string',
        ], [
            'phone.regex' => 'El telefono solo debe contener numeros.',
            'whatsapp.regex' => 'El WhatsApp solo debe contener numeros.',
            'email.email' => 'Ingresa un correo electronico valido.',
        ]);

        $data['is_active'] = true;

        $client = Client::create($data);

        return response()->json([
            'message' => 'Cliente registrado correctamente.',
            'client' => [
                'id' => $client->id,
                'name' => $client->name,
                'phone' => $client->phone,
            ],
        ]);
    }

    public function quickStoreCompany(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:20|unique:companies,code',
            'color' => 'required|string|max:20',
        ]);

        $data['is_active'] = true;
        $data['company_type'] = Company::TYPE_EXPENSE_DEBIT;

        $company = Company::create($data);

        return response()->json([
            'message' => 'Empresa registrada correctamente.',
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
                'code' => $company->code,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'company_id' => 'required|exists:companies,id',
            'concept' => 'required|string|max:250',
            'total_amount' => 'required|numeric|min:0.01',
            'granted_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:granted_date',
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

        $company = Company::findOrFail($data['company_id']);
        $isTransferenciaZelle = strcasecmp((string) $company->name, 'TRANSFERENCIA ZELLE') === 0;

        if (! $isTransferenciaZelle && empty($data['due_date'])) {
            return back()->withErrors(['due_date' => 'La fecha limite de pago es obligatoria.'])->withInput();
        }

        if ($isTransferenciaZelle) {
            $data['due_date'] = null;
        }

        $data['paid_amount'] = 0;
        $data['status'] = 'active';
        $data = BranchContext::assign($data);

        $credit = Credit::create($data);
        $client = $credit->client;

        if ($client->whatsapp) {
            $message = "Hola {$client->name}, se registró un débito por $" . number_format($credit->total_amount, 2) . " por concepto de: {$credit->concept}. Sistema Cobros.";
            $this->whatsApp->send($client->whatsapp, $message, $client->name, Credit::class, $credit->id);
        }

        return redirect()->route('dashboard')->with('success', 'Débito registrado correctamente.');
    }

    public function show(Credit $credit)
    {
        BranchContext::abortIfForbidden($credit->branch_id);

        $credit->load('client', 'payments');

        return view('expenses.show', compact('credit'));
    }

    public function edit(Credit $credit)
    {
        BranchContext::abortIfForbidden($credit->branch_id);

        $clients = Client::where('is_active', true)->orderBy('name')->get();
        $companies = Company::where('is_active', true)->ofType(Company::TYPE_EXPENSE_DEBIT)->orderByBusinessList()->get();

        return view('expenses.edit', compact('credit', 'clients', 'companies'));
    }

    public function update(Request $request, Credit $credit)
    {
        BranchContext::abortIfForbidden($credit->branch_id);

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
        BranchContext::abortIfForbidden($credit->branch_id);

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
        BranchContext::abortIfForbidden($credit->branch_id);

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
        BranchContext::abortIfForbidden($credit->branch_id);

        $credit->payments()->delete();
        $credit->delete();

        return redirect()->route('expenses.index')->with('success', 'Débito eliminado.');
    }
}
