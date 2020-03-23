<?php

use Phinx\Migration\AbstractMigration;

class CreateGopayPaymentsTable extends AbstractMigration
{
    public function change()
    {
        $this->table('gopay_payments')
            ->addColumn('payment_id', 'integer', ['null' => false])
            ->addColumn('card_brand', 'string', ['null' => true])
            ->addColumn('card_expiration', 'string', ['null' => true])
            ->addColumn('card_number', 'string', ['null' => true])
            ->addColumn('contact_country_code', 'string', ['null' => true])
            ->addColumn('contact_email', 'string', ['null' => true])
            ->addColumn('eet_bkp', 'text', ['null' => true])
            ->addColumn('eet_fik', 'text', ['null' => true])
            ->addColumn('eet_pkp', 'text', ['null' => true])
            ->addColumn('issuer_bank', 'string', ['null' => true])
            ->addColumn('issuer_country', 'string', ['null' => true])
            ->addColumn('payment_instrument', 'string', ['null' => true])
            ->addColumn('recurrence_cycle', 'string', ['null' => true])
            ->addColumn('recurrence_date_to', 'string', ['null' => true])
            ->addColumn('recurrence_state', 'string', ['null' => true])
            ->addColumn('state', 'string', ['null' => true])
            ->addColumn('transaction_id', 'string', ['null' => true])
            ->addColumn('transaction_reference', 'string', ['null' => true])
            ->addColumn('url', 'text', ['null' => true])
            ->addIndex(['payment_id'], ['unique' => true])
            ->addForeignKey('payment_id', 'payments')
            ->create();
    }
}
