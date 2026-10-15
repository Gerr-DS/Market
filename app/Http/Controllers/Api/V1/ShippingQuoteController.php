<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ShippingQuoteController extends Controller
{
    private function apiKey(): string
    {
        return (string) config('services.logistics.api_key');
    }

    public function locations(Request $request)
    {
        $data = $request->validate(['search' => 'required|string|min:3|max:120']);
        if ($this->apiKey() === '') {
            return response()->json(['message' => 'API gratis RajaOngkir belum dikonfigurasi. Tambahkan RAJAONGKIR_API_KEY pada .env.'], 503);
        }

        $search = trim($data['search']);
        $locations = Cache::remember('rajaongkir.destination.'.mb_strtolower($search), now()->addDay(), function () use ($search) {
            try {
                $response = Http::acceptJson()
                    ->withHeaders(['key' => $this->apiKey()])
                    ->timeout(15)
                    ->get(rtrim((string) config('services.logistics.api_url'), '/').'/destination/domestic-destination', [
                        'search' => $search,
                        'limit' => 10,
                        'offset' => 0,
                    ]);
            } catch (\Throwable $exception) {
                Log::warning('RajaOngkir location search failed', ['message' => $exception->getMessage()]);
                abort(503, 'Layanan pencarian lokasi tidak dapat dijangkau.');
            }

            if (! $response->successful() || data_get($response->json(), 'meta.status') === 'error') {
                Log::warning('RajaOngkir location search returned an error', [
                    'status' => $response->status(),
                    'message' => mb_substr((string) data_get($response->json(), 'meta.message', ''), 0, 300),
                ]);
                abort(502, 'Pencarian lokasi dari RajaOngkir gagal.');
            }

            return collect(data_get($response->json(), 'data', []))
                ->filter(fn ($location) => isset($location['id'], $location['label']))
                ->map(fn ($location) => [
                    'id' => (string) $location['id'],
                    'label' => (string) $location['label'],
                    'city' => $location['city_name'] ?? null,
                    'district' => $location['district_name'] ?? null,
                    'subdistrict' => $location['subdistrict_name'] ?? null,
                    'postal_code' => $location['zip_code'] ?? null,
                ])->values()->all();
        });

        return response()->json(['locations' => $locations]);
    }

    public function __invoke(Request $request)
    {
        $data = $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'address.recipient_name' => 'required|string|max:255',
            'address.phone' => 'required|string|max:30',
            'address.full_address' => 'required|string|max:1500',
            'address.area_id' => 'required|string|max:40',
            'address.area_label' => 'required|string|max:255',
        ]);

        $product = Product::whereKey($data['product_id'])->where('status', 'active')->firstOrFail();
        abort_if($product->user_id === $request->user()->id, 422, 'Tidak dapat memesan produk sendiri.');
        abort_unless($product->pickup_area_id && $product->pickup_area_label, 422, 'Penjual belum memilih lokasi asal untuk tarif kurir.');
        abort_if($this->apiKey() === '', 503, 'Tambahkan RAJAONGKIR_API_KEY pada file .env.');

        try {
            $response = Http::asForm()
                ->acceptJson()
                ->withHeaders(['key' => $this->apiKey()])
                ->timeout(20)
                ->post(rtrim((string) config('services.logistics.api_url'), '/').'/calculate/domestic-cost', [
                    'origin' => $product->pickup_area_id,
                    'destination' => $data['address']['area_id'],
                    'weight' => (int) $product->weight_gram,
                    'courier' => (string) config('services.logistics.couriers'),
                    'price' => 'lowest',
                ]);
        } catch (\Throwable $exception) {
            Log::warning('RajaOngkir rate request failed', ['message' => $exception->getMessage()]);
            return response()->json(['message' => 'Layanan cek ongkir tidak dapat dijangkau. Coba lagi nanti.'], 503);
        }

        if (! $response->successful() || data_get($response->json(), 'meta.status') === 'error') {
            Log::warning('RajaOngkir rate API returned an error', [
                'status' => $response->status(),
                'message' => mb_substr((string) data_get($response->json(), 'meta.message', $response->body()), 0, 300),
            ]);
            return response()->json(['message' => 'Ongkir gagal dihitung. Periksa area asal, tujuan, berat, atau kunci API.'], 502);
        }

        $rates = collect(data_get($response->json(), 'data', []))
            ->map(function (array $rate) {
                $cost = $rate['cost'] ?? null;
                if (is_array($cost)) {
                    $cost = data_get($cost, '0.value', data_get($cost, '0.cost'));
                }
                return [...$rate, 'normalized_cost' => is_numeric($cost) ? (int) $cost : null];
            })
            ->filter(fn ($rate) => $rate['normalized_cost'] !== null && $rate['normalized_cost'] > 0)
            ->values();

        if ($rates->isEmpty()) {
            return response()->json(['message' => 'Tidak ada layanan kurir untuk area dan berat paket ini.'], 422);
        }

        $expiresAt = now()->addMinutes(15);
        $quotes = DB::transaction(function () use ($rates, $request, $product, $data, $expiresAt) {
            return $rates->map(function (array $rate) use ($request, $product, $data, $expiresAt) {
                $courier = (string) ($rate['name'] ?? $rate['code'] ?? 'Kurir');
                $service = (string) ($rate['service'] ?? 'Reguler');
                $price = $rate['normalized_cost'];
                $quoteId = DB::table('shipping_quotes')->insertGetId([
                    'user_id' => $request->user()->id,
                    'product_id' => $product->id,
                    'courier_code' => (string) ($rate['code'] ?? 'courier'),
                    'courier_name' => $courier,
                    'service_code' => $service,
                    'service_name' => $service,
                    'price' => $price,
                    'duration' => $rate['etd'] ?? null,
                    'destination' => json_encode($data['address'], JSON_UNESCAPED_UNICODE),
                    'expires_at' => $expiresAt,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return [
                    'id' => $quoteId,
                    'courier_code' => $rate['code'] ?? null,
                    'courier_name' => $courier,
                    'service_name' => $service,
                    'description' => $rate['description'] ?? null,
                    'price' => $price,
                    'duration' => $rate['etd'] ?? null,
                ];
            });
        });

        return response()->json(['quotes' => $quotes, 'expires_at' => $expiresAt->toIso8601String()]);
    }
}
