<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Client;
use App\Models\Credit;
use App\Support\BranchContext;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search');
        $branchId = BranchContext::isPrivileged() ? ($request->integer('branch_id') ?: null) : BranchContext::branchId();
        $clientsQuery = Client::with('branch')
            ->when($search, fn($q) => $q->where(function ($searchQuery) use ($search) {
                $searchQuery->where('clients.name', 'like', "%$search%")
                    ->orWhere('clients.phone', 'like', "%$search%")
                    ->orWhere('clients.email', 'like', "%$search%");
            }));

        if (BranchContext::isPrivileged() && $branchId) {
            $clientsQuery->where('branch_id', $branchId);
        } else {
            BranchContext::scope($clientsQuery);
        }

        $clients = $clientsQuery
            ->leftJoin('branches as client_branches', 'client_branches.id', '=', 'clients.branch_id')
            ->select('clients.*')
            ->orderByRaw('clients.branch_id IS NULL')
            ->orderBy('client_branches.name')
            ->orderBy('clients.name')
            ->paginate(20);

        $branches = Branch::where('is_active', true)->orderBy('name')->get();

        return view('clients.index', compact('clients', 'search', 'branches', 'branchId'));
    }

    public function create()
    {
        $branches = Branch::where('is_active', true)->orderBy('name')->get();

        return view('clients.create', compact('branches'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'      => 'required|string|max:150',
            'email'     => 'nullable|email|max:150',
            'phone'     => 'nullable|string|max:30',
            'whatsapp'  => 'nullable|string|max:30',
            'address'   => 'nullable|string|max:250',
            'notes'     => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'branch_id'  => 'nullable|exists:branches,id',
        ]);
        $data['is_active'] = $request->boolean('is_active', true);
        $data = BranchContext::assign($data);
        $data['branch_id'] ??= Branch::where('is_active', true)->orderBy('id')->value('id');
        $client = Client::create($data);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Cliente registrado correctamente.',
                'client' => [
                    'id' => $client->id,
                    'name' => $client->name,
                    'phone' => $client->phone,
                ],
            ]);
        }

        return redirect()->route('clients.index')->with('success', 'Cliente registrado correctamente.');
    }

    public function show(Client $client)
    {
        BranchContext::abortIfForbidden($client->branch_id);

        $credits = $client->credits()->with('payments')->latest()->get();
        $totalDebt = $credits->whereIn('status', ['active', 'partial'])
            ->sum(fn($c) => $c->total_amount - $c->paid_amount);
        return view('clients.show', compact('client', 'credits', 'totalDebt'));
    }

    public function edit(Client $client)
    {
        BranchContext::abortIfForbidden($client->branch_id);

        $branches = Branch::where('is_active', true)->orderBy('name')->get();

        return view('clients.edit', compact('client', 'branches'));
    }

    public function update(Request $request, Client $client)
    {
        BranchContext::abortIfForbidden($client->branch_id);

        $data = $request->validate([
            'name'      => 'required|string|max:150',
            'email'     => 'nullable|email|max:150',
            'phone'     => 'nullable|string|max:30',
            'whatsapp'  => 'nullable|string|max:30',
            'address'   => 'nullable|string|max:250',
            'notes'     => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'branch_id'  => 'nullable|exists:branches,id',
        ]);
        $data['is_active'] = $request->boolean('is_active');
        $data = BranchContext::assign($data);
        $client->update($data);
        return redirect()->route('clients.index')->with('success', 'Cliente actualizado.');
    }

    public function destroy(Client $client)
    {
        BranchContext::abortIfForbidden($client->branch_id);

        if ($client->credits()->exists()) {
            return back()->with('error', 'No se puede eliminar un cliente con fiados registrados.');
        }
        $client->delete();
        return redirect()->route('clients.index')->with('success', 'Cliente eliminado.');
    }
}
