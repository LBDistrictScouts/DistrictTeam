<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddIsTrusteeRoleToRoles extends BaseMigration
{
    /**
     * Mark roles that count towards a group's trustee board membership.
     *
     * @return void
     */
    public function change(): void
    {
        $this->table('roles')
            ->addColumn('is_trustee_role', 'boolean', [
                'default' => false,
                'null' => false,
            ])
            ->update();

        $this->execute(<<<'SQL'
UPDATE roles SET is_trustee_role = TRUE
WHERE template IN (
    'group-lead-volunteer',
    'trustee-board-chair',
    'group-treasurer',
    'trustee-board-member'
)
SQL);
    }
}
