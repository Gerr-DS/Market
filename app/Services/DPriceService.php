<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DPriceService
{
    /** Search DPrice and recommend a lower price from the product's stated condition. */
    public function recommendation(string $query, ?string $condition): ?array
    {
        $key = config('services.dprice.key');
        if (!is_string($key) || trim($key) === '' || trim($query) === '') {
            return null;
        }

        try {
            $response = Http::acceptJson()
                ->withHeaders(['X-API-Key' => $key])
                ->timeout(12)
                ->get(rtrim(config('services.dprice.url'), '/').'/products', ['query' => $query]);

            if (!$response->successful()) {
                Log::warning('DPrice lookup failed', ['status' => $response->status()]);
                return null;
            }

            $payload = $response->json();
            if (!is_array($payload)) {
                return null;
            }

            $products = data_get($payload, 'data.products')
                ?? data_get($payload, 'products')
                ?? data_get($payload, 'data.results')
                ?? data_get($payload, 'results')
                ?? data_get($payload, 'data');
            $candidates = is_array($products) && array_is_list($products) ? $products : [$products];
            $product = null;
            $marketPrice = null;
            foreach ($candidates as $candidate) {
                if (!is_array($candidate)) {
                    continue;
                }
                $candidatePrice = data_get($candidate, 'lowest_price')
                    ?? data_get($candidate, 'min_price')
                    ?? data_get($candidate, 'price');
                if (is_array($candidatePrice)) {
                    $candidatePrice = $candidatePrice['amount'] ?? $candidatePrice['value'] ?? null;
                }
                if (is_numeric($candidatePrice) && (float) $candidatePrice > 0) {
                    $product = $candidate;
                    $marketPrice = (float) $candidatePrice;
                    break;
                }
            }
            if (!$marketPrice && isset($candidates[0]['id'])) {
                $product = $candidates[0];
                $offersResponse = Http::acceptJson()
                    ->withHeaders(['X-API-Key' => $key])
                    ->timeout(12)
                    ->get(rtrim(config('services.dprice.url'), '/').'/products/'.$product['id'].'/offers');

                if ($offersResponse->successful()) {
                    $offers = data_get($offersResponse->json(), 'data', []);
                    $prices = collect(is_array($offers) ? $offers : [])
                        ->map(fn ($offer) => is_array($offer) ? ($offer['price'] ?? $offer['current_price'] ?? null) : null)
                        ->filter(fn ($price) => is_numeric($price) && (float) $price > 0)
                        ->map(fn ($price) => (float) $price);
                    if ($prices->isNotEmpty()) {
                        $marketPrice = $prices->min();
                    }
                }
            }
            if (!$product || !$marketPrice) {
                return null;
            }

            $discountPercent = match ($condition) {
                'like_new' => 10,
                'minor_defect' => 30,
                'heavily_used' => 50,
                default => 30,
            };

            return [
                'market_price' => $marketPrice,
                'sale_price' => (int) round($marketPrice * (100 - $discountPercent) / 100),
                'discount_percent' => $discountPercent,
                'matched_title' => (string) (data_get($product, 'name') ?? data_get($product, 'title') ?? $query),
            ];
        } catch (\Throwable $exception) {
            Log::warning('DPrice lookup unavailable', ['error' => $exception->getMessage()]);
            return null;
        }
    }
}
