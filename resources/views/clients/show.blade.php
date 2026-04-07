@extends('layouts.app')
@section('title', $client->name)
@section('page-title', $client->name)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('clients.index') }}">Clientes</a></li>
    <li class="breadcrumb-item active">{{ $client->name }}</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-md-4">
            <div class="card card-primary card-outline">
                <div class="card-body box-profile">
                    <div class="text-center">
                        <div class="rounded-circle bg-primary d-inline-flex align-items-center justify-content-center"
                            style="width:80px;height:80px;">
                            <i class="fas fa-user fa-2x text-white"></i>
                        </div>
                    </div>
                    <h3 class="profile-username text-center mt-2">{{ $client->name }}</h3>
                    <p class="text-center">
                        <span class="badge badge-{{ $client->is_active ? 'success' : 'secondary' }}">
                            {{ $client->is_active ? 'Activo' : 'Inactivo' }}
                        </span>
                    </p>
                    <ul class="list-group list-group-unbordered mb-3">
                        @if ($client->phone)
                            <li class="list-group-item">
                                <b><i class="fas fa-phone mr-1"></i>Teléfono</b>
                                <a class="float-right">{{ $client->phone }}</a>
                            </li>
                        @endif
                        @if ($client->email)
                            <li class="list-group-item">
                                <b><i class="fas fa-envelope mr-1"></i>Correo</b>
                                <a class="float-right" href="mailto:{{ $client->email }}">{{ $client->email }}</a>
                            </li>
                        @endif
                        @if ($client->whatsapp)
                            <li class="list-group-item">
                                <b><i class="fab fa-whatsapp text-success mr-1"></i>WhatsApp</b>
                                <a class="float-right text-success"
                                    href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $client->whatsapp) }}"
                                    target="_blank">
                                    {{ $client->whatsapp }}
                                </a>
                            </li>
                        @endif
                        @if ($client->address)
                            <li class="list-group-item">
                                <b><i class="fas fa-map-marker-alt mr-1"></i>Dirección</b>
                                <span class="float-right">{{ $client->address }}</span>
                            </li>
                        @endif
                        <li class="list-group-item">
                            <b>Deuda Total</b>
                            <span
                                class="float-right text-danger font-weight-bold">${{ number_format($totalDebt, 2) }}</span>
                        </li>
                    </ul>
                    <a href="{{ route('clients.edit', $client) }}" class="btn btn-warning btn-block">
                        <i class="fas fa-edit mr-1"></i> Editar
                    </a>
                    <a href="{{ route('expenses.create', ['client_id' => $client->id]) }}"
                        class="btn btn-primary btn-block">
                        <i class="fas fa-plus mr-1"></i> Nuevo Débito
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card card-outline card-warning">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-hand-holding-usd mr-1"></i> Fiados del Cliente</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Concepto</th>
                                <th>Fecha</th>
                                <th class="text-right">Total</th>
                                <th class="text-right">Pagado</th>
                                <th class="text-right">Saldo</th>
                                <th>Estado</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($credits as $credit)
                                <tr>
                                    <td>{{ $credit->concept }}</td>
                                    <td>{{ $credit->granted_date->format('d/m/Y') }}</td>
                                    <td class="text-right">${{ number_format($credit->total_amount, 2) }}</td>
                                    <td class="text-right text-success">${{ number_format($credit->paid_amount, 2) }}</td>
                                    <td
                                        class="text-right font-weight-bold text-{{ $credit->balance > 0 ? 'danger' : 'success' }}">
                                        ${{ number_format($credit->balance, 2) }}
                                    </td>
                                    <td>
                                        <span
                                            class="badge badge-{{ $credit->status === 'paid' ? 'success' : ($credit->status === 'partial' ? 'info' : 'warning') }}">
                                            {{ $credit->status === 'paid' ? 'Pagado' : ($credit->status === 'partial' ? 'Parcial' : 'Activo') }}
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('expenses.show', $credit) }}" class="btn btn-xs btn-info">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-3">Sin fiados registrados</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
