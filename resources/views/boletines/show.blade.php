@extends('layouts.app')

@section('content')

{{-- Estilos rápidos de la vista (puedes moverlos a tu CSS si quieres) --}}
<style>
    :root {
        --orange-main: #fd7e14;
        --orange-soft: #fff4e6;
    }

    .text-orange {
        color: var(--orange-main) !important;
    }
    .bg-orange-soft {
        background-color: var(--orange-soft) !important;
    }
    .btn-orange {
        background-color: var(--orange-main);
        border-color: var(--orange-main);
        
    }
    .btn-orange:hover {
        background-color: #f76707;
        border-color: #f76707;
        color: #fff;
    }
    .btn-outline-orange {
        color: var(--orange-main);
        border-color: var(--orange-main);
    }
    .btn-outline-orange:hover {
        background-color: var(--orange-main);
        border-color: var(--orange-main);
        color: #fff;
    }

    .page-header-title {
        font-size: 1.6rem;
        font-weight: 600;
    }

    .section-card {
        border-radius: .75rem;
        border: 1px solid #edf2f7;
    }

    .section-card-header {
        border-bottom: 1px solid #f1f3f5;
        background-color: #fff;
        border-bottom: 1px solid #ffe8cc !important;
    }

    .section-title {
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: .5rem;
    }

    .section-title::before {
        content: "";
        display: inline-block;
        width: .35rem;
        height: 1.4rem;
        border-radius: 999px;
        background-color: var(--orange-main);
    }

    .card-lift {
        transition: transform .15s ease, box-shadow .15s ease;
    }

    .card-lift:hover {
        transform: translateY(-2px);
        box-shadow: 0 .6rem 1.2rem rgba(15, 23, 42, 0.12);
    }
</style>

<div class="container-xl py-4">

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    @endif

    {{-- CABECERA / ACCIONES --}}
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
        <div>
            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                <h1 class="page-header-title mb-0">
                    Boletín #{{ $boletin->id }}
                </h1>

                <span class="badge rounded-pill bg-orange-soft text-orange">
                    Detalle de instalación fotovoltaica
                </span>
            </div>

            <small class="text-muted">
                Resumen del boletín, cliente asociado e información técnica de la instalación.
            </small>
        </div>

        <div class="d-flex flex-wrap gap-2 justify-content-lg-end">
            @if($boletin->cliente)
                <a href="{{ route('clientes.show', $boletin->cliente) }}"
                   class="btn btn-sm btn-outline-secondary">
                    Volver al cliente
                </a>
            @endif

            <a href="{{ route('boletines.index') }}" class="btn btn-sm btn-outline-secondary">
                Listado boletines
            </a>

            <a href="{{ route('boletines.edit', $boletin) }}" class="btn btn-sm btn-orange">
                Editar boletín
            </a>

            <a href="{{ route('boletines.pdf.oficial', $boletin) }}"
               class="btn btn-sm btn-outline-dark">
                PDF oficial
            </a>

            <a href="{{ route('boletines.pdf.memoria', $boletin) }}"
               class="btn btn-sm btn-outline-orange">
                Memoria técnica
            </a>

            <form action="{{ route('boletines.destroy', $boletin) }}"
                  method="POST"
                  class="d-inline"
                  onsubmit="return confirm('¿Seguro que quieres eliminar este boletín?');">
                @csrf
                @method('DELETE')
                <button class="btn btn-sm btn-outline-danger">
                    Eliminar
                </button>
            </form>
        </div>
    </div>

    {{-- CLIENTE --}}
    <div class="card section-card card-lift mb-4">
        <div class="card-header section-card-header">
            <div class="d-flex justify-content-between align-items-center">
                <div class="section-title">
                    Cliente
                </div>
                @if($boletin->cliente)
                    <span class="badge bg-orange-soft text-orange">
                        ID cliente: {{ $boletin->cliente->id }}
                    </span>
                @endif
            </div>
        </div>
        <div class="card-body">
            @if($boletin->cliente)
                <div class="row g-3">
                    <div class="col-md-6">
                        <p class="mb-2">
                            <span class="text-muted small d-block">Nombre completo</span>
                            <span class="fw-semibold">
                                {{ $boletin->cliente->nombre }}
                                {{ $boletin->cliente->primer_apellido }}
                                {{ $boletin->cliente->segundo_apellido }}
                            </span>
                        </p>
                        <p class="mb-0">
                            <span class="text-muted small d-block">DNI / CIF</span>
                            <span class="fw-semibold">
                                {{ $boletin->cliente->dni_cif }}
                            </span>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-2">
                            <span class="text-muted small d-block">Email</span>
                            <span class="fw-semibold">
                                {{ $boletin->cliente->email }}
                            </span>
                        </p>
                        <p class="mb-0">
                            <span class="text-muted small d-block">Teléfono</span>
                            <span class="fw-semibold">
                                {{ $boletin->cliente->telefono }}
                            </span>
                        </p>
                    </div>
                </div>
            @else
                <em class="text-muted">Este boletín no tiene cliente asociado.</em>
            @endif
        </div>
    </div>

    {{-- FILA 1: DATOS GENERALES + INVERSORES --}}
    <div class="row g-3 mb-4">
        {{-- Datos generales --}}
        <div class="col-lg-6">
            <div class="card section-card card-lift h-100">
                <div class="card-header section-card-header">
                    <div class="section-title">
                        Datos generales
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-3 mb-2">
                        <div class="col-6">
                            <span class="text-muted small d-block">Fecha</span>
                            <span class="fw-semibold">
                                {{ $boletin->fecha?->format('d/m/Y') ?? '—' }}
                            </span>
                        </div>
                        <div class="col-6">
                            <span class="text-muted small d-block">Nº registro</span>
                            <span class="fw-semibold">
                                {{ $boletin->numero_registro ?: '—' }}
                            </span>
                        </div>
                    </div>

                    <div class="row g-3 mb-2">
                        <div class="col-6">
                            <span class="text-muted small d-block">CUPS</span>
                            <span class="fw-semibold">
                                {{ $boletin->cups ?: '—' }}
                            </span>
                        </div>
                        <div class="col-6">
                            <span class="text-muted small d-block">Referencia catastral</span>
                            <span class="fw-semibold">
                                {{ $boletin->referencia_catastral ?: '—' }}
                            </span>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <span class="text-muted small d-block">Potencia en factura de luz</span>
                            <span class="fw-semibold">
                                {{ $boletin->potencia_factura_luz ?: '—' }}
                            </span>
                        </div>
                        <div class="col-6">
                            <span class="text-muted small d-block">Superficie vivienda (m²)</span>
                            <span class="fw-semibold">
                                {{ $boletin->metros_cuadrados_vivienda ?: '—' }}
                            </span>
                        </div>
                    </div>

                    <div class="pt-3 border-top">
                        <span class="text-muted small d-block">Potencia pico FV</span>
                        @php
                            $picoKw = $boletin->potencia_pico
                                ? $boletin->potencia_pico / 1000
                                : null;
                        @endphp

                        @if($picoKw)
                            <span class="fw-bold text-orange fs-5">
                                {{ number_format($picoKw, 2, ',', '.') }} kW
                            </span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Inversores --}}
