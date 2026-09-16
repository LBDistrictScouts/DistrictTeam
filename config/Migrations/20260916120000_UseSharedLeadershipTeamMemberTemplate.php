<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class UseSharedLeadershipTeamMemberTemplate extends BaseMigration
{
    /**
     * Use one leadership-team-member template for Group and District roles.
     *
     * @return void
     */
    public function up(): void
    {
        $this->execute(
            "UPDATE roles SET template = 'leadership-team-member' WHERE template = 'group-leadership-team-member'",
        );
    }

    /**
     * Restore the former Group-specific template for Group roles.
     *
     * @return void
     */
    public function down(): void
    {
        $this->execute(<<<'SQL'
UPDATE roles SET template = 'group-leadership-team-member'
FROM groups
WHERE roles.group_id = groups.id
  AND groups.type = 'group'
  AND roles.template = 'leadership-team-member'
SQL);
    }
}
