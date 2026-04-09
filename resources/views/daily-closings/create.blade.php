@extends('layouts.app')
@section('title', 'Nuevo Cierre')
@section('page-title', 'Giros/Transferencias')
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
                    <h3 class="card-title"><i class="fas fa-plus-circle mr-1"></i> Datos del Giro</h3>
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
                                    <label>Fecha del Giro *</label>
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
                                    <select name="sender_name"
                                        class="form-control @error('sender_name') is-invalid @enderror" required>
                                        <option value="">Seleccionar remitente...</option>
                                        @foreach ($users as $user)
                                            <option value="{{ $user->name }}"
                                                {{ old('sender_name', auth()->user()->name ?? '') === $user->name ? 'selected' : '' }}>
                                                {{ $user->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="form-text text-muted">
                                        Recomendado: {{ auth()->user()->name ?? 'Usuario actual' }}
                                    </small>
                                    @error('sender_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="d-flex justify-content-between align-items-center">
                                        <span>Nombre del Destinatario *</span>
                                        <button type="button" class="btn btn-xs btn-outline-success" data-toggle="modal"
                                            data-target="#newClientModalClosing">
                                            <i class="fas fa-user-plus mr-1"></i> Nuevo cliente
                                        </button>
                                    </label>
                                    <select id="closing_receiver_name_select"
                                        class="form-control @error('receiver_name') is-invalid @enderror" required>
                                        <option value="">Seleccionar destinatario...</option>
                                        @foreach ($clients as $client)
                                            <option value="{{ $client->name }}"
                                                {{ old('receiver_name') === $client->name ? 'selected' : '' }}>
                                                {{ $client->name }}{{ $client->phone ? ' - ' . $client->phone : '' }}
                                            </option>
                                        @endforeach
                                        <option value="__manual__"
                                            {{ old('receiver_name') && !$clients->pluck('name')->contains(old('receiver_name')) ? 'selected' : '' }}>
                                            Otro (escribir manualmente)
                                        </option>
                                    </select>
                                    <input type="hidden" id="closing_receiver_name" name="receiver_name"
                                        value="{{ old('receiver_name') }}" required>
                                    @error('receiver_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-12">
                                <div class="form-group" id="closing_receiver_name_manual_wrapper" style="display:none;">
                                    <label>Destinatario manual *</label>
                                    <input type="text" id="closing_receiver_name_manual" class="form-control"
                                        placeholder="Escribe el nombre del destinatario">
                                    <small class="form-text text-muted">Usa esta opción solo si el destinatario no está en
                                        la lista.</small>
                                </div>
                            </div>

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

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Código de Transacción</label>
                                    <input type="text" name="transaction_code" class="form-control"
                                        value="{{ old('transaction_code') }}" placeholder="Número de referencia">
                                </div>
                            </div>

                            <div class="col-md-12">
                                <div class="form-group mb-0">
                                    <label>Notas</label>
                                    <input type="text" name="notes" class="form-control" value="{{ old('notes') }}">
                                </div>
                            </div>
                        </div>
                    </div>

                    <input type="hidden" name="status" value="sent">

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-1"></i> Guardar Giro
                        </button>
                        <a href="{{ route('transfers.index') }}" class="btn btn-secondary ml-2">Ver Giros</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card card-outline card-primary h-100">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-paper-plane mr-1"></i> Lista de Giros</h3>
                    <div class="card-tools">
                        <span class="badge badge-secondary mr-1">Total sistema: {{ $transferTotalCount }}</span>
                        @if ($transferPendingCount > 0)
                            <span class="badge badge-warning">
                                <i class="fas fa-clock mr-1"></i>{{ $transferPendingCount }} pendiente(s)
                            </span>
                        @endif
                    </div>
                </div>
                <div class="card-body p-2">
                    <form method="GET" action="{{ route('daily-closings.create') }}" class="mb-2">
                        <input type="hidden" name="date" value="{{ $date }}">
                        <div class="form-row">
                            <div class="col-md-4 mb-1">
                                <input type="text" name="transfer_search" class="form-control form-control-sm"
                                    placeholder="Buscar remitente/destinatario" value="{{ $transferSearch }}">
                            </div>
                            <div class="col-md-3 mb-1">
                                <input type="date" name="transfer_list_date" class="form-control form-control-sm"
                                    value="{{ $transferListDate }}" title="Filtrar por fecha de giro">
                            </div>
                            <div class="col-md-2 mb-1">
                                <select name="transfer_company_id" class="form-control form-control-sm">
                                    <option value="">Empresa</option>
                                    @foreach ($companies as $c)
                                        <option value="{{ $c->id }}"
                                            {{ (string) $transferCompany === (string) $c->id ? 'selected' : '' }}>
                                            {{ $c->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2 mb-1">
                                <select name="transfer_status" class="form-control form-control-sm">
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
                                    <th>Remitente</th>
                                    <th>Destinatario</th>
                                    <th class="text-right">Monto</th>
                                    <th class="text-center">Estado</th>
                                    <th class="text-center">Acc.</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($transferList as $transfer)
                                    <tr>
                                        <td>{{ $transfer->transfer_date?->format('d/m/Y') ?? '-' }}</td>
                                        <td><small>{{ $transfer->company?->name ?? '-' }}</small></td>
                                        <td><small>{{ $transfer->sender_name }}</small></td>
                                        <td><small>{{ $transfer->receiver_name }}</small></td>
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
                                                    class="d-inline" onsubmit="return confirm('¿Eliminar este giro?')">
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
                                        <td colspan="7" class="text-center text-muted py-3">
                                            Sin giros con los filtros aplicados.
                                            @if (($transferTotalCount ?? 0) > 0)
                                                <a href="{{ route('daily-closings.create', ['date' => $date]) }}" class="btn btn-link btn-sm">
                                                    Limpiar filtros
                                                </a>
                                            @endif
                                        </td>
                                    </tr>
                                @endforelse
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
                                    <label class="font-weight-bold">Total Ingresos (Giros)</label>
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

    <div class="modal fade" id="newClientModalClosing" tabindex="-1" aria-labelledby="newClientModalClosingLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-success">
                    <h5 class="modal-title" id="newClientModalClosingLabel">
                        <i class="fas fa-user-plus mr-1"></i> Nuevo cliente
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="inline_new_client_form_closing">
                    <div class="modal-body">
                        <div id="inline_client_alert_closing" class="alert alert-danger d-none py-2 mb-3"></div>

                        <div class="form-group">
                            <label>Nombre completo *</label>
                            <input type="text" class="form-control" id="inline_client_name_closing" name="name" required>
                        </div>

                        <div class="form-group">
                            <label>Teléfono</label>
                            <input type="text" class="form-control" id="inline_client_phone_closing" name="phone"
                                placeholder="0999999999">
                        </div>

                        <div class="form-group mb-0">
                            <label>WhatsApp</label>
                            <input type="text" class="form-control" id="inline_client_whatsapp_closing" name="whatsapp"
                                placeholder="+593999999999">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success" id="inline_client_save_btn_closing">
                            <i class="fas fa-save mr-1"></i> Guardar cliente
                        </button>
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

            const receiverSelect = document.getElementById('closing_receiver_name_select');
            const receiverInput = document.getElementById('closing_receiver_name');
            const manualWrapper = document.getElementById('closing_receiver_name_manual_wrapper');
            const manualInput = document.getElementById('closing_receiver_name_manual');
            const transferForm = receiverSelect ? receiverSelect.closest('form') : null;

            const inlineClientForm = document.getElementById('inline_new_client_form_closing');
            const inlineClientAlert = document.getElementById('inline_client_alert_closing');
            const inlineClientSaveBtn = document.getElementById('inline_client_save_btn_closing');
            const inlineClientName = document.getElementById('inline_client_name_closing');
            const inlineClientPhone = document.getElementById('inline_client_phone_closing');
            const inlineClientWhatsapp = document.getElementById('inline_client_whatsapp_closing');
            const newClientModal = document.getElementById('newClientModalClosing');

            function syncReceiverValue() {
                if (!receiverSelect || !receiverInput || !manualWrapper || !manualInput) {
                    return;
                }

                if (receiverSelect.value === '__manual__') {
                    manualWrapper.style.display = 'block';
                    receiverInput.value = manualInput.value.trim();
                    return;
                }

                manualWrapper.style.display = 'none';
                manualInput.value = '';
                receiverInput.value = receiverSelect.value;
            }

            if (receiverSelect && receiverInput && manualWrapper && manualInput && transferForm) {
                receiverSelect.addEventListener('change', function() {
                    syncReceiverValue();
                    if (receiverSelect.value === '__manual__') {
                        manualInput.focus();
                    }
                });

                manualInput.addEventListener('input', function() {
                    if (receiverSelect.value !== '__manual__') {
                        return;
                    }
                    receiverInput.value = manualInput.value.trim();
                });

                transferForm.addEventListener('submit', function(event) {
                    syncReceiverValue();
                    if (!receiverInput.value.trim()) {
                        event.preventDefault();
                        if (receiverSelect.value === '__manual__') {
                            manualInput.focus();
                        } else {
                            receiverSelect.focus();
                        }
                    }
                });

                if (receiverSelect.value === '__manual__') {
                    manualInput.value = receiverInput.value;
                }

                syncReceiverValue();
            }

            if (!inlineClientForm || !inlineClientAlert || !inlineClientSaveBtn || !inlineClientName || !newClientModal ||
                !receiverSelect) {
                return;
            }

            function setInlineAlert(message) {
                inlineClientAlert.textContent = message;
                inlineClientAlert.classList.remove('d-none');
            }

            function clearInlineAlert() {
                inlineClientAlert.textContent = '';
                inlineClientAlert.classList.add('d-none');
            }

            function appendClientOption(client) {
                const option = document.createElement('option');
                option.value = client.name;
                option.textContent = client.phone ? (client.name + ' - ' + client.phone) : client.name;

                const manualOption = receiverSelect.querySelector('option[value="__manual__"]');
                if (manualOption) {
                    receiverSelect.insertBefore(option, manualOption);
                } else {
                    receiverSelect.appendChild(option);
                }
            }

            $(newClientModal).on('hidden.bs.modal', function() {
                inlineClientForm.reset();
                clearInlineAlert();
                inlineClientSaveBtn.disabled = false;
                inlineClientSaveBtn.innerHTML = '<i class="fas fa-save mr-1"></i> Guardar cliente';
            });

            inlineClientForm.addEventListener('submit', async function(event) {
                event.preventDefault();
                clearInlineAlert();

                const payload = {
                    name: inlineClientName.value.trim(),
                    phone: inlineClientPhone ? inlineClientPhone.value.trim() : '',
                    whatsapp: inlineClientWhatsapp ? inlineClientWhatsapp.value.trim() : '',
                    is_active: 1,
                };

                if (!payload.name) {
                    setInlineAlert('El nombre del cliente es obligatorio.');
                    inlineClientName.focus();
                    return;
                }

                try {
                    inlineClientSaveBtn.disabled = true;
                    inlineClientSaveBtn.innerHTML =
                        '<i class="fas fa-spinner fa-spin mr-1"></i> Guardando...';

                    const response = await fetch('{{ route('clients.store') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                .getAttribute('content'),
                        },
                        body: JSON.stringify(payload),
                    });

                    const result = await response.json();

                    if (!response.ok) {
                        if (result.errors) {
                            const firstError = Object.values(result.errors)[0];
                            setInlineAlert(Array.isArray(firstError) ? firstError[0] :
                                'No se pudo registrar el cliente.');
                        } else {
                            setInlineAlert(result.message || 'No se pudo registrar el cliente.');
                        }
                        return;
                    }

                    appendClientOption(result.client);
                    receiverSelect.value = result.client.name;
                    syncReceiverValue();

                    $(newClientModal).modal('hide');
                } catch (error) {
                    setInlineAlert('Error de conexión. Intenta nuevamente.');
                } finally {
                    inlineClientSaveBtn.disabled = false;
                    inlineClientSaveBtn.innerHTML = '<i class="fas fa-save mr-1"></i> Guardar cliente';
                }
            });
        });
    </script>
@endpush
