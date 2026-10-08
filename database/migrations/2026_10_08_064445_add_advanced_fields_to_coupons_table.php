<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            if (!Schema::hasColumn('coupons', 'quantity')) {
                $table->integer('quantity')->default(100)->after('min_order_amount');
            }
            if (!Schema::hasColumn('coupons', 'used_count')) {
                $table->integer('used_count')->default(0)->after('quantity');
            }
            if (!Schema::hasColumn('coupons', 'category_id')) {
                $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete()->after('used_count');
            }
            if (!Schema::hasColumn('coupons', 'start_date')) {
                $table->timestamp('start_date')->nullable()->after('category_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            if (Schema::hasColumn('coupons', 'category_id')) {
                $table->dropForeign(['category_id']);
            }
            $table->dropColumn(array_filter(['quantity', 'used_count', 'category_id', 'start_date'], fn($col) => Schema::hasColumn('coupons', $col)));
        });
    }
};