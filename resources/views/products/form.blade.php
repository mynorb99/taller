@extends('layouts.app')
@section('title', $product->exists ? 'Editar producto' : 'Nuevo producto')
@section('heading', $product->exists ? 'Editar producto' : 'Nuevo producto')
@section('subtitle', 'Datos de inventario y precio')

@section('content')
    <form class="form-card stack" method="POST" action="{{ $product->exists ? route('products.update', $product) : route('products.store') }}">
        @csrf @if ($product->exists) @method('PUT') @endif
        <label>Nombre del producto *
            <input class="input" name="name" value="{{ old('name', $product->name) }}" required maxlength="255">
        </label>
        <label>Categoría
            <input class="input" name="category" value="{{ old('category', $product->category) }}" maxlength="255">
        </label>
        <div class="form-grid">
            <label>Costo (Q)<input class="input" type="number" name="cost" min="0" step="0.01" value="{{ old('cost', $product->cost ?? 0) }}" required></label>
            <label>Precio de venta (Q)<input class="input" type="number" name="price" min="0" step="0.01" value="{{ old('price', $product->price) }}" required></label>
            <label>Stock actual<input class="input" type="number" name="stock" min="0" value="{{ old('stock', $product->stock ?? 0) }}" required></label>
            <label>Stock mínimo<input class="input" type="number" name="minimum_stock" min="0" value="{{ old('minimum_stock', $product->minimum_stock ?? 2) }}" required></label>
        </div>
        <div class="form-actions"><button class="button primary" type="submit">Guardar producto</button><a class="button" href="{{ route('products.index') }}">Cancelar</a></div>
    </form>
@endsection
