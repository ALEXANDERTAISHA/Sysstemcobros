@extends('layouts.app')
@section('title', 'Nuevo Cliente')
@section('page-title', 'Registrar Nuevo Cliente')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('clients.index') }}">Clientes</a></li>
    <li class="breadcrumb-item active">Nuevo</li>
@endsection

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card card-success card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-user-plus mr-1"></i> Datos del Cliente</h3>
                </div>
                <form method="POST" action="{{ route('clients.store') }}">
                    @csrf
                    <div class="card-body">
                        <div class="form-group">
                            <label>Nombre completo *</label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                value="{{ old('name') }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label>Correo electrónico</label>
                            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                                value="{{ old('email') }}" placeholder="cliente@correo.com">
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Teléfono</label>
                                    <input type="tel" name="phone" class="form-control" value="{{ old('phone') }}"
                                        inputmode="numeric" pattern="[0-9]*" maxlength="15" autocomplete="tel"
                                        placeholder="Solo números">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label><i class="fab fa-whatsapp text-success mr-1"></i>WhatsApp</label>
                                    <input type="hidden" name="whatsapp" id="whatsapp_hidden"
                                        value="{{ old('whatsapp') }}">
                                    <input type="tel" id="whatsapp_input"
                                        class="form-control @error('whatsapp') is-invalid @enderror"
                                        value="{{ old('whatsapp') }}" inputmode="numeric" autocomplete="tel"
                                        placeholder="WhatsApp USA: 5551234567">
                                    @error('whatsapp')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">WhatsApp de Estados Unidos, escribe el número sin el código +1.</small>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Dirección</label>
                            <input type="text" name="address" class="form-control" value="{{ old('address') }}">
                        </div>
                        <div class="form-group">
                            <label>Notas</label>
                            <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
                        </div>
                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="is_active" name="is_active"
                                    value="1" {{ old('is_active', 1) ? 'checked' : '' }}>
                                <label class="custom-control-label" for="is_active">Cliente activo</label>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-success"><i class="fas fa-save mr-1"></i> Guardar</button>
                        <a href="{{ route('clients.index') }}" class="btn btn-secondary ml-2">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intl-tel-input@24.6.1/build/css/intlTelInput.css">
    <style>
        .iti {
            width: 100%;
        }

        .iti__country-list {
            z-index: 1060;
        }
    </style>
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/intl-tel-input@24.6.1/build/js/intlTelInput.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form[action="{{ route('clients.store') }}"]');
            const whatsappInput = document.getElementById('whatsapp_input');
            const whatsappHidden = document.getElementById('whatsapp_hidden');

            if (!form || !whatsappInput || !whatsappHidden) {
                return;
            }

            const iti = window.intlTelInput(whatsappInput, {
                initialCountry: 'us',
                onlyCountries: ['us'],
                separateDialCode: true,
                nationalMode: true,
                autoPlaceholder: 'aggressive',
                formatOnDisplay: true,
                strictMode: false,
                loadUtils: () => import(
                    'https://cdn.jsdelivr.net/npm/intl-tel-input@24.6.1/build/js/utils.js'),
            });

            const oldValue = whatsappHidden.value.trim();
            if (oldValue) {
                iti.setNumber(oldValue);
            }

            form.addEventListener('submit', function() {
                const typedNumber = whatsappInput.value.trim();

                if (!typedNumber) {
                    whatsappHidden.value = '';
                    return;
                }

                const international = iti.getNumber();
                whatsappHidden.value = international || typedNumber;
            });
        });
    </script>
@endpush
