<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddSortOrderToTeams extends BaseMigration
{
    /**
     * @return void
     */
    public function up(): void
    {
        $this->table('teams')
            ->addColumn('sort_order', 'integer', ['null' => false, 'default' => 0])
            ->update();
        $this->execute('UPDATE teams SET sort_order = COALESCE(tree_left, 0)');
    }

    /**
     * @return void
     */
    public function down(): void
    {
        $this->table('teams')->removeColumn('sort_order')->update();
    }
}
