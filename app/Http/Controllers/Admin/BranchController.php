<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function index()
    {
        $branches = Branch::orderByDesc('id')->paginate(20);

        return view('admin.branches.index', compact('branches'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'code' => 'required|string|max:30|unique:branches,code',
            'address' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active', true);

        $branch = Branch::create($data);

        return redirect()->route('admin.branches.index')
            ->with('success', 'Sucursal creada correctamente.')
            ->with('created_branch_id', $branch->id);
    }

    public function update(Request $request, Branch $branch)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'code' => 'required|string|max:30|unique:branches,code,' . $branch->id,
            'address' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active');

        $branch->update($data);

        return back()->with('success', 'Sucursal actualizada correctamente.');
    }

    public function destroy(Branch $branch)
    {
        if ($branch->users()->exists()) {
            return back()->withErrors(['branch' => 'No se puede eliminar una sucursal con usuarios asignados.']);
        }

        $branch->delete();

        return back()->with('success', 'Sucursal eliminada correctamente.');
    }
}
