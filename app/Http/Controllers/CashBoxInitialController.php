<?php

namespace App\Http\Controllers;

use App\Models\CashBoxInitial;
use Illuminate\Http\Request;

class CashBoxInitialController extends Controller
{
    public function index()
    {
        $today = today()->toDateString();
        $initial = CashBoxInitial::whereDate('date', $today)->latest('id')->first();
        $todayTotal = (float) CashBoxInitial::whereDate('date', $today)->sum('initial_amount');
        $history = CashBoxInitial::orderByDesc('date')->orderByDesc('id')->paginate(20);

        return view('cash-box-initial.index', compact('initial', 'today', 'todayTotal', 'history'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'date' => 'required|date',
            'initial_amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        // Solo permitir registrar para hoy
        if ($data['date'] !== today()->toDateString()) {
            return back()->withErrors(['date' => 'Solo puedes registrar dinero inicial para hoy (' . today()->format('d/m/Y') . ').']);
        }

        CashBoxInitial::create($data);

        return redirect()->route('cash-box-initial.index')
            ->with('success', 'Dinero inicial registrado correctamente.');
    }

    public function update(Request $request, CashBoxInitial $cashBoxInitial)
    {
        $data = $request->validate([
            'initial_amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $cashBoxInitial->update([
            'initial_amount' => $data['initial_amount'],
            'notes' => $data['notes'] ?? null,
        ]);

        return redirect()->route('cash-box-initial.index')
            ->with('success', 'Monto de dinero inicial actualizado correctamente.');
    }

    public function destroy(CashBoxInitial $cashBoxInitial)
    {
        $cashBoxInitial->delete();

        return redirect()->route('cash-box-initial.index')
            ->with('success', 'Registro de dinero inicial eliminado correctamente.');
    }
}
