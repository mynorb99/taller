@extends('layouts.app')
@section('title', 'Clientes')
@section('heading', 'Clientes')
@section('subtitle', 'Historial de reparaciones y saldo pendiente por cliente')

@section('content')
    <div class="toolbar">
        <form method="GET" action="{{ route('clients.index') }}">
            <input class="input search-input" name="q" value="{{ $search }}" placeholder="Buscar por nombre, celular o correo">
            <button class="button" type="submit">Buscar</button>
        </form>
        <a class="button primary" href="{{ route('clients.create') }}">＋ Nuevo cliente</a>
    </div>
    <section class="panel"><div class="table-wrap"><table>
        <thead><tr><th>Cliente</th><th>Contacto</th><th class="numeric">Órdenes</th><th class="numeric">Saldo pendiente</th><th></th></tr></thead>
        <tbody>
        @forelse ($clients as $client)
            <tr>
                <td><a href="{{ route('clients.show', $client) }}"><b>{{ $client->name }}</b></a></td>
                <td>{{ $client->phone }}@if ($client->email)<span class="subtle">{{ $client->email }}</span>@endif</td>
                <td class="numeric">{{ $client->work_orders_count }}</td>
                <td class="numeric {{ $client->outstanding_balance > 0.001 ? 'negative' : 'positive' }}">Q {{ number_format($client->outstanding_balance, 2) }}</td>
                <td><a class="button" href="{{ route('clients.show', $client) }}">Ver órdenes</a></td>
            </tr>
        @empty<tr><td colspan="5" class="empty">No hay clientes que coincidan con la búsqueda.</td></tr>@endforelse
        </tbody>
    </table></div></section>
    {{ $clients->links() }}
@endsection
