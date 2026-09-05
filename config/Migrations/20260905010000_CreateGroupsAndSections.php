<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class CreateGroupsAndSections extends BaseMigration
{
    /**
     * @return void
     */
    public function change(): void
    {
        $this->table('groups', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', ['null' => false])
            ->addColumn('group_name', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('group_osm_id', 'integer', ['null' => true])
            ->addColumn('sort_order', 'integer', ['null' => true])
            ->addIndex(['group_name'], ['unique' => true])
            ->create();

        $this->table('sections', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', ['null' => false])
            ->addColumn('group_id', 'uuid', ['null' => false])
            ->addColumn('section_osm_id', 'integer', ['null' => false])
            ->addColumn('section_name', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('section_type', 'string', ['limit' => 32, 'null' => false])
            ->addColumn('meeting_start_time', 'string', ['limit' => 5, 'null' => true])
            ->addColumn('meeting_end_time', 'string', ['limit' => 5, 'null' => true])
            ->addColumn('meeting_day', 'string', ['limit' => 9, 'null' => true])
            ->addIndex(['section_osm_id'], ['unique' => true])
            ->addIndex(['section_name'], ['unique' => true])
            ->addIndex(['id', 'group_id'], ['unique' => true])
            ->addForeignKey('group_id', 'groups', 'id', ['update' => 'CASCADE', 'delete' => 'RESTRICT'])
            ->create();

        $this->table('teams')
            ->addColumn('group_id', 'uuid', ['null' => true])
            ->addColumn('section_id', 'uuid', ['null' => true])
            ->addIndex(['group_id'])
            ->addIndex(['section_id', 'group_id'])
            ->addForeignKey('group_id', 'groups', 'id', ['update' => 'CASCADE', 'delete' => 'RESTRICT'])
            ->addForeignKey(['section_id', 'group_id'], 'sections', ['id', 'group_id'], [
                'update' => 'CASCADE', 'delete' => 'RESTRICT',
            ])
            ->update();
    }
}
