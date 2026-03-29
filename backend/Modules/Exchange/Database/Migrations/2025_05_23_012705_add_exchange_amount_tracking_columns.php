<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('transaction_exchanges', function (Blueprint $table) {
            $table->decimal('original_amount', 22, 4)
                ->default(0.0000)
                ->comment('Total amount of returned items');

            $table->decimal('new_amount', 22, 4)
                ->default(0.0000)
                ->comment('Total amount of new items');

            $table->decimal('exchange_difference', 22, 4)
                ->default(0.0000)
                ->comment('Difference amount (new - original)');

            $table->decimal('payment_received', 22, 4)
                ->default(0.0000)
                ->comment('Additional payment received from customer');

            $table->decimal('refund_given', 22, 4)
                ->default(0.0000)
                ->comment('Refund given to customer');
        });

        // Add indexes in a separate statement as some DBs don't support
        // adding indexes in the same operation as adding columns
        Schema::table('transaction_exchanges', function (Blueprint $table) {
            $table->index([
                'original_amount',
                'new_amount',
                'exchange_difference'
            ], 'idx_exchange_amounts');

            $table->index([
                'payment_received',
                'refund_given'
            ], 'idx_exchange_financial');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transaction_exchanges', function (Blueprint $table) {
            $table->dropIndex('idx_exchange_amounts');
            $table->dropIndex('idx_exchange_financial');

            $table->dropColumn([
                'original_amount',
                'new_amount',
                'exchange_difference',
                'payment_received',
                'refund_given'
            ]);
        });
    }
};
