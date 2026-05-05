<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyClosing extends Model
{
    protected $fillable = [
        'branch_id',
        'closing_date',
        'total_incomes',
        'total_expenses',
        'value_total',
        'other_incomes_total',
        'sum_total',
        'existing_value',
        'difference',
        'final_total',
        'notes',
    ];

    protected $casts = [
        'closing_date'        => 'date',
        'total_incomes'       => 'decimal:2',
        'total_expenses'      => 'decimal:2',
        'value_total'         => 'decimal:2',
        'other_incomes_total' => 'decimal:2',
        'sum_total'           => 'decimal:2',
        'existing_value'      => 'decimal:2',
        'difference'          => 'decimal:2',
        'final_total'         => 'decimal:2',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Suma un gasto al cierre de caja del día y sucursal dados.
     */
    public static function addExpense($branchId, $amount, $description = null)
    {
        $date = now()->toDateString();
        $closing = self::firstOrCreate([
            'branch_id' => $branchId,
            'closing_date' => $date,
        ]);
        $closing->total_expenses += $amount;
        $closing->save();
        // Aquí podrías guardar el detalle del gasto si tienes tabla de detalles
        // Ejemplo: DailyClosingExpense::create([...])
        return $closing;
    }
}
