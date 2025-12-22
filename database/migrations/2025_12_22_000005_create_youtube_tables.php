<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // YouTube Channels
        Schema::create('youtube_channels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('social_account_id')->constrained()->cascadeOnDelete();
            $table->string('channel_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('custom_url')->nullable();
            $table->text('thumbnail_url')->nullable();
            $table->text('banner_url')->nullable();
            $table->string('country', 10)->nullable();
            $table->unsignedBigInteger('subscriber_count')->default(0);
            $table->unsignedInteger('video_count')->default(0);
            $table->unsignedBigInteger('view_count')->default(0);
            $table->boolean('is_monetized')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['social_account_id', 'channel_id'], 'unique_channel');
        });

        // YouTube Videos
        Schema::create('youtube_videos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('youtube_channel_id')->constrained()->cascadeOnDelete();
            $table->string('video_id');
            $table->string('title', 500);
            $table->text('description')->nullable();
            $table->text('thumbnail_url')->nullable();
            $table->string('duration', 50)->nullable();
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->string('category_id', 50)->nullable();
            $table->json('tags')->nullable();
            $table->enum('privacy_status', ['public', 'private', 'unlisted'])->default('public');
            $table->boolean('is_live_content')->default(false);
            $table->boolean('is_short')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['youtube_channel_id', 'video_id'], 'unique_video');
            $table->index('published_at');
        });

        // YouTube Analytics
        Schema::create('youtube_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('youtube_channel_id')->constrained()->cascadeOnDelete();
            $table->foreignId('youtube_video_id')->nullable()->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->enum('metric_type', ['channel', 'video']);
            $table->unsignedBigInteger('views')->default(0);
            $table->unsignedBigInteger('watch_time_minutes')->default(0);
            $table->decimal('average_view_duration', 10, 2)->default(0);
            $table->decimal('average_view_percentage', 5, 2)->default(0);
            $table->integer('subscribers_gained')->default(0);
            $table->unsignedInteger('subscribers_lost')->default(0);
            $table->unsignedInteger('likes')->default(0);
            $table->unsignedInteger('dislikes')->default(0);
            $table->unsignedInteger('comments')->default(0);
            $table->unsignedInteger('shares')->default(0);
            $table->decimal('estimated_revenue', 10, 2)->default(0);
            $table->unsignedBigInteger('impressions')->default(0);
            $table->decimal('impressions_ctr', 5, 4)->default(0);
            $table->unsignedBigInteger('unique_viewers')->default(0);
            $table->json('traffic_source_data')->nullable();
            $table->json('device_type_data')->nullable();
            $table->json('geography_data')->nullable();
            $table->timestamps();

            $table->unique(['youtube_channel_id', 'youtube_video_id', 'date', 'metric_type'], 'unique_yt_analytics');
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('youtube_analytics');
        Schema::dropIfExists('youtube_videos');
        Schema::dropIfExists('youtube_channels');
    }
};

