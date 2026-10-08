<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('support_requests')) {
            Schema::table('support_requests', function (Blueprint $table) {
                if (!Schema::hasColumn('support_requests', 'attachment_path')) {
                    $table->string('attachment_path')->nullable()->after('message');
                }
            });
        } else {
            Schema::create('support_requests', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email');
                $table->string('phone')->nullable();
                $table->string('subject');
                $table->text('message');
                $table->string('attachment_path')->nullable();
                $table->enum('status', ['pending', 'replied'])->default('pending');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('support_requests');
    }
};