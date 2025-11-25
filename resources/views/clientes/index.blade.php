@extends('layouts.app')

@section('content')

<style>
    :root {
        --orange-soft: #ffb457;
        --orange-soft-dark: #ff922b;
        --orange-light-bg: #fff4e6;
        --gray-soft: #f1f3f5;
    }

    /* Botones más compactos para que quepan */
    .btn-clean {
        border-radius: .45rem !important;
        padding: .18rem .55rem !important;
        font-size: 0.80rem !important;
        font-weight: 500;
        white-space: nowrap;
    }

    /* Botón naranja */
    .btn-orange-soft {
        background-color: var(--orange-soft);
        border: 1px solid var(--orange-soft);
        color: #fff;
    }
    .btn-orange-soft:hover {
        background-color: var(--orange-soft-dark);
        border-color: var(--orange-soft-dark);
        color: #fff;
    }

    /* Outline naranja */
    .btn-outline-soft-orange {
        border: 1px solid var(--orange-soft);
        color: var(--orange-soft-dark);
        background-color: #fff;
    }
    .btn-outline-soft-orange:hover {
        background-color: var(--orange-light-bg);
        color: var(--orange-soft-dark);
    }

    /* Gris */
    .btn-outline-gray {
        border: 1px solid #ced4da;
        color: #495057;
        background-color: #fff;
    }
    .btn-outline-gray:hover {
        background-color: var(--gray-soft);
    }

    /* Rojo */
    .btn-outline-red {
        border: 1px solid #e03131;
        color: #e03131;
        background-color: #fff;
    }
    .btn-outline-red:hover {
        background-color: #e03131;
        color: #fff;
    }

    /* Contenedor de botones: NO usar wrap */
    .actions-nowrap {
        display: flex;
        flex-wrap: nowrap !important;
        gap: .35rem;
        justify-content: flex-end;
        overflow-x: auto;     /* permite scroll si hay demasiados */
        padding-bottom: 2px;  /* evita que aparezca scrollbar encima */
    }

    /* Cabecera centrada */
    .table-header-orange th {
        text-align: center !important;
    }

    /* Centrar texto de las celdas */
    td, th {
        vertical-align: middle !important;
        text-align: center !important;
    }

    /* Hover de tabla */
    .table-hover tbody tr:hover {
        background-color: var(--orange-light-bg) !important;
    }
</style>

<div class="container mt-4">

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">Listado de Clientes</h2>

        <a href="{{ route('clientes.create') }}" class="btn btn-orange-soft btn-clean">
            + Nuevo Cliente
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">

            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">

                    <thead class="table-header-orange">
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>DNI/CIF</th>
                            <th>Email</th>
                            <th>Teléfono</th>
                            <th>Provincia</th>
                            <th>Código Postal</th>
                            <th>Acciones</th>
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

                                <td>
                                    <div class="actions-nowrap">

                                        <a href="{{ route('clientes.show', $cliente) }}"
                                           class="btn btn-sm btn-outline-gray btn-clean">
                                            Ver
                                        </a>

                                        <a href="{{ route('clientes.edit', $cliente) }}"
                                           class="btn btn-sm btn-outline-soft-orange btn-clean">
                                            Editar
                                        </a>

                                        @php
                                            $ultimoBoletin = $cliente->boletines->last();
                                        @endphp

                                        @if($ultimoBoletin)
                                            <a href="{{ route('boletines.pdf.memoria', $ultimoBoletin) }}"
                                               class="btn btn-sm btn-orange-soft btn-clean">
                                                Memoria técnica
                                            </a>

                                            <a href="{{ route('boletines.pdf.oficial', $ultimoBoletin) }}"
                                               class="btn btn-sm btn-outline-soft-orange btn-clean">
                                                PDF Oficial
                                            </a>
                                        @endif

                                        <form action="{{ route('clientes.destroy', $cliente) }}" method="POST"
                                              onsubmit="return confirm('¿Seguro que quieres eliminar este cliente?');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-outline-red btn-clean">
                                                Eliminar
                                            </button>
                                        </form>

                                    </div>
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

    <div class="mt-3">
        {{ $clientes->links() }}
    </div>

</div>

@endsection
