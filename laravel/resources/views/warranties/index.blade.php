@extends('layouts.app')
@section('title', 'Garantías')
@section('heading', 'Historial y garantías')
@section('subtitle', 'Registro permanente de trabajos finalizados · garantía de 90 días')

@section('content')
    <section class="panel"><div class="table-wrap"><table>
        <thead><tr><th>Orden</th><th>Cliente</th><th>Equipo</th><th>Fecha de entrega</th><th>Garantía</th><th class="numeric">Total</th></tr></thead>
        <tbody>
        @forelse ($orders as $order)
            @php $warrantyExpires = $order->completed_at->copy()->addDays(90); @endphp
            <tr>
                <td><a class="positive" href="{{ route('orders.show', $order) }}">{{ $order->code }}</a><span class="subtle">{{ $order->repair_type }}</span></td>
                <td>{{ $order->client }}<span class="subtle">{{ $order->phone }}</span></td>
                <td>{{ $order->equipment ?: '—' }}</td>
                <td>{{ $order->completed_at->format('d/m/Y') }}</td>
                <td class="{{ $warrantyExpires->isFuture() ? 'positive' : 'subtle' }}">{{ $warrantyExpires->isFuture() ? 'Vigente hasta ' : 'Vencida ' }}{{ $warrantyExpires->format('d/m/Y') }}</td>
                <td class="numeric">Q {{ number_format($order->total, 2) }}</td>
            </tr>
        @empty<tr><td colspan="6" class="empty">Aún no hay órdenes finalizadas.</td></tr>@endforelse
        </tbody>
    </table></div></section>
@endsection
