@extends('layouts.app')
@section('title', 'Clientes')
@section('page-title', 'Clientes')
@section('breadcrumb')<li class="breadcrumb-item active">Clientes</li>@endsection

@section('content')
    <div class="row mb-3">
        <div class="col-md-8">
            <form method="GET" class="form-inline">
                <input type="text" name="search" class="form-control mr-2" placeholder="Buscar por nombre o teléfono..."
                    value="{{ $search }}">
                <button class="btn btn-primary mr-1"><i class="fas fa-search"></i></button>
                <a href="{{ route('clients.index') }}" class="btn btn-secondary"><i class="fas fa-redo"></i></a>
            </form>
        </div>
        <div class="col-md-4 text-right">
            <a href="{{ route('clients.create') }}" class="btn btn-success">
                <i class="fas fa-user-plus mr-1"></i> Nuevo Cliente
            </a>
        </div>
    </div>

    <div class="card card-outline card-success">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-users mr-1"></i> Lista de Clientes</h3>
            <div class="card-tools"><span class="badge badge-secondary">{{ $clients->total() }} registros</span></div>
        </div>
        <div class="card-body p-0">
            <table class="table table-hover table-sm mb-0">
                <thead class="thead-dark">
                    <tr>
                        <th>Nombre</th>
                        <th>Teléfono</th>
                        <th>Correo</th>
                        <th>WhatsApp</th>
                        <th>Estado</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($clients as $client)
                        <tr>
                            <td>
                                <a href="{{ route('clients.show', $client) }}" class="font-weight-bold">
                                    {{ $client->name }}
                                </a>
                            </td>
                            <td>{{ $client->phone ?? '-' }}</td>
                            <td>
                                @if ($client->email)
                                    <a href="mailto:{{ $client->email }}">{{ $client->email }}</a>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if ($client->whatsapp)
                                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $client->whatsapp) }}"
                                        target="_blank" class="text-success">
                                        <i class="fab fa-whatsapp mr-1"></i>{{ $client->whatsapp }}
                                    </a>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge badge-{{ $client->is_active ? 'success' : 'secondary' }}">
                                    {{ $client->is_active ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('clients.show', $client) }}" class="btn btn-info" title="Ver"><i
                                            class="fas fa-eye"></i></a>
                                    <a href="{{ route('clients.edit', $client) }}" class="btn btn-warning"
                                        title="Editar"><i class="fas fa-edit"></i></a>
                                    <form method="POST" action="{{ route('clients.destroy', $client) }}" class="d-inline"
                                        onsubmit="return confirm('¿Eliminar este cliente?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-danger" title="Eliminar"><i
                                                class="fas fa-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4"><i
                                    class="fas fa-users fa-2x d-block mb-2"></i> Sin clientes</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($clients->hasPages())
            <div class="card-footer">{{ $clients->withQueryString()->links() }}</div>
        @endif
    </div>
@endsection
