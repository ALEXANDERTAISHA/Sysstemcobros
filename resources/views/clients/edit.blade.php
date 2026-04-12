@extends('layouts.app')
@section('title', 'Editar Cliente')
@section('page-title', 'Editar Cliente')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('clients.index') }}">Clientes</a></li>
    <li class="breadcrumb-item active">Editar</li>
@endsection

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card card-warning card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-edit mr-1"></i> Editar: {{ $client->name }}</h3>
                </div>
                <form method="POST" action="{{ route('clients.update', $client) }}">
                    @csrf @method('PUT')
                    <div class="card-body">
                        <div class="form-group">
                            <label>Nombre completo *</label>
                            <input type="text" name="name" class="form-control" value="{{ $client->name }}" required>
                        </div>
                        <div class="form-group">
                            <label>Correo electrónico</label>
                            <input type="email" name="email" class="form-control" value="{{ old('email', $client->email) }}"
                                placeholder="cliente@correo.com">
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Teléfono</label>
                                    <input type="tel" name="phone" class="form-control" value="{{ $client->phone }}"
                                        inputmode="numeric" pattern="[0-9]*" maxlength="15" autocomplete="tel"
                                        placeholder="Solo números">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label><i class="fab fa-whatsapp text-success mr-1"></i>WhatsApp</label>
                                    <input type="hidden" name="whatsapp" id="whatsapp_hidden"
                                        value="{{ old('whatsapp', $client->whatsapp) }}">
                                    <input type="tel" id="whatsapp_input"
                                        class="form-control @error('whatsapp') is-invalid @enderror"
                                        value="{{ old('whatsapp', $client->whatsapp) }}" inputmode="numeric" autocomplete="tel"
                                        placeholder="Numero de telefono">
                                    @error('whatsapp')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">Selecciona pais y escribe el numero sin el
                                        codigo.</small>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Dirección</label>
                            <input type="text" name="address" class="form-control" value="{{ $client->address }}">
                        </div>
                        <div class="form-group">
                            <label>Notas</label>
                            <textarea name="notes" class="form-control" rows="2">{{ $client->notes }}</textarea>
                        </div>
                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="is_active" name="is_active"
                                    value="1" {{ $client->is_active ? 'checked' : '' }}>
                                <label class="custom-control-label" for="is_active">Cliente activo</label>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-warning"><i class="fas fa-save mr-1"></i> Actualizar</button>
                        <a href="{{ route('clients.show', $client) }}" class="btn btn-secondary ml-2">Cancelar</a>
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
            const form = document.querySelector('form[action="{{ route('clients.update', $client) }}"]');
            const whatsappInput = document.getElementById('whatsapp_input');
            const whatsappHidden = document.getElementById('whatsapp_hidden');

            if (!form || !whatsappInput || !whatsappHidden) {
                return;
            }

            const iti = window.intlTelInput(whatsappInput, {
                initialCountry: 'us',
                preferredCountries: ['us', 'ec', 'co', 'pe', 'mx', 'es'],
                separateDialCode: true,
                nationalMode: true,
                autoPlaceholder: 'aggressive',
                formatOnDisplay: true,
                strictMode: false,
                loadUtils: () => import(
                    'https://cdn.jsdelivr.net/npm/intl-tel-input@24.6.1/build/js/utils.js'),
            });

            const currentValue = whatsappHidden.value.trim();
            if (currentValue) {
                iti.setNumber(currentValue);
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
