<?php
declare(strict_types=1);

namespace App\Service;

use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\ORM\Query\SelectQuery;
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
     * @return array{
     *     members: array{
     *         count: int,
     *         items: \Cake\Datasource\ResultSetInterface<int, \Cake\Datasource\EntityInterface>,
     *         truncated: bool
     *     },
     *     appointments: array{
     *         count: int,
     *         items: \Cake\Datasource\ResultSetInterface<int, \Cake\Datasource\EntityInterface>,
     *         truncated: bool
     *     },
     *     roles: array{
     *         count: int,
     *         items: \Cake\Datasource\ResultSetInterface<int, \Cake\Datasource\EntityInterface>,
     *         truncated: bool
     *     }
     * }
     */
    public function missingFromImport(string $importFileId, int $limit = 50): array
    {
        $limit = min(max($limit, 1), 100);
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
            'members' => $this->missing($this->fetchTable('Members'), $memberIds, $limit),
            'appointments' => $this->missing(
                $this->fetchTable('Appointments'),
                $appointmentIds,
                $limit,
                ['Roles', 'Members'],
            ),
            'roles' => $this->missing($this->fetchTable('Roles'), $roleIds, $limit, ['Teams']),
        ];
    }

    /**
     * @param \Cake\ORM\Table $table Current entity table.
     * @param \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface|array<string, mixed>> $representedIds
     *     Entity IDs represented by the source.
     * @param int $limit Maximum records to return.
     * @param list<string> $contain Associations needed by the report.
     * @return array{
     *     count: int,
     *     items: \Cake\Datasource\ResultSetInterface<int, \Cake\Datasource\EntityInterface>,
     *     truncated: bool
     * }
     */
    private function missing(Table $table, SelectQuery $representedIds, int $limit, array $contain = []): array
    {
        $query = $table->find()->contain($contain)->orderBy([$table->getAlias() . '.id' => 'ASC']);
        $query->where([$table->getAlias() . '.id NOT IN' => $representedIds]);
        $count = (clone $query)->count();

        return [
            'count' => $count,
            'items' => $query->limit($limit)->all(),
            'truncated' => $count > $limit,
        ];
    }
}
