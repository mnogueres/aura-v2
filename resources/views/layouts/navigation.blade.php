<nav class="aura-nav">
    <div class="aura-nav-container">
        <div class="aura-nav-content">
            <!-- Logo y Navegación Principal -->
            <div class="aura-nav-left">
                <a href="{{ route('dashboard') }}" class="aura-nav-logo">
                    <span class="aura-logo-text">Aura</span>
                </a>

                <div class="aura-nav-links">
                    <a href="{{ route('dashboard') }}" class="aura-nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        Inicio
                    </a>
                    <a href="{{ route('patients.index') }}" class="aura-nav-link {{ request()->routeIs('patients.*') ? 'active' : '' }}">
                        Pacientes
                    </a>
                </div>
            </div>

            <!-- Usuario (si está autenticado) -->
            @auth
            <div class="aura-nav-right">
                <span class="aura-nav-user">{{ Auth::user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}" class="inline">
                    @csrf
                    <button type="submit" class="aura-nav-logout">
                        Salir
                    </button>
                </form>
            </div>
            @endauth
        </div>
    </div>
</nav>
