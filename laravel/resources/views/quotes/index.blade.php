@extends('layouts.app')
@section('title', 'Cotizaciones')
@section('heading', 'Cotizaciones')
@section('subtitle', 'Presupuestos pendientes y listos para iniciar reparación')

@section('content')
    <div class="toolbar">
        <p class="subtle">{{ $orders->count() }} cotizaciones activas</p>
        <a class="button primary" href="{{ route('orders.create') }}">＋ Nueva cotización</a>
    </div>
    <section class="panel">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Código</th><th>Cliente y equipo</th><th>Reparación</th><th>Estado</th><th>Ingreso</th><th class="numeric">Importe</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse ($orders as $order)
                        <tr>
                            <td><a class="positive" href="{{ route('orders.show', $order) }}">{{ $order->code }}</a></td>
                            <td>{{ $order->client }}<span class="subtle">{{ $order->equipment ?: 'Sin equipo' }} · {{ $order->phone }}</span></td>
                            <td>{{ $order->repair_type }}</td>
                            <td>@include('partials.status', ['status' => $order->status])</td>
                            <td>{{ $order->received_at->format('d/m/Y') }}</td>
                            <td class="numeric">Q {{ number_format($order->total, 2) }}</td>
                            <td><a class="button" href="{{ route('orders.show', $order) }}">{{ $order->status === 'reception' ? 'Completar cotización' : 'Ver cotización' }}</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="empty">No hay cotizaciones pendientes. Registra un ingreso para comenzar.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
