<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('support_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('support_requests', 'reply_content')) {
                $table->text('reply_content')->nullable()->after('message');
            }
        });
    }

    public function down(): void
    {
        Schema::table('support_requests', function (Blueprint $table) {
            if (Schema::hasColumn('support_requests', 'reply_content')) {
                $table->dropColumn('reply_content');
            }
        });
    }
};