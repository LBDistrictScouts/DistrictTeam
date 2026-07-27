<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class CreateMembers extends BaseMigration
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
        $table = $this->table('members', ['id' => false, 'primary_key' => ['id']]);
        $table->addColumn('id', 'uuid', ['null' => false]);

        $table->addColumn('first_name', 'string', ['limit' => 100]);
        $table->addColumn('last_name', 'string', ['limit' => 100]);

        $table->addColumn('membership_number', 'integer', ['null' => false]);

        $table->addColumn('join_date', 'datetime', ['null' => false]);
        $table->addColumn('leave_date', 'datetime', ['null' => true]);
        $table->addColumn('active', 'boolean', ['null' => false, 'default' => true]);

        $table->addIndex(['membership_number'], ['unique' => true]);

        $table->create();
    }
}
