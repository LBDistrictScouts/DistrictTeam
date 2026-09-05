<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddGroupTypeAndDomains extends BaseMigration
{
    /**
     * Existing rows remain unclassified until refreshed from core data.
     *
     * @return void
     */
    public function change(): void
    {
        $this->table('groups')
            ->addColumn('type', 'string', ['limit' => 16, 'null' => true])
            ->addColumn('domains', 'json', ['null' => true])
            ->addIndex(['type'])
            ->update();
    }
}
