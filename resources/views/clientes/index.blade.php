@extends('layouts.app')

@section('content')

<style>
    :root {
        --orange-soft: #ffb457;
        --orange-soft-dark: #ff922b;
        --orange-light-bg: #fff4e6;
        --gray-soft: #f1f3f5;
    }


    .btn-clean {
        border-radius: .45rem !important;
        padding: .18rem .55rem !important;
        font-size: 0.80rem !important;
        font-weight: 500;
        white-space: nowrap;
    }


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

    
    .btn-outline-soft-orange {
        border: 1px solid var(--orange-soft);
        color: var(--orange-soft-dark);
        background-color: #fff;
    }
    .btn-outline-soft-orange:hover {
        background-color: var(--orange-light-bg);
        color: var(--orange-soft-dark);
    }


    .btn-outline-gray {
        border: 1px solid #ced4da;
        color: #495057;
        background-color: #fff;
    }
    .btn-outline-gray:hover {
        background-color: var(--gray-soft);
    }


    .btn-outline-red {
        border: 1px solid #e03131;
        color: #e03131;
        background-color: #fff;
    }
    .btn-outline-red:hover {
        background-color: #e03131;
        color: #fff;
    }

   
    .actions-nowrap {
        display: flex;
        flex-wrap: nowrap !important;
        gap: .35rem;
        justify-content: flex-end;
        overflow-x: auto;     
        padding-bottom: 2px;  
    }

 
    .table-header-orange th {
        text-align: center !important;
    }

    td, th {
        vertical-align: middle !important;
        text-align: center !important;
    }

    .table-hover tbody tr:hover {
        background-color: var(--orange-light-bg) !important;
    }
</style>

<div class="container mt-4">

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h2 class="mb-0">Listado de Clientes</h2>

        <div class="d-flex align-items-center gap-2">
        
            <input
                type="text"
                id="buscador"
                name="search"
                class="form-control form-control-sm"
                placeholder="Buscar por nombre, DNI, email, teléfono..."
                value="{{ request('search') }}"
                style="min-width: 220px;"
            >

            <a href="{{ route('clientes.create') }}" class="btn btn-orange-soft btn-clean">
                + Nuevo Cliente
            </a>
        </div>
    </div>


    <div id="tabla-clientes">

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
                                <th>Poblacion</th>
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
                                    <td>{{ $cliente->poblacion }}</td>
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
            {{ $clientes->appends(request()->query())->links() }}
        </div>

    </div> 

</div>


<script>
let timer = null;

document.getElementById('buscador').addEventListener('keyup', function() {
    clearTimeout(timer);

    let query = this.value;

    timer = setTimeout(() => {
        fetch("{{ route('clientes.index') }}?search=" + encodeURIComponent(query))
            .then(response => response.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, "text/html");

                const tabla = doc.querySelector("#tabla-clientes").innerHTML;
                document.getElementById("tabla-clientes").innerHTML = tabla;
            });
    }, 300); 
});
</script>

@endsection
