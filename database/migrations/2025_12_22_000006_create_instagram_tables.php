<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Instagram Accounts
        Schema::create('instagram_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('social_account_id')->constrained()->cascadeOnDelete();
            $table->string('instagram_id');
            $table->string('username');
            $table->string('name')->nullable();
            $table->text('biography')->nullable();
            $table->text('profile_picture_url')->nullable();
            $table->string('website', 500)->nullable();
            $table->unsignedInteger('followers_count')->default(0);
            $table->unsignedInteger('follows_count')->default(0);
            $table->unsignedInteger('media_count')->default(0);
            $table->enum('account_type', ['business', 'creator']);
            $table->timestamps();

            $table->unique(['social_account_id', 'instagram_id'], 'unique_ig_account');
        });

        // Instagram Posts
        Schema::create('instagram_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instagram_account_id')->constrained()->cascadeOnDelete();
            $table->string('media_id');
            $table->enum('media_type', ['IMAGE', 'VIDEO', 'CAROUSEL_ALBUM', 'REELS', 'STORY']);
            $table->text('media_url')->nullable();
            $table->text('thumbnail_url')->nullable();
            $table->text('permalink')->nullable();
            $table->text('caption')->nullable();
            $table->json('hashtags')->nullable();
            $table->string('location')->nullable();
            $table->boolean('is_story')->default(false);
            $table->timestamp('story_expires_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['instagram_account_id', 'media_id'], 'unique_ig_media');
            $table->index('media_type');
            $table->index('published_at');
        });

        // Instagram Analytics
        Schema::create('instagram_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instagram_account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('instagram_post_id')->nullable()->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->enum('metric_type', ['account', 'post', 'story', 'reel']);
            $table->unsignedInteger('impressions')->default(0);
            $table->unsignedInteger('reach')->default(0);
            $table->unsignedInteger('profile_views')->default(0);
            $table->unsignedInteger('website_clicks')->default(0);
            $table->unsignedInteger('email_contacts')->default(0);
            $table->unsignedInteger('phone_call_clicks')->default(0);
            $table->integer('followers_gained')->default(0);
            $table->unsignedInteger('likes')->default(0);
            $table->unsignedInteger('comments')->default(0);
            $table->unsignedInteger('saves')->default(0);
            $table->unsignedInteger('shares')->default(0);
            $table->unsignedInteger('video_views')->default(0);
            $table->decimal('engagement_rate', 5, 4)->default(0);
            $table->unsignedInteger('story_exits')->default(0);
            $table->unsignedInteger('story_replies')->default(0);
            $table->unsignedInteger('story_taps_forward')->default(0);
            $table->unsignedInteger('story_taps_back')->default(0);
            $table->json('audience_demographics')->nullable();
            $table->timestamps();

            $table->unique(['instagram_account_id', 'instagram_post_id', 'date', 'metric_type'], 'unique_ig_analytics');
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instagram_analytics');
        Schema::dropIfExists('instagram_posts');
        Schema::dropIfExists('instagram_accounts');
    }
};

