@extends('layouts.app')
@section('title', 'Empresas')
@section('page-title', 'Empresas / Compañías')
@section('breadcrumb')<li class="breadcrumb-item active">Empresas</li>@endsection

@section('content')
    <div class="row">
        <div class="col-lg-8">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-building mr-1"></i> Lista de Empresas</h3>
                    <div class="card-tools">
                        <a href="{{ route('companies.create') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus mr-1"></i> Nueva Empresa
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead class="thead-dark">
                            <tr>
                                <th>Color</th>
                                <th>Código</th>
                                <th>Nombre</th>
                                <th class="text-center">Giros</th>
                                <th class="text-center">Estado</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($companies as $company)
                                <tr>
                                    <td>
                                        <span class="badge px-3 py-2"
                                            style="background-color:{{ $company->color }};">&nbsp;&nbsp;&nbsp;</span>
                                    </td>
                                    <td><strong>{{ $company->code }}</strong></td>
                                    <td>{{ $company->name }}</td>
                                    <td class="text-center">{{ $company->transfers_count }}</td>
                                    <td class="text-center">
                                        <span class="badge badge-{{ $company->is_active ? 'success' : 'secondary' }}">
                                            {{ $company->is_active ? 'Activa' : 'Inactiva' }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('companies.edit', $company) }}" class="btn btn-xs btn-warning">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form method="POST" action="{{ route('companies.destroy', $company) }}"
                                            class="d-inline" onsubmit="return confirm('¿Eliminar empresa?')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-xs btn-danger"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">Sin empresas registradas</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            @if (request()->routeIs('companies.index'))
                <div class="card card-info card-outline">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-info-circle mr-1"></i> Empresas Predeterminadas</h3>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small">Puedes agregar las empresas de giro que manejas. Ejemplos:</p>
                        <ul class="list-unstyled">
                            <li><span class="badge mr-1" style="background:#17a2b8;">&nbsp;</span> V. AMERICA</li>
                            <li><span class="badge mr-1" style="background:#28a745;">&nbsp;</span> LA NACIONAL</li>
                            <li><span class="badge mr-1" style="background:#fd7e14;">&nbsp;</span> RIA</li>
                            <li><span class="badge mr-1" style="background:#6f42c1;">&nbsp;</span> W. UNION</li>
                            <li><span class="badge mr-1" style="background:#e83e8c;">&nbsp;</span> RECARGAS</li>
                            <li><span class="badge mr-1" style="background:#20c997;">&nbsp;</span> CHEQUES</li>
                        </ul>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
