<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class CreateTags extends BaseMigration
{
    /**
     * @return void
     */
    public function change(): void
    {
        $this->table('tags', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', ['null' => false])
            ->addColumn('tag_label', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('tag_code', 'string', ['limit' => 100, 'null' => false])
            ->addIndex(['tag_code'], ['unique' => true])
            ->create();

        foreach ([
            'team_tags' => ['team_id', 'teams'],
            'role_tags' => ['role_id', 'roles'],
            'appointment_tags' => ['appointment_id', 'appointments'],
        ] as $tableName => [$entityColumn, $entityTable]) {
            $this->table($tableName, ['id' => false, 'primary_key' => [$entityColumn, 'tag_id']])
                ->addColumn($entityColumn, 'uuid', ['null' => false])
                ->addColumn('tag_id', 'uuid', ['null' => false])
                ->addIndex(['tag_id'])
                ->addForeignKey($entityColumn, $entityTable, 'id', [
                    'update' => 'CASCADE',
                    'delete' => 'CASCADE',
                ])
                ->addForeignKey('tag_id', 'tags', 'id', [
                    'update' => 'CASCADE',
                    'delete' => 'CASCADE',
                ])
                ->create();
        }
    }
}
