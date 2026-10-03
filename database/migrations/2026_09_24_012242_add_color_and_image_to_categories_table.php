<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->text('colors')->nullable()->after('description'); // Lưu danh sách mảng màu (JSON)
            $table->string('image')->nullable()->after('colors');      // Lưu đường dẫn ảnh
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn(['colors', 'image']);
        });
    }
};