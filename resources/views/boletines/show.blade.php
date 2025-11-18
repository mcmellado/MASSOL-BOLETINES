@extends('layouts.app')

@section('content')
<div class="container mt-4">

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">
            Boletín #{{ $boletin->id }}
        </h2>

        <div>
            @if($boletin->cliente)
                <a href="{{ route('clientes.show', $boletin->cliente) }}" class="btn btn-secondary">
                    Volver al cliente
                </a>
            @endif

            <a href="{{ route('boletines.index') }}" class="btn btn-outline-secondary">
                Volver al listado de boletines
            </a>

            <a href="{{ route('boletines.edit', $boletin) }}" class="btn btn-warning">
                Editar boletín
            </a>

            <form action="{{ route('boletines.destroy', $boletin) }}"
                  method="POST"
                  class="d-inline"
                  onsubmit="return confirm('¿Seguro que quieres eliminar este boletín?');">
                @csrf
                @method('DELETE')
                <button class="btn btn-danger">
                    Eliminar
                </button>
               <a href="{{ route('boletines.pdf.oficial', $boletin) }}" class="btn btn-outline-dark">
                    Descargar boletín oficial
                </a>


            </form>
        </div>
    </div>

    {{-- Cliente --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-dark text-white">
            Cliente
        </div>
        <div class="card-body">
            @if($boletin->cliente)
                <p class="mb-1">
                    <strong>Nombre:</strong>
                    {{ $boletin->cliente->nombre }}
                    {{ $boletin->cliente->primer_apellido }}
                    {{ $boletin->cliente->segundo_apellido }}
                </p>
                <p class="mb-1">
                    <strong>DNI/CIF:</strong>
                    {{ $boletin->cliente->dni_cif }}
                </p>
                <p class="mb-1">
                    <strong>Email:</strong>
                    {{ $boletin->cliente->email }}
                </p>
                <p class="mb-0">
                    <strong>Teléfono:</strong>
                    {{ $boletin->cliente->telefono }}
                </p>
            @else
                <em>Este boletín no tiene cliente asociado.</em>
            @endif
        </div>
    </div>

    {{-- Datos generales --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-secondary text-white">
            Datos generales
        </div>
        <div class="card-body">
            <div class="row mb-2">
                <div class="col-md-4">
                    <strong>Fecha:</strong><br>
                    {{ $boletin->fecha?->format('d/m/Y') }}
                </div>
                <div class="col-md-4">
                    <strong>Número de registro:</strong><br>
                    {{ $boletin->numero_registro }}
                </div>
                <div class="col-md-4">
                    <strong>CUPS:</strong><br>
                    {{ $boletin->cups }}
                </div>
            </div>

            <div class="row mb-2">
                <div class="col-md-4">
                    <strong>Referencia catastral:</strong><br>
                    {{ $boletin->referencia_catastral }}
                </div>
                <div class="col-md-4">
                    <strong>Potencia factura luz:</strong><br>
                    {{ $boletin->potencia_factura_luz }}
                </div>
                <div class="col-md-4">
                    <strong>m² vivienda:</strong><br>
                    {{ $boletin->metros_cuadrados_vivienda }}
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <strong>Potencia pico:</strong><br>
                    {{ $boletin->potencia_pico }}
                </div>
            </div>
        </div>
    </div>
        {{-- Protecciones contra sobreintensidades --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-danger text-white">
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
                <p class="mb-0">
                    {{ $textoProteccion }}
                </p>
            @else
                <em>No se ha especificado protección contra sobreintensidades.</em>
            @endif
        </div>
    </div>

    {{-- Inversores --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-info text-white">
            Inversores
        </div>
        <div class="card-body">
            <div class="row mb-2">
                <div class="col-md-4">
                    <strong>Marca inversor:</strong><br>
                    {{ $boletin->marca_inversor }}
                </div>
                <div class="col-md-4">
                    <strong>Modelo inversor:</strong><br>
                    {{ $boletin->modelo_inversor }}
                </div>
                <div class="col-md-4">
                    <strong>Potencia inversores:</strong><br>
                    {{ $boletin->potencia_inversores }}
                </div>
            </div>
        </div>
    </div>

    {{-- Instalación eléctrica --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white">
            Instalación eléctrica
        </div>
        <div class="card-body">
            <div class="row mb-2">
                <div class="col-md-4">
                    <strong>Instalación:</strong><br>
                    {{ ucfirst($boletin->tipo_instalacion_electrica) }}
                </div>
                <div class="col-md-4">
                    <strong>Tensión del suministro:</strong><br>
                    {{ $boletin->tension_suministro }}
                </div>
                <div class="col-md-4">
                    <strong>Tipo instalación:</strong><br>
                    {{ ucfirst($boletin->tipo_instalacion) }}
                </div>
            </div>
        </div>
    </div>

    {{-- Tipo de instalación en cubierta --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-warning text-dark">
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

    {{-- Baterías --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-success text-white">
            Baterías
        </div>
        <div class="card-body">
            <p class="mb-1">
                <strong>¿Tiene batería?</strong><br>
                {{ $boletin->tiene_bateria ? 'Sí' : 'No' }}
            </p>

            @if($boletin->tiene_bateria)
                <p class="mb-1">
                    <strong>Potencia batería:</strong><br>
                    {{ $boletin->potencia_bateria }}
                </p>
                <p class="mb-0">
                    <strong>Número de baterías:</strong><br>
                    {{ $boletin->numero_baterias }}
                </p>
            @endif
        </div>
    </div>

    {{-- Placas solares --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-dark text-white">
            Placas solares
        </div>
        <div class="card-body">
            @if($boletin->placas && $boletin->placas->count())
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Modelo de placa</th>
                                <th>Potencia de placa</th>
                                <th>Cantidad de placas</th>
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
