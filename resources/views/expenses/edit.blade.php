@extends('layouts.app')
@section('title', 'Editar Débito')
@section('page-title', 'Editar Débito')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('expenses.index') }}">Gastos / Débitos</a></li>
    <li class="breadcrumb-item active">Editar</li>
@endsection

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card card-warning card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-edit mr-1"></i> Editar Débito</h3>
                </div>
                <form method="POST" action="{{ route('expenses.update', $credit) }}">
                    @csrf @method('PUT')
                    <div class="card-body">
                        <div class="form-group">
                            <label>Cliente *</label>
                            <select name="client_id" class="form-control" required>
                                @foreach ($clients as $client)
                                    <option value="{{ $client->id }}"
                                        {{ $credit->client_id == $client->id ? 'selected' : '' }}>
                                        {{ $client->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Empresa *</label>
                            <select name="company_id" class="form-control" required>
                                <option value="">Seleccionar empresa...</option>
                                @foreach ($companies as $company)
                                    <option value="{{ $company->id }}"
                                        {{ $credit->company_id == $company->id ? 'selected' : '' }}>
                                        {{ $company->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Concepto *</label>
                            <input type="text" name="concept" class="form-control" value="{{ $credit->concept }}"
                                required>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Monto Total ($) *</label>
                                    <input type="number" name="total_amount" step="0.01" class="form-control"
                                        value="{{ $credit->total_amount }}" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Fecha Otorgamiento *</label>
                                    <input type="date" name="granted_date" class="form-control"
                                        value="{{ $credit->granted_date->toDateString() }}" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Fecha Límite</label>
                                    <input type="date" name="due_date" class="form-control"
                                        value="{{ $credit->due_date?->toDateString() }}">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Notas</label>
                            <textarea name="notes" class="form-control" rows="2">{{ $credit->notes }}</textarea>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-warning"><i class="fas fa-save mr-1"></i> Actualizar</button>
                        <a href="{{ route('expenses.show', $credit) }}" class="btn btn-secondary ml-2">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