<div class="col-lg-6">
    <div class="card section-card card-lift h-100">
        <div class="card-header section-card-header d-flex justify-content-between align-items-center">
            <div class="section-title mb-0">
                Inversores
            </div>

            @php
                // Usamos la relación Eloquent
                $listaInversores = $boletin->inversores ?? collect();
                $countInversores = $listaInversores->count();
            @endphp

            <span class="badge bg-orange-soft text-orange">
                {{ $countInversores }}
                inversor{{ $countInversores !== 1 ? 'es' : '' }}
            </span>
        </div>

        <div class="card-body">
            @if($countInversores > 0)
                <div class="table-responsive">
                    <table class="table table-sm table-striped table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Marca</th>
                                <th>Modelo</th>
                                <th>Potencia</th>
                                <th>Cantidad</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($listaInversores as $inv)
                                <tr>
                                    <td>{{ $inv->marca ?? '—' }}</td>
                                    <td>{{ $inv->modelo ?? '—' }}</td>
                                    <td>{{ $inv->potencia ?? '—' }}</td>
                                    <td>{{ $inv->cantidad ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="pt-3 border-top">
                    <span class="text-muted small d-block">Potencia total inversores</span>
                    @if(!is_null($potenciaDerivacionKw))
                        <span class="fw-bold text-orange fs-5">
                            {{ number_format($potenciaDerivacionKw, 2, ',', '.') }} kW
                        </span>
                    @else
                        <span class="text-muted">—</span>
                    @endif
                </div>

            @else
                <em class="text-muted">No se han añadido inversores a este boletín.</em>
            @endif
        </div>
    </div>
</div>

       
    {{-- PLACAS SOLARES --}}
    <div class="card section-card card-lift mb-4">
        <div class="card-header section-card-header d-flex justify-content-between align-items-center">
            <div class="section-title mb-0">
                Placas solares
            </div>
            @if($boletin->placas && $boletin->placas->count())
                <span class="badge bg-orange-soft text-orange">
                    {{ $boletin->placas->sum('cantidad_placas') }} placas totales
                </span>
            @endif
        </div>
        <div class="card-body">
            @if($boletin->placas && $boletin->placas->count())
                <div class="table-responsive">
                    <table class="table table-sm table-striped table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Modelo</th>
                                <th>Potencia (W)</th>
                                <th>Cantidad</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($boletin->placas as $placa)
                                <tr>
                                    <td>{{ $placa->modelo_placa }}</td>
                                    <td>{{ $placa->potencia_placa }}</td>
                                    <td>{{ $placa->cantidad_placas }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <em class="text-muted">No se han añadido placas a este boletín.</em>
            @endif
        </div>
    </div>

</div>
@endsection
