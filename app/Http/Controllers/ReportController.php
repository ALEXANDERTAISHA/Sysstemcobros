<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\Branch;
use App\Models\CashBoxInitial;
use App\Models\Company;
use App\Models\DailyClosing;
use App\Support\BranchContext;
use App\Services\FinancialSummaryService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class ReportController extends Controller
{
    public function __construct(private FinancialSummaryService $financialSummary) {}

    public function index(Request $request)
    {
        [$dateFrom, $dateTo, $companyId, $branchId] = $this->resolveFilters($request);

        $transferQuery = $this->financialSummary->transferQuery($dateFrom, $dateTo, $companyId, $branchId);

        $transfers = (clone $transferQuery)
            ->latest('transfer_date')
            ->paginate(30)
            ->withQueryString();

        $summary = $this->buildSummary($dateFrom, $dateTo, $companyId, $branchId);
        $printable = $this->buildPrintableSections($dateFrom, $dateTo, $companyId, $summary, $branchId);
        $companies = Company::where('is_active', true)->orderByBusinessList()->get();
        $branches = Branch::where('is_active', true)->orderBy('name')->get();

        return view('reports.index', compact(
            'dateFrom',
            'dateTo',
            'companyId',
            'branchId',
            'transfers',
            'summary',
            'printable',
            'companies',
            'branches'
        ));
    }

    public function exportPdf(Request $request)
    {
        [$dateFrom, $dateTo, $companyId, $branchId] = $this->resolveFilters($request);

        $transferQuery = $this->financialSummary->transferQuery($dateFrom, $dateTo, $companyId, $branchId);

        $transfers = (clone $transferQuery)
            ->latest('transfer_date')
            ->get();

        $summary = $this->buildSummary($dateFrom, $dateTo, $companyId, $branchId);
        $printable = $this->buildPrintableSections($dateFrom, $dateTo, $companyId, $summary, $branchId);

        $companyName = null;
        if ($companyId) {
            $companyName = Company::whereKey($companyId)->value('name');
        }

        $branchName = null;
        if ($branchId) {
            $branchName = Branch::whereKey($branchId)->value('name');
        }

        $pdf = Pdf::loadView('reports.pdf.daily-report', [
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'companyId' => $companyId,
            'companyName' => $companyName,
            'branchId' => $branchId,
            'branchName' => $branchName,
            'transfers' => $transfers,
            'summary' => $summary,
            'printable' => $printable,
            'systemLogoDataUri' => $this->systemLogoDataUri(),
        ])->setPaper('a4', 'portrait');

        $fileName = "reporte_cobros_{$dateFrom}_{$dateTo}.pdf";

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"{$fileName}\"",
        ]);
    }

    private function resolveFilters(Request $request): array
    {
        $validated = $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'company_id' => 'nullable|exists:companies,id',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        $dateFrom = $validated['date_from'] ?? today()->toDateString();
        $dateTo = $validated['date_to'] ?? $dateFrom;
        $companyId = $validated['company_id'] ?? null;
        $branchId = BranchContext::isPrivileged()
            ? ($validated['branch_id'] ?? null)
            : BranchContext::branchId();

        return [$dateFrom, $dateTo, $companyId, $branchId];
    }

    private function buildSummary(string $dateFrom, string $dateTo, ?int $companyId, ?int $branchId): array
    {
        $closingAggregate = $this->dailyClosingAggregate($dateFrom, $dateTo, $branchId);
        if (!is_null($closingAggregate)) {
            return [
                'transfers_count' => 0,
                'total_incomes' => (float) $closingAggregate['total_incomes'],
                'total_expenses' => (float) $closingAggregate['total_expenses'],
                'total_debits' => (float) $closingAggregate['total_expenses'],
                'total_other_incomes' => (float) $closingAggregate['other_incomes_total'],
                'value_total' => (float) $closingAggregate['value_total'],
                'sum_total' => (float) $closingAggregate['sum_total'],
                'active_credit_balance' => 0.0,
                'existing_value' => (float) $closingAggregate['existing_value'],
                'difference' => (float) $closingAggregate['difference'],
                'final_total' => (float) $closingAggregate['final_total'],
            ];
        }

        $isSingleDay = $dateFrom === $dateTo;

        $summary = $this->financialSummary->summarizeRange(
            $dateFrom,
            $dateTo,
            $companyId,
            $branchId,
            excludeTodayIncomes: $isSingleDay,
            excludeSameDayCollectedDebits: $isSingleDay
        );

        $cashBoxInitialTotal = $this->cashBoxInitialTotal($dateFrom, $dateTo, $branchId);
        $summary['total_other_incomes'] = (float) $summary['total_other_incomes'] + $cashBoxInitialTotal;
        $summary['sum_total'] = (float) $summary['value_total'] + (float) $summary['total_other_incomes'];
        $summary['existing_value'] = $cashBoxInitialTotal;
        $summary['difference'] = (float) $summary['sum_total'] - $cashBoxInitialTotal;
        $summary['final_total'] = $summary['difference'];

        return $summary;
    }

    private function buildPrintableSections(string $dateFrom, string $dateTo, ?int $companyId, array $summary, ?int $branchId): array
    {
        $transfersByCompany = $this->financialSummary->transferBreakdownByCompany($dateFrom, $dateTo, $companyId, $branchId);
        $debits = $this->financialSummary->debitEntries($dateFrom, $dateTo, $branchId);
        $otherIncomes = $this->financialSummary->otherIncomeEntries($dateFrom, $dateTo, $branchId);
        $cashBoxEntries = $this->cashBoxInitialEntries($dateFrom, $dateTo, $branchId);
        $otherIncomes = $otherIncomes->concat($cashBoxEntries);

        $dailyClosingNotesQuery = DailyClosing::query()
            ->whereBetween('closing_date', [$dateFrom, $dateTo]);

        if (BranchContext::isPrivileged() && $branchId) {
            $dailyClosingNotesQuery->where('branch_id', $branchId);
        } else {
            BranchContext::scope($dailyClosingNotesQuery);
        }

        $closingNotes = $dailyClosingNotesQuery
            ->whereNotNull('notes')
            ->orderBy('closing_date')
            ->pluck('notes')
            ->map(fn($note) => trim((string) $note))
            ->filter(fn($note) => $note !== '')
            ->unique()
            ->implode(' | ');

        $existingValue = (float) ($summary['existing_value'] ?? 0);
        $difference = (float) ($summary['difference'] ?? ((float) $summary['sum_total'] - $existingValue));
        $finalTotal = (float) ($summary['final_total'] ?? $difference);

        return [
            'transfers_by_company' => $transfersByCompany,
            'debits' => $debits,
            'other_incomes' => $otherIncomes,
            'existing_value' => $existingValue,
            'difference' => $difference,
            'final_total' => $finalTotal,
            'closing_notes' => $closingNotes,
        ];
    }

    private function systemLogoDataUri(): ?string
    {
        $logoPath = AppSetting::systemLogoPath();

        if (!$logoPath || !Storage::disk('public')->exists($logoPath)) {
            return null;
        }

        $absolutePath = Storage::disk('public')->path($logoPath);
        $mimeType = mime_content_type($absolutePath) ?: 'image/png';
        $binary = file_get_contents($absolutePath);

        if ($binary === false) {
            return null;
        }

        return 'data:' . $mimeType . ';base64,' . base64_encode($binary);
    }

    private function cashBoxInitialTotal(string $dateFrom, string $dateTo, ?int $branchId): float
    {
        $query = CashBoxInitial::query()->whereBetween('date', [$dateFrom, $dateTo]);

        if (BranchContext::isPrivileged() && $branchId) {
            $query->where('branch_id', $branchId);
        } else {
            BranchContext::scope($query);
        }

        return (float) $query->sum('initial_amount');
    }

    private function cashBoxInitialEntries(string $dateFrom, string $dateTo, ?int $branchId): Collection
    {
        $query = CashBoxInitial::query()
            ->whereBetween('date', [$dateFrom, $dateTo])
            ->orderBy('date')
            ->orderBy('id');

        if (BranchContext::isPrivileged() && $branchId) {
            $query->where('branch_id', $branchId);
        } else {
            BranchContext::scope($query);
        }

        return $query->get()->map(function (CashBoxInitial $entry) {
            $note = trim((string) ($entry->notes ?? ''));

            return (object) [
                'amount' => (float) $entry->initial_amount,
                'description' => 'Caja chica' . ($note !== '' ? ': ' . $note : ''),
                'client' => null,
            ];
        });
    }

    private function dailyClosingAggregate(string $dateFrom, string $dateTo, ?int $branchId): ?array
    {
        $query = DailyClosing::query()->whereBetween('closing_date', [$dateFrom, $dateTo]);

        if (BranchContext::isPrivileged() && $branchId) {
            $query->where('branch_id', $branchId);
        } else {
            BranchContext::scope($query);
        }

        if (! $query->exists()) {
            return null;
        }

        return [
            'total_incomes' => (float) $query->sum('total_incomes'),
            'total_expenses' => (float) $query->sum('total_expenses'),
            'value_total' => (float) $query->sum('value_total'),
            'other_incomes_total' => (float) $query->sum('other_incomes_total'),
            'sum_total' => (float) $query->sum('sum_total'),
            'existing_value' => (float) $query->sum('existing_value'),
            'difference' => (float) $query->sum('difference'),
            'final_total' => (float) $query->sum('final_total'),
        ];
    }
}
