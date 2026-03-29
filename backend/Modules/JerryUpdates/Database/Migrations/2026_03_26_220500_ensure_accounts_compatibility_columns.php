<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('accounts')) {
            Schema::table('accounts', function (Blueprint $table) {
                if (! Schema::hasColumn('accounts', 'account_type_id')) {
                    $table->integer('account_type_id')->nullable()->after('account_number');
                }

                if (! Schema::hasColumn('accounts', 'account_details')) {
                    $table->text('account_details')->nullable()->after('account_type_id');
                }
            });
        }
    }

    public function down(): void
    {
        // Keep backward-compatible columns in place; avoid destructive rollback.
    }
};

