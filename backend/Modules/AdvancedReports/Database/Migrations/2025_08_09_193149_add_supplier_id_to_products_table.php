<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Check if supplier_id column doesn't exist before adding it
        if (!Schema::hasColumn('products', 'supplier_id')) {
            Schema::table('products', function (Blueprint $table) {      
                $table->unsignedInteger('supplier_id')->nullable()->after('brand_id');
                $table->foreign('supplier_id')->references('id')->on('contacts')->onDelete('set null');
                $table->index('supplier_id');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('products', 'supplier_id')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropForeign(['supplier_id']);
                $table->dropIndex(['supplier_id']);
                $table->dropColumn('supplier_id');
            });
        }
    }
};