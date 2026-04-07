@extends('layouts.app')
@section('title', 'Editar Giro')
@section('page-title', 'Editar Giro #' . $transfer->id)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('transfers.index') }}">Giros</a></li>
    <li class="breadcrumb-item active">Editar</li>
@endsection

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card card-warning card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-edit mr-1"></i> Editar Giro</h3>
                </div>
                <form method="POST" action="{{ route('transfers.update', $transfer) }}">
                    @csrf @method('PUT')
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Empresa *</label>
                                    <select name="company_id" class="form-control" required>
                                        @foreach ($companies as $c)
                                            <option value="{{ $c->id }}"
                                                {{ $transfer->company_id == $c->id ? 'selected' : '' }}>{{ $c->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Fecha *</label>
                                    <input type="date" name="transfer_date" class="form-control"
                                        value="{{ $transfer->transfer_date->toDateString() }}" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Remitente *</label>
                                    <input type="text" name="sender_name" class="form-control"
                                        value="{{ $transfer->sender_name }}" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Destinatario *</label>
                                    <select id="receiver_name_select" class="form-control" required>
                                        <option value="">Seleccionar destinatario...</option>
                                        @foreach ($clients as $client)
                                            <option value="{{ $client->name }}"
                                                {{ $transfer->receiver_name === $client->name ? 'selected' : '' }}>
                                                {{ $client->name }}{{ $client->phone ? ' - ' . $client->phone : '' }}
                                            </option>
                                        @endforeach
                                        <option value="__manual__"
                                            {{ !$clients->pluck('name')->contains($transfer->receiver_name) ? 'selected' : '' }}>
                                            Otro (escribir manualmente)
                                        </option>
                                    </select>
                                    <input type="hidden" id="receiver_name" name="receiver_name"
                                        value="{{ $transfer->receiver_name }}" required>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group" id="receiver_name_manual_wrapper" style="display:none;">
                                    <label>Destinatario manual *</label>
                                    <input type="text" id="receiver_name_manual" class="form-control"
                                        placeholder="Escribe el nombre del destinatario"
                                        value="{{ !$clients->pluck('name')->contains($transfer->receiver_name) ? $transfer->receiver_name : '' }}">
                                    <small class="form-text text-muted">Usa esta opción solo si el destinatario no está en
                                        la lista.</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Monto ($) *</label>
                                    <input type="number" name="amount" step="0.01" class="form-control"
                                        value="{{ $transfer->amount }}" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Estado *</label>
                                    <select name="status" class="form-control" required>
                                        @foreach (['pending' => 'Pendiente', 'sent' => 'Enviado', 'resent' => 'Reenviado', 'cancelled' => 'Cancelado'] as $val => $label)
                                            <option value="{{ $val }}"
                                                {{ $transfer->status === $val ? 'selected' : '' }}>{{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Código de Transacción</label>
                                    <input type="text" name="transaction_code" class="form-control"
                                        value="{{ $transfer->transaction_code }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Notas</label>
                                    <input type="text" name="notes" class="form-control"
                                        value="{{ $transfer->notes }}">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-warning"><i class="fas fa-save mr-1"></i> Actualizar</button>
                        <a href="{{ route('transfers.index') }}" class="btn btn-secondary ml-2">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const receiverSelect = document.getElementById('receiver_name_select');
            const receiverInput = document.getElementById('receiver_name');
            const manualWrapper = document.getElementById('receiver_name_manual_wrapper');
            const manualInput = document.getElementById('receiver_name_manual');
            const form = receiverSelect ? receiverSelect.closest('form') : null;

            if (!receiverSelect || !receiverInput || !manualWrapper || !manualInput || !form) return;

            function syncReceiverValue() {
                if (receiverSelect.value === '__manual__') {
                    manualWrapper.style.display = 'block';
                    receiverInput.value = manualInput.value.trim();
                    return;
                }
                manualWrapper.style.display = 'none';
                manualInput.value = '';
                receiverInput.value = receiverSelect.value;
            }

            receiverSelect.addEventListener('change', function() {
                syncReceiverValue();
                if (receiverSelect.value === '__manual__') manualInput.focus();
            });

            manualInput.addEventListener('input', function() {
                if (receiverSelect.value !== '__manual__') return;
                receiverInput.value = manualInput.value.trim();
            });

            form.addEventListener('submit', function(e) {
                syncReceiverValue();
                if (!receiverInput.value.trim()) {
                    e.preventDefault();
                    (receiverSelect.value === '__manual__' ? manualInput : receiverSelect).focus();
                }
            });

            syncReceiverValue();
        });
    </script>
@endpush
