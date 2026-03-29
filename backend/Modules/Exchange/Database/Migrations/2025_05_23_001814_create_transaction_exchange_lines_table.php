<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('transaction_exchange_lines', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('exchange_id')->unsigned();
            $table->integer('original_sell_line_id')->unsigned()->comment('Original item being returned');
            $table->integer('new_sell_line_id')->unsigned()->nullable()->comment('New item being sold (if any)');
            $table->enum('exchange_type', ['return_only', 'exchange_with_new', 'new_only'])->default('exchange_with_new');
            $table->decimal('original_quantity', 22, 4)->default(0.0000);
            $table->decimal('original_unit_price', 22, 4)->default(0.0000);
            $table->decimal('new_quantity', 22, 4)->default(0.0000);
            $table->decimal('new_unit_price', 22, 4)->default(0.0000);
            $table->decimal('price_difference', 22, 4)->default(0.0000)->comment('New price - Original price');
            $table->timestamps();

            $table->foreign('exchange_id')->references('id')->on('transaction_exchanges')->onDelete('cascade');
            $table->foreign('original_sell_line_id')->references('id')->on('transaction_sell_lines')->onDelete('cascade');
            $table->foreign('new_sell_line_id')->references('id')->on('transaction_sell_lines')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('transaction_exchange_lines');
    }
};
