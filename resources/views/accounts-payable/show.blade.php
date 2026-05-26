@extends('layouts.app')
@section('title', 'Cuenta por Pagar de ' . $accountPayable->client->name)
@section('page-title', 'Cuenta por Pagar: ' . $accountPayable->client->name)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('accounts-payable.index') }}">Cuentas por Pagar</a></li>
    <li class="breadcrumb-item active">{{ $accountPayable->client->name }}</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-md-5">
            <div class="card card-outline card-warning">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-info-circle mr-1"></i> Detalle</h3>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-borderless mb-0">
                        <tr><th>Cliente:</th><td>{{ $accountPayable->client->name }}</td></tr>
                        <tr><th>Empresa:</th><td>{{ $accountPayable->company?->name ?? '-' }}</td></tr>
                        <tr><th>Concepto:</th><td>{{ $accountPayable->concept }}</td></tr>
                        <tr><th>Fecha:</th><td>{{ $accountPayable->issued_date->format('d/m/Y') }}</td></tr>
                        @if($accountPayable->due_date)
                            <tr>
                                <th>Vence:</th>
                                <td class="{{ $accountPayable->due_date->isPast() && $accountPayable->status !== 'paid' ? 'text-danger font-weight-bold' : '' }}">
                                    {{ $accountPayable->due_date->format('d/m/Y') }}
                                </td>
                            </tr>
                        @endif
                        <tr><th>Total:</th><td class="font-weight-bold">${{ number_format($accountPayable->total_amount, 2) }}</td></tr>
                        <tr><th>Pagado:</th><td class="text-success font-weight-bold">${{ number_format($accountPayable->paid_amount, 2) }}</td></tr>
                        <tr>
                            <th>Saldo:</th>
                            <td class="font-weight-bold {{ $accountPayable->balance > 0 ? 'text-danger' : 'text-success' }} h5 mb-0">
                                ${{ number_format($accountPayable->balance, 2) }}
                            </td>
                        </tr>
                        <tr>
                            <th>Estado:</th>
                            <td>
                                <span class="badge badge-{{ $accountPayable->status === 'paid' ? 'success' : ($accountPayable->status === 'partial' ? 'info' : 'warning') }} badge-pill px-3">
                                    {{ $accountPayable->status === 'paid' ? 'Pagado' : ($accountPayable->status === 'partial' ? 'Parcial' : 'Activo') }}
                                </span>
                            </td>
                        </tr>
                    </table>
                    @if($accountPayable->notes)
                        <div class="mt-2 alert alert-light p-2"><small>{{ $accountPayable->notes }}</small></div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-7">
            @if($accountPayable->status !== 'paid')
                <div class="card card-success card-outline">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-money-bill-wave mr-1"></i> Registrar Pago</h3>
                    </div>
                    <form method="POST" action="{{ route('accounts-payable.payments.store', $accountPayable) }}">
                        @csrf
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-5">
                                    <div class="form-group">
                                        <label>Monto a Pagar ($) *</label>
                                        <input type="number" name="amount" step="0.01" min="0.01" max="{{ $accountPayable->balance }}"
                                            class="form-control @error('amount') is-invalid @enderror"
                                            placeholder="Max: ${{ number_format($accountPayable->balance, 2) }}" required>
                                        @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Fecha *</label>
                                        <input type="date" name="payment_date" class="form-control" value="{{ today()->toDateString() }}" required>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>&nbsp;</label>
                                        <button type="submit" class="btn btn-success btn-block">
                                            <i class="fas fa-plus mr-1"></i> Pagar
                                        </button>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-group mb-0">
                                        <label>Notas</label>
                                        <input type="text" name="notes" class="form-control" placeholder="Observaciones del pago...">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            @endif

            <div class="card card-outline card-info">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-history mr-1"></i> Historial de Pagos</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Fecha</th>
                                <th class="text-right">Monto</th>
                                <th>Notas</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($accountPayable->payments as $payment)
                                <tr>
                                    <td>{{ $payment->payment_date->format('d/m/Y') }}</td>
                                    <td class="text-right text-success font-weight-bold">${{ number_format($payment->amount, 2) }}</td>
                                    <td><small>{{ $payment->notes ?? '-' }}</small></td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-muted py-3">Sin pagos registrados</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
