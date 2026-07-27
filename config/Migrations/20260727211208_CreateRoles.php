<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class CreateRoles extends BaseMigration
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
        $table = $this->table('roles', ['id' => false, 'primary_key' => 'id']);
        $table->addColumn('id', 'uuid', ['null' => false]);
        $table->addColumn('team_id', 'uuid', ['null' => false]);

        $table->addColumn('name', 'string', ['limit' => 255, 'null' => false]);
        $table->addColumn('slug', 'string', ['limit' => 255, 'null' => false]);
        $table->addColumn('description', 'string', ['limit' => 255, 'null' => true]);
        $table->addColumn('currently_filled', 'boolean', ['null' => false, 'default' => false]);

        $table->addForeignKey('team_id', 'teams', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE']);

        $table->create();

        $table = $this->table('teams');
        $table->addColumn('slug', 'string', ['null' => true]);
        $table->update();
    }
}
