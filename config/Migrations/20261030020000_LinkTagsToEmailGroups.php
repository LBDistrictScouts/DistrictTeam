<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class LinkTagsToEmailGroups extends BaseMigration
{
    /**
     * @return void
     */
    public function change(): void
    {
        $this->table('tags')
            ->addColumn('email_group_id', 'uuid', ['null' => true])
            ->addIndex(['email_group_id'], ['unique' => true])
            ->addForeignKey('email_group_id', 'email_groups', 'id', [
                'update' => 'CASCADE',
                'delete' => 'SET_NULL',
            ])
            ->update();
    }
}
