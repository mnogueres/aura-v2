<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aura</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body>
    <div class="aura-layout">
        <!-- Sidebar -->
        <aside class="aura-sidebar">
            <div class="aura-logo">Aura</div>

            <nav class="aura-nav">
                <a href="#" class="aura-nav-item active">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    Dashboard
                </a>

                <a href="#" class="aura-nav-item">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    Pacientes
                </a>
            </nav>
        </aside>

        <!-- Main -->
        <main class="aura-main">
            <header class="aura-header">
                <h1 class="aura-header-title">@yield('header', 'Dashboard')</h1>

                <div class="aura-user">
                    <span class="aura-user-name">Usuario</span>
                    <form method="POST" action="#" class="aura-user-form">
                        @csrf
                        <button type="submit" class="aura-btn">Salir</button>
                    </form>
                </div>
            </header>

            <div class="aura-content">
                @yield('content')
            </div>
        </main>
    </div>
</body>

</html>