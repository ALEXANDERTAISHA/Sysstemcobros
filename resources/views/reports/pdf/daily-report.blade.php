<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Reporte Diario de Caja</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #111827;
            margin: 18px;
        }

        .report-header {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 4px;
        }

        .report-header td {
            vertical-align: middle;
        }

        .logo-col {
            width: 90px;
        }

        .logo-box {
            width: 80px;
            height: 80px;
            border: 1px solid #9ca3af;
            border-radius: 6px;
            object-fit: cover;
        }

        .report-title {
            text-align: center;
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 2px;
            letter-spacing: 0.3px;
        }

        .report-meta {
            text-align: center;
            font-size: 10px;
            margin-bottom: 12px;
        }

        .layout {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .layout td {
            vertical-align: top;
            padding: 4px 6px;
        }

        .box {
            width: 100%;
            border-collapse: collapse;
        }

        .box th,
        .box td {
            border: 1px solid #111827;
            padding: 4px 5px;
        }

        .box .box-title {
            background: #e5e7eb;
            font-size: 10px;
            text-align: center;
            font-weight: bold;
        }

        .center {
            text-align: center;
        }

        .right {
            text-align: right;
        }

        .bold {
            font-weight: bold;
        }

        .no-border {
            border: none !important;
        }

        .total-row td {
            font-weight: bold;
            background: #f3f4f6;
        }

        .mono {
            font-family: DejaVu Sans, sans-serif;
        }

        .footer {
            margin-top: 10px;
            text-align: right;
            font-size: 9px;
            color: #4b5563;
        }
    </style>
</head>

<body>
    <table class="report-header">
        <tr>
            <td class="logo-col">
                @if (!empty($systemLogoDataUri))
                    <img src="{{ $systemLogoDataUri }}" alt="Logo sistema" class="logo-box">
                @endif
            </td>
            <td>
                <div class="report-title">REPORTE DE INGRESOS Y EGRESOS DIARIOS</div>
            </td>
            <td class="logo-col"></td>
        </tr>
    </table>
    <div class="report-meta">
        FECHA: {{ \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') }}
        @if ($dateFrom !== $dateTo)
            - {{ \Carbon\Carbon::parse($dateTo)->format('d/m/Y') }}
        @endif
        @if ($companyName)
            | EMPRESA: {{ strtoupper($companyName) }}
        @endif
    </div>

    <table class="layout">
        <tr>
            <td style="width: 50%;">
                <table class="box">
                    <tr>
                        <th colspan="4" class="box-title">TRANSFERENCIAS</th>
                    </tr>
                    <tr>
                        <th style="width: 8%;" class="center">#</th>
                        <th style="width: 52%;">EMPRESA</th>
                        <th style="width: 12%;" class="center">N</th>
                        <th style="width: 28%;" class="right">VALOR</th>
                    </tr>
                    @php
                        $transferRows = $printable['transfers_by_company']
                            ->filter(fn($r) => $r->transfers_count > 0)
                            ->values();
                    @endphp
                    @forelse($transferRows as $i => $row)
                        <tr>
                            <td class="center">{{ $i + 1 }}</td>
                            <td>{{ $row->name }}</td>
                            <td class="center">{{ (int) $row->transfers_count }}</td>
                            <td class="right mono">$ {{ number_format((float) $row->transfers_total_amount, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="center" style="color:#888;">Sin transferencias</td>
                        </tr>
                    @endforelse
                    <tr class="total-row">
                        <td colspan="3" class="bold">INGRESOS</td>
                        <td class="right mono">$ {{ number_format($summary['total_incomes'], 2) }}</td>
                    </tr>
                </table>
            </td>
            <td style="width: 50%;">
                <table class="box">
                    <tr>
                        <th colspan="4" class="box-title">DEBITOS</th>
                    </tr>
                    <tr>
                        <th style="width: 8%;" class="center">#</th>
                        <th style="width: 18%;" class="right">VALOR</th>
                        <th style="width: 29%;">EMPRESA GASTOS DÉBITOS</th>
                        <th style="width: 45%;">DESCRIPCION</th>
                    </tr>
                    @php
                        $debitRows = $printable['debits']->values();
                    @endphp
                    @forelse($debitRows as $i => $row)
                        <tr>
                            <td class="center">{{ $i + 1 }}</td>
                            <td class="right mono">$ {{ number_format((float) $row->total_amount, 2) }}</td>
                            <td>{{ $row->company?->name ?? '-' }}</td>
                            <td>
                                {{ $row->concept }}
                                @if ($row->client)
                                    - {{ $row->client->name }}
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="center" style="color:#888;">Sin débitos</td>
                        </tr>
                    @endforelse
                    <tr class="total-row">
                        <td colspan="3" class="bold">TOTAL</td>
                        <td class="right mono">$ {{ number_format($summary['total_expenses'], 2) }}</td>
                    </tr>
                </table>
            </td>
        </tr>

        <tr>
            <td style="width: 50%;">
                <table class="box">
                    <tr>
                        <th colspan="3" class="box-title">OTROS INGRESOS</th>
                    </tr>
                    <tr>
                        <th style="width: 8%;" class="center">#</th>
                        <th style="width: 22%;" class="right">VALOR</th>
                        <th style="width: 70%;">DETALLE</th>
                    </tr>
                    @php
                        $otherIncomeRows = $printable['other_incomes']->values();
                    @endphp
                    @forelse($otherIncomeRows as $i => $row)
                        <tr>
                            <td class="center">{{ $i + 1 }}</td>
                            <td class="right mono">$ {{ number_format((float) $row->amount, 2) }}</td>
                            <td>
                                {{ $row->description }}
                                @if ($row->client)
                                    - {{ $row->client->name }}
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="center" style="color:#888;">Sin otros ingresos</td>
                        </tr>
                    @endforelse
                    <tr class="total-row">
                        <td colspan="2" class="bold">TOTAL</td>
                        <td class="right mono">$ {{ number_format($summary['total_other_incomes'], 2) }}</td>
                    </tr>
                </table>
            </td>
            <td style="width: 50%;">
                <table class="box">
                    <tr>
                        <th colspan="2" class="box-title">TOTAL CIERRE DE CAJA</th>
                    </tr>
                    <tr>
                        <td class="bold">TOTAL DE TRANSFERENCIAS</td>
                        <td class="right mono">$ {{ number_format($summary['total_incomes'], 2) }}</td>
                    </tr>
                    <tr>
                        <td class="bold">(-) GASTOS / DEBITOS</td>
                        <td class="right mono">$ {{ number_format($summary['total_expenses'], 2) }}</td>
                    </tr>
                    <tr>
                        <td class="bold">VALOR TOTAL</td>
                        <td class="right mono">$ {{ number_format($summary['value_total'], 2) }}</td>
                    </tr>
                    <tr>
                        <td class="bold">(+) OTROS INGRESOS</td>
                        <td class="right mono">$ {{ number_format($summary['total_other_incomes'], 2) }}</td>
                    </tr>
                    <tr>
                        <td class="bold">SUMA TOTAL</td>
                        <td class="right mono">$ {{ number_format($summary['sum_total'], 2) }}</td>
                    </tr>
                    <tr>
                        <td class="bold">VALOR EXISTENTE / DINERO INICIAL CAJA CHICA</td>
                        <td class="right mono">$ {{ number_format($printable['existing_value'], 2) }}</td>
                    </tr>
                    <tr>
                        <td class="bold">DIFERENCIA</td>
                        <td class="right mono">$ {{ number_format($printable['difference'], 2) }}</td>
                    </tr>
                    <tr class="total-row">
                        <td class="bold">TOTAL</td>
                        <td class="right mono">$ {{ number_format($printable['final_total'], 2) }}</td>
                    </tr>
                    <tr>
                        <td class="bold">NOTA</td>
                        <td>{{ $printable['closing_notes'] ?: '-' }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="footer">
        Generado el {{ now()->format('d/m/Y H:i') }}
    </div>
</body>

</html>
