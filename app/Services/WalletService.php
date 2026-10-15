<?php
namespace App\Services;

use App\Models\Order;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WalletService
{
    public function release(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $locked = Order::with(['product.user','reports'])->lockForUpdate()->findOrFail($order->id);
            if ($locked->payment_status !== 'paid_escrow' || $locked->order_status !== 'delivered') return;
            if ($locked->reports()->whereIn('status', ['open','investigating'])->exists()) return;
            $seller = $locked->product?->user;
            if (! $seller) throw ValidationException::withMessages(['order'=>'Penjual tidak ditemukan.']);
            $locked->update(['payment_status'=>'released','order_status'=>'completed']);
            $seller->increment('wallet_balance', $locked->total_amount);
            WalletTransaction::create(['user_id'=>$seller->id,'order_id'=>$locked->id,'type'=>'credit','amount'=>$locked->total_amount,'description'=>"Pencairan dana transaksi {$locked->order_number}"]);
        });
    }
}
