<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('phone');
            $table->text('address');
            $table->decimal('total_price', 15, 2);
            $table->string('status')->default('pending');
            $table->integer('to_district_id')->nullable();
            $table->string('to_ward_code')->nullable();
            $table->decimal('ghn_total_fee', 15, 2)->default(0);
            $table->string('ghn_order_code')->nullable();
            $table->string('shipping_status')->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};