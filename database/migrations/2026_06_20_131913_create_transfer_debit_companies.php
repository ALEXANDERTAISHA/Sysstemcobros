<?php

use App\Models\Company;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Crear empresas de débito/cheques si no existen
        $companies = [
            [
                'name' => 'VIAS AMERICAS CHEQUES',
                'code' => 'VAC',
                'color' => '#dc3545',
                'company_type' => Company::TYPE_GENERAL,
            ],
            [
                'name' => 'VIAS AMERICAS TRANSFERENCIAS TARJETA DE DEBITO',
                'code' => 'VATD',
                'color' => '#dc3545',
                'company_type' => Company::TYPE_GENERAL,
            ],
            [
                'name' => 'LA NACIONAL CHEQUES',
                'code' => 'LNC',
                'color' => '#dc3545',
                'company_type' => Company::TYPE_GENERAL,
            ],
            [
                'name' => 'LA NACIONAL TARJETA DE DEBITO',
                'code' => 'LNTD',
                'color' => '#dc3545',
                'company_type' => Company::TYPE_GENERAL,
            ],
        ];

        foreach ($companies as $company) {
            Company::firstOrCreate(
                ['code' => $company['code']],
                $company
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Company::whereIn('code', ['VAC', 'VATD', 'LNC', 'LNTD'])->delete();
    }
};

