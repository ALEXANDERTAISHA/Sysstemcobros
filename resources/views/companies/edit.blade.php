@extends('layouts.app')
@section('title', $pageTitle ?? 'Editar Empresa')
@section('page-title', $pageTitle ?? 'Editar Empresa')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route(($companyRoutePrefix ?? 'companies') . '.index') }}">{{ $pageTitle ?? 'Empresas' }}</a></li>
    <li class="breadcrumb-item active">Editar</li>
@endsection

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card card-warning card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-edit mr-1"></i> Editar: {{ $company->name }}</h3>
                </div>
                <form method="POST" action="{{ route(($companyRoutePrefix ?? 'companies') . '.update', $company) }}" enctype="multipart/form-data">
                    @csrf @method('PUT')
                    <div class="card-body">
                        <div class="form-group">
                            <label>Nombre *</label>
                            <input type="text" name="name" class="form-control" value="{{ $company->name }}" required>
                        </div>
                        <div class="form-group">
                            <label>Código *</label>
                            <input type="text" name="code" class="form-control" value="{{ $company->code }}"
                                maxlength="20" required>
                        </div>
                        <div class="form-group">
                            <label>Color</label>
                            <input type="color" name="color" class="form-control" value="{{ $company->color }}"
                                style="height:38px;padding:2px;">
                        </div>
                        <div class="form-group">
                            <label>Logo de la empresa</label>
                            <div class="d-flex align-items-center mb-2">
                                <img src="{{ $company->logo_url }}" alt="Logo" class="img-circle elevation-2 mr-2"
                                    style="width:48px;height:48px;object-fit:cover;">
                                <small class="text-muted">Actualiza el logo para esta empresa.</small>
                            </div>
                            <div class="custom-file">
                                <input type="file" name="logo"
                                    class="custom-file-input @error('logo') is-invalid @enderror" id="logo"
                                    accept="image/*">
                                <label class="custom-file-label" for="logo">Seleccionar imagen...</label>
                                @error('logo')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            @if ($company->logo_path)
                                <div class="custom-control custom-checkbox mt-2">
                                    <input type="checkbox" class="custom-control-input" id="remove_logo" name="remove_logo"
                                        value="1">
                                    <label class="custom-control-label" for="remove_logo">Eliminar logo actual</label>
                                </div>
                            @endif
                        </div>
                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="is_active" name="is_active"
                                    value="1" {{ $company->is_active ? 'checked' : '' }}>
                                <label class="custom-control-label" for="is_active">Empresa activa</label>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-warning"><i class="fas fa-save mr-1"></i> Actualizar</button>
                        <a href="{{ route(($companyRoutePrefix ?? 'companies') . '.index') }}" class="btn btn-secondary ml-2">Cancelar</a>
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
