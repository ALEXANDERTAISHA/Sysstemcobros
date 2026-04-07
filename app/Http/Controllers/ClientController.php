<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Credit;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search');
        $clients = Client::when($search, fn($q) => $q->where('name', 'like', "%$search%")
            ->orWhere('phone', 'like', "%$search%")
            ->orWhere('email', 'like', "%$search%"))
            ->orderBy('name')
            ->paginate(20);
        return view('clients.index', compact('clients', 'search'));
    }

    public function create()
    {
        return view('clients.create');
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
        ]);
        $data['is_active'] = $request->boolean('is_active', true);
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
        $credits = $client->credits()->with('payments')->latest()->get();
        $totalDebt = $credits->whereIn('status', ['active', 'partial'])
            ->sum(fn($c) => $c->total_amount - $c->paid_amount);
        return view('clients.show', compact('client', 'credits', 'totalDebt'));
    }

    public function edit(Client $client)
    {
        return view('clients.edit', compact('client'));
    }

    public function update(Request $request, Client $client)
    {
        $data = $request->validate([
            'name'      => 'required|string|max:150',
            'email'     => 'nullable|email|max:150',
            'phone'     => 'nullable|string|max:30',
            'whatsapp'  => 'nullable|string|max:30',
            'address'   => 'nullable|string|max:250',
            'notes'     => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);
        $data['is_active'] = $request->boolean('is_active');
        $client->update($data);
        return redirect()->route('clients.index')->with('success', 'Cliente actualizado.');
    }

    public function destroy(Client $client)
    {
        if ($client->credits()->exists()) {
            return back()->with('error', 'No se puede eliminar un cliente con fiados registrados.');
        }
        $client->delete();
        return redirect()->route('clients.index')->with('success', 'Cliente eliminado.');
    }
}
