<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\CashBoxInitial;
use App\Support\BranchContext;
use Illuminate\Http\Request;

class CashBoxInitialController extends Controller
{
    public function index(Request $request)
    {
        $today = today()->toDateString();
        $branchId = BranchContext::isPrivileged() ? ($request->integer('branch_id') ?: null) : BranchContext::branchId();

        $initialQuery = CashBoxInitial::whereDate('date', $today);
        $todayTotalQuery = CashBoxInitial::whereDate('date', $today);
        $historyQuery = CashBoxInitial::with('branch');

        if (BranchContext::isPrivileged() && $branchId) {
            $initialQuery->where('branch_id', $branchId);
            $todayTotalQuery->where('branch_id', $branchId);
            $historyQuery->where('branch_id', $branchId);
        } else {
            BranchContext::scope($initialQuery);
            BranchContext::scope($todayTotalQuery);
            BranchContext::scope($historyQuery);
        }

        $initial = $initialQuery->latest('id')->first();
        $todayTotal = (float) $todayTotalQuery->sum('initial_amount');
        $history = $historyQuery
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $branches = Branch::where('is_active', true)->orderBy('name')->get();

        return view('cash-box-initial.index', compact('initial', 'today', 'todayTotal', 'history', 'branches', 'branchId'));
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

        $assignedData = BranchContext::assign($data);
        
        // Usar updateOrCreate para evitar duplicados
        CashBoxInitial::updateOrCreate(
            [
                'date' => $assignedData['date'],
                'branch_id' => $assignedData['branch_id'],
            ],
            $assignedData
        );

        return redirect()->route('cash-box-initial.index')
            ->with('success', 'Dinero inicial registrado correctamente.');
    }

    public function update(Request $request, CashBoxInitial $cashBoxInitial)
    {
        BranchContext::abortIfForbidden($cashBoxInitial->branch_id);

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
        BranchContext::abortIfForbidden($cashBoxInitial->branch_id);

        $cashBoxInitial->delete();

        return redirect()->route('cash-box-initial.index')
            ->with('success', 'Registro de dinero inicial eliminado correctamente.');
    }
}
