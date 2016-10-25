<?php

use Phinx\Migration\AbstractMigration;

class AddDefaultAdmin extends AbstractMigration
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
    // public function change()
    // {
    //
    // }

    public function up()
    {
        $admin = [
            'username' => 'admin',
            'real_name' => '管理員',
            'password' => password_hash('admin', PASSWORD_DEFAULT),
            'is_admin' => 1
        ];

        $table = $this->table('users');
        $table->insert($admin)
            ->saveData();
    }

    public function down()
    {
        $this->execute('DELETE FROM users');
    }
}
