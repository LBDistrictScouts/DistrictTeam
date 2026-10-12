<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class CreateImportAuditTables extends BaseMigration
{
    /**
     * @return void
     */
    public function change(): void
    {
        $this->table('import_files', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', ['null' => false])
            ->addColumn('filename', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('imported_at', 'datetime', ['null' => false])
            ->addColumn('source_record_count', 'integer', ['null' => false])
            ->addColumn('record_count', 'integer', ['null' => false])
            ->addColumn('member_count', 'integer', ['null' => false, 'default' => 0])
            ->addColumn('contact_count', 'integer', ['null' => false, 'default' => 0])
            ->addColumn('appointment_count', 'integer', ['null' => false, 'default' => 0])
            ->addColumn('warning_count', 'integer', ['null' => false, 'default' => 0])
            ->addIndex(['imported_at'])
            ->create();

        $this->table('import_records', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', ['null' => false])
            ->addColumn('import_file_id', 'uuid', ['null' => false])
            ->addColumn('source_line', 'integer', ['null' => false])
            ->addColumn('entity_type', 'string', ['limit' => 20, 'null' => false])
            ->addColumn('action', 'string', ['limit' => 20, 'null' => false])
            ->addColumn('member_id', 'uuid', ['null' => true])
            ->addColumn('appointment_id', 'uuid', ['null' => true])
            ->addColumn('source_data', 'json', ['null' => false])
            ->addColumn('entity_data', 'json', ['null' => false])
            ->addIndex(['import_file_id'])
            ->addIndex(['member_id'])
            ->addIndex(['appointment_id'])
            ->addForeignKey('import_file_id', 'import_files', 'id', ['update' => 'CASCADE', 'delete' => 'CASCADE'])
            ->addForeignKey('member_id', 'members', 'id', ['update' => 'CASCADE', 'delete' => 'SET_NULL'])
            ->addForeignKey('appointment_id', 'appointments', 'id', ['update' => 'CASCADE', 'delete' => 'SET_NULL'])
            ->create();
    }
}
