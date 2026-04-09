@extends('layouts.app')
@section('title', 'Sucursales')
@section('page-title', 'Sucursales')
@section('breadcrumb')
    <li class="breadcrumb-item active">Sucursales</li>
@endsection

@section('content')
    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>No se pudo guardar la sucursal:</strong>
            <ul class="mb-0 mt-2 pl-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-5">
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-code-branch mr-1"></i>Nueva Sucursal</h3>
                </div>
                <form method="POST" action="{{ route('admin.branches.store') }}">
                    @csrf
                    <div class="card-body">
                        <div class="form-group">
                            <label>Nombre *</label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                value="{{ old('name') }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label>Código *</label>
                            <input type="text" name="code" class="form-control @error('code') is-invalid @enderror"
                                value="{{ old('code') }}" required>
                            @error('code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label>Dirección</label>
                            <input type="text" name="address" class="form-control @error('address') is-invalid @enderror"
                                value="{{ old('address') }}">
                            @error('address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="custom-control custom-switch">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" class="custom-control-input" id="branch_active" name="is_active"
                                value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                            <label class="custom-control-label" for="branch_active">Activa</label>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button class="btn btn-primary"><i class="fas fa-save mr-1"></i>Guardar</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card card-outline card-info">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-list mr-1"></i>Listado de Sucursales</h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="thead-dark">
                                <tr>
                                    <th>Nombre</th>
                                    <th>Código</th>
                                    <th>Dirección</th>
                                    <th>Estado</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($branches as $branch)
                                    <tr class="{{ session('created_branch_id') === $branch->id ? 'table-success' : '' }}">
                                        <form method="POST" action="{{ route('admin.branches.update', $branch) }}">
                                            @csrf
                                            @method('PUT')
                                            <td><input class="form-control form-control-sm" name="name"
                                                    value="{{ $branch->name }}" required></td>
                                            <td><input class="form-control form-control-sm" name="code"
                                                    value="{{ $branch->code }}" required></td>
                                            <td><input class="form-control form-control-sm" name="address"
                                                    value="{{ $branch->address }}"></td>
                                            <td>
                                                <select name="is_active" class="form-control form-control-sm">
                                                    <option value="1" {{ $branch->is_active ? 'selected' : '' }}>
                                                        Activa</option>
                                                    <option value="0" {{ !$branch->is_active ? 'selected' : '' }}>
                                                        Inactiva</option>
                                                </select>
                                            </td>
                                            <td class="text-center">
                                                <button class="btn btn-xs btn-warning" title="Actualizar"><i
                                                        class="fas fa-save"></i></button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.branches.destroy', $branch) }}"
                                            class="d-inline" onsubmit="return confirm('¿Eliminar sucursal?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-xs btn-danger" title="Eliminar"><i
                                                    class="fas fa-trash"></i></button>
                                        </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-3">Sin sucursales registradas
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if ($branches->hasPages())
                    <div class="card-footer">{{ $branches->links() }}</div>
                @endif
            </div>
        </div>
    </div>
@endsection
