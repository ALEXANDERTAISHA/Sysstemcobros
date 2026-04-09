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
            transition: opacity 0.6s ease, max-height 0.6s ease, margin 0.6s ease;
            margin: 0;
        }
        .form-group-visible {
            opacity: 1;
            max-height: 500px;
            transition: opacity 0.6s ease, max-height 0.6s ease, margin 0.6s ease;
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
                        <!-- PASO 1: CLIENTE (siempre visible) -->
                        <div class="form-group">
                            <label>Cliente * <span class="step-indicator">(Paso 1/3)</span></label>
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
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- PASO 2: EMPRESA (oculto, se muestra al seleccionar cliente) -->
                        <div id="company_section" class="form-group form-group-hidden">
                            <label>Empresa * <span class="step-indicator">(Paso 2/3)</span></label>
                            <select id="company_select" name="company_id" class="form-control @error('company_id') is-invalid @enderror">
                                <option value="">Seleccionar empresa...</option>
                                @foreach ($companies as $company)
                                    <option value="{{ $company->id }}"
                                        {{ old('company_id') == $company->id ? 'selected' : '' }}>
                                        {{ $company->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('company_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-success" id="auto_filled_message" style="display: none;">
                                <i class="fas fa-check-circle mr-1"></i>Auto-completado según cliente
                            </small>
                        </div>

                        <!-- PASO 3: CONCEPTO (oculto, se muestra al seleccionar empresa) -->
                        <div id="concept_section" class="form-group form-group-hidden">
                            <label>Concepto / Descripción * <span class="step-indicator">(Paso 3/3)</span></label>
                            <input type="text" id="concept_input" name="concept" class="form-control @error('concept') is-invalid @enderror"
                                value="{{ old('concept') }}" placeholder="Ej: Préstamo en efectivo, mercadería..."
                                required>
                            @error('concept')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- DEMÁS CAMPOS (oculto, se muestra al llenar concepto) -->
                        <div id="advanced_section" class="form-group form-group-hidden">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Monto Total ($) *</label>
                                        <input type="number" name="total_amount" step="0.01" min="0.01"
                                            class="form-control @error('total_amount') is-invalid @enderror"
                                            value="{{ old('total_amount') }}" required>
                                        @error('total_amount')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Fecha de Otorgamiento *</label>
                                        <input type="date" name="granted_date" class="form-control"
                                            value="{{ old('granted_date', today()->toDateString()) }}" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Fecha Límite de Pago *</label>
                                        <input type="date" name="due_date"
                                            class="form-control @error('due_date') is-invalid @enderror"
                                            value="{{ old('due_date') }}" required>
                                        @error('due_date')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Notas</label>
                                <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
                            </div>
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
            const clientSelect = document.getElementById('client_select');
            const companySection = document.getElementById('company_section');
            const companySelect = document.getElementById('company_select');
            const conceptSection = document.getElementById('concept_section');
            const conceptInput = document.getElementById('concept_input');
            const advancedSection = document.getElementById('advanced_section');
            const autoFilledMsg = document.getElementById('auto_filled_message');

            // Mapeo de clientes a empresas
            const clientToCompany = @json(
                $clients->mapWithKeys(fn($c) => [$c->id => $c->company_id])->toArray()
            );

            // Mostrar/ocultar secciones según el estado actual
            function updateVisibility() {
                const hasClient = clientSelect.value !== '';
                const hasCompany = companySelect.value !== '';
                const hasConcept = conceptInput.value.trim() !== '';

                // Mostrar empresa s solo si hay cliente
                if (hasClient) {
                    companySection.classList.remove('form-group-hidden');
                    companySection.classList.add('form-group-visible');
                } else {
                    companySection.classList.remove('form-group-visible');
                    companySection.classList.add('form-group-hidden');
                    autoFilledMsg.style.display = 'none';
                }

                // Mostrar concepto solo si hay empresa
                if (hasCompany) {
                    conceptSection.classList.remove('form-group-hidden');
                    conceptSection.classList.add('form-group-visible');
                } else {
                    conceptSection.classList.remove('form-group-visible');
                    conceptSection.classList.add('form-group-hidden');
                }

                // Mostrar campos avanzados solo si hay concepto
                if (hasConcept && hasCompany) {
                    advancedSection.classList.remove('form-group-hidden');
                    advancedSection.classList.add('form-group-visible');
                } else {
                    advancedSection.classList.remove('form-group-visible');
                    advancedSection.classList.add('form-group-hidden');
                }
            }

            // Al cambiar cliente, auto-llenar empresa
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

            // Actualizar visualización al cambiar empresa
            companySelect.addEventListener('change', updateVisibility);

            // Actualizar visualización al escribir concepto
            conceptInput.addEventListener('input', updateVisibility);

            // Inicializar visibilidad
            updateVisibility();
        });
    </script>
@endpush
