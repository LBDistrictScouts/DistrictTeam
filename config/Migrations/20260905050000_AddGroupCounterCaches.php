<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddGroupCounterCaches extends BaseMigration
{
    /**
     * @return void
     */
    public function up(): void
    {
        $this->table('groups')
            ->addColumn('sections_count', 'integer', ['null' => false, 'default' => 0])
            ->addColumn('teams_count', 'integer', ['null' => false, 'default' => 0])
            ->addColumn('roles_count', 'integer', ['null' => false, 'default' => 0])
            ->update();

        $this->execute(
            'UPDATE groups SET '
            . 'sections_count = (SELECT COUNT(*) FROM sections WHERE sections.group_id = groups.id), '
            . 'teams_count = (SELECT COUNT(*) FROM teams WHERE teams.group_id = groups.id), '
            . 'roles_count = (SELECT COUNT(*) FROM roles '
            . 'INNER JOIN teams ON teams.id = roles.team_id WHERE teams.group_id = groups.id)',
        );
    }

    /**
     * @return void
     */
    public function down(): void
    {
        $this->table('groups')
            ->removeColumn('sections_count')
            ->removeColumn('teams_count')
            ->removeColumn('roles_count')
            ->update();
    }
}
