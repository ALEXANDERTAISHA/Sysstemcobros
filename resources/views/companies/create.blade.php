@extends('layouts.app')
@section('title', 'Nueva Empresa')
@section('page-title', 'Nueva Empresa')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('companies.index') }}">Empresas</a></li>
    <li class="breadcrumb-item active">Nueva</li>
@endsection

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-building mr-1"></i> Registrar Empresa</h3>
                </div>
                <form method="POST" action="{{ route('companies.store') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="card-body">
                        <div class="form-group">
                            <label>Nombre de la Empresa *</label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                value="{{ old('name') }}" placeholder="Ej: V. AMERICA" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label>Código *</label>
                            <input type="text" name="code" class="form-control @error('code') is-invalid @enderror"
                                value="{{ old('code') }}" placeholder="Ej: VA" maxlength="20" required>
                            @error('code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label>Color de identificación</label>
                            <div class="input-group">
                                <input type="color" name="color" class="form-control"
                                    value="{{ old('color', '#007bff') }}" style="height:38px;padding:2px;">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Logo de la empresa</label>
                            <div class="custom-file">
                                <input type="file" name="logo"
                                    class="custom-file-input @error('logo') is-invalid @enderror" id="logo"
                                    accept="image/*">
                                <label class="custom-file-label" for="logo">Seleccionar imagen...</label>
                                @error('logo')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <small class="text-muted">PNG o JPG. Tamaño máximo: 2MB.</small>
                        </div>
                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="is_active" name="is_active"
                                    value="1" {{ old('is_active', 1) ? 'checked' : '' }}>
                                <label class="custom-control-label" for="is_active">Empresa activa</label>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Guardar</button>
                        <a href="{{ route('companies.index') }}" class="btn btn-secondary ml-2">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const logoInput = document.getElementById('logo');
            if (logoInput) {
                logoInput.addEventListener('change', function() {
                    const label = this.nextElementSibling;
                    if (this.files.length > 0) {
                        label.textContent = this.files[0].name;
                    }
                });
            }
        });
    </script>
@endpush
