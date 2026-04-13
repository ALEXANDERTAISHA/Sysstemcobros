<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Sistema Cobros') | SystemCobros</title>

    <!-- Google Font: Source Sans Pro -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+Pro:wght@300;400;600;700&display=swap"
        rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- AdminLTE 3 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">

    <style>
        body {
            font-family: 'Source Sans Pro', sans-serif;
        }

        .sidebar-dark-primary {
            background-color: #1a1a2e !important;
        }

        .brand-link {
            background-color: #16213e !important;
        }

        .brand-link-pro {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .55rem;
            padding-left: .7rem;
            padding-right: .7rem;
        }

        .brand-copy {
            display: flex;
            flex-direction: column;
            line-height: 1.05;
            min-width: 0;
        }

        .brand-logo-system {
            width: 30px;
            height: 30px;
            border-radius: .45rem;
            object-fit: cover;
            border: 1px solid rgba(255, 255, 255, 0.28);
            background: rgba(255, 255, 255, 0.08);
        }

        .nav-sidebar .nav-item .nav-link.active {
            background-color: rgba(255, 193, 7, 0.2);
            color: #ffc107 !important;
        }

        .nav-sidebar .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.05);
        }

        .brand-text {
            color: #ffc107 !important;
            font-weight: 700;
            letter-spacing: 1px;
        }

        .brand-branch-text {
            color: rgba(255, 255, 255, 0.75);
            font-size: .68rem;
            font-weight: 600;
            letter-spacing: .03rem;
            text-transform: uppercase;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 150px;
        }

        .nav-sidebar .nav-header {
            color: rgba(255, 255, 255, 0.45);
            font-size: .72rem;
            letter-spacing: .08rem;
            font-weight: 700;
            margin-top: .35rem;
        }

        .nav-sidebar .nav-treeview {
            margin-top: .2rem;
            padding-left: .55rem;
        }

        .nav-sidebar .nav-treeview>.nav-item>.nav-link {
            border-radius: .45rem;
            margin: .12rem 0;
            padding-top: .55rem;
            padding-bottom: .55rem;
        }

        .nav-sidebar .nav-treeview>.nav-item>.nav-link p {
            font-size: .98rem;
        }

        .nav-sidebar .nav-treeview>.nav-item>.nav-link .nav-icon {
            width: 1.6rem;
            font-size: .95rem;
        }

        .nav-sidebar .nav-item.menu-open>.nav-link {
            background-color: rgba(255, 255, 255, 0.06);
        }

        .nav-sidebar .nav-item>.nav-link .right {
            transition: transform .2s ease;
        }

        .nav-sidebar .nav-item.menu-open>.nav-link .right {
            transform: rotate(-90deg);
        }

        .nav-sidebar .nav-treeview .badge {
            margin-top: .2rem;
        }

        .nav-link-disabled {
            opacity: .55;
            pointer-events: none;
            cursor: not-allowed;
            filter: grayscale(.12);
        }

        .operation-lock-note {
            color: rgba(255, 255, 255, 0.62);
            font-size: .78rem;
            line-height: 1.35;
            padding: .45rem .9rem .15rem 3.1rem;
        }

        .operation-lock-badge {
            background: rgba(245, 158, 11, .18);
            color: #fbbf24;
            border: 1px solid rgba(251, 191, 36, .28);
            font-size: .68rem;
            font-weight: 700;
            letter-spacing: .03rem;
            border-radius: 999px;
            padding: .15rem .45rem;
            margin-left: .45rem;
        }

        .small-box h3 {
            font-size: 2rem;
        }

        .table td,
        .table th {
            vertical-align: middle;
        }

        .badge-status {
            font-size: 0.8rem;
        }

        .card-header-tabs .nav-link.active {
            background: transparent;
            border-bottom: 2px solid #007bff;
        }

        .sidebar-mini.sidebar-collapse .main-sidebar:hover {
            width: 250px !important;
        }

        .top-user-avatar {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid rgba(13, 110, 253, 0.2);
        }

        .dropdown-user-card {
            min-width: 270px;
            padding: .8rem;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            align-items: center;
            gap: .7rem;
        }

        .dropdown-user-avatar {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #dee2e6;
        }

        .dropdown-user-name {
            font-weight: 700;
            margin: 0;
            line-height: 1.1;
        }

        .dropdown-user-email {
            color: #6c757d;
            font-size: .85rem;
            margin: .2rem 0 0;
        }

        .flash-pro {
            border: 0;
            border-left: 4px solid transparent;
            border-radius: .7rem;
            box-shadow: 0 6px 20px rgba(26, 32, 44, .08);
        }

        .flash-pro-success {
            background: #f0fdf4;
            color: #14532d;
            border-left-color: #22c55e;
        }

        .flash-pro-danger {
            background: #fef2f2;
            color: #7f1d1d;
            border-left-color: #ef4444;
        }

        .flash-pro-info {
            background: #eff6ff;
            color: #1e3a8a;
            border-left-color: #3b82f6;
        }

        .flash-pro-warning {
            background: #fffbeb;
            color: #78350f;
            border-left-color: #f59e0b;
        }

        .flash-pro-body {
            display: flex;
            align-items: center;
            gap: .75rem;
        }

        .flash-pro-logo {
            width: 38px;
            height: 38px;
            border-radius: .55rem;
            border: 1px solid rgba(0, 0, 0, .08);
            object-fit: cover;
            background: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 38px;
        }

        .flash-pro-content {
            flex: 1;
            line-height: 1.3;
            font-weight: 600;
        }
    </style>
    @stack('styles')
