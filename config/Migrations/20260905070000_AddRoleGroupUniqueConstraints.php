<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddRoleGroupUniqueConstraints extends BaseMigration
{
    /**
     * Scope role names and slugs to their owning group.
     *
     * @return void
     */
    public function up(): void
    {
        $this->table('roles')
            ->addColumn('group_id', 'uuid', ['null' => true])
            ->update();

        $this->execute('UPDATE roles SET group_id = teams.group_id FROM teams WHERE roles.team_id = teams.id');

        $this->table('teams')
            ->addIndex(['id', 'group_id'], ['name' => 'teams_id_group_unique', 'unique' => true])
            ->update();
        $this->table('roles')
            ->changeColumn('group_id', 'uuid', ['null' => false])
            ->addIndex(['group_id', 'name'], ['name' => 'roles_group_name_unique', 'unique' => true])
            ->addIndex(['group_id', 'slug'], ['name' => 'roles_group_slug_unique', 'unique' => true])
            ->addForeignKey('group_id', 'groups', 'id', ['update' => 'CASCADE', 'delete' => 'RESTRICT'])
            ->addForeignKey(['team_id', 'group_id'], 'teams', ['id', 'group_id'], [
                'update' => 'CASCADE',
                'delete' => 'CASCADE',
            ])
            ->update();
    }

    /**
     * @return void
     */
    public function down(): void
    {
        $this->table('roles')
            ->dropForeignKey('group_id')
            ->dropForeignKey(['team_id', 'group_id'])
            ->removeIndexByName('roles_group_name_unique')
            ->removeIndexByName('roles_group_slug_unique')
            ->removeColumn('group_id')
            ->update();
        $this->table('teams')->removeIndexByName('teams_id_group_unique')->update();
    }
}
