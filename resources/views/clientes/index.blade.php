@extends('layouts.app')

@section('content')

{{-- 🔸 Estilos rápidos para mejorar la tabla y botones --}}
<style>
    :root {
        --orange-main: #ff922b;
        --orange-dark: #f76707;
        --orange-soft: #fff4e6;
    }

    .btn-orange {
        background-color: var(--orange-main);
        border-color: var(--orange-main);
        color: white;
        font-weight: 500;
    }
    .btn-orange:hover {
        background-color: var(--orange-dark);
        border-color: var(--orange-dark);
        color: white;
    }

    .btn-outline-orange {
        border-color: var(--orange-main);
        color: var(--orange-main);
        font-weight: 500;
    }
    .btn-outline-orange:hover {
        background-color: var(--orange-main);
        color: white;
    }

    .btn-outline-red {
        border-color: #e03131;
        color: #e03131;
        font-weight: 500;
    }
    .btn-outline-red:hover {
        background-color: #e03131;
        color: white;
    }

    .table-header-orange {
        background-color: var(--orange-main) !important;
        color: white !important;
    }

    .table-hover tbody tr:hover {
        background-color: #fff7ef !important;
    }
</style>

<div class="container mt-4">

    {{-- ALERTA DE ÉXITO --}}
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    {{-- CABECERA --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">Listado de Clientes</h2>

        <a href="{{ route('clientes.create') }}" class="btn btn-orange">
            + Nuevo Cliente
        </a>
    </div>

    {{-- TABLA --}}
    <div class="card shadow-sm">
        <div class="card-body p-0">

            <div class="table-responsive">
                <table class="table table-hover mb-0">

                    <thead class="table-header-orange">
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

                                    <a href="{{ route('clientes.show', $cliente) }}"
                                       class="btn btn-sm btn-outline-secondary">
                                        Ver
                                    </a>

                                    <a href="{{ route('clientes.edit', $cliente) }}"
                                       class="btn btn-sm btn-outline-orange">
                                        Editar
                                    </a>

                                    <form action="{{ route('clientes.destroy', $cliente) }}"
                                          method="POST"
                                          class="d-inline"
                                          onsubmit="return confirm('¿Seguro que quieres eliminar este cliente?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-red">
                                            Eliminar
                                        </button>
                                    </form>

                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-3 text-muted">
                                    No hay clientes registrados aún.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>

                </table>
            </div>

        </div>
    </div>

    {{-- PAGINACIÓN --}}
    <div class="mt-3">
        {{ $clientes->links() }}
    </div>

</div>

@endsection
