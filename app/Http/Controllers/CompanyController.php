<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CompanyController extends Controller
{
    public function index()
    {
        $companies = Company::withCount('transfers')->orderByBusinessList()->get();
        return view('companies.index', compact('companies'));
    }

    public function create()
    {
        return view('companies.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'      => 'required|string|max:100',
            'code'      => 'required|string|max:20|unique:companies,code',
            'color'     => 'required|string|max:20',
            'logo'      => 'nullable|image|max:2048',
            'is_active' => 'nullable|boolean',
        ]);
        unset($data['logo']);
        $data['is_active'] = $request->boolean('is_active', true);

        if ($request->hasFile('logo')) {
            $data['logo_path'] = $request->file('logo')->store('company-logos', 'public');
        }

        Company::create($data);
        return redirect()->route('companies.index')->with('success', 'Empresa creada correctamente.');
    }

    public function edit(Company $company)
    {
        return view('companies.edit', compact('company'));
    }

    public function update(Request $request, Company $company)
    {
        $data = $request->validate([
            'name'      => 'required|string|max:100',
            'code'      => 'required|string|max:20|unique:companies,code,' . $company->id,
            'color'     => 'required|string|max:20',
            'logo'      => 'nullable|image|max:2048',
            'remove_logo' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);
        unset($data['logo'], $data['remove_logo']);

        if ($request->boolean('remove_logo') && $company->logo_path) {
            Storage::disk('public')->delete($company->logo_path);
            $data['logo_path'] = null;
        }

        if ($request->hasFile('logo')) {
            if ($company->logo_path) {
                Storage::disk('public')->delete($company->logo_path);
            }

            $data['logo_path'] = $request->file('logo')->store('company-logos', 'public');
        }

        $data['is_active'] = $request->boolean('is_active');
        $company->update($data);
        return redirect()->route('companies.index')->with('success', 'Empresa actualizada.');
    }

    public function destroy(Company $company)
    {
        if ($company->transfers()->exists()) {
            return back()->with('error', 'No se puede eliminar una empresa con transferencias registradas.');
        }
        $company->delete();
        return redirect()->route('companies.index')->with('success', 'Empresa eliminada.');
    }
}
