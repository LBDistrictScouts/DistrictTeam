<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddIsLeadToRoles extends BaseMigration
{
    /**
     * Add the lead-role marker and ensure a team has at most one lead role.
     *
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('roles');

        $table->addColumn('is_lead', 'boolean', [
            'default' => false,
            'null' => false,
        ]);
        $table->addIndex(['team_id'], [
            'name' => 'roles_one_lead_per_team',
            'unique' => true,
            'where' => '"is_lead" = TRUE',
        ]);

        $table->update();
    }
}
