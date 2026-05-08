<?php

namespace App\Http\Middleware;

use App\Models\CashBoxInitial;
use App\Support\BranchContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCashBoxInitialIsRegistered
{
    public function handle(Request $request, Closure $next): Response
    {
        $initialCashQuery = CashBoxInitial::whereDate('date', today()->toDateString());
        BranchContext::scope($initialCashQuery);
        $hasInitialCash = $initialCashQuery->exists();

        if ($hasInitialCash) {
            return $next($request);
        }

        return redirect()
            ->route('cash-box-initial.index')
            ->with('warning', 'Primero debes registrar el dinero inicial de caja chica de hoy para habilitar las operaciones.');
    }
}
