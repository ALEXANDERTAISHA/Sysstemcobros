<?php

namespace App\Http\Controllers;

use App\Models\CashBoxInitial;
use App\Models\DailyClosing;
use App\Services\FinancialSummaryService;
use Illuminate\Http\Request;

class DailyClosingController extends Controller
{
    public function __construct(private FinancialSummaryService $financialSummary) {}

    public function index()
    {
        $closings = DailyClosing::orderByDesc('closing_date')->paginate(20);
        return view('daily-closings.index', compact('closings'));
    }

    public function create(Request $request)
    {
        $date = $request->get('date', today()->toDateString());
        $summary = $this->financialSummary->summarizeRange($date, $date);

        $existing = DailyClosing::whereDate('closing_date', $date)->first();
        $cashBoxInitial = CashBoxInitial::whereDate('date', $date)->first();

        return view('daily-closings.create', compact(
            'date',
            'existing',
            'cashBoxInitial'
        ) + [
            'totalIncomes' => $summary['total_incomes'],
            'totalExpenses' => $summary['total_expenses'],
            'otherTotal' => $summary['total_other_incomes'],
            'valueTotal' => $summary['value_total'],
            'sumTotal' => $summary['sum_total'],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'closing_date'        => 'required|date',
            'existing_value'      => 'required|numeric|min:0',
            'notes'               => 'nullable|string',
        ]);

        $summary = $this->financialSummary->summarizeRange($data['closing_date'], $data['closing_date']);
        $difference = $summary['sum_total'] - (float) $data['existing_value'];
        $finalTotal = $difference;

        DailyClosing::updateOrCreate(
            ['closing_date' => $data['closing_date']],
            [
                'total_incomes' => $summary['total_incomes'],
                'total_expenses' => $summary['total_expenses'],
                'value_total' => $summary['value_total'],
                'other_incomes_total' => $summary['total_other_incomes'],
                'sum_total' => $summary['sum_total'],
                'existing_value' => $data['existing_value'],
                'difference' => $difference,
                'final_total' => $finalTotal,
                'notes' => $data['notes'] ?? null,
            ]
        );

        return redirect()->route('daily-closings.index')->with('success', 'Cierre de caja guardado correctamente.');
    }

    public function show(DailyClosing $dailyClosing)
    {
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
        $dailyClosing->delete();
        return redirect()->route('daily-closings.index')->with('success', 'Cierre eliminado.');
    }
}
