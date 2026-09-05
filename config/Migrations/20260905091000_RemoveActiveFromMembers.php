<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class RemoveActiveFromMembers extends BaseMigration
{
    /**
     * Membership activity is derived from join and leave dates.
     *
     * @return void
     */
    public function change(): void
    {
        $this->table('members')
            ->removeColumn('active')
            ->update();
    }
}
