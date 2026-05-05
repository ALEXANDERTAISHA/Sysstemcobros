<tbody id="pending_debts_tbody">
@forelse($pendingDebts as $debt)
    @php
        $isOverdue = $debt->due_date && $debt->due_date->isPast();
        $diffDays = $debt->due_date ? (int) now()->startOfDay()->diffInDays($debt->due_date->startOfDay(), false) : null;
    @endphp
    <tr class="filterable-pending-row{{ $isOverdue ? ' table-danger' : '' }}">
        <td>{{ $debt->granted_date?->format('d/m/Y') ?? '-' }}</td>
        @if(auth()->user()->isAdmin())
            <td>{{ $debt->branch?->name ?? 'Sin sucursal' }}</td>
        @endif
        <td>
            @if(auth()->user()->isAdmin())
                <a href="{{ route('clients.show', $debt->client) }}">{{ $debt->client->name }}</a>
            @else
                {{ $debt->client->name }}
            @endif
        </td>
        <td>{{ $debt->company?->name ?? '-' }}</td>
        <td>{{ $debt->due_date?->format('d/m/Y') ?? 'Sin fecha' }}</td>
        <td class="text-center">
            @if($diffDays === null)
                <span class="text-muted">—</span>
            @elseif($diffDays < 0)
                <span class="badge badge-danger">{{ abs($diffDays) }}d vencido</span>
            @elseif($diffDays === 0)
                <span class="badge badge-warning">Hoy</span>
            @else
                <span class="badge badge-info">{{ $diffDays }}d</span>
            @endif
        </td>
        <td class="text-right font-weight-bold text-danger">${{ number_format($debt->balance, 2) }}</td>
        <td class="text-center">
            <span class="badge badge-{{ $isOverdue ? 'danger' : 'warning' }}">
                {{ $isOverdue ? 'Vencido' : 'Pendiente' }}
            </span>
        </td>
        <td class="text-center">
            <button class="btn btn-success px-3 py-1 font-weight-bold"
                style="font-size:1em;"
                onclick="openCollectModal({{ $debt->id }}, '{{ addslashes($debt->client->name) }}', '{{ addslashes($debt->concept) }}', {{ $debt->balance }})">
                <i class="fas fa-dollar-sign mr-1"></i>Cobrar
            </button>
            <button class="btn btn-primary px-3 py-1 font-weight-bold ml-2"
                style="font-size:1em;"
                onclick="openCollectZelleModal({{ $debt->id }}, '{{ addslashes($debt->client->name) }}', '{{ addslashes($debt->concept) }}', {{ $debt->balance }})">
                <i class="fas fa-university mr-1"></i>Cobrar vía ZELLE
            </button>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="{{ auth()->user()->isAdmin() ? '9' : '8' }}" class="text-center text-muted py-4">Sin débitos pendientes para seguimiento</td>
    </tr>
@endforelse
<tr id="pending_debts_no_results" style="display: none;">
    <td colspan="{{ auth()->user()->isAdmin() ? '9' : '8' }}" class="text-center text-muted py-4">Sin resultados en débitos pendientes.</td>
</tr>
</tbody>
