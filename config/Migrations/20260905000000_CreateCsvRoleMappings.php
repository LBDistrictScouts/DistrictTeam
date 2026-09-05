<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class CreateCsvRoleMappings extends BaseMigration
{
    /**
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('csv_role_mappings', ['id' => false, 'primary_key' => ['source_key']]);
        $table->addColumn('source_key', 'string', ['limit' => 64, 'null' => false]);
        foreach (['source_unit', 'source_parent', 'source_team', 'source_role', 'source_type'] as $column) {
            $table->addColumn($column, 'text', ['null' => false]);
        }
        // A null destination records an explicit decision to skip appointments.
        $table->addColumn('role_id', 'uuid', ['null' => true]);
        $table->addForeignKey('role_id', 'roles', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE']);
        $table->create();
    }
}
