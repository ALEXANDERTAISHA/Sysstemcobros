<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Admin user
        User::firstOrCreate(
            ['email' => 'admin@systemcobros.com'],
            [
                'name'     => 'Administrador',
                'password' => Hash::make('admin123'),
            ]
        );

        // Empresas predeterminadas del sistema de cobros
        $companies = [
            ['name' => 'V. AMERICA',         'code' => 'VA',  'color' => '#17a2b8'],
            ['name' => 'LA NACIONAL',         'code' => 'LN',  'color' => '#28a745'],
            ['name' => 'RIA',                 'code' => 'RIA', 'color' => '#fd7e14'],
            ['name' => 'W. UNION',            'code' => 'WU',  'color' => '#6f42c1'],
            ['name' => 'PAQUETERIA',          'code' => 'PKT', 'color' => '#e83e8c'],
            ['name' => 'PAGO SERVICIOS RIA',  'code' => 'PSR', 'color' => '#20c997'],
            ['name' => 'PRODUCTOS DE TIENDA', 'code' => 'PT',  'color' => '#6c757d'],
            ['name' => 'RECARGAS',            'code' => 'RC',  'color' => '#ffc107'],
            ['name' => 'CHEQUES',             'code' => 'CHQ', 'color' => '#dc3545'],
        ];

        foreach ($companies as $company) {
            Company::firstOrCreate(['code' => $company['code']], $company);
        }
    }
}
