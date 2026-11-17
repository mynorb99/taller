@extends('layouts.app')
@section('title', 'Panel')
@section('heading', 'Panel de control')
@section('subtitle', 'Resumen operativo del taller')

@section('content')
    <div class="grid stats">
        <article class="stat"><span class="label">Órdenes activas</span><strong>{{ $activeOrders->count() }}</strong><small>{{ $repairingOrders }} en taller · {{ $readyOrders }} listas para entrega</small></article>
        <article class="stat"><span class="label">Por cobrar</span><strong>Q {{ number_format($receivables, 2) }}</strong><small>Saldo pendiente en órdenes abiertas</small></article>
        <article class="stat"><span class="label">Balance de hoy</span><strong class="{{ $todayIncome >= $todayExpenses ? 'positive' : 'negative' }}">Q {{ number_format($todayIncome - $todayExpenses, 2) }}</strong><small><span class="positive">+ Q {{ number_format($todayIncome, 2) }}</span> ingresos · <span class="negative">− Q {{ number_format($todayExpenses, 2) }}</span> gastos</small></article>
        <article class="stat"><span class="label">Stock crítico</span><strong>{{ $criticalProducts->count() }}</strong><small>Productos en el mínimo o por debajo</small></article>
    </div>

    <div class="columns">
        <section class="panel">
            <div class="panel-header"><h2>Órdenes recientes</h2><a class="button" href="{{ route('orders.index') }}">Ver todas</a></div>
            <div class="table-wrap">
                <table><thead><tr><th>Orden</th><th>Cliente</th><th>Estado</th><th class="numeric">Total</th></tr></thead>
                    <tbody>
                    @forelse ($recentOrders as $order)
                        <tr>
                            <td><a class="positive" href="{{ route('orders.show', $order) }}">{{ $order->code }}</a><span class="subtle">{{ $order->received_at->format('d/m/Y') }}</span></td>
                            <td>{{ $order->client }}<span class="subtle">{{ $order->equipment ?: 'Sin equipo especificado' }}</span></td>
                            <td>@include('partials.status', ['status' => $order->status])</td>
                            <td class="numeric">Q {{ number_format($order->total, 2) }}<span class="subtle">Saldo Q {{ number_format(max($order->balance, 0), 2) }}</span></td>
                        </tr>
                    @empty<tr><td colspan="4" class="empty">Todavía no hay órdenes registradas.</td></tr>@endforelse
                    </tbody>
                </table>
            </div>
        </section>
        <section class="panel">
            <div class="panel-header"><h2>Stock crítico</h2><a class="button" href="{{ route('products.index') }}">Inventario</a></div>
            @forelse ($criticalProducts as $product)
                <div class="panel-body" style="padding:12px 17px;border-bottom:1px solid var(--line)">
                    <b>{{ $product->name }}</b><span class="subtle">{{ $product->stock }} unidades · mínimo {{ $product->minimum_stock }}</span>
                </div>
            @empty<div class="empty">Todo el inventario está por encima del mínimo.</div>@endforelse
        </section>
    </div>
@endsection
