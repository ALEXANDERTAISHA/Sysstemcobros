@extends('layouts.app')
@section('title', 'Nuevo Cierre')
@section('page-title', 'Transferencias')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('daily-closings.index') }}">Cierres</a></li>
    <li class="breadcrumb-item active">Nuevo</li>
@endsection

@push('styles')
    <style>
        .closing-section {
            border-left: 4px solid;
            padding-left: 12px;
            margin-bottom: 8px;
        }

        .closing-section.income {
            border-color: #28a745;
        }

        .closing-section.expense {
            border-color: #dc3545;
        }

        .closing-section.other {
            border-color: #28a745;
        }

        .closing-section.total {
            border-color: #ffc107;
        }
    </style>
@endpush

@section('content')

    @if ($existing)
        <div class="alert alert-info"><i class="fas fa-info-circle mr-1"></i> Ya existe un cierre para esta fecha. Puedes
            actualizarlo.</div>
    @endif

    <div class="row mb-3">
        <div class="col-lg-6">
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-plus-circle mr-1"></i> Datos de Transferencia</h3>
                </div>
                <form method="POST" action="{{ route('transfers.store') }}">
                    @csrf
                    <input type="hidden" name="from_daily_closing" value="1">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Empresa *</label>
                                    <select name="company_id" class="form-control @error('company_id') is-invalid @enderror"
                                        required>
                                        <option value="">Seleccionar empresa...</option>
                                        @foreach ($companies as $c)
                                            <option value="{{ $c->id }}"
                                                {{ old('company_id') == $c->id ? 'selected' : '' }}>
                                                {{ $c->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('company_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Fecha de transferencia *</label>
                                    <input type="date" name="transfer_date"
                                        class="form-control @error('transfer_date') is-invalid @enderror"
                                        value="{{ old('transfer_date', $date) }}" required>
                                    @error('transfer_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Nombre del Remitente *</label>
                                    <input type="text" class="form-control"
                                        value="{{ auth()->user()->branch?->name ?? auth()->user()->name ?? 'Usuario actual' }}" readonly>
                                    <input type="hidden" name="sender_name"
                                        value="{{ old('sender_name', auth()->user()->branch?->name ?? auth()->user()->name ?? '') }}">
                                    <small class="form-text text-muted">
                                        Este campo se registra automaticamente con la sucursal de la cuenta abierta.
                                    </small>
                                    @error('sender_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <input type="hidden" name="receiver_name" value="{{ old('receiver_name', 'N/A') }}">

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Monto ($) *</label>
                                    <input type="number" name="amount" step="0.01" min="0.01"
                                        class="form-control @error('amount') is-invalid @enderror"
                                        value="{{ old('amount') }}" required>
                                    @error('amount')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                        </div>
                    </div>

                    <input type="hidden" name="status" value="sent">

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-1"></i> Guardar Transferencia
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card card-outline card-primary h-100">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-paper-plane mr-1"></i> Lista de Transferencia</h3>
                    <div class="card-tools">
                        <span class="badge badge-secondary mr-1">Total del dia: {{ $transferTotalCount }}</span>
                        @if ($transferPendingCount > 0)
                            <span class="badge badge-warning">
                                <i class="fas fa-clock mr-1"></i>{{ $transferPendingCount }} pendiente(s)
                            </span>
                        @endif
                    </div>
                </div>
                <div class="card-body p-2">
                    <form method="GET" action="{{ route('daily-closings.create') }}" class="mb-2" id="transferFilterForm">
                        <input type="hidden" name="date" value="{{ $date }}">
                        <div class="form-row">
                            <div class="col-md-4 mb-1">
                                <input type="text" name="transfer_search" id="transferSearch" class="form-control form-control-sm"
                                    placeholder="Buscar remitente/destinatario/empresa" value="{{ $transferSearch }}" autocomplete="off">
                            </div>
                            <div class="col-md-3 mb-1">
                                <input type="date" name="transfer_list_date" id="transferListDate" class="form-control form-control-sm"
                                    value="{{ $transferListDate }}" title="Filtrar por fecha de transferencia">
                            </div>
                            <div class="col-md-4 mb-1">
                                <select name="transfer_status" id="transferStatus" class="form-control form-control-sm">
                                    <option value="all" {{ $transferStatus === 'all' ? 'selected' : '' }}>Todos</option>
                                    <option value="pending" {{ $transferStatus === 'pending' ? 'selected' : '' }}>Pendientes</option>
                                    <option value="sent" {{ $transferStatus === 'sent' ? 'selected' : '' }}>Enviados</option>
                                    <option value="resent" {{ $transferStatus === 'resent' ? 'selected' : '' }}>Reenviados</option>
                                    <option value="cancelled" {{ $transferStatus === 'cancelled' ? 'selected' : '' }}>Cancelados</option>
                                </select>
                            </div>
                            <div class="col-md-1 mb-1">
                                <button class="btn btn-sm btn-primary btn-block" title="Filtrar">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="thead-dark">
                                <tr>
                                    <th>Fecha</th>
                                    <th>Empresa</th>
                                    <th>Sucursal (Remitente)</th>
                                    <th class="text-right">Monto</th>
                                    <th class="text-center">Estado</th>
                                    <th class="text-center">Acc.</th>
                                </tr>
                            </thead>
                            <tbody id="transferTableBody">
                                @forelse($transferList as $transfer)
                                    <tr>
                                        <td>{{ $transfer->transfer_date?->format('d/m/Y') ?? '-' }}</td>
                                        <td><small>{{ $transfer->company?->name ?? '-' }}</small></td>
                                        <td>
                                            <small>
                                                {{ $transfer->branch?->name ?? $transfer->sender_name }}
                                                @if(auth()->user()->isAdmin() && $transfer->branch?->name)
                                                    <span class="text-muted">({{ $transfer->sender_name }})</span>
                                                @endif
                                            </small>
                                        </td>
                                        <td class="text-right font-weight-bold">${{ number_format($transfer->amount, 2) }}</td>
                                        <td class="text-center">
                                            <span class="badge badge-{{ $transfer->status_color }}">{{ $transfer->status_label }}</span>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-xs">
                                                <a href="{{ route('transfers.edit', $transfer) }}" class="btn btn-warning btn-xs"
                                                    title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form method="POST" action="{{ route('transfers.destroy', $transfer) }}"
                                                    class="d-inline" onsubmit="return confirm('¿Eliminar esta transferencia?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="btn btn-danger btn-xs" title="Eliminar">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-3">
                                            Sin transferencias con los filtros aplicados.
                                            @if (($transferTotalCount ?? 0) > 0)
                                                <a href="{{ route('daily-closings.create', ['date' => $date]) }}" class="btn btn-link btn-sm">
                                                    Limpiar filtros
                                                </a>
                                            @endif
                                        </td>
                                    </tr>
                                @endforelse
                                <tr id="transferClientNoResults" style="display: none;">
                                    <td colspan="6" class="text-center text-muted py-3">Sin resultados en esta página.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                @if ($transferList->hasPages())
                    <div class="card-footer py-2">
                        {{ $transferList->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="row mb-2">
        <div class="col-12">
            <h2 class="m-0 text-dark">Cierre de Caja</h2>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-4">
            <form method="GET" class="form-inline">
                <label class="mr-2">Fecha:</label>
                <input type="date" name="date" class="form-control mr-2" value="{{ $date }}">
                <button class="btn btn-primary"><i class="fas fa-sync"></i> Cargar</button>
            </form>
        </div>
    </div>

    <div class="row">
        <!-- Preview -->
        <div class="col-lg-5 order-2 order-lg-2">
            <div class="card card-outline card-dark">
                <div class="card-header bg-dark text-white">
                    <h3 class="card-title">
                        <i class="fas fa-file-alt mr-1"></i>
                        REPORTE {{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}
                    </h3>
                </div>
                <div class="card-body">
                    <div class="closing-section income">
                        <div class="d-flex justify-content-between">
                            <span>TOTAL INGRESOS</span>
                            <strong class="text-success" id="prev_incomes">${{ number_format($totalIncomes, 2) }}</strong>
                        </div>
                    </div>
                    <div class="closing-section expense">
                        <div class="d-flex justify-content-between">
                            <span>(-) GASTOS / DÉBITOS</span>
                            <strong class="text-danger">${{ number_format($totalExpenses, 2) }}</strong>
                        </div>
                    </div>
                    <div class="closing-section total p-2 bg-light rounded mb-2">
                        <div class="d-flex justify-content-between">
                            <strong>VALOR TOTAL</strong>
                            <strong class="text-primary">${{ number_format($valueTotal, 2) }}</strong>
                        </div>
                    </div>
                    <div class="closing-section other">
                        <div class="d-flex justify-content-between">
                            <span>(+) OTROS INGRESOS</span>
                            <strong class="text-success">${{ number_format($otherTotal, 2) }}</strong>
                        </div>
                    </div>
                    <div class="closing-section total p-2 bg-light rounded mb-2">
                        <div class="d-flex justify-content-between">
                            <strong>SUMA TOTAL</strong>
                            <strong class="text-primary" id="prev_sumtotal">${{ number_format($sumTotal, 2) }}</strong>
                        </div>
                    </div>
                    <div class="closing-section">
                        <div class="d-flex justify-content-between">
                            <span>VALOR EXISTENTE</span>
                            <strong id="prev_existing">$0.00</strong>
                        </div>
                    </div>
                    <div class="closing-section">
                        <div class="d-flex justify-content-between">
                            <span>DIFERENCIA</span>
                            <strong id="prev_diff">$0.00</strong>
                        </div>
                    </div>
                    <div class="bg-dark text-warning p-2 rounded mt-2">
                        <div class="d-flex justify-content-between">
                            <strong>TOTAL FINAL</strong>
                            <strong id="prev_total">${{ number_format($sumTotal, 2) }}</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form -->
        <div class="col-lg-7 order-1 order-lg-1">
            <div class="card card-info card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-save mr-1"></i> Guardar Cierre</h3>
                </div>
                <form method="POST" action="{{ route('daily-closings.store') }}">
                    @csrf
                    <div class="card-body">
                        <input type="hidden" name="closing_date" value="{{ $date }}">
                        <input type="hidden" name="total_incomes" value="{{ $totalIncomes }}">
                        <input type="hidden" name="total_expenses" value="{{ $totalExpenses }}">
                        <input type="hidden" name="value_total" value="{{ $valueTotal }}">
                        <input type="hidden" name="other_incomes_total" value="{{ $otherTotal }}">
                        <input type="hidden" name="sum_total" value="{{ $sumTotal }}">
                        <input type="hidden" name="difference" id="h_difference" value="0">
                        <input type="hidden" name="final_total" id="h_final_total" value="{{ $sumTotal }}">

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="font-weight-bold">Total Ingresos (Transferencias)</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend"><span class="input-group-text">$</span></div>
                                        <input type="text" class="form-control text-success font-weight-bold"
                                            value="{{ number_format($totalIncomes, 2) }}" readonly>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="font-weight-bold">Total Gastos / Débitos</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend"><span class="input-group-text">$</span></div>
                                        <input type="text" class="form-control text-danger"
                                            value="{{ number_format($totalExpenses, 2) }}" readonly>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="font-weight-bold">Valor Total</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend"><span class="input-group-text">$</span></div>
                                        <input type="text" class="form-control text-primary font-weight-bold"
                                            value="{{ number_format($valueTotal, 2) }}" readonly>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="font-weight-bold">Otros Ingresos (Fiados)</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend"><span class="input-group-text">$</span></div>
                                        <input type="text" class="form-control text-success"
                                            value="{{ number_format($otherTotal, 2) }}" readonly>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="font-weight-bold">Suma Total</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend"><span class="input-group-text">$</span></div>
                                        <input type="text" class="form-control font-weight-bold text-primary"
                                            value="{{ number_format($sumTotal, 2) }}" readonly>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="font-weight-bold text-dark">Valor Existente (Caja) *</label>
                                    @if (($cashBoxInitialTotal ?? 0) > 0)
                                        <div class="input-group">
                                            <div class="input-group-prepend"><span class="input-group-text">$</span></div>
                                            <input type="number" name="existing_value" id="existing_value"
                                                step="0.01" min="0" class="form-control"
                                                value="{{ number_format($cashBoxInitialTotal, 2, '.', '') }}" readonly>
                                        </div>
                                        <small class="form-text text-success"><i
                                                class="fas fa-check-circle mr-1"></i>Dinero inicial acumulado registrado</small>
                                    @else
                                        <div class="alert alert-warning" role="alert">
                                            <i class="fas fa-exclamation-triangle mr-1"></i> <strong>No registrado</strong>
                                            - Debe registrar el dinero inicial primero en
                                            <a href="{{ route('cash-box-initial.index', ['date' => $date]) }}"
                                                class="alert-link font-weight-bold">Dinero Inicial Caja Chica</a>
                                        </div>
                                        <input type="hidden" name="existing_value" value="0">
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="font-weight-bold">Diferencia</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend"><span class="input-group-text">$</span></div>
                                        <input type="text" id="show_difference" class="form-control" value="0.00"
                                            readonly>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="font-weight-bold">Total Final</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend"><span class="input-group-text">$</span></div>
                                        <input type="text" id="show_final" class="form-control font-weight-bold"
                                            value="{{ number_format($sumTotal, 2) }}" readonly>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-group">
                                    <label>Notas</label>
                                    <textarea name="notes" class="form-control" rows="2">{{ $existing?->notes }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-info btn-lg">
                            <i class="fas fa-save mr-1"></i> Guardar Cierre de Caja
                        </button>
                        <a href="{{ route('daily-closings.index') }}" class="btn btn-secondary ml-2">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sumTotal = {{ $sumTotal }};
            const existingValueInput = document.getElementById('existing_value');
            const transferFilterForm = document.getElementById('transferFilterForm');
            const transferSearch = document.getElementById('transferSearch');
            const transferListDate = document.getElementById('transferListDate');
            const transferStatus = document.getElementById('transferStatus');
            const transferTableBody = document.getElementById('transferTableBody');
            const transferClientNoResults = document.getElementById('transferClientNoResults');
            let transferFilterTimeout;

            function recalculate() {
                const existing = existingValueInput ? (parseFloat(existingValueInput.value) || 0) : 0;
                const diff = sumTotal - existing;
                const final_t = diff;

                document.getElementById('show_difference').value = diff.toFixed(2);
                document.getElementById('show_final').value = final_t.toFixed(2);
                document.getElementById('h_difference').value = diff.toFixed(2);
                document.getElementById('h_final_total').value = final_t.toFixed(2);

                document.getElementById('prev_existing').textContent = '$' + existing.toFixed(2);
                document.getElementById('prev_diff').textContent = '$' + Math.abs(diff).toFixed(2);
                document.getElementById('prev_total').textContent = '$' + final_t.toFixed(2);
            }

            if (existingValueInput) {
                existingValueInput.addEventListener('input', recalculate);
            }
            recalculate();

            if (transferFilterForm) {
                const submitTransferFilters = function() {
                    transferFilterForm.submit();
                };

                if (transferSearch) {
                    transferSearch.addEventListener('input', function() {
                        clearTimeout(transferFilterTimeout);
                        transferFilterTimeout = setTimeout(function() {
                            if (!transferTableBody) {
                                return;
                            }

                            const q = transferSearch.value.trim().toLowerCase();
                            let visibleRows = 0;
                            const rows = transferTableBody.querySelectorAll('tr');

                            rows.forEach(function(row) {
                                if (row.id === 'transferClientNoResults') {
                                    return;
                                }

                                const rowText = row.textContent.toLowerCase();
                                const isMatch = q === '' || rowText.includes(q);
                                row.style.display = isMatch ? '' : 'none';
                                if (isMatch) {
                                    visibleRows++;
                                }
                            });

                            if (transferClientNoResults) {
                                transferClientNoResults.style.display = visibleRows === 0 ? '' : 'none';
                            }
                        }, 120);
                    });

                    // Enter aplica el filtro del servidor (toda la data), escribir filtra en esta página.
                    transferSearch.addEventListener('keydown', function(event) {
                        if (event.key === 'Enter') {
                            event.preventDefault();
                            submitTransferFilters();
                        }
                    });
                }

                [transferListDate, transferStatus].forEach(function(field) {
                    if (field) {
                        field.addEventListener('change', submitTransferFilters);
                    }
                });

                if (transferSearch && transferSearch.value.trim() !== '') {
                    transferSearch.dispatchEvent(new Event('input'));
                }
            }

        });
    </script>
@endpush
