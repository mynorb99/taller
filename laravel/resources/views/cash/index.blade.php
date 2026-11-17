@extends('layouts.app')
@section('title', 'Caja')
@section('heading', 'Caja · Libro diario')
@section('subtitle', 'Balance del día e historial de ingresos y gastos')

@section('content')
    <div class="grid stats three">
        <article class="stat"><span class="label">Ingresos de hoy</span><strong class="positive">Q {{ number_format($todayIncome, 2) }}</strong></article>
        <article class="stat"><span class="label">Gastos de hoy</span><strong class="negative">Q {{ number_format($todayExpenses, 2) }}</strong></article>
        <article class="stat"><span class="label">Balance del mes</span><strong class="{{ $monthlyIncome >= $monthlyExpenses ? 'positive' : 'negative' }}">Q {{ number_format($monthlyIncome - $monthlyExpenses, 2) }}</strong></article>
    </div>
    <div class="toolbar">
        <form method="GET" action="{{ route('cash.index') }}"><label>Mes<input class="input" type="month" name="month" value="{{ $month }}"></label><button class="button" type="submit">Ver mes</button></form>
        <a class="button primary" href="{{ route('cash.create') }}">＋ Registrar ingreso / gasto</a>
    </div>
    <section class="panel">
        <div class="panel-header"><h2>Movimientos · {{ \Illuminate\Support\Carbon::createFromFormat('Y-m', $month)->translatedFormat('F Y') }}</h2>
            <span>Ingresos Q {{ number_format($monthlyIncome, 2) }} · Gastos Q {{ number_format($monthlyExpenses, 2) }}</span></div>
        <div class="table-wrap"><table><thead><tr><th>Fecha</th><th>Tipo</th><th>Concepto</th><th>Categoría</th><th class="numeric">Monto</th><th></th></tr></thead><tbody>
            @forelse ($movements as $movement)
                <tr><td>{{ $movement->occurred_at->format('d/m/Y H:i') }}</td><td>{{ $movement->type === 'income' ? 'Ingreso' : 'Gasto' }}</td>
                    <td>{{ $movement->description }}@if ($movement->workOrder)<span class="subtle"><a href="{{ route('orders.show', $movement->workOrder) }}">{{ $movement->workOrder->code }}</a></span>@endif</td>
                    <td>{{ $movement->category }}</td><td class="numeric {{ $movement->type === 'income' ? 'positive' : 'negative' }}">Q {{ number_format($movement->amount, 2) }}</td>
                    <td>@if ($movement->origin === 'manual')<form method="POST" action="{{ route('cash.destroy', $movement) }}" onsubmit="return confirm('¿Eliminar este movimiento?')">@csrf @method('DELETE')<button class="button danger" type="submit">Eliminar</button></form>@endif</td>
                </tr>
            @empty<tr><td colspan="6" class="empty">Sin movimientos para este mes.</td></tr>@endforelse
        </tbody></table></div>
    </section>
@endsection
