<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Iniciar sesión · Taller Torque</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="login-page">
    <main class="login-card">
        <div class="brand-mark">🔧</div>
        <p class="eyebrow">Mecánica de maquinaria</p>
        <h1>Taller <span>Torque</span></h1>
        <p class="muted">Ingresa para administrar órdenes, inventario y caja.</p>

        @if ($errors->any())
            <div class="alert error" role="alert">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('login.store') }}" class="stack">
            @csrf
            <label>Usuario
                <input class="input" name="username" value="{{ old('username') }}" autocomplete="username" required autofocus>
            </label>
            <label>Contraseña
                <input class="input" type="password" name="password" autocomplete="current-password" required>
            </label>
            <label class="check-row"><input type="checkbox" name="remember"> Mantener sesión iniciada</label>
            <button class="button primary" type="submit">Ingresar al taller</button>
        </form>
        <p class="demo-hint">Cuenta de demostración: <b>admin</b> / <b>taller123</b></p>
    </main>
</body>
</html>
