@extends('layouts.app')
@section('title', 'Órdenes')
@section('heading', 'Órdenes de trabajo')
@section('subtitle', 'Registro de ingresos y seguimiento de reparaciones')

@section('content')
    <div class="toolbar">
        <form method="GET" action="{{ route('orders.index') }}">
            <input class="input search-input" name="q" value="{{ $search }}" placeholder="Buscar cliente, código, equipo o celular">
            <select class="input" name="status" aria-label="Filtrar por estado">
                @foreach (['all' => 'Todos los estados', 'reception' => 'Cotización pendiente', 'quoted' => 'Cotización creada', 'repairing' => 'En reparación', 'ready' => 'Listo', 'completed' => 'Finalizada'] as $value => $label)
                    <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <button class="button" type="submit">Buscar</button>
        </form>
        <a class="button primary" href="{{ route('orders.create') }}">＋ Registrar ingreso</a>
    </div>
    <section class="panel"><div class="table-wrap"><table>
        <thead><tr><th>Código</th><th>Cliente y equipo</th><th>Tipo</th><th>Estado</th><th>Ingreso</th><th class="numeric">Total</th><th class="numeric">Saldo</th></tr></thead>
        <tbody>
        @forelse ($orders as $order)
            <tr class="clickable-row" data-order-url="{{ route('orders.show', $order) }}"
                onclick="if (!event.target.closest('a,button,form')) window.location.href=this.dataset.orderUrl;">
                <td><a class="positive" href="{{ route('orders.show', $order) }}">{{ $order->code }}</a></td>
                <td>{{ $order->client }}<span class="subtle">{{ $order->equipment ?: 'Sin equipo' }} · {{ $order->phone }}</span></td>
                <td>{{ $order->repair_type }}</td>
                <td>@include('partials.status', ['status' => $order->status])</td>
                <td>{{ $order->received_at->format('d/m/Y') }}</td>
                <td class="numeric">Q {{ number_format($order->total, 2) }}</td>
                <td class="numeric {{ $order->balance > 0 ? 'negative' : 'positive' }}">
                    @if (in_array($order->status, ['reception', 'quoted'], true))—@else Q {{ number_format(max($order->balance, 0), 2) }}@endif
                </td>
            </tr>
        @empty<tr><td colspan="7" class="empty">No hay órdenes que coincidan con la búsqueda.</td></tr>@endforelse
        </tbody>
    </table></div></section>
@endsection
