@extends('layouts.app')
@section('title', $order->code)
@section('heading', $order->code.' · '.$order->repair_type)
@section('subtitle', 'Ingreso '.$order->received_at->format('d/m/Y H:i'))

@section('content')
    <div class="toolbar"><a class="button" href="{{ route('orders.index') }}">← Volver a órdenes</a>@include('partials.status', ['status' => $order->status])</div>
    <div class="detail-grid">
        <article class="detail-card"><span>Cliente</span><b>
            @if ($order->registeredClient)<a href="{{ route('clients.show', $order->registeredClient) }}">{{ $order->client }}</a>@else{{ $order->client }}@endif
            </b><a class="subtle" href="tel:{{ $order->phone }}">{{ $order->phone }}</a></article>
        <article class="detail-card"><span>Maquinaria / equipo</span><b>{{ $order->equipment ?: 'No especificado' }}</b></article>
    </div>
    @if ($order->notes)<section class="panel panel-body section"><b>Notas / diagnóstico</b><p>{{ $order->notes }}</p></section>@endif

    @if ($order->quote_data)
        @php
            $quote = $order->quote_data;
        @endphp
        <style>
            @media print {
                body * { visibility: hidden; }
                .quote-document, .quote-document * { visibility: visible; }
                .quote-document { position: absolute; inset: 0; width: 100%; margin: 0; box-shadow: none; }
                .quote-document .no-print { display: none !important; }
            }
        </style>
        <section class="section panel quote-document">
            <div class="panel-header">
                <div><h2>Cotización · {{ $quote['code'] }}</h2><span>Creada el {{ $order->quoted_at->format('d/m/Y H:i') }}</span></div>
                <button class="button no-print" type="button" onclick="window.print()">Imprimir cotización</button>
            </div>
            <div class="panel-body stack">
                <div class="detail-grid">
                    <article class="detail-card"><span>Cliente</span><b>{{ $quote['client'] }}</b><span>{{ $quote['phone'] }}</span></article>
                    <article class="detail-card"><span>Equipo / reparación</span><b>{{ $quote['equipment'] ?: 'No especificado' }}</b><span>{{ $quote['repair_type'] }}</span></article>
                </div>
                @if ($quote['notes'])<div><b>Diagnóstico / notas</b><p>{{ $quote['notes'] }}</p></div>@endif
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Concepto</th><th>Cantidad</th><th class="numeric">Precio unitario</th><th class="numeric">Importe</th></tr></thead>
                        <tbody>
                            @forelse ($quote['parts'] as $part)
                                <tr><td>{{ $part['name'] }}<span class="subtle">{{ $part['category'] }}</span></td><td>{{ $part['quantity'] }}</td><td class="numeric">Q {{ number_format($part['unit_price'], 2) }}</td><td class="numeric">Q {{ number_format($part['total'], 2) }}</td></tr>
                            @empty<tr><td colspan="4" class="empty">Sin repuestos cotizados.</td></tr>@endforelse
                            @foreach ($quote['labor_items'] as $labor)
                                <tr><td>{{ $labor['description'] }}<span class="subtle">Mano de obra</span></td><td>—</td><td class="numeric">—</td><td class="numeric">Q {{ number_format($labor['amount'], 2) }}</td></tr>
                            @endforeach
                        </tbody>
                        <tfoot><tr><th colspan="3" class="numeric">Total cotizado</th><th class="numeric">Q {{ number_format($quote['total'], 2) }}</th></tr></tfoot>
                    </table>
                </div>
            </div>
        </section>
    @endif

    @if ($order->status !== 'quoted')
    <section class="section panel">
        <div class="panel-header"><h2>{{ $order->status === 'reception' ? 'Repuestos cotizados' : 'Repuestos utilizados' }}</h2><span>Q {{ number_format($order->parts->sum(fn ($part) => $part->quantity * (float) $part->unit_price), 2) }}</span></div>
        <div class="table-wrap"><table><thead><tr><th>Producto</th><th>Cantidad</th><th class="numeric">Precio unitario</th><th class="numeric">Importe</th><th></th></tr></thead><tbody>
            @forelse ($order->parts as $part)
                <tr><td>{{ $part->name }}<span class="subtle">{{ $part->category }}</span></td><td>{{ $part->quantity }}</td><td class="numeric">Q {{ number_format($part->unit_price, 2) }}</td><td class="numeric">Q {{ number_format($part->quantity * (float) $part->unit_price, 2) }}</td>
                    <td>@if (in_array($order->status, ['reception', 'repairing', 'ready'], true))<form method="POST" action="{{ route('orders.parts.destroy', [$order, $part]) }}">@csrf @method('DELETE')<button class="button danger" type="submit">Quitar</button></form>@endif</td>
                </tr>
            @empty<tr><td colspan="5" class="empty">Aún no se han agregado repuestos.</td></tr>@endforelse
        </tbody></table></div>
        @if (in_array($order->status, ['reception', 'repairing', 'ready'], true))
            <form class="inline-form panel-body" method="POST" action="{{ route('orders.parts.store', $order) }}">@csrf
                <label>Repuesto<select class="input" name="product_id" required><option value="">Selecciona un producto</option>@foreach ($products as $product)<option value="{{ $product->id }}">{{ $product->name }} · {{ $product->stock }} disponibles · Q {{ number_format($product->price, 2) }}</option>@endforeach</select></label>
                <label style="max-width:120px">Cantidad<input class="input" type="number" name="quantity" min="1" value="1" required></label>
                <button class="button" type="submit">Agregar repuesto</button>
            </form>
            @if ($order->status === 'reception')<p class="panel-body subtle">Los repuestos se descuentan del inventario cuando inicies la reparación.</p>@endif
        @endif
    </section>

    <section class="section panel">
        <div class="panel-header"><h2>Mano de obra</h2><span>Q {{ number_format($order->laborItems->sum('amount'), 2) }}</span></div>
        <div class="table-wrap"><table><thead><tr><th>Descripción</th><th class="numeric">Monto</th><th></th></tr></thead><tbody>
            @forelse ($order->laborItems as $labor)
            <tr><td>{{ $labor->description }}</td><td class="numeric">Q {{ number_format($labor->amount, 2) }}</td><td>@if (in_array($order->status, ['reception', 'repairing', 'ready'], true))<form method="POST" action="{{ route('orders.labor.destroy', [$order, $labor->id]) }}">@csrf @method('DELETE')<button class="button danger" type="submit">Quitar</button></form>@endif</td></tr>
            @empty<tr><td colspan="3" class="empty">No se han registrado trabajos.</td></tr>@endforelse
        </tbody></table></div>
        @if (in_array($order->status, ['reception', 'repairing', 'ready'], true))
            <form class="inline-form panel-body" method="POST" action="{{ route('orders.labor.store', $order) }}">@csrf
                <label>Descripción<input class="input" name="description" required maxlength="255"></label>
                <label style="max-width:180px">Monto (Q)<input class="input" type="number" name="amount" min="0.01" step="0.01" required></label>
                <button class="button" type="submit">Añadir mano de obra</button>
            </form>
        @endif
    </section>
    @endif

    <div class="columns section">
        <section class="panel">
            <div class="panel-header"><h2>Abonos del cliente</h2></div>
            <div class="table-wrap"><table><thead><tr><th>Fecha</th><th>Método</th><th class="numeric">Monto</th></tr></thead><tbody>
                @forelse ($order->payments as $payment)<tr><td>{{ $payment->paid_at->format('d/m/Y H:i') }}</td><td>{{ $payment->method }}</td><td class="numeric positive">Q {{ number_format($payment->amount, 2) }}</td>
                @empty<tr><td colspan="3" class="empty">Todavía no se registran abonos.</td></tr>@endforelse
            </tbody></table></div>
            @if (in_array($order->status, ['repairing', 'ready'], true) && $order->balance > 0.001)
                <form class="panel-body stack" method="POST" action="{{ route('orders.payments.store', $order) }}">@csrf
                    <label>Monto (saldo: Q {{ number_format($order->balance, 2) }})<input class="input" type="number" name="amount" min="0.01" max="{{ number_format($order->balance, 2, '.', '') }}" step="0.01" required></label>
                    <label>Método<select class="input" name="method"><option>Efectivo</option><option>Transferencia</option><option>Tarjeta</option><option>Billetera digital</option></select></label>
                    <button class="button primary" type="submit">Registrar abono</button>
                </form>
            @endif
        </section>
        <section class="panel">
            <div class="panel-header"><h2>Resumen de costos</h2></div>
            <div class="panel-body stack">
                <div>Repuestos <b style="float:right">Q {{ number_format($order->parts->sum(fn ($part) => $part->quantity * (float) $part->unit_price), 2) }}</b></div>
                <div>Mano de obra <b style="float:right">Q {{ number_format($order->laborItems->sum('amount'), 2) }}</b></div>
                <hr style="width:100%;border-color:var(--line)">
                <div>{{ $order->status === 'reception' ? 'Total estimado' : ($order->status === 'quoted' ? 'Total cotizado' : 'Total') }} <b style="float:right">Q {{ number_format($order->total, 2) }}</b></div>
                @if (in_array($order->status, ['reception', 'quoted'], true))
                    <div class="subtle">El saldo se calcula al iniciar la reparación.</div>
                @else
                    <div>Abonado <b class="positive" style="float:right">Q {{ number_format($order->paid, 2) }}</b></div>
                    <div>Saldo pendiente <b class="{{ $order->balance > 0 ? 'negative' : 'positive' }}" style="float:right">Q {{ number_format(max($order->balance, 0), 2) }}</b></div>
                @endif
                @if ($order->status !== 'completed')
                    @php
                        $next = ['quoted' => ['repairing', 'Iniciar reparación'], 'repairing' => ['ready', 'Marcar como lista'], 'ready' => ['completed', 'Finalizar y entregar']][$order->status] ?? null;
                    @endphp
                    @if ($order->status === 'reception')
                        <p class="subtle">Agrega los repuestos y la mano de obra para preparar el presupuesto.</p>
                        <form method="POST" action="{{ route('orders.quote.store', $order) }}">@csrf
                            <button class="button primary full" type="submit">Crear cotización</button>
                        </form>
                    @elseif ($next && ($next[0] !== 'completed' || $order->balance <= 0.001))
                        <form method="POST" action="{{ route('orders.status', $order) }}">@csrf @method('PATCH')
                            <input type="hidden" name="status" value="{{ $next[0] }}">
                            <button class="button primary full" type="submit">{{ $next[1] }}</button>
                        </form>
                    @elseif ($order->status === 'ready')
                        <p class="subtle">Registra el pago completo para finalizar y entregar esta orden.</p>
                    @endif
                @else
                    <p class="positive">Orden archivada · garantía del taller por 90 días.</p>
                @endif
            </div>
        </section>
    </div>
@endsection
