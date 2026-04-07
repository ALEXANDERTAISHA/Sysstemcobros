@extends('layouts.app')
@section('title', 'Nuevo Débito')
@section('page-title', 'Registrar Débito')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('expenses.index') }}">Gastos / Débitos</a></li>
    <li class="breadcrumb-item active">Nuevo</li>
@endsection

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card card-warning card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-hand-holding-usd mr-1"></i> Registrar Débito</h3>
                </div>
                <form method="POST" action="{{ route('expenses.store') }}">
                    @csrf
                    <div class="card-body">
                        <div class="form-group">
                            <label>Cliente *</label>
                            <select name="client_id" class="form-control @error('client_id') is-invalid @enderror" required>
                                <option value="">Seleccionar cliente...</option>
                                @foreach ($clients as $client)
                                    <option value="{{ $client->id }}"
                                        {{ old('client_id', request('client_id')) == $client->id ? 'selected' : '' }}>
                                        {{ $client->name }} {{ $client->phone ? "({$client->phone})" : '' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('client_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label>Empresa *</label>
                            <select name="company_id" class="form-control @error('company_id') is-invalid @enderror"
                                required>
                                <option value="">Seleccionar empresa...</option>
                                @foreach ($companies as $company)
                                    <option value="{{ $company->id }}"
                                        {{ old('company_id') == $company->id ? 'selected' : '' }}>
                                        {{ $company->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('company_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label>Concepto / Descripción *</label>
                            <input type="text" name="concept" class="form-control @error('concept') is-invalid @enderror"
                                value="{{ old('concept') }}" placeholder="Ej: Préstamo en efectivo, mercadería..."
                                required>
                            @error('concept')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Monto Total ($) *</label>
                                    <input type="number" name="total_amount" step="0.01" min="0.01"
                                        class="form-control @error('total_amount') is-invalid @enderror"
                                        value="{{ old('total_amount') }}" required>
                                    @error('total_amount')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Fecha de Otorgamiento *</label>
                                    <input type="date" name="granted_date" class="form-control"
                                        value="{{ old('granted_date', today()->toDateString()) }}" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Fecha Límite de Pago *</label>
                                    <input type="date" name="due_date"
                                        class="form-control @error('due_date') is-invalid @enderror"
                                        value="{{ old('due_date') }}" required>
                                    @error('due_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Notas</label>
                            <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-warning"><i class="fas fa-save mr-1"></i> Registrar
                            Débito</button>
                        <a href="{{ route('expenses.index') }}" class="btn btn-secondary ml-2">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
