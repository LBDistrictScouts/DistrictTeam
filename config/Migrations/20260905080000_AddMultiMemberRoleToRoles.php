<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddMultiMemberRoleToRoles extends BaseMigration
{
    /**
     * Mark roles that should remain open to further appointments.
     *
     * @return void
     */
    public function change(): void
    {
        $this->table('roles')
            ->addColumn('multi_member_role', 'boolean', [
                'default' => false,
                'null' => false,
            ])
            ->update();
    }
}
