@extends('layouts.app')

@section('content')
<div class="container mt-4">

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">Listado de Clientes</h2>
        <a href="{{ route('clientes.create') }}" class="btn btn-primary">
            + Nuevo Cliente
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>DNI/CIF</th>
                            <th>Email</th>
                            <th>Teléfono</th>
                            <th>Provincia</th>
                            <th>Código Postal</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($clientes as $cliente)
                            <tr>
                                <td>{{ $cliente->id }}</td>
                                <td>{{ $cliente->nombre }} {{ $cliente->primer_apellido }}</td>
                                <td>{{ $cliente->dni_cif }}</td>
                                <td>{{ $cliente->email }}</td>
                                <td>{{ $cliente->telefono }}</td>
                                <td>{{ $cliente->provincia }}</td>
                                <td>{{ $cliente->codigo_postal }}</td>

                                <td class="text-end">
                                    <a href="{{ route('clientes.show', $cliente) }}" class="btn btn-sm btn-info">
                                        Ver
                                    </a>

                                    <a href="{{ route('clientes.edit', $cliente) }}" class="btn btn-sm btn-warning">
                                        Editar
                                    </a>

                                    <form action="{{ route('clientes.destroy', $cliente) }}"
                                          method="POST"
                                          class="d-inline"
                                          onsubmit="return confirm('¿Seguro que quieres eliminar este cliente?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-danger">Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-3">
                                    No hay clientes registrados aún.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-3">
        {{ $clientes->links() }}
    </div>

</div>
@endsection
