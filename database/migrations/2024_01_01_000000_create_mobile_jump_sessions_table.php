<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mobile_jump_sessions', function (Blueprint $table) {
            $table->string('session_id', 32)->primary();
            $table->string('token_hash', 255);
            $table->string('frontend_url', 512);
            $table->string('api_url', 512);
            $table->unsignedSmallInteger('hmr_port')->default(5173);
            $table->timestamp('expires_at')->index();
            $table->text('device_info')->nullable();
            $table->timestamp('connected_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mobile_jump_sessions');
    }
};
