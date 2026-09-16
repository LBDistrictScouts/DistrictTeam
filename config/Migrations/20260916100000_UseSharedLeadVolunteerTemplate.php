<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class UseSharedLeadVolunteerTemplate extends BaseMigration
{
    /**
     * Use one lead-volunteer template for Group and District lead roles.
     *
     * @return void
     */
    public function up(): void
    {
        $this->execute(
            "UPDATE roles SET template = 'lead-volunteer' WHERE template = 'group-lead-volunteer'",
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
UPDATE roles SET template = 'group-lead-volunteer'
FROM groups
WHERE roles.group_id = groups.id
  AND groups.type = 'group'
  AND roles.template = 'lead-volunteer'
SQL);
    }
}
