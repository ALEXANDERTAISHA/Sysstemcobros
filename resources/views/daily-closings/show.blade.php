@extends('layouts.app')
@section('title', 'Reporte ' . $dailyClosing->closing_date->format('d/m/Y'))
@section('page-title', 'Reporte del ' . $dailyClosing->closing_date->format('d/m/Y'))
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('daily-closings.index') }}">Cierres</a></li>
    <li class="breadcrumb-item active">{{ $dailyClosing->closing_date->format('d/m/Y') }}</li>
@endsection

@section('content')
    <div class="row mb-3">
        <div class="col">
            <a href="{{ route('reports.export-pdf', ['date_from' => $dailyClosing->closing_date->toDateString(), 'date_to' => $dailyClosing->closing_date->toDateString()]) }}"
                target="_blank" class="btn btn-secondary">
                <i class="fas fa-print mr-1"></i> Imprimir Reporte PDF
            </a>
            <a href="{{ route('daily-closings.index') }}" class="btn btn-light ml-2">
                <i class="fas fa-arrow-left mr-1"></i> Volver
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Transferencias por empresa -->
        <div class="col-lg-7">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-paper-plane mr-1"></i> TRANSFERENCIAS</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>EMPRESA</th>
                                <th class="text-center">CANT.</th>
                                <th class="text-right">MONTO $</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($transfersByCompany as $row)
                                <tr>
                                    <td>{{ $row->name ?? 'N/A' }}</td>
                                    <td class="text-center">{{ $row->transfers_count }}</td>
                                    <td class="text-right">${{ number_format((float) $row->transfers_total_amount, 2) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="table-success">
                                <td><strong>INGRESOS</strong></td>
                                <td class="text-center"><strong>{{ $transfersByCompany->sum('transfers_count') }}</strong>
                                </td>
                                <td class="text-right">
                                    <strong>${{ number_format($dailyClosing->total_incomes, 2) }}</strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Otros Ingresos -->
            <div class="card card-outline card-info">
                <div class="card-header">
                    <h3 class="card-title">OTROS INGRESOS</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        @foreach ($otherIncomes as $i => $income)
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td>{{ $income->description }} {{ $income->client ? "- {$income->client->name}" : '' }}
                                </td>
                                <td class="text-right">${{ number_format($income->amount, 2) }}</td>
                            </tr>
                        @endforeach
                        <tr class="table-info">
                            <td colspan="2"><strong>TOTAL</strong></td>
                            <td class="text-right">
                                <strong>${{ number_format($dailyClosing->other_incomes_total, 2) }}</strong></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <!-- Debitos -->
            <div class="card card-outline card-danger">
                <div class="card-header">
                    <h3 class="card-title">DÉBITOS</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        @foreach ($debits as $i => $debit)
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td>
                                    {{ $debit->concept }}
                                    @if ($debit->client)
                                        <br><small class="text-muted">{{ $debit->client->name }}</small>
                                    @endif
                                </td>
                                <td class="text-right">${{ number_format($debit->total_amount, 2) }}</td>
                            </tr>
                        @endforeach
                        <tr class="table-danger">
                            <td colspan="2"><strong>TOTAL</strong></td>
                            <td class="text-right"><strong>${{ number_format($dailyClosing->total_expenses, 2) }}</strong>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Cierre Total -->
            <div class="card card-dark">
                <div class="card-header bg-dark text-white">
                    <h3 class="card-title">TOTAL CIERRE DE CAJA</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <tr>
                            <td>TOTAL INGRESOS</td>
                            <td class="text-right text-success font-weight-bold">
                                ${{ number_format($dailyClosing->total_incomes, 2) }}</td>
                        </tr>
                        <tr>
                            <td>(-) GASTOS/DÉBITOS</td>
                            <td class="text-right text-danger font-weight-bold">-
                                ${{ number_format($dailyClosing->total_expenses, 2) }}</td>
                        </tr>
                        <tr class="table-light">
                            <td><strong>VALOR TOTAL</strong></td>
                            <td class="text-right text-primary font-weight-bold">
                                ${{ number_format($dailyClosing->value_total, 2) }}</td>
                        </tr>
                        <tr>
                            <td>(+) OTROS INGRESOS</td>
                            <td class="text-right text-info font-weight-bold">+
                                ${{ number_format($dailyClosing->other_incomes_total, 2) }}</td>
                        </tr>
                        <tr class="table-light">
                            <td><strong>SUMA TOTAL</strong></td>
                            <td class="text-right text-primary font-weight-bold">
                                ${{ number_format($dailyClosing->sum_total, 2) }}</td>
                        </tr>
                        <tr>
                            <td>VALOR EXISTENTE</td>
                            <td class="text-right font-weight-bold">${{ number_format($dailyClosing->existing_value, 2) }}
                            </td>
                        </tr>
                        <tr>
                            <td>DIFERENCIA</td>
                            <td
                                class="text-right font-weight-bold {{ $dailyClosing->difference >= 0 ? 'text-success' : 'text-danger' }}">
                                ${{ number_format(abs($dailyClosing->difference), 2) }}
                            </td>
                        </tr>
                        <tr class="bg-dark">
                            <td class="text-warning"><strong>TOTAL</strong></td>
                            <td class="text-right text-warning h5 mb-0">
                                <strong>${{ number_format($dailyClosing->final_total, 2) }}</strong></td>
                        </tr>
                    </table>
                </div>
                @if ($dailyClosing->notes)
                    <div class="card-footer bg-light">
                        <small><i class="fas fa-sticky-note mr-1"></i>{{ $dailyClosing->notes }}</small>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