</head>

<body class="hold-transition sidebar-mini layout-fixed">
    <div class="wrapper">

        <!-- Navbar -->
        <nav class="main-header navbar navbar-expand navbar-white navbar-light">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i
                            class="fas fa-bars"></i></a>
                </li>
                <li class="nav-item d-none d-sm-inline-block">
                    <a href="{{ route('dashboard') }}" class="nav-link">
                        <i class="fas fa-home mr-1"></i> Dashboard
                    </a>
                </li>
            </ul>
            <ul class="navbar-nav ml-auto">
                <li class="nav-item">
                    <span class="nav-link text-muted">
                        <i class="fas fa-calendar-alt mr-1"></i>
                        {{ now()->format('d/m/Y') }}
                    </span>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link" data-toggle="dropdown" href="#">
                        <img src="{{ auth()->user()->avatar_url }}" alt="Avatar" class="top-user-avatar">
                        <span class="ml-1 d-none d-sm-inline">{{ auth()->user()->name }}</span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right">
                        <div class="dropdown-user-card">
                            <img src="{{ auth()->user()->avatar_url }}" alt="Avatar" class="dropdown-user-avatar">
                            <div>
                                <p class="dropdown-user-name">{{ auth()->user()->name }}</p>
                                <p class="dropdown-user-email">{{ auth()->user()->email }}</p>
                                <p class="dropdown-user-email mb-0">{{ auth()->user()->role_label }}</p>
                                <p class="dropdown-user-email mb-0">
                                    {{ auth()->user()->branch?->name ?? 'Sin sucursal' }}</p>
                            </div>
                        </div>
                        <a href="{{ route('profile.edit') }}" class="dropdown-item">
                            <i class="fas fa-user-edit mr-1"></i> Personalizar perfil
                        </a>
                        <div class="dropdown-divider"></div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-item text-danger">
                                <i class="fas fa-sign-out-alt mr-1"></i> Cerrar Sesión
                            </button>
                        </form>
                    </div>
                </li>
            </ul>
        </nav>

        <!-- Sidebar -->
        <aside class="main-sidebar sidebar-dark-primary elevation-4">
            <a href="{{ route('dashboard') }}" class="brand-link brand-link-pro">
                @if (!empty($systemLogoUrl))
                    <img src="{{ $systemLogoUrl }}" alt="Logo del sistema" class="brand-logo-system">
                @else
                    <i class="fas fa-dollar-sign" style="color:#ffc107; font-size:1.3rem;"></i>
                @endif
                <span class="brand-copy">
                    <span class="brand-text">SystemCobros</span>
                    <span class="brand-branch-text">
                        Sucursal:
                        {{ auth()->user()->isAdmin() ? 'Vista Global' : auth()->user()->branch?->name ?? 'Sin sucursal' }}
                    </span>
                </span>
            </a>

            <div class="sidebar">
                <nav class="mt-2">
                    <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu"
                        data-accordion="false">

                        @php
                            $operacionesOpen =
                                request()->routeIs('cash-box-initial.*') ||
                                request()->routeIs('transfers.*') ||
                                request()->routeIs('expenses.*') ||
                                request()->routeIs('other-incomes.*') ||
                                request()->routeIs('daily-closings.*');
                            $adminOpen =
                                request()->routeIs('clients.*') ||
                                request()->routeIs('companies.*') ||
                                request()->routeIs('admin.users.*') ||
                                request()->routeIs('admin.branches.*');
                            $reportsOpen = request()->routeIs('reports.*');
                            $canOperate = auth()->user()->hasRole('super_admin', 'admin', 'operator');
                        @endphp

                        @php
                            $initialCashQuery = \App\Models\CashBoxInitial::whereDate('date', today()->toDateString());
                            if (!auth()->user()->isAdmin()) {
                                $initialCashQuery->where('branch_id', auth()->user()->branch_id);
                            }
                            $hasTodayInitialCash = $initialCashQuery->exists();
                            $operationLockedClass = $hasTodayInitialCash ? '' : 'nav-link-disabled';
                            $operationLockedTitle = $hasTodayInitialCash
                                ? ''
                                : 'Primero registra el dinero inicial de caja chica para habilitar esta opción.';
                        @endphp

                        <li class="nav-item">
                            <a href="{{ route('dashboard') }}"
                                class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-tachometer-alt"></i>
                                <p>Dashboard</p>
                            </a>
                        </li>

                        @if ($canOperate)
                            <li class="nav-item {{ $operacionesOpen ? 'menu-open' : '' }}">
                                <a href="#" class="nav-link {{ $operacionesOpen ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-cogs"></i>
                                    <p>
                                        OPERACIONES
                                        @unless ($hasTodayInitialCash)
                                            <span class="operation-lock-badge">BLOQUEADO</span>
                                        @endunless
                                        <i class="right fas fa-angle-left"></i>
                                    </p>
                                </a>
                                <ul class="nav nav-treeview">
                                    <li class="nav-item">
                                        <a href="{{ route('cash-box-initial.index') }}"
                                            class="nav-link {{ request()->routeIs('cash-box-initial.*') ? 'active' : '' }}">
                                            <i class="fas fa-coins nav-icon"></i>
                                            <p>Dinero Inicial Caja Chica</p>
                                        </a>
                                    </li>

                                    <li class="nav-item">
                                        <a href="{{ route('expenses.index') }}"
                                            class="nav-link {{ request()->routeIs('expenses.*') ? 'active' : '' }} {{ $operationLockedClass }}"
                                            title="{{ $operationLockedTitle }}"
                                            aria-disabled="{{ $hasTodayInitialCash ? 'false' : 'true' }}">
                                            <i class="fas fa-minus-circle nav-icon"></i>
                                            <p>Gastos / Débitos</p>
                                        </a>
                                    </li>

                                    <li class="nav-item">
                                        <a href="{{ route('other-incomes.index') }}"
                                            class="nav-link {{ request()->routeIs('other-incomes.*') ? 'active' : '' }} {{ $operationLockedClass }}"
                                            title="{{ $operationLockedTitle }}"
                                            aria-disabled="{{ $hasTodayInitialCash ? 'false' : 'true' }}">
                                            <i class="fas fa-plus-circle nav-icon"></i>
                                            <p>Otros Ingresos</p>
                                        </a>
                                    </li>

                                    <li class="nav-item">
                                        <a href="{{ route('daily-closings.index') }}"
                                            class="nav-link {{ request()->routeIs('daily-closings.*') ? 'active' : '' }} {{ $operationLockedClass }}"
                                            title="{{ $operationLockedTitle }}"
                                            aria-disabled="{{ $hasTodayInitialCash ? 'false' : 'true' }}">
                                            <i class="fas fa-cash-register nav-icon"></i>
                                            <p>Cierre de Caja</p>
                                        </a>
                                    </li>

                                    @unless ($hasTodayInitialCash)
                                        <li class="nav-item">
                                            <div class="operation-lock-note">
                                                Registra primero el dinero inicial de caja chica del día para habilitar
                                                todas las opciones operativas.
                                            </div>
                                        </li>
                                    @endunless
                                </ul>
                            </li>
                        @endif

                        <li class="nav-item">
                            <a href="{{ route('reports.index') }}"
                                class="nav-link {{ $reportsOpen ? 'active' : '' }}">
                                <i class="nav-icon fas fa-file-pdf"></i>
                                <p>Reportes PDF</p>
                            </a>
                        </li>

                        @if (auth()->user()->isAdmin())
                            <li class="nav-item {{ $adminOpen ? 'menu-open' : '' }}">
                                <a href="#" class="nav-link {{ $adminOpen ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-user-cog"></i>
                                    <p>
                                        ADMINISTRACIÓN
                                        <i class="right fas fa-angle-left"></i>
                                    </p>
                                </a>
                                <ul class="nav nav-treeview">
                                    <li class="nav-item">
                                        <a href="{{ route('clients.index') }}"
                                            class="nav-link {{ request()->routeIs('clients.*') ? 'active' : '' }}">
                                            <i class="fas fa-users nav-icon"></i>
                                            <p>Clientes</p>
                                        </a>
                                    </li>

                                    <li class="nav-item">
                                        <a href="{{ route('companies.index') }}"
                                            class="nav-link {{ request()->routeIs('companies.*') && !request()->routeIs('companies.gastos-debitos.*') ? 'active' : '' }}">
                                            <i class="fas fa-building nav-icon"></i>
                                            <p>Empresas</p>
                                        </a>
                                    </li>

                                    <li class="nav-item">
                                        <a href="{{ route('companies.gastos-debitos.index') }}"
                                            class="nav-link {{ request()->routeIs('companies.gastos-debitos.*') ? 'active' : '' }}">
                                            <i class="fas fa-building nav-icon"></i>
                                            <p>Empresas Gastos Débitos</p>
                                        </a>
                                    </li>

                                    @if (auth()->user()->isSuperAdmin())
                                        <li class="nav-item">
                                            <a href="{{ route('admin.branches.index') }}"
                                                class="nav-link {{ request()->routeIs('admin.branches.*') ? 'active' : '' }}">
                                                <i class="fas fa-code-branch nav-icon"></i>
                                                <p>Sucursales</p>
                                            </a>
                                        </li>

                                        <li class="nav-item">
                                            <a href="{{ route('admin.users.index') }}"
                                                class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                                                <i class="fas fa-user-shield nav-icon"></i>
                                                <p>Usuarios y Roles</p>
                                            </a>
                                        </li>
                                    @endif
                                </ul>
                            </li>
                        @endif

                    </ul>
                </nav>
            </div>
        </aside>

        <!-- Content Wrapper -->
        <div class="content-wrapper">
            <!-- Content Header -->
            <div class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1 class="m-0">@yield('page-title', 'Dashboard')</h1>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Inicio</a></li>
                                @yield('breadcrumb')
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main content -->
            <div class="content">
                <div class="container-fluid">

                    {{-- Flash messages --}}
                    @foreach (['success' => 'check-circle', 'error' => 'times-circle', 'info' => 'info-circle', 'warning' => 'exclamation-triangle'] as $type => $icon)
                        @if (session($type))
                            @php
                                $alertType = $type === 'error' ? 'danger' : $type;
                            @endphp
                            <div class="alert flash-pro flash-pro-{{ $alertType }} alert-dismissible fade show">
                                <div class="flash-pro-body">
                                    @if (!empty($systemLogoUrl))
                                        <img src="{{ $systemLogoUrl }}" alt="Logo" class="flash-pro-logo">
                                    @else
                                        <span class="flash-pro-logo"><i class="fas fa-dollar-sign"></i></span>
                                    @endif
                                    <div class="flash-pro-content">
                                        <i class="fas fa-{{ $icon }} mr-1"></i>
                                        {!! session($type) !!}
                                    </div>
                                </div>
                                <button type="button" class="close" data-dismiss="alert">
                                    <span>&times;</span>
                                </button>
                            </div>
                        @endif
                    @endforeach

                    @yield('content')
                </div>
            </div>
        </div>

        <!-- Footer -->
        <footer class="main-footer">
            <strong>&copy; {{ date('Y') }} TaishaTechonolgy.</strong> Todos los derechos reservados.
            <div class="float-right d-none d-sm-inline-block">
                <b>Versión</b> 1.0.0
            </div>
        </footer>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- Bootstrap 4 -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- AdminLTE App -->
    <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const numberInputs = document.querySelectorAll('input[type="number"]');

            function applyNumberMessage(input) {
                const value = (input.value || '').trim();

                if (value === '') {
                    input.setCustomValidity('');
                    return;
                }

                if (input.validity.badInput || /[^0-9+\-.]/.test(value)) {
                    input.setCustomValidity('Ingrese solo numeros en este campo.');
                    return;
                }

                input.setCustomValidity('');
            }

            numberInputs.forEach(function(input) {
                input.addEventListener('input', function() {
                    applyNumberMessage(input);
                });

                input.addEventListener('invalid', function() {
                    if (input.validity.valueMissing) {
                        input.setCustomValidity('Este campo es obligatorio.');
                        return;
                    }

                    if (input.validity.badInput || /[^0-9+\-.]/.test((input.value || '').trim())) {
                        input.setCustomValidity('Ingrese solo numeros en este campo.');
                        return;
                    }

                    if (input.validity.rangeUnderflow || input.validity.rangeOverflow || input
                        .validity.stepMismatch) {
                        input.setCustomValidity('Ingrese un numero valido para este campo.');
                        return;
                    }

                    input.setCustomValidity('Ingrese un numero valido.');
                });
            });
        });
    </script>

    @stack('scripts')
</body>

</html>
