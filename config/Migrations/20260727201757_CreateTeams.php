<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class CreateTeams extends BaseMigration
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
        $table = $this->table('teams', ['id' => false, 'primary_key' => ['id']]);
        $table->addColumn('id', 'uuid', ['null' => false]);

        $table->addColumn('team_name', 'string', ['limit' => 255, 'null' => false]);
        $table->addColumn('team_parent_id', 'uuid', ['null' => true]);

        $table->addForeignKey(
            'team_parent_id',
            'teams',
            'id',
            ['delete' => 'RESTRICT', 'update' => 'CASCADE'],
        );

        $table->create();
    }
}
