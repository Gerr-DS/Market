<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Models\Withdrawal;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class WalletController extends Controller
{
    public function transactions(Request $request)
    {
        return $request->user()->walletTransactions()->latest()->paginate(20);
    }

    public function createDemoTopup(Request $request)
    {
        abort_unless(app()->environment(['local', 'testing']), 404);
        $data = $request->validate(['amount' => 'required|integer|min:1000|max:5000000']);
        $token = Str::random(48);
        $expiresAt = now()->addMinutes(15);
        $topupId = DB::table('demo_wallet_topups')->insertGetId([
            'user_id' => $request->user()->id,
            'amount' => $data['amount'],
            'token_hash' => hash('sha256', $token),
            'status' => 'pending',
            'expires_at' => $expiresAt,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'id' => $topupId,
            'amount' => (int) $data['amount'],
            'status' => 'pending',
            'expires_at' => $expiresAt->toIso8601String(),
            'scan_path' => '/api/v1/wallet/demo-topups/scan/'.$token,
            'mode' => 'demo',
        ], 201);
    }

    public function demoTopupStatus(Request $request, int $topup)
    {
        abort_unless(app()->environment(['local', 'testing']), 404);
        $record = DB::table('demo_wallet_topups')->where('id', $topup)
            ->where('user_id', $request->user()->id)->first();
        abort_unless($record, 404);

        return response()->json([
            'id' => $record->id,
            'amount' => (int) $record->amount,
            'status' => $record->status === 'pending' && Carbon::parse($record->expires_at)->isPast()
                ? 'expired'
                : $record->status,
            'paid_at' => $record->paid_at,
        ]);
    }

    public function scanDemoTopup(string $token)
    {
        abort_unless(app()->environment(['local', 'testing']), 404);
        $result = DB::transaction(function () use ($token) {
            $topup = DB::table('demo_wallet_topups')
                ->where('token_hash', hash('sha256', $token))
                ->lockForUpdate()
                ->first();
            abort_unless($topup, 404, 'QR demo tidak valid.');

            if ($topup->status === 'paid') {
                return ['already_paid' => true, 'amount' => (int) $topup->amount];
            }

            if ($topup->status !== 'pending' || Carbon::parse($topup->expires_at)->isPast()) {
                abort(410, 'QR demo sudah kedaluwarsa. Buat QR baru.');
            }

            $user = User::whereKey($topup->user_id)->lockForUpdate()->firstOrFail();
            $user->increment('wallet_balance', $topup->amount);
            WalletTransaction::create([
                'user_id' => $user->id,
                'type' => 'credit',
                'amount' => $topup->amount,
                'description' => 'Isi saldo demo melalui QR',
            ]);
            DB::table('demo_wallet_topups')->where('id', $topup->id)->update([
                'status' => 'paid',
                'paid_at' => now(),
                'updated_at' => now(),
            ]);

            return ['already_paid' => false, 'amount' => (int) $topup->amount];
        });

        return response()->view('demo-payment-result', [
            'amount' => $result['amount'],
            'alreadyPaid' => $result['already_paid'],
        ]);
    }

    public function withdraw(Request $request)
    {
        $data = $request->validate([
            'amount' => 'required|numeric|min:10000',
            'bank_name' => 'required|string|max:50',
            'account_number' => 'required|string|max:50',
            'account_holder_name' => 'required|string|max:255',
        ]);

        $withdrawal = DB::transaction(function () use ($request, $data) {
            $user = User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            if ((float) $user->wallet_balance < (float) $data['amount']) {
                throw ValidationException::withMessages(['amount' => 'Saldo dompet tidak mencukupi.']);
            }
            $user->decrement('wallet_balance', $data['amount']);
            WalletTransaction::create([
                'user_id' => $user->id,
                'type' => 'debit',
                'amount' => $data['amount'],
                'description' => 'Penarikan saldo ke '.$data['bank_name'],
            ]);

            return Withdrawal::create(['user_id' => $user->id, ...$data]);
        });

        return response()->json($withdrawal, 201);
    }
}
