<?php
namespace App\Http\Controllers\Api\V1;
use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
class ChatController extends Controller
{
    public function send(Request $request,Product $product){$data=$request->validate(['message'=>'required|string|max:5000']);abort_unless($product->status==='active',404);abort_if($product->user_id===$request->user()->id,422);return response()->json(Chat::create(['product_id'=>$product->id,'sender_id'=>$request->user()->id,'receiver_id'=>$product->user_id,'message'=>$data['message']]),201);}
    public function sendForOrder(Request $request,Order $order){$data=$request->validate(['message'=>'required|string|max:5000']);$sellerId=$order->product?->user_id;abort_unless($sellerId===$request->user()->id||$order->buyer_id===$request->user()->id,403);abort_unless(in_array($order->order_status,['pending','processing','shipped','delivered'],true),409,'Chat ditutup karena pesanan sudah selesai atau dibatalkan.');$receiverId=$request->user()->id===$sellerId?$order->buyer_id:$sellerId;return response()->json(Chat::create(['product_id'=>$order->product_id,'sender_id'=>$request->user()->id,'receiver_id'=>$receiverId,'message'=>$data['message']]),201);}
    public function conversations(Request $request)
    {
        $user = $request->user();
        $latestChats = Chat::with(['product:id,user_id,title', 'product.user:id,name', 'sender:id,name', 'receiver:id,name'])
            ->where(fn ($query) => $query->where('sender_id', $user->id)->orWhere('receiver_id', $user->id))
            ->latest()
            ->get();

        $conversations = $latestChats->map(function (Chat $chat) use ($user) {
            $product = $chat->product;
            if (! $product) {
                return null;
            }

            $otherId = (int) $chat->sender_id === (int) $user->id ? $chat->receiver_id : $chat->sender_id;
            $otherName = (int) $chat->sender_id === (int) $user->id
                ? ($chat->receiver?->name ?? 'Pengguna')
                : ($chat->sender?->name ?? 'Pengguna');
            $key = $product->id.':'.$otherId;
            $buyerId = (int) $product->user_id === (int) $user->id ? $otherId : $user->id;
            $order = Order::where('product_id', $product->id)
                ->where('buyer_id', $buyerId)
                ->whereNotIn('order_status', ['completed', 'cancelled'])
                ->latest()
                ->first();

            return [
                'key' => $key,
                'order_id' => $order?->id,
                'buyer_id' => $order?->buyer_id ?? $buyerId,
                'product_id' => $product->id,
                'product_title' => $product->title,
                'other_user_id' => $otherId,
                'other_user_name' => $otherName,
                'side' => (int) $product->user_id === (int) $user->id ? 'seller' : 'buyer',
                'order_status' => $order?->order_status ?? 'Chat produk',
                'last_message' => $chat->message,
                'updated_at' => $chat->created_at,
            ];
        })->filter()->unique('key')->values();

        return response()->json($conversations);
    }
    public function inbox(Request $request){$query=Chat::with(['product:id,title','sender:id,name','receiver:id,name'])->where(fn($q)=>$q->where('sender_id',$request->user()->id)->orWhere('receiver_id',$request->user()->id));if($request->filled('product_id')&&$request->filled('other_user_id')){$otherId=$request->integer('other_user_id');$query->where('product_id',$request->integer('product_id'))->where(fn($q)=>$q->where(fn($pair)=>$pair->where('sender_id',$request->user()->id)->where('receiver_id',$otherId))->orWhere(fn($pair)=>$pair->where('sender_id',$otherId)->where('receiver_id',$request->user()->id)));}return $query->latest()->paginate($request->filled('product_id')?100:30);}
}
