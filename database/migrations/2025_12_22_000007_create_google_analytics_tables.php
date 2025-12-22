<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Google Analytics Properties
        Schema::create('google_analytics_properties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('social_account_id')->constrained()->cascadeOnDelete();
            $table->string('property_id');
            $table->string('property_name');
            $table->enum('property_type', ['GA4'])->default('GA4');
            $table->string('account_id');
            $table->string('account_name')->nullable();
            $table->string('currency_code', 10)->default('USD');
            $table->string('time_zone', 100)->nullable();
            $table->string('industry_category', 100)->nullable();
            $table->enum('service_level', ['STANDARD', 'PREMIUM'])->default('STANDARD');
            $table->timestamps();

            $table->unique(['social_account_id', 'property_id'], 'unique_ga_property');
        });

        // Google Analytics Data
        Schema::create('google_analytics_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('google_analytics_property_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('dimension_type', 50)->nullable();
            $table->string('dimension_value', 500)->nullable();
            $table->unsignedInteger('sessions')->default(0);
            $table->unsignedInteger('total_users')->default(0);
            $table->unsignedInteger('new_users')->default(0);
            $table->unsignedInteger('active_users')->default(0);
            $table->unsignedInteger('pageviews')->default(0);
            $table->decimal('screens_per_session', 5, 2)->default(0);
            $table->decimal('average_session_duration', 10, 2)->default(0);
            $table->decimal('bounce_rate', 5, 4)->default(0);
            $table->decimal('engagement_rate', 5, 4)->default(0);
            $table->unsignedInteger('engaged_sessions')->default(0);
            $table->unsignedInteger('events_count')->default(0);
            $table->unsignedInteger('conversions')->default(0);
            $table->decimal('total_revenue', 12, 2)->default(0);
            $table->unsignedInteger('ecommerce_purchases')->default(0);
            $table->timestamps();

            $table->index('date');
            $table->index(['google_analytics_property_id', 'date', 'dimension_type'], 'ga_data_property_date_dimension_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('google_analytics_data');
        Schema::dropIfExists('google_analytics_properties');
    }
};

