@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Autoguardado temporal en localStorage
    const storageKey = 'accountsPayableDraft';
    const companySelect = document.getElementById('company_select');
    const totalAmountInput = document.getElementById('total_amount_input');
    const conceptField = document.getElementById('concept_field');
    // Cargar datos guardados
    const draft = JSON.parse(localStorage.getItem(storageKey) || '{}');
    if (draft.client_id && clientSelect) clientSelect.value = draft.client_id;
    if (draft.company_id && companySelect) companySelect.value = draft.company_id;
    if (draft.total_amount && totalAmountInput) totalAmountInput.value = draft.total_amount;
    if (draft.concept && conceptField) conceptField.value = draft.concept;

    // Guardar cambios
    function saveDraft() {
        localStorage.setItem(storageKey, JSON.stringify({
            client_id: clientSelect ? clientSelect.value : '',
            company_id: companySelect ? companySelect.value : '',
            total_amount: totalAmountInput ? totalAmountInput.value : '',
            concept: conceptField ? conceptField.value : ''
        }));
    }
    if (clientSelect) clientSelect.addEventListener('change', saveDraft);
    if (companySelect) companySelect.addEventListener('change', saveDraft);
    if (totalAmountInput) totalAmountInput.addEventListener('input', saveDraft);
    if (conceptField) conceptField.addEventListener('input', saveDraft);

    // Limpiar draft al enviar
    const form = document.querySelector('form[action*="accounts-payable.store"]');
    if (form) {
        form.addEventListener('submit', function() {
            localStorage.removeItem(storageKey);
        });
    }
    const clientSelect = document.getElementById('client_select');
    const clientFilterInput = document.getElementById('client_filter_input');
    function normalizeClientSearch(value) {
        return (value || '').toString().normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/\s+/g, ' ').trim().toLowerCase();
    }
    function clientOptionMatches(option, term) {
        const tokens = normalizeClientSearch(term).split(' ').filter(Boolean);
        if (tokens.length === 0) return true;
        const searchableText = normalizeClientSearch(option.dataset.search || option.text);
        return tokens.every(function(token) { return searchableText.includes(token); });
    }
    function filterClientOptions() {
        if (!clientFilterInput || !clientSelect) return;
        const term = clientFilterInput.value.trim();
        Array.from(clientSelect.options).forEach(function(option, index) {
            if (index === 0) { option.hidden = false; return; }
            option.hidden = !clientOptionMatches(option, term);
        });
        expandClientSelect();
        if (term === '') {
            clientSelect.value = '';
        }
    }

    // Expande la lista al enfocar el input
    if (clientFilterInput) {
        clientFilterInput.addEventListener('focus', function() {
            expandClientSelect();
        });
        clientFilterInput.addEventListener('blur', function() {
            setTimeout(collapseClientSelect, 200); // Espera para permitir selección
        });
        clientFilterInput.addEventListener('input', filterClientOptions);
        clientFilterInput.addEventListener('keydown', function(event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                selectFirstMatchingClientOption();
                collapseClientSelect();
            } else if (event.key === 'ArrowDown') {
                event.preventDefault();
                // Selecciona el siguiente visible
                let idx = clientSelect.selectedIndex;
                let options = Array.from(clientSelect.options);
                let next = options.findIndex((opt, i) => i > idx && !opt.hidden);
                if (next !== -1) {
                    clientSelect.selectedIndex = next;
                }
                expandClientSelect();
            } else if (event.key === 'ArrowUp') {
                event.preventDefault();
                let idx = clientSelect.selectedIndex;
                let options = Array.from(clientSelect.options);
                let prev = options.slice(0, idx).reverse().findIndex(opt => !opt.hidden);
                if (prev !== -1) {
                    clientSelect.selectedIndex = idx - prev - 1;
                }
                expandClientSelect();
            }
        });
    }
    function expandClientSelect() {
        if (!clientSelect || clientSelect.value) return;
        const visibleCount = Array.from(clientSelect.options).filter((option, index) => index === 0 || !option.hidden).length;
        const visibleOptions = Math.min(Math.max(visibleCount, 2), 8);
        clientSelect.setAttribute('size', String(visibleOptions));
        clientSelect.classList.add('select-expanded');
    }
    function collapseClientSelect() {
        if (!clientSelect) return;
        clientSelect.setAttribute('size', '1');
        clientSelect.classList.remove('select-expanded');
    }
    function selectFirstMatchingClientOption() {
        if (!clientSelect) return;
        const firstMatch = Array.from(clientSelect.options).find(function(option, index) { return index > 0 && !option.hidden; });
        if (firstMatch) {
            clientSelect.value = firstMatch.value;
            clientSelect.dispatchEvent(new Event('change'));
        }
    }
    // ...el resto del código permanece igual...
});
</script>
@endpush
@extends('layouts.app')
@section('title', 'Nueva Cuenta por Pagar')
@section('page-title', 'Registrar Cuenta por Pagar')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('accounts-payable.index') }}">Cuentas por Pagar</a></li>
    <li class="breadcrumb-item active">Nueva</li>
