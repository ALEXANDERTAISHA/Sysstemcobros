@extends('layouts.app')
@section('title', 'Reportes')
@section('page-title', 'Reportes Financieros')
@section('breadcrumb')<li class="breadcrumb-item active">Reportes</li>@endsection

@section('content')
    <div class="row mb-3">
        <div class="col-md-12">
            <div class="card card-outline card-primary mb-3">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-filter mr-1"></i> Filtros del Reporte</h3>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('reports.index') }}" class="form-row align-items-end">
                        <div class="form-group col-md-3">
                            <label>Desde</label>
                            <input type="date" name="date_from" class="form-control" value="{{ $dateFrom }}"
                                required>
                        </div>
                        <div class="form-group col-md-3">
                            <label>Hasta</label>
                            <input type="date" name="date_to" class="form-control" value="{{ $dateTo }}" required>
                        </div>
                        <div class="form-group col-md-3">
                            <label>Empresa</label>
                            <select name="company_id" class="form-control">
                                <option value="">Todas las empresas</option>
                                @foreach ($companies as $company)
                                    <option value="{{ $company->id }}"
                                        {{ (string) $companyId === (string) $company->id ? 'selected' : '' }}>
                                        {{ $company->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        @if(auth()->user()->isAdmin())
                            <div class="form-group col-md-2">
                                <label>Sucursal</label>
                                <select name="branch_id" class="form-control">
                                    <option value="">Todas</option>
                                    @foreach ($branches as $branch)
                                        <option value="{{ $branch->id }}"
                                            {{ (string) $branchId === (string) $branch->id ? 'selected' : '' }}>
                                            {{ $branch->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        <div class="form-group col-md-2 text-right">
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-search mr-1"></i> Consultar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-3">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>${{ number_format($summary['total_incomes'], 2) }}</h3>
                    <p>Total Ingresos (Giros)</p>
                </div>
                <div class="icon"><i class="fas fa-arrow-circle-up"></i></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>${{ number_format($summary['total_expenses'], 2) }}</h3>
                    <p>Total Débitos</p>
                </div>
                <div class="icon"><i class="fas fa-arrow-circle-down"></i></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>${{ number_format($summary['total_other_incomes'], 2) }}</h3>
                    <p>Otros Ingresos</p>
                </div>
                <div class="icon"><i class="fas fa-coins"></i></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="small-box bg-primary">
                <div class="inner">
                    <h3>${{ number_format($printable['final_total'], 2) }}</h3>
                    <p>TOTAL FINAL (GANANCIAS)</p>
                </div>
                <div class="icon"><i class="fas fa-calculator"></i></div>
            </div>
        </div>
    </div>

    <div class="card card-outline card-secondary">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0"><i class="fas fa-table mr-1"></i> Detalle de Giros</h3>
            <a href="{{ route('reports.export-pdf', ['date_from' => $dateFrom, 'date_to' => $dateTo, 'company_id' => $companyId, 'branch_id' => $branchId]) }}"
                class="btn btn-danger btn-sm" target="_blank" rel="noopener">
                <i class="fas fa-file-pdf mr-1"></i> Exportar PDF
            </a>
        </div>
        <div class="card-body p-0">
            <table class="table table-hover table-sm mb-0">
                <thead class="thead-dark">
                    <tr>
                        <th>Fecha</th>
                        <th>Empresa</th>
                        <th>Sucursal (Remitente)</th>
                        <th>Destinatario</th>
                        <th class="text-center">Estado</th>
                        <th class="text-right">Monto</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transfers as $transfer)
                        <tr>
                            <td>{{ $transfer->transfer_date->format('d/m/Y') }}</td>
                            <td>{{ $transfer->company?->name ?? '-' }}</td>
                            <td>
                                {{ $transfer->branch?->name ?? $transfer->sender_name }}
                                @if(auth()->user()->isAdmin() && $transfer->branch?->name)
                                    <small class="text-muted d-block">{{ $transfer->sender_name }}</small>
                                @endif
                            </td>
                            <td>{{ $transfer->receiver_name }}</td>
                            <td class="text-center"><span
                                    class="badge badge-{{ $transfer->status_color }}">{{ $transfer->status_label }}</span>
                            </td>
                            <td class="text-right">${{ number_format($transfer->amount, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No hay datos para este rango.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($transfers->hasPages())
            <div class="card-footer">
                {{ $transfers->links() }}
            </div>
        @endif
    </div>

    <div class="row mt-3">
        <div class="col-md-6">
            <div class="card card-outline card-info">
                <div class="card-header">
                    <h3 class="card-title">Resumen Contable</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <tr>
                            <th>Giros Registrados</th>
                            <td class="text-right">{{ number_format($summary['transfers_count']) }}</td>
                        </tr>
                        <tr>
                            <th>Valor Total (Ingresos - Débitos)</th>
                            <td class="text-right">${{ number_format(abs($summary['value_total']), 2) }}</td>
                        </tr>
                        <tr class="table-success">
                            <th>Suma Total (Valor Total + Otros Ingresos)</th>
                            <td class="text-right font-weight-bold">${{ number_format($summary['sum_total'], 2) }}</td>
                        </tr>
                        <tr class="table-light">
                            <th>VALOR EXISTENTE (CAJA) *</th>
                            <td class="text-right">${{ number_format($printable['existing_value'], 2) }}</td>
                        </tr>
                        <tr class="table-warning">
                            <th>Diferencia</th>
                            <td class="text-right font-weight-bold">${{ number_format($printable['difference'], 2) }}</td>
                        </tr>
                        <tr class="table-primary">
                            <th>Total Final</th>
                            <td class="text-right font-weight-bold">${{ number_format($printable['final_total'], 2) }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card card-outline card-warning">
                <div class="card-header">
                    <h3 class="card-title">Cartera de Fiados</h3>
                </div>
                <div class="card-body text-center">
                    <p class="text-muted mb-1">Saldo activo pendiente de cobro</p>
                    <h2 class="mb-0">${{ number_format($summary['active_credit_balance'], 2) }}</h2>
                </div>
            </div>
        </div>
    </div>
@endsection
