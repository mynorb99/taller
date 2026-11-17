@extends('layouts.app')
@section('title', $client->name)
@section('heading', $client->name)
@section('subtitle', 'Historial de ingresos al taller y estado de cuenta')

@section('content')
    <div class="toolbar">
        <a class="button" href="{{ route('clients.index') }}">← Volver a clientes</a>
        <a class="button" href="{{ route('clients.edit', $client) }}">Editar datos</a>
        <a class="button primary" href="{{ route('orders.create') }}">＋ Nueva orden</a>
    </div>
    <div class="grid stats three">
        <article class="stat"><span class="label">Saldo pendiente</span><strong class="{{ $client->outstanding_balance > 0.001 ? 'negative' : 'positive' }}">Q {{ number_format($client->outstanding_balance, 2) }}</strong><small>Saldo acumulado de todas sus órdenes</small></article>
        <article class="stat"><span class="label">Órdenes pendientes</span><strong>{{ $pendingOrders->count() }}</strong><small>Órdenes con saldo por cobrar</small></article>
        <article class="stat"><span class="label">Ingresos registrados</span><strong>{{ $orders->count() }}</strong><small>Historial de reparaciones del cliente</small></article>
    </div>
    <div class="detail-grid">
        <article class="detail-card"><span>Celular</span><b><a href="tel:{{ $client->phone }}">{{ $client->phone }}</a></b></article>
        <article class="detail-card"><span>Correo</span><b>{{ $client->email ?: 'No registrado' }}</b></article>
    </div>
    @if ($client->notes)<section class="panel panel-body section"><b>Notas del cliente</b><p>{{ $client->notes }}</p></section>@endif

    <section class="section panel">
        <div class="panel-header"><h2>Órdenes de trabajo</h2><span>{{ $orders->count() }} en total</span></div>
        <div class="table-wrap"><table>
            <thead><tr><th>Orden</th><th>Equipo / reparación</th><th>Ingreso</th><th>Estado</th><th class="numeric">Total</th><th class="numeric">Abonado</th><th class="numeric">Saldo</th></tr></thead>
            <tbody>
            @forelse ($orders as $order)
                <tr>
                    <td><a class="positive" href="{{ route('orders.show', $order) }}">{{ $order->code }}</a></td>
                    <td>{{ $order->equipment ?: 'Sin equipo' }}<span class="subtle">{{ $order->repair_type }}</span></td>
                    <td>{{ $order->received_at->format('d/m/Y') }}</td>
                    <td>@include('partials.status', ['status' => $order->status])</td>
                    <td class="numeric">Q {{ number_format($order->total, 2) }}</td>
                    <td class="numeric">Q {{ number_format($order->paid, 2) }}</td>
                    <td class="numeric {{ $order->balance > 0.001 ? 'negative' : 'positive' }}">
                        @if (in_array($order->status, ['reception', 'quoted'], true))—@else Q {{ number_format(max($order->balance, 0), 2) }}@endif
                    </td>
                </tr>
            @empty<tr><td colspan="7" class="empty">Este cliente todavía no tiene órdenes.</td></tr>@endforelse
            </tbody>
        </table></div>
    </section>
@endsection
