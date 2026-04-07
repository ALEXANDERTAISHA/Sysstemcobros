@extends('layouts.app')
@section('title', 'Débito de ' . $credit->client->name)
@section('page-title', 'Débito: ' . $credit->client->name)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('expenses.index') }}">Gastos / Débitos</a></li>
    <li class="breadcrumb-item active">{{ $credit->client->name }}</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-md-5">
            <div class="card card-outline card-warning">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-info-circle mr-1"></i> Detalle del Débito</h3>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <th>Cliente:</th>
                            <td>{{ $credit->client->name }}</td>
                        </tr>
                        <tr>
                            <th>Concepto:</th>
                            <td>{{ $credit->concept }}</td>
                        </tr>
                        <tr>
                            <th>Fecha:</th>
                            <td>{{ $credit->granted_date->format('d/m/Y') }}</td>
                        </tr>
                        @if ($credit->due_date)
                            <tr>
                                <th>Vence:</th>
                                <td
                                    class="{{ $credit->due_date->isPast() && $credit->status !== 'paid' ? 'text-danger font-weight-bold' : '' }}">
                                    {{ $credit->due_date->format('d/m/Y') }}
                                    @if ($credit->due_date->isPast() && $credit->status !== 'paid')
                                        <span class="badge badge-danger ml-1">Vencido</span>
                                    @endif
                                </td>
                            </tr>
                        @endif
                        <tr>
                            <th>Total:</th>
                            <td class="font-weight-bold">${{ number_format($credit->total_amount, 2) }}</td>
                        </tr>
                        <tr>
                            <th>Pagado:</th>
                            <td class="text-success font-weight-bold">${{ number_format($credit->paid_amount, 2) }}</td>
                        </tr>
                        <tr>
                            <th>Saldo:</th>
                            <td
                                class="font-weight-bold {{ $credit->balance > 0 ? 'text-danger' : 'text-success' }} h5 mb-0">
                                ${{ number_format($credit->balance, 2) }}
                            </td>
                        </tr>
                        <tr>
                            <th>Estado:</th>
                            <td>
                                <span
                                    class="badge badge-{{ $credit->status === 'paid' ? 'success' : ($credit->status === 'partial' ? 'info' : 'warning') }} badge-pill px-3">
                                    {{ $credit->status === 'paid' ? 'Pagado' : ($credit->status === 'partial' ? 'Parcial' : 'Activo') }}
                                </span>
                            </td>
                        </tr>
                    </table>
                    @if ($credit->notes)
                        <div class="mt-2 alert alert-light p-2"><small>{{ $credit->notes }}</small></div>
                    @endif

                    @if ($credit->client->whatsapp)
                        <div class="mt-3">
                            <form method="POST" action="{{ route('expenses.send-reminder', $credit) }}">
                                @csrf
                                <button type="submit" class="btn btn-success btn-block">
                                    <i class="fab fa-whatsapp mr-1"></i> Enviar recordatorio WhatsApp
                                </button>
                            </form>
                        </div>
                    @endif
                </div>
            </div>

            @php $pct = $credit->total_amount > 0 ? round(($credit->paid_amount / $credit->total_amount) * 100) : 0; @endphp
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-1">
                        <span>Progreso de pago</span>
                        <strong>{{ $pct }}%</strong>
                    </div>
                    <div class="progress" style="height:20px;">
                        <div class="progress-bar bg-{{ $pct >= 100 ? 'success' : ($pct >= 50 ? 'info' : 'warning') }} progress-bar-striped"
                            style="width:{{ $pct }}%">{{ $pct }}%</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-7">
            @if ($credit->status !== 'paid')
                <div class="card card-success card-outline">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-money-bill-wave mr-1"></i> Registrar Pago</h3>
                    </div>
                    <form method="POST" action="{{ route('expenses.payments.store', $credit) }}">
                        @csrf
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-5">
                                    <div class="form-group">
                                        <label>Monto a Pagar ($) *</label>
                                        <input type="number" name="amount" step="0.01" min="0.01"
                                            max="{{ $credit->balance }}"
                                            class="form-control @error('amount') is-invalid @enderror"
                                            placeholder="Máx: ${{ number_format($credit->balance, 2) }}" required>
                                        @error('amount')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Fecha *</label>
                                        <input type="date" name="payment_date" class="form-control"
                                            value="{{ today()->toDateString() }}" required>
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
                                    <div class="form-group">
                                        <label>Notas</label>
                                        <input type="text" name="notes" class="form-control"
                                            placeholder="Observaciones del pago...">
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
                            @forelse($credit->payments as $payment)
                                <tr>
                                    <td>{{ $payment->payment_date->format('d/m/Y') }}</td>
                                    <td class="text-right text-success font-weight-bold">
                                        ${{ number_format($payment->amount, 2) }}</td>
                                    <td><small>{{ $payment->notes ?? '-' }}</small></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-3">Sin pagos registrados</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
