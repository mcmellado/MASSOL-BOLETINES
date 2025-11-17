@extends('layouts.app')

@section('content')
<div class="container mt-4">

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">
            Ficha del cliente: {{ $cliente->nombre }} {{ $cliente->primer_apellido }}
        </h2>

        <div>
            <a href="{{ route('clientes.index') }}" class="btn btn-secondary">
                Volver al listado
            </a>

            <a href="{{ route('clientes.edit', $cliente) }}" class="btn btn-warning">
                Editar cliente
            </a>

            <form action="{{ route('clientes.destroy', $cliente) }}"
                  method="POST"
                  class="d-inline"
                  onsubmit="return confirm('¿Seguro que quieres eliminar este cliente y todos sus boletines?');">
                @csrf
                @method('DELETE')
                <button class="btn btn-danger">
                    Eliminar cliente
                </button>
            </form>
        </div>
    </div>

    {{-- Datos del cliente --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-dark text-white">
            Datos del cliente
        </div>
        <div class="card-body">

            <div class="row mb-2">
                <div class="col-md-4">
                    <strong>Nombre:</strong><br>
                    {{ $cliente->nombre }} {{ $cliente->primer_apellido }} {{ $cliente->segundo_apellido }}
                </div>
                <div class="col-md-4">
                    <strong>DNI/CIF:</strong><br>
                    {{ $cliente->dni_cif }}
                </div>
                <div class="col-md-4">
                    <strong>Email:</strong><br>
                    {{ $cliente->email }}
                </div>
            </div>

            <div class="row mb-2">
                <div class="col-md-4">
                    <strong>Teléfono:</strong><br>
                    {{ $cliente->telefono }}
                </div>
                <div class="col-md-4">
                    <strong>Población:</strong><br>
                    {{ $cliente->poblacion }}
                </div>
                <div class="col-md-4">
                    <strong>Provincia:</strong><br>
                    {{ $cliente->provincia }}
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <strong>Dirección:</strong><br>
                    {{ $cliente->direccion }}
                </div>
            </div>

        </div>
    </div>

    {{-- Boletines del cliente --}}
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h4 class="mb-0">Boletines de este cliente</h4>

        <a href="{{ route('boletines.create', ['cliente_id' => $cliente->id]) }}"
           class="btn btn-primary">
            + Crear boletín para este cliente
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Fecha</th>
                            <th>Nº registro</th>
                            <th>CUPS</th>
                            <th>Potencia factura luz</th>
                            <th>Marca inversor</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($boletines as $boletin)
                            <tr>
                                <td>{{ $boletin->fecha?->format('d/m/Y') }}</td>
                                <td>{{ $boletin->numero_registro }}</td>
                                <td>{{ $boletin->cups }}</td>
                                <td>{{ $boletin->potencia_factura_luz }}</td>
                                <td>{{ $boletin->marca_inversor }}</td>
                                <td class="text-end">
                                    <a href="{{ route('boletines.show', $boletin) }}"
                                       class="btn btn-sm btn-info">
                                        Ver
                                    </a>

                                    <a href="{{ route('boletines.edit', $boletin) }}"
                                       class="btn btn-sm btn-warning">
                                        Editar
                                    </a>

                                    <form action="{{ route('boletines.destroy', $boletin) }}"
                                          method="POST"
                                          class="d-inline"
                                          onsubmit="return confirm('¿Seguro que quieres eliminar este boletín?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-danger">
                                            Eliminar
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-3">
                                    Este cliente aún no tiene boletines.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-3">
        {{ $boletines->links() }}
    </div>

</div>
@endsection
