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
        Schema::create('transaction_exchanges', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('business_id')->unsigned();
            $table->integer('location_id')->unsigned();
            $table->integer('original_transaction_id')->unsigned()->comment('Reference to original sale');
            $table->integer('exchange_transaction_id')->unsigned()->comment('New exchange transaction');
            $table->string('exchange_ref_no');
            $table->datetime('exchange_date');
            $table->decimal('total_exchange_amount', 22, 4)->default(0.0000)->comment('Net amount (positive=customer pays, negative=refund)');
            $table->enum('status', ['pending', 'completed', 'cancelled'])->default('pending');
            $table->integer('created_by')->unsigned();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('location_id')->references('id')->on('business_locations')->onDelete('cascade');
            $table->foreign('original_transaction_id')->references('id')->on('transactions')->onDelete('cascade');
            $table->foreign('exchange_transaction_id')->references('id')->on('transactions')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');

            $table->index(['business_id', 'status']);
            $table->index(['exchange_date']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transaction_exchanges');
    }
};
