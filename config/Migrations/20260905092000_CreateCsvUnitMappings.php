<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class CreateCsvUnitMappings extends BaseMigration
{
    /**
     * @return void
     */
    public function change(): void
    {
        $this->table('csv_unit_mappings', ['id' => false, 'primary_key' => ['source_key']])
            ->addColumn('source_key', 'string', ['limit' => 64, 'null' => false])
            ->addColumn('source_unit', 'text', ['null' => false])
            ->addColumn('source_parent_unit', 'text', ['null' => false])
            ->addColumn('group_id', 'uuid', ['null' => true])
            ->addColumn('section_id', 'uuid', ['null' => true])
            ->addForeignKey('group_id', 'groups', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addForeignKey('section_id', 'sections', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->create();
    }
}
