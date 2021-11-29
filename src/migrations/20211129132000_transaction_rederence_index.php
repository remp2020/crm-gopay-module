<?php

use Phinx\Migration\AbstractMigration;

class AddSubStateField extends AbstractMigration
{
    public function change()
    {
        $this->table('gopay_payments')
            ->addIndex('transaction_reference')
            ->update();
    }
}
