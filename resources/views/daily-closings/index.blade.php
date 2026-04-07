@extends('layouts.app')
@section('title', 'Cierre de Caja')
@section('page-title', 'Cierres de Caja')
@section('breadcrumb')<li class="breadcrumb-item active">Cierre de Caja</li>@endsection

@section('content')
    <div class="row mb-3">
        <div class="col-md-8">
            <a href="{{ route('daily-closings.create') }}" class="btn btn-success">
                <i class="fas fa-plus mr-1"></i> Nuevo Cierre
            </a>
            <a href="{{ route('daily-closings.create', ['date' => today()->toDateString()]) }}" class="btn btn-primary ml-2">
                <i class="fas fa-calendar-day mr-1"></i> Cerrar Hoy
            </a>
        </div>
    </div>

    <div class="card card-outline card-success">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-cash-register mr-1"></i> Historial de Cierres</h3>
        </div>
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead class="thead-dark">
                    <tr>
                        <th>Fecha</th>
                        <th class="text-right">Ingresos</th>
                        <th class="text-right">Gastos</th>
                        <th class="text-right">Valor Total</th>
                        <th class="text-right">Otros Ing.</th>
                        <th class="text-right">Suma Total</th>
                        <th class="text-right">V. Existente</th>
                        <th class="text-right">Diferencia</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($closings as $c)
                        <tr>
                            <td><strong>{{ $c->closing_date->format('d/m/Y') }}</strong></td>
                            <td class="text-right text-success">${{ number_format($c->total_incomes, 2) }}</td>
                            <td class="text-right text-danger">${{ number_format($c->total_expenses, 2) }}</td>
                            <td class="text-right">${{ number_format($c->value_total, 2) }}</td>
                            <td class="text-right text-info">${{ number_format($c->other_incomes_total, 2) }}</td>
                            <td class="text-right font-weight-bold">${{ number_format($c->sum_total, 2) }}</td>
                            <td class="text-right">${{ number_format($c->existing_value, 2) }}</td>
                            <td
                                class="text-right {{ $c->difference >= 0 ? 'text-success' : 'text-danger' }} font-weight-bold">
                                {{ $c->difference >= 0 ? '+' : '-' }}${{ number_format(abs($c->difference), 2) }}
                            </td>
                            <td class="text-center"> <a
                                    href="{{ route('reports.export-pdf', ['date_from' => $c->closing_date->toDateString(), 'date_to' => $c->closing_date->toDateString()]) }}"
                                    target="_blank" class="btn btn-xs btn-secondary" title="Imprimir PDF">
                                    <i class="fas fa-print"></i>
                                </a> <a href="{{ route('daily-closings.show', $c) }}" class="btn btn-xs btn-info">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <form method="POST" action="{{ route('daily-closings.destroy', $c) }}" class="d-inline"
                                    onsubmit="return confirm('¿Eliminar este cierre?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-xs btn-danger"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">Sin cierres registrados</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($closings->hasPages())
            <div class="card-footer">{{ $closings->links() }}</div>
        @endif
    </div>
@endsection
