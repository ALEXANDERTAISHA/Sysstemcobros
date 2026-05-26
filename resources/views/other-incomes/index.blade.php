@extends('layouts.app')
@section('title', 'Otros Ingresos')
@section('page-title', 'Otros Ingresos del Día')
@section('breadcrumb')<li class="breadcrumb-item active">Otros Ingresos</li>@endsection

@section('content')
    <div class="row mb-3">
        <div class="col-12">
            <form method="GET" class="w-100" id="other_income_filters_form">
                <div class="form-row align-items-end">
                    <div class="col-md-4 col-sm-6 mb-2 mb-md-0">
                        <label class="mb-1">Cliente/Empresa (opcional)</label>
                        <div class="input-group">
                            <input type="text" name="client_search" id="client_search_input" class="form-control"
                                placeholder="Escribe para buscar cliente o empresa..."
                                value="{{ $clientSearch ?? '' }}" autocomplete="off">
                            <div class="input-group-append">
                                <button type="button" id="client_search_clear" class="btn btn-outline-secondary"
                                    title="Limpiar busqueda" aria-label="Limpiar busqueda"
                                    style="{{ empty($clientSearch) ? 'display:none;' : '' }}">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                        <small id="client_search_status" class="text-muted d-none">Buscando...</small>
                    </div>
                    @if(auth()->user()->isSuperAdmin())
                        <div class="col-md-3 col-sm-6 mb-2 mb-md-0">
                            <label class="mb-1">Sucursal</label>
                            <select name="branch_id" class="form-control" id="branch_id_filter">
                                <option value="">Todas las sucursales</option>
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}"
                                        {{ (string) $branchId === (string) $branch->id ? 'selected' : '' }}>
                                        {{ $branch->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <div class="col-auto">
                        <button class="btn btn-primary" type="submit">
                            <i class="fas fa-search mr-1"></i> Buscar
                        </button>
                    </div>
                    <div class="col-auto">
                        <button type="submit" form="collect_client_debts_form" class="btn btn-success"
                            onclick="return confirm('¿Cobrar TODO lo pendiente y vencido del cliente buscado y pasarlo a Ingresos del día?')"
                            {{ empty($clientSearch) || mb_strlen($clientSearch) < 2 || (($clientDebtBreakdown['total'] ?? 0) <= 0) ? 'disabled' : '' }}>
                            <i class="fas fa-coins mr-1"></i> Cobrar Total Cliente
                        </button>
                    </div>
                    <div class="col-auto">
                        <button type="button" class="btn btn-primary" id="collect_client_debts_zelle_btn"
                            onclick="collectClientDebtsViaZelle(this)"
                            {{ empty($clientSearch) || mb_strlen($clientSearch) < 2 || (($clientDebtBreakdown['total'] ?? 0) <= 0) ? 'disabled' : '' }}>
                            <i class="fas fa-university mr-1"></i> Cobrar Total vía ZELLE
                        </button>
                    </div>

                    @if(!empty($clientSearch))
                        <div class="col-12 mt-2">
                            <span class="badge badge-danger mr-2">Vencidos: ${{ number_format($clientDebtBreakdown['overdue'] ?? 0, 2) }}</span>
                            <span class="badge badge-warning mr-2">Pendientes: ${{ number_format($clientDebtBreakdown['pending'] ?? 0, 2) }}</span>
                            <span class="badge badge-success">Total a cobrar: ${{ number_format($clientDebtBreakdown['total'] ?? 0, 2) }}</span>
                        </div>
                    @endif
                </div>
            </form>

            <form method="POST" action="{{ route('other-incomes.collect-client-debts') }}" id="collect_client_debts_form" class="d-none">
                @csrf
                <input type="hidden" name="date" id="collect_date" value="{{ $date }}">
                <input type="hidden" name="client_search" id="collect_client_search" value="{{ $clientSearch ?? '' }}">
                @if(auth()->user()->isSuperAdmin())
                    <input type="hidden" name="branch_id" id="collect_branch_id" value="{{ $branchId }}">
                @endif
            </form>
        </div>
    </div>

        <div class="row mb-3">
                <div class="col-md-3">
                        <div class="info-box bg-danger">
                                <span class="info-box-icon"><i class="fas fa-exclamation-circle"></i></span>
                                <div class="info-box-content">
                                        <span class="info-box-text">Deuda Activa</span>
                                        <span class="info-box-number">${{ number_format($debtTotals['active'], 2) }}</span>
                                </div>
                        </div>
                </div>
                <div class="col-md-3">
                        <div class="info-box bg-info">
                                <span class="info-box-icon"><i class="fas fa-hourglass-half"></i></span>
                                <div class="info-box-content">
                                        <span class="info-box-text">Pago Parcial</span>
                                        <span class="info-box-number">${{ number_format($debtTotals['partial'], 2) }}</span>
                                </div>
                        </div>
                </div>
                <div class="col-md-3">
                        <div class="info-box bg-warning">
                                <span class="info-box-icon"><i class="fas fa-dollar-sign"></i></span>
                                <div class="info-box-content">
                                        <span class="info-box-text">Total Pendiente</span>
                                        <span class="info-box-number">${{ number_format($debtTotals['pending'], 2) }}</span>
                                </div>
                        </div>
                </div>
                <div class="col-md-3">
                        <div class="info-box bg-primary info-box-clickable" id="special-transfers-card" style="cursor:pointer;">
                                <span class="info-box-icon"><i class="fas fa-exchange-alt"></i></span>
                                <div class="info-box-content">
                                        <span class="info-box-text">Transferencias Especiales</span>
                                        <span class="info-box-number">${{ number_format($specialTransfersTotal ?? 0, 2) }}</span>
                                </div>
                        </div>
                </div>
        </div>

        <!-- Modal Transferencias Especiales -->
        <div class="modal fade" id="specialTransfersModal" tabindex="-1" role="dialog" aria-labelledby="specialTransfersModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="specialTransfersModalLabel">Transferencias Especiales</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form method="GET" id="specialTransfersFilterForm" class="mb-3" autocomplete="off">
                            <div class="form-row align-items-end">
                                <div class="col-md-3 mb-2">
                                    <label>Fecha inicio</label>
                                    <input type="date" name="date_start" class="form-control" value="{{ request('date_start', $dateStart ?? '') }}">
                                </div>
                                <div class="col-md-3 mb-2">
                                    <label>Fecha fin</label>
                                    <input type="date" name="date_end" class="form-control" value="{{ request('date_end', $dateEnd ?? '') }}">
                                </div>
                                <div class="col-md-4 mb-2">
                                    <label>Buscar por nombre</label>
                                    <input type="text" name="special_search" class="form-control" value="{{ request('special_search', $specialSearch ?? '') }}" placeholder="Cliente, empresa o descripción">
                                </div>
                                <div class="col-auto mb-2 d-flex align-items-center">
                                    <button type="submit" class="btn btn-primary mr-2"><i class="fas fa-search mr-1"></i> Filtrar</button>
                                    <div id="specialTransfersTotalCard" class="card bg-info text-white mb-0" style="min-width: 140px; min-height: 38px; display: flex; align-items: center; justify-content: center; font-size: 1.1em;">
                                        <span>Total: $<span id="specialTransfersTotalValue">{{ number_format($specialTransfersTotal ?? 0, 2) }}</span></span>
                                    </div>
                                </div>
                            </div>
                        </form>
                        <div id="specialTransfersTableWrapper">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover table-sm mb-0">
                                    <thead class="thead-dark">
                                        <tr>
                                            <th>Fecha</th>
                                            <th>Empresa</th>
                                            <th>Cliente</th>
                                            <th>Descripción</th>
                                            <th class="text-right">Monto</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($specialTransfers as $st)
                                        <tr>
                                            <td>{{ $st->income_date->format('d/m/Y') }}</td>
                                            <td>{{ $st->credit?->company?->name ?? '-' }}</td>
                                            <td>{{ $st->client?->name ?? '-' }}</td>
                                            <td>{{ $st->description }}</td>
                                            <td class="text-right">${{ number_format($st->amount, 2) }}</td>
                                        </tr>
                                        @empty
                                        <tr><td colspan="5" class="text-center text-muted">Sin resultados.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const card = document.getElementById('special-transfers-card');
        if(card) {
            card.addEventListener('click', function() {
                $('#specialTransfersModal').modal('show');
            });
        }

        // AJAX para filtrar transferencias especiales sin cerrar el modal
        $('#specialTransfersFilterForm').on('submit', function(e) {
            e.preventDefault();
            var $form = $(this);
            var url = window.location.pathname + '/special-transfers';
            var data = $form.serialize();
            // Mostrar loading opcional
            $('#specialTransfersTableWrapper').html('<div class="text-center py-3">Cargando...</div>');
            $.ajax({
                url: url,
                type: 'GET',
                dataType: 'json',
                data: data,
                success: function(response) {
                    $('#specialTransfersTableWrapper').html(response.html);
                    $('#specialTransfersTotalValue').text(response.total);
                },
                error: function() {
                    $('#specialTransfersTableWrapper').html('<div class="text-danger text-center py-3">Error al filtrar. Intente de nuevo.</div>');
                    $('#specialTransfersTotalValue').text('0.00');
                }
            });
        });
    });
