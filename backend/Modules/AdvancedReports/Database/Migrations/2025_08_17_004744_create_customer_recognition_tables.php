<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
/**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Customer Recognition Settings
        Schema::create('customer_recognition_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->boolean('weekly_enabled')->default(true);
            $table->boolean('monthly_enabled')->default(true);
            $table->boolean('yearly_enabled')->default(true);
            $table->integer('winner_count_weekly')->default(3);
            $table->integer('winner_count_monthly')->default(5);
            $table->integer('winner_count_yearly')->default(10);
            $table->enum('scoring_method', [
                'pure_sales', 
                'weighted', 
                'pure_payments', 
                'weighted_payments', 
                'payment_adjusted'
            ])->default('payment_adjusted');
            $table->decimal('sales_weight', 3, 2)->default(0.70); // 70%
            $table->decimal('engagement_weight', 3, 2)->default(0.30); // 30%
            $table->date('module_start_date');
            $table->boolean('calculate_historical')->default(false);
            $table->integer('historical_months')->default(12);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->unique('business_id');
        });

        // Customer Engagements
        Schema::create('customer_engagements', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('customer_id');
            $table->enum('engagement_type', [
                'youtube_follow', 
                'facebook_follow', 
                'content_share', 
                'review', 
                'referral',
                'instagram_follow',
                'twitter_follow',
                'google_review',
                'other'
            ]);
            $table->integer('points')->default(0); // 0-10 points
            $table->text('verification_notes')->nullable();
            $table->string('platform')->nullable(); // YouTube, Facebook, etc.
            $table->string('reference_url')->nullable(); // Link to post/review
            $table->unsignedInteger('recorded_by'); // Staff member
            $table->date('recorded_date');
            $table->enum('status', ['pending', 'verified', 'rejected'])->default('verified');
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('customer_id')->references('id')->on('contacts')->onDelete('cascade');
            $table->foreign('recorded_by')->references('id')->on('users')->onDelete('cascade');

            $table->index(['business_id', 'customer_id']);
            $table->index(['business_id', 'recorded_date']);
        });

        // Award Catalog
        Schema::create('award_catalog', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('product_id')->nullable(); // Link to products table
            $table->string('award_name');
            $table->text('description')->nullable();
            $table->integer('point_threshold')->default(0); // Minimum points to qualify
            $table->decimal('monetary_value', 22, 4)->default(0);
            $table->boolean('stock_required')->default(false); // Whether to deduct from inventory
            $table->integer('stock_quantity')->default(0); // Available award quantity
            $table->string('award_image')->nullable(); // Image path
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('set null');

            $table->index(['business_id', 'is_active']);
        });

        // Award Periods (for period finalization)
        Schema::create('award_periods', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->enum('period_type', ['weekly', 'monthly', 'yearly']);
            $table->date('period_start');
            $table->date('period_end');
            $table->boolean('is_finalized')->default(false);
            $table->datetime('finalized_at')->nullable();
            $table->unsignedInteger('finalized_by')->nullable();
            $table->integer('total_participants')->default(0);
            $table->integer('winners_count')->default(0);
            $table->json('period_summary')->nullable(); // Store summary stats
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('finalized_by')->references('id')->on('users')->onDelete('set null');

            $table->unique(['business_id', 'period_type', 'period_start'], 'ap_business_period_start_unique');
            $table->index(['business_id', 'period_type', 'is_finalized'], 'ap_business_period_finalized_idx');
        });

        // Customer Awards
        Schema::create('customer_awards', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('customer_id');
            $table->unsignedBigInteger('period_id')->nullable(); // Link to award_periods
            $table->enum('period_type', ['weekly', 'monthly', 'yearly']);
            $table->date('period_start');
            $table->date('period_end');
            $table->integer('rank_position'); // 1st, 2nd, 3rd, etc.
            $table->decimal('sales_total', 22, 4)->default(0);
            $table->integer('engagement_points')->default(0);
            $table->decimal('final_score', 22, 4)->default(0);
            $table->integer('transaction_count')->default(0);
            $table->decimal('avg_transaction_value', 22, 4)->default(0);
            
            // Award Details
            $table->enum('award_type', ['manual', 'catalog', 'none'])->default('none');
            $table->unsignedBigInteger('catalog_item_id')->nullable();
            $table->string('gift_description')->nullable();
            $table->decimal('gift_monetary_value', 22, 4)->default(0);
            $table->boolean('stock_deducted')->default(false);
            $table->text('award_notes')->nullable();
            
            // Award Tracking
            $table->boolean('is_awarded')->default(false);
            $table->unsignedInteger('awarded_by')->nullable();
            $table->datetime('awarded_date')->nullable();
            $table->boolean('notification_sent')->default(false);
            $table->string('certificate_path')->nullable(); // Generated certificate file
            
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('customer_id')->references('id')->on('contacts')->onDelete('cascade');
            $table->foreign('period_id')->references('id')->on('award_periods')->onDelete('set null');
            $table->foreign('catalog_item_id')->references('id')->on('award_catalog')->onDelete('set null');
            $table->foreign('awarded_by')->references('id')->on('users')->onDelete('set null');

            $table->unique(['business_id', 'customer_id', 'period_type', 'period_start'], 'ca_business_customer_period_unique');
            $table->index(['business_id', 'period_type', 'rank_position'], 'ca_business_period_rank_idx');
            $table->index(['business_id', 'is_awarded'], 'ca_business_awarded_idx');
        });

        // Customer Recognition Cache (for performance)
        Schema::create('customer_recognition_cache', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('customer_id');
            $table->enum('period_type', ['weekly', 'monthly', 'yearly']);
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('sales_total', 22, 4)->default(0);
            $table->integer('engagement_points')->default(0);
            $table->decimal('final_score', 22, 4)->default(0);
            $table->integer('transaction_count')->default(0);
            $table->integer('current_rank')->nullable();
            $table->datetime('last_updated');
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('customer_id')->references('id')->on('contacts')->onDelete('cascade');

            $table->unique(['business_id', 'customer_id', 'period_type', 'period_start'], 'crc_business_customer_period_unique');
            $table->index(['business_id', 'period_type', 'final_score'], 'crc_business_period_score_idx');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('customer_recognition_cache');
        Schema::dropIfExists('customer_awards');
        Schema::dropIfExists('award_periods');
        Schema::dropIfExists('award_catalog');
        Schema::dropIfExists('customer_engagements');
        Schema::dropIfExists('customer_recognition_settings');
    }

};