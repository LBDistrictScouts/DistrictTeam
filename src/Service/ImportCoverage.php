<?php
declare(strict_types=1);

namespace App\Service;

use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\ORM\Query;
use Cake\ORM\Table;

/** Compares the current directory with the entities represented by an import file. */
class ImportCoverage
{
    use LocatorAwareTrait;

    /**
     * The comparison is intentionally against the current database state. This means it identifies
     * appointments or members added manually, or retained after they disappeared from the source CSV.
     *
     * @param string $importFileId Import file to compare.
     * @return array{members: iterable<\Cake\Datasource\EntityInterface>, appointments: iterable<\Cake\Datasource\EntityInterface>, roles: iterable<\Cake\Datasource\EntityInterface>}
     */
    public function missingFromImport(string $importFileId): array
    {
        $records = $this->fetchTable('ImportRecords');
        $memberIds = $records->find()->select(['member_id'])
            ->where(['import_file_id' => $importFileId, 'entity_type' => 'member'])
            ->where(['member_id IS NOT' => null]);
        $appointmentIds = $records->find()->select(['appointment_id'])
            ->where(['import_file_id' => $importFileId, 'entity_type' => 'appointment'])
            ->where(['appointment_id IS NOT' => null]);
        $roleIds = $records->find();
        $roleIds->select(['role_id' => $roleIds->newExpr("CAST(entity_data->>'role_id' AS uuid)")])
            ->where(['import_file_id' => $importFileId, 'entity_type' => 'appointment'])
            ->where($roleIds->newExpr("entity_data->>'role_id' IS NOT NULL"));

        return [
            'members' => $this->missing($this->fetchTable('Members'), $memberIds),
            'appointments' => $this->missing(
                $this->fetchTable('Appointments'),
                $appointmentIds,
                ['Roles', 'Members'],
            ),
            'roles' => $this->missing($this->fetchTable('Roles'), $roleIds, ['Teams']),
        ];
    }

    /**
     * @param \Cake\ORM\Table $table Current entity table.
     * @param \Cake\ORM\Query\SelectQuery $representedIds Entity IDs represented by the source.
     * @param list<string> $contain Associations needed by the report.
     * @return iterable<\Cake\Datasource\EntityInterface>
     */
    private function missing(Table $table, Query $representedIds, array $contain = []): iterable
    {
        $query = $table->find()->contain($contain)->orderBy([$table->getAlias() . '.id' => 'ASC']);
        $query->where([$table->getAlias() . '.id NOT IN' => $representedIds]);

        return $query->all();
    }
}
