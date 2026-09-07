<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddIsCoveredUntilToRoles extends BaseMigration
{
    /**
     * Store the last day a vacant role has interim cover.
     *
     * @return void
     */
    public function change(): void
    {
        $this->table('roles')
            ->addColumn('is_covered_until', 'date', ['null' => true])
            ->update();
    }
}
