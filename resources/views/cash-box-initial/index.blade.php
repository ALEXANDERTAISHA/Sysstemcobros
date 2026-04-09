@extends('layouts.app')
@section('title', 'Dinero Inicial Caja Chica')
@section('page-title', 'Dinero Inicial Caja Chica')
@section('breadcrumb')<li class="breadcrumb-item active">Dinero Inicial Caja Chica</li>@endsection

@section('content')
    <div class="row">
        <div class="col-lg-5">
            @if ($initial && $initial->date->toDateString() !== today()->toDateString())
                <div class="alert alert-warning alert-dismissible fade show">
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                    <strong><i class="fas fa-info-circle mr-1"></i>Información</strong><br>
                    Solo se puede registrar el dinero inicial para <strong>hoy {{ today()->format('d/m/Y') }}</strong>. Los
                    registros de otras fechas son de solo lectura.
                </div>
            @endif

            <div class="card card-outline card-success">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-coins mr-1"></i> Registrar Dinero Inicial</h3>
                </div>
                <form method="POST" action="{{ route('cash-box-initial.store') }}">
                    @csrf
                    <div class="card-body">
                        <div class="form-group">
                            <label>Fecha</label>
                            <input type="date" name="date" class="form-control" value="{{ today()->toDateString() }}"
                                readonly>
                            <small class="form-text text-muted">Solo puedes registrar dinero inicial para hoy</small>
                        </div>
                        <div class="form-group">
                            <label>Dinero Inicial ($) *</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">$</span>
                                </div>
                                <input type="number" id="initial_amount" name="initial_amount" step="0.01" min="0"
                                    class="form-control @error('initial_amount') is-invalid @enderror"
                                    value="{{ old('initial_amount', 0) }}"
                                    required>
                            </div>
                            <small class="form-text text-muted">Efectivo que hay en caja al inicio del día</small>
                            @error('initial_amount')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label>Valor Existente (Caja) *</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">$</span>
                                </div>
                                <input type="text" id="existing_value_preview" class="form-control"
                                    value="{{ number_format($todayTotal ?? 0, 2, '.', '') }}" readonly>
                            </div>
                            <small class="form-text text-muted">Se actualiza automaticamente con el dinero inicial ingresado.</small>
                        </div>
                        <div class="form-group">
                            <label>Notas</label>
                            <textarea name="notes" class="form-control" rows="2">{{ old('notes', $initial && today()->toDateString() === $today ? $initial->notes : '') }}</textarea>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save mr-1"></i> Registrar Dinero Inicial
                        </button>
                        <a href="{{ route('daily-closings.index') }}" class="btn btn-secondary ml-2">
                            <i class="fas fa-arrow-left mr-1"></i> Volver
                        </a>
                    </div>
                </form>
            </div>

            @if (($todayTotal ?? 0) > 0 && today()->toDateString() === $today)
                <div class="alert alert-info mt-3">
                    <strong><i class="fas fa-check-circle mr-1"></i> Dinero Inicial Registrado Hoy</strong>
                    <p class="mb-0 mt-2">Monto: <strong
                            class="text-success">${{ number_format($todayTotal, 2) }}</strong></p>
                    @if ($initial->notes)
                        <p class="mb-0">Notas: <small class="text-muted">{{ $initial->notes }}</small></p>
                    @endif
                </div>
            @endif
        </div>

        <div class="col-lg-7">
            <div class="card card-outline card-info">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-history mr-1"></i> Historial de Dinero Inicial</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover table-sm mb-0">
                        <thead class="thead-dark">
                            <tr>
                                <th>Fecha</th>
                                <th class="text-right">Monto ($)</th>
                                <th>Notas</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($history as $record)
                                <tr class="{{ $record->date->isToday() ? 'table-info font-weight-bold' : '' }}">
                                    <td>
                                        {{ $record->date->format('d/m/Y') }}
                                        @if ($record->date->isToday())
                                            <span class="badge badge-info ml-1">Hoy</span>
                                        @endif
                                    </td>
                                    <td class="text-right text-success font-weight-bold">
                                        ${{ number_format($record->initial_amount, 2) }}</td>
                                    <td>
                                        @if ($record->notes)
                                            <small class="text-muted">{{ $record->notes }}</small>
                                        @else
                                            <small class="text-muted">—</small>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-xs btn-warning edit-cash-box-btn"
                                            data-id="{{ $record->id }}"
                                            data-date="{{ $record->date->format('d/m/Y') }}"
                                            data-amount="{{ $record->initial_amount }}"
                                            data-notes="{{ $record->notes ?? '' }}"
                                            data-toggle="modal" data-target="#editCashBoxModal">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form action="{{ route('cash-box-initial.destroy', $record) }}" method="POST"
                                            class="d-inline"
                                            onsubmit="return confirm('¿Seguro que deseas eliminar este registro de dinero inicial?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-xs btn-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">Sin registros de dinero inicial
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($history->hasPages())
                    <div class="card-footer">{{ $history->links() }}</div>
                @endif
            </div>
        </div>
    </div>

    <div class="modal fade" id="editCashBoxModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title"><i class="fas fa-edit mr-1"></i> Editar Dinero Inicial</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="POST" id="editCashBoxForm">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Fecha</label>
                            <input type="text" id="edit_cash_box_date" class="form-control" readonly>
                        </div>
                        <div class="form-group">
                            <label>Monto ($) *</label>
                            <input type="number" name="initial_amount" id="edit_cash_box_amount" step="0.01"
                                min="0" class="form-control" required>
                        </div>
                        <div class="form-group mb-0">
                            <label>Notas</label>
                            <textarea name="notes" id="edit_cash_box_notes" rows="2" class="form-control"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-save mr-1"></i> Guardar cambios
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
            const initialAmountInput = document.getElementById('initial_amount');
            const existingPreview = document.getElementById('existing_value_preview');
            const baseExistingTotal = {{ (float) ($todayTotal ?? 0) }};
            const editForm = document.getElementById('editCashBoxForm');
            const editDate = document.getElementById('edit_cash_box_date');
            const editAmount = document.getElementById('edit_cash_box_amount');
            const editNotes = document.getElementById('edit_cash_box_notes');
            const buttons = document.querySelectorAll('.edit-cash-box-btn');

            function syncExistingPreview() {
                if (!initialAmountInput || !existingPreview) {
                    return;
                }

                const amount = parseFloat(initialAmountInput.value) || 0;
                existingPreview.value = (baseExistingTotal + amount).toFixed(2);
            }

            if (initialAmountInput) {
                initialAmountInput.addEventListener('input', syncExistingPreview);
                syncExistingPreview();
            }

            buttons.forEach(function(button) {
                button.addEventListener('click', function() {
                    const id = this.dataset.id;
                    editForm.action = "{{ url('cash-box-initial') }}/" + id;
                    editDate.value = this.dataset.date || '';
                    editAmount.value = this.dataset.amount || 0;
                    editNotes.value = this.dataset.notes || '';
                });
            });
        });
    </script>
@endpush
