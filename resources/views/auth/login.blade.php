<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Iniciar Sesión | SystemCobros</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
        }

        .login-box {
            width: 380px;
        }

        .login-card-body {
            border-radius: 12px;
        }

        .login-logo a {
            color: #ffc107;
            font-size: 2rem;
            font-weight: 700;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        .login-system-logo {
            width: 72px;
            height: 72px;
            object-fit: contain;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.9);
            padding: 8px;
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.22);
            margin-bottom: 10px;
        }

        .login-logo small {
            display: block;
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.9rem;
            letter-spacing: 2px;
            text-transform: uppercase;
        }
    </style>
</head>

<body class="hold-transition login-page">
    @php($systemLogoUrl = \App\Models\AppSetting::systemLogoUrl())
    <div class="login-box">
        <div class="login-logo">
            @if ($systemLogoUrl)
                <img src="{{ $systemLogoUrl }}" alt="Logo del sistema" class="login-system-logo d-block mx-auto">
            @endif
            <a href="#">
                @if (!$systemLogoUrl)
                    <i class="fas fa-dollar-sign mr-2"></i>
                @endif
                SystemCobros
            </a>
            <small>Sistema de Cobros y Giros</small>
        </div>

        <div class="card">
            <div class="card-body login-card-body">
                <p class="login-box-msg">Inicia sesión para continuar</p>

                @if ($errors->any())
                    <div class="alert alert-danger py-2">
                        <i class="fas fa-exclamation-circle mr-1"></i>
                        {{ $errors->first() }}
                    </div>
                @endif

                <form action="{{ route('login') }}" method="POST">
                    @csrf
                    <div class="input-group mb-3">
                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                            placeholder="Correo electrónico" value="{{ old('email') }}" required autofocus>
                        <div class="input-group-append">
                            <div class="input-group-text"><span class="fas fa-envelope"></span></div>
                        </div>
                    </div>
                    <div class="input-group mb-3">
                        <input type="password" name="password" class="form-control" placeholder="Contraseña" required>
                        <div class="input-group-append">
                            <div class="input-group-text"><span class="fas fa-lock"></span></div>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-8">
                            <div class="icheck-primary">
                                <input type="checkbox" id="remember" name="remember">
                                <label for="remember">Recordarme</label>
                            </div>
                        </div>
                        <div class="col-4">
                            <button type="submit" class="btn btn-warning btn-block font-weight-bold">
                                <i class="fas fa-sign-in-alt mr-1"></i> Entrar
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
</body>

</html>
