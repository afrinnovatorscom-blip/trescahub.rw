<?php
namespace App\Http\Controllers;
use App\Models\Service;
use App\Models\ShopProduct;
use Illuminate\Http\Request;
class SearchController extends Controller
{
    public function live(Request $request)
    {
        $q = trim($request->input('q', ''));
        if (strlen($q) < 2) {
            return response()->json([]);
        }
        $services = Service::with('category')
            ->where('is_in_stock', true)
            ->where(function ($query) use ($q) {
                $query->where('title', 'like', "%{$q}%")
                      ->orWhere('description', 'like', "%{$q}%");
            })
            ->limit(5)
            ->get()
            ->map(fn($s) => [
                'type'      => 'service',
                'title'     => $s->title,
                'subtitle'  => $s->category->title ?? '',
                'thumbnail' => $s->thumbnail ? url('assets/' . ltrim(str_replace('public/assets/', '', $s->thumbnail), '/')) : null,
                'url'       => route('service.show', $s->slug),
            ]);
        $products = ShopProduct::query()->publicCatalog()->with('category')
            ->where(function ($query) use ($q) {
                $query->where('product_name', 'like', "%{$q}%")
                      ->orWhere('description', 'like', "%{$q}%");
            })
            ->limit(5)
            ->get()
            ->map(fn($p) => [
                'type'      => 'product',
                'title'     => $p->product_name,
                'subtitle'  => $p->category->name ?? '',
                'thumbnail' => $p->thumbnail ? url('assets/' . ltrim(str_replace('public/assets/', '', $p->thumbnail), '/')) : null,
                'url'       => route('shop-products.show', $p->slug),
            ]);
        return response()->json(collect($services)->concat($products)->values());
    }
}