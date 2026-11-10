<?php

namespace App\Http\Controllers\Storefront\Account;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Keeps the account's wishlist the same as the one in the browser, so it follows the shopper to other devices.
 */
class WishlistSyncController extends Controller
{
    private const LIMIT = 50;

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['present', 'array', 'max:'.self::LIMIT],
            'ids.*' => ['integer'],
        ]);

        $ids = array_values(array_unique(array_map('intval', $validated['ids'])));
        $existing = Product::query()->whereKey($ids)->pluck('id')->all();
        $kept = array_values(array_filter($ids, fn (int $id): bool => in_array($id, $existing, true)));

        $request->user()->wishlistProducts()->sync($kept);

        return response()->json(['ids' => $kept]);
    }
}
