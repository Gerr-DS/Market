<?php
namespace App\Http\Controllers\Api\V1;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\DPriceService;
use App\Services\GeminiProductAnalysisService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;
class ProductController extends Controller
{
    public function index(Request $request){return Product::with(['images','category','user:id,name'])->where('status','active')->when($request->filled('category_id'),fn($q)=>$q->where('category_id',$request->integer('category_id')))->when($request->filled('type'),fn($q)=>$q->where('type',$request->input('type')))->when($request->filled('q'),fn($q)=>$q->whereRaw('LOWER(title) LIKE ?', ['%'.mb_strtolower($request->input('q')).'%']))->latest()->paginate(20);}
    public function mine(Request $request){return $request->user()->products()->with(['images','category'])->latest()->paginate(20);}
    public function destroy(Request $request,Product $product){abort_unless($product->user_id===$request->user()->id,403);abort_if($product->orders()->whereIn('order_status',['pending','processing','shipped','delivered','disputed'])->exists(),409,'Produk tidak dapat dihapus karena masih memiliki pesanan aktif.');$product->delete();return response()->noContent();}
    public function analyze(Request $request, DPriceService $dprice, GeminiProductAnalysisService $gemini)
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
            'seller_price' => 'required_if:type,goods|nullable|numeric|min:1|max:9999999999',
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
            $result = $gemini->analyze(
                $data,
                $data['images'],
                $data['expiry_label'] ?? null,
                (string) $product->category()->value('name'),
            );
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
        } elseif (!$result['product_matches']) {
            $reason = 'product_mismatch';
        } elseif ($score < 80) {
            $reason = 'ai_confidence_too_low';
        } elseif (($product->type === 'goods' && $result['estimated_market_price'] === null)
            || ($product->type === 'food' && $result['recommended_price'] === null)) {
            $reason = 'price_estimation_not_configured';
        }

        $dpricePrice = null;
        $matchedProduct = null;
        $discountPercent = null;
        $priceSource = 'Gemini';
        $marketPrice = $result['estimated_market_price'];
        $price = $result['recommended_price'];

        if ($product->type === 'goods' && $reason === null) {
            $candidates = [];
            if ($result['estimated_market_price']) {
                $candidates['Gemini'] = $result['estimated_market_price'];
            }
            if (is_numeric($product->seller_price) && $product->seller_price > 0) {
                $candidates['Harga penjual'] = (float) $product->seller_price;
            }
            if (filter_var(config('services.dprice.enabled'), FILTER_VALIDATE_BOOLEAN)) {
                $recommendation = $dprice->recommendation(
                    $result['recognized_name'] ?: $product->title,
                    $product->item_condition,
                );
                if ($recommendation) {
                    $dpricePrice = $recommendation['market_price'];
                    $matchedProduct = $recommendation['matched_title'];
                    $candidates['DPrice'] = $dpricePrice;
                }
            }

            if ($candidates !== []) {
                $priceSource = array_search(min($candidates), $candidates, true) ?: 'Gemini';
                $marketPrice = min($candidates);
                $discountPercent = match ($product->item_condition) {
                    'like_new' => 10,
                    'minor_defect' => 30,
                    'heavily_used' => 50,
                    default => 30,
                };
                $price = (int) round($marketPrice * (100 - $discountPercent) / 100);
            }
        }

        if ($reason === null && (!is_numeric($price) || $price <= 0)) {
            $reason = 'price_estimation_not_configured';
        }

        $rejected = $reason !== null;
        $product->update([
            'ai_confidence' => $score,
            'market_price' => $marketPrice,
            'system_price' => is_numeric($price) ? $price : null,
            'expired_at' => $expired,
            'status' => $rejected ? 'rejected' : 'pending_approval',
        ]);
        $product->analysisLogs()->create([
            'raw_ocr_text' => $result['raw_ocr_text'] ?: $result['recognized_name'],
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
                'recognized_name' => $result['recognized_name'],
                'product_kind' => $result['product_kind'],
                'condition_assessment' => $result['condition_assessment'],
                'product_matches' => $result['product_matches'],
                'price_reasoning' => $result['price_reasoning'],
                'gemini_price' => $result['estimated_market_price'],
                'market_price' => $product->market_price,
                'seller_price' => $product->seller_price,
                'dprice_price' => $dpricePrice,
                'price_source' => $priceSource,
                'discount_percent' => $discountPercent,
                'matched_product' => $matchedProduct,
            ],
        ], $rejected ? 422 : 202);
    }
    public function approve(Request $request, Product $product){abort_unless($product->user_id===$request->user()->id,403);abort_unless($product->status==='pending_approval',409);$request->validate(['approved'=>'required|boolean']);$product->update(['status'=>$request->boolean('approved')?'active':'rejected']);return $product->fresh();}
}
