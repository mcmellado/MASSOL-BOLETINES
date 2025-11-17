<div class="row">
    <div class="col-md-4 mb-3">
        <label for="nombre" class="form-label">Nombre</label>
        <input type="text"
               name="nombre"
               id="nombre"
               class="form-control @error('nombre') is-invalid @enderror"
               value="{{ old('nombre', $cliente->nombre ?? '') }}">
        @error('nombre')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4 mb-3">
        <label for="primer_apellido" class="form-label">Primer apellido</label>
        <input type="text"
               name="primer_apellido"
               id="primer_apellido"
               class="form-control @error('primer_apellido') is-invalid @enderror"
               value="{{ old('primer_apellido', $cliente->primer_apellido ?? '') }}">
        @error('primer_apellido')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4 mb-3">
        <label for="segundo_apellido" class="form-label">Segundo apellido</label>
        <input type="text"
               name="segundo_apellido"
               id="segundo_apellido"
               class="form-control @error('segundo_apellido') is-invalid @enderror"
               value="{{ old('segundo_apellido', $cliente->segundo_apellido ?? '') }}">
        @error('segundo_apellido')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="row">
    <div class="col-md-4 mb-3">
        <label for="dni_cif" class="form-label">DNI/CIF</label>
        <input type="text"
               name="dni_cif"
               id="dni_cif"
               class="form-control @error('dni_cif') is-invalid @enderror"
               value="{{ old('dni_cif', $cliente->dni_cif ?? '') }}">
        @error('dni_cif')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4 mb-3">
        <label for="email" class="form-label">Email</label>
        <input type="email"
               name="email"
               id="email"
               class="form-control @error('email') is-invalid @enderror"
               value="{{ old('email', $cliente->email ?? '') }}">
        @error('email')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4 mb-3">
        <label for="telefono" class="form-label">Teléfono</label>
        <input type="text"
               name="telefono"
               id="telefono"
               class="form-control @error('telefono') is-invalid @enderror"
               value="{{ old('telefono', $cliente->telefono ?? '') }}">
        @error('telefono')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label for="direccion" class="form-label">Dirección</label>
        <input type="text"
               name="direccion"
               id="direccion"
               class="form-control @error('direccion') is-invalid @enderror"
               value="{{ old('direccion', $cliente->direccion ?? '') }}">
        @error('direccion')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-3 mb-3">
        <label for="poblacion" class="form-label">Población</label>
        <input type="text"
               name="poblacion"
               id="poblacion"
               class="form-control @error('poblacion') is-invalid @enderror"
               value="{{ old('poblacion', $cliente->poblacion ?? '') }}">
        @error('poblacion')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-3 mb-3">
        <label for="provincia" class="form-label">Provincia</label>
        <input type="text"
               name="provincia"
               id="provincia"
               class="form-control @error('provincia') is-invalid @enderror"
               value="{{ old('provincia', $cliente->provincia ?? '') }}">
        @error('provincia')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="d-flex justify-content-between mt-3">
    <a href="{{ route('clientes.index') }}" class="btn btn-secondary">
        Volver
    </a>

    <button type="submit" class="btn btn-primary">
        Guardar
    </button>
</div>
