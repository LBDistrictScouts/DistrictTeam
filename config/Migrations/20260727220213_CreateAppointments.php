<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class CreateAppointments extends BaseMigration
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
        $table = $this->table('appointments', ['id' => false, 'primary_key' => 'id']);
        $table->addColumn('id', 'uuid', ['null' => false]);

        $table->addColumn('role_id', 'uuid', ['null' => false]);
        $table->addColumn('member_id', 'uuid', ['null' => false]);
        $table->addColumn('member_contact_method_id', 'uuid', ['null' => false]);

        $table->addForeignKey(
            'role_id',
            'roles',
            'id',
            [
                'delete' => 'CASCADE',
                'update' => 'CASCADE'
            ]
        );

        $table->addForeignKey(
            'member_id',
            'members',
            'id',
            [
                'delete' => 'CASCADE',
                'update' => 'CASCADE'
            ]
        );

        $table->addForeignKey(
            'member_contact_method_id',
            'member_contact_methods',
            'id',
            [
                'delete' => 'CASCADE',
                'update' => 'CASCADE'
            ]
        );

        $table->addColumn('effective_start_date', 'date', ['null' => false]);
        $table->addColumn('effective_end_date', 'date', ['null' => true]);
        $table->addColumn('active', 'boolean', ['null' => false, 'default' => true]);

        $table->addIndex(['role_id', 'member_id', 'effective_start_date'], ['unique' => true]);

        $table->create();
    }
}
