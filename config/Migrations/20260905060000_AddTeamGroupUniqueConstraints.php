<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddTeamGroupUniqueConstraints extends BaseMigration
{
    /**
     * Make team names and slugs unique only within their group.
     *
     * @return void
     */
    public function change(): void
    {
        $this->table('teams')
            ->addIndex(['group_id', 'team_name'], [
                'name' => 'teams_group_team_name_unique',
                'unique' => true,
            ])
            ->addIndex(['group_id', 'slug'], [
                'name' => 'teams_group_slug_unique',
                'unique' => true,
            ])
            ->update();
    }
}
