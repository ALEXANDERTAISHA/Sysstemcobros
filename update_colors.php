<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Company;

// Actualizar colores de empresas de débito
Company::whereIn('code', ['VAC', 'VATD', 'LNC', 'LNTD'])
    ->update(['color' => '#ff6b6b']);

echo "✓ Colores actualizados exitosamente\n";
echo "Empresas de cheques y tarjeta débito ahora con color rojo (#ff6b6b)\n";
