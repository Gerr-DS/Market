<?php
namespace App\Http\Controllers\Api\V1;
use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;
class ProductController extends Controller
{
    public function index(Request $request){return Product::with(['images','category','user:id,name'])->where('status','active')->where('stock','>',0)->when($request->filled('category_id'),fn($q)=>$q->where('category_id',$request->integer('category_id')))->when($request->filled('type'),fn($q)=>$q->where('type',$request->input('type')))->when($request->filled('q'),fn($q)=>$q->whereRaw('LOWER(title) LIKE ?', ['%'.mb_strtolower($request->input('q')).'%']))->latest()->paginate(20);}
    public function show(Request $request, Product $product){$isOwner=(int)$request->user()?->id===(int)$product->user_id;abort_unless($product->status==='active'&&($product->stock>0||$isOwner),404);return $product->load(['images','category','user:id,name']);}
    public function mine(Request $request)
    {
        $rejected = $request->user()->products()->where('status', 'rejected')->with('images')->get();
        foreach ($rejected as $product) {
            $this->deleteProductFiles($product);
            $product->forceDelete();
        }

        return $request->user()->products()->where('status', 'active')->with(['images', 'category'])->latest()->paginate(20);
    }
    public function destroy(Request $request,Product $product){abort_unless($product->user_id===$request->user()->id,403);abort_if($product->orders()->whereIn('order_status',['pending','processing','shipped','delivered','disputed'])->exists(),409,'Produk tidak dapat dihapus karena masih memiliki pesanan aktif.');$this->deleteProductFiles($product);$product->forceDelete();return response()->noContent();}

    public function update(Request $request, Product $product)
    {
        abort_unless($product->user_id === $request->user()->id, 403);
        abort_unless($product->status === 'active', 409, 'Hanya postingan yang sedang tayang yang dapat diedit.');
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'seller_price' => 'required|numeric|min:1|max:9999999999',
            'item_condition' => 'nullable|in:like_new,minor_defect,heavily_used',
            'pickup_address' => 'required|string|max:1000',
            'weight_gram' => 'required|integer|min:1|max:50000',
            'stock' => 'required|integer|min:0|max:1000000',
        ]);

        $product->update([
            ...$data,
            'system_price' => $data['seller_price'],
            'market_price' => null,
        ]);

        return $product->fresh(['images', 'category', 'user:id,name']);
    }
    public function analyze(Request $request)
    {
        $data = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'type' => 'required|in:goods,food',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'pickup_address' => 'required|string|max:1000',
            'pickup_latitude' => 'nullable|numeric|between:-90,90',
            'pickup_longitude' => 'nullable|numeric|between:-180,180',
            'pickup_area_id' => 'required|string|max:40',
            'pickup_area_label' => 'required|string|max:255',
            'weight_gram' => 'required|integer|min:1|max:50000',
            'item_condition' => 'nullable|in:like_new,minor_defect,heavily_used',
            'seller_price' => 'required|numeric|min:1|max:9999999999',
            'stock' => 'required|integer|min:1|max:1000000',
            'expired_at' => 'required_if:type,food|nullable|date_format:Y-m-d',
            'images' => 'required|array|min:1|max:6',
            'images.*' => 'image|max:10240',
        ]);

        $product = Product::create([
            'user_id' => $request->user()->id,
            'category_id' => $data['category_id'],
            'type' => $data['type'],
            'title' => $data['title'],
            'description' => $data['description'],
            'pickup_address' => $data['pickup_address'],
            'pickup_latitude' => $data['pickup_latitude'] ?? null,
            'pickup_longitude' => $data['pickup_longitude'] ?? null,
            'pickup_area_id' => $data['pickup_area_id'],
            'pickup_area_label' => $data['pickup_area_label'],
            'weight_gram' => $data['weight_gram'],
            'item_condition' => $data['item_condition'] ?? null,
            'expired_at' => $data['type'] === 'food' ? $data['expired_at'] : null,
            'seller_price' => $data['seller_price'] ?? null,
            'stock' => $data['stock'],
        ]);

        foreach ($data['images'] as $image) {
            $product->images()->create([
                'image_path' => $image->store('products', 'public'),
                'image_type' => 'full_product',
            ]);
        }
        $expired = $product->type === 'food' ? $data['expired_at'] : null;
        $expiryTooSoon = $expired !== null
            && Carbon::parse($expired)->startOfDay()->lessThanOrEqualTo(today()->addDays(30));
        $reason = $expiryTooSoon ? 'expiry_within_30_days' : null;

        $price = (float) $product->seller_price;

        $rejected = $reason !== null;
        if ($rejected) {
            $this->deleteProductFiles($product);
            $product->forceDelete();

            $message = match ($reason) {
                'expiry_within_30_days' => 'Masa kedaluwarsa harus lebih dari 30 hari. Produk tidak disimpan.',
                default => 'Produk tidak memenuhi syarat dan tidak disimpan.',
            };

            return response()->json(['message' => $message, 'rejection_reason' => $reason], 422);
        }

        $product->update([
            'market_price' => null,
            'system_price' => $price,
            'expired_at' => $expired,
            'status' => $rejected ? 'rejected' : 'pending_approval',
        ]);
        return response()->json([
            'product' => $product->fresh('images'),
            'rejection_reason' => $reason,
            'analysis' => [
                'detected_date' => $expired,
                'seller_price' => $product->seller_price,
                'price_source' => 'manual',
            ],
        ], 202);
    }
    public function approve(Request $request, Product $product){abort_unless($product->user_id===$request->user()->id,403);abort_unless($product->status==='pending_approval',409);$request->validate(['approved'=>'required|boolean']);if(!$request->boolean('approved')){$this->deleteProductFiles($product);$product->forceDelete();return response()->json(['deleted'=>true]);}$product->update(['status'=>'active']);return $product->fresh();}

    private function deleteProductFiles(Product $product): void
    {
        foreach ($product->images()->pluck('image_path') as $path) {
            Storage::disk('public')->delete($path);
        }
    }
}
