@extends('layouts.app')
@section('title', 'Nueva Cuenta por Pagar')
@section('page-title', 'Registrar Cuenta por Pagar')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('accounts-payable.index') }}">Cuentas por Pagar</a></li>
    <li class="breadcrumb-item active">Nueva</li>
@endsection

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card card-warning card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-file-invoice-dollar mr-1"></i> Registrar Cuenta por Pagar</h3>
                </div>
                <form method="POST" action="{{ route('accounts-payable.store') }}">
                    @csrf
                    <div class="card-body">
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>Cliente *</label>
                                <select name="client_id" class="form-control @error('client_id') is-invalid @enderror" required>
                                    <option value="">Seleccionar cliente...</option>
                                    @foreach($clients as $client)
                                        <option value="{{ $client->id }}" {{ old('client_id', request('client_id')) == $client->id ? 'selected' : '' }}>
                                            {{ $client->name }}{{ $client->phone ? ' (' . $client->phone . ')' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('client_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="form-group col-md-6">
                                <label>Empresa</label>
                                <select name="company_id" class="form-control @error('company_id') is-invalid @enderror">
                                    <option value="">Sin empresa...</option>
                                    @foreach($companies as $company)
                                        <option value="{{ $company->id }}" {{ old('company_id') == $company->id ? 'selected' : '' }}>
                                            {{ $company->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('company_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Concepto *</label>
                            <input type="text" name="concept" class="form-control @error('concept') is-invalid @enderror"
                                value="{{ old('concept', 'Cuenta por pagar') }}" required>
                            @error('concept')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-12">
                                <label>Monto Total ($) *</label>
                                <input type="number" name="total_amount" step="0.01" min="0.01"
                                    class="form-control @error('total_amount') is-invalid @enderror"
                                    value="{{ old('total_amount') }}" required>
                                @error('total_amount')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                        </div>

                            <!-- Notas field removed as requested -->
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-warning"><i class="fas fa-save mr-1"></i> Registrar Cuenta</button>
                        <a href="{{ route('accounts-payable.index') }}" class="btn btn-secondary ml-2">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
