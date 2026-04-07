<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_closings', function (Blueprint $table) {
            $table->id();
            $table->date('closing_date')->unique();
            $table->decimal('total_incomes', 12, 2)->default(0);      // suma de transferencias
            $table->decimal('total_expenses', 12, 2)->default(0);     // (-) gastos/debitos
            $table->decimal('value_total', 12, 2)->default(0);        // total_incomes - total_expenses
            $table->decimal('other_incomes_total', 12, 2)->default(0); // (+) otros ingresos (pagos fiados)
            $table->decimal('sum_total', 12, 2)->default(0);          // value_total + other_incomes_total
            $table->decimal('existing_value', 12, 2)->default(0);     // valor existente (caja inicial)
            $table->decimal('difference', 12, 2)->default(0);         // diferencia
            $table->decimal('final_total', 12, 2)->default(0);        // total final
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_closings');
    }
};
