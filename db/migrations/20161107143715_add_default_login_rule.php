<?php

use Phinx\Migration\AbstractMigration;

class AddDefaultLoginRule extends AbstractMigration
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
    /*public function change()
    {

    }*/

    public function up()
    {
        $rule = [
            'school_id' => '*',
            'rule' => json_encode([]),
            'priority' => 1
        ];
        $this->execute('TRUNCATE TABLE openid_rules');
        $table = $this->table('openid_rules');
        $table->insert($rule)
            ->saveData();
    }

    public function down()
    {
        $this->execute('TRUNCATE TABLE openid_rules');
    }
}
