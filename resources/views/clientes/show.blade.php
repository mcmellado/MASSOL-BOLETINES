@extends('layouts.app')

@section('content')

<style>
    :root {
        --orange-main: #ff922b;
        --orange-dark: #f76707;
        --orange-soft: #fff4e6;
        --orange-border: #ffe8cc;
        --gray-bg: #f6f6f7;
    }

    body {
        background-color: var(--gray-bg);
    }

    .container-centered {
        max-width: 1000px;
        margin: 0 auto;
    }

    h2.page-title {
        font-size: 1.8rem;
        font-weight: 600;
        margin-bottom: .2rem;
    }

    .subtitle {
        font-size: .9rem;
        color: #6c757d;
    }

    /* Botones */
    .btn-orange {
        background: var(--orange-main);
        border-color: var(--orange-main);
        color: white;
        font-weight: 500;
        border-radius: .5rem;
    }
    .btn-orange:hover {
        background: var(--orange-dark);
        border-color: var(--orange-dark);
    }

    .btn-outline-orange {
        border-color: var(--orange-main);
        color: var(--orange-main);
        font-weight: 500;
        border-radius: .5rem;
    }
    .btn-outline-orange:hover {
        background: var(--orange-main);
        color: white;
    }

    .btn-outline-red {
        border-color: #e03131;
        color: #e03131;
        font-weight: 500;
        border-radius: .5rem;
    }
    .btn-outline-red:hover {
        background: #e03131;
        color: white;
    }

    /* Tarjeta de datos */
    .client-panel {
        background: white;
        border: 1px solid var(--orange-border);
        border-radius: .9rem;
        padding: 1.5rem 1.7rem;
        margin-top: 1rem;
        box-shadow: 0 5px 14px rgba(0,0,0,.07);
    }

    .section-heading {
        font-weight: 600;
        font-size: 1.1rem;
        margin-bottom: 1.2rem;
        display: flex;
        align-items: center;
        gap: .6rem;
        color: #d9480f;
    }
    .section-heading::before {
        content: "";
        width: .35rem;
        height: 1.4rem;
        border-radius: 1rem;
        background: var(--orange-main);
        display: inline-block;
    }

    .client-info p strong {
        color: #333;
        font-weight: 600;
    }

    .client-info p {
        margin-bottom: .7rem;
        font-size: .95rem;
    }

    /* Tabla */
    .table-header-orange {
        background: var(--orange-main) !important;
        color: white !important;
    }

    .table-hover tbody tr:hover {
        background-color: var(--orange-soft);
    }
</style>

<div class="container container-centered py-4">

    {{-- ALERTA --}}
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    {{-- CABECERA --}}
    <div class="d-flex justify-content-between flex-wrap gap-3 mb-4">

        <div>
            <h2 class="page-title">Ficha del cliente</h2>
            <div class="subtitle">
                {{ $cliente->nombre }} {{ $cliente->primer_apellido }} {{ $cliente->segundo_apellido }}
            </div>
        </div>

        <div class="text-end">
            <a href="{{ route('clientes.index') }}" class="btn btn-outline-secondary">
                Volver
            </a>
            <a href="{{ route('clientes.edit', $cliente) }}" class="btn btn-outline-orange">
                Editar
            </a>
            <form action="{{ route('clientes.destroy', $cliente) }}" class="d-inline" method="POST"
                  onsubmit="return confirm('¿Eliminar este cliente y sus boletines?');">
                @csrf
                @method('DELETE')
                <button class="btn btn-outline-red">Eliminar</button>
            </form>
        </div>

    </div>

    {{-- PANEL: DATOS DEL CLIENTE --}}
    <div class="client-panel">

        <h3 class="section-heading">Datos del cliente</h3>

        <div class="row client-info">

            <div class="col-md-6">
                <p><strong>Nombre:</strong><br>
                    {{ $cliente->nombre }} {{ $cliente->primer_apellido }} {{ $cliente->segundo_apellido }}
                </p>

                <p><strong>DNI/CIF:</strong><br>
                    {{ $cliente->dni_cif }}
                </p>

                <p><strong>Teléfono:</strong><br>
                    {{ $cliente->telefono }}
                </p>
            </div>

            <div class="col-md-6">
                <p><strong>Email:</strong><br>
                    {{ $cliente->email }}
                </p>

                <p><strong>Dirección:</strong><br>
                    {{ $cliente->direccion }}
                </p>

                <p><strong>Población / Provincia:</strong><br>
                    {{ $cliente->poblacion }} ({{ $cliente->provincia }})
                </p>

                <p><strong>Código Postal:</strong><br>
                    {{ $cliente->codigo_postal }}
                </p>
            </div>

        </div>

    </div>

    {{-- PANEL: BOLETINES --}}
    <div class="d-flex justify-content-between align-items-center mt-4 mb-2">
        <h4 class="mb-0">Boletines</h4>

        <a href="{{ route('boletines.create', ['cliente_id' => $cliente->id]) }}"
           class="btn btn-orange btn-sm">
            + Crear boletín
        </a>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">

            <table class="table table-hover mb-0">
                <thead class="table-header-orange">
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
                               class="btn btn-sm btn-outline-secondary">
                                Ver
                            </a>

                            <a href="{{ route('boletines.edit', $boletin) }}"
                               class="btn btn-sm btn-outline-orange">
                                Editar
                            </a>

                            <form action="{{ route('boletines.destroy', $boletin) }}"
                                  method="POST"
                                  class="d-inline"
                                  onsubmit="return confirm('¿Eliminar este boletín?');">
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
                        <td colspan="6" class="text-center p-3 text-muted">
                            No hay boletines para este cliente.
                        </td>
                    </tr>
                @endforelse
                </tbody>

            </table>

        </div>
    </div>

</div>
@endsection
