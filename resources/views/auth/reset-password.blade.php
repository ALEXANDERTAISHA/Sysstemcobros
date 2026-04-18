<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Restablecer Contraseña | SystemCobros</title>
    @php($faviconUrl = \App\Models\AppSetting::faviconUrl())
    @if ($faviconUrl)
        <link rel="icon" type="image/png" href="{{ $faviconUrl }}">
        <link rel="shortcut icon" href="{{ $faviconUrl }}">
    @endif
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
        }

        .login-box {
            width: 430px;
        }

        .login-logo a {
            color: #ffc107;
            font-size: 2rem;
            font-weight: 700;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }
    </style>
</head>

<body class="hold-transition login-page">
    <div class="login-box">
        <div class="login-logo">
            <a href="#"><i class="fas fa-lock-open mr-2"></i>SystemCobros</a>
        </div>

        <div class="card">
            <div class="card-body login-card-body">
                <p class="login-box-msg">Restablecer contraseña</p>

                @if ($errors->any())
                    <div class="alert alert-danger py-2">{{ $errors->first() }}</div>
                @endif

                <form method="POST" action="{{ route('password.update') }}">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">

                    <div class="input-group mb-3">
                        <input type="email" name="email" class="form-control" placeholder="Correo electrónico" value="{{ old('email', $email) }}" required autofocus>
                        <div class="input-group-append">
                            <div class="input-group-text"><span class="fas fa-envelope"></span></div>
                        </div>
                    </div>

                    <div class="input-group mb-3">
                        <input type="password" id="reset_password" name="password" class="form-control" placeholder="Nueva contraseña" required>
                        <div class="input-group-append">
                            <button type="button" class="btn btn-outline-secondary js-toggle-password" data-target="reset_password" title="Mostrar/Ocultar contraseña">
                                <span class="fas fa-eye"></span>
                            </button>
                        </div>
                    </div>

                    <div class="input-group mb-3">
                        <input type="password" id="reset_password_confirmation" name="password_confirmation" class="form-control" placeholder="Confirmar contraseña" required>
                        <div class="input-group-append">
                            <button type="button" class="btn btn-outline-secondary js-toggle-password" data-target="reset_password_confirmation" title="Mostrar/Ocultar contraseña">
                                <span class="fas fa-eye"></span>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-warning btn-block font-weight-bold">
                        <i class="fas fa-check mr-1"></i> Guardar nueva contraseña
                    </button>
                </form>

                <div class="text-center mt-3">
                    <a href="{{ route('login') }}">Volver al login</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.js-toggle-password').forEach(function(button) {
                button.addEventListener('click', function() {
                    const target = document.getElementById(button.dataset.target);
                    if (!target) {
                        return;
                    }

                    const isPassword = target.type === 'password';
                    target.type = isPassword ? 'text' : 'password';
                    button.innerHTML = isPassword ? '<span class="fas fa-eye-slash"></span>' : '<span class="fas fa-eye"></span>';
                });
            });
        });
    </script>
</body>

</html>
