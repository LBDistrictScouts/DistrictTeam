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
        // Ensure the referenced key exists even if the earlier teams index
        // migration was recorded without leaving the index in place.
        $this->execute(
            'CREATE UNIQUE INDEX IF NOT EXISTS teams_id_section_id_unique ON teams (id, section_id)',
        );

        $this->table('email_groups')
            ->addColumn('section_id', 'uuid', ['null' => true])
            ->addIndex(['section_id'])
            ->addForeignKey(['section_id', 'group_id'], 'sections', ['id', 'group_id'], [
                'update' => 'RESTRICT',
                'delete' => 'RESTRICT',
            ])
            ->addForeignKey(['team_id', 'section_id'], 'teams', ['id', 'section_id'], [
                'update' => 'RESTRICT',
                'delete' => 'RESTRICT',
            ])
            ->update();
    }
}
