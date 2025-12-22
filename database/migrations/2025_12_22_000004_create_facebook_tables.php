<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Facebook Pages
        Schema::create('facebook_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('social_account_id')->constrained()->cascadeOnDelete();
            $table->string('page_id');
            $table->string('name');
            $table->string('username')->nullable();
            $table->string('category')->nullable();
            $table->unsignedInteger('followers_count')->default(0);
            $table->unsignedInteger('likes_count')->default(0);
            $table->text('profile_picture_url')->nullable();
            $table->text('cover_photo_url')->nullable();
            $table->string('website', 500)->nullable();
            $table->text('about')->nullable();
            $table->boolean('is_published')->default(true);
            $table->timestamps();

            $table->unique(['social_account_id', 'page_id'], 'unique_page');
        });

        // Facebook Posts
        Schema::create('facebook_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facebook_page_id')->constrained()->cascadeOnDelete();
            $table->string('post_id');
            $table->text('message')->nullable();
            $table->text('story')->nullable();
            $table->enum('post_type', ['status', 'photo', 'video', 'link', 'share', 'event']);
            $table->text('permalink_url')->nullable();
            $table->text('full_picture')->nullable();
            $table->boolean('is_ad')->default(false);
            $table->boolean('is_published')->default(true);
            $table->timestamp('scheduled_publish_time')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['facebook_page_id', 'post_id'], 'unique_post');
            $table->index('published_at');
            $table->index('is_ad');
        });

        // Facebook Analytics
        Schema::create('facebook_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facebook_page_id')->constrained()->cascadeOnDelete();
            $table->foreignId('facebook_post_id')->nullable()->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->enum('metric_type', ['page', 'post']);
            $table->unsignedInteger('impressions')->default(0);
            $table->unsignedInteger('reach')->default(0);
            $table->unsignedInteger('engaged_users')->default(0);
            $table->unsignedInteger('reactions_total')->default(0);
            $table->unsignedInteger('reactions_like')->default(0);
            $table->unsignedInteger('reactions_love')->default(0);
            $table->unsignedInteger('reactions_haha')->default(0);
            $table->unsignedInteger('reactions_wow')->default(0);
            $table->unsignedInteger('reactions_sad')->default(0);
            $table->unsignedInteger('reactions_angry')->default(0);
            $table->unsignedInteger('comments')->default(0);
            $table->unsignedInteger('shares')->default(0);
            $table->unsignedInteger('clicks')->default(0);
            $table->unsignedInteger('video_views')->default(0);
            $table->unsignedInteger('video_view_time')->default(0);
            $table->decimal('ctr', 5, 4)->default(0);
            $table->decimal('engagement_rate', 5, 4)->default(0);
            $table->unsignedInteger('negative_feedback')->default(0);
            $table->timestamps();

            $table->unique(['facebook_page_id', 'facebook_post_id', 'date', 'metric_type'], 'unique_fb_analytics');
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facebook_analytics');
        Schema::dropIfExists('facebook_posts');
        Schema::dropIfExists('facebook_pages');
    }
};

