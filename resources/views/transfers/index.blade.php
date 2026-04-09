@extends('layouts.app')
@section('title', 'Giros / Transferencias')
@section('page-title', 'Giros / Transferencias')
@section('breadcrumb')<li class="breadcrumb-item active">Giros</li>@endsection

@section('content')
    <div class="row mb-3">
        <div class="col-md-8">
            <form method="GET" class="form-inline">
                <input type="text" name="search" class="form-control mr-2 mb-1"
                    placeholder="Buscar remitente, destinatario..." value="{{ $search }}">
                <input type="date" name="date" class="form-control mr-2 mb-1" value="{{ $date }}">
                <select name="company_id" class="form-control mr-2 mb-1">
                    <option value="">Todas las empresas</option>
                    @foreach ($companies as $c)
                        <option value="{{ $c->id }}" {{ $company == $c->id ? 'selected' : '' }}>{{ $c->name }}
                        </option>
                    @endforeach
                </select>
                <select name="status" class="form-control mr-2 mb-1">
                    <option value="all" {{ $status === 'all' ? 'selected' : '' }}>Todos</option>
                    <option value="pending" {{ $status === 'pending' ? 'selected' : '' }}>Pendientes</option>
                    <option value="sent" {{ $status === 'sent' ? 'selected' : '' }}>Enviados</option>
                    <option value="resent" {{ $status === 'resent' ? 'selected' : '' }}>Reenviados</option>
                    <option value="cancelled" {{ $status === 'cancelled' ? 'selected' : '' }}>Cancelados</option>
                </select>
                <button class="btn btn-primary mb-1"><i class="fas fa-search"></i></button>
                <a href="{{ route('transfers.index') }}" class="btn btn-secondary mb-1 ml-1"><i
                        class="fas fa-redo"></i></a>
            </form>
        </div>
        <div class="col-md-4 text-right">
            @if ($pendingCount > 0)
                <span class="badge badge-warning badge-pill p-2 mr-2">
                    <i class="fas fa-clock mr-1"></i> {{ $pendingCount }} pendiente(s)
                </span>
            @endif
            <a href="{{ route('transfers.create') }}" class="btn btn-primary">
                <i class="fas fa-plus mr-1"></i> Nuevo Giro
            </a>
        </div>
    </div>

    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-paper-plane mr-1"></i> Lista de Giros</h3>
            <div class="card-tools">
                <span class="badge badge-secondary">{{ $transfers->total() }} registros</span>
            </div>
        </div>
        <div class="card-body p-0">
            <table class="table table-hover table-sm mb-0">
                <thead class="thead-dark">
                    <tr>
                        <th>Fecha</th>
                        <th>Empresa</th>
                        <th>Sucursal (Remitente)</th>
                        <th>Destinatario</th>
                        <th class="text-right">Monto</th>
                        <th class="text-center">Estado</th>
                        <th>Código</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transfers as $transfer)
                        <tr>
                            <td>{{ $transfer->transfer_date->format('d/m/Y') }}</td>
                            <td>
                                <span class="badge" style="background-color:{{ $transfer->company->color }};">
                                    {{ $transfer->company->code }}
                                </span>
                                <small>{{ $transfer->company->name }}</small>
                            </td>
                            <td>
                                {{ $transfer->branch?->name ?? $transfer->sender_name }}
                                @if (auth()->user()->isAdmin() && $transfer->branch?->name)
                                    <small class="text-muted d-block">{{ $transfer->sender_name }}</small>
                                @endif
                            </td>
                            <td>{{ $transfer->receiver_name }}</td>
                            <td class="text-right font-weight-bold">${{ number_format($transfer->amount, 2) }}</td>
                            <td class="text-center">
                                <span
                                    class="badge badge-{{ $transfer->status_color }}">{{ $transfer->status_label }}</span>
                            </td>
                            <td><small>{{ $transfer->transaction_code ?? '-' }}</small></td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    @if ($transfer->status === 'pending')
                                        <form method="POST" action="{{ route('transfers.mark-sent', $transfer) }}"
                                            class="d-inline">
                                            @csrf @method('PATCH')
                                            <button class="btn btn-success btn-sm" title="Marcar enviado">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        </form>
                                    @endif
                                    @if (in_array($transfer->status, ['pending', 'sent']))
                                        <form method="POST" action="{{ route('transfers.resend', $transfer) }}"
                                            class="d-inline">
                                            @csrf @method('PATCH')
                                            <button class="btn btn-info btn-sm" title="Reenviar">
                                                <i class="fas fa-redo"></i>
                                            </button>
                                        </form>
                                    @endif
                                    <a href="{{ route('transfers.edit', $transfer) }}" class="btn btn-warning btn-sm">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form method="POST" action="{{ route('transfers.destroy', $transfer) }}"
                                        class="d-inline" onsubmit="return confirm('¿Eliminar este giro?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">
                                <i class="fas fa-inbox fa-2x d-block mb-2"></i> Sin giros registrados
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($transfers->hasPages())
            <div class="card-footer">
                {{ $transfers->withQueryString()->links() }}
            </div>
        @endif
    </div>
@endsection
