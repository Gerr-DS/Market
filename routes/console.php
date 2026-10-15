<?php

use App\Models\Order;
use App\Models\Product;
use App\Services\WalletService;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Storage;

Schedule::call(function () {
    Product::with('images')
        ->where('type', 'food')
        ->whereIn('status', ['active', 'pending_approval'])
        ->whereDate('expired_at', '<=', today()->addDays(7))
        ->each(function (Product $product) {
            Storage::disk('public')->delete($product->images->pluck('image_path')->all());
            $product->forceDelete();
        });
})->daily();

Schedule::call(function () {
    Order::where('order_status', 'delivered')
        ->where('payment_status', 'paid_escrow')
        ->whereNotNull('delivered_at')
        ->where('delivered_at', '<=', now()->subHours(72))
        ->whereDoesntHave('reports', fn ($query) => $query->whereIn('status', ['open', 'investigating']))
        ->each(fn (Order $order) => app(WalletService::class)->release($order));
})->hourly();
