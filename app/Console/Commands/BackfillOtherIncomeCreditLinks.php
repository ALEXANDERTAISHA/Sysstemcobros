<?php

namespace App\Console\Commands;

use App\Models\Credit;
use App\Models\OtherIncome;
use Illuminate\Console\Command;

class BackfillOtherIncomeCreditLinks extends Command
{
    protected $signature = 'other-incomes:backfill-credit-links {--dry-run : Preview changes without saving}';

    protected $description = 'Link historical other incomes to credits using client and concept matching';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $rows = OtherIncome::query()
            ->whereNull('credit_id')
            ->whereNotNull('client_id')
            ->where(function ($query) {
                $query->where('description', 'like', 'Cobro de débito:%')
                    ->orWhere('description', 'like', 'Cobro de debito:%');
            })
            ->orderBy('income_date')
            ->orderBy('id')
            ->get();

        if ($rows->isEmpty()) {
            $this->info('No historical incomes require backfill.');
            return self::SUCCESS;
        }

        $linked = 0;
        $skipped = 0;
        $notFound = 0;

        foreach ($rows as $income) {
            $concept = $this->extractConcept((string) $income->description);

            if ($concept === '') {
                $skipped++;
                continue;
            }

            $creditBase = Credit::query()
                ->where('client_id', $income->client_id)
                ->where('concept', $concept);

            $credit = (clone $creditBase)
                ->whereDate('granted_date', '<=', $income->income_date)
                ->orderByDesc('granted_date')
                ->orderByDesc('id')
                ->first();

            if (!$credit) {
                $credit = (clone $creditBase)
                    ->orderByDesc('id')
                    ->first();
            }

            if (!$credit) {
                $notFound++;
                continue;
            }

            if (!$dryRun) {
                $income->update(['credit_id' => $credit->id]);
            }

            $linked++;
        }

        $mode = $dryRun ? 'DRY RUN' : 'APPLIED';
        $this->info("Backfill {$mode}: linked={$linked}, not_found={$notFound}, skipped={$skipped}, total={$rows->count()}");

        return self::SUCCESS;
    }

    private function extractConcept(string $description): string
    {
        $prefixes = ['Cobro de débito:', 'Cobro de debito:'];

        foreach ($prefixes as $prefix) {
            if (str_starts_with($description, $prefix)) {
                return trim(substr($description, strlen($prefix)));
            }
        }

        return '';
    }
}
