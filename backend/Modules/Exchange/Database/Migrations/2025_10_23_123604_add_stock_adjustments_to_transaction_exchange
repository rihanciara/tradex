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
        Schema::table('transaction_exchanges', function (Blueprint $table) {
            $table->text('stock_adjustments')->nullable()->comment('JSON data of stock adjustments for reversal');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transaction_exchanges', function (Blueprint $table) {
            $table->dropColumn('stock_adjustments');
        });
    }
};
