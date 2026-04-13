<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CompanyController extends Controller
{
    public function index()
    {
        $companies = Company::withCount('transfers')
            ->ofType(Company::TYPE_GENERAL)
            ->orderByBusinessList()
            ->get();

        $pageTitle = 'Empresas / Compañías';
        $companyRoutePrefix = 'companies';

        return view('companies.index', compact('companies', 'pageTitle', 'companyRoutePrefix'));
    }

    public function gastosDebitos()
    {
        $companies = Company::withCount('transfers')
            ->ofType(Company::TYPE_EXPENSE_DEBIT)
            ->orderByBusinessList()
            ->get();

        $pageTitle = 'Empresas Gastos Débitos';
        $companyRoutePrefix = 'companies.gastos-debitos';

        return view('companies.index', compact('companies', 'pageTitle', 'companyRoutePrefix'));
    }

    public function create()
    {
        $pageTitle = 'Nueva Empresa';
        $companyRoutePrefix = 'companies';

        return view('companies.create', compact('pageTitle', 'companyRoutePrefix'));
    }

    public function createGastosDebitos()
    {
        $pageTitle = 'Nueva Empresa Gastos Débitos';
        $companyRoutePrefix = 'companies.gastos-debitos';
        $companyType = Company::TYPE_EXPENSE_DEBIT;

        return view('companies.create', compact('pageTitle', 'companyRoutePrefix', 'companyType'));
    }

    public function store(Request $request)
    {
        return $this->storeCompany($request, Company::TYPE_GENERAL, 'companies.index', 'Empresa creada correctamente.');
    }

    public function storeGastosDebitos(Request $request)
    {
        return $this->storeCompany($request, Company::TYPE_EXPENSE_DEBIT, 'companies.gastos-debitos.index', 'Empresa Gastos Débitos creada correctamente.');
    }

    protected function storeCompany(Request $request, string $type, string $redirectRoute, string $successMessage)
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
        $data['company_type'] = $type;

        if ($request->hasFile('logo')) {
            $data['logo_path'] = $request->file('logo')->store('company-logos', 'public');
        }

        Company::create($data);

        return redirect()->route($redirectRoute)->with('success', $successMessage);
    }

    public function edit(Company $company)
    {
        $pageTitle = 'Editar Empresa';
        $companyRoutePrefix = 'companies';

        return view('companies.edit', compact('company', 'pageTitle', 'companyRoutePrefix'));
    }

    public function editGastosDebitos(Company $company)
    {
        abort_if($company->company_type !== Company::TYPE_EXPENSE_DEBIT, 404);

        $pageTitle = 'Editar Empresa Gastos Débitos';
        $companyRoutePrefix = 'companies.gastos-debitos';

        return view('companies.edit', compact('company', 'pageTitle', 'companyRoutePrefix'));
    }

    public function update(Request $request, Company $company)
    {
        return $this->updateCompany($request, $company, 'companies.index', 'Empresa actualizada.');
    }

    public function updateGastosDebitos(Request $request, Company $company)
    {
        abort_if($company->company_type !== Company::TYPE_EXPENSE_DEBIT, 404);

        return $this->updateCompany($request, $company, 'companies.gastos-debitos.index', 'Empresa Gastos Débitos actualizada.');
    }

    protected function updateCompany(Request $request, Company $company, string $redirectRoute, string $successMessage)
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

        return redirect()->route($redirectRoute)->with('success', $successMessage);
    }

    public function destroy(Company $company)
    {
        return $this->destroyCompany($company, 'companies.index', 'Empresa eliminada.');
    }

    public function destroyGastosDebitos(Company $company)
    {
        abort_if($company->company_type !== Company::TYPE_EXPENSE_DEBIT, 404);

        return $this->destroyCompany($company, 'companies.gastos-debitos.index', 'Empresa Gastos Débitos eliminada.');
    }

    protected function destroyCompany(Company $company, string $redirectRoute, string $successMessage)
    {
        if ($company->transfers()->exists()) {
            return back()->with('error', 'No se puede eliminar una empresa con transferencias registradas.');
        }

        $company->delete();

        return redirect()->route($redirectRoute)->with('success', $successMessage);
    }
}
