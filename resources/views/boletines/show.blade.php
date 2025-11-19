@extends('layouts.app')

@section('content')
<div class="container mt-4">

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    {{-- CABECERA --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">
            Boletín #{{ $boletin->id }}
        </h2>

        <div class="text-end">
            @if($boletin->cliente)
                <a href="{{ route('clientes.show', $boletin->cliente) }}" class="btn btn-sm btn-outline-secondary mb-1">
                    Volver al cliente
                </a>
            @endif

            <a href="{{ route('boletines.index') }}" class="btn btn-sm btn-outline-secondary mb-1">
                Listado boletines
            </a>

            <a href="{{ route('boletines.edit', $boletin) }}" class="btn btn-sm btn-warning mb-1">
                Editar
            </a>

            <form action="{{ route('boletines.destroy', $boletin) }}"
                  method="POST"
                  class="d-inline"
                  onsubmit="return confirm('¿Seguro que quieres eliminar este boletín?');">
                @csrf
                @method('DELETE')
                <button class="btn btn-sm btn-danger mb-1">
                    Eliminar
                </button>
                <a href="{{ route('boletines.pdf.oficial', $boletin) }}" class="btn btn-sm btn-outline-dark mb-1">
                    PDF oficial
                </a>
            </form>
        </div>
    </div>

    {{-- CLIENTE --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header" style="background-color:#ff922b; color:#fff;">
            Cliente
        </div>
        <div class="card-body">
            @if($boletin->cliente)
                <div class="row">
                    <div class="col-md-6">
                        <p class="mb-1">
                            <strong>Nombre:</strong><br>
                            {{ $boletin->cliente->nombre }}
                            {{ $boletin->cliente->primer_apellido }}
                            {{ $boletin->cliente->segundo_apellido }}
                        </p>
                        <p class="mb-1">
                            <strong>DNI/CIF:</strong><br>
                            {{ $boletin->cliente->dni_cif }}
                        </p>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-1">
                            <strong>Email:</strong><br>
                            {{ $boletin->cliente->email }}
                        </p>
                        <p class="mb-0">
                            <strong>Teléfono:</strong><br>
                            {{ $boletin->cliente->telefono }}
                        </p>
                    </div>
                </div>
            @else
                <em>Este boletín no tiene cliente asociado.</em>
            @endif
        </div>
    </div>

    {{-- FILA 1: DATOS GENERALES + INVERSORES --}}
    <div class="row mb-4">
        {{-- Datos generales --}}
        <div class="col-md-6 mb-3 mb-md-0">
            <div class="card shadow-sm h-100">
                <div class="card-header" style="background-color:#ffe8cc; color:#d9480f;">
                    Datos generales
                </div>
                <div class="card-body">
                    <div class="row mb-2">
                        <div class="col-6">
                            <small class="text-muted">Fecha</small><br>
                            {{ $boletin->fecha?->format('d/m/Y') ?? '—' }}
                        </div>
                        <div class="col-6">
                            <small class="text-muted">Nº registro</small><br>
                            {{ $boletin->numero_registro ?: '—' }}
                        </div>
                    </div>

                    <div class="row mb-2">
                        <div class="col-6">
                            <small class="text-muted">CUPS</small><br>
                            {{ $boletin->cups ?: '—' }}
                        </div>
                        <div class="col-6">
                            <small class="text-muted">Ref. catastral</small><br>
                            {{ $boletin->referencia_catastral ?: '—' }}
                        </div>
                    </div>

                    <div class="row mb-2">
                        <div class="col-6">
                            <small class="text-muted">Potencia factura luz</small><br>
                            {{ $boletin->potencia_factura_luz ?: '—' }}
                        </div>
                        <div class="col-6">
                            <small class="text-muted">m² vivienda</small><br>
                            {{ $boletin->metros_cuadrados_vivienda ?: '—' }}
                        </div>
                    </div>

                    <div class="row mt-2">
                        <div class="col-12">
                            <small class="text-muted">Potencia pico FV</small><br>
                            @php
                                $picoKw = $boletin->potencia_pico
                                    ? $boletin->potencia_pico / 1000
                                    : null;
                            @endphp

                            @if($picoKw)
                                <span class="fw-bold">
                                    {{ number_format($picoKw, 2, ',', '.') }} kW
                                </span>
                            @else
                                —
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Inversores --}}
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header d-flex justify-content-between align-items-center"
                     style="background-color:#ff922b; color:#fff;">
                    <span>Inversores</span>
                    <span class="badge bg-light text-dark">
                        {{ $boletin->numero_inversores ?? 1 }}
                        inversor{{ ($boletin->numero_inversores ?? 1) > 1 ? 'es' : '' }}
                    </span>
                </div>
                <div class="card-body">
                    <div class="row mb-2">
                        <div class="col-6">
                            <small class="text-muted">Marca</small><br>
                            {{ $boletin->marca_inversor ?: '—' }}
                        </div>
                        <div class="col-6">
                            <small class="text-muted">Modelo</small><br>
                            {{ $boletin->modelo_inversor ?: '—' }}
                        </div>
                    </div>

                    <div class="row mb-2">
                        <div class="col-6">
                            <small class="text-muted">Potencia (dato)</small><br>
                            {{ $boletin->potencia_inversores ?: '—' }}
                        </div>
                        <div class="col-6">
                            <small class="text-muted">Nº inversores</small><br>
                            {{ $boletin->numero_inversores ?? '—' }}
                        </div>
                    </div>

                    <div class="row mt-2">
                        <div class="col-12">
                            <small class="text-muted">Potencia total inversores</small><br>
                            @isset($potenciaDerivacionKw)
                                <span class="fw-bold">
                                    {{ number_format($potenciaDerivacionKw, 2, ',', '.') }} kW
                                </span>
                            @else
                                <span class="text-muted">No calculada</span>
                            @endisset
                            <br>
                            <small class="text-muted">
                                Calculada usando potencia del inversor y número de inversores.
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- FILA 2: INSTALACIÓN + BATERÍAS / CUBIERTA / PROTECCIONES --}}
    <div class="row mb-4">
        {{-- Instalación + Protecciones --}}
        <div class="col-md-6 mb-3 mb-md-0">
            <div class="card shadow-sm mb-3">
                <div class="card-header" style="background-color:#ffe8cc; color:#d9480f;">
                    Instalación eléctrica
                </div>
                <div class="card-body">
                    <div class="row mb-2">
                        <div class="col-6">
                            <small class="text-muted">Tipo instalación eléctrica</small><br>
                            {{ ucfirst($boletin->tipo_instalacion_electrica) }}
                        </div>
                        <div class="col-6">
                            <small class="text-muted">Tensión suministro</small><br>
                            {{ $boletin->tension_suministro }}
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <small class="text-muted">Tipo instalación</small><br>
                            {{ ucfirst($boletin->tipo_instalacion) }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header" style="background-color:#ff6b6b; color:#fff;">
                    Protecciones contra sobreintensidades
                </div>
                <div class="card-body">
                    @php
                        $textoProteccion = match ($boletin->proteccion_sobretension) {
                            'interruptor_automatico' => 'Interruptor automático de protección contra sobrecargas y cortocircuitos',
                            'fusibles_calibrados'    => 'Fusibles calibrados de protección contra sobrecargas y cortocircuitos',
                            default                  => null,
                        };
                    @endphp

                    @if($textoProteccion)
                        <p class="mb-0">{{ $textoProteccion }}</p>
                    @else
                        <em>No se ha especificado protección contra sobreintensidades.</em>
                    @endif
                </div>
            </div>
        </div>

        {{-- Baterías + Tipo cubierta --}}
        <div class="col-md-6">
            <div class="card shadow-sm mb-3">
                <div class="card-header" style="background-color:#51cf66; color:#fff;">
                    Baterías
                </div>
                <div class="card-body">
                    <p class="mb-1">
                        <small class="text-muted">¿Tiene batería?</small><br>
                        {{ $boletin->tiene_bateria ? 'Sí' : 'No' }}
                    </p>

                    @if($boletin->tiene_bateria)
                        <p class="mb-1">
                            <small class="text-muted">Potencia batería</small><br>
                            {{ $boletin->potencia_bateria }}
                        </p>
                        <p class="mb-0">
                            <small class="text-muted">Nº baterías</small><br>
                            {{ $boletin->numero_baterias }}
                        </p>
                    @endif
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header" style="background-color:#ffe8cc; color:#d9480f;">
                    Tipo de instalación en cubierta
                </div>
                <div class="card-body">
                    @php
                        $tiposCubierta = $boletin->tipos_cubierta ?? [];
                    @endphp

                    @if(!empty($tiposCubierta) && is_array($tiposCubierta))
                        <ul class="mb-0">
                            @foreach($tiposCubierta as $tipo)
                                <li>{{ $tipo }}</li>
                            @endforeach
                        </ul>
                    @else
                        <em>No se ha especificado tipo de instalación en cubierta.</em>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- PLACAS SOLARES --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header" style="background-color:#343a40; color:#fff;">
            Placas solares
        </div>
        <div class="card-body">
            @if($boletin->placas && $boletin->placas->count())
                <div class="table-responsive">
                    <table class="table table-sm table-striped table-hover mb-0">
                        <thead>
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
                <em>No se han añadido placas a este boletín.</em>
            @endif
        </div>
    </div>

</div>
@endsection