@endsection


@push('styles')
    <style>
        .form-group-hidden { opacity: 0; max-height: 0; overflow: hidden; pointer-events: none; transition: opacity 0.45s ease, max-height 0.45s ease, margin 0.45s ease; margin: 0; }
        .form-group-visible { opacity: 1; max-height: 500px; pointer-events: auto; transition: opacity 0.45s ease, max-height 0.45s ease, margin 0.45s ease; margin-bottom: 1rem; }
        .step-indicator { font-size: 0.85rem; color: #999; margin-top: 0.25rem; }
        .form-group label::after { content: " "; }
        .expense-flow-summary { display: grid; gap: 10px; margin-bottom: 1rem; }
        .expense-flow-pill { display: none; align-items: center; justify-content: space-between; padding: 0.75rem 0.9rem; border-radius: 10px; background: #fff8e1; border: 1px solid #ffe08a; }
        .expense-flow-pill.is-visible { display: flex; }
        .expense-flow-pill-label { display: block; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.04em; color: #8a6d3b; }
        .expense-flow-pill-value { display: block; color: #2f2f2f; font-weight: 600; }
        .expense-flow-edit { border: 0; background: transparent; color: #856404; font-size: 0.85rem; font-weight: 600; }
        .expense-step-card { border: 1px dashed #f1c40f; border-radius: 12px; background: linear-gradient(180deg, #fffdf6 0%, #ffffff 100%); padding: 1rem; }
        .expense-step-title { margin-bottom: 0.35rem; font-size: 1rem; font-weight: 700; color: #6b4f00; }
        .expense-step-help { margin-bottom: 0.9rem; color: #8c8c8c; font-size: 0.9rem; }
        .client-filter-wrap { margin-bottom: .6rem; }
        .client-filter-input { border-radius: 10px; border: 1px solid #ecd38a; background: #fffdf5; }
        .client-filter-input:focus { border-color: #d6a800; box-shadow: 0 0 0 .15rem rgba(214, 168, 0, .15); }
        #client_select option { white-space: pre; }
    </style>
@endpush

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card card-warning card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-file-invoice-dollar mr-1"></i> Registrar Cuenta por Pagar</h3>
                </div>
                <form method="POST" action="{{ route('accounts-payable.store') }}">
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
                        </div>

                        <div class="form-group expense-step-card">
                            <div class="expense-step-title">Cliente</div>
                            <div class="expense-step-help">Selecciona el cliente de la cuenta por pagar.</div>
                            <label>Cliente *</label>
                            <div class="client-filter-wrap" id="client_filter_wrap">
                                <input type="text" id="client_filter_input" class="form-control client-filter-input" placeholder="Buscar cliente por nombre o teléfono...">
                            </div>
                            <select id="client_select" name="client_id" class="form-control @error('client_id') is-invalid @enderror" required size="1">
                                <option value="">Seleccionar cliente...</option>
                                @foreach ($clients as $client)
                                    <option value="{{ $client->id }}" data-company="{{ $client->company_id ?? '' }}" data-search="{{ trim(collect([$client->name, $client->phone, $client->whatsapp, $client->email, $client->address])->filter()->implode(' ')) }}" {{ old('client_id', request('client_id')) == $client->id ? 'selected' : '' }}>
                                        {{ $client->name }}&nbsp;&nbsp;{{ $client->phone ? "({$client->phone})" : '' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('client_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group expense-step-card">
                            <div class="expense-step-title">Empresa</div>
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
                            @error('company_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-row expense-step-card">
                            <div class="form-group col-md-6">
                                <label>Monto Total ($) *</label>
                                <input type="number" id="total_amount_input" name="total_amount" step="0.01" min="0.01" class="form-control @error('total_amount') is-invalid @enderror" value="{{ old('total_amount') }}" required>
                                @error('total_amount')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="form-group col-md-6">
                                <label>Concepto *</label>
                                <input type="text" id="concept_field" name="concept" class="form-control @error('concept') is-invalid @enderror" value="{{ old('concept') }}" required>
                                @error('concept')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-warning"><i class="fas fa-save mr-1"></i> Registrar Cuenta</button>
                        <a href="{{ route('accounts-payable.index') }}" class="btn btn-secondary ml-2">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
