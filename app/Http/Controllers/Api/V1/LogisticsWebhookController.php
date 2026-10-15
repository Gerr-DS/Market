<?php
namespace App\Http\Controllers\Api\V1;
use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
class LogisticsWebhookController extends Controller
{
    public function __invoke(Request $request){$raw=$request->getContent();$secret=(string)config('services.logistics.webhook_secret');$signature=$request->header('X-Webhook-Signature','');abort_unless($secret!==''&&hash_equals(hash_hmac('sha512',$raw,$secret),$signature),403,'Invalid webhook signature.');$data=$request->validate(['tracking_number'=>'required_without:order_number|nullable|string','order_number'=>'required_without:tracking_number|nullable|string','status'=>'required|string']);$order=Order::when(!empty($data['tracking_number']),fn($query)=>$query->where('tracking_number',$data['tracking_number']),fn($query)=>$query->where('order_number',$data['order_number']))->firstOrFail();if(strtolower($data['status'])==='delivered'&&$order->order_status==='shipped')$order->update(['order_status'=>'delivered','delivered_at'=>$order->delivered_at??now()]);return response()->json(['received'=>true]);}
}
