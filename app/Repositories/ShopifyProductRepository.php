<?php

namespace App\Repositories;

use App\Repositories\Interfaces\ShopifyProductInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class ShopifyProductRepository implements ShopifyProductInterface
{
    protected Client $client;

    public function __construct()
    {
        $this->client = new Client(['verify' => false, 'timeout' => 30]);
    }

    protected function guzzleJsonRequest(string $method, string $url, string $accessToken, array $json = []): array
    {
        try {
            $resp = $this->client->request($method, $url, [
                'verify' => false,
                'headers' => [
                    'X-Shopify-Access-Token' => $accessToken,
                    'Content-Type'           => 'application/json',
                    'Accept'                 => 'application/json',
                ],
                'json' => $json,
            ]);

            return ['success' => true, 'data' => json_decode($resp->getBody()->getContents(), true)];
        } catch (RequestException $e) {
            $body = $e->getResponse() ? (string)$e->getResponse()->getBody() : null;

            return [
                'success'   => false,
                'message'   => 'Shopify REST request failed',
                'error'     => $e->getMessage(),
                'response'  => $body,
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'Unexpected error', 'error' => $e->getMessage()];
        }
    }

    protected function shopifyRequest(string $shopDomain, string $accessToken, string $query, array $variables = []): array
    {
        try {
            $url = "https://{$shopDomain}/admin/api/2025-07/graphql.json";
            $resp = $this->client->post($url, [
                'verify' => false,
                'headers' => [
                    'X-Shopify-Access-Token' => $accessToken,
                    'Content-Type'           => 'application/json',
                    'Accept'                 => 'application/json',
                ],
                'json' => [
                    'query'     => $query,
                    'variables' => $variables,
                ],
            ]);

            return ['success' => true, 'data' => json_decode((string)$resp->getBody(), true)];
        } catch (RequestException $e) {
            $body = $e->getResponse() ? (string)$e->getResponse()->getBody() : null;

            return [
                'success'  => false,
                'message'  => 'Shopify GraphQL request failed',
                'error'    => $e->getMessage(),
                'response' => $body,
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'Unexpected error', 'error' => $e->getMessage()];
        }
    }

    protected function getLocationId(string $shopDomain, string $accessToken): array
    {
        try {
            $client = new Client([
                'verify'   => false,
                'base_uri' => "https://{$shopDomain}/admin/api/2025-07/"
            ]);

            $response = $client->get('locations.json', [
                'verify' => false,
                'headers' => [
                    'X-Shopify-Access-Token' => $accessToken,
                    'Accept'                 => 'application/json',
                    'Content-Type'           => 'application/json'
                ],
                'http_errors' => false
            ]);

            $status = $response->getStatusCode();
            $body   = json_decode($response->getBody()->getContents(), true);

            if ($status !== 200) {
                return [
                    'success'   => false,
                    'message'   => "Shopify REST request failed",
                    'status'    => $status,
                    'response'  => $body
                ];
            }

            if (empty($body['locations'])) {
                return [
                    'success' => false,
                    'message' => "No locations found in Shopify store",
                    'status'  => 404
                ];
            }

            return [
                'success'     => true,
                'location_id' => $body['locations'][0]['id']
            ];
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            return [
                'success'   => false,
                'message'   => 'ClientException occurred',
                'error'     => $e->getMessage(),
                'response'  => (string)$e->getResponse()->getBody()
            ];
        } catch (\GuzzleHttp\Exception\ServerException $e) {
            return [
                'success'  => false,
                'message'  => 'ServerException occurred',
                'error'    => $e->getMessage(),
                'response' => (string)$e->getResponse()->getBody()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Unexpected error occurred',
                'error'   => $e->getMessage()
            ];
        }
    }

    protected function updateVariantSku(string $shopDomain, string $accessToken, string $variantGid, string $sku): array
    {
        $numericVariantId   = preg_replace('/^gid:\/\/shopify\/ProductVariant\//', '', $variantGid);

        $url                = "https://{$shopDomain}/admin/api/2025-07/variants/{$numericVariantId}.json";

        return $this->guzzleJsonRequest('PUT', $url, $accessToken, [
            'variant' => [
                'id'   => $numericVariantId,
                'sku'  => $sku,
            ]
        ]);
    }

    public function addProductImages(string $shopDomain, string $accessToken, string $productId, array $images): array
    {
        $map = [];
        $numericId = preg_replace('/^gid:\/\/shopify\/Product\//', '', $productId);

        foreach ($images as $img) {
            $payload = ['image' => ['src' => $img['src']]];

            if (!empty($img['alt'])) {
                $payload['image']['alt'] = $img['alt'];
            }

            $url = "https://{$shopDomain}/admin/api/2025-07/products/{$numericId}/images.json";
            $res = $this->guzzleJsonRequest('POST', $url, $accessToken, $payload);

            if (!$res['success']) {
                return $res;
            }

            if (!empty($img['alt']) && !empty($res['data']['image']['id'])) {
                $map[$img['alt']] = $res['data']['image']['id'];
            }
        }

        return ['success' => true, 'map' => $map];
    }


    protected function setInventoryLevel(string $shopDomain, string $accessToken, string $inventoryItemId, string $locationId, int $available): array
    {
        $url = "https://{$shopDomain}/admin/api/2025-07/inventory_levels/set.json";
        return $this->guzzleJsonRequest('POST', $url, $accessToken, [
            'inventory_item_id' => $inventoryItemId,
            'location_id'       => $locationId,
            'available'         => $available,
        ]);
    }


public function createProduct(array $data, string $accessToken, string $shopDomain): array
{

    $loc = $this->getLocationId($shopDomain, $accessToken);
    if (!$loc['success']) {
        return $loc;
    }
    $locationId = (string)$loc['location_id'];

    $createProductMutation = <<<'GRAPHQL'
        mutation createProduct($input: ProductInput!) {
        productCreate(input: $input) {
            product { id title variants(first:1) { nodes { id } } }
            userErrors { field message }
        }
        }
        GRAPHQL;

    $variables = [
        'input' => [
            'title'           => $data['title'] ?? '',
            'descriptionHtml' => $data['description'] ?? '',
            'vendor'          => $data['vendor'] ?? '',
            'productType'     => $data['product_type'] ?? '',
            'status'          => strtoupper($data['status'] ?? 'ACTIVE'),
        ],
    ];

    $createResp = $this->shopifyRequest($shopDomain, $accessToken, $createProductMutation, $variables);
    if (!$createResp['success']) {
        return $createResp;
    }

    $payload = $createResp['data'] ?? [];
    if (!empty($payload['errors'])) {
        return ['success' => false, 'message' => 'GraphQL errors', 'errors' => $payload['errors']];
    }
    if (!empty($payload['data']['productCreate']['userErrors'])) {
        return ['success' => false, 'message' => 'shopify.productCreate.userErrors', 'errors' => $payload['data']['productCreate']['userErrors']];
    }

    $productId = $payload['data']['productCreate']['product']['id'];

    $defaultVariantNode = $payload['data']['productCreate']['product']['variants']['nodes'][0] ?? null;
    if ($defaultVariantNode && !empty($data['variants'][0])) {
        $variantGid     = $defaultVariantNode['id'];
        $variantNumeric = preg_replace('/^gid:\/\/shopify\/ProductVariant\//', '', $variantGid);

        $payloadVariant = [
            'variant' => [
                'id'                   => $variantNumeric,
                'inventory_management' => 'SHOPIFY',
            ],
        ];

        if (!empty($data['variants'][0]['price'])) {
            $payloadVariant['variant']['price'] = (string)$data['variants'][0]['price'];
        }
        if (!empty($data['variants'][0]['sku'])) {
            $payloadVariant['variant']['sku'] = $data['variants'][0]['sku'];
        }

        $url = "https://{$shopDomain}/admin/api/2025-07/variants/{$variantNumeric}.json";
        $res = $this->guzzleJsonRequest('PUT', $url, $accessToken, $payloadVariant);
        if (!$res['success']) {
            return $res;
        }
    }

    $optionIdMap = [];
    if (!empty($data['options'])) {
        $optionsMutation = <<<'GRAPHQL'
            mutation createOptions($productId: ID!, $options: [OptionCreateInput!]!) {
            productOptionsCreate(productId: $productId, options: $options) {
                product { id options { id name optionValues { id name } } }
                userErrors { field message }
            }
            }
            GRAPHQL;

        $optionsVariables = [
            'productId' => $productId,
            'options'   => array_map(function ($opt, $index) {
                return [
                    'name' => $opt['name'],
                    'position' => $index + 1,
                    'values'   => array_map(fn($v) => ['name' => $v], $opt['values']),
                ];
            }, $data['options'], array_keys($data['options'])),
        ];

        $optionsResp = $this->shopifyRequest($shopDomain, $accessToken, $optionsMutation, $optionsVariables);
        if (!$optionsResp['success']) {
            return $optionsResp;
        }

        if (!empty($optionsResp['data']['data']['productOptionsCreate']['userErrors'])) {
            return ['success' => false, 'message' => 'productOptionsCreate.userErrors', 'errors' => $optionsResp['data']['data']['productOptionsCreate']['userErrors']];
        }

        foreach ($optionsResp['data']['data']['productOptionsCreate']['product']['options'] as $opt) {
            $optionIdMap[$opt['name']] = $opt['id'];
        }
    }

    $queryVariants = <<<'GRAPHQL'
        query getProductVariants($id: ID!) {
        product(id: $id) {
            variants(first: 100) {
            nodes { id selectedOptions { name value } price inventoryItem { id tracked } }
            }
        }
        }
        GRAPHQL;

    $existingResp = $this->shopifyRequest($shopDomain, $accessToken, $queryVariants, ['id' => $productId]);
    if (!$existingResp['success']) {
        return $existingResp;
    }

    $existingVariants   = $existingResp['data']['data']['product']['variants']['nodes'] ?? [];
    $existingVariantMap = [];
    foreach ($existingVariants as $v) {
        $opts = [];
        foreach ($v['selectedOptions'] as $so) {
            $opts[$so['name']] = $so['value'];
        }
        $key = implode('|', array_map(fn($k, $val) => "$k:$val", array_keys($opts), $opts));
        $existingVariantMap[$key] = $v;
    }

    $variantsToCreate = [];
    $variantsToUpdate = [];

    foreach ($data['variants'] as $variant) {
        $optionValuesInput = [];
        foreach ($variant['options'] as $optName => $optValueName) {
            $optionValuesInput[] = [
                'optionId' => $optionIdMap[$optName] ?? null,
                'name'     => $optValueName,
            ];
        }

        $key = implode('|', array_map(fn($k, $val) => "$k:$val", array_keys($variant['options']), $variant['options']));

        if (isset($existingVariantMap[$key])) {
            $variantsToUpdate[] = [
                'id'        => $existingVariantMap[$key]['id'],
                'price'     => (string)$variant['price'],
                'options'   => $variant['options'],
                'sku'       => $variant['sku'] ?? null,
            ];
        } else {
            $variantsToCreate[] = [
                'price'        => (string)$variant['price'],
                'sku'          => $variant['sku'] ?? null,
                'optionValues' => $optionValuesInput,
            ];
        }
    }

    if (!empty($variantsToCreate)) {
        $variantMutation = <<<'GRAPHQL'
        mutation productVariantsBulkCreate($productId: ID!, $variants: [ProductVariantsBulkInput!]!) {
        productVariantsBulkCreate(productId: $productId, variants: $variants) {
            productVariants { id price selectedOptions { name value } }
            userErrors { field message }
        }
        }
        GRAPHQL;

        $variantResp = $this->shopifyRequest($shopDomain, $accessToken, $variantMutation, [
            'productId'        => $productId,
            'variants'         => array_map(fn($v) => [
                'price'        => $v['price'],
                'optionValues' => $v['optionValues'],
            ], $variantsToCreate),
        ]);

        if (!$variantResp['success']) {
            return $variantResp;
        }

        if (!empty($variantResp['data']['data']['productVariantsBulkCreate']['userErrors'])) {
            return ['success' => false, 'message' => 'productVariantsBulkCreate.userErrors', 'errors' => $variantResp['data']['data']['productVariantsBulkCreate']['userErrors']];
        }

        $createdList = $variantResp['data']['data']['productVariantsBulkCreate']['productVariants'] ?? [];
        foreach ($createdList as $createdVariant) {
            $createdOptionsMap = [];
            foreach ($createdVariant['selectedOptions'] as $so) {
                $createdOptionsMap[$so['name']] = $so['value'];
            }

            foreach ($variantsToCreate as $inputVariant) {
                $match = true;
                foreach ($inputVariant['optionValues'] as $ov) {
                    if (!isset($createdOptionsMap[$ov['optionId'] ? array_search($ov['optionId'], $optionIdMap) : '']) ||
                        $createdOptionsMap[array_search($ov['optionId'], $optionIdMap)] != $ov['name']) {
                        $match = false;
                        break;
                    }
                }
                if ($match && !empty($inputVariant['sku'])) {
                    $this->updateVariantSku($shopDomain, $accessToken, $createdVariant['id'], $inputVariant['sku']);
                    break;
                }
            }
        }
    }

    foreach ($variantsToUpdate as $updateVar) {
        if (!empty($updateVar['sku'])) {
            $this->updateVariantSku($shopDomain, $accessToken, $updateVar['id'], $updateVar['sku']);
        }
    }

    $respAfter = $this->shopifyRequest($shopDomain, $accessToken, $queryVariants, ['id' => $productId]);
    if (!$respAfter['success']) return $respAfter;

    $shopifyVariants    = $respAfter['data']['data']['product']['variants']['nodes'] ?? [];
    foreach ($shopifyVariants as $shopifyVariant) {
        $shopifyOptions = [];
        foreach ($shopifyVariant['selectedOptions'] as $o) $shopifyOptions[$o['name']] = $o['value'];

        foreach ($data['variants'] as $inputVariant) {
            if ($shopifyOptions === $inputVariant['options']) {
                $inventoryItemId = preg_replace('/^gid:\/\/shopify\/InventoryItem\//', '', $shopifyVariant['inventoryItem']['id']);
                $available       = (int)($inputVariant['inventory_quantity'] ?? 0);
                $this->setInventoryLevel($shopDomain, $accessToken, $inventoryItemId, $locationId, $available);
            }
        }
    }

    if (!empty($data['images'])) {
        $imagesRes = $this->addProductImages($shopDomain, $accessToken, $productId, $data['images']);
        if (!$imagesRes['success']) return $imagesRes;
    }

    $finalQuery = <<<'GRAPHQL'
        query getProduct($id: ID!) {
        product(id: $id) {
            id
            title
            options { id name optionValues { id name } }
            variants(first: 100) {
            nodes {
                id
                sku
                price
                selectedOptions { name value }
                inventoryQuantity
                inventoryItem { id }
            }
            }
            images(first: 100) { edges { node { id url altText } } }
        }
        }
        GRAPHQL;

    $finalResp = $this->shopifyRequest($shopDomain, $accessToken, $finalQuery, ['id' => $productId]);
    if (!$finalResp['success']) return $finalResp;

    return ['success' => true, 'data' => $finalResp['data']];
}

}
