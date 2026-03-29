<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('transaction_sell_lines', function (Blueprint $table) {
            $table->boolean('is_exchange_return')->default(0)->comment('Mark if this line item was returned in exchange');
            $table->integer('exchange_parent_line_id')->unsigned()->nullable()->comment('Reference to original sell line if this is exchange replacement');

            $table->index(['is_exchange_return']);
            $table->index(['exchange_parent_line_id']);
        });
    }

    public function down()
    {
        Schema::table('transaction_sell_lines', function (Blueprint $table) {
            $table->dropIndex(['is_exchange_return']);
            $table->dropIndex(['exchange_parent_line_id']);
            $table->dropColumn(['is_exchange_return', 'exchange_parent_line_id']);
        });
    }
};
