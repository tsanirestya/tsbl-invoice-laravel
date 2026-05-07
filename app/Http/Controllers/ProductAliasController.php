<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductAlias;
use Illuminate\Http\Request;

class ProductAliasController extends Controller
{
    public function index(Product $product)
    {
        $aliases = $product->aliases()->with('creator')->orderBy('alias_name')->get();
        return view('products.aliases', compact('product', 'aliases'));
    }

    public function store(Request $request, Product $product)
    {
        $request->validate([
            'alias_name' => ['required', 'string', 'max:255'],
        ]);

        $alias = strtoupper(trim($request->alias_name));

        // Check not already an exact product name
        if (Product::whereRaw('UPPER(product_name) = ?', [$alias])->exists()) {
            return back()->withErrors(['alias_name' => 'Nama ini sudah merupakan nama produk — tidak perlu alias.']);
        }

        ProductAlias::firstOrCreate(
            ['alias_name' => $alias, 'product_id' => $product->id],
            ['created_by' => auth()->id()]
        );

        return back()->with('success', "Alias '{$alias}' ditambahkan.");
    }

    public function destroy(Product $product, ProductAlias $alias)
    {
        abort_if($alias->product_id !== $product->id, 404);
        $alias->delete();
        return back()->with('success', 'Alias dihapus.');
    }
}