</script>
@endpush

    <div class="row">
        <div class="col-12">
            <div class="card card-outline card-warning">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-hand-holding-usd mr-1"></i> Seguimiento de Débitos Pendientes</h3>
                    <div class="card-tools d-flex align-items-center">
                        <span class="badge badge-warning mr-2">Total por cobrar: ${{ number_format($pendingDebtTotal, 2) }}</span>
                        <form action="{{ route('other-incomes.send-overdue-reminders') }}" method="POST" class="mb-0">
                            @csrf
                            @if(auth()->user()->isSuperAdmin())
                                <input type="hidden" name="branch_id" value="{{ $branchId }}">
                            @endif
                            <button type="submit" class="btn btn-sm btn-danger"
                                onclick="return confirm('¿Enviar recordatorio por correo y WhatsApp a todos los deudores vencidos?')"
                                title="Enviar recordatorio a todos los deudores con plazo vencido">
                                <i class="fas fa-bell mr-1"></i> Enviar Recordatorios
                            </button>
                        </form>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0">
                        <thead class="thead-dark">
                            <tr>
                                <th>Fecha</th>
                                @if(auth()->user()->isSuperAdmin())
                                    <th>Sucursal</th>
                                @endif
                                <th>Cliente</th>
                                <th>Empresa</th>
                                <th>Vence</th>
                                <th class="text-center">Días</th>
                                <th class="text-right">Saldo</th>
                                <th class="text-center">Estado</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="pending_debts_tbody">
                            @forelse($pendingDebts as $debt)
                                @php
                                    $isOverdue = $debt->due_date && $debt->due_date->isPast();
                                    $diffDays = $debt->due_date ? (int) now()->startOfDay()->diffInDays($debt->due_date->startOfDay(), false) : null;
                                @endphp
                                <tr class="filterable-pending-row{{ $isOverdue ? ' table-danger' : '' }}"
                                    data-search="{{ trim(collect([$debt->client?->name, $debt->client?->phone, $debt->client?->whatsapp, $debt->client?->email, $debt->client?->address])->filter()->implode(' ')) }}">
                                    <td>{{ $debt->granted_date?->format('d/m/Y') ?? '-' }}</td>
                                    @if(auth()->user()->isSuperAdmin())
                                        <td>{{ $debt->branch?->name ?? 'Sin sucursal' }}</td>
                                    @endif
                                    <td>
                                            @if(auth()->user()->isSuperAdmin())
                                                <a href="{{ route('clients.show', $debt->client) }}">{{ $debt->client->name }}</a>
                                            @else
                                                {{ $debt->client->name }}
                                            @endif
                                    </td>
                                    <td>{{ $debt->company?->name ?? '-' }}</td>
                                    <td>{{ $debt->due_date?->format('d/m/Y') ?? 'Sin fecha' }}</td>
                                    <td class="text-center">
                                        @if($diffDays === null)
                                            <span class="text-muted">—</span>
                                        @elseif($diffDays < 0)
                                            <span class="badge badge-danger">{{ abs($diffDays) }}d vencido</span>
                                        @elseif($diffDays === 0)
                                            <span class="badge badge-warning">Hoy</span>
                                        @else
                                            <span class="badge badge-info">{{ $diffDays }}d</span>
                                        @endif
                                    </td>
                                    <td class="text-right font-weight-bold text-danger">${{ number_format($debt->balance, 2) }}</td>
                                    <td class="text-center">
                                        <span class="badge badge-{{ $isOverdue ? 'danger' : 'warning' }}">
                                            {{ $isOverdue ? 'Vencido' : 'Pendiente' }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-success px-3 py-1 font-weight-bold js-collect-debit"
                                            style="font-size:1em;"
                                            data-credit-id="{{ $debt->id }}"
                                            data-client-name="{{ $debt->client->name }}"
                                            data-concept="{{ $debt->concept }}"
                                            data-balance="{{ number_format($debt->balance, 2, '.', '') }}">
                                            <i class="fas fa-dollar-sign mr-1"></i>Cobrar
                                        </button>
                                        <button type="button" class="btn btn-primary px-3 py-1 font-weight-bold ml-2 js-collect-zelle"
                                            style="font-size:1em;"
                                            data-credit-id="{{ $debt->id }}"
                                            data-client-name="{{ $debt->client->name }}"
                                            data-concept="{{ $debt->concept }}"
                                            data-balance="{{ number_format($debt->balance, 2, '.', '') }}">
                                            <i class="fas fa-university mr-1"></i>Cobrar vía ZELLE
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ auth()->user()->isSuperAdmin() ? '9' : '8' }}" class="text-center text-muted py-4">Sin débitos pendientes para seguimiento</td>
                                </tr>
                            @endforelse
                            <tr id="pending_debts_no_results" style="display: none;">
                                <td colspan="{{ auth()->user()->isSuperAdmin() ? '9' : '8' }}" class="text-center text-muted py-4">Sin resultados en débitos pendientes.</td>
                            </tr>
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>

            <div class="card card-outline card-info mt-3">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-plus-circle mr-1"></i> Ingresos del
                        {{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}</h3>
                    <div class="card-tools">
                        <span class="badge badge-info">Total: ${{ number_format($total, 2) }}</span>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0">
                        <thead class="thead-dark">
                            <tr>
                                <th>Fecha</th>
                                @if(auth()->user()->isSuperAdmin())
                                    <th>Sucursal</th>
                                @endif
                                <th>Cliente Fiado</th>
                                <th>Empresa</th>
                                <th>Descripción</th>
                                <th class="text-right">Monto</th>
                                <th class="text-right">Pagado</th>
                                <th class="text-right">Saldo</th>
                                <th class="text-center">Estado</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="incomes_tbody">
                            @forelse($incomes as $i => $income)
                                @php
                                    $incomeCredit = $income->credit;
                                    $isToday = $income->income_date->isToday();
                                    $creditIsToday = $incomeCredit && $incomeCredit->granted_date->isToday();
                                @endphp
                                <tr class="filterable-income-row{{ $isToday && $creditIsToday ? ' table-warning' : '' }}">
                                    <td>
                                        {{ $income->income_date->format('d/m/Y') }}
                                        @if($isToday && $creditIsToday)
                                            <span class="badge badge-warning ml-1">Hoy</span>
                                        @endif
                                    </td>
                                    @if(auth()->user()->isSuperAdmin())
                                        <td>{{ $income->branch?->name ?? 'Sin sucursal' }}</td>
                                    @endif
                                    <td>{{ $income->client?->name ?? '-' }}</td>
                                    <td>{{ $income->credit?->company?->name ?? '-' }}</td>
                                    <td>{{ $income->description }}</td>
                                    <td class="text-right text-info font-weight-bold">
                                        ${{ number_format($income->amount, 2) }}</td>
                                    <td class="text-right">
                                        {{ $incomeCredit ? '$' . number_format($incomeCredit->paid_amount, 2) : '-' }}
                                    </td>
                                    <td class="text-right font-weight-bold {{ $incomeCredit && $incomeCredit->balance > 0 ? 'text-danger' : 'text-success' }}">
                                        {{ $incomeCredit ? '$' . number_format($incomeCredit->balance, 2) : '-' }}
                                    </td>
                                    <td class="text-center">
                                        @if ($incomeCredit)
                                            <span class="badge badge-{{ $incomeCredit->status === 'paid' ? 'success' : ($incomeCredit->status === 'partial' ? 'info' : 'warning') }}">
                                                {{ $incomeCredit->status === 'paid' ? 'Pagado' : ($incomeCredit->status === 'partial' ? 'Parcial' : 'Activo') }}
                                            </span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <button class="btn btn-xs btn-warning"
                                            onclick="editIncome({{ $income->id }}, '{{ addslashes($income->description) }}', {{ $income->amount }}, {{ $income->client_id ?? 'null' }}, '{{ addslashes($income->notes ?? '') }}')">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form method="POST" action="{{ route('other-incomes.destroy', $income) }}"
                                            class="d-inline" onsubmit="return confirm('¿Eliminar?')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-xs btn-danger"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ auth()->user()->isSuperAdmin() ? '10' : '9' }}" class="text-center text-muted py-4">Sin ingresos para esta fecha</td>
                                </tr>
                            @endforelse
                            <tr id="incomes_no_results" style="display: none;">
                                <td colspan="{{ auth()->user()->isSuperAdmin() ? '10' : '9' }}" class="text-center text-muted py-4">Sin resultados en ingresos del día.</td>
                            </tr>
                        </tbody>
                        @if ($incomes->count() > 0)
                            <tfoot>
                                <tr class="table-info">
                                    <td colspan="{{ auth()->user()->isSuperAdmin() ? '9' : '8' }}"><strong>TOTAL OTROS INGRESOS</strong></td>
                                    <td class="text-right"><strong>${{ number_format($total, 2) }}</strong></td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Edit modal -->
    <div class="modal fade" id="editIncomeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title">Editar Ingreso</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <form id="editIncomeForm" method="POST">
                    @csrf @method('PUT')
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Descripción</label>
                            <input type="text" name="description" id="edit_income_description" class="form-control"
                                required>
                        </div>
                        <div class="form-group">
                            <label>Monto ($)</label>
                            <input type="number" name="amount" id="edit_income_amount" step="0.01" min="0.01"
                                class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Cliente Fiado (opcional)</label>
                            <select name="client_id" id="edit_income_client_id" class="form-control">
                                <option value="">— Sin cliente —</option>
                                @foreach ($clients as $client)
                                    <option value="{{ $client->id }}">{{ $client->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group mb-0">
                            <label>Notas</label>
                            <input type="text" name="notes" id="edit_income_notes" class="form-control">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-warning"><i class="fas fa-save mr-1"></i> Guardar</button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="collectDebitModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success" id="collect_modal_header">
                    <h5 class="modal-title">Registrar Cobro de Débito</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <form method="POST" action="{{ route('other-incomes.collect-debit') }}" id="collect_debit_form">
                    @csrf
                    <input type="hidden" name="credit_id" id="collect_credit_id">
                    <div class="modal-body">
                        <div class="alert alert-light border">
                            <strong id="collect_client_name"></strong><br>
                            <small class="text-muted" id="collect_credit_concept"></small>
                        </div>
                        <div class="form-group">
                            <label>Fecha de cobro *</label>
                            <input type="date" name="payment_date" id="collect_payment_date" class="form-control" value="{{ $date }}" required>
                        </div>
                        <div class="form-group">
                            <label>Monto cobrado ($) *</label>
                            <input type="number" name="amount" id="collect_amount" step="0.01" min="0.01"
                                class="form-control" required>
                            <small class="text-muted">Saldo pendiente: <span id="collect_balance_label"></span></small>
                        </div>
                        <div class="form-group mb-0">
                            <label>Notas</label>
                            <input type="text" name="notes" id="collect_notes" class="form-control"
                                placeholder="Ej: pago parcial en caja">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success" id="collect_submit_btn"><i class="fas fa-save mr-1"></i> Registrar cobro</button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const filterForm = document.getElementById('other_income_filters_form');
            const clientSearchInput = document.getElementById('client_search_input');
            const clientSearchClear = document.getElementById('client_search_clear');
            const clientSearchStatus = document.getElementById('client_search_status');
            const dateInput = filterForm ? filterForm.querySelector('input[name="date"]') : null;
            const branchInput = document.getElementById('branch_id_filter');
            const collectDate = document.getElementById('collect_date');
            const collectClientSearch = document.getElementById('collect_client_search');
            const collectBranchId = document.getElementById('collect_branch_id');
            const pendingRows = Array.from(document.querySelectorAll('.filterable-pending-row'));
            const pendingNoResults = document.getElementById('pending_debts_no_results');

            if (!filterForm || !clientSearchInput) {
                return;
            }

            const SERVER_SEARCH_DELAY = 650;
            const CLEAR_SEARCH_DELAY = 250;
            let localFilterFrame;
            let serverDebounceTimer;
            let lastServerSubmittedSignature = [
                clientSearchInput.value.trim(),
                dateInput ? dateInput.value : '',
                branchInput ? branchInput.value : ''
            ].join('|');

            function getCurrentSignature() {
                return [
                    clientSearchInput.value.trim(),
                    dateInput ? dateInput.value : '',
                    branchInput ? branchInput.value : ''
                ].join('|');
            }

            function setSearchStatus(isSearching) {
                if (clientSearchStatus) {
                    clientSearchStatus.classList.toggle('d-none', !isSearching);
                }
            }

            function updateClearButton() {
                if (clientSearchClear) {
                    clientSearchClear.style.display = clientSearchInput.value.length > 0 ? '' : 'none';
                }
            }

            function applyInstantTableFilter() {
                const query = clientSearchInput.value.trim();

                if (query.length === 0) {
                    // Reset: mostrar todos los débitos pendientes
                    pendingRows.forEach(row => row.style.display = '');
                    if (pendingNoResults) pendingNoResults.style.display = 'none';
                    return;
                }

                // Filtrado local en tabla de DÉBITOS PENDIENTES y EMPRESAS
                let visiblePending = 0;
                pendingRows.forEach(function(row) {
                    const matched = clientSearchMatches(row.dataset.search || row.textContent, query);
                    row.style.display = matched ? '' : 'none';
                    if (matched) visiblePending++;
                });
                if (pendingNoResults) {
                    pendingNoResults.style.display = pendingRows.length > 0 && visiblePending === 0 ? '' : 'none';
                }
            }

            function normalizeClientSearch(value) {
                return (value || '')
                    .toString()
                    .normalize('NFD')
                    .replace(/[\u0300-\u036f]/g, '')
                    .replace(/\s+/g, ' ')
                    .trim()
                    .toLowerCase();
            }

            function clientSearchMatches(searchableText, term) {
                const tokens = normalizeClientSearch(term).split(' ').filter(Boolean);
                if (tokens.length === 0) {
                    return true;
                }

                const normalizedText = normalizeClientSearch(searchableText);
                return tokens.every(function(token) {
                    return normalizedText.includes(token);
                });
            }

            function queueInstantFilter() {
                if (localFilterFrame) {
                    window.cancelAnimationFrame(localFilterFrame);
                }

                localFilterFrame = window.requestAnimationFrame(function() {
                    applyInstantTableFilter();
                    syncCollectForm();
                    updateClearButton();
                });
            }

            function submitToServer(force = false) {
                const signature = getCurrentSignature();
                if (!force && signature === lastServerSubmittedSignature) {
                    setSearchStatus(false);
                    return;
                }
                lastServerSubmittedSignature = signature;
                setSearchStatus(true);
                filterForm.submit();
            }

            function scheduleServerSearch(delay) {
                window.clearTimeout(serverDebounceTimer);
                serverDebounceTimer = window.setTimeout(function() {
                    const search = clientSearchInput.value.trim();
                    if (search.length === 0 || search.length >= 2) {
                        submitToServer();
                    } else {
                        setSearchStatus(false);
                    }
                }, delay);
            }

            clientSearchInput.addEventListener('input', function() {
                const search = clientSearchInput.value.trim();

                queueInstantFilter();

                if (search.length === 1) {
                    window.clearTimeout(serverDebounceTimer);
                    setSearchStatus(false);
                    return;
                }

                setSearchStatus(search.length >= 2);
                scheduleServerSearch(search.length === 0 ? CLEAR_SEARCH_DELAY : SERVER_SEARCH_DELAY);
            });

            // Enter: envío inmediato al servidor para búsqueda completa
            clientSearchInput.addEventListener('keydown', function(event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    if (localFilterFrame) {
                        window.cancelAnimationFrame(localFilterFrame);
                    }
                    window.clearTimeout(serverDebounceTimer);
                    applyInstantTableFilter();
                    syncCollectForm();
                    updateClearButton();
                    submitToServer();
                }
            });

            function syncCollectForm() {
                if (collectDate && dateInput) {
                    collectDate.value = dateInput.value;
                }
                if (collectClientSearch) {
                    collectClientSearch.value = clientSearchInput.value.trim();
                }
                if (collectBranchId && branchInput) {
                    collectBranchId.value = branchInput.value;
                }
            }

            clientSearchInput.addEventListener('change', syncCollectForm);
            if (clientSearchClear) {
                clientSearchClear.addEventListener('click', function() {
                    clientSearchInput.value = '';
                    clientSearchInput.focus();
                    if (localFilterFrame) {
                        window.cancelAnimationFrame(localFilterFrame);
                    }
                    window.clearTimeout(serverDebounceTimer);
                    applyInstantTableFilter();
                    syncCollectForm();
                    updateClearButton();
                    submitToServer(true);
                });
            }
            // Cambios en fecha o sucursal: envío inmediato (sin debounce)
            if (dateInput) {
                dateInput.addEventListener('change', function() {
                    if (localFilterFrame) {
                        window.cancelAnimationFrame(localFilterFrame);
                    }
                    window.clearTimeout(serverDebounceTimer);
                    applyInstantTableFilter();
                    syncCollectForm();
                    updateClearButton();
                    submitToServer();
                });
            }
            if (branchInput) {
                branchInput.addEventListener('change', function() {
                    if (localFilterFrame) {
                        window.cancelAnimationFrame(localFilterFrame);
                    }
                    window.clearTimeout(serverDebounceTimer);
                    applyInstantTableFilter();
                    syncCollectForm();
                    updateClearButton();
                    submitToServer();
                });
            }

            syncCollectForm();
            updateClearButton();
            applyInstantTableFilter();
        });
    </script>
