<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class ShopifyApiService
{
    public function sendGraphQLRequest(string $query, array $variables, string $accessToken, string $shopDomain): array
    {
        $client = new Client(['base_uri' => "https://{$shopDomain}/admin/api/2025-07/graphql.json"]);

        try {
            $response = $client->post('', [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'X-Shopify-Access-Token' => $accessToken,
                ],
                'json' => [
                    'query'     => $query,
                    'variables' => $variables,
                ],
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            return [
                'success' => false,
                'message' => 'Request to Shopify failed',
                'error' => $e->getMessage(),
            ];
        }
    }
}
