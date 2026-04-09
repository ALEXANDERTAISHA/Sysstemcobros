@extends('layouts.app')
@section('title', 'Nuevo Débito')
@section('page-title', 'Registrar Débito')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('expenses.index') }}">Gastos / Débitos</a></li>
    <li class="breadcrumb-item active">Nuevo</li>
@endsection

@push('styles')
    <style>
        .form-group-hidden {
            opacity: 0;
            max-height: 0;
            overflow: hidden;
            pointer-events: none;
            transition: opacity 0.45s ease, max-height 0.45s ease, margin 0.45s ease;
            margin: 0;
        }

        .form-group-visible {
            opacity: 1;
            max-height: 500px;
            pointer-events: auto;
            transition: opacity 0.45s ease, max-height 0.45s ease, margin 0.45s ease;
            margin-bottom: 1rem;
        }

        .step-indicator {
            font-size: 0.85rem;
            color: #999;
            margin-top: 0.25rem;
        }

        .form-group label::after {
            content: " ";
        }

        .expense-flow-summary {
            display: grid;
            gap: 10px;
            margin-bottom: 1rem;
        }

        .expense-flow-pill {
            display: none;
            align-items: center;
            justify-content: space-between;
            padding: 0.75rem 0.9rem;
            border-radius: 10px;
            background: #fff8e1;
            border: 1px solid #ffe08a;
        }

        .expense-flow-pill.is-visible {
            display: flex;
        }

        .expense-flow-pill-label {
            display: block;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #8a6d3b;
        }

        .expense-flow-pill-value {
            display: block;
            color: #2f2f2f;
            font-weight: 600;
        }

        .expense-flow-edit {
            border: 0;
            background: transparent;
            color: #856404;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .expense-step-card {
            border: 1px dashed #f1c40f;
            border-radius: 12px;
            background: linear-gradient(180deg, #fffdf6 0%, #ffffff 100%);
            padding: 1rem;
        }

        .expense-step-title {
            margin-bottom: 0.35rem;
            font-size: 1rem;
            font-weight: 700;
            color: #6b4f00;
        }

        .expense-step-help {
            margin-bottom: 0.9rem;
            color: #8c8c8c;
            font-size: 0.9rem;
        }
    </style>
@endpush

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card card-warning card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-hand-holding-usd mr-1"></i> Registrar Débito</h3>
                </div>
                <form method="POST" action="{{ route('expenses.store') }}">
                    @csrf
                    <div class="card-body">
                        <div class="expense-flow-summary">
                            <div class="expense-flow-pill" id="client_pill">
                                <div>
                                    <span class="expense-flow-pill-label">Cliente</span>
                                    <span class="expense-flow-pill-value" id="client_pill_value"></span>
                                </div>
                                <button type="button" class="expense-flow-edit" data-edit-step="client">
                                    <i class="fas fa-pen mr-1"></i>Editar
                                </button>
                            </div>
                            <div class="expense-flow-pill" id="company_pill">
                                <div>
                                    <span class="expense-flow-pill-label">Empresa</span>
                                    <span class="expense-flow-pill-value" id="company_pill_value"></span>
                                </div>
                                <button type="button" class="expense-flow-edit" data-edit-step="company">
                                    <i class="fas fa-pen mr-1"></i>Editar
                                </button>
                            </div>
                            <div class="expense-flow-pill" id="concept_pill">
                                <div>
                                    <span class="expense-flow-pill-label">Concepto</span>
                                    <span class="expense-flow-pill-value" id="concept_pill_value"></span>
                                </div>
                                <button type="button" class="expense-flow-edit" data-edit-step="concept">
                                    <i class="fas fa-pen mr-1"></i>Editar
                                </button>
                            </div>
                            <div class="expense-flow-pill" id="amount_pill">
                                <div>
                                    <span class="expense-flow-pill-label">Monto Total</span>
                                    <span class="expense-flow-pill-value" id="amount_pill_value"></span>
                                </div>
                                <button type="button" class="expense-flow-edit" data-edit-step="amount">
                                    <i class="fas fa-pen mr-1"></i>Editar
                                </button>
                            </div>
                            <div class="expense-flow-pill" id="granted_pill">
                                <div>
                                    <span class="expense-flow-pill-label">Fecha de Otorgamiento</span>
                                    <span class="expense-flow-pill-value" id="granted_pill_value"></span>
                                </div>
                                <button type="button" class="expense-flow-edit" data-edit-step="granted">
                                    <i class="fas fa-pen mr-1"></i>Editar
                                </button>
                            </div>
                            <div class="expense-flow-pill" id="due_pill">
                                <div>
                                    <span class="expense-flow-pill-label">Fecha Límite de Pago</span>
                                    <span class="expense-flow-pill-value" id="due_pill_value"></span>
                                </div>
                                <button type="button" class="expense-flow-edit" data-edit-step="due">
                                    <i class="fas fa-pen mr-1"></i>Editar
                                </button>
                            </div>
                        </div>

                        <div id="client_section" class="form-group form-group-visible expense-step-card">
                            <div class="expense-step-title">Cliente <span class="step-indicator">Paso 1</span></div>
                            <div class="expense-step-help">Empieza seleccionando a quién pertenece este débito.</div>
                            <label>Cliente *</label>
                            <select id="client_select" name="client_id" class="form-control @error('client_id') is-invalid @enderror" required>
                                <option value="">Seleccionar cliente...</option>
                                @foreach ($clients as $client)
                                    <option value="{{ $client->id }}" data-company="{{ $client->company_id ?? '' }}"
                                        {{ old('client_id', request('client_id')) == $client->id ? 'selected' : '' }}>
                                        {{ $client->name }} {{ $client->phone ? "({$client->phone})" : '' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('client_id')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div id="company_section" class="form-group form-group-hidden expense-step-card">
                            <div class="expense-step-title">Empresa <span class="step-indicator">Paso 2</span></div>
                            <div class="expense-step-help">Confirma la empresa asociada al cliente.</div>
                            <label>Empresa *</label>
                            <select id="company_select" name="company_id" class="form-control @error('company_id') is-invalid @enderror">
                                <option value="">Seleccionar empresa...</option>
                                @foreach ($companies as $company)
                                    <option value="{{ $company->id }}" {{ old('company_id') == $company->id ? 'selected' : '' }}>
                                        {{ $company->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('company_id')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-success" id="auto_filled_message" style="display: none;">
                                <i class="fas fa-check-circle mr-1"></i>Empresa completada automáticamente según el cliente
                            </small>
                        </div>

                        <div id="concept_section" class="form-group form-group-hidden expense-step-card">
                            <div class="expense-step-title">Concepto / Descripción <span class="step-indicator">Paso 3</span></div>
                            <div class="expense-step-help">Describe el motivo del débito.</div>
                            <label>Concepto / Descripción *</label>
                            <input type="text" id="concept_input" name="concept" class="form-control @error('concept') is-invalid @enderror"
                                value="{{ old('concept') }}" placeholder="Ej: Préstamo en efectivo, mercadería..." required>
                            @error('concept')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div id="amount_section" class="form-group form-group-hidden expense-step-card">
                            <div class="expense-step-title">Monto Total ($) <span class="step-indicator">Paso 4</span></div>
                            <div class="expense-step-help">Ingresa el valor del débito para continuar.</div>
                            <label>Monto Total ($) *</label>
                            <input type="number" id="total_amount_input" name="total_amount" step="0.01" min="0.01"
                                class="form-control @error('total_amount') is-invalid @enderror"
                                value="{{ old('total_amount') }}" required>
                            @error('total_amount')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div id="granted_section" class="form-group form-group-hidden expense-step-card">
                            <div class="expense-step-title">Fecha de Otorgamiento <span class="step-indicator">Paso 5</span></div>
                            <div class="expense-step-help">Define cuándo se registró este débito.</div>
                            <label>Fecha de Otorgamiento *</label>
                            <input type="date" id="granted_date_input" name="granted_date" class="form-control"
                                value="{{ old('granted_date', today()->toDateString()) }}" required>
                        </div>

                        <div id="due_section" class="form-group form-group-hidden expense-step-card">
                            <div class="expense-step-title">Fecha Límite de Pago <span class="step-indicator">Paso 6</span></div>
                            <div class="expense-step-help">Establece la fecha máxima para el pago del débito.</div>
                            <label>Fecha Límite de Pago *</label>
                            <input type="date" id="due_date_input" name="due_date"
                                class="form-control @error('due_date') is-invalid @enderror"
                                value="{{ old('due_date') }}" required>
                            @error('due_date')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div id="notes_section" class="form-group form-group-hidden expense-step-card">
                            <div class="expense-step-title">Notas <span class="step-indicator">Final</span></div>
                            <div class="expense-step-help">Agrega una nota final si necesitas más contexto antes de guardar.</div>
                            <label>Notas</label>
                            <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-warning"><i class="fas fa-save mr-1"></i> Registrar Débito</button>
                        <a href="{{ route('expenses.index') }}" class="btn btn-secondary ml-2">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const clientSection = document.getElementById('client_section');
            const clientSelect = document.getElementById('client_select');
            const companySection = document.getElementById('company_section');
            const companySelect = document.getElementById('company_select');
            const conceptSection = document.getElementById('concept_section');
            const conceptInput = document.getElementById('concept_input');
            const amountSection = document.getElementById('amount_section');
            const totalAmountInput = document.getElementById('total_amount_input');
            const grantedSection = document.getElementById('granted_section');
            const grantedDateInput = document.getElementById('granted_date_input');
            const dueSection = document.getElementById('due_section');
            const dueDateInput = document.getElementById('due_date_input');
            const notesSection = document.getElementById('notes_section');
            const autoFilledMsg = document.getElementById('auto_filled_message');

            const clientPill = document.getElementById('client_pill');
            const companyPill = document.getElementById('company_pill');
            const conceptPill = document.getElementById('concept_pill');
            const amountPill = document.getElementById('amount_pill');
            const grantedPill = document.getElementById('granted_pill');
            const duePill = document.getElementById('due_pill');

            const clientPillValue = document.getElementById('client_pill_value');
            const companyPillValue = document.getElementById('company_pill_value');
            const conceptPillValue = document.getElementById('concept_pill_value');
            const amountPillValue = document.getElementById('amount_pill_value');
            const grantedPillValue = document.getElementById('granted_pill_value');
            const duePillValue = document.getElementById('due_pill_value');

            const editButtons = document.querySelectorAll('[data-edit-step]');

            const clientToCompany = @json(
                $clients->mapWithKeys(fn($client) => [$client->id => $client->company_id])->toArray()
            );

            function showSection(element) {
                element.classList.remove('form-group-hidden');
                element.classList.add('form-group-visible');
            }

            function hideSection(element) {
                element.classList.remove('form-group-visible');
                element.classList.add('form-group-hidden');
            }

            function setPill(pill, valueElement, value) {
                valueElement.textContent = value;
                pill.classList.toggle('is-visible', value.trim() !== '');
            }

            function selectedText(select) {
                return select.options[select.selectedIndex]?.text?.trim() ?? '';
            }

            function formatDate(value) {
                if (!value) {
                    return '';
                }

                const [year, month, day] = value.split('-');
                return year && month && day ? `${day}/${month}/${year}` : value;
            }

            function updateVisibility() {
                const hasClient = clientSelect.value !== '';
                const hasCompany = companySelect.value !== '';
                const hasConcept = conceptInput.value.trim() !== '';
                const hasAmount = (parseFloat(totalAmountInput.value) || 0) > 0;
                const hasGranted = grantedDateInput.value !== '';
                const hasDue = dueDateInput.value !== '';

                setPill(clientPill, clientPillValue, hasClient ? selectedText(clientSelect) : '');
                setPill(companyPill, companyPillValue, hasCompany ? selectedText(companySelect) : '');
                setPill(conceptPill, conceptPillValue, hasConcept ? conceptInput.value.trim() : '');
                setPill(amountPill, amountPillValue, hasAmount ? `$${Number(totalAmountInput.value).toFixed(2)}` : '');
                setPill(grantedPill, grantedPillValue, hasGranted ? formatDate(grantedDateInput.value) : '');
                setPill(duePill, duePillValue, hasDue ? formatDate(dueDateInput.value) : '');

                if (!hasClient) {
                    showSection(clientSection);
                    hideSection(companySection);
                    hideSection(conceptSection);
                    hideSection(amountSection);
                    hideSection(grantedSection);
                    hideSection(dueSection);
                    hideSection(notesSection);
                    autoFilledMsg.style.display = 'none';
                    return;
                }

                hideSection(clientSection);

                if (!hasCompany) {
                    showSection(companySection);
                    hideSection(conceptSection);
                    hideSection(amountSection);
                    hideSection(grantedSection);
                    hideSection(dueSection);
                    hideSection(notesSection);
                    return;
                }

                hideSection(companySection);

                if (!hasConcept) {
                    showSection(conceptSection);
                    hideSection(amountSection);
                    hideSection(grantedSection);
                    hideSection(dueSection);
                    hideSection(notesSection);
                    return;
                }

                hideSection(conceptSection);

                if (!hasAmount) {
                    showSection(amountSection);
                    hideSection(grantedSection);
                    hideSection(dueSection);
                    hideSection(notesSection);
                    return;
                }

                hideSection(amountSection);

                if (!hasGranted) {
                    showSection(grantedSection);
                    hideSection(dueSection);
                    hideSection(notesSection);
                    return;
                }

                hideSection(grantedSection);

                if (!hasDue) {
                    showSection(dueSection);
                    hideSection(notesSection);
                    return;
                }

                hideSection(dueSection);
                showSection(notesSection);
            }

            clientSelect.addEventListener('change', function() {
                const clientId = this.value;

                if (clientId && clientToCompany[clientId]) {
                    companySelect.value = clientToCompany[clientId];
                    autoFilledMsg.style.display = 'inline';
                } else {
                    companySelect.value = '';
                    autoFilledMsg.style.display = 'none';
                }

                updateVisibility();
            });

            companySelect.addEventListener('change', updateVisibility);
            conceptInput.addEventListener('blur', updateVisibility);
            conceptInput.addEventListener('keydown', function(event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    updateVisibility();
                }
            });

            totalAmountInput.addEventListener('blur', updateVisibility);
            totalAmountInput.addEventListener('keydown', function(event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    updateVisibility();
                }
            });

            grantedDateInput.addEventListener('change', updateVisibility);
            dueDateInput.addEventListener('change', updateVisibility);

            editButtons.forEach(function(button) {
                button.addEventListener('click', function() {
                    const step = this.dataset.editStep;

                    if (step === 'client') {
                        showSection(clientSection);
                        hideSection(companySection);
                        hideSection(conceptSection);
                        hideSection(amountSection);
                        hideSection(grantedSection);
                        hideSection(dueSection);
                        hideSection(notesSection);
                        clientSelect.focus();
                    }

                    if (step === 'company') {
                        hideSection(clientSection);
                        showSection(companySection);
                        hideSection(conceptSection);
                        hideSection(amountSection);
                        hideSection(grantedSection);
                        hideSection(dueSection);
                        hideSection(notesSection);
                        companySelect.focus();
                    }

                    if (step === 'concept') {
                        hideSection(clientSection);
                        hideSection(companySection);
                        showSection(conceptSection);
                        hideSection(amountSection);
                        hideSection(grantedSection);
                        hideSection(dueSection);
                        hideSection(notesSection);
                        conceptInput.focus();
                    }

                    if (step === 'amount') {
                        hideSection(clientSection);
                        hideSection(companySection);
                        hideSection(conceptSection);
                        showSection(amountSection);
                        hideSection(grantedSection);
                        hideSection(dueSection);
                        hideSection(notesSection);
                        totalAmountInput.focus();
                    }

                    if (step === 'granted') {
                        hideSection(clientSection);
                        hideSection(companySection);
                        hideSection(conceptSection);
                        hideSection(amountSection);
                        showSection(grantedSection);
                        hideSection(dueSection);
                        hideSection(notesSection);
                        grantedDateInput.focus();
                    }

                    if (step === 'due') {
                        hideSection(clientSection);
                        hideSection(companySection);
                        hideSection(conceptSection);
                        hideSection(amountSection);
                        hideSection(grantedSection);
                        showSection(dueSection);
                        hideSection(notesSection);
                        dueDateInput.focus();
                    }
                });
            });

            updateVisibility();
        });
    </script>
@endpush
