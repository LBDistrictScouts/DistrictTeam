<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddSectionToEmailGroups extends BaseMigration
{
    /**
     * @return void
     */
    public function change(): void
    {
        $this->table('email_groups')
            ->addColumn('section_id', 'uuid', ['null' => true])
            ->addIndex(['section_id'])
            ->addForeignKey(['section_id', 'group_id'], 'sections', ['id', 'group_id'], [
                'update' => 'CASCADE',
                'delete' => 'RESTRICT',
            ])
            ->update();
    }
}
