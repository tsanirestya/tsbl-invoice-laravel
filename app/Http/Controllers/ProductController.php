<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::query();

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('product_name', 'like', '%' . $request->search . '%')
                  ->orWhere('dsi_code', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('active')) {
            $query->where('is_active', $request->active);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        $products   = $query->orderBy('product_name')->paginate(15)->withQueryString();
        $categories = Product::whereNotNull('category')->distinct()->orderBy('category')->pluck('category');

        return view('products.index', compact('products', 'categories'));
    }

    public function create()
    {
        return view('products.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_name'  => 'required|string|max:200',
            'category'      => 'nullable|string|max:10',
            'description'   => 'nullable|string',
            'default_price' => 'required|numeric|min:0',
            'unit'          => 'required|string|max:30',
            'is_active'     => 'boolean',
        ]);

        $validated['is_active']  = $request->boolean('is_active', true);
        $validated['created_by'] = auth()->id();

        Product::create($validated);

        return redirect()->route('products.index')->with('success', 'Produk berhasil ditambahkan.');
    }

    public function edit(Product $product)
    {
        return view('products.edit', compact('product'));
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'product_name'  => 'required|string|max:200',
            'category'      => 'nullable|string|max:10',
            'description'   => 'nullable|string',
            'default_price' => 'required|numeric|min:0',
            'unit'          => 'required|string|max:30',
            'is_active'     => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $product->update($validated);

        return redirect()->route('products.index')->with('success', 'Produk berhasil diperbarui.');
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return redirect()->route('products.index')->with('success', 'Produk berhasil dihapus.');
    }
}
