<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->text('pickup_address')->nullable();
            $table->decimal('pickup_latitude', 10, 7)->nullable();
            $table->decimal('pickup_longitude', 10, 7)->nullable();
            $table->unsignedInteger('weight_gram')->default(1000);
        });

        Schema::create('shipping_quotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('courier_code', 40);
            $table->string('courier_name', 100);
            $table->string('service_code', 60)->nullable();
            $table->string('service_name', 100);
            $table->unsignedInteger('price');
            $table->string('duration', 80)->nullable();
            $table->json('destination')->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'product_id', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_quotes');
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['pickup_address', 'pickup_latitude', 'pickup_longitude', 'weight_gram']);
        });
    }
};
