<?php

namespace Modules\JerryUpdates\Listeners;

use App\AccountTransaction;
use App\Events\TransactionPaymentAdded;
use App\Events\TransactionPaymentDeleted;
use App\Events\TransactionPaymentUpdated;
use Modules\JerryUpdates\Utils\JerrySettings;

class JerryAccountHardeningListener
{
    public function onPaymentAdded(TransactionPaymentAdded $event): void
    {
        if (! $this->isEnabled() || $event->transactionPayment->method == 'advance') {
            return;
        }

        $transaction_type = ! empty($event->formInput['transaction_type']) ? $event->formInput['transaction_type'] : null;
        AccountTransaction::syncForPayment($event->transactionPayment, $transaction_type);
    }

    public function onPaymentUpdated(TransactionPaymentUpdated $event): void
    {
        if (! $this->isEnabled() || $event->transactionPayment->method == 'advance') {
            return;
        }

        AccountTransaction::syncForPayment($event->transactionPayment, $event->transactionType);
    }

    public function onPaymentDeleted(TransactionPaymentDeleted $event): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        AccountTransaction::where('transaction_payment_id', $event->transactionPayment->id)->delete();
    }

    private function isEnabled(): bool
    {
        return JerrySettings::get('jerry_account_hardening', '0') == '1';
    }
}
