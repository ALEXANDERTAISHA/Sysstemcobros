<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\CashBoxInitial;
use App\Models\Company;
use App\Services\FinancialSummaryService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ReportController extends Controller
{
    public function __construct(private FinancialSummaryService $financialSummary) {}

    public function index(Request $request)
    {
        [$dateFrom, $dateTo, $companyId] = $this->resolveFilters($request);

        $transferQuery = $this->financialSummary->transferQuery($dateFrom, $dateTo, $companyId);

        $transfers = (clone $transferQuery)
            ->latest('transfer_date')
            ->paginate(30)
            ->withQueryString();

        $summary = $this->buildSummary($dateFrom, $dateTo, $companyId);
        $printable = $this->buildPrintableSections($dateFrom, $dateTo, $companyId, $summary);
        $companies = Company::where('is_active', true)->orderBy('name')->get();

        return view('reports.index', compact(
            'dateFrom',
            'dateTo',
            'companyId',
            'transfers',
            'summary',
            'printable',
            'companies'
        ));
    }

    public function exportPdf(Request $request)
    {
        [$dateFrom, $dateTo, $companyId] = $this->resolveFilters($request);

        $transferQuery = $this->financialSummary->transferQuery($dateFrom, $dateTo, $companyId);

        $transfers = (clone $transferQuery)
            ->latest('transfer_date')
            ->get();

        $summary = $this->buildSummary($dateFrom, $dateTo, $companyId);
        $printable = $this->buildPrintableSections($dateFrom, $dateTo, $companyId, $summary);

        $companyName = null;
        if ($companyId) {
            $companyName = Company::whereKey($companyId)->value('name');
        }

        $pdf = Pdf::loadView('reports.pdf.daily-report', [
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'companyId' => $companyId,
            'companyName' => $companyName,
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
        ]);

        $dateFrom = $validated['date_from'] ?? today()->toDateString();
        $dateTo = $validated['date_to'] ?? $dateFrom;
        $companyId = $validated['company_id'] ?? null;

        return [$dateFrom, $dateTo, $companyId];
    }

    private function buildSummary(string $dateFrom, string $dateTo, ?int $companyId): array
    {
        return $this->financialSummary->summarizeRange($dateFrom, $dateTo, $companyId);
    }

    private function buildPrintableSections(string $dateFrom, string $dateTo, ?int $companyId, array $summary): array
    {
        $transfersByCompany = $this->financialSummary->transferBreakdownByCompany($dateFrom, $dateTo, $companyId);
        $debits = $this->financialSummary->debitEntries($dateFrom, $dateTo);
        $otherIncomes = $this->financialSummary->otherIncomeEntries($dateFrom, $dateTo);

        // El valor existente se toma automáticamente del dinero inicial definido
        // para el día o rango del reporte.
        $existingValue = (float) CashBoxInitial::query()
            ->whereBetween('date', [$dateFrom, $dateTo])
            ->sum('initial_amount');
        $difference = (float) $summary['sum_total'] - $existingValue;
        $finalTotal = $difference;

        return [
            'transfers_by_company' => $transfersByCompany,
            'debits' => $debits,
            'other_incomes' => $otherIncomes,
            'existing_value' => $existingValue,
            'difference' => $difference,
            'final_total' => $finalTotal,
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
}
