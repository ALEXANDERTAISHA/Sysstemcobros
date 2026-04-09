<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\CashBoxInitial;
use App\Models\Company;
use App\Models\DailyClosing;
use App\Models\Transfer;
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

        $summary = $this->financialSummary->summarizeRange($date, $date);
        $transfersByCompany = $this->financialSummary->transferBreakdownByCompany($date, $date);
        $debits = $this->financialSummary->debitEntries($date, $date);
        $otherIncomes = $this->financialSummary->otherIncomeEntries($date, $date);

        $totalIncomes = $summary['total_incomes'];
        $totalExpenses = $summary['total_expenses'];
        $totalOtherIncomes = $summary['total_other_incomes'];
        $valueTotal = $summary['value_total'];
        $sumTotal = $summary['sum_total'];

        $closing = DailyClosing::whereDate('closing_date', $date)->first();
        $existingValue = (float) CashBoxInitial::whereDate('date', $date)->sum('initial_amount');
        $difference    = $sumTotal - $existingValue;

        $pendingTransfers = Transfer::where('status', 'pending')->count();

        $activeCredits = $summary['active_credit_balance'];

        $companies = Company::where('is_active', true)->get();
        $totalClients = Client::where('is_active', true)->count();

        return view('dashboard.index', compact(
            'date',
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
