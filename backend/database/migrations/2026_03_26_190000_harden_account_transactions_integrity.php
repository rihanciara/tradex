<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
        // Remove rows pointing to missing payments/accounts before adding constraints.
        DB::table('account_transactions as at')
            ->whereNotNull('at.transaction_payment_id')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('transaction_payments as tp')
                    ->whereColumn('tp.id', 'at.transaction_payment_id');
            })
            ->delete();

        DB::table('account_transactions as at')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('accounts as a')
                    ->whereColumn('a.id', 'at.account_id');
            })
            ->delete();

        // Keep one ledger row per payment to prevent duplicate booking.
        $duplicates = DB::table('account_transactions')
            ->select('transaction_payment_id', DB::raw('MIN(id) as keep_id'))
            ->whereNotNull('transaction_payment_id')
            ->groupBy('transaction_payment_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $dup) {
            DB::table('account_transactions')
                ->where('transaction_payment_id', $dup->transaction_payment_id)
                ->where('id', '!=', $dup->keep_id)
                ->delete();
        }

        Schema::table('account_transactions', function (Blueprint $table) {
            $table->index(['account_id', 'operation_date'], 'at_account_date_idx');
            $table->unique('transaction_payment_id', 'at_unique_payment_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('account_transactions', function (Blueprint $table) {
            $table->dropUnique('at_unique_payment_id');
            $table->dropIndex('at_account_date_idx');
        });
    }
};
