@extends('layouts.app')

@section('content')
<div class="container mt-4">

    <h2>Editar Boletín</h2>

    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Hay errores en el formulario:</strong>
            <ul class="mb-0 mt-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card shadow-sm mt-3">
        <div class="card-body">

            <form action="{{ route('boletines.update', $boletin) }}" method="POST">
                @csrf
                @method('PUT')

                {{-- Aquí usamos el formulario de BOLETINES, no el de clientes --}}
                @include('boletines._form', ['boletin' => $boletin])

            </form>

        </div>
    </div>

</div>
@endsection
