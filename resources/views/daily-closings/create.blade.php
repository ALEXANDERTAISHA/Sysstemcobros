@extends('layouts.app')
@section('title', 'Nuevo Cierre')
@section('page-title', 'Cierre de Caja')
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
            border-color: #17a2b8;
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
        <div class="col-lg-5">
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
                            <strong class="text-info">${{ number_format($otherTotal, 2) }}</strong>
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
        <div class="col-lg-7">
            <div class="card card-success card-outline">
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
                                        <input type="text" class="form-control text-info"
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
                                    @if ($cashBoxInitial)
                                        <div class="input-group">
                                            <div class="input-group-prepend"><span class="input-group-text">$</span></div>
                                            <input type="number" name="existing_value" id="existing_value"
                                                step="0.01" min="0" class="form-control"
                                                value="{{ $cashBoxInitial->initial_amount }}" readonly>
                                        </div>
                                        <small class="form-text text-success"><i
                                                class="fas fa-check-circle mr-1"></i>Dinero inicial registrado</small>
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
                        <button type="submit" class="btn btn-success btn-lg">
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
        const sumTotal = {{ $sumTotal }};

        function recalculate() {
            const existing = parseFloat(document.getElementById('existing_value').value) || 0;
            const diff = sumTotal - existing;
            const final_t = diff;

            document.getElementById('show_difference').value = diff.toFixed(2);
            document.getElementById('show_final').value = final_t.toFixed(2);
            document.getElementById('h_difference').value = diff.toFixed(2);
            document.getElementById('h_final_total').value = final_t.toFixed(2);

            // Update preview
            document.getElementById('prev_existing').textContent = '$' + existing.toFixed(2);
            document.getElementById('prev_diff').textContent = '$' + Math.abs(diff).toFixed(2);
            document.getElementById('prev_total').textContent = '$' + final_t.toFixed(2);
        }

        document.getElementById('existing_value').addEventListener('input', recalculate);
        recalculate();
    </script>
@endpush
