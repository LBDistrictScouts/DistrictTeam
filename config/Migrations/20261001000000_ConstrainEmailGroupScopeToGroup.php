<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class ConstrainEmailGroupScopeToGroup extends BaseMigration
{
    /**
     * Replace single-column scope foreign keys on existing installations.
     *
     * @return void
     */
    public function up(): void
    {
        // Existing installations have the single-column names, while fresh
        // installs already receive the composite constraints from the updated
        // create migrations. Drop either form before creating the final form.
        $this->execute('ALTER TABLE email_groups DROP CONSTRAINT IF EXISTS email_groups_team_id_fkey');
        $this->execute('ALTER TABLE email_groups DROP CONSTRAINT IF EXISTS email_groups_section_id_fkey');
        $this->execute('ALTER TABLE email_groups DROP CONSTRAINT IF EXISTS email_groups_team_id_group_id_fkey');
        $this->execute('ALTER TABLE email_groups DROP CONSTRAINT IF EXISTS email_groups_section_id_group_id_fkey');
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
    }

    /**
     * @return void
     */
    public function down(): void
    {
        $this->execute('ALTER TABLE email_groups DROP CONSTRAINT email_groups_team_id_group_id_fkey');
        $this->execute('ALTER TABLE email_groups DROP CONSTRAINT email_groups_section_id_group_id_fkey');
        $this->execute(
            'ALTER TABLE email_groups ADD CONSTRAINT email_groups_team_id_fkey '
            . 'FOREIGN KEY (team_id) REFERENCES teams (id) ON UPDATE CASCADE ON DELETE RESTRICT',
        );
        $this->execute(
            'ALTER TABLE email_groups ADD CONSTRAINT email_groups_section_id_fkey '
            . 'FOREIGN KEY (section_id) REFERENCES sections (id) ON UPDATE CASCADE ON DELETE RESTRICT',
        );
    }
}
