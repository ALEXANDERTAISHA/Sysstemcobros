@extends('layouts.app')
@section('title', 'Mi Perfil')
@section('page-title', 'Personalizar Perfil')
@section('breadcrumb')
    <li class="breadcrumb-item active">Perfil</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-5">
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-user-circle mr-1"></i> Perfil de Usuario</h3>
                </div>
                <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        <div class="text-center mb-3">
                            <img src="{{ $user->avatar_url }}" alt="Avatar" class="img-circle elevation-2"
                                style="width:96px;height:96px;object-fit:cover;">
                        </div>

                        <div class="form-group">
                            <label>Foto de perfil</label>
                            <div class="custom-file">
                                <input type="file" name="avatar" id="avatar"
                                    class="custom-file-input @error('avatar') is-invalid @enderror" accept="image/*">
                                <label class="custom-file-label" for="avatar">Seleccionar imagen...</label>
                                @error('avatar')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            @if ($user->avatar_path)
                                <div class="custom-control custom-checkbox mt-2">
                                    <input type="checkbox" class="custom-control-input" id="remove_avatar"
                                        name="remove_avatar" value="1">
                                    <label class="custom-control-label" for="remove_avatar">Eliminar foto actual</label>
                                </div>
                            @endif
                        </div>

                        <div class="form-group">
                            <label>Nombre</label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                value="{{ old('name', $user->name) }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label>Correo</label>
                            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                                value="{{ old('email', $user->email) }}" required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="card-footer">
                        <button class="btn btn-primary"><i class="fas fa-save mr-1"></i> Guardar perfil</button>
                    </div>
                </form>
            </div>

            <div class="card card-warning card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-key mr-1"></i> Cambiar Contraseña</h3>
                </div>
                <form method="POST" action="{{ route('profile.password.update') }}">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        <div class="form-group">
                            <label>Contraseña actual</label>
                            <div class="input-group">
                                <input type="password" name="current_password" id="current_password"
                                    autocomplete="current-password"
                                    class="form-control @error('current_password') is-invalid @enderror" required>
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-outline-secondary js-toggle-password"
                                        data-target="current_password" title="Mostrar/Ocultar contraseña">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            @error('current_password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label>Nueva contraseña</label>
                            <div class="input-group">
                                <input type="password" name="password" id="new_password"
                                    autocomplete="new-password"
                                    class="form-control @error('password') is-invalid @enderror" required>
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-outline-secondary js-toggle-password"
                                        data-target="new_password" title="Mostrar/Ocultar contraseña">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Mínimo 8 caracteres, letras mayúsculas y números.</small>
                        </div>
                        <div class="form-group mb-0">
                            <label>Confirmar nueva contraseña</label>
                            <div class="input-group">
                                <input type="password" name="password_confirmation" id="password_confirmation"
                                    autocomplete="new-password"
                                    class="form-control @error('password_confirmation') is-invalid @enderror" required>
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-outline-secondary js-toggle-password"
                                        data-target="password_confirmation" title="Mostrar/Ocultar contraseña">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            @error('password_confirmation')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="card-footer">
                        <button class="btn btn-warning"><i class="fas fa-shield-alt mr-1"></i> Actualizar
                            contraseña</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card card-success card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-palette mr-1"></i> Identidad del Sistema</h3>
                </div>
                <form method="POST" action="{{ route('profile.system-logo.update') }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="mr-3">
                                @if ($hasSystemLogo && $systemLogoUrl)
                                    <img src="{{ $systemLogoUrl }}" alt="Logo del sistema" class="img-circle elevation-2"
                                        style="width:72px;height:72px;object-fit:cover;">
                                @else
                                    <div class="img-circle elevation-1 d-flex align-items-center justify-content-center bg-light"
                                        style="width:72px;height:72px;">
                                        <i class="fas fa-image text-muted" style="font-size:1.4rem;"></i>
                                    </div>
                                @endif
                            </div>
                            <div>
                                <strong>Logo principal del sistema</strong>
                                <p class="text-muted mb-0">Este logo se mostrará junto al nombre del sistema y en los
                                    reportes PDF.</p>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Subir logo principal</label>
                            <div class="custom-file">
                                <input type="file" name="system_logo" id="system_logo"
                                    class="custom-file-input @error('system_logo') is-invalid @enderror" accept="image/*">
                                <label class="custom-file-label" for="system_logo">Seleccionar imagen...</label>
                                @error('system_logo')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        @if ($hasSystemLogo)
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="remove_system_logo"
                                    name="remove_system_logo" value="1">
                                <label class="custom-control-label" for="remove_system_logo">Eliminar logo principal
                                    actual</label>
                            </div>
                        @endif
                    </div>
                    <div class="card-footer">
                        <button class="btn btn-success"><i class="fas fa-save mr-1"></i> Guardar logo del sistema</button>
                    </div>
                </form>
            </div>

            <div class="card card-info card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-sign-in-alt mr-1"></i> Logo de Autenticación</h3>
                </div>
                <form method="POST" action="{{ route('profile.auth-logo.update') }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="mr-3">
                                @if ($hasAuthLogo && $authLogoUrl)
                                    <img src="{{ $authLogoUrl }}" alt="Logo de autenticación" class="img-circle elevation-2"
                                        style="width:72px;height:72px;object-fit:cover;">
                                @else
                                    <div class="img-circle elevation-1 d-flex align-items-center justify-content-center bg-light"
                                        style="width:72px;height:72px;">
                                        <i class="fas fa-image text-muted" style="font-size:1.4rem;"></i>
                                    </div>
                                @endif
                            </div>
                            <div>
                                <strong>Logo autenticación</strong>
                                <p class="text-muted mb-0">Este logo se mostrará en la pantalla de inicio de sesión.</p>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Subir logo autenticación</label>
                            <div class="custom-file">
                                <input type="file" name="auth_logo" id="auth_logo"
                                    class="custom-file-input @error('auth_logo') is-invalid @enderror" accept="image/*">
                                <label class="custom-file-label" for="auth_logo">Seleccionar imagen...</label>
                                @error('auth_logo')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        @if ($hasAuthLogo)
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="remove_auth_logo"
                                    name="remove_auth_logo" value="1">
                                <label class="custom-control-label" for="remove_auth_logo">Eliminar logo autenticación actual</label>
                            </div>
                        @endif
                    </div>
                    <div class="card-footer">
                        <button class="btn btn-info"><i class="fas fa-save mr-1"></i> Guardar logo autenticación</button>
                    </div>
                </form>
            </div>

        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const fileInputs = [
                document.getElementById('avatar'),
                document.getElementById('system_logo'),
                document.getElementById('auth_logo')
            ];

            fileInputs.forEach(function(input) {
                if (!input) return;
                input.addEventListener('change', function() {
                    const label = this.nextElementSibling;
                    if (this.files.length > 0) {
                        label.textContent = this.files[0].name;
                    }
                });
            });

            document.querySelectorAll('.js-toggle-password').forEach(function(button) {
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

        });
    </script>
@endpush
