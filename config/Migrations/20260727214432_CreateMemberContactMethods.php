<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class CreateMemberContactMethods extends BaseMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/migrations/5/guides/writing-migrations/migration-methods.html#the-change-method
     *
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('member_contact_methods', ['id' => false, 'primary_key' => ['id']]);
        $table->addColumn('id', 'uuid', ['null' => false]);

        $table->addColumn('member_id', 'uuid', ['null' => false]);
        $table->addForeignKey('member_id', 'members', 'id', [
            'delete' => 'CASCADE',
            'update' => 'CASCADE'
        ]);

        $table->addColumn('contact_method', 'string', ['limit' => 255, 'null' => false]);
        $table->addIndex(['contact_method', 'member_id'], ['unique' => true]);

        $table->addColumn('contact_method_type', 'integer', ['null' => false, 'default' => 1]);

        $table->create();
    }
}
