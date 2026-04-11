@extends('layouts.app')
@section('title', 'Otros Ingresos')
@section('page-title', 'Otros Ingresos del Día')
@section('breadcrumb')<li class="breadcrumb-item active">Otros Ingresos</li>@endsection

@section('content')
    <div class="row mb-3">
        <div class="col-12">
            <form method="GET" class="w-100" id="other_income_filters_form">
                <div class="form-row align-items-end">
                    <div class="col-md-3 col-sm-6 mb-2 mb-md-0">
                        <label class="mb-1">Fecha:</label>
                        <input type="date" name="date" class="form-control" value="{{ $date }}">
                    </div>
                    <div class="col-md-4 col-sm-6 mb-2 mb-md-0">
                        <label class="mb-1">Cliente Fiado (opcional)</label>
                        <input type="text" name="client_search" id="client_search_input" class="form-control"
                            placeholder="Escribe para buscar cliente..."
                            value="{{ $clientSearch ?? '' }}" autocomplete="off">
                        <small class="form-text text-muted">Busca desde 2 letras. Presiona Enter para buscar al instante.</small>
                    </div>
                    @if(auth()->user()->isAdmin())
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
                            {{ empty($clientSearch) || (($clientDebtBreakdown['total'] ?? 0) <= 0) ? 'disabled' : '' }}>
                            <i class="fas fa-coins mr-1"></i> Cobrar Total Cliente
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
                @if(auth()->user()->isAdmin())
                    <input type="hidden" name="branch_id" id="collect_branch_id" value="{{ $branchId }}">
                @endif
            </form>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-4">
            <div class="info-box bg-danger">
                <span class="info-box-icon"><i class="fas fa-exclamation-circle"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Deuda Activa</span>
                    <span class="info-box-number">${{ number_format($debtTotals['active'], 2) }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="info-box bg-info">
                <span class="info-box-icon"><i class="fas fa-hourglass-half"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Pago Parcial</span>
                    <span class="info-box-number">${{ number_format($debtTotals['partial'], 2) }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="info-box bg-warning">
                <span class="info-box-icon"><i class="fas fa-dollar-sign"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Total Pendiente</span>
                    <span class="info-box-number">${{ number_format($debtTotals['pending'], 2) }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card card-outline card-warning">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-hand-holding-usd mr-1"></i> Seguimiento de Débitos Pendientes</h3>
                    <div class="card-tools d-flex align-items-center">
                        <span class="badge badge-warning mr-2">Total por cobrar: ${{ number_format($pendingDebtTotal, 2) }}</span>
                        <form action="{{ route('other-incomes.send-overdue-reminders') }}" method="POST" class="mb-0">
                            @csrf
                            @if(auth()->user()->isAdmin())
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
                                @if(auth()->user()->isAdmin())
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
                                <tr class="filterable-pending-row{{ $isOverdue ? ' table-danger' : '' }}">
                                    <td>{{ $debt->granted_date?->format('d/m/Y') ?? '-' }}</td>
                                    @if(auth()->user()->isAdmin())
                                        <td>{{ $debt->branch?->name ?? 'Sin sucursal' }}</td>
                                    @endif
                                    <td>
                                        <a href="{{ route('clients.show', $debt->client) }}">{{ $debt->client->name }}</a>
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
                                        <button class="btn btn-xs btn-success"
                                            onclick="openCollectModal({{ $debt->id }}, '{{ addslashes($debt->client->name) }}', '{{ addslashes($debt->concept) }}', {{ $debt->balance }})">
                                            <i class="fas fa-dollar-sign mr-1"></i>Cobrar
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ auth()->user()->isAdmin() ? '9' : '8' }}" class="text-center text-muted py-4">Sin débitos pendientes para seguimiento</td>
                                </tr>
                            @endforelse
                            <tr id="pending_debts_no_results" style="display: none;">
                                <td colspan="{{ auth()->user()->isAdmin() ? '9' : '8' }}" class="text-center text-muted py-4">Sin resultados en débitos pendientes.</td>
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
                                @if(auth()->user()->isAdmin())
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
                                    @if(auth()->user()->isAdmin())
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
                                    <td colspan="{{ auth()->user()->isAdmin() ? '10' : '9' }}" class="text-center text-muted py-4">Sin ingresos para esta fecha</td>
                                </tr>
                            @endforelse
                            <tr id="incomes_no_results" style="display: none;">
                                <td colspan="{{ auth()->user()->isAdmin() ? '10' : '9' }}" class="text-center text-muted py-4">Sin resultados en ingresos del día.</td>
                            </tr>
                        </tbody>
                        @if ($incomes->count() > 0)
                            <tfoot>
                                <tr class="table-info">
                                    <td colspan="{{ auth()->user()->isAdmin() ? '9' : '8' }}"><strong>TOTAL OTROS INGRESOS</strong></td>
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
                <div class="modal-header bg-success">
                    <h5 class="modal-title">Registrar Cobro de Débito</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <form method="POST" action="{{ route('other-incomes.collect-debit') }}">
                    @csrf
                    <input type="hidden" name="credit_id" id="collect_credit_id">
                    <div class="modal-body">
                        <div class="alert alert-light border">
                            <strong id="collect_client_name"></strong><br>
                            <small class="text-muted" id="collect_credit_concept"></small>
                        </div>
                        <div class="form-group">
                            <label>Fecha de cobro *</label>
                            <input type="date" name="payment_date" class="form-control" value="{{ $date }}" required>
                        </div>
                        <div class="form-group">
                            <label>Monto cobrado ($) *</label>
                            <input type="number" name="amount" id="collect_amount" step="0.01" min="0.01"
                                class="form-control" required>
                            <small class="text-muted">Saldo pendiente: <span id="collect_balance_label"></span></small>
                        </div>
                        <div class="form-group mb-0">
                            <label>Notas</label>
                            <input type="text" name="notes" class="form-control"
                                placeholder="Ej: pago parcial en caja">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success"><i class="fas fa-save mr-1"></i> Registrar cobro</button>
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
            const dateInput = filterForm ? filterForm.querySelector('input[name="date"]') : null;
            const branchInput = document.getElementById('branch_id_filter');
            const collectDate = document.getElementById('collect_date');
            const collectClientSearch = document.getElementById('collect_client_search');
            const collectBranchId = document.getElementById('collect_branch_id');
            const pendingRows = Array.from(document.querySelectorAll('.filterable-pending-row'));
            const incomeRows = Array.from(document.querySelectorAll('.filterable-income-row'));
            const pendingNoResults = document.getElementById('pending_debts_no_results');
            const incomesNoResults = document.getElementById('incomes_no_results');

            if (!filterForm || !clientSearchInput) {
                return;
            }

            let debounceTimer;
            let lastSubmittedSignature = [
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

            function applyInstantTableFilter() {
                const query = clientSearchInput.value.trim().toLowerCase();

                let visiblePending = 0;
                pendingRows.forEach(function(row) {
                    const matched = query === '' || row.textContent.toLowerCase().includes(query);
                    row.style.display = matched ? '' : 'none';
                    if (matched) {
                        visiblePending++;
                    }
                });
                if (pendingNoResults) {
                    pendingNoResults.style.display = pendingRows.length > 0 && visiblePending === 0 ? '' : 'none';
                }

                let visibleIncomes = 0;
                incomeRows.forEach(function(row) {
                    const matched = query === '' || row.textContent.toLowerCase().includes(query);
                    row.style.display = matched ? '' : 'none';
                    if (matched) {
                        visibleIncomes++;
                    }
                });
                if (incomesNoResults) {
                    incomesNoResults.style.display = incomeRows.length > 0 && visibleIncomes === 0 ? '' : 'none';
                }
            }

            function submitIfChanged() {
                const signature = getCurrentSignature();
                if (signature === lastSubmittedSignature) {
                    return;
                }
                lastSubmittedSignature = signature;
                filterForm.submit();
            }

            clientSearchInput.addEventListener('input', function() {
                applyInstantTableFilter();
                syncCollectForm();

                window.clearTimeout(debounceTimer);
                debounceTimer = window.setTimeout(function() {
                    const search = clientSearchInput.value.trim();
                    if (search.length === 0 || search.length >= 2) {
                        submitIfChanged();
                    }
                }, 700);
            });

            clientSearchInput.addEventListener('keydown', function(event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    window.clearTimeout(debounceTimer);
                    syncCollectForm();
                    submitIfChanged();
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
            if (dateInput) {
                dateInput.addEventListener('change', function() {
                    syncCollectForm();
                    submitIfChanged();
                });
            }
            if (branchInput) {
                branchInput.addEventListener('change', function() {
                    syncCollectForm();
                    submitIfChanged();
                });
            }

            syncCollectForm();
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
            document.getElementById('collect_credit_id').value = creditId;
            document.getElementById('collect_client_name').textContent = clientName;
            document.getElementById('collect_credit_concept').textContent = concept;
            document.getElementById('collect_amount').value = Number(balance).toFixed(2);
            document.getElementById('collect_amount').max = Number(balance).toFixed(2);
            document.getElementById('collect_balance_label').textContent = '$' + Number(balance).toFixed(2);
            $('#collectDebitModal').modal('show');
        }
    </script>
@endpush
