@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Reporte Diario - ' . \Carbon\Carbon::parse($date)->format('d/m/Y'))

@push('styles')
    <style>
        .report-table th {
            background-color: #e9ecef;
        }

        .closing-card {
            border-left: 4px solid #007bff;
        }

        .closing-card .closing-row {
            padding: 6px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .closing-card .closing-row:last-child {
            border-bottom: none;
        }

        .closing-total {
            background: #1a1a2e;
            color: #ffc107;
            font-size: 1.2rem;
            border-radius: 6px;
            padding: 10px 15px;
        }
    </style>
@endpush

@section('content')

    <!-- Date Picker -->
    <div class="row mb-3">
        <div class="col-md-7">
            <form method="GET" action="{{ route('dashboard') }}" class="form-row">
                <div class="col-md-5 mb-2 mb-md-0">
                    <input type="date" name="date" class="form-control" value="{{ $date }}">
                </div>
                @if (auth()->user()->isAdmin())
                    <div class="col-md-5 mb-2 mb-md-0">
                        <select name="branch_id" class="form-control">
                            <option value="">Todas las sucursales</option>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}"
                                    {{ (string) $branchId === (string) $branch->id ? 'selected' : '' }}>
                                    {{ $branch->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="col-md-2">
                    <button class="btn btn-primary btn-block"><i class="fas fa-search"></i></button>
                </div>
            </form>
        </div>
        <div class="col-md-5 text-right">
            <a href="{{ route('daily-closings.create', ['date' => $date]) }}" class="btn btn-info">
                <i class="fas fa-cash-register mr-1"></i> Hacer Cierre de Caja
            </a>

        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row justify-content-start">

        <div class="col-lg-4 col-md-6">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>${{ number_format($totalExpenses, 2) }}</h3>
                    <p>Total Gastos/Débitos</p>
                </div>
                <div class="icon"><i class="fas fa-arrow-down"></i></div>
                <a href="{{ route('expenses.index', ['status' => 'active']) }}" class="small-box-footer">
                    Ver débitos <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
        <div class="col-lg-4 col-md-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>${{ number_format($totalOtherIncomes, 2) }}</h3>
                    <p>Otros Ingresos (cobrando Fiados)</p>
                </div>
                <div class="icon"><i class="fas fa-hand-holding-usd"></i></div>
                <a href="{{ route('other-incomes.index', ['date' => $date]) }}" class="small-box-footer">
                    Ver otros ingresos <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Debitos del dia -->
        <div class="col-lg-7">
            <div class="card card-outline card-danger">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-minus-circle mr-1"></i> Débitos del Día
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('expenses.create') }}" class="btn btn-xs btn-danger">
                            <i class="fas fa-plus"></i> Nuevo
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm table-hover mb-0">
                        <tbody>
                            @forelse($debits as $debit)
                                <tr>
                                    <td>
                                        {{ $debit->concept }}
                                        <br><small class="text-muted">{{ $debit->client?->name ?? 'Sin cliente' }}</small>
                                    </td>
                                    <td class="text-right text-danger font-weight-bold">
                                        ${{ number_format($debit->total_amount, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="text-center text-muted py-3">Sin débitos registrados</td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if ($debits->count() > 0)
                            <tfoot>
                                <tr class="table-danger">
                                    <td><strong>TOTAL DÉBITOS</strong></td>
                                    <td class="text-right"><strong>${{ number_format($totalExpenses, 2) }}</strong></td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>

        <!-- Cierre de Caja + Otros Ingresos -->
        <div class="col-lg-5">
            <!-- Otros Ingresos -->
            <div class="card card-outline card-success">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-plus-circle mr-1"></i> Otros Ingresos
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('other-incomes.index', ['date' => $date]) }}" class="btn btn-xs btn-success">
                            <i class="fas fa-plus"></i> Agregar
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm table-hover mb-0">
                        <tbody>
                            @forelse($otherIncomes as $income)
                                <tr>
                                    <td>
                                        {{ $income->description }}
                                        @if ($income->client)
                                            <br><small class="text-muted">{{ $income->client->name }}</small>
                                        @endif
                                    </td>
                                    <td class="text-right text-success font-weight-bold">
                                        ${{ number_format($income->amount, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="text-center text-muted py-3">Sin ingresos registrados</td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if ($otherIncomes->count() > 0)
                            <tfoot>
                                <tr class="table-success">
                                    <td><strong>TOTAL</strong></td>
                                    <td class="text-right"><strong>${{ number_format($totalOtherIncomes, 2) }}</strong>
                                    </td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>

            <!-- Total Cierre de Caja -->
            <div class="card card-outline card-dark">
                <div class="card-header">
                    <h3 class="card-title font-weight-bold">
                        <i class="fas fa-cash-register mr-1"></i> Total Cierre de Caja
                    </h3>
                </div>
                <div class="card-body">
                    <div class="closing-row d-flex justify-content-between">
                        <span>TOTAL INGRESOS</span>
                        <strong class="text-success">${{ number_format($totalIncomes, 2) }}</strong>
                    </div>
                    <div class="closing-row d-flex justify-content-between">
                        <span>(-) GASTOS / DÉBITOS</span>
                        <strong class="text-danger">- ${{ number_format($totalExpenses, 2) }}</strong>
                    </div>
                    <div class="closing-row d-flex justify-content-between bg-light rounded px-2">
                        <span class="font-weight-bold">VALOR TOTAL</span>
                        <strong class="text-primary">${{ number_format($valueTotal, 2) }}</strong>
                    </div>
                    <div class="closing-row d-flex justify-content-between">
                        <span>(+) OTROS INGRESOS</span>
                        <strong class="text-success">+ ${{ number_format($totalOtherIncomes, 2) }}</strong>
                    </div>
                    <div class="closing-row d-flex justify-content-between bg-light rounded px-2">
                        <span class="font-weight-bold">SUMA TOTAL</span>
                        <strong class="text-primary">${{ number_format($sumTotal, 2) }}</strong>
                    </div>
                    <div class="closing-row d-flex justify-content-between">
                        <span>VALOR EXISTENTE</span>
                        <strong>${{ number_format($existingValue, 2) }}</strong>
                    </div>
                    <div class="closing-row d-flex justify-content-between">
                        <span>DIFERENCIA</span>
                        <strong class="{{ $difference >= 0 ? 'text-success' : 'text-danger' }}">
                            ${{ number_format(abs($difference), 2) }}
                        </strong>
                    </div>
                    <div class="closing-total d-flex justify-content-between mt-3">
                        <span>TOTAL FINAL</span>
                        <strong class="{{ $difference >= 0 ? 'text-success' : 'text-danger' }}">
                            ${{ number_format($difference, 2) }}
                        </strong>
                    </div>

                    @if (!$closing)
                        <div class="mt-3 text-center">
                            <a href="{{ route('daily-closings.create', ['date' => $date]) }}"
                                class="btn btn-info btn-block">
                                <i class="fas fa-save mr-1"></i> Guardar Cierre del Día
                            </a>
                        </div>
                    @else
                        <div class="mt-2 text-center">
                            <span class="badge badge-success badge-pill px-3 py-2">
                                <i class="fas fa-check-circle mr-1"></i> Cierre guardado
                            </span>
                            <a href="{{ route('daily-closings.show', $closing) }}"
                                class="btn btn-sm btn-outline-secondary mt-1">
                                Ver reporte completo
                            </a>
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>

@endsection
