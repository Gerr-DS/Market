<?php
namespace App\Http\Controllers\Api\V1;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\GeminiProductAnalysisService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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
    public function analyze(Request $request, GeminiProductAnalysisService $gemini)
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
            'expired_at' => 'nullable|date',
            'images' => 'required|array|min:1|max:6',
            'images.*' => 'image|max:10240',
            'expiry_label' => 'required_if:type,food|nullable|image|max:10240',
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
            'expired_at' => null,
            'seller_price' => $data['seller_price'] ?? null,
            'stock' => $data['stock'],
        ]);

        foreach ($data['images'] as $image) {
            $product->images()->create([
                'image_path' => $image->store('products', 'public'),
                'image_type' => 'full_product',
            ]);
        }
        if (isset($data['expiry_label'])) {
            $product->images()->create([
                'image_path' => $data['expiry_label']->store('products', 'public'),
                'image_type' => 'expiry_label',
            ]);
        }

        try {
            // AI is used only to read food expiry labels. Goods need no AI analysis.
            if ($product->type === 'goods') {
                $result = ['detected_date' => null, 'confidence_score' => 0, 'raw_ocr_text' => '', 'model' => 'manual-price'];
            } else {
                $result = $gemini->analyze(
                    $data,
                    $data['images'],
                    $data['expiry_label'] ?? null,
                    (string) $product->category()->value('name'),
                );
            }
        } catch (\Throwable $exception) {
            Log::error('Gemini product analysis failed', [
                'product_id' => $product->id,
                'error' => $exception->getMessage(),
            ]);
            foreach ($product->images()->pluck('image_path') as $imagePath) {
                Storage::disk('public')->delete($imagePath);
            }
            $product->forceDelete();
            $errorMessage = $exception->getMessage();
            $message = str_contains($errorMessage, 'GEMINI_API_KEY')
                ? 'Gemini belum dikonfigurasi. Isi GEMINI_API_KEY pada file .env.'
                : (str_contains($errorMessage, 'HTTP 429') && (str_contains(mb_strtolower($errorMessage), 'quota') || str_contains(mb_strtolower($errorMessage), 'free_tier_requests'))
                    ? 'Kuota Gemini gratis hari ini sudah habis. Coba lagi setelah waktu reset kuota di Google AI Studio, atau gunakan project Gemini dengan kuota tersedia.'
                    : (str_contains($errorMessage, 'HTTP 429')
                        ? 'Gemini membatasi permintaan sementara. Tunggu sebentar sebelum mencoba lagi.'
                        : (str_contains($errorMessage, 'HTTP 503')
                            ? 'Layanan Gemini sedang sibuk. Silakan coba analisis lagi beberapa saat.'
                            : 'Analisis Gemini gagal. Periksa koneksi dan storage/logs/laravel.log.')));

            return response()->json(['message' => $message, 'product_id' => $product->id], 503);
        }

        $score = $result['confidence_score'];
        $expired = $product->type === 'food' ? $result['detected_date'] : null;
        $expiryTooSoon = $expired !== null
            && Carbon::parse($expired)->startOfDay()->lessThanOrEqualTo(today()->addDays(30));
        $reason = null;
        if ($product->type === 'food' && $expired === null) {
            $reason = 'expiry_date_not_found';
        } elseif ($expiryTooSoon) {
            $reason = 'expiry_within_30_days';
        } elseif ($product->type === 'food' && $score < 80) {
            $reason = 'ai_confidence_too_low';
        }

        $price = (float) $product->seller_price;

        $rejected = $reason !== null;
        if ($rejected) {
            $this->deleteProductFiles($product);
            $product->forceDelete();

            $message = match ($reason) {
                'expiry_date_not_found' => 'Tanggal kedaluwarsa tidak terbaca. Produk tidak disimpan; unggah kembali dengan foto label yang lebih jelas.',
                'expiry_within_30_days' => 'Masa kedaluwarsa kurang dari 30 hari. Produk tidak disimpan.',
                'ai_confidence_too_low' => 'Tanggal pada label belum terbaca dengan yakin. Produk tidak disimpan; unggah kembali dengan foto yang lebih jelas.',
                default => 'Produk tidak memenuhi syarat dan tidak disimpan.',
            };

            return response()->json(['message' => $message, 'rejection_reason' => $reason], 422);
        }

        $product->update([
            'ai_confidence' => $score,
            'market_price' => null,
            'system_price' => $price,
            'expired_at' => $expired,
            'status' => $rejected ? 'rejected' : 'pending_approval',
        ]);
        $product->analysisLogs()->create([
            'raw_ocr_text' => ($result['raw_ocr_text'] ?? '') ?: ($result['recognized_name'] ?? null),
            'detected_date' => $expired,
            'confidence_score' => $score,
            'raw_ai_response' => $result,
        ]);

        return response()->json([
            'product' => $product->fresh('images'),
            'rejection_reason' => $reason,
            'analysis' => [
                'raw_ocr_text' => $result['raw_ocr_text'],
                'detected_date' => $expired,
                'confidence_score' => $score,
                'model' => $result['model'],
                'recognized_name' => null,
                'product_kind' => null,
                'condition_assessment' => null,
                'product_matches' => null,
                'price_reasoning' => null,
                'gemini_price' => null,
                'market_price' => $product->market_price,
                'seller_price' => $product->seller_price,
                'dprice_price' => null,
                'price_source' => 'manual',
                'discount_percent' => null,
                'matched_product' => null,
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