@endpush

@push('scripts')
    <script>
        function editIncome(id, description, amount, clientId, notes) {
            document.getElementById('editIncomeForm').action = '/other-incomes/' + id;
            document.getElementById('edit_income_description').value = description;
            document.getElementById('edit_income_amount').value = amount;
            document.getElementById('edit_income_client_id').value = clientId === null ? '' : String(clientId);
            document.getElementById('edit_income_notes').value = notes;
            $('#editIncomeModal').modal('show');
        }

        function openCollectModal(creditId, clientName, concept, balance) {
            const form = document.getElementById('collect_debit_form');
            const header = document.getElementById('collect_modal_header');
            const submitBtn = document.getElementById('collect_submit_btn');
            const notes = document.getElementById('collect_notes');

            form.action = '{{ route('other-incomes.collect-debit') }}';
            header.classList.remove('bg-primary');
            header.classList.add('bg-success');
            submitBtn.classList.remove('btn-primary');
            submitBtn.classList.add('btn-success');
            header.querySelector('.modal-title').textContent = 'Registrar Cobro de Debito';
            notes.placeholder = 'Ej: pago parcial en caja';
            notes.value = '';

            document.getElementById('collect_credit_id').value = creditId;
            document.getElementById('collect_client_name').textContent = clientName;
            document.getElementById('collect_credit_concept').textContent = concept;
            document.getElementById('collect_amount').value = Number(balance).toFixed(2);
            document.getElementById('collect_amount').max = Number(balance).toFixed(2);
            document.getElementById('collect_balance_label').textContent = '$' + Number(balance).toFixed(2);
            $('#collectDebitModal').modal('show');
        }

        function openCollectZelleModal(creditId, clientName, concept, balance) {
            const form = document.getElementById('collect_debit_form');
            const header = document.getElementById('collect_modal_header');
            const submitBtn = document.getElementById('collect_submit_btn');
            const notes = document.getElementById('collect_notes');

            form.action = '{{ route('other-incomes.collect-debit-zelle') }}';
            header.classList.remove('bg-success');
            header.classList.add('bg-primary');
            submitBtn.classList.remove('btn-success');
            submitBtn.classList.add('btn-primary');
            header.querySelector('.modal-title').textContent = 'Registrar Cobro via ZELLE';
            notes.placeholder = 'Ej: pago parcial via ZELLE';
            notes.value = '';

            document.getElementById('collect_credit_id').value = creditId;
            document.getElementById('collect_client_name').textContent = clientName;
            document.getElementById('collect_credit_concept').textContent = concept;
            document.getElementById('collect_amount').value = Number(balance).toFixed(2);
            document.getElementById('collect_amount').max = Number(balance).toFixed(2);
            document.getElementById('collect_balance_label').textContent = '$' + Number(balance).toFixed(2);
            $('#collectDebitModal').modal('show');
        }

        function readCollectButtonData(button) {
            return {
                creditId: button.dataset.creditId,
                clientName: button.dataset.clientName || '',
                concept: button.dataset.concept || '',
                balance: Number(button.dataset.balance || 0)
            };
        }

        document.addEventListener('click', function(event) {
            const collectButton = event.target.closest('.js-collect-debit');
            if (collectButton) {
                event.preventDefault();
                const data = readCollectButtonData(collectButton);
                openCollectModal(data.creditId, data.clientName, data.concept, data.balance);
                return;
            }

            const zelleButton = event.target.closest('.js-collect-zelle');
            if (zelleButton) {
                event.preventDefault();
                const data = readCollectButtonData(zelleButton);
                openCollectZelleModal(data.creditId, data.clientName, data.concept, data.balance);
            }
        });

        const collectDebitForm = document.getElementById('collect_debit_form');
        if (collectDebitForm) {
            collectDebitForm.addEventListener('submit', function() {
                const amountInput = document.getElementById('collect_amount');
                if (amountInput) {
                    amountInput.value = amountInput.value.replace(',', '.');
                }
            });
        }

        function collectClientDebtsViaZelle(btn) {
            if(btn) btn.disabled = true;
            if(confirm('¿Desea cobrar TODO lo pendiente y vencido del cliente buscado vía ZELLE y pasarlo a Transferencias Especiales?')) {
                // AJAX premium
                $.ajax({
                    url: '{{ route('other-incomes.collect-client-debts-zelle') }}',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        date: document.getElementById('collect_date').value,
                        client_search: document.getElementById('collect_client_search').value,
                        branch_id: document.getElementById('collect_branch_id') ? document.getElementById('collect_branch_id').value : undefined,
                        via_zelle: 1
                    },
                    success: function(response) {
                        // Recargar tablas y totales premium (doble recarga para asegurar sincronía en hostings lentos)
                        function reloadTables() {
                            $.get('{{ route('other-incomes.ajax-refresh-tables') }}', {
                                date: document.getElementById('collect_date').value,
                                client_search: document.getElementById('collect_client_search').value,
                                branch_id: document.getElementById('collect_branch_id') ? document.getElementById('collect_branch_id').value : undefined
                            }, function(data) {
                                $('#pending_debts_tbody').replaceWith(data.pendingDebitsHtml);
                                $('#specialTransfersTableWrapper').html(data.specialTransfersHtml);
                                $('#specialTransfersTotalValue').text(data.specialTransfersTotal);
                                $(".badge-warning:contains('Total por cobrar')").text('Total por cobrar: $' + data.pendingDebtTotal);
                            });
                        }
                        reloadTables();
                        setTimeout(reloadTables, 600); // segunda recarga tras 600ms
                        // Mensaje premium
                        Swal.fire({
                            icon: 'success',
                            title: 'Cobro vía ZELLE realizado',
                            text: 'Los débitos fueron movidos a Transferencias Especiales y registrados en caja.',
                            timer: 2500,
                            showConfirmButton: false
                        });
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: xhr.responseJSON?.message || 'Ocurrió un error al cobrar vía ZELLE.'
                        });
                    },
                    complete: function() {
                        if(btn) btn.disabled = false;
                    }
                });
            } else {
                if(btn) btn.disabled = false;
            }
        }
    </script>
@endpush
