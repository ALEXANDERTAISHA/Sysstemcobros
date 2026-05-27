@extends('layouts.app')
@section('title', 'Cuentas por Pagar')
@section('page-title', 'Cuentas por Pagar')
@section('breadcrumb')<li class="breadcrumb-item active">Cuentas por Pagar</li>@endsection

@section('content')
    <div class="row mb-3">
        <div class="col-md-8">
            <form method="GET" class="form-inline">
                <input type="text" name="search" class="form-control mr-2" placeholder="Buscar cliente, empresa o concepto..."
                    value="{{ $search }}">
                <input type="date" name="date" class="form-control mr-2" value="{{ $date ?? '' }}">
                @if(auth()->user()->isSuperAdmin())
                    <select name="branch_id" class="form-control mr-2">
                        <option value="">Todas las sucursales</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" {{ (string) $branchId === (string) $branch->id ? 'selected' : '' }}>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </select>
                @endif
                <select name="status" class="form-control mr-2">
                    <option value="all" {{ $status === 'all' ? 'selected' : '' }}>Todos</option>
                    <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Activos</option>
                    <option value="partial" {{ $status === 'partial' ? 'selected' : '' }}>Parcial</option>
                    <option value="paid" {{ $status === 'paid' ? 'selected' : '' }}>Pagados</option>
                </select>
                <button class="btn btn-primary mr-1"><i class="fas fa-search"></i></button>
                <a href="{{ route('accounts-payable.index') }}" class="btn btn-secondary"><i class="fas fa-redo"></i></a>
            </form>
        </div>
        <div class="col-md-4 text-right">
            <a href="{{ route('accounts-payable.create') }}" class="btn btn-warning mr-2">
                <i class="fas fa-plus mr-1"></i> Nueva Cuenta
            </a>
            <button id="btn-cobrar-total" class="btn btn-success" style="display:none;">
                <i class="fas fa-cash-register mr-1"></i> Cobrar total
            </button>
        </div>
    <!-- Modal Cobrar Total -->
    <div class="modal fade" id="modalCobrarTotal" tabindex="-1" role="dialog" aria-labelledby="modalCobrarTotalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalCobrarTotalLabel">Cobrar total al cliente</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="form-cobrar-total" method="POST" action="{{ route('accounts-payable.index') }}/cobrar-total">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-2">
                            <strong>Cliente:</strong> <span id="modal-cliente-nombre"></span>
                        </div>
                        <div class="mb-2">
                            <strong>Monto a Pagar ($):</strong> <span id="modal-monto-total"></span>
                        </div>
                        <input type="hidden" name="client_id" id="modal-client-id">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">Confirmar cobro</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // --- Botón Cobrar total ---
        const searchInput = document.querySelector('input[name="search"]');
        const cobrarBtn = document.getElementById('btn-cobrar-total');
        let lastClientId = null;
        function getVisibleClientIdAndName() {
            // Busca el primer cliente visible en la tabla
            const rows = document.querySelectorAll('table tbody tr');
            let clientId = null, clientName = null, total = 0;
            rows.forEach(row => {
                if (row.querySelector('td a[href*="clients/"]')) {
                    const link = row.querySelector('td a[href*="clients/"]');
                    const name = link.textContent.trim();
                    const href = link.getAttribute('href');
                    const match = href.match(/clients\/(\d+)/);
                    if (match) {
                        const id = match[1];
                        if (!clientId) {
                            clientId = id;
                            clientName = name;
                        }
                    }
                }
            });
            // Suma el saldo de todas las filas del mismo cliente
            if (clientId) {
                rows.forEach(row => {
                    const link = row.querySelector('td a[href*="clients/' + clientId + '"]');
                    if (link) {
                        const saldoTd = row.querySelector('td.text-right.font-weight-bold');
                        if (saldoTd) {
                            const saldo = parseFloat(saldoTd.textContent.replace(/[^\d\.]/g, ''));
                            total += saldo;
                        }
                    }
                });
            }
            return { clientId, clientName, total };
        }
        function updateCobrarBtnVisibility() {
            const { clientId } = getVisibleClientIdAndName();
            if (searchInput && searchInput.value.trim() && clientId) {
                cobrarBtn.style.display = '';
                lastClientId = clientId;
            } else {
                cobrarBtn.style.display = 'none';
                lastClientId = null;
            }
        }
        if (searchInput) {
            searchInput.addEventListener('input', updateCobrarBtnVisibility);
            updateCobrarBtnVisibility();
        }
        cobrarBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const { clientId, clientName, total } = getVisibleClientIdAndName();
            if (!clientId) return;
            document.getElementById('modal-cliente-nombre').textContent = clientName;
            document.getElementById('modal-monto-total').textContent = total.toFixed(2);
            document.getElementById('modal-client-id').value = clientId;
            $('#modalCobrarTotal').modal('show');
        });
    });
    </script>
    @endpush
    </div>

    <div class="card card-outline card-warning">
        <div class="card-body p-0">
            <table class="table table-hover table-sm mb-0">
                <thead class="thead-dark">
                    <tr>
                        <th>Fecha</th>
                        @if(auth()->user()->isSuperAdmin())
                            <th>Sucursal</th>
                        @endif
                        <th>Cliente</th>
                        <th>Empresa</th>
                        <th>Concepto</th>
                        <th class="text-right">Total</th>
                        <th class="text-right">Saldo</th>
                        <th class="text-center">Estado</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($accounts as $account)
                        <tr class="{{ $account->status === 'paid' ? 'table-success' : ($account->due_date && $account->due_date->isPast() && $account->status !== 'paid' ? 'table-danger' : '') }}">
                            <td>{{ $account->issued_date->format('d/m/Y') }}</td>
                            @if(auth()->user()->isSuperAdmin())
                                <td>{{ $account->branch?->name ?? 'Sin sucursal' }}</td>
                            @endif
                            <td><a href="{{ route('clients.show', $account->client) }}">{{ $account->client->name }}</a></td>
                            <td>{{ $account->company?->name ?? '-' }}</td>
                            <td>{{ $account->concept }}</td>
                            <td class="text-right">${{ number_format($account->total_amount, 2) }}</td>
                            <td class="text-right font-weight-bold {{ $account->balance > 0 ? 'text-danger' : 'text-success' }}">
                                ${{ number_format($account->balance, 2) }}
                            </td>
                            <td class="text-center">
                                <span class="badge badge-{{ $account->status === 'paid' ? 'success' : ($account->status === 'partial' ? 'info' : 'warning') }}">
                                    {{ $account->status === 'paid' ? 'Pagado' : ($account->status === 'partial' ? 'Parcial' : 'Activo') }}
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="btn-group" style="gap: 0.5rem;">
                                    <a href="{{ route('accounts-payable.show', $account) }}" class="btn btn-info" title="Ver" style="font-size: 1.35rem; padding: 0.35rem 0.7rem;"><i class="fas fa-eye"></i></a>
                                    <form method="POST" action="{{ route('accounts-payable.destroy', $account) }}" class="d-inline"
                                        onsubmit="return confirm('¿Eliminar esta cuenta por pagar?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-danger" title="Eliminar" style="font-size: 1.35rem; padding: 0.35rem 0.7rem;"><i class="fas fa-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ auth()->user()->isSuperAdmin() ? '9' : '8' }}" class="text-center text-muted py-4">
                                Sin cuentas por pagar registradas
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($accounts->hasPages())
            <div class="card-footer bg-white border-top-0">
                <nav aria-label="Paginacion de cuentas por pagar" class="d-flex justify-content-center">
                    {{ $accounts->withQueryString()->links('vendor.pagination.bootstrap-4') }}
                </nav>
            </div>
        @endif
    </div>
@endsection
