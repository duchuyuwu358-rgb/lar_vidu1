<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('hoods', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Tên máy hút mùi
            $table->string('model')->nullable(); // Model
            $table->text('description')->nullable(); // Mô tả
            $table->foreignId('category_id')->constrained('categories')->onDelete('cascade'); // Danh mục
            $table->decimal('price', 12, 2)->nullable(); // Giá
            $table->string('power')->nullable(); // Công suất (W)
            $table->string('dimensions')->nullable(); // Kích thước
            $table->string('color')->nullable(); // Màu sắc
            $table->string('manufacturer')->nullable(); // Nhà sản xuất
            $table->string('material')->nullable(); // Chất liệu
            $table->integer('warranty_months')->default(12); // Bảo hành (tháng)
            $table->enum('type', ['wall-mounted', 'under-cabinet', 'island', 'cooktop'])->default('wall-mounted'); // Loại hút mùi
            
            // BỔ SUNG CỘT LƯU ẢNH (Dùng longText để chứa link dài / dữ liệu Base64)
            $table->longText('image')->nullable();
            $table->longText('image_url')->nullable();

            $table->boolean('is_active')->default(true); // Kích hoạt/Ẩn
            $table->integer('stock_quantity')->default(0); // Số lượng tồn kho
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hoods');
    }
};