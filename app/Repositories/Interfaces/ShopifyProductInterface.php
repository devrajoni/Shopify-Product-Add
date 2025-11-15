<?php

namespace App\Repositories\Interfaces;

interface ShopifyProductInterface
{
    public function createProduct(array $data, string $accessToken, string $shopDomain): array;
}
