<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            if (!Schema::hasColumn('coupons', 'hood_id')) {
                $table->unsignedBigInteger('hood_id')->nullable()->after('code');
            }
            if (!Schema::hasColumn('coupons', 'category_id')) {
                $table->unsignedBigInteger('category_id')->nullable()->after('hood_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->dropColumn(['hood_id', 'category_id']);
        });
    }
};