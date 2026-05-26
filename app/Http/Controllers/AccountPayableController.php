<?php

namespace App\Http\Controllers;

use App\Models\AccountPayable;
use App\Models\AccountPayablePayment;
use App\Models\Branch;
use App\Models\Client;
use App\Models\Company;
use App\Support\BranchContext;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AccountPayableController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->get('search', ''));
        $status = $request->get('status', 'all');
        $date = $request->get('date');
        $branchId = BranchContext::isPrivileged() ? ($request->integer('branch_id') ?: null) : BranchContext::branchId();

        $accountsQuery = AccountPayable::with('client', 'company', 'branch')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($sub) use ($search) {
                    $sub->where('concept', 'like', "%{$search}%")
                        ->orWhereHas('client', fn($clientQuery) => $clientQuery->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('company', fn($companyQuery) => $companyQuery->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($status !== 'all', fn($query) => $query->where('status', $status))
            ->when($date, fn($query) => $query->whereDate('issued_date', $date))
            ->latest();

        if (BranchContext::isPrivileged() && $branchId) {
            $accountsQuery->where('branch_id', $branchId);
        } else {
            BranchContext::scope($accountsQuery);
        }

        $accounts = $accountsQuery->paginate(20)->withQueryString();
        $branches = Branch::where('is_active', true)->orderBy('name')->get();

        return view('accounts-payable.index', compact('accounts', 'search', 'status', 'date', 'branches', 'branchId'));
    }

    public function create()
    {
        $clients = Client::where('is_active', true)->orderBy('name')->get();
        $companies = Company::where('is_active', true)->ofType(Company::TYPE_EXPENSE_DEBIT)->orderByBusinessList()->get();

        return view('accounts-payable.create', compact('clients', 'companies'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'company_id' => 'nullable|exists:companies,id',
            'concept' => 'required|string|max:250',
            'total_amount' => 'required|numeric|min:0.01',
            'issued_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:issued_date',
            'notes' => 'nullable|string',
        ]);

        if (empty($data['due_date'])) {
            $data['due_date'] = Carbon::parse($data['issued_date'])->addDays(7)->toDateString();
        }

        $data['paid_amount'] = 0;
        $data['status'] = 'active';
        $data = BranchContext::assign($data);

        $account = AccountPayable::create($data);

        return redirect()->route('accounts-payable.show', $account)->with('success', 'Cuenta por pagar registrada correctamente.');
    }

    public function show(AccountPayable $accountPayable)
    {
        BranchContext::abortIfForbidden($accountPayable->branch_id);
        $accountPayable->load('client', 'company', 'payments');

        return view('accounts-payable.show', compact('accountPayable'));
    }

    public function storePayment(Request $request, AccountPayable $accountPayable)
    {
        BranchContext::abortIfForbidden($accountPayable->branch_id);

        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        if ((float) $data['amount'] > (float) $accountPayable->balance) {
            return back()->withErrors(['amount' => 'El pago excede el saldo pendiente de $' . number_format($accountPayable->balance, 2)]);
        }

        AccountPayablePayment::create($data + ['account_payable_id' => $accountPayable->id]);

        $paidAmount = (float) $accountPayable->paid_amount + (float) $data['amount'];
        $status = $paidAmount >= (float) $accountPayable->total_amount ? 'paid' : 'partial';
        $accountPayable->update([
            'paid_amount' => $paidAmount,
            'status' => $status,
        ]);

        return redirect()->route('accounts-payable.show', $accountPayable)->with('success', 'Pago registrado correctamente.');
    }

    public function destroy(AccountPayable $accountPayable)
    {
        BranchContext::abortIfForbidden($accountPayable->branch_id);
        $accountPayable->payments()->delete();
        $accountPayable->delete();

        return redirect()->route('accounts-payable.index')->with('success', 'Cuenta por pagar eliminada.');
    }
}
