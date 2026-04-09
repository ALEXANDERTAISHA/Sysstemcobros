@extends('layouts.app')
@section('title', 'Nuevo Giro')
@section('page-title', 'Registrar Nuevo Giro')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('transfers.index') }}">Giros</a></li>
    <li class="breadcrumb-item active">Nuevo</li>
@endsection

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-plus-circle mr-1"></i> Datos del Giro</h3>
                </div>
                <form method="POST" action="{{ route('transfers.store') }}">
                    @csrf
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
                                        value="{{ old('transfer_date', today()->toDateString()) }}" required>
                                    @error('transfer_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Nombre del Remitente *</label>
                                    <input type="text" class="form-control"
                                        value="{{ auth()->user()->branch?->name ?? (auth()->user()->name ?? 'Usuario actual') }}"
                                        readonly>
                                    <input type="hidden" name="sender_name"
                                        value="{{ old('sender_name', auth()->user()->branch?->name ?? (auth()->user()->name ?? '')) }}"
                                        required>
                                    <small class="form-text text-muted">
                                        Se registra automaticamente la sucursal de la cuenta abierta.
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
                                            data-target="#newClientModal">
                                            <i class="fas fa-user-plus mr-1"></i> Nuevo cliente
                                        </button>
                                    </label>
                                    <select id="receiver_name_select"
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
                                    <input type="hidden" id="receiver_name" name="receiver_name"
                                        value="{{ old('receiver_name') }}" required>
                                    @error('receiver_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-12">
                                <div class="form-group" id="receiver_name_manual_wrapper" style="display:none;">
                                    <label>Destinatario manual *</label>
                                    <input type="text" id="receiver_name_manual" class="form-control"
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
                                <div class="form-group">
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
                        <a href="{{ route('transfers.index') }}" class="btn btn-secondary ml-2">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="newClientModal" tabindex="-1" aria-labelledby="newClientModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-success">
                    <h5 class="modal-title" id="newClientModalLabel">
                        <i class="fas fa-user-plus mr-1"></i> Nuevo cliente
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="inline_new_client_form">
                    <div class="modal-body">
                        <div id="inline_client_alert" class="alert alert-danger d-none py-2 mb-3"></div>

                        <div class="form-group">
                            <label>Nombre completo *</label>
                            <input type="text" class="form-control" id="inline_client_name" name="name" required>
                        </div>

                        <div class="form-group">
                            <label>Teléfono</label>
                            <input type="text" class="form-control" id="inline_client_phone" name="phone"
                                placeholder="0999999999">
                        </div>

                        <div class="form-group mb-0">
                            <label>WhatsApp</label>
                            <input type="text" class="form-control" id="inline_client_whatsapp" name="whatsapp"
                                placeholder="+593999999999">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success" id="inline_client_save_btn">
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
            const receiverSelect = document.getElementById('receiver_name_select');
            const receiverInput = document.getElementById('receiver_name');
            const manualWrapper = document.getElementById('receiver_name_manual_wrapper');
            const manualInput = document.getElementById('receiver_name_manual');
            const form = receiverSelect ? receiverSelect.closest('form') : null;
            const inlineClientForm = document.getElementById('inline_new_client_form');
            const inlineClientAlert = document.getElementById('inline_client_alert');
            const inlineClientSaveBtn = document.getElementById('inline_client_save_btn');
            const inlineClientName = document.getElementById('inline_client_name');
            const inlineClientPhone = document.getElementById('inline_client_phone');
            const inlineClientWhatsapp = document.getElementById('inline_client_whatsapp');
            const newClientModal = document.getElementById('newClientModal');

            if (!receiverSelect || !receiverInput || !manualWrapper || !manualInput || !form) {
                return;
            }

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

            form.addEventListener('submit', function(event) {
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

            if (!inlineClientForm || !inlineClientAlert || !inlineClientSaveBtn || !inlineClientName || !
                newClientModal) {
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
