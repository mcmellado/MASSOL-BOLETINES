<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Boletín #{{ $boletin->id }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
        }
        .titulo {
            text-align: center;
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .seccion-titulo {
            background: #e0e0e0;
            font-weight: bold;
            padding: 4px;
            margin-top: 10px;
            margin-bottom: 4px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
        }
        td, th {
            border: 1px solid #777;
            padding: 4px;
            vertical-align: top;
        }
        .no-border td, .no-border th {
            border: none;
        }
        .check-box {
            display: inline-block;
            width: 12px;
            height: 12px;
            border: 1px solid #000;
            text-align: center;
            font-size: 9px;
            line-height: 12px;
        }
    </style>
</head>
<body>

    <div class="titulo">
        BOLETÍN DE INSTALACIÓN ELÉCTRICA EN BAJA TENSIÓN
    </div>

    {{-- Datos principales / cabecera --}}
    <table class="no-border">
        <tr>
            <td style="width: 60%;">
                <strong>Cliente:</strong>
                {{ $boletin->cliente?->nombre }}
                {{ $boletin->cliente?->primer_apellido }}
                {{ $boletin->cliente?->segundo_apellido }}<br>
                <strong>DNI/CIF:</strong> {{ $boletin->cliente?->dni_cif }}<br>
                <strong>Dirección:</strong> {{ $boletin->cliente?->direccion }}<br>
                <strong>Población:</strong> {{ $boletin->cliente?->poblacion }}
                ({{ $boletin->cliente?->provincia }})
            </td>
            <td style="width: 40%;">
                <strong>Nº de registro de la instalación:</strong> {{ $numeroRegistroInstalacion }}<br>
                <strong>Nº de boletín (CRM):</strong> {{ $boletin->id }}<br>
                <strong>Fecha boletín:</strong>
                {{ $boletin->fecha?->format('d/m/Y') }}
            </td>
        </tr>
    </table>

    {{-- DATOS GENERALES --}}
    <div class="seccion-titulo">DATOS GENERALES</div>
    <table>
        <tr>
            <td><strong>Nº registro interno:</strong> {{ $boletin->numero_registro }}</td>
            <td><strong>CUPS:</strong> {{ $boletin->cups }}</td>
        </tr>
        <tr>
            <td><strong>Referencia catastral:</strong> {{ $boletin->referencia_catastral }}</td>
            <td><strong>Potencia factura luz:</strong> {{ $boletin->potencia_factura_luz }}</td>
        </tr>
        <tr>
            <td><strong>Metros cuadrados vivienda:</strong> {{ $boletin->metros_cuadrados_vivienda }}</td>
            <td><strong>Potencia pico:</strong> {{ $boletin->potencia_pico }}</td>
        </tr>
    </table>

    {{-- INSTALACIÓN ELÉCTRICA --}}
    <div class="seccion-titulo">INSTALACIÓN ELÉCTRICA</div>
    <table>
        <tr>
            <td style="width: 33%;">
                <strong>Instalación (monofásica / trifásica):</strong><br>
                <span class="check-box">
                    {{ $boletin->tipo_instalacion_electrica === 'monofasica' ? 'X' : '' }}
                </span> Monofásica
                &nbsp;&nbsp;
                <span class="check-box">
                    {{ $boletin->tipo_instalacion_electrica === 'trifasica' ? 'X' : '' }}
                </span> Trifásica
            </td>
            <td style="width: 33%;">
                <strong>Tensión del suministro:</strong><br>
                <span class="check-box">
                    {{ $boletin->tension_suministro === '230V' ? 'X' : '' }}
                </span> 230 V
                &nbsp;&nbsp;
                <span class="check-box">
                    {{ $boletin->tension_suministro === '400V' ? 'X' : '' }}
                </span> 400 V
            </td>
            <td style="width: 34%;">
                <strong>Tipo de instalación:</strong><br>
                <span class="check-box">
                    {{ $boletin->tipo_instalacion === 'nueva' ? 'X' : '' }}
                </span> Nueva
                &nbsp;&nbsp;
                <span class="check-box">
                    {{ $boletin->tipo_instalacion === 'ampliacion' ? 'X' : '' }}
                </span> Ampliación
            </td>
        </tr>
    </table>

    {{-- Sección fase/neutro (condición 400V) --}}
    <table>
        <tr>
            <td style="width: 50%;">
                <strong>Sección fase / neutro:</strong><br>
                @if($seccionFaseNeutro)
                    {{ $seccionFaseNeutro }}
                @else
                    {{-- Si quieres un valor por defecto para 230V, puedes ponerlo aquí --}}
                    -
                @endif
            </td>
            <td style="width: 50%;">
                <strong>Tensión de suministro declarada:</strong>
                {{ $boletin->tension_suministro }}
            </td>
        </tr>
    </table>

    {{-- TIPO DE INSTALACIÓN EN CUBIERTA --}}
    <div class="seccion-titulo">TIPO DE INSTALACIÓN EN CUBIERTA</div>
    @php
        $cubiertas = $boletin->tipos_cubierta ?? [];
    @endphp
    <table>
        <tr>
            <td>
                <span class="check-box">
                    {{ in_array('instalación coplanar', $cubiertas ?? []) ? 'X' : '' }}
                </span> Instalación coplanar
            </td>
            <td>
                <span class="check-box">
                    {{ in_array('instalación aporticada', $cubiertas ?? []) ? 'X' : '' }}
                </span> Instalación aporticada
            </td>
            <td>
                <span class="check-box">
                    {{ in_array('instalación en estructura tipo pérgola', $cubiertas ?? []) ? 'X' : '' }}
                </span> Instalación en estructura tipo pérgola
            </td>
        </tr>
    </table>

    {{-- INVERSORES --}}
    <div class="seccion-titulo">INVERSORES</div>
    <table>
        <tr>
            <td><strong>Marca inversor:</strong> {{ $boletin->marca_inversor }}</td>
            <td><strong>Modelo inversor:</strong> {{ $boletin->modelo_inversor }}</td>
        </tr>
        <tr>
            <td colspan="2">
                <strong>Potencia inversores:</strong> {{ $boletin->potencia_inversores }}
            </td>
        </tr>
    </table>

    {{-- BATERÍAS --}}
    <div class="seccion-titulo">BATERÍAS</div>
    <table>
        <tr>
            <td style="width: 25%;">
                <strong>Tiene batería:</strong><br>
                <span class="check-box">
                    {{ $boletin->tiene_bateria ? 'X' : '' }}
                </span> Sí
                &nbsp;&nbsp;
                <span class="check-box">
                    {{ !$boletin->tiene_bateria ? 'X' : '' }}
                </span> No
            </td>
            <td style="width: 35%;">
                <strong>Potencia batería:</strong><br>
                {{ $boletin->potencia_bateria }}
            </td>
            <td style="width: 40%;">
                <strong>Número de baterías:</strong><br>
                {{ $boletin->numero_baterias }}
            </td>
        </tr>
    </table>

    {{-- PROTECCIONES CONTRA SOBREINTENSIDADES --}}
    <div class="seccion-titulo">PROTECCIONES CONTRA SOBREINTENSIDADES</div>
    <table>
        <tr>
            <td style="width: 50%;">
                <span class="check-box">
                    {{ $proteccionSobreintensidades === 'magnetotermico' ? 'X' : '' }}
                </span> Magnetotérmico
            </td>
            <td style="width: 50%;">
                <span class="check-box">
                    {{ $proteccionSobreintensidades === 'fusibles' ? 'X' : '' }}
                </span> Fusibles
            </td>
        </tr>
    </table>

    {{-- PLACAS SOLARES --}}
    <div class="seccion-titulo">PLACAS SOLARES</div>
    @if($boletin->placas && $boletin->placas->count())
        <table>
            <thead>
                <tr>
                    <th>Modelo de placa</th>
                    <th>Potencia de placa</th>
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
    @else
        <p>No se han registrado placas para este boletín.</p>
    @endif

</body>
</html>
