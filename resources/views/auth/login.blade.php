@extends('layouts.app')
@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-md-6 col-lg-4">

            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h1 class="h4 mb-3">Entrar</h1>

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login.post') }}">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label">Usuario</label>
                            <input
                                type="text"
                                name="username"
                                value="{{ old('username') }}"
                                required
                                autofocus
                                class="form-control"
                                placeholder="Tu usuario"
                            >
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Contraseña</label>
                            <input
                                type="password"
                                name="password"
                                required
                                class="form-control"
                                placeholder="Tu contraseña"
                            >
                        </div>

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="remember" value="1" id="remember">
                            <label class="form-check-label" for="remember">Recordarme</label>
                        </div>

                        <button type="submit" class="btn btn-dark w-100">
                            Entrar
                        </button>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
