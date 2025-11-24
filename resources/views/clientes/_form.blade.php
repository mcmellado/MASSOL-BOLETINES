<style>
    /* Contenedor más bonito */
    .form-card {
        background: #fff;
        padding: 25px;
        border-radius: 12px;
        box-shadow: 0 4px 18px rgba(0,0,0,0.07);
        border-left: 6px solid #ff8c1a;
    }

    /* Etiquetas */
    .form-label {
        font-weight: 600;
        color: #444;
    }

    /* Inputs */
    .form-control {
        border-radius: 8px;
        border: 1px solid #ddd;
        padding: 10px;
        transition: all 0.2s ease-in-out;
    }

    .form-control:focus {
        border-color: #ff8c1a;
        box-shadow: 0 0 0 0.2rem rgba(255,140,26,0.25);
    }

    /* Botones */
    .btn-orange {
        background: linear-gradient(135deg, #ff7a00, #ffae42);
        border: none;
        color: #fff;
        font-weight: bold;
        padding: 10px 28px;
        border-radius: 8px;
        transition: 0.2s;
    }

    .btn-orange:hover {
        background: linear-gradient(135deg, #ff6a00, #ff9500);
        transform: translateY(-1px);
    }

    .btn-outline-orange {
        border: 2px solid #ff8c1a;
        color: #ff8c1a;
        font-weight: bold;
        padding: 8px 22px;
        border-radius: 8px;
        transition: 0.2s;
    }

    .btn-outline-orange:hover {
        background: #ff8c1a;
        color: white;
    }
</style>

<div class="form-card">

    <div class="row">
        <div class="col-md-4 mb-3">
            <label for="nombre" class="form-label">Nombre</label>
            <input type="text" name="nombre" id="nombre"
                class="form-control @error('nombre') is-invalid @enderror"
                value="{{ old('nombre', $cliente->nombre ?? '') }}">
            @error('nombre') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-4 mb-3">
            <label for="primer_apellido" class="form-label">Primer apellido</label>
            <input type="text" name="primer_apellido" id="primer_apellido"
                class="form-control @error('primer_apellido') is-invalid @enderror"
                value="{{ old('primer_apellido', $cliente->primer_apellido ?? '') }}">
            @error('primer_apellido') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-4 mb-3">
            <label for="segundo_apellido" class="form-label">Segundo apellido</label>
            <input type="text" name="segundo_apellido" id="segundo_apellido"
                class="form-control @error('segundo_apellido') is-invalid @enderror"
                value="{{ old('segundo_apellido', $cliente->segundo_apellido ?? '') }}">
            @error('segundo_apellido') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    </div>


    <div class="row">
        <div class="col-md-4 mb-3">
            <label for="codigo_postal" class="form-label">Código postal</label>
            <input type="text" name="codigo_postal" id="codigo_postal"
                class="form-control @error('codigo_postal') is-invalid @enderror"
                value="{{ old('codigo_postal', $cliente->codigo_postal ?? '') }}">
            @error('codigo_postal') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-4 mb-3">
            <label for="dni_cif" class="form-label">DNI/CIF</label>
            <input type="text" name="dni_cif" id="dni_cif"
                class="form-control @error('dni_cif') is-invalid @enderror"
                value="{{ old('dni_cif', $cliente->dni_cif ?? '') }}">
            @error('dni_cif') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-4 mb-3">
            <label for="telefono" class="form-label">Teléfono</label>
            <input type="text" name="telefono" id="telefono"
                class="form-control @error('telefono') is-invalid @enderror"
                value="{{ old('telefono', $cliente->telefono ?? '') }}">
            @error('telefono') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    </div>


    <div class="row">
        <div class="col-md-4 mb-3">
            <label for="email" class="form-label">Email (opcional)</label>
            <input type="email" name="email" id="email"
                class="form-control @error('email') is-invalid @enderror"
                value="{{ old('email', $cliente->email ?? '') }}">
            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-4 mb-3">
            <label for="poblacion" class="form-label">Población</label>
            <input type="text" name="poblacion" id="poblacion"
                class="form-control @error('poblacion') is-invalid @enderror"
                value="{{ old('poblacion', $cliente->poblacion ?? '') }}">
            @error('poblacion') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-4 mb-3">
            <label for="provincia" class="form-label">Provincia</label>
            <input type="text" name="provincia" id="provincia"
                class="form-control @error('provincia') is-invalid @enderror"
                value="{{ old('provincia', $cliente->provincia ?? '') }}">
            @error('provincia') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    </div>


    <div class="row">
        <div class="col-md-12 mb-3">
            <label for="direccion" class="form-label">Dirección</label>
            <input type="text" name="direccion" id="direccion"
                class="form-control @error('direccion') is-invalid @enderror"
                value="{{ old('direccion', $cliente->direccion ?? '') }}">
            @error('direccion') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    </div>

    <div class="d-flex justify-content-between mt-4">
        <a href="{{ route('clientes.index') }}" class="btn-outline-orange">
            Volver
        </a>

        <button type="submit" class="btn-orange">
            Guardar
        </button>
    </div>

</div>

