<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddNotImportedReasonsToImportRecords extends BaseMigration
{
    /** @return void */
    public function change(): void
    {
        $this->table('import_records')
            ->addColumn('reason', 'text', ['null' => true])
            ->update();
    }
}
