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

        .quick-create-box {
            margin-top: 1rem;
            border-top: 1px dashed #e1c15a;
            padding-top: .9rem;
        }

        .quick-create-toggle {
            border: 0;
            background: transparent;
            color: #856404;
            font-weight: 700;
            padding: 0;
        }

        .quick-create-form {
            display: none;
            margin-top: .85rem;
            padding: .9rem;
            border-radius: 10px;
            background: #fff;
            border: 1px solid #f5df95;
        }

        .quick-create-form.is-visible {
            display: block;
        }

        .quick-feedback {
            display: none;
            margin-top: .75rem;
        }

        .quick-feedback.is-visible {
            display: block;
        }

        .select-expanded {
            height: auto;
            border: 1px solid #e6c86b;
            border-radius: 10px;
            background: #fffef9;
            box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.05);
            padding: .25rem;
        }

        .client-filter-wrap {
            margin-bottom: .6rem;
        }

        .client-filter-input {
            border-radius: 10px;
            border: 1px solid #ecd38a;
            background: #fffdf5;
        }

        .client-filter-input:focus {
            border-color: #d6a800;
            box-shadow: 0 0 0 .15rem rgba(214, 168, 0, .15);
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
                            <div class="client-filter-wrap" id="client_filter_wrap">
                                <input type="text" id="client_filter_input" class="form-control client-filter-input"
                                    placeholder="Buscar cliente por nombre o teléfono...">
                            </div>
                            <select id="client_select" name="client_id" class="form-control @error('client_id') is-invalid @enderror" required size="1">
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
                            <div class="quick-create-box">
                                <button type="button" class="quick-create-toggle" id="toggle_quick_client">
                                    <i class="fas fa-user-plus mr-1"></i>No encuentro el cliente, registrarlo aqui
                                </button>
                                <div class="quick-create-form" id="quick_client_form_wrap">
                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label>Nombre del cliente *</label>
                                            <input type="text" class="form-control" id="quick_client_name" autocapitalize="words">
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label>Telefono</label>
                                            <input type="tel" class="form-control" id="quick_client_phone" inputmode="numeric" pattern="[0-9]*" maxlength="15" autocomplete="tel" placeholder="Solo numeros">
                                        </div>
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label>Email</label>
                                            <input type="email" class="form-control" id="quick_client_email" autocomplete="email" placeholder="correo@ejemplo.com">
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label>WhatsApp</label>
                                            <input type="tel" class="form-control" id="quick_client_whatsapp" inputmode="numeric" pattern="[0-9]*" maxlength="15" autocomplete="tel" placeholder="Solo numeros">
                                        </div>
                                    </div>
                                    <div class="form-group mb-2">
                                        <label>Direccion</label>
                                        <input type="text" class="form-control" id="quick_client_address">
                                    </div>
                                    <button type="button" class="btn btn-sm btn-warning" id="quick_client_submit">
                                        <i class="fas fa-save mr-1"></i>Guardar cliente
                                    </button>
                                    <div class="alert mt-2 mb-0 quick-feedback" id="quick_client_feedback"></div>
                                </div>
                            </div>
                        </div>

                        <div id="company_section" class="form-group form-group-hidden expense-step-card">
                            <div class="expense-step-title">Empresa <span class="step-indicator">Paso 2</span></div>
                            <div class="expense-step-help">Confirma la empresa asociada al cliente.</div>
                            <label>Empresa *</label>
                            <select id="company_select" name="company_id" class="form-control @error('company_id') is-invalid @enderror" size="{{ min(($companies->count() + 1), 8) }}">
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
                            <div class="quick-create-box">
                                <button type="button" class="quick-create-toggle" id="toggle_quick_company">
                                    <i class="fas fa-building mr-1"></i>No encuentro la empresa, registrarla aqui
                                </button>
                                <div class="quick-create-form" id="quick_company_form_wrap">
                                    <div class="form-row">
                                        <div class="form-group col-md-5">
                                            <label>Nombre de empresa *</label>
                                            <input type="text" class="form-control" id="quick_company_name">
                                        </div>
                                        <div class="form-group col-md-3">
                                            <label>Codigo *</label>
                                            <input type="text" class="form-control" id="quick_company_code">
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label>Color *</label>
                                            <input type="color" class="form-control" id="quick_company_color" value="#0d6efd">
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-warning" id="quick_company_submit">
                                        <i class="fas fa-save mr-1"></i>Guardar empresa
                                    </button>
                                    <div class="alert mt-2 mb-0 quick-feedback" id="quick_company_feedback"></div>
                                </div>
                            </div>
                        </div>

                        <input type="hidden" name="concept" value="{{ old('concept', 'Débito registrado') }}">

                        <div id="granted_section" class="form-group form-group-hidden expense-step-card">
                            <div class="expense-step-title">Fecha de Otorgamiento <span class="step-indicator">Paso 3</span></div>
                            <div class="expense-step-help">Define cuándo se registró este débito.</div>
                            <label>Fecha de Otorgamiento *</label>
                            <input type="date" id="granted_date_input" name="granted_date" class="form-control"
                                value="{{ old('granted_date', today()->toDateString()) }}" required>
                        </div>

                        <div id="amount_section" class="form-group form-group-hidden expense-step-card">
                            <div class="expense-step-title">Monto y Fecha Límite <span class="step-indicator">Paso 4</span></div>
                            <div class="expense-step-help">Ingresa el monto y confirma la fecha límite (automática a 7 días).</div>
                            <div class="form-row">
                                <div class="form-group col-md-6 mb-0">
                                    <label>Monto Total ($) *</label>
                                    <input type="number" id="total_amount_input" name="total_amount" step="0.01" min="0.01"
                                        class="form-control @error('total_amount') is-invalid @enderror"
                                        value="{{ old('total_amount') }}" required>
                                    @error('total_amount')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="form-group col-md-6 mb-0">
                                    <label>Fecha Límite de Pago *</label>
                                    <input type="date" id="due_date_input" name="due_date"
                                        class="form-control @error('due_date') is-invalid @enderror"
                                        value="{{ old('due_date') }}" required>
                                    @error('due_date')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <input type="hidden" name="notes" value="{{ old('notes') }}">
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
            const clientFilterWrap = document.getElementById('client_filter_wrap');
            const clientFilterInput = document.getElementById('client_filter_input');
            const companySection = document.getElementById('company_section');
            const companySelect = document.getElementById('company_select');
            const amountSection = document.getElementById('amount_section');
            const totalAmountInput = document.getElementById('total_amount_input');
            const grantedSection = document.getElementById('granted_section');
            const grantedDateInput = document.getElementById('granted_date_input');
            const dueDateInput = document.getElementById('due_date_input');
            const autoFilledMsg = document.getElementById('auto_filled_message');

            const clientPill = document.getElementById('client_pill');
            const companyPill = document.getElementById('company_pill');
            const amountPill = document.getElementById('amount_pill');
            const grantedPill = document.getElementById('granted_pill');
            const duePill = document.getElementById('due_pill');

            const clientPillValue = document.getElementById('client_pill_value');
            const companyPillValue = document.getElementById('company_pill_value');
            const amountPillValue = document.getElementById('amount_pill_value');
            const grantedPillValue = document.getElementById('granted_pill_value');
            const duePillValue = document.getElementById('due_pill_value');

            const editButtons = document.querySelectorAll('[data-edit-step]');
            const toggleQuickClient = document.getElementById('toggle_quick_client');
            const quickClientFormWrap = document.getElementById('quick_client_form_wrap');
            const quickClientSubmit = document.getElementById('quick_client_submit');
            const quickClientFeedback = document.getElementById('quick_client_feedback');
            const quickClientPhoneInput = document.getElementById('quick_client_phone');
            const quickClientEmailInput = document.getElementById('quick_client_email');
            const quickClientWhatsappInput = document.getElementById('quick_client_whatsapp');
            const toggleQuickCompany = document.getElementById('toggle_quick_company');
            const quickCompanyFormWrap = document.getElementById('quick_company_form_wrap');
            const quickCompanySubmit = document.getElementById('quick_company_submit');
            const quickCompanyFeedback = document.getElementById('quick_company_feedback');
            let openedClientSelectOnce = false;
            let openedCompanySelectOnce = false;

            const clientToCompany = @json(
                $clients->mapWithKeys(fn($client) => [$client->id => $client->company_id])->toArray()
            );

            function toggleQuickForm(element) {
                element.classList.toggle('is-visible');
            }

            function setFeedback(element, type, message) {
                element.className = `alert alert-${type} mt-2 mb-0 quick-feedback is-visible`;
                element.textContent = message;
            }

            function onlyDigits(value) {
                return (value || '').replace(/\D+/g, '');
            }

            function isValidEmail(value) {
                if (!value) {
                    return true;
                }

                return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
            }

            async function postJson(url, payload) {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    },
                    body: JSON.stringify(payload),
                });

                const data = await response.json();

                if (!response.ok) {
                    const errors = data.errors ? Object.values(data.errors).flat().join(' ') : (data.message || 'No se pudo completar la solicitud.');
                    throw new Error(errors);
                }

                return data;
            }

            toggleQuickClient.addEventListener('click', function() {
                toggleQuickForm(quickClientFormWrap);
            });

            toggleQuickCompany.addEventListener('click', function() {
                toggleQuickForm(quickCompanyFormWrap);
            });

            const quickClientNameInput = document.getElementById('quick_client_name');

            if (quickClientNameInput) {
                quickClientNameInput.addEventListener('input', function() {
                    this.value = this.value.replace(/\b\w/g, function(letter) {
                        return letter.toUpperCase();
                    });
                });
            }

            if (quickClientPhoneInput) {
                quickClientPhoneInput.addEventListener('input', function() {
                    this.value = onlyDigits(this.value);
                });
            }

            if (quickClientWhatsappInput) {
                quickClientWhatsappInput.addEventListener('input', function() {
                    this.value = onlyDigits(this.value);
                });
            }

            quickClientSubmit.addEventListener('click', async function() {
                try {
                    this.disabled = true;
                    const cleanPhone = onlyDigits(document.getElementById('quick_client_phone').value);
                    const cleanWhatsapp = onlyDigits(document.getElementById('quick_client_whatsapp').value);
                    const emailValue = (document.getElementById('quick_client_email').value || '').trim();

                    if (!isValidEmail(emailValue)) {
                        throw new Error('Ingresa un correo electronico valido.');
                    }

                    const data = await postJson('{{ route('expenses.quick-client') }}', {
                        name: document.getElementById('quick_client_name').value,
                        phone: cleanPhone,
                        email: emailValue,
                        whatsapp: cleanWhatsapp,
                        address: document.getElementById('quick_client_address').value,
                    });

                    const option = new Option(
                        `${data.client.name}${data.client.phone ? ' (' + data.client.phone + ')' : ''}`,
                        data.client.id,
                        true,
                        true
                    );
                    clientSelect.add(option);
                    clientToCompany[String(data.client.id)] = '';
                    quickClientFormWrap.classList.remove('is-visible');
                    setFeedback(quickClientFeedback, 'success', data.message);
                    clientSelect.dispatchEvent(new Event('change'));
                } catch (error) {
                    setFeedback(quickClientFeedback, 'danger', error.message);
                } finally {
                    this.disabled = false;
                }
            });

            quickCompanySubmit.addEventListener('click', async function() {
                try {
                    this.disabled = true;
                    const data = await postJson('{{ route('expenses.quick-company') }}', {
                        name: document.getElementById('quick_company_name').value,
                        code: document.getElementById('quick_company_code').value,
                        color: document.getElementById('quick_company_color').value,
                    });

                    const option = new Option(data.company.name, data.company.id, true, true);
                    companySelect.add(option);
                    quickCompanyFormWrap.classList.remove('is-visible');
                    setFeedback(quickCompanyFeedback, 'success', data.message);
                    updateVisibility();
                } catch (error) {
                    setFeedback(quickCompanyFeedback, 'danger', error.message);
                } finally {
                    this.disabled = false;
                }
            });

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

            function expandClientSelect() {
                if (!clientSelect || clientSelect.value) {
                    return;
                }

                const visibleCount = Array.from(clientSelect.options)
                    .filter((option, index) => index === 0 || !option.hidden).length;
                const visibleOptions = Math.min(Math.max(visibleCount, 2), 8);
                clientSelect.setAttribute('size', String(visibleOptions));
                clientSelect.classList.add('select-expanded');
            }

            function collapseClientSelect() {
                if (!clientSelect) {
                    return;
                }

                clientSelect.setAttribute('size', '1');
                clientSelect.classList.remove('select-expanded');
            }

            function expandCompanySelect() {
                if (!companySelect || companySelect.value) {
                    return;
                }

                const visibleCount = companySelect.options.length;
                const visibleOptions = Math.min(Math.max(visibleCount, 2), 8);
                companySelect.setAttribute('size', String(visibleOptions));
                companySelect.classList.add('select-expanded');
            }

            function collapseCompanySelect() {
                if (!companySelect) {
                    return;
                }

                companySelect.setAttribute('size', '1');
                companySelect.classList.remove('select-expanded');
            }

            function filterClientOptions() {
                if (!clientFilterInput || !clientSelect) {
                    return;
                }

                const term = clientFilterInput.value.trim().toLowerCase();

                Array.from(clientSelect.options).forEach(function(option, index) {
                    if (index === 0) {
                        option.hidden = false;
                        return;
                    }

                    option.hidden = term !== '' && !option.text.toLowerCase().includes(term);
                });

                if (term === '') {
                    clientSelect.value = '';
                    updateVisibility();
                }
            }

            function selectFirstMatchingClientOption() {
                if (!clientSelect) {
                    return;
                }

                const firstMatch = Array.from(clientSelect.options).find(function(option, index) {
                    return index > 0 && !option.hidden;
                });

                if (firstMatch) {
                    clientSelect.value = firstMatch.value;
                    clientSelect.dispatchEvent(new Event('change'));
                }
            }

            function addDays(baseDate, days) {
                const date = new Date(baseDate + 'T00:00:00');
                date.setDate(date.getDate() + days);
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                return `${year}-${month}-${day}`;
            }

            function syncDueDateFromGranted(force = false) {
                if (!grantedDateInput || !dueDateInput || !grantedDateInput.value) {
                    return;
                }

                if (!force && dueDateInput.value) {
                    return;
                }

                dueDateInput.value = addDays(grantedDateInput.value, 7);
            }

            function updateVisibility() {
                const hasClient = clientSelect.value !== '';
                const hasCompany = companySelect.value !== '';
                const hasAmount = (parseFloat(totalAmountInput.value) || 0) > 0;
                const hasGranted = grantedDateInput.value !== '';
                const hasDue = dueDateInput.value !== '';

                setPill(clientPill, clientPillValue, hasClient ? selectedText(clientSelect) : '');
                setPill(companyPill, companyPillValue, hasCompany ? selectedText(companySelect) : '');
                setPill(amountPill, amountPillValue, hasAmount ? `$${Number(totalAmountInput.value).toFixed(2)}` : '');
                setPill(grantedPill, grantedPillValue, hasGranted ? formatDate(grantedDateInput.value) : '');
                setPill(duePill, duePillValue, hasDue ? formatDate(dueDateInput.value) : '');

                if (!hasClient) {
                    showSection(clientSection);
                    hideSection(companySection);
                    hideSection(grantedSection);
                    hideSection(amountSection);
                    autoFilledMsg.style.display = 'none';
                    collapseClientSelect();
                    return;
                }

                hideSection(clientSection);
                collapseClientSelect();

                if (!hasCompany) {
                    showSection(companySection);
                    hideSection(grantedSection);
                    hideSection(amountSection);
                    expandCompanySelect();
                    return;
                }

                hideSection(companySection);
                collapseCompanySelect();

                if (!hasGranted) {
                    showSection(grantedSection);
                    hideSection(amountSection);
                    return;
                }

                hideSection(grantedSection);

                if (!hasAmount || !hasDue) {
                    showSection(amountSection);
                    return;
                }

                hideSection(amountSection);
            }

            clientSelect.addEventListener('change', function() {
                const clientId = this.value;

                if (clientId && clientToCompany[clientId]) {
                    companySelect.value = clientToCompany[clientId];
                    autoFilledMsg.style.display = 'inline';
                    collapseCompanySelect();
                } else {
                    companySelect.value = '';
                    autoFilledMsg.style.display = 'none';
                }

                if (clientId) {
                    collapseClientSelect();
                } else {
                    if (clientFilterInput) {
                        clientFilterInput.value = '';
                    }
                    Array.from(clientSelect.options).forEach(function(option) {
                        option.hidden = false;
                    });
                    expandClientSelect();
                }

                updateVisibility();

                if (clientId && !companySelect.value && !openedCompanySelectOnce) {
                    openedCompanySelectOnce = true;
                    setTimeout(function() {
                        companySelect.focus();
                    }, 120);
                }
            });

            companySelect.addEventListener('change', updateVisibility);

            if (clientFilterInput) {
                clientFilterInput.addEventListener('input', filterClientOptions);
                clientFilterInput.addEventListener('keydown', function(event) {
                    if (event.key === 'Enter') {
                        event.preventDefault();
                        selectFirstMatchingClientOption();
                    }
                });
            }

            totalAmountInput.addEventListener('blur', updateVisibility);
            totalAmountInput.addEventListener('keydown', function(event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    updateVisibility();
                }
            });

            grantedDateInput.addEventListener('change', function() {
                syncDueDateFromGranted(true);
                updateVisibility();
            });
            dueDateInput.addEventListener('change', updateVisibility);

            editButtons.forEach(function(button) {
                button.addEventListener('click', function() {
                    const step = this.dataset.editStep;

                    if (step === 'client') {
                        showSection(clientSection);
                        hideSection(companySection);
                        hideSection(grantedSection);
                        hideSection(amountSection);
                        if (clientFilterInput) {
                            clientFilterInput.value = '';
                        }
                        Array.from(clientSelect.options).forEach(function(option) {
                            option.hidden = false;
                        });
                        collapseClientSelect();
                        if (clientFilterInput) {
                            clientFilterInput.focus();
                        } else {
                            clientSelect.focus();
                        }
                    }

                    if (step === 'company') {
                        hideSection(clientSection);
                        showSection(companySection);
                        hideSection(grantedSection);
                        hideSection(amountSection);
                        expandCompanySelect();
                        companySelect.focus();
                    }

                    if (step === 'amount') {
                        hideSection(clientSection);
                        hideSection(companySection);
                        hideSection(grantedSection);
                        showSection(amountSection);
                        totalAmountInput.focus();
                    }

                    if (step === 'granted') {
                        hideSection(clientSection);
                        hideSection(companySection);
                        hideSection(amountSection);
                        showSection(grantedSection);
                        grantedDateInput.focus();
                    }

                    if (step === 'due') {
                        hideSection(clientSection);
                        hideSection(companySection);
                        hideSection(grantedSection);
                        showSection(amountSection);
                        dueDateInput.focus();
                    }
                });
            });

            syncDueDateFromGranted();
            updateVisibility();
        });
    </script>
@endpush
