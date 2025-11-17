@extends('layouts.app')

@section('content')
<div class="container mt-4">

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">Listado de Boletines</h2>
        <a href="{{ route('boletines.create') }}" class="btn btn-primary">
            + Nuevo Boletín
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Fecha</th>
                            <th>Cliente</th>
                            <th>Nº Registro</th>
                            <th>CUPS</th>
                            <th>Marca inversor</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($boletines as $boletin)
                            <tr>
                                <td>{{ $boletin->id }}</td>
                                <td>{{ $boletin->fecha?->format('d/m/Y') }}</td>
                                <td>
                                    @if($boletin->cliente)
                                        <a href="{{ route('clientes.show', $boletin->cliente) }}">
                                            {{ $boletin->cliente->nombre }} {{ $boletin->cliente->primer_apellido }}
                                        </a>
                                    @else
                                        <em>Sin cliente</em>
                                    @endif
                                </td>
                                <td>{{ $boletin->numero_registro }}</td>
                                <td>{{ $boletin->cups }}</td>
                                <td>{{ $boletin->marca_inversor }}</td>

                                <td class="text-end">
                                    <a href="{{ route('boletines.show', $boletin) }}" class="btn btn-sm btn-info">
                                        Ver
                                    </a>

                                    <a href="{{ route('boletines.edit', $boletin) }}" class="btn btn-sm btn-warning">
                                        Editar
                                    </a>

                                    <form action="{{ route('boletines.destroy', $boletin) }}"
                                          method="POST"
                                          class="d-inline"
                                          onsubmit="return confirm('¿Seguro que quieres eliminar este boletín?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-danger">Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-3">
                                    No hay boletines registrados todavía.
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
