<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Staff Award Periods
        if (!Schema::hasTable('staff_award_periods')) {
            Schema::create('staff_award_periods', function (Blueprint $table) {
                $table->id();
            $table->unsignedInteger('business_id');
            $table->enum('period_type', ['weekly', 'monthly', 'yearly']);
            $table->date('period_start');
            $table->date('period_end');
            $table->integer('winner_count')->default(10);
            $table->boolean('is_finalized')->default(false);
            $table->timestamp('finalized_at')->nullable();
            $table->unsignedInteger('finalized_by')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('finalized_by')->references('id')->on('users')->onDelete('set null');
            $table->unique(['business_id', 'period_type', 'period_start']);
            });
        }

        // Staff Awards
        if (!Schema::hasTable('staff_awards')) {
            Schema::create('staff_awards', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('staff_id');
            $table->unsignedBigInteger('period_id')->nullable();
            $table->enum('period_type', ['weekly', 'monthly', 'yearly']);
            $table->date('period_start');
            $table->date('period_end');
            $table->integer('rank_position');
            $table->decimal('sales_total', 15, 4)->default(0);
            $table->integer('transaction_count')->default(0);
            $table->decimal('avg_transaction_value', 15, 4)->default(0);
            $table->integer('performance_points')->default(0);
            $table->decimal('final_score', 15, 4)->default(0);
            $table->enum('award_type', ['manual', 'catalog'])->nullable();
            $table->unsignedInteger('catalog_item_id')->nullable();
            $table->integer('award_quantity')->default(1);
            $table->string('gift_description')->nullable();
            $table->decimal('gift_monetary_value', 15, 4)->default(0);
            $table->boolean('stock_deducted')->default(false);
            $table->text('award_notes')->nullable();
            $table->boolean('is_awarded')->default(false);
            $table->unsignedInteger('awarded_by')->nullable();
            $table->timestamp('awarded_date')->nullable();
            $table->boolean('notification_sent')->default(false);
            $table->string('certificate_path')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('staff_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('period_id')->references('id')->on('staff_award_periods')->onDelete('set null');
            $table->foreign('catalog_item_id')->references('id')->on('variations')->onDelete('set null');
            $table->foreign('awarded_by')->references('id')->on('users')->onDelete('set null');
            });
        }

        // Staff Performance Activities
        if (!Schema::hasTable('staff_performance_activities')) {
            Schema::create('staff_performance_activities', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('staff_id');
            $table->enum('activity_type', ['punctuality', 'customer_service', 'upselling', 'teamwork', 'training_completion', 'cleanliness', 'other']);
            $table->integer('points');
            $table->text('description')->nullable();
            $table->string('reference_url')->nullable();
            $table->text('verification_notes')->nullable();
            $table->unsignedInteger('recorded_by');
            $table->date('recorded_date');
            $table->enum('status', ['pending', 'verified', 'rejected'])->default('verified');
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('staff_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('recorded_by')->references('id')->on('users')->onDelete('cascade');
            });
        }

        // Staff Recognition Settings
        if (!Schema::hasTable('staff_recognition_settings')) {
            Schema::create('staff_recognition_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->json('performance_weights')->nullable();
            $table->json('activity_points')->nullable();
            $table->integer('min_transactions_weekly')->default(10);
            $table->integer('min_transactions_monthly')->default(50);
            $table->integer('min_transactions_yearly')->default(500);
            $table->boolean('auto_finalize_periods')->default(false);
            $table->boolean('send_notifications')->default(true);
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('staff_recognition_settings');
        Schema::dropIfExists('staff_performance_activities');
        Schema::dropIfExists('staff_awards');
        Schema::dropIfExists('staff_award_periods');
    }
};