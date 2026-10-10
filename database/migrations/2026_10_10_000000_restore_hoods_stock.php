<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Chạy migration cập nhật lại tồn kho sản phẩm
     */
    public function up(): void
    {
        // Cập nhật tất cả sản phẩm đang có số lượng = 0 thành 10
        if (Schema::hasTable('hoods')) {
            if (Schema::hasColumn('hoods', 'stock')) {
                DB::table('hoods')->where('stock', '<=', 0)->update(['stock' => 10]);
            }
            if (Schema::hasColumn('hoods', 'stock_quantity')) {
                DB::table('hoods')->where('stock_quantity', '<=', 0)->update(['stock_quantity' => 10]);
            }
        }

        if (Schema::hasTable('products')) {
            if (Schema::hasColumn('products', 'stock')) {
                DB::table('products')->where('stock', '<=', 0)->update(['stock' => 10]);
            }
            if (Schema::hasColumn('products', 'stock_quantity')) {
                DB::table('products')->where('stock_quantity', '<=', 0)->update(['stock_quantity' => 10]);
            }
        }
    }

    public function down(): void
    {
        //
    }
};