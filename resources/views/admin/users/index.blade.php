@extends('layouts.app')
@section('title', 'Usuarios')
@section('page-title', 'Usuarios y Roles')
@section('breadcrumb')
    <li class="breadcrumb-item active">Usuarios</li>
@endsection

@section('content')
    @if($errors->any())
        <div class="alert alert-danger">
            <strong>No se pudo guardar el usuario:</strong>
            <ul class="mb-0 mt-2 pl-3">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-5">
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-user-plus mr-1"></i>Nuevo Usuario</h3>
                </div>
                <form method="POST" action="{{ route('admin.users.store') }}">
                    @csrf
                    <div class="card-body">
                        <div class="form-group">
                            <label>Nombre *</label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label>Email *</label>
                            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label>Clave *</label>
                            <div class="input-group">
                                <input type="password"
                                    name="password"
                                    id="new_user_password"
                                    autocomplete="new-password"
                                    class="form-control @error('password') is-invalid @enderror"
                                    required>
                                <div class="input-group-append">
                                    <button type="button"
                                        class="btn btn-outline-secondary js-toggle-password"
                                        data-target="new_user_password"
                                        title="Mostrar/Ocultar clave">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label>Confirmar clave *</label>
                            <div class="input-group">
                                <input type="password"
                                    name="password_confirmation"
                                    id="new_user_password_confirmation"
                                    autocomplete="new-password"
                                    class="form-control @error('password_confirmation') is-invalid @enderror"
                                    required>
                                <div class="input-group-append">
                                    <button type="button"
                                        class="btn btn-outline-secondary js-toggle-password"
                                        data-target="new_user_password_confirmation"
                                        title="Mostrar/Ocultar clave">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            @error('password_confirmation')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label>Rol *</label>
                            <select name="role" class="form-control @error('role') is-invalid @enderror" required>
                                <option value="operator" {{ old('role', 'operator') === 'operator' ? 'selected' : '' }}>Operador</option>
                                <option value="viewer" {{ old('role') === 'viewer' ? 'selected' : '' }}>Consulta</option>
                                <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>Administrador</option>
                                <option value="super_admin" {{ old('role') === 'super_admin' ? 'selected' : '' }}>Super Administrador</option>
                            </select>
                            @error('role')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label>Sucursal</label>
                            <select name="branch_id" class="form-control @error('branch_id') is-invalid @enderror">
                                <option value="">Sin asignar</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}" {{ (string) old('branch_id') === (string) $branch->id ? 'selected' : '' }}>
                                        {{ $branch->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('branch_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="custom-control custom-switch">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" class="custom-control-input" id="user_active" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                            <label class="custom-control-label" for="user_active">Activo</label>
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
                    <h3 class="card-title"><i class="fas fa-users-cog mr-1"></i>Gestión de Usuarios</h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="thead-dark">
                                <tr>
                                    <th>Nombre</th>
                                    <th>Email</th>
                                    <th>Rol</th>
                                    <th>Sucursal</th>
                                    <th>Estado</th>
                                    <th class="text-center">Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($users as $user)
                                    <tr>
                                        <form method="POST" action="{{ route('admin.users.update', $user) }}" id="user-update-form-{{ $user->id }}">
                                            @csrf
                                            @method('PUT')
                                        </form>
                                            <td><input class="form-control form-control-sm" name="name" value="{{ $user->name }}" form="user-update-form-{{ $user->id }}" required></td>
                                            <td><input class="form-control form-control-sm" name="email" type="email" value="{{ $user->email }}" form="user-update-form-{{ $user->id }}" required></td>
                                            <td>
                                                <select name="role" class="form-control form-control-sm" form="user-update-form-{{ $user->id }}" required>
                                                    <option value="operator" {{ $user->role === 'operator' ? 'selected' : '' }}>Operador</option>
                                                    <option value="viewer" {{ $user->role === 'viewer' ? 'selected' : '' }}>Consulta</option>
                                                    <option value="admin" {{ $user->role === 'admin' ? 'selected' : '' }}>Administrador</option>
                                                    <option value="super_admin" {{ $user->role === 'super_admin' ? 'selected' : '' }}>Super Administrador</option>
                                                </select>
                                            </td>
                                            <td>
                                                <select name="branch_id" class="form-control form-control-sm" form="user-update-form-{{ $user->id }}">
                                                    <option value="">Sin asignar</option>
                                                    @foreach($branches as $branch)
                                                        <option value="{{ $branch->id }}" {{ (string) $user->branch_id === (string) $branch->id ? 'selected' : '' }}>
                                                            {{ $branch->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td>
                                                <select name="is_active" class="form-control form-control-sm" form="user-update-form-{{ $user->id }}">
                                                    <option value="1" {{ $user->is_active ? 'selected' : '' }}>Activo</option>
                                                    <option value="0" {{ !$user->is_active ? 'selected' : '' }}>Inactivo</option>
                                                </select>
                                            </td>
                                            <td class="text-center">
                                                <div class="input-group input-group-sm mb-1">
                                                    <input type="password"
                                                        name="password"
                                                        id="password_update_{{ $user->id }}"
                                                        placeholder="Nueva clave"
                                                        autocomplete="new-password"
                                                        class="form-control form-control-sm"
                                                        form="user-update-form-{{ $user->id }}">
                                                    <div class="input-group-append">
                                                        <button type="button"
                                                            class="btn btn-outline-secondary js-toggle-password"
                                                            data-target="password_update_{{ $user->id }}"
                                                            title="Mostrar/Ocultar clave">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                                <button class="btn btn-xs btn-warning" form="user-update-form-{{ $user->id }}"><i class="fas fa-save"></i> Actualizar</button>
                                                <button type="button"
                                                    class="btn btn-xs btn-danger mt-1 js-delete-user"
                                                    data-delete-url="{{ route('admin.users.destroy', $user) }}"
                                                    data-user-name="{{ $user->name }}">
                                                    <i class="fas fa-trash"></i> Eliminar
                                                </button>
                                            </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-3">Sin usuarios registrados</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($users->hasPages())
                    <div class="card-footer">{{ $users->links() }}</div>
                @endif
            </div>
        </div>
    </div>

    <form method="POST" id="deleteUserForm" class="d-none">
        @csrf
        @method('DELETE')
    </form>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const deleteForm = document.getElementById('deleteUserForm');
            const buttons = document.querySelectorAll('.js-delete-user');
            const toggleButtons = document.querySelectorAll('.js-toggle-password');

            toggleButtons.forEach(function(button) {
                button.addEventListener('click', function() {
                    const targetId = this.dataset.target;
                    const input = document.getElementById(targetId);

                    if (!input) {
                        return;
                    }

                    const isPassword = input.type === 'password';
                    input.type = isPassword ? 'text' : 'password';
                    this.innerHTML = isPassword ? '<i class="fas fa-eye-slash"></i>' : '<i class="fas fa-eye"></i>';
                });
            });

            buttons.forEach(function(button) {
                button.addEventListener('click', function() {
                    const url = this.dataset.deleteUrl;
                    const userName = this.dataset.userName || 'este usuario';

                    if (!url) {
                        return;
                    }

                    if (!confirm('¿Eliminar a ' + userName + '?')) {
                        return;
                    }

                    deleteForm.action = url;
                    deleteForm.submit();
                });
            });
        });
    </script>
@endpush
