<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Credit;
use App\Models\OtherIncome;
use App\Models\Transfer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FinancialSummaryService
{
    public function transferQuery(string $dateFrom, string $dateTo, ?int $companyId = null): Builder
    {
        return Transfer::query()
            ->with('company')
            ->whereDate('transfer_date', '>=', $dateFrom)
            ->whereDate('transfer_date', '<=', $dateTo)
            ->whereNotIn('status', ['cancelled'])
            ->when($companyId, fn(Builder $query) => $query->where('company_id', $companyId));
    }

    public function debitQuery(string $dateFrom, string $dateTo): Builder
    {
        return Credit::query()
            ->with('client')
            ->whereDate('granted_date', '>=', $dateFrom)
            ->whereDate('granted_date', '<=', $dateTo);
    }

    public function otherIncomeQuery(string $dateFrom, string $dateTo): Builder
    {
        return OtherIncome::query()
            ->with('client')
            ->whereDate('income_date', '>=', $dateFrom)
            ->whereDate('income_date', '<=', $dateTo);
    }

    public function summarizeRange(string $dateFrom, string $dateTo, ?int $companyId = null): array
    {
        $transferQuery = $this->transferQuery($dateFrom, $dateTo, $companyId);
        $debitQuery = $this->debitQuery($dateFrom, $dateTo);
        $otherIncomeQuery = $this->otherIncomeQuery($dateFrom, $dateTo);

        $totalIncomes = (float) (clone $transferQuery)->sum('amount');
        $totalDebits = (float) (clone $debitQuery)->sum('total_amount');
        $totalOtherIncomes = (float) (clone $otherIncomeQuery)->sum('amount');
        $transfersCount = (int) (clone $transferQuery)->count();
        $activeCreditBalance = (float) Credit::query()
            ->whereIn('status', ['active', 'partial'])
            ->sum(DB::raw('total_amount - paid_amount'));

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

    public function transferBreakdownByCompany(string $dateFrom, string $dateTo, ?int $companyId = null): Collection
    {
        return Company::query()
            ->where('is_active', true)
            ->withCount([
                'transfers as transfers_count' => fn(Builder $query) => $query
                    ->whereDate('transfer_date', '>=', $dateFrom)
                    ->whereDate('transfer_date', '<=', $dateTo)
                    ->whereNotIn('status', ['cancelled'])
                    ->when($companyId, fn(Builder $inner) => $inner->where('company_id', $companyId)),
            ])
            ->withSum([
                'transfers as transfers_total_amount' => fn(Builder $query) => $query
                    ->whereDate('transfer_date', '>=', $dateFrom)
                    ->whereDate('transfer_date', '<=', $dateTo)
                    ->whereNotIn('status', ['cancelled'])
                    ->when($companyId, fn(Builder $inner) => $inner->where('company_id', $companyId)),
            ], 'amount')
            ->orderByRaw('COALESCE(transfers_total_amount, 0) DESC')
            ->orderBy('name')
            ->get();
    }

    public function debitEntries(string $dateFrom, string $dateTo): Collection
    {
        return $this->debitQuery($dateFrom, $dateTo)
            ->orderBy('granted_date')
            ->orderBy('id')
            ->get();
    }

    public function otherIncomeEntries(string $dateFrom, string $dateTo): Collection
    {
        return $this->otherIncomeQuery($dateFrom, $dateTo)
            ->orderBy('income_date')
            ->orderBy('id')
            ->get();
    }
}
