@extends('layouts.app')
@section('title', 'WhatsApp')
@section('page-title', 'Notificaciones WhatsApp')
@section('breadcrumb')<li class="breadcrumb-item active">WhatsApp</li>@endsection

@section('content')
    <div class="row">
        <div class="col-lg-5">
            <div class="card card-success card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fab fa-whatsapp mr-1"></i> Enviar Mensaje</h3>
                </div>
                <form method="POST" action="{{ route('whatsapp.send') }}">
                    @csrf
                    <div class="card-body">
                        @if (!$hasApiKey)
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle mr-1"></i>
                                <strong>Configuración API:</strong> Para envío automático, configura
                                <code>META_WHATSAPP_TOKEN</code> y <code>META_WHATSAPP_PHONE_NUMBER_ID</code>
                                (recomendado) o <code>CALLMEBOT_API_KEY</code> en el archivo <code>.env</code>.
                                <a href="https://developers.facebook.com/docs/whatsapp/cloud-api/" target="_blank"
                                    class="alert-link">Meta Cloud API</a>
                                o
                                <a href="https://www.callmebot.com/blog/free-api-whatsapp-messages/" target="_blank"
                                    class="alert-link">CallMeBot</a>.
                                Sin API, se generará un enlace de WhatsApp Web.
                            </div>
                        @else
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle mr-1"></i>
                                API de WhatsApp configurada. Los mensajes se intentarán enviar automáticamente.
                            </div>
                        @endif

                        <div class="form-group">
                            <label>Seleccionar Cliente (opcional)</label>
                            <select id="client_select" class="form-control">
                                <option value="">— Número manual —</option>
                                @foreach ($clients as $client)
                                    @if ($client->whatsapp)
                                        <option value="{{ $client->whatsapp }}" data-name="{{ $client->name }}">
                                            {{ $client->name }} ({{ $client->whatsapp }})
                                        </option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Número WhatsApp *</label>
                            <input type="hidden" name="phone" id="phone_hidden" value="{{ old('phone') }}">
                            <input type="tel" id="phone_input" class="form-control @error('phone') is-invalid @enderror"
                                placeholder="Número de teléfono" value="{{ old('phone') }}" required>
                            <small class="form-text text-muted">Selecciona país y escribe el número sin el código.</small>
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label>Nombre del destinatario</label>
                            <input type="text" name="name" id="name_input" class="form-control"
                                value="{{ old('name') }}" placeholder="Ej: Juan Pérez">
                        </div>
                        <div class="form-group">
                            <label>Mensaje *</label>
                            <textarea name="message" id="message_input" class="form-control @error('message') is-invalid @enderror" rows="4"
                                maxlength="1000" required>{{ old('message') }}</textarea>
                            <small class="text-muted"><span id="char_count">0</span>/1000 caracteres</small>
                            @error('message')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Mensajes rápidos -->
                        <div class="form-group">
                            <label>Mensajes Rápidos</label>
                            <div class="d-flex flex-wrap gap-1">
                                <button type="button" class="btn btn-xs btn-outline-success mb-1"
                                    onclick="setMsg('Hola, le recordamos que tiene un pago pendiente. Por favor contactarse. Gracias.')">
                                    Recordatorio de pago
                                </button>
                                <button type="button" class="btn btn-xs btn-outline-primary mb-1"
                                    onclick="setMsg('Su giro ha sido enviado exitosamente. Gracias por preferirnos.')">
                                    Giro enviado
                                </button>
                                <button type="button" class="btn btn-xs btn-outline-warning mb-1"
                                    onclick="setMsg('Su transacción está siendo procesada. Le notificaremos cuando esté lista.')">
                                    Procesando giro
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-success btn-block">
                            <i class="fab fa-whatsapp mr-1"></i> Enviar Mensaje
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card card-outline card-secondary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-history mr-1"></i> Historial de Notificaciones</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="thead-dark">
                            <tr>
                                <th>Teléfono</th>
                                <th>Mensaje</th>
                                <th class="text-center">Estado</th>
                                <th>Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($notifications as $notif)
                                <tr>
                                    <td>
                                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $notif->recipient_phone) }}"
                                            target="_blank" class="text-success">
                                            <i class="fab fa-whatsapp mr-1"></i>{{ $notif->recipient_phone }}
                                        </a>
                                        @if ($notif->recipient_name)
                                            <br><small class="text-muted">{{ $notif->recipient_name }}</small>
                                        @endif
                                    </td>
                                    <td><small>{{ Str::limit($notif->message, 60) }}</small></td>
                                    <td class="text-center">
                                        <span
                                            class="badge badge-{{ $notif->status === 'sent' ? 'success' : ($notif->status === 'failed' ? 'danger' : 'warning') }}">
                                            {{ $notif->status === 'sent' ? 'Enviado' : ($notif->status === 'failed' ? 'Fallido' : 'Pendiente') }}
                                        </span>
                                    </td>
                                    <td><small>{{ $notif->created_at->format('d/m/Y H:i') }}</small></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">Sin notificaciones</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($notifications->hasPages())
                    <div class="card-footer">{{ $notifications->links() }}</div>
                @endif
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
        const clientSelect = document.getElementById('client_select');
        const phoneInput = document.getElementById('phone_input');
        const phoneHidden = document.getElementById('phone_hidden');
        const form = document.querySelector('form[action="{{ route('whatsapp.send') }}"]');

        const iti = window.intlTelInput(phoneInput, {
            initialCountry: 'ec',
            preferredCountries: ['ec', 'co', 'pe', 'mx', 'us', 'es'],
            separateDialCode: true,
            nationalMode: true,
            autoPlaceholder: 'aggressive',
            formatOnDisplay: true,
            strictMode: false,
            loadUtils: () => import('https://cdn.jsdelivr.net/npm/intl-tel-input@24.6.1/build/js/utils.js'),
        });

        if (phoneHidden.value.trim()) {
            iti.setNumber(phoneHidden.value.trim());
        }

        clientSelect.addEventListener('change', function() {
            const phone = this.value;
            const name = this.options[this.selectedIndex].dataset.name || '';

            if (phone) {
                iti.setNumber(phone);
                phoneHidden.value = phone;
                document.getElementById('name_input').value = name;
            }
        });

        form.addEventListener('submit', function() {
            const typedNumber = phoneInput.value.trim();

            if (!typedNumber) {
                phoneHidden.value = '';
                return;
            }

            const international = iti.getNumber();
            phoneHidden.value = international || typedNumber;
        });

        document.getElementById('message_input').addEventListener('input', function() {
            document.getElementById('char_count').textContent = this.value.length;
        });

        function setMsg(text) {
            document.getElementById('message_input').value = text;
            document.getElementById('char_count').textContent = text.length;
        }
    </script>
@endpush
