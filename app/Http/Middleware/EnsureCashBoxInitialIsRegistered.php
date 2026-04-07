<?php

namespace App\Http\Middleware;

use App\Models\CashBoxInitial;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCashBoxInitialIsRegistered
{
    public function handle(Request $request, Closure $next): Response
    {
        $hasInitialCash = CashBoxInitial::whereDate('date', today()->toDateString())->exists();

        if ($hasInitialCash) {
            return $next($request);
        }

        return redirect()
            ->route('cash-box-initial.index')
            ->with('warning', 'Primero debes registrar el dinero inicial de caja chica de hoy para habilitar las operaciones.');
    }
}