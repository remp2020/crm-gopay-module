<?php

namespace Crm\GoPayModule\Repository;

use Crm\ApplicationModule\Repository;
use Nette\Database\Table\ActiveRow;

class GopayPaymentsRepository extends Repository
{
    protected $tableName = 'gopay_payments';

    final public function add(ActiveRow $payment, $transactionId, $transactionReference)
    {
        return $this->insert([
            'payment_id' => $payment->id,
            'transaction_id' => $transactionId,
            'transaction_reference' => $transactionReference,
        ]);
    }

    final public function updatePayment(ActiveRow $payment, GopayPaymentValues $values)
    {
        $meta = $this->findByPayment($payment);
        return $this->update($meta, $values->getValues());
    }

    final public function findByPayment(ActiveRow $payment)
    {
        return $this->getTable()->where(['payment_id' => $payment->id])->limit(1)->fetch();
    }
}
