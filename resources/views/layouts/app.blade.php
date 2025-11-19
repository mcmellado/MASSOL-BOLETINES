<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRM Fotovoltaica</title>

    {{-- Bootstrap 5 vía Vite --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Estilos opcionales personalizados --}}
    <style>
        body {
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>

    {{-- Barra de navegación --}}
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand fw-bold" href="{{ route('clientes.index') }}">
                 BOLETINES MASSOL
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                    data-bs-target="#navbarNav" aria-controls="navbarNav"
                    aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">

                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('clientes*') ? 'active' : '' }}"
                           href="{{ route('clientes.index') }}">
                           Clientes
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('boletines*') ? 'active' : '' }}"
                           href="{{ route('boletines.index') }}">
                           Boletines
                        </a>
                    </li>
                </ul>

            </div>
        </div>
    </nav>

    {{-- Contenido dinámico --}}
    <main>
        @yield('content')
    </main>

    {{-- Footer sencillo --}}
    <footer class="text-center text-muted py-3 mt-4">
        <small>CRM Fotovoltaica © {{ date('Y') }}</small>
    </footer>

</body>
</html>
