<?php
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\AdminController;
use App\Http\Controllers\Api\V1\ChatController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\PaymentWebhookController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\ShippingQuoteController;
use App\Http\Controllers\Api\V1\WalletController;
use App\Http\Controllers\Api\V1\LogisticsWebhookController;
use App\Models\Category;
use Illuminate\Support\Facades\Route;
Route::prefix('v1')->group(function () {
    Route::post('/auth/register',[AuthController::class,'register'])->middleware('throttle:10,1');
    Route::post('/auth/login',[AuthController::class,'login'])->middleware('throttle:10,1');
    Route::get('/categories',function(){
        foreach(['Makanan','Elektronik','Fashion','Rumah Tangga','Lainnya'] as $name){
            Category::firstOrCreate(['slug'=>str($name)->slug()->toString()],['name'=>$name]);
        }
        return Category::query()->orderBy('name')->get(['id','name','slug']);
    });
    Route::get('/products',[ProductController::class,'index']);
    Route::post('/payment/webhook',PaymentWebhookController::class);
    Route::post('/logistics/webhook',LogisticsWebhookController::class);
    Route::get('/wallet/demo-topups/scan/{token}',[WalletController::class,'scanDemoTopup'])
        ->where('token', '[A-Za-z0-9]+')->middleware('throttle:20,1');
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/user/profile',[AuthController::class,'profile']);
        Route::get('/user/products',[ProductController::class,'mine']);
        Route::delete('/products/{product}',[ProductController::class,'destroy']);
        Route::get('/user/wallet/transactions',[WalletController::class,'transactions']);
        Route::post('/user/wallet/demo-topups',[WalletController::class,'createDemoTopup'])->middleware('throttle:10,1');
        Route::get('/user/wallet/demo-topups/{topup}',[WalletController::class,'demoTopupStatus']);
        Route::post('/user/wallet/withdrawals',[WalletController::class,'withdraw']);
        Route::get('/chats',[ChatController::class,'inbox']);
        Route::get('/user/conversations',[ChatController::class,'conversations']);
        Route::post('/products/{product}/chats',[ChatController::class,'send']);
        Route::post('/products/analyze',[ProductController::class,'analyze'])->middleware('throttle:10,1');
        Route::patch('/products/{product}/approval',[ProductController::class,'approve']);
        Route::post('/orders/checkout',[OrderController::class,'checkout']);
        Route::post('/shipping/quotes',[ShippingQuoteController::class,'__invoke'])->middleware('throttle:20,1');
        Route::get('/shipping/locations',[ShippingQuoteController::class,'locations'])->middleware('throttle:30,1');
        Route::get('/user/orders',[OrderController::class,'mine']);
        Route::get('/user/sales',[OrderController::class,'incoming']);
        Route::post('/orders/{order}/ship',[OrderController::class,'ship']);
        Route::post('/orders/{order}/handoff',[OrderController::class,'directHandoff']);
        Route::post('/orders/{order}/chats',[ChatController::class,'sendForOrder']);
        Route::post('/orders/{order}/confirm',[OrderController::class,'confirm']);
        Route::post('/orders/{order}/cancel',[OrderController::class,'cancel']);
        Route::post('/orders/{order}/reports',[OrderController::class,'report']);
        Route::prefix('admin')->group(function(){
            Route::get('/reports',[AdminController::class,'reports']);
            Route::post('/reports/{report}/resolve',[AdminController::class,'resolveReport']);
            Route::get('/withdrawals',[AdminController::class,'withdrawals']);
            Route::post('/withdrawals/{withdrawal}/decision',[AdminController::class,'decideWithdrawal']);
        });
    });
});
