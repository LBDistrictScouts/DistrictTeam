<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class UseSharedTreasurerTemplate extends BaseMigration
{
    /**
     * Use one treasurer template for Group and District treasurer roles.
     *
     * @return void
     */
    public function up(): void
    {
        $this->execute(
            "UPDATE roles SET template = 'treasurer' WHERE template = 'group-treasurer'",
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
UPDATE roles SET template = 'group-treasurer'
FROM groups
WHERE roles.group_id = groups.id
  AND groups.type = 'group'
  AND roles.template = 'treasurer'
SQL);
    }
}
