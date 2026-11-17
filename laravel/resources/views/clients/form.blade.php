@extends('layouts.app')
@section('title', $client->exists ? 'Editar cliente' : 'Nuevo cliente')
@section('heading', $client->exists ? 'Editar cliente' : 'Nuevo cliente')
@section('subtitle', 'Mantén actualizados los datos de contacto')

@section('content')
    <form class="form-card stack" method="POST" action="{{ $client->exists ? route('clients.update', $client) : route('clients.store') }}">
        @csrf @if ($client->exists) @method('PUT') @endif
        <div class="form-grid">
            <label>Nombre del cliente *
                <input class="input" name="name" value="{{ old('name', $client->name) }}" maxlength="255" required>
            </label>
            <label>Celular *
                <input class="input" name="phone" value="{{ old('phone', $client->phone) }}" maxlength="50" required>
            </label>
            <label>Correo electrónico
                <input class="input" type="email" name="email" value="{{ old('email', $client->email) }}" maxlength="255">
            </label>
        </div>
        <label>Notas
            <textarea class="input" name="notes" rows="4" maxlength="5000">{{ old('notes', $client->notes) }}</textarea>
        </label>
        <div class="form-actions"><button class="button primary" type="submit">Guardar cliente</button><a class="button" href="{{ $client->exists ? route('clients.show', $client) : route('clients.index') }}">Cancelar</a></div>
    </form>
@endsection
