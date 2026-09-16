<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddPublicOptOutToMembers extends BaseMigration
{
    /**
     * Store whether a member's name must be hidden on public sites.
     *
     * @return void
     */
    public function change(): void
    {
        $this->table('members')
            ->addColumn('public_opt_out', 'boolean', [
                'default' => false,
                'null' => false,
            ])
            ->update();
    }
}
