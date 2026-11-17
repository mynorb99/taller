<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Panel') · Taller Torque</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    <div class="app-shell">
        <aside class="sidebar" id="sidebar">
            <a class="brand" href="{{ route('dashboard') }}"><span class="brand-mark">🔧</span><span><b>TORQUE</b><small>TALLER MECÁNICO</small></span></a>
            <nav class="nav-list" aria-label="Navegación principal">
                <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">▦ <span>Panel</span></a>
                <a class="nav-link {{ request()->routeIs('orders.*') ? 'active' : '' }}" href="{{ route('orders.index') }}">▤ <span>Órdenes</span></a>
                <a class="nav-link {{ request()->routeIs('quotes.*') ? 'active' : '' }}" href="{{ route('quotes.index') }}">▧ <span>Cotizaciones</span></a>
                <a class="nav-link {{ request()->routeIs('clients.*') ? 'active' : '' }}" href="{{ route('clients.index') }}">◉ <span>Clientes</span></a>
                <a class="nav-link {{ request()->routeIs('products.*') ? 'active' : '' }}" href="{{ route('products.index') }}">▣ <span>Productos</span></a>
                <a class="nav-link {{ request()->routeIs('cash.*') ? 'active' : '' }}" href="{{ route('cash.index') }}">Ｑ <span>Caja</span></a>
                <a class="nav-link {{ request()->routeIs('warranties.*') ? 'active' : '' }}" href="{{ route('warranties.index') }}">◇ <span>Garantías</span></a>
            </nav>
            <div class="sidebar-user">
                <b>{{ auth()->user()->name }}</b><small>{{ auth()->user()->role }}</small>
                <form method="POST" action="{{ route('logout') }}">@csrf
                    <button class="button ghost full" type="submit">Cerrar sesión</button>
                </form>
            </div>
        </aside>

        <div class="main-column">
            <header class="topbar">
                <button class="menu-toggle" type="button" aria-label="Abrir menú" aria-expanded="false" onclick="toggleMenu()">☰</button>
                <div><h1>@yield('heading', 'Panel de control')</h1><p>@yield('subtitle', 'Resumen operativo del taller')</p></div>
                <a class="button primary top-action" href="{{ route('orders.create') }}">＋ Nueva orden</a>
            </header>
            <main class="content">
                @if (session('success'))<div class="alert success" role="status">{{ session('success') }}</div>@endif
                @if ($errors->any() && !request()->routeIs('login*'))
                    <div class="alert error" role="alert">{{ $errors->first() }}</div>
                @endif
                @yield('content')
            </main>
        </div>
    </div>
    <div class="mobile-backdrop" id="mobileBackdrop" onclick="toggleMenu(false)"></div>
    <script>
        function toggleMenu(force) {
            const sidebar = document.getElementById('sidebar');
            const open = typeof force === 'boolean' ? force : !sidebar.classList.contains('open');
            sidebar.classList.toggle('open', open);
            document.getElementById('mobileBackdrop').classList.toggle('show', open);
            document.querySelector('.menu-toggle').setAttribute('aria-expanded', String(open));
        }
    </script>
    @stack('scripts')
</body>
</html>
