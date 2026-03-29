<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccountTransaction extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'operation_date' => 'datetime',
    ];

    public function media()
    {
        return $this->morphMany(\App\Media::class, 'model');
    }

    public function transaction()
    {
        return $this->belongsTo(\App\Transaction::class, 'transaction_id');
    }

    /**
     * Gives account transaction type from payment transaction type
     *
     * @param  string  $payment_transaction_type
     * @return string
     */
    public static function getAccountTransactionType($tansaction_type)
    {
        $account_transaction_types = [
            'sell' => 'credit',
            'purchase' => 'debit',
            'expense' => 'debit',
            'purchase_return' => 'credit',
            'sell_return' => 'debit',
            'payroll' => 'debit',
            'expense_refund' => 'credit',
            'hms_booking' => 'credit',
            'gym_subscription' => 'credit',
        ];

        return $account_transaction_types[$tansaction_type] ?? null;
    }

    /**
     * Creates new account transaction
     *
     * @return obj
     */
    public static function createAccountTransaction($data)
    {
        $transaction_data = [
            'amount' => $data['amount'],
            'account_id' => $data['account_id'],
            'type' => $data['type'],
            'sub_type' => ! empty($data['sub_type']) ? $data['sub_type'] : null,
            'operation_date' => ! empty($data['operation_date']) ? $data['operation_date'] : \Carbon::now(),
            'created_by' => $data['created_by'],
            'transaction_id' => ! empty($data['transaction_id']) ? $data['transaction_id'] : null,
            'transaction_payment_id' => ! empty($data['transaction_payment_id']) ? $data['transaction_payment_id'] : null,
            'note' => ! empty($data['note']) ? $data['note'] : null,
            'transfer_transaction_id' => ! empty($data['transfer_transaction_id']) ? $data['transfer_transaction_id'] : null,
        ];

        $account_transaction = AccountTransaction::create($transaction_data);

        return $account_transaction;
    }

    /**
     * Keep a payment-linked account transaction in sync.
     * If payment is not linked to an account, remove stale account transaction rows.
     */
    public static function syncForPayment($transaction_payment, $transaction_type = null)
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($transaction_payment, $transaction_type) {
            if (! $transaction_payment->relationLoaded('transaction')) {
                $transaction_payment->load('transaction.contact');
            }

            $existing = AccountTransaction::where('transaction_payment_id', $transaction_payment->id)
                ->lockForUpdate()
                ->get();

            if (empty($transaction_payment->account_id)) {
                if ($existing->isNotEmpty()) {
                    AccountTransaction::where('transaction_payment_id', $transaction_payment->id)->delete();
                }

                return null;
            }

            $type = in_array($transaction_payment->payment_type, ['credit', 'debit']) ? $transaction_payment->payment_type : null;
            if (empty($type) && ! empty($transaction_type)) {
                $type = self::getAccountTransactionType($transaction_type);
            }
            if (empty($type) && ! empty($transaction_payment->transaction->type)) {
                $type = self::getAccountTransactionType($transaction_payment->transaction->type);
            }
            if (
                empty($type)
                && (
                    (! empty($transaction_type) && $transaction_type == 'opening_balance')
                    || (! empty($transaction_payment->transaction->type) && $transaction_payment->transaction->type == 'opening_balance')
                )
            ) {
                $contact_type = ! empty($transaction_payment->transaction->contact->type) ? $transaction_payment->transaction->contact->type : null;
                if ($contact_type == 'supplier') {
                    $type = 'debit';
                } elseif ($contact_type == 'customer') {
                    $type = 'credit';
                }
            }

            // If change return then force debit.
            if (! empty($transaction_payment->transaction) && $transaction_payment->transaction->type == 'sell' && $transaction_payment->is_return == 1) {
                $type = 'debit';
            }
            if (! empty($transaction_payment->transaction) && in_array($transaction_payment->transaction->type, ['hms_booking', 'gym_subscription']) && $transaction_payment->is_return == 1) {
                $type = 'debit';
            }
            if (empty($type)) {
                // Unknown mapping: keep existing row type if present; otherwise skip unsafe creation.
                if (! empty($existing->first())) {
                    $type = $existing->first()->type;
                } else {
                    \Log::warning('Skipping account transaction sync due to unknown debit/credit mapping.', [
                        'transaction_payment_id' => $transaction_payment->id,
                        'transaction_type' => $transaction_type,
                        'payment_type' => $transaction_payment->payment_type,
                    ]);

                    return null;
                }
            }

            $account_transaction = $existing->first();
            if (! empty($account_transaction)) {
                $account_transaction->amount = $transaction_payment->amount;
                $account_transaction->account_id = $transaction_payment->account_id;
                $account_transaction->type = $type;
                $account_transaction->operation_date = $transaction_payment->paid_on;
                $account_transaction->save();
            } else {
                $account_transaction = self::createAccountTransaction([
                    'amount' => $transaction_payment->amount,
                    'account_id' => $transaction_payment->account_id,
                    'type' => $type,
                    'operation_date' => $transaction_payment->paid_on,
                    'created_by' => $transaction_payment->created_by,
                    'transaction_id' => $transaction_payment->transaction_id,
                    'transaction_payment_id' => $transaction_payment->id,
                ]);
            }

            // Guard against historical duplicates if they exist.
            if ($existing->count() > 1) {
                AccountTransaction::where('transaction_payment_id', $transaction_payment->id)
                    ->where('id', '!=', $account_transaction->id)
                    ->delete();
            }

            return $account_transaction;
        });
    }

    /**
     * Updates transaction payment from transaction payment
     *
     * @param  obj  $transaction_payment
     * @param  array  $inputs
     * @param  string  $transaction_type
     * @return string
     */
    public static function updateAccountTransaction($transaction_payment, $transaction_type)
    {
        return self::syncForPayment($transaction_payment, $transaction_type);
    }

    public function transfer_transaction()
    {
        return $this->belongsTo(\App\AccountTransaction::class, 'transfer_transaction_id');
    }

    public function account()
    {
        return $this->belongsTo(\App\Account::class, 'account_id');
    }
}
