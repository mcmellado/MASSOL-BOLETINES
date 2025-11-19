@php
    $boletin = $boletin ?? null;

    // Cliente seleccionado
    $clienteIdSeleccionado = old(
        'cliente_id',
        $boletin->cliente_id ?? ($clienteSeleccionado->id ?? null)
    );

    // Tipos cubierta
    $tiposCubiertaSeleccionados = old(
        'tipos_cubierta',
        $boletin->tipos_cubierta ?? []
    );

    // Batería
    $tieneBateria = old(
        'tiene_bateria',
        $boletin->tiene_bateria ?? false
    );

    // Proteccion contra sobreintensidades
     $proteccionSobretensionSeleccionada = old(
        'proteccion_sobretension',
        $boletin->proteccion_sobretension ?? null
    );

    // Variables IMPORTANTES del bloque placas
    $oldModelosPlaca    = old('modelo_placa', []);
    $oldCantidadesPlaca = old('cantidad_placas', []);
@endphp


{{-- ----------------------------------------------------
     FILA 1: Cliente, Fecha, Registro
----------------------------------------------------- --}}
<div class="row mb-3">
    <div class="col-md-4">
        <label for="cliente_id" class="form-label">Cliente</label>
        <select name="cliente_id" id="cliente_id"
                class="form-select @error('cliente_id') is-invalid @enderror">
            <option value="">-- Selecciona un cliente --</option>
            @foreach($clientes as $cliente)
                <option value="{{ $cliente->id }}"
                    {{ (string)$clienteIdSeleccionado === (string)$cliente->id ? 'selected' : '' }}>
                    {{ $cliente->nombre }} {{ $cliente->primer_apellido }} ({{ $cliente->dni_cif }})
                </option>
            @endforeach
        </select>
        @error('cliente_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="fecha" class="form-label">Fecha</label>
        <input type="date"
               name="fecha"
               id="fecha"
               class="form-control @error('fecha') is-invalid @enderror"
               value="{{ old('fecha', isset($boletin) && $boletin->fecha ? $boletin->fecha->format('Y-m-d') : '') }}">
        @error('fecha')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="numero_registro" class="form-label">Número de registro</label>
        <input type="text"
               name="numero_registro"
               id="numero_registro"
               class="form-control @error('numero_registro') is-invalid @enderror"
               value="{{ old('numero_registro', $boletin->numero_registro ?? '') }}">
        @error('numero_registro')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>



{{-- ----------------------------------------------------
     FILA 2: CUPS, Catastral, Potencia Factura
----------------------------------------------------- --}}
<div class="row mb-3">
    <div class="col-md-4">
        <label for="cups" class="form-label">CUPS</label>
        <input type="text"
               name="cups"
               id="cups"
               class="form-control @error('cups') is-invalid @enderror"
               value="{{ old('cups', $boletin->cups ?? '') }}">
        @error('cups')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="referencia_catastral" class="form-label">Referencia catastral</label>
        <input type="text"
               name="referencia_catastral"
               id="referencia_catastral"
               class="form-control @error('referencia_catastral') is-invalid @enderror"
               value="{{ old('referencia_catastral', $boletin->referencia_catastral ?? '') }}">
        @error('referencia_catastral')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="potencia_factura_luz" class="form-label">Potencia factura luz</label>
        <input type="text"
               name="potencia_factura_luz"
               id="potencia_factura_luz"
               class="form-control @error('potencia_factura_luz') is-invalid @enderror"
               value="{{ old('potencia_factura_luz', $boletin->potencia_factura_luz ?? '') }}">
        @error('potencia_factura_luz')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>



{{-- ----------------------------------------------------
     FILA 3: m2 vivienda + potencia pico (calculada)
----------------------------------------------------- --}}
<div class="row mb-3">
    <div class="col-md-4">
        <label for="metros_cuadrados_vivienda" class="form-label">m² vivienda</label>
        <input type="text"
               name="metros_cuadrados_vivienda"
               id="metros_cuadrados_vivienda"
               class="form-control @error('metros_cuadrados_vivienda') is-invalid @enderror"
               value="{{ old('metros_cuadrados_vivienda', $boletin->metros_cuadrados_vivienda ?? '') }}">
        @error('metros_cuadrados_vivienda')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- Solo visual, potencia pico la calculará el controlador --}}
    <div class="col-md-4">
        <label class="form-label">Potencia pico (se calculará sola)</label>
        <input type="text" class="form-control" value="{{ $boletin->potencia_pico ?? '—' }}" disabled>
    </div>
