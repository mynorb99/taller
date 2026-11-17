@extends('layouts.app')
@section('title', 'Inventario')
@section('heading', 'Inventario de repuestos')
@section('subtitle', 'Stock, costos y trazabilidad por orden')

@section('content')
    <div class="toolbar"><a class="button primary" href="{{ route('products.create') }}">＋ Nuevo producto</a>
        <span class="subtle">{{ $products->count() }} productos · valor en stock Q {{ number_format($products->sum(fn ($product) => $product->price * $product->stock), 2) }}</span></div>
    <section class="panel"><div class="table-wrap"><table><thead><tr><th>Producto</th><th>Categoría</th><th class="numeric">Costo</th><th class="numeric">Venta</th><th>Stock</th><th>Estado</th><th>Acciones</th></tr></thead><tbody>
        @forelse ($products as $product)
            <tr>
                <td><b>{{ $product->name }}</b><span class="subtle">{{ $product->parts_count }} uso(s) en órdenes</span></td>
                <td>{{ $product->category }}</td><td class="numeric">Q {{ number_format($product->cost, 2) }}</td><td class="numeric">Q {{ number_format($product->price, 2) }}</td>
                <td>{{ $product->stock }} <span class="subtle">mínimo {{ $product->minimum_stock }}</span></td>
                <td>@if ($product->stock === 0)<span class="badge" style="color:var(--red)">Agotado</span>@elseif ($product->stock <= $product->minimum_stock)<span class="badge amber">Bajo</span>@else<span class="badge green">OK</span>@endif</td>
                <td><div class="row-actions">
                    <a class="button" href="{{ route('products.edit', $product) }}">Editar</a>
                    <details><summary class="button">＋ Stock</summary><form method="POST" action="{{ route('products.stock', $product) }}" class="inline-form" style="padding-top:8px">@csrf<label>Cantidad<input class="input" type="number" min="1" name="quantity" required></label><button class="button" type="submit">Registrar</button></form></details>
                    @if ($product->parts_count === 0)<form method="POST" action="{{ route('products.destroy', $product) }}" onsubmit="return confirm('¿Eliminar este producto?')">@csrf @method('DELETE')<button class="button danger" type="submit">Eliminar</button></form>@endif
                </div></td>
            </tr>
        @empty<tr><td colspan="7" class="empty">No hay productos registrados.</td></tr>@endforelse
    </tbody></table></div></section>
@endsection
