<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AlterMembers extends BaseMigration
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
        $table = $this->table('members');

        $table->changeColumn('join_date', 'date', ['null' => false]);
        $table->changeColumn('leave_date', 'date', ['null' => true]);

        $table->update();
    }
}