</div>

<hr>



{{-- ----------------------------------------------------
     INVERSORES
----------------------------------------------------- --}}
<div class="row mb-3">
    <div class="col-md-4">
        <label for="marca_inversor" class="form-label">Marca inversor</label>
        <select name="marca_inversor" id="marca_inversor"
                class="form-select @error('marca_inversor') is-invalid @enderror">
            <option value="">-- Selecciona marca --</option>
            @foreach($marcasInversor as $marca)
                <option value="{{ $marca }}"
                    {{ old('marca_inversor', $boletin->marca_inversor ?? '') === $marca ? 'selected' : '' }}>
                    {{ $marca }}
                </option>
            @endforeach
        </select>
        @error('marca_inversor')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="modelo_inversor" class="form-label">Modelo inversor</label>
        <input type="text"
               name="modelo_inversor"
               id="modelo_inversor"
               class="form-control @error('modelo_inversor') is-invalid @enderror"
               value="{{ old('modelo_inversor', $boletin->modelo_inversor ?? '') }}">
        @error('modelo_inversor')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="potencia_inversores" class="form-label">Potencia inversores</label>
        <input type="text"
               name="potencia_inversores"
               id="potencia_inversores"
               class="form-control @error('potencia_inversores') is-invalid @enderror"
               value="{{ old('potencia_inversores', $boletin->potencia_inversores ?? '') }}">
        @error('potencia_inversores')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="col-md-4">
    <label for="numero_inversores" class="form-label">Número de inversores</label>
    <input type="number"
           name="numero_inversores"
           id="numero_inversores"
           class="form-control @error('numero_inversores') is-invalid @enderror"
           value="{{ old('numero_inversores', $boletin->numero_inversores ?? '') }}">
    @error('numero_inversores')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>


<hr>



{{-- ----------------------------------------------------
     INSTALACIÓN
----------------------------------------------------- --}}
<div class="row mb-3">
    <div class="col-md-4">
        <label for="tipo_instalacion_electrica" class="form-label">Instalación eléctrica</label>
        <select name="tipo_instalacion_electrica" id="tipo_instalacion_electrica"
                class="form-select @error('tipo_instalacion_electrica') is-invalid @enderror">
            <option value="">-- Selecciona tipo --</option>
            @foreach($tiposInstalacionElectrica as $tipo)
                <option value="{{ $tipo }}"
                    {{ old('tipo_instalacion_electrica', $boletin->tipo_instalacion_electrica ?? '') === $tipo ? 'selected' : '' }}>
                    {{ ucfirst($tipo) }}
                </option>
            @endforeach
        </select>
        @error('tipo_instalacion_electrica')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="tension_suministro" class="form-label">Tensión suministro</label>
        <select name="tension_suministro" id="tension_suministro"
                class="form-select @error('tension_suministro') is-invalid @enderror">
            <option value="">-- Selecciona tensión --</option>
            @foreach($tensionesSuministro as $tension)
                <option value="{{ $tension }}"
                    {{ old('tension_suministro', $boletin->tension_suministro ?? '') === $tension ? 'selected' : '' }}>
                    {{ $tension }}
                </option>
            @endforeach
        </select>
        @error('tension_suministro')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="tipo_instalacion" class="form-label">Tipo instalación</label>
        <select name="tipo_instalacion" id="tipo_instalacion"
                class="form-select @error('tipo_instalacion') is-invalid @enderror">
            <option value="">-- Selecciona tipo --</option>
            @foreach($tiposInstalacion as $tipo)
                <option value="{{ $tipo }}"
                    {{ old('tipo_instalacion', $boletin->tipo_instalacion ?? '') === $tipo ? 'selected' : '' }}>
                    {{ ucfirst($tipo) }}
                </option>
            @endforeach
        </select>
        @error('tipo_instalacion')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

<hr>



