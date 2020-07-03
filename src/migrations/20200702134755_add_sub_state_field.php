<?php

use Phinx\Migration\AbstractMigration;

class AddSubStateField extends AbstractMigration
{
    public function change()
    {
        $this->table('gopay_payments')
            ->addColumn('sub_state', 'string', ['null' => true, 'after' => 'state'])
            ->update();
    }
}
