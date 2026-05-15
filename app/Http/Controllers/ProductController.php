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

        if ($request->filled('market_type')) {
            $query->where('market_type', $request->market_type);
        }

        if ($request->filled('sub_market_type')) {
            $query->where('sub_market_type', $request->sub_market_type);
        }

        if ($request->filled('sub_payment_mode')) {
            $query->where('sub_payment_mode', $request->sub_payment_mode);
        }

        $products   = $query->orderBy('product_name')->paginate(15)->withQueryString();
        $categories = Product::whereNotNull('category')->distinct()->orderBy('category')->pluck('category');
        $totalCount  = Product::count();
        $activeCount = Product::where('is_active', true)->count();

        return view('products.index', compact('products', 'categories', 'totalCount', 'activeCount'));
    }

    public function create()
    {
        return view('products.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_name'      => 'required|string|max:200',
            'dsi_code'          => 'nullable|string|max:50',
            'category'          => 'nullable|string|max:10',
            'partner_type'      => 'nullable|string|max:50',
            'description'       => 'nullable|string',
            'publish_rate'      => 'nullable|numeric|min:0',
            'komisi'            => 'nullable|numeric|min:0',
            'nett_price'        => 'nullable|numeric|min:0',
            'unit_price_dsi'    => 'nullable|numeric|min:0',
            'default_price'     => 'required|numeric|min:0',
            'unit'              => 'required|string|max:30',
            'payment_mode'      => 'nullable|string|max:20',
            'is_active'         => 'boolean',
            'market_type'       => 'nullable|in:foreign,domestic',
            'sub_market_type'   => 'nullable|in:adult,child',
            'sub_payment_mode'  => 'nullable|in:NETT,GROSS',
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
            'product_name'      => 'required|string|max:200',
            'dsi_code'          => 'nullable|string|max:50',
            'category'          => 'nullable|string|max:10',
            'partner_type'      => 'nullable|string|max:50',
            'description'       => 'nullable|string',
            'publish_rate'      => 'nullable|numeric|min:0',
            'komisi'            => 'nullable|numeric|min:0',
            'nett_price'        => 'nullable|numeric|min:0',
            'unit_price_dsi'    => 'nullable|numeric|min:0',
            'default_price'     => 'required|numeric|min:0',
            'unit'              => 'required|string|max:30',
            'payment_mode'      => 'nullable|string|max:20',
            'is_active'         => 'boolean',
            'market_type'       => 'nullable|in:foreign,domestic',
            'sub_market_type'   => 'nullable|in:adult,child',
            'sub_payment_mode'  => 'nullable|in:NETT,GROSS',
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
