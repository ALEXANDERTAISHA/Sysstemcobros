@extends('layouts.app')
@section('title', 'Gastos / Débitos')
@section('page-title', 'Gastos / Débitos')
@section('breadcrumb')<li class="breadcrumb-item active">Gastos / Débitos</li>@endsection

@section('content')
    <div class="row mb-3">
        <div class="col-md-8">
            <form method="GET" class="form-inline">
                <input type="text" name="search" class="form-control mr-2" placeholder="Buscar cliente..."
                    value="{{ $search }}">
                <input type="date" name="date" class="form-control mr-2" value="{{ $date ?? '' }}">
                @if (auth()->user()->isAdmin())
                    <select name="branch_id" class="form-control mr-2">
                        <option value="">Todas las sucursales</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}"
                                {{ (string) $branchId === (string) $branch->id ? 'selected' : '' }}>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </select>
                @endif
                <select name="status" class="form-control mr-2">
                    <option value="all" {{ $status === 'all' ? 'selected' : '' }}>Todos</option>
                    <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Activos</option>
                    <option value="partial" {{ $status === 'partial' ? 'selected' : '' }}>Parcial</option>
                    <option value="paid" {{ $status === 'paid' ? 'selected' : '' }}>Pagados</option>
                </select>
                <button class="btn btn-primary mr-1"><i class="fas fa-search"></i></button>
                <a href="{{ route('expenses.index') }}" class="btn btn-secondary"><i class="fas fa-redo"></i></a>
            </form>
        </div>
        <div class="col-md-4 text-right">
            <a href="{{ route('expenses.create') }}" class="btn btn-warning">
                <i class="fas fa-plus mr-1"></i> Nuevo Débito
            </a>
        </div>
    </div>

    <div class="card card-outline card-warning">
        <div class="card-body p-0">
            <table class="table table-hover table-sm mb-0">
                <thead class="thead-dark">
                    <tr>
                        <th>Fecha</th>
                        @if (auth()->user()->isAdmin())
                            <th>Sucursal</th>
                        @endif
                        <th>Cliente</th>
                        <th>Empresa</th>
                        <th>Concepto</th>
                        <th class="text-right">Total</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($credits as $credit)
                        <tr
                            class="{{ $credit->status === 'paid' ? 'table-success' : ($credit->due_date && $credit->due_date->isPast() && $credit->status !== 'paid' ? 'table-danger' : '') }}">
                            <td>{{ $credit->granted_date->format('d/m/Y') }}</td>
                            @if (auth()->user()->isAdmin())
                                <td>{{ $credit->branch?->name ?? 'Sin sucursal' }}</td>
                            @endif
                            <td>
                                <a href="{{ route('clients.show', $credit->client) }}">
                                    {{ $credit->client->name }}
                                </a>
                            </td>
                            <td>{{ $credit->company->name ?? '—' }}</td>
                            <td>{{ $credit->concept }}</td>
                            <td class="text-right">${{ number_format($credit->total_amount, 2) }}</td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('expenses.show', $credit) }}" class="btn btn-info" title="Ver"><i
                                            class="fas fa-eye"></i></a>
                                    <a href="{{ route('expenses.edit', $credit) }}" class="btn btn-warning"
                                        title="Editar"><i class="fas fa-edit"></i></a>
                                    <form method="POST" action="{{ route('expenses.destroy', $credit) }}" class="d-inline"
                                        onsubmit="return confirm('¿Eliminar este débito?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-danger" title="Eliminar"><i
                                                class="fas fa-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ auth()->user()->isAdmin() ? '7' : '6' }}" class="text-center text-muted py-4">
                                Sin débitos registrados</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($credits->hasPages())
            <div class="card-footer">{{ $credits->withQueryString()->links() }}</div>
        @endif
    </div>
@endsection
