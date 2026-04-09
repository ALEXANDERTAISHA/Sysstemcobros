<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Branch;
use App\Models\CashBoxInitial;
use App\Models\Company;
use App\Models\DailyClosing;
use App\Models\Transfer;
use App\Support\BranchContext;
use App\Services\FinancialSummaryService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function __construct(private FinancialSummaryService $financialSummary) {}

    public function index(Request $request)
    {
        $date = $request->get('date', today()->toDateString());
        $carbonDate = Carbon::parse($date);
        $branchId = BranchContext::isPrivileged() ? ($request->integer('branch_id') ?: null) : BranchContext::branchId();

        $summary = $this->financialSummary->summarizeRange($date, $date, null, $branchId);
        $transfersByCompany = $this->financialSummary->transferBreakdownByCompany($date, $date, null, $branchId);
        $debits = $this->financialSummary->debitEntries($date, $date, $branchId);
        $otherIncomes = $this->financialSummary->otherIncomeEntries($date, $date, $branchId);

        $totalIncomes = $summary['total_incomes'];
        $totalExpenses = $summary['total_expenses'];
        $totalOtherIncomes = $summary['total_other_incomes'];
        $valueTotal = $summary['value_total'];
        $sumTotal = $summary['sum_total'];

        $closingQuery = DailyClosing::whereDate('closing_date', $date);
        if (BranchContext::isPrivileged() && $branchId) {
            $closingQuery->where('branch_id', $branchId);
        } else {
            BranchContext::scope($closingQuery);
        }
        $closing = $closingQuery->first();

        $existingQuery = CashBoxInitial::whereDate('date', $date);
        if (BranchContext::isPrivileged() && $branchId) {
            $existingQuery->where('branch_id', $branchId);
        } else {
            BranchContext::scope($existingQuery);
        }
        $existingValue = (float) $existingQuery->sum('initial_amount');
        $difference    = $sumTotal - $existingValue;

        $pendingQuery = Transfer::where('status', 'pending');
        if (BranchContext::isPrivileged() && $branchId) {
            $pendingQuery->where('branch_id', $branchId);
        } else {
            BranchContext::scope($pendingQuery);
        }
        $pendingTransfers = $pendingQuery->count();

        $activeCredits = $summary['active_credit_balance'];

        $companies = Company::where('is_active', true)->get();
        $totalClients = Client::where('is_active', true)->count();
        $branches = Branch::where('is_active', true)->orderBy('name')->get();

        return view('dashboard.index', compact(
            'date',
            'branchId',
            'branches',
            'carbonDate',
            'transfersByCompany',
            'companies',
            'totalIncomes',
            'debits',
            'totalExpenses',
            'otherIncomes',
            'totalOtherIncomes',
            'valueTotal',
            'sumTotal',
            'existingValue',
            'difference',
            'closing',
            'pendingTransfers',
            'activeCredits',
            'totalClients'
        ));
    }
}
