<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddTemplateToTeamsAndRoles extends BaseMigration
{
    /**
     * @return void
     */
    public function up(): void
    {
        $this->table('teams')
            ->addColumn('template', 'string', ['limit' => 100, 'null' => true])
            ->update();
        $this->table('roles')
            ->addColumn('template', 'string', ['limit' => 100, 'null' => true])
            ->update();
        $this->execute(
            "UPDATE teams SET template = CASE\n"
            . "WHEN team_name = groups.group_name || ' Leadership Team' THEN 'leadership-team'\n"
            . "WHEN team_name = 'Beaver Section' THEN 'beaver-section'\n"
            . "WHEN team_name = 'Cub Section' THEN 'cub-section'\n"
            . "WHEN team_name = 'Scout Section' THEN 'scout-section'\n"
            . "WHEN team_name = 'Trustee Board' THEN 'trustee-board' END\n"
            . "FROM groups WHERE teams.group_id = groups.id AND teams.template IS NULL",
        );
        $this->execute(
            "UPDATE roles SET template = CASE\n"
            . "WHEN name = 'Group Lead Volunteer' THEN 'group-lead-volunteer'\n"
            . "WHEN name = 'Group Leadership Team Member' THEN 'group-leadership-team-member'\n"
            . "WHEN name = 'Beaver Section Team Leader' THEN 'beaver-section-team-leader'\n"
            . "WHEN name = 'Beaver Section Team Member' THEN 'beaver-section-team-member'\n"
            . "WHEN name = 'Cub Section Team Leader' THEN 'cub-section-team-leader'\n"
            . "WHEN name = 'Cub Section Team Member' THEN 'cub-section-team-member'\n"
            . "WHEN name = 'Scout Section Team Leader' THEN 'scout-section-team-leader'\n"
            . "WHEN name = 'Scout Section Team Member' THEN 'scout-section-team-member'\n"
            . "WHEN name = 'Trustee Board Chair' THEN 'trustee-board-chair'\n"
            . "WHEN name = 'Group Treasurer' THEN 'group-treasurer'\n"
            . "WHEN name = 'Trustee Board Member' THEN 'trustee-board-member' END\n"
            . "FROM teams WHERE roles.team_id = teams.id AND teams.template IS NOT NULL AND roles.template IS NULL",
        );
        $this->table('teams')
            ->addIndex(['group_id', 'template'], ['name' => 'teams_group_template_unique', 'unique' => true])
            ->update();
        $this->table('roles')
            ->addIndex(['group_id', 'template'], ['name' => 'roles_group_template_unique', 'unique' => true])
            ->update();
    }

    /**
     * @return void
     */
    public function down(): void
    {
        $this->table('roles')->removeIndexByName('roles_group_template_unique')->removeColumn('template')->update();
        $this->table('teams')->removeIndexByName('teams_group_template_unique')->removeColumn('template')->update();
    }
}
