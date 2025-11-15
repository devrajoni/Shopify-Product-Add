<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\ShopifyProductController;


Route::prefix('shopify')->group(function () {
    Route::post('/products', [ShopifyProductController::class, 'create']);
});

/*
|--------------------------------------------------------------------------
| Fallback Route
|--------------------------------------------------------------------------
*/

Route::fallback(function () {
    return response()->json([
        'success' => false,
        'message' => 'Endpoint not found.'
    ], 404);
});
