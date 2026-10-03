<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('orders', 'ghn_order_code')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->string('ghn_order_code')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('orders', 'ghn_order_code')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn('ghn_order_code');
            });
        }
    }
};