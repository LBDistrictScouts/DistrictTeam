<?php
declare(strict_types=1);

namespace App\Service;

use Cake\ORM\Locator\LocatorAwareTrait;
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
        $records = $this->fetchTable('ImportRecords')->find()
            ->select(['entity_type', 'member_id', 'appointment_id', 'entity_data'])
            ->where(['import_file_id' => $importFileId]);
        $memberIds = [];
        $appointmentIds = [];
        $roleIds = [];
        foreach ($records as $record) {
            if ($record->get('entity_type') === 'member' && is_string($record->get('member_id'))) {
                $memberIds[] = $record->get('member_id');
            }
            if ($record->get('entity_type') === 'appointment') {
                if (is_string($record->get('appointment_id'))) {
                    $appointmentIds[] = $record->get('appointment_id');
                }
                $entityData = $record->get('entity_data');
                $roleId = is_array($entityData) ? $entityData['role_id'] ?? null : null;
                if (is_string($roleId)) {
                    $roleIds[] = $roleId;
                }
            }
        }

        return [
            'members' => $this->missing($this->fetchTable('Members'), array_values(array_unique($memberIds))),
            'appointments' => $this->missing(
                $this->fetchTable('Appointments'),
                array_values(array_unique($appointmentIds)),
                ['Roles', 'Members'],
            ),
            'roles' => $this->missing($this->fetchTable('Roles'), array_values(array_unique($roleIds)), ['Teams']),
        ];
    }

    /**
     * @param \Cake\ORM\Table $table Current entity table.
     * @param list<string> $representedIds Entity IDs represented by the source.
     * @param list<string> $contain Associations needed by the report.
     * @return iterable<\Cake\Datasource\EntityInterface>
     */
    private function missing(Table $table, array $representedIds, array $contain = []): iterable
    {
        $query = $table->find()->contain($contain)->orderBy([$table->getAlias() . '.id' => 'ASC']);
        if ($representedIds) {
            $query->where([$table->getAlias() . '.id NOT IN' => $representedIds]);
        }

        return $query->all();
    }
}
