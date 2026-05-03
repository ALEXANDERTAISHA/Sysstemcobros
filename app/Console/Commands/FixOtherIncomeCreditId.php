<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\OtherIncome;
use App\Models\Credit;

class FixOtherIncomeCreditId extends Command
{
    protected $signature = 'fix:other-income-credit-id';
    protected $description = 'Asocia el credit_id correcto a los registros de OtherIncome de empresas especiales';

    public function handle()
    {
        $specialCompanies = ['TRANSFERENCIA ZELLE', 'GASTOS TIENDA', 'GIRO REENVIADO'];
        $count = 0;
        $incomes = OtherIncome::whereNull('credit_id')->get();
        foreach ($incomes as $income) {
            $credit = Credit::where('client_id', $income->client_id)
                ->whereDate('granted_date', $income->income_date)
                ->where('total_amount', $income->amount)
                ->whereHas('company', function($q) use ($specialCompanies) {
                    foreach ($specialCompanies as $company) {
                        $q->orWhereRaw('UPPER(name) LIKE ?', ['%' . mb_strtoupper($company) . '%']);
                    }
                })
                ->first();
            if ($credit) {
                $income->credit_id = $credit->id;
                $income->save();
                $count++;
            }
        }
        $this->info("Registros actualizados: $count");
    }
}
