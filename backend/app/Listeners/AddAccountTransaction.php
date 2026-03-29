<?php

namespace App\Listeners;

use App\AccountTransaction;
use App\Events\TransactionPaymentAdded;
use App\Utils\ModuleUtil;
use App\Utils\TransactionUtil;

class AddAccountTransaction
{
    protected $transactionUtil;

    protected $moduleUtil;

    /**
     * Constructor
     *
     * @param  TransactionUtil  $transactionUtil
     * @return void
     */
    public function __construct(TransactionUtil $transactionUtil, ModuleUtil $moduleUtil)
    {
        $this->transactionUtil = $transactionUtil;
        $this->moduleUtil = $moduleUtil;
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(TransactionPaymentAdded $event)
    {
        //echo "<pre>";print_r($event->transactionPayment->toArray());exit;
        if ($event->transactionPayment->method == 'advance') {
            $this->transactionUtil->updateContactBalance($event->transactionPayment->payment_for, $event->transactionPayment->amount, 'deduct');
        }

        if (! $this->moduleUtil->isModuleEnabled('account', $event->transactionPayment->business_id)) {
            return true;
        }

        if ($event->transactionPayment->method != 'advance') {
            $transaction_type = ! empty($event->formInput['transaction_type']) ? $event->formInput['transaction_type'] : null;
            AccountTransaction::syncForPayment($event->transactionPayment, $transaction_type);
        }
    }
}
