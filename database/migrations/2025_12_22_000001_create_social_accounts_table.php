<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('platform', ['facebook', 'youtube', 'instagram', 'google_analytics']);
            $table->string('platform_user_id');
            $table->string('platform_account_id');
            $table->string('account_name');
            $table->string('account_type', 50)->nullable(); // page, channel, business, property
            $table->text('access_token');
            $table->text('refresh_token')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->text('scopes')->nullable();
            $table->json('account_data')->nullable();
            $table->enum('status', ['active', 'expired', 'error', 'disconnected'])->default('active');
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'platform', 'platform_account_id'], 'unique_platform_account');
            $table->index('platform');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_accounts');
    }
};

