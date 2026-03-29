<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
        try {
            // Report configurations table
            if (!Schema::hasTable('advanced_report_configurations')) {
                Schema::create('advanced_report_configurations', function (Blueprint $table) {
                    $table->increments('id');
                    $table->string('report_type', 50);
                    $table->string('report_name', 100);
                    $table->longText('columns')->nullable();
                    $table->longText('filters')->nullable();
                    $table->longText('settings')->nullable();
                    $table->boolean('is_active')->default(true);
                    $table->integer('created_by')->unsigned()->nullable();
                    $table->timestamps();

                    $table->index(['report_type', 'is_active'], 'arc_type_active_idx');
                });
            }

            // Report schedules table
            if (!Schema::hasTable('advanced_report_schedules')) {
                Schema::create('advanced_report_schedules', function (Blueprint $table) {
                    $table->increments('id');
                    $table->integer('business_id')->unsigned();
                    $table->string('report_type', 50);
                    $table->string('name', 100);
                    $table->longText('filters')->nullable();
                    $table->enum('frequency', ['daily', 'weekly', 'monthly', 'quarterly']);
                    $table->longText('email_recipients')->nullable();
                    $table->boolean('is_active')->default(true);
                    $table->timestamp('last_run_at')->nullable();
                    $table->timestamp('next_run_at')->nullable();
                    $table->integer('created_by')->unsigned()->nullable();
                    $table->timestamps();

                    // Only add foreign key if business table exists
                    if (Schema::hasTable('business')) {
                        $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
                    }
                    $table->index(['business_id', 'is_active'], 'ars_biz_active_idx');
                    $table->index('next_run_at', 'ars_next_run_idx');
                });
            }

            // Report exports table
            if (!Schema::hasTable('advanced_report_exports')) {
                Schema::create('advanced_report_exports', function (Blueprint $table) {
                    $table->increments('id');
                    $table->integer('business_id')->unsigned();
                    $table->string('report_type', 50);
                    $table->string('file_name', 255);
                    $table->string('file_path', 500)->nullable();
                    $table->string('export_format', 20)->default('excel');
                    $table->longText('filters')->nullable();
                    $table->integer('total_records')->default(0);
                    $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
                    $table->longText('error_message')->nullable();
                    $table->timestamp('started_at')->nullable();
                    $table->timestamp('completed_at')->nullable();
                    $table->integer('created_by')->unsigned()->nullable();
                    $table->timestamps();

                    if (Schema::hasTable('business')) {
                        $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
                    }
                    $table->index(['business_id', 'status'], 'are_biz_status_idx');
                    $table->index(['created_at', 'status'], 'are_created_status_idx');
                });
            }

            // Report saved filters table
            if (!Schema::hasTable('advanced_report_saved_filters')) {
                Schema::create('advanced_report_saved_filters', function (Blueprint $table) {
                    $table->increments('id');
                    $table->integer('business_id')->unsigned();
                    $table->integer('user_id')->unsigned();
                    $table->string('report_type', 50);
                    $table->string('filter_name', 100);
                    $table->longText('filter_data');
                    $table->boolean('is_default')->default(false);
                    $table->timestamps();

                    if (Schema::hasTable('business')) {
                        $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
                    }
                    if (Schema::hasTable('users')) {
                        $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                    }
                    $table->index(['business_id', 'user_id', 'report_type'], 'arsf_biz_user_type_idx');
                });
            }

            // Insert default configurations safely
            $this->insertDefaultConfigurations();
        } catch (\Exception $e) {
            Log::error('AdvancedReports Migration Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        try {
            Schema::dropIfExists('advanced_report_saved_filters');
            Schema::dropIfExists('advanced_report_exports');
            Schema::dropIfExists('advanced_report_schedules');
            Schema::dropIfExists('advanced_report_configurations');
        } catch (\Exception $e) {
            Log::error('AdvancedReports Migration Rollback Error: ' . $e->getMessage());
        }
    }

    /**
     * Insert default report configurations safely
     */
    private function insertDefaultConfigurations()
    {
        try {
            // Check if configurations already exist
            $existing = DB::table('advanced_report_configurations')->count();
            if ($existing > 0) {
                return; // Skip if already exists
            }

            $configurations = [
                [
                    'report_type' => 'stock',
                    'report_name' => 'Stock Report',
                    'columns' => json_encode([
                        'sku',
                        'product_name',
                        'variation_name',
                        'category',
                        'location',
                        'current_stock',
                        'unit_price',
                        'stock_value_purchase',
                        'stock_value_sale',
                        'potential_profit',
                        'total_sold'
                    ]),
                    'filters' => json_encode([
                        'location_id',
                        'category_id',
                        'brand_id',
                        'unit_id',
                        'stock_status'
                    ]),
                    'settings' => json_encode([
                        'show_zero_stock' => true,
                        'show_negative_stock' => true,
                        'group_by_location' => false,
                        'include_variations' => true
                    ]),
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now()
                ],
                [
                    'report_type' => 'sales',
                    'report_name' => 'Sales Report',
                    'columns' => json_encode([
                        'invoice_no',
                        'transaction_date',
                        'customer_name',
                        'location',
                        'payment_status',
                        'total_before_tax',
                        'tax_amount',
                        'final_total',
                        'total_paid',
                        'balance_due',
                        'created_by'
                    ]),
                    'filters' => json_encode([
                        'date_range',
                        'location_id',
                        'customer_id',
                        'payment_status',
                        'created_by',
                        'payment_method'
                    ]),
                    'settings' => json_encode([
                        'include_quotations' => false,
                        'include_draft' => false,
                        'group_by_day' => false,
                        'show_line_items' => false
                    ]),
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            ];

            foreach ($configurations as $config) {
                DB::table('advanced_report_configurations')->insert($config);
            }

            Log::info('AdvancedReports: Default configurations inserted successfully');
        } catch (\Exception $e) {
            Log::error('AdvancedReports: Error inserting default configs: ' . $e->getMessage());
            // Don't throw - let migration continue
        }
    }
}; // ✅ FIXED: Added semicolon here!