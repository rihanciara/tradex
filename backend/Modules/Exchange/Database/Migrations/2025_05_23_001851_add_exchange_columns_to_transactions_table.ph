<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->integer('exchange_parent_id')->unsigned()->nullable()->comment('Reference to original transaction if this is an exchange');
            $table->boolean('is_exchange')->default(0)->comment('Mark if this transaction is an exchange');

            $table->index(['exchange_parent_id']);
            $table->index(['is_exchange']);
        });
    }

    public function down()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex(['exchange_parent_id']);
            $table->dropIndex(['is_exchange']);
            $table->dropColumn(['exchange_parent_id', 'is_exchange']);
        });
    }
};
