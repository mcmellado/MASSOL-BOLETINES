@extends('layouts.app')

@section('content')
<div class="container mt-4">

    <h2>Nuevo Boletín</h2>

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
            <form action="{{ route('boletines.store') }}" method="POST">
                @csrf

                @include('boletines._form', ['boletin' => null])

            </form>
        </div>
    </div>
</div>
@endsection
