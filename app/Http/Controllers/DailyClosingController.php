<?php

namespace App\Http\Controllers;

use App\Models\CashBoxInitial;
use App\Models\Company;
use App\Models\DailyClosing;
use App\Models\Branch;
use App\Models\Transfer;
use App\Support\BranchContext;
use App\Services\FinancialSummaryService;
use Illuminate\Http\Request;

class DailyClosingController extends Controller
{
    public function __construct(private FinancialSummaryService $financialSummary) {}

    public function index(Request $request)
    {
        $branchId = null;
        if (BranchContext::isPrivileged()) {
            $branchId = $request->integer('branch_id') ?: null;
        }

        $closingQuery = DailyClosing::with('branch');
        if (BranchContext::isPrivileged() && $branchId) {
            $closingQuery->where('branch_id', $branchId);
        } else {
            BranchContext::scope($closingQuery);
        }

        $closings = $closingQuery
            ->orderByDesc('closing_date')
            ->paginate(20)
            ->withQueryString();

        $branches = Branch::where('is_active', true)->orderBy('name')->get();

        return view('daily-closings.index', compact('closings', 'branches', 'branchId'));
    }

    public function create(Request $request)
    {
        $date = $request->get('date', today()->toDateString());
        $summary = $this->financialSummary->summarizeRange(
            $date,
            $date,
            excludeTodayIncomes: true,
            excludeSameDayCollectedDebits: true
        );
        $transferSearch = trim((string) $request->get('transfer_search', ''));
        $transferStatus = $request->get('transfer_status', 'all');
        $transferListDate = $request->get('transfer_list_date', today()->toDateString());

        $existing = BranchContext::scope(DailyClosing::whereDate('closing_date', $date))->first();
        $cashBoxInitialTotal = (float) BranchContext::scope(CashBoxInitial::whereDate('date', $date))->sum('initial_amount');
        $companies = Company::where('is_active', true)
            ->ofType(Company::TYPE_GENERAL)
            ->orderByBusinessList()
            ->get();

        // Caja chica tambien cuenta como parte de "Otros Ingresos (Fiados)" para el cierre del dia.
        $otherTotal = (float) $summary['total_other_incomes'] + $cashBoxInitialTotal;
        $sumTotal = (float) $summary['value_total'] + $otherTotal;

        $transferQuery = Transfer::with('company', 'branch')
            ->when($transferListDate, fn($q) => $q->whereDate('transfer_date', $transferListDate))
            ->when(mb_strlen($transferSearch) >= 2, fn($q) => $q->where(function ($subQuery) use ($transferSearch) {
                $subQuery->where('sender_name', 'like', "%{$transferSearch}%")
                    ->orWhere('receiver_name', 'like', "%{$transferSearch}%")
                    ->orWhere('transaction_code', 'like', "%{$transferSearch}%")
                    ->orWhereHas('company', fn($companyQuery) => $companyQuery->where('name', 'like', "%{$transferSearch}%"))
                    ->orWhereHas('branch', fn($branchQuery) => $branchQuery->where('name', 'like', "%{$transferSearch}%"));
            }))
            ->when($transferStatus !== 'all', fn($q) => $q->where('status', $transferStatus));

        $transferQuery = BranchContext::scope($transferQuery);

        $transferList = (clone $transferQuery)
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        $transferPendingCount = (clone $transferQuery)->where('status', 'pending')->count();
        $transferTotalCount = BranchContext::scope(Transfer::query())
            ->whereDate('transfer_date', $transferListDate)
            ->count();

        return view('daily-closings.create', compact(
            'date',
            'existing',
            'cashBoxInitialTotal',
            'companies',
            'transferSearch',
            'transferStatus',
            'transferListDate',
            'transferList',
            'transferPendingCount',
            'transferTotalCount'
        ) + [
            'totalIncomes' => $summary['total_incomes'],
            'totalExpenses' => $summary['total_expenses'],
            'otherTotal' => $otherTotal,
            'valueTotal' => $summary['value_total'],
            'sumTotal' => $sumTotal,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'closing_date'        => 'required|date',
            'existing_value'      => 'required|numeric|min:0',
            'notes'               => 'nullable|string',
        ]);

        $summary = $this->financialSummary->summarizeRange(
            $data['closing_date'],
            $data['closing_date'],
            excludeTodayIncomes: true,
            excludeSameDayCollectedDebits: true
        );

        $cashBoxInitialTotal = (float) BranchContext::scope(
            CashBoxInitial::whereDate('date', $data['closing_date'])
        )->sum('initial_amount');

        $otherIncomesTotal = (float) $summary['total_other_incomes'] + $cashBoxInitialTotal;
        $sumTotal = (float) $summary['value_total'] + $otherIncomesTotal;

        $difference = $sumTotal - (float) $data['existing_value'];
        $finalTotal = $difference;

        $payload = BranchContext::assign([
            'total_incomes' => $summary['total_incomes'],
            'total_expenses' => $summary['total_expenses'],
            'value_total' => $summary['value_total'],
            'other_incomes_total' => $otherIncomesTotal,
            'sum_total' => $sumTotal,
            'existing_value' => $data['existing_value'],
            'difference' => $difference,
            'final_total' => $finalTotal,
            'notes' => $data['notes'] ?? null,
        ]);

        $lookup = BranchContext::assign(['closing_date' => $data['closing_date']]);

        DailyClosing::updateOrCreate(
            $lookup,
            $payload
        );

        return redirect()->route('daily-closings.index')->with('success', 'Cierre de caja guardado correctamente.');
    }

    public function show(DailyClosing $dailyClosing)
    {
        BranchContext::abortIfForbidden($dailyClosing->branch_id);

        $date = $dailyClosing->closing_date->toDateString();
        $transfers = $this->financialSummary->transferQuery($date, $date)->get();
        $transfersByCompany = $this->financialSummary->transferBreakdownByCompany($date, $date);
        $debits = $this->financialSummary->debitEntries($date, $date);
        $otherIncomes = $this->financialSummary->otherIncomeEntries($date, $date);

        return view('daily-closings.show', compact(
            'dailyClosing',
            'transfers',
            'transfersByCompany',
            'debits',
            'otherIncomes'
        ));
    }

    public function destroy(DailyClosing $dailyClosing)
    {
        BranchContext::abortIfForbidden($dailyClosing->branch_id);

        $dailyClosing->delete();
        return redirect()->route('daily-closings.index')->with('success', 'Cierre eliminado.');
    }
}
