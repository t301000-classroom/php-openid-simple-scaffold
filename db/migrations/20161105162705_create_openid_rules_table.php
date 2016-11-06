<?php

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

class CreateOpenidRulesTable extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-abstractmigration-class
     *
     * The following commands can be used in this method and Phinx will
     * automatically reverse them when rolling back:
     *
     *    createTable
     *    renameTable
     *    addColumn
     *    renameColumn
     *    addIndex
     *    addForeignKey
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change()
    {
        $table = $this->table('openid_rules');
        $table->addColumn('school_id', 'string')
            ->addColumn('rule', 'text')
            ->addColumn(
                'priority',
                'integer',
                array(  // 0 ~ 255
                    'limit' => MysqlAdapter::INT_TINY,
                    'default' => 1,
                    'signed' => false
                )
            )
            ->create();
    }
}