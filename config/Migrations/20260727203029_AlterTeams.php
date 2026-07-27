<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AlterTeams extends BaseMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/migrations/5/guides/writing-migrations/migration-methods.html#the-change-method
     *
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('teams');

        $table->addColumn('tree_left', 'integer', ['null' => true, 'signed' => true]);
        $table->addColumn('tree_right', 'integer', ['null' => true, 'signed' => true]);
        $table->addColumn('tree_level', 'integer', ['null' => true, 'signed' => true]);

        $table->addIndex(['tree_left']);

        $table->update();
    }
}
