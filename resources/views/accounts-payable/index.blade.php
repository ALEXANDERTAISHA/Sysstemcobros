@extends('layouts.app')
@section('title', 'Cuentas por Pagar')
@section('page-title', 'Cuentas por Pagar')
@section('breadcrumb')<li class="breadcrumb-item active">Cuentas por Pagar</li>@endsection

@section('content')
    <div class="row mb-3">
        <div class="col-md-8">
            <form method="GET" class="form-inline">
                <input type="text" name="search" class="form-control mr-2" placeholder="Buscar cliente, empresa o concepto..."
                    value="{{ $search }}">
                <input type="date" name="date" class="form-control mr-2" value="{{ $date ?? '' }}">
                @if(auth()->user()->isSuperAdmin())
                    <select name="branch_id" class="form-control mr-2">
                        <option value="">Todas las sucursales</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" {{ (string) $branchId === (string) $branch->id ? 'selected' : '' }}>
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
                <a href="{{ route('accounts-payable.index') }}" class="btn btn-secondary"><i class="fas fa-redo"></i></a>
            </form>
        </div>
        <div class="col-md-4 text-right">
            <a href="{{ route('accounts-payable.create') }}" class="btn btn-warning">
                <i class="fas fa-plus mr-1"></i> Nueva Cuenta
            </a>
        </div>
    </div>

    <div class="card card-outline card-warning">
        <div class="card-body p-0">
            <table class="table table-hover table-sm mb-0">
                <thead class="thead-dark">
                    <tr>
                        <th>Fecha</th>
                        @if(auth()->user()->isSuperAdmin())
                            <th>Sucursal</th>
                        @endif
                        <th>Cliente</th>
                        <th>Empresa</th>
                        <th>Concepto</th>
                        <th class="text-right">Total</th>
                        <th class="text-right">Saldo</th>
                        <th class="text-center">Estado</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($accounts as $account)
                        <tr class="{{ $account->status === 'paid' ? 'table-success' : ($account->due_date && $account->due_date->isPast() && $account->status !== 'paid' ? 'table-danger' : '') }}">
                            <td>{{ $account->issued_date->format('d/m/Y') }}</td>
                            @if(auth()->user()->isSuperAdmin())
                                <td>{{ $account->branch?->name ?? 'Sin sucursal' }}</td>
                            @endif
                            <td><a href="{{ route('clients.show', $account->client) }}">{{ $account->client->name }}</a></td>
                            <td>{{ $account->company?->name ?? '-' }}</td>
                            <td>{{ $account->concept }}</td>
                            <td class="text-right">${{ number_format($account->total_amount, 2) }}</td>
                            <td class="text-right font-weight-bold {{ $account->balance > 0 ? 'text-danger' : 'text-success' }}">
                                ${{ number_format($account->balance, 2) }}
                            </td>
                            <td class="text-center">
                                <span class="badge badge-{{ $account->status === 'paid' ? 'success' : ($account->status === 'partial' ? 'info' : 'warning') }}">
                                    {{ $account->status === 'paid' ? 'Pagado' : ($account->status === 'partial' ? 'Parcial' : 'Activo') }}
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('accounts-payable.show', $account) }}" class="btn btn-info" title="Ver"><i class="fas fa-eye"></i></a>
                                    <form method="POST" action="{{ route('accounts-payable.destroy', $account) }}" class="d-inline"
                                        onsubmit="return confirm('¿Eliminar esta cuenta por pagar?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-danger" title="Eliminar"><i class="fas fa-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ auth()->user()->isSuperAdmin() ? '9' : '8' }}" class="text-center text-muted py-4">
                                Sin cuentas por pagar registradas
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($accounts->hasPages())
            <div class="card-footer bg-white border-top-0">
                <nav aria-label="Paginacion de cuentas por pagar" class="d-flex justify-content-center">
                    {{ $accounts->withQueryString()->links('vendor.pagination.bootstrap-4') }}
                </nav>
            </div>
        @endif
    </div>
@endsection
