<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // Mã voucher (VD: XFAN10, GIAM50K)
            $table->enum('type', ['percent', 'fixed'])->default('fixed'); // percent: % | fixed: số tiền cố định
            $table->decimal('value', 15, 2); // Giá trị giảm (10% hoặc 50.000đ)
            $table->decimal('min_order_amount', 15, 2)->default(0); // Đơn hàng tối thiểu
            $table->boolean('is_active')->default(true); // 1: Hoạt động | 0: Khóa
            $table->timestamp('expires_at')->nullable(); // Ngày hết hạn
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};