{{-- ----------------------------------------------------
     TIPO DE CUBIERTA
----------------------------------------------------- --}}
<div class="mb-3">
    <label class="form-label d-block">Tipo de instalación en cubierta</label>

    @foreach($tiposCubierta as $tipo)
        <div class="form-check form-check-inline">
            <input type="checkbox"
                   name="tipos_cubierta[]"
                   id="cubierta_{{ md5($tipo) }}"
                   value="{{ $tipo }}"
                   class="form-check-input"
                   {{ in_array($tipo, $tiposCubiertaSeleccionados ?? []) ? 'checked' : '' }}>
            <label for="cubierta_{{ md5($tipo) }}" class="form-check-label">
                {{ $tipo }}
            </label>
        </div>
    @endforeach

    @error('tipos_cubierta')
        <div class="text-danger small mt-1">{{ $message }}</div>
    @enderror
</div>

<hr>
<hr>

{{-- ----------------------------------------------------
     PROTECCIONES CONTRA SOBREINTENSIDADES
----------------------------------------------------- --}}
<div class="mb-3">
    <label class="form-label d-block">Protecciones contra sobreintensidades</label>

    <div class="form-check form-check-inline">
        <input
            class="form-check-input"
            type="radio"
            name="proteccion_sobretension"
            id="proteccion_interruptor"
            value="interruptor_automatico"
            {{ $proteccionSobretensionSeleccionada === 'interruptor_automatico' ? 'checked' : '' }}
        >
        <label class="form-check-label" for="proteccion_interruptor">
            Interruptor automático de protección<br>
            contra sobrecargas y cortocircuitos
        </label>
    </div>

    <div class="form-check form-check-inline">
        <input
            class="form-check-input"
            type="radio"
            name="proteccion_sobretension"
            id="proteccion_fusibles"
            value="fusibles_calibrados"
            {{ $proteccionSobretensionSeleccionada === 'fusibles_calibrados' ? 'checked' : '' }}
        >
        <label class="form-check-label" for="proteccion_fusibles">
            Fusibles calibrados de protección<br>
            contra sobrecargas y cortocircuitos
        </label>
    </div>

    @error('proteccion_sobretension')
        <div class="text-danger small mt-1">{{ $message }}</div>
    @enderror
</div>



{{-- ----------------------------------------------------
     BATERÍA
----------------------------------------------------- --}}
<div class="mb-3">
    <div class="form-check">
        <input type="checkbox"
               name="tiene_bateria"
               id="tiene_bateria"
               value="1"
               class="form-check-input"
               {{ $tieneBateria ? 'checked' : '' }}>
        <label for="tiene_bateria" class="form-check-label">Batería</label>
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-6">
        <label for="potencia_bateria" class="form-label">Potencia batería</label>
        <input type="text"
               name="potencia_bateria"
               id="potencia_bateria"
               class="form-control @error('potencia_bateria') is-invalid @enderror"
               value="{{ old('potencia_bateria', $boletin->potencia_bateria ?? '') }}">
        @error('potencia_bateria')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="numero_baterias" class="form-label">Número de baterías</label>
        <input type="number"
               name="numero_baterias"
               id="numero_baterias"
               class="form-control @error('numero_baterias') is-invalid @enderror"
               value="{{ old('numero_baterias', $boletin->numero_baterias ?? '') }}">
        @error('numero_baterias')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

<hr>



{{-- ----------------------------------------------------
     PLACAS SOLARES (CORREGIDAS)
----------------------------------------------------- --}}
<h5>Placas solares</h5>

<div id="placas-container" class="mt-3">

    {{-- Si vienen datos antiguos del formulario (por errores de validación) --}}
    @if(!empty($oldModelosPlaca))
        @foreach($oldModelosPlaca as $i => $modeloSeleccionado)
            <div class="row placa-item align-items-end mb-2">

                {{-- Modelo de placa (select) --}}
                <div class="col-md-6 mb-2">
                    <label class="form-label">Modelo de placa</label>
                    <select name="modelo_placa[]" class="form-select">
                        <option value="">-- Selecciona modelo --</option>
                        @foreach($modelosPlaca as $m)
                            <option value="{{ $m }}" {{ $modeloSeleccionado === $m ? 'selected' : '' }}>
                                {{ $m }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Cantidad --}}
                <div class="col-md-4 mb-2">
                    <label class="form-label">Cantidad</label>
                    <input type="number"
                           name="cantidad_placas[]"
                           class="form-control"
                           value="{{ $oldCantidadesPlaca[$i] ?? '' }}">
                </div>

                {{-- Botón eliminar --}}
                <div class="col-md-2 mb-2 text-end">
                    <button type="button" class="btn btn-outline-danger btn-remove-placa">
                        Eliminar
                    </button>
                </div>

            </div>
        @endforeach

    {{-- Si el boletín ya tiene placas guardadas --}}
    @elseif(isset($boletin) && $boletin->placas->count() > 0)
        @foreach($boletin->placas as $placa)
            <div class="row placa-item align-items-end mb-2">

                {{-- Modelo de placa (select) --}}
                <div class="col-md-6 mb-2">
                    <label class="form-label">Modelo de placa</label>
                    <select name="modelo_placa[]" class="form-select">
                        <option value="">-- Selecciona modelo --</option>
                        @foreach($modelosPlaca as $m)
                            <option value="{{ $m }}" {{ $placa->modelo_placa === $m ? 'selected' : '' }}>
                                {{ $m }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Cantidad --}}
                <div class="col-md-4 mb-2">
                    <label class="form-label">Cantidad</label>
                    <input type="number"
                           name="cantidad_placas[]"
                           class="form-control"
                           value="{{ $placa->cantidad_placas }}">
                </div>

                {{-- Botón eliminar --}}
                <div class="col-md-2 mb-2 text-end">
                    <button type="button" class="btn btn-outline-danger btn-remove-placa">
                        Eliminar
                    </button>
                </div>

            </div>
        @endforeach

    {{-- Si no hay placas aún --}}
    @else
        <div class="row placa-item align-items-end mb-2">

            {{-- Modelo de placa --}}
            <div class="col-md-6 mb-2">
                <label class="form-label">Modelo de placa</label>
                <select name="modelo_placa[]" class="form-select">
                    <option value="">-- Selecciona modelo --</option>
                    @foreach($modelosPlaca as $m)
                        <option value="{{ $m }}">{{ $m }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Cantidad --}}
            <div class="col-md-4 mb-2">
                <label class="form-label">Cantidad</label>
                <input type="number" name="cantidad_placas[]" class="form-control">
            </div>

            {{-- Botón eliminar --}}
            <div class="col-md-2 mb-2 text-end">
                <button type="button" class="btn btn-outline-danger btn-remove-placa">
                    Eliminar
                </button>
            </div>

        </div>
    @endif

</div>

<button type="button" id="btn-add-placa" class="btn btn-outline-primary btn-sm mt-2">
    + Añadir placa
</button>

<template id="placa-template">
    <div class="row placa-item align-items-end mb-2">

        <div class="col-md-6 mb-2">
            <label class="form-label">Modelo de placa</label>
            <select name="modelo_placa[]" class="form-select">
                <option value="">-- Selecciona modelo --</option>
                @foreach($modelosPlaca as $m)
                    <option value="{{ $m }}">{{ $m }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-4 mb-2">
            <label class="form-label">Cantidad</label>
            <input type="number" name="cantidad_placas[]" class="form-control">
        </div>

        <div class="col-md-2 mb-2 text-end">
            <button type="button" class="btn btn-outline-danger btn-remove-placa">
                Eliminar
            </button>
        </div>

    </div>
</template>


<script>
    document.addEventListener('DOMContentLoaded', function () {
        const btnAdd = document.getElementById('btn-add-placa');
        const container = document.getElementById('placas-container');
        const template = document.getElementById('placa-template');

        btnAdd.addEventListener('click', () => {
            container.appendChild(template.content.cloneNode(true));
        });

        container.addEventListener('click', e => {
            if (e.target.classList.contains('btn-remove-placa')) {
                e.target.closest('.placa-item').remove();
            }
        });
    });
</script>


{{-- ----------------------------------------------------
     BOTONES
----------------------------------------------------- --}}
<div class="d-flex justify-content-between mt-4">
    <a href="{{ route('boletines.index') }}" class="btn btn-secondary">
        Volver
    </a>

    <button type="submit" class="btn btn-primary">
        Guardar boletín
    </button>
</div>
