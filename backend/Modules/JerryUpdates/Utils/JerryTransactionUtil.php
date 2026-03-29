<?php

namespace Modules\JerryUpdates\Utils;

use App\Utils\TransactionUtil;
use Illuminate\Support\Facades\DB;
use App\Contact;
use App\Transaction;
use App\TransactionPayment;
use App\TransactionSellLine;

class JerryTransactionUtil extends TransactionUtil
{
    /**
     * Function to get ledger details
     * (Overridden to fix the advance payment balance anomaly)
     * 
     * Updated to v12 base: adds hms_booking/gym_subscription transaction types
     * while preserving Jerry's advance payment bug fixes.
     */
    public function getLedgerDetails($contact_id, $start, $end, $format = 'format_1', $location_id = null, $line_details = false)
    {
        // Check if the advance payment ledger fix is enabled
        if (\Modules\JerryUpdates\Utils\JerrySettings::get('jerry_ledger_fix') != '1') {
            return parent::getLedgerDetails($contact_id, $start, $end, $format, $location_id, $line_details);
        }

        // 1. Call the PARENT strictly to get the 100% up-to-date base logic
        $output = parent::getLedgerDetails($contact_id, $start, $end, $format, $location_id, $line_details);

        // 3. Apply Jerry's Advance Payment Ledger Math Adjustments over the top
        //    Core base computes total_paid correctly but uses total_transactions_paid for the balance_due instead.
        //    Jerry aligns this to use total_paid.
        $output['balance_due'] = $output['total_invoice'] + $output['total_purchase'] - $output['total_paid'] + $output['beginning_balance'];

        // 4. Core base computes overall_total_advance_payment but fails to add it to all_invoice_paid.
        //    We must re-fetch it and add it.
        $overall_total_advance_payment = $this->__paymentQuery($contact_id, null, null, $location_id)
            ->select(\Illuminate\Support\Facades\DB::raw('(transaction_payments.amount - COALESCE((SELECT SUM(amount) from transaction_payments as TP where TP.parent_id = transaction_payments.id), 0)) as amount'))
            ->where('is_advance', 1)
            ->get()
            ->sum('amount');

        $output['all_invoice_paid'] += $overall_total_advance_payment;
        $output['all_balance_due'] -= $overall_total_advance_payment;

        return $output;
    }

    /**
     * Query to get transaction totals for a customer
     */
    private function __transactionQuery($contact_id, $start, $end = null, $location_id = null)
    {
        $business_id = request()->session()->get('user.business_id');
        $transaction_type_keys = array_keys(Transaction::transactionTypes());

        $query = Transaction::where('transactions.contact_id', $contact_id)
                        ->where('transactions.business_id', $business_id)
                        ->where('transactions.status', '!=', 'draft')
                        ->whereIn('transactions.type', $transaction_type_keys);

        if (! empty($start) && ! empty($end)) {
            $query->whereDate(
                'transactions.transaction_date',
                '>=',
                $start
            )
                ->whereDate('transactions.transaction_date', '<=', $end);
        }

        if (! empty($location_id)) {
            $query->where('transactions.location_id', $location_id);
        }

        if (! empty($start) && empty($end)) {
            $query->whereDate('transactions.transaction_date', '<', $start);
        }

        return $query;
    }

    /**
     * Query to get payment details for a customer
     */
    private function __paymentQuery($contact_id, $start, $end = null, $location_id = null)
    {
        $business_id = request()->session()->get('user.business_id');

        $query = TransactionPayment::leftJoin(
            'transactions as t',
            'transaction_payments.transaction_id',
            '=',
            't.id'
        )
            ->leftJoin('business_locations as bl', 't.location_id', '=', 'bl.id')
            ->where('transaction_payments.payment_for', $contact_id)
            // use to not diaplay expense in payment list in ledger
            ->where(function ($query) {
                $query->where('t.type', '!=', 'expense')
                      ->orWhereNull('t.type');
            }); 
        //->whereNull('transaction_payments.parent_id');

        if (! empty($start) && ! empty($end)) {
            $query->whereDate('paid_on', '>=', $start)
                        ->whereDate('paid_on', '<=', $end);
        }

        if (! empty($start) && empty($end)) {
            $query->whereDate('paid_on', '<', $start);
        }

        if (! empty($location_id)) {
            //if location id present get all transaction with the location id and opening balance
            $query->where(function ($q) use ($location_id) {
                $q->where('transaction_payments.is_advance', 1)
                     ->orWhere('t.location_id', $location_id);
            });
        }

        return $query;
    }
}
