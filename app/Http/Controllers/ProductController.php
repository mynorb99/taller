<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(): View
    {
        return view('products.index', [
            'products' => Product::withCount('parts')->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('products.form', ['product' => new Product]);
    }

    public function store(Request $request): RedirectResponse
    {
        Product::create($this->validated($request));

        return redirect()->route('products.index')->with('success', 'Producto creado.');
    }

    public function edit(Product $product): View
    {
        return view('products.form', compact('product'));
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $product->update($this->validated($request));

        return redirect()->route('products.index')->with('success', 'Producto actualizado.');
    }

    public function addStock(Request $request, Product $product): RedirectResponse
    {
        $data = $request->validate(['quantity' => ['required', 'integer', 'min:1', 'max:1000000']]);

        $product->increment('stock', $data['quantity']);

        return back()->with('success', "Stock actualizado: {$product->fresh()->stock} unidades.");
    }

    public function destroy(Product $product): RedirectResponse
    {
        if ($product->parts()->exists()) {
            return back()->withErrors(['product' => 'No se puede eliminar: el producto tiene trazabilidad en órdenes.']);
        }

        $product->delete();

        return redirect()->route('products.index')->with('success', 'Producto eliminado.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'cost' => ['required', 'numeric', 'min:0', 'max:9999999999'],
            'price' => ['required', 'numeric', 'min:0', 'max:9999999999'],
            'stock' => ['required', 'integer', 'min:0', 'max:1000000'],
            'minimum_stock' => ['required', 'integer', 'min:0', 'max:1000000'],
        ]);
        $data['category'] = $data['category'] ?: 'General';

        return $data;
    }
}
