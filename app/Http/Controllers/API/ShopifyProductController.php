<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreShopifyProductRequest;
use App\Repositories\ShopifyProductRepository;
use Illuminate\Http\JsonResponse;

class ShopifyProductController extends Controller
{
    public function __construct(protected ShopifyProductRepository $shopifyRepo) {}


    public function create(StoreShopifyProductRequest $request): JsonResponse
    {
        $shopDomain  = $request->header('X-Shopify-Shop-Domain');
        $accessToken = $request->header('X-Shopify-Access-Token');

        if (!$shopDomain || !$accessToken) {
            return response()->json([
                'success' => false,
                'message' => 'Missing Shopify headers: X-Shopify-Shop-Domain or X-Shopify-Access-Token'
            ], 400);
        }

        $data    = $request->validated();

        $result  = $this->shopifyRepo->createProduct($data, $accessToken, $shopDomain);

        $status  = $result['success'] ? 200 : ($result['status'] ?? 500);

        return response()->json($result, $status);
    }
}
