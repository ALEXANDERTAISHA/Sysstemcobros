<div class="table-responsive">
    <table class="table table-bordered table-hover table-sm mb-0">
        <thead class="thead-dark">
            <tr>
                <th>Fecha</th>
                <th>Empresa</th>
                <th>Cliente</th>
                <th>Descripción</th>
                <th class="text-right">Monto</th>
            </tr>
        </thead>
        <tbody>
            @forelse($specialTransfers as $st)
            @php
                $specialNote = trim((string) ($st->notes ?: ($st->credit?->notes ?? '')));
            @endphp
            <tr>
                <td>{{ $st->income_date->format('d/m/Y') }}</td>
                <td>{{ $st->credit?->company?->name ?? '-' }}</td>
                <td>
                    @if($st->client)
                        <span title="{{ $specialNote !== '' ? 'Nota: ' . $specialNote : '' }}">{{ $st->client->name }}</span>
                    @else
                        -
                    @endif
                </td>
                <td>{{ $st->description }}</td>
                <td class="text-right">${{ number_format($st->amount, 2) }}</td>
            </tr>
            @empty
            <tr><td colspan="5" class="text-center text-muted">Sin resultados.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
