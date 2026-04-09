<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Client;
use App\Models\Transfer;
use App\Models\User;
use App\Support\BranchContext;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;

class TransferController extends Controller
{
    public function __construct(private WhatsAppService $whatsApp) {}

    public function index(Request $request)
    {
        $search  = $request->get('search');
        $status  = $request->get('status', 'all');
        $date    = $request->get('date');
        $company = $request->get('company_id');

        $transferQuery = Transfer::with('company', 'branch')
            ->when($search, fn($q) => $q->where(function ($sub) use ($search) {
                $sub->where('sender_name', 'like', "%$search%")
                    ->orWhere('receiver_name', 'like', "%$search%")
                    ->orWhere('transaction_code', 'like', "%$search%")
                    ->orWhereHas('branch', fn($branchQuery) => $branchQuery->where('name', 'like', "%$search%"));
            }))
            ->when($status !== 'all', fn($q) => $q->where('status', $status))
            ->when($date, fn($q) => $q->whereDate('transfer_date', $date))
            ->when($company, fn($q) => $q->where('company_id', $company));

        $transfers = BranchContext::scope($transferQuery)
            ->latest()
            ->paginate(25);

        $companies = Company::where('is_active', true)->get();
        $pendingCount = BranchContext::scope(Transfer::where('status', 'pending'))->count();

        return view('transfers.index', compact('transfers', 'search', 'status', 'date', 'company', 'companies', 'pendingCount'));
    }

    public function create()
    {
        $companies = Company::where('is_active', true)->orderBy('name')->get();
        $clients = Client::orderBy('name')->get(['id', 'name', 'phone']);
        $users = User::orderBy('name')->get(['id', 'name']);

        return view('transfers.create', compact('companies', 'clients', 'users'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'company_id'       => 'required|exists:companies,id',
            'transfer_date'    => 'required|date',
            'sender_name'      => 'required|string|max:150',
            'receiver_name'    => 'required|string|max:150',
            'amount'           => 'required|numeric|min:0.01',
            'commission'       => 'nullable|numeric|min:0',
            'transaction_code' => 'nullable|string|max:100',
            'status'           => 'required|in:sent,pending,resent,cancelled',
            'notes'            => 'nullable|string',
        ]);

        if ($request->boolean('from_daily_closing')) {
            $data['sender_name'] = (string) ($request->user()?->branch?->name ?? $request->user()?->name ?? $data['sender_name']);
        }

        $data = BranchContext::assign($data);

        if ($data['status'] === 'sent') {
            $data['sent_at'] = now();
        }
        Transfer::create($data);

        if ($request->boolean('from_daily_closing')) {
            return redirect()
                ->route('daily-closings.create', ['date' => $data['transfer_date']])
                ->with('success', 'Giro registrado correctamente.');
        }

        return redirect()->route('transfers.index')->with('success', 'Giro registrado correctamente.');
    }

    public function edit(Transfer $transfer)
    {
        BranchContext::abortIfForbidden($transfer->branch_id);

        $companies = Company::where('is_active', true)->orderBy('name')->get();
        $clients = Client::orderBy('name')->get(['id', 'name', 'phone']);
        return view('transfers.edit', compact('transfer', 'companies', 'clients'));
    }

    public function update(Request $request, Transfer $transfer)
    {
        BranchContext::abortIfForbidden($transfer->branch_id);

        $data = $request->validate([
            'company_id'       => 'required|exists:companies,id',
            'transfer_date'    => 'required|date',
            'sender_name'      => 'required|string|max:150',
            'receiver_name'    => 'required|string|max:150',
            'amount'           => 'required|numeric|min:0.01',
            'commission'       => 'nullable|numeric|min:0',
            'transaction_code' => 'nullable|string|max:100',
            'status'           => 'required|in:sent,pending,resent,cancelled',
            'notes'            => 'nullable|string',
        ]);
        if ($data['status'] === 'sent' && $transfer->status !== 'sent') {
            $data['sent_at'] = now();
        }
        $transfer->update($data);
        return redirect()->route('transfers.index')->with('success', 'Giro actualizado.');
    }

    public function markSent(Transfer $transfer)
    {
        BranchContext::abortIfForbidden($transfer->branch_id);

        $transfer->update(['status' => 'sent', 'sent_at' => now()]);
        return back()->with('success', 'Giro marcado como enviado.');
    }

    public function resend(Transfer $transfer)
    {
        BranchContext::abortIfForbidden($transfer->branch_id);

        $transfer->update(['status' => 'resent', 'sent_at' => now()]);
        return back()->with('success', 'Giro marcado como reenviado.');
    }

    public function notifyWhatsApp(Request $request, Transfer $transfer)
    {
        BranchContext::abortIfForbidden($transfer->branch_id);

        $request->validate([
            'phone' => 'required|string|max:30',
        ]);

        $msg = "Hola! Tu giro de {$transfer->amount} vía {$transfer->company->name} fue {$transfer->status_label}. Código: {$transfer->transaction_code}. Sistema Cobros.";
        $notification = $this->whatsApp->send($request->phone, $msg, null, Transfer::class, $transfer->id);

        if ($notification->status === 'sent') {
            return back()->with('success', 'Notificación WhatsApp enviada.');
        }

        // Return whatsapp link as fallback
        $link = $this->whatsApp->chatUrl($request->phone, $msg);
        return back()->with('info', "API no configurada. <a href=\"{$link}\" target=\"_blank\">Enviar por WhatsApp Web</a>.");
    }

    public function destroy(Transfer $transfer)
    {
        BranchContext::abortIfForbidden($transfer->branch_id);

        $transfer->delete();
        return redirect()->route('transfers.index')->with('success', 'Giro eliminado.');
    }
}
