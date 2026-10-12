<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class PreserveEmailGroupScopeIntegrity extends BaseMigration
{
    /**
     * Prevent parent scope changes from leaving email groups internally inconsistent.
     *
     * @return void
     */
    public function up(): void
    {
        $this->execute(
            'CREATE UNIQUE INDEX IF NOT EXISTS teams_id_section_id_unique ON teams (id, section_id)',
        );
        $this->execute(
            'UPDATE email_groups AS email_group SET section_id = team.section_id '
            . 'FROM teams AS team WHERE email_group.team_id = team.id '
            . 'AND email_group.section_id IS NOT NULL '
            . 'AND email_group.section_id IS DISTINCT FROM team.section_id',
        );
        $this->execute('ALTER TABLE email_groups DROP CONSTRAINT IF EXISTS email_groups_group_id_fkey');
        $this->execute('ALTER TABLE email_groups DROP CONSTRAINT IF EXISTS email_groups_team_id_group_id_fkey');
        $this->execute('ALTER TABLE email_groups DROP CONSTRAINT IF EXISTS email_groups_section_id_group_id_fkey');
        $this->execute('ALTER TABLE email_groups DROP CONSTRAINT IF EXISTS email_groups_team_id_section_id_fkey');
        $this->execute('ALTER TABLE email_groups DROP CONSTRAINT IF EXISTS email_groups_team_section_fkey');
        $this->execute(
            'ALTER TABLE email_groups ADD CONSTRAINT email_groups_group_id_fkey '
            . 'FOREIGN KEY (group_id) REFERENCES groups (id) ON UPDATE RESTRICT ON DELETE RESTRICT',
        );
        $this->execute(
            'ALTER TABLE email_groups ADD CONSTRAINT email_groups_team_id_group_id_fkey '
            . 'FOREIGN KEY (team_id, group_id) REFERENCES teams (id, group_id) '
            . 'ON UPDATE RESTRICT ON DELETE RESTRICT',
        );
        $this->execute(
            'ALTER TABLE email_groups ADD CONSTRAINT email_groups_section_id_group_id_fkey '
            . 'FOREIGN KEY (section_id, group_id) REFERENCES sections (id, group_id) '
            . 'ON UPDATE RESTRICT ON DELETE RESTRICT',
        );
        $this->execute(
            'ALTER TABLE email_groups ADD CONSTRAINT email_groups_team_section_fkey '
            . 'FOREIGN KEY (team_id, section_id) REFERENCES teams (id, section_id) '
            . 'ON UPDATE RESTRICT ON DELETE RESTRICT',
        );
    }

    /**
     * @return void
     */
    public function down(): void
    {
        $this->execute('ALTER TABLE email_groups DROP CONSTRAINT email_groups_team_section_fkey');
        $this->execute('ALTER TABLE email_groups DROP CONSTRAINT email_groups_section_id_group_id_fkey');
        $this->execute('ALTER TABLE email_groups DROP CONSTRAINT email_groups_team_id_group_id_fkey');
        $this->execute('ALTER TABLE email_groups DROP CONSTRAINT email_groups_group_id_fkey');
        $this->execute(
            'ALTER TABLE email_groups ADD CONSTRAINT email_groups_group_id_fkey '
            . 'FOREIGN KEY (group_id) REFERENCES groups (id) ON UPDATE CASCADE ON DELETE RESTRICT',
        );
        $this->execute(
            'ALTER TABLE email_groups ADD CONSTRAINT email_groups_team_id_group_id_fkey '
            . 'FOREIGN KEY (team_id, group_id) REFERENCES teams (id, group_id) '
            . 'ON UPDATE CASCADE ON DELETE RESTRICT',
        );
        $this->execute(
            'ALTER TABLE email_groups ADD CONSTRAINT email_groups_section_id_group_id_fkey '
            . 'FOREIGN KEY (section_id, group_id) REFERENCES sections (id, group_id) '
            . 'ON UPDATE CASCADE ON DELETE RESTRICT',
        );
    }
}
