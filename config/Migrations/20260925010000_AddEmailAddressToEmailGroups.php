<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddEmailAddressToEmailGroups extends BaseMigration
{
    /**
     * Add an address without invalidating email groups created before this field existed.
     *
     * New records require an address through the application validation rules.
     *
     * @return void
     */
    public function change(): void
    {
        $this->table('email_groups')
            ->addColumn('email_address', 'string', ['limit' => 255, 'null' => true])
            ->addIndex(['email_address'], ['unique' => true])
            ->update();
    }
}
