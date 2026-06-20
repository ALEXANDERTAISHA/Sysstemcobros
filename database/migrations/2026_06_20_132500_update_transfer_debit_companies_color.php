<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Actualizar color de empresas de débito/cheques a naranja/oro para diferenciar
        DB::table('companies')
            ->whereIn('code', ['VAC', 'VATD', 'LNC', 'LNTD'])
            ->update(['color' => '#FF9500']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('companies')
            ->whereIn('code', ['VAC', 'VATD', 'LNC', 'LNTD'])
            ->update(['color' => '#dc3545']);
    }
};
