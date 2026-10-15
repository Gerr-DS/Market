<?php
namespace App\Http\Controllers\Api\V1;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class PaymentWebhookController extends Controller
{
    public function __invoke(Request $request){$raw=$request->getContent();$signature=$request->header('X-Webhook-Signature','');$secret=(string)config('services.payment.webhook_secret');abort_unless($secret!==''&&hash_equals(hash_hmac('sha512',$raw,$secret),$signature),403,'Invalid webhook signature.');$data=$request->validate(['order_number'=>'required|string','transaction_id'=>'nullable|string','payment_type'=>'nullable|string','status'=>'required|in:paid,settlement,success']);DB::transaction(function()use($data,$request){$order=Order::where('order_number',$data['order_number'])->lockForUpdate()->firstOrFail();if($order->payment_status==='paid_escrow')return;$order->update(['payment_status'=>'paid_escrow','order_status'=>'processing']);$order->product()->update(['status'=>'sold']);Payment::create(['order_id'=>$order->id,'payment_gateway_trx_id'=>$data['transaction_id']??null,'payment_type'=>$data['payment_type']??null,'raw_response'=>$request->all()]);});return response()->json(['received'=>true]);}
}
