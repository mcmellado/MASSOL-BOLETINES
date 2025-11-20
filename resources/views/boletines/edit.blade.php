@extends('layouts.app')

@section('content')

<div class="container mt-4">

    <h2 class="mb-3">Editar Boletín</h2>

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

    <div class="card section-card card-lift mt-3">

        <div class="card-header section-card-header">
            <h5 class="mb-0 text-orange">Formulario de edición</h5>
        </div>

        <div class="card-body">

            <form action="{{ route('boletines.update', $boletin) }}" method="POST">
                @csrf
                @method('PUT')

                @include('boletines._form', ['boletin' => $boletin])

            </form>

        </div>
    </div>

</div>

@endsection
