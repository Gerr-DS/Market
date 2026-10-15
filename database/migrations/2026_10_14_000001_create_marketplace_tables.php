<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_addresses', function (Blueprint $table) {
            $table->id(); $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('recipient_name'); $table->string('phone', 20); $table->text('full_address');
            $table->unsignedInteger('city_id'); $table->unsignedInteger('district_id');
            $table->string('postal_code', 10); $table->boolean('is_primary')->default(false); $table->timestamps();
        });
        Schema::create('categories', function (Blueprint $table) {
            $table->id(); $table->string('name', 100); $table->string('slug', 100)->unique(); $table->timestamps();
        });
        Schema::create('products', function (Blueprint $table) {
            $table->id(); $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained();
            $table->enum('type', ['goods', 'food']); $table->string('title'); $table->text('description');
            $table->enum('item_condition', ['like_new', 'minor_defect', 'heavily_used'])->nullable();
            $table->date('expired_at')->nullable(); $table->decimal('market_price', 12, 2)->nullable();
            $table->decimal('system_price', 12, 2)->nullable(); $table->unsignedSmallInteger('ai_confidence')->default(0);
            $table->enum('status', ['analyzing', 'pending_approval', 'active', 'rejected', 'auto_removed', 'sold'])->default('analyzing');
            $table->timestamps(); $table->softDeletes(); $table->index(['status', 'type', 'expired_at']);
        });
        Schema::create('product_images', function (Blueprint $table) {
            $table->id(); $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('image_path'); $table->enum('image_type', ['full_product', 'expiry_label', 'defect_detail']); $table->timestamps();
        });
        Schema::create('ai_analysis_logs', function (Blueprint $table) {
            $table->id(); $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->text('raw_ocr_text')->nullable(); $table->date('detected_date')->nullable();
            $table->unsignedSmallInteger('confidence_score'); $table->json('raw_ai_response')->nullable(); $table->timestamps();
        });
        Schema::create('orders', function (Blueprint $table) {
            $table->id(); $table->string('order_number', 50)->unique();
            $table->foreignId('buyer_id')->constrained('users'); $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('fulfillment_type', ['direct_deal', 'expedition']); $table->json('shipping_address')->nullable();
            $table->string('shipping_courier', 50)->nullable(); $table->string('tracking_number', 100)->nullable();
            $table->decimal('item_amount', 12, 2); $table->decimal('shipping_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2); $table->enum('payment_status', ['unpaid', 'paid_escrow', 'released', 'refunded'])->default('unpaid');
            $table->enum('order_status', ['pending', 'processing', 'shipped', 'delivered', 'completed', 'cancelled', 'disputed'])->default('pending');
            $table->timestamp('delivered_at')->nullable(); $table->timestamps(); $table->softDeletes();
            $table->index(['order_status', 'payment_status', 'delivered_at']);
        });
        Schema::create('payments', function (Blueprint $table) {
            $table->id(); $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('payment_gateway_trx_id', 100)->nullable(); $table->string('payment_type', 50)->nullable();
            $table->string('snap_token')->nullable(); $table->json('raw_response')->nullable(); $table->timestamps();
        });
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id(); $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('type', ['credit', 'debit']); $table->decimal('amount', 12, 2); $table->string('description'); $table->timestamps();
        });
        Schema::create('withdrawals', function (Blueprint $table) {
            $table->id(); $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2); $table->string('bank_name', 50); $table->string('account_number', 50);
            $table->string('account_holder_name'); $table->enum('status', ['pending', 'completed', 'rejected'])->default('pending'); $table->timestamps();
        });
        Schema::create('chats', function (Blueprint $table) {
            $table->id(); $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('receiver_id')->constrained('users')->cascadeOnDelete();
            $table->text('message'); $table->boolean('is_read')->default(false); $table->timestamps();
        });
        Schema::create('reports', function (Blueprint $table) {
            $table->id(); $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reporter_id')->constrained('users')->cascadeOnDelete(); $table->text('reason');
            $table->enum('status', ['open', 'investigating', 'resolved', 'rejected'])->default('open'); $table->timestamps();
        });
        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id(); $table->morphs('tokenable'); $table->string('name');
            $table->string('token', 64)->unique(); $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable(); $table->timestamp('expires_at')->nullable(); $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['personal_access_tokens','reports','chats','withdrawals','wallet_transactions','payments','orders','ai_analysis_logs','product_images','products','categories','user_addresses'] as $table) Schema::dropIfExists($table);
    }
};
