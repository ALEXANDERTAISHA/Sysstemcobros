<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Credit;
use App\Models\OtherIncome;
use App\Models\Transfer;
use App\Support\BranchContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FinancialSummaryService
{
    public function transferQuery(string $dateFrom, string $dateTo, ?int $companyId = null, ?int $branchId = null): Builder
    {
        $query = Transfer::query()
            ->with('company', 'branch')
            ->whereDate('transfer_date', '>=', $dateFrom)
            ->whereDate('transfer_date', '<=', $dateTo)
            ->whereNotIn('status', ['cancelled'])
            ->when($companyId, fn(Builder $query) => $query->where('company_id', $companyId));

        return $this->scopeByBranch($query, $branchId);
    }

    public function debitQuery(string $dateFrom, string $dateTo, ?int $branchId = null): Builder
    {
        $query = Credit::query()
            ->with('client')
            ->whereDate('granted_date', '>=', $dateFrom)
            ->whereDate('granted_date', '<=', $dateTo);

        return $this->scopeByBranch($query, $branchId);
    }

    public function otherIncomeQuery(string $dateFrom, string $dateTo, ?int $branchId = null): Builder
    {
        $query = OtherIncome::query()
            ->with('client')
            ->whereDate('income_date', '>=', $dateFrom)
            ->whereDate('income_date', '<=', $dateTo);

        return $this->scopeByBranch($query, $branchId);
    }

    public function summarizeRange(
        string $dateFrom,
        string $dateTo,
        ?int $companyId = null,
        ?int $branchId = null,
        bool $excludeTodayIncomes = false,
        bool $excludeSameDayCollectedDebits = false
    ): array
    {
        $transferQuery = $this->transferQuery($dateFrom, $dateTo, $companyId, $branchId);
        $debitQuery = $this->debitQuery($dateFrom, $dateTo, $branchId);
        
        // Si es para cierre de caja (same day), excluir cobros de créditos creados hoy
        $otherIncomeQuery = $excludeTodayIncomes && $dateFrom === $dateTo
            ? OtherIncome::query()
                ->with('client')
                ->whereDate('income_date', '>=', $dateFrom)
                ->whereDate('income_date', '<=', $dateTo)
                ->whereHas('credit', fn(Builder $q) => $q->whereDate('granted_date', '<', $dateFrom))
                ->tap(fn($q) => $this->scopeByBranch($q, $branchId))
            : $this->otherIncomeQuery($dateFrom, $dateTo, $branchId);

        $totalIncomes = (float) (clone $transferQuery)->sum('amount');
        $totalDebits = $excludeSameDayCollectedDebits && $dateFrom === $dateTo
            ? $this->calculateSameDayDebitExposure($dateFrom, $branchId)
            : (float) (clone $debitQuery)->sum('total_amount');
        $totalOtherIncomes = (float) (clone $otherIncomeQuery)->sum('amount');
        $transfersCount = (int) (clone $transferQuery)->count();
        $activeCreditQuery = Credit::query()
            ->whereIn('status', ['active', 'partial'])
            ->selectRaw('SUM(total_amount - paid_amount) as balance_total');
        $activeCreditBalance = (float) $this->scopeByBranch($activeCreditQuery, $branchId)->value('balance_total');

        $valueTotal = $totalIncomes - $totalDebits;
        $sumTotal = $valueTotal + $totalOtherIncomes;

        return [
            'transfers_count' => $transfersCount,
            'total_incomes' => $totalIncomes,
            'total_expenses' => $totalDebits,
            'total_debits' => $totalDebits,
            'total_other_incomes' => $totalOtherIncomes,
            'value_total' => $valueTotal,
            'sum_total' => $sumTotal,
            'active_credit_balance' => $activeCreditBalance,
        ];
    }

    private function calculateSameDayDebitExposure(string $date, ?int $branchId = null): float
    {
        $credits = $this->scopeByBranch(
            Credit::query()
                ->whereDate('granted_date', $date)
                ->with([
                    'payments' => fn($query) => $query->whereDate('payment_date', $date),
                ]),
            $branchId
        )->get(['id', 'total_amount']);

        return (float) $credits->sum(function (Credit $credit): float {
            $sameDayCollected = (float) $credit->payments->sum('amount');
            return max((float) $credit->total_amount - $sameDayCollected, 0);
        });
    }

    public function transferBreakdownByCompany(string $dateFrom, string $dateTo, ?int $companyId = null, ?int $branchId = null): Collection
    {
        $effectiveBranchId = $branchId;
        $restrictByBranch = false;

        if (!BranchContext::isPrivileged()) {
            $effectiveBranchId = BranchContext::branchId();
            $restrictByBranch = (bool) $effectiveBranchId;
        } elseif ($effectiveBranchId) {
            $restrictByBranch = true;
        }

        return Company::query()
            ->where('is_active', true)
            ->withCount([
                'transfers as transfers_count' => fn(Builder $query) => $query
                    ->whereDate('transfer_date', '>=', $dateFrom)
                    ->whereDate('transfer_date', '<=', $dateTo)
                    ->whereNotIn('status', ['cancelled'])
                    ->when($restrictByBranch, fn(Builder $inner) => $inner->where('branch_id', $effectiveBranchId))
                    ->when($companyId, fn(Builder $inner) => $inner->where('company_id', $companyId)),
            ])
            ->withSum([
                'transfers as transfers_total_amount' => fn(Builder $query) => $query
                    ->whereDate('transfer_date', '>=', $dateFrom)
                    ->whereDate('transfer_date', '<=', $dateTo)
                    ->whereNotIn('status', ['cancelled'])
                    ->when($restrictByBranch, fn(Builder $inner) => $inner->where('branch_id', $effectiveBranchId))
                    ->when($companyId, fn(Builder $inner) => $inner->where('company_id', $companyId)),
            ], 'amount')
            ->orderByRaw('COALESCE(transfers_total_amount, 0) DESC')
            ->orderBy('name')
            ->get();
    }

    public function debitEntries(string $dateFrom, string $dateTo, ?int $branchId = null): Collection
    {
        return $this->debitQuery($dateFrom, $dateTo, $branchId)
            ->orderBy('granted_date')
            ->orderBy('id')
            ->get();
    }

    public function otherIncomeEntries(string $dateFrom, string $dateTo, ?int $branchId = null): Collection
    {
        return $this->otherIncomeQuery($dateFrom, $dateTo, $branchId)
            ->orderBy('income_date')
            ->orderBy('id')
            ->get();
    }

    private function scopeByBranch(Builder $query, ?int $branchId = null): Builder
    {
        if (BranchContext::isPrivileged() && $branchId) {
            return $query->where('branch_id', $branchId);
        }

        return BranchContext::scope($query);
    }
}
