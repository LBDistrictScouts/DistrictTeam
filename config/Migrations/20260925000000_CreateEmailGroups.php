<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class CreateEmailGroups extends BaseMigration
{
    /**
     * @return void
     */
    public function change(): void
    {
        $this->table('email_groups', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', ['null' => false])
            ->addColumn('group_id', 'uuid', ['null' => false])
            ->addColumn('team_id', 'uuid', ['null' => true])
            ->addColumn('email_group_name', 'string', ['limit' => 255, 'null' => false])
            ->addIndex(['group_id'])
            ->addIndex(['team_id'])
            ->addIndex(['group_id', 'email_group_name'], ['unique' => true])
            ->addForeignKey('group_id', 'groups', 'id', ['update' => 'CASCADE', 'delete' => 'RESTRICT'])
            ->addForeignKey('team_id', 'teams', 'id', ['update' => 'CASCADE', 'delete' => 'RESTRICT'])
            ->create();

        $this->table('member_contact_methods')
            ->addColumn('email_group_id', 'uuid', ['null' => true])
            ->addIndex(['email_group_id'])
            ->addForeignKey('email_group_id', 'email_groups', 'id', [
                'update' => 'CASCADE',
                'delete' => 'SET_NULL',
            ])
            ->update();
    }
}
