<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\ImportCoverage;

/** Browse CSV import history and identify records absent from a source file. */
class ImportsController extends AppController
{
    /** @return \Cake\Http\Response|null|void */
    public function index()
    {
        $imports = $this->paginate($this->fetchTable('ImportFiles')->find()->orderByDesc('imported_at'));
        $this->set(compact('imports'));
    }

    /**
     * @param string|null $id Import file ID.
     * @return \Cake\Http\Response|null|void
     */
    public function view(?string $id = null)
    {
        $import = $this->fetchTable('ImportFiles')->get($id);
        $search = $this->indexFilter('q');
        $recordsQuery = $this->fetchTable('ImportRecords')->find()
            ->where(['import_file_id' => $import->get('id')])
            ->orderBy(['source_line' => 'ASC', 'entity_type' => 'ASC']);
        if ($search !== '') {
            $recordsQuery->where(
                'ImportRecords.entity_type ILIKE :search OR ImportRecords.action ILIKE :search'
                . ' OR CAST(ImportRecords.source_data AS TEXT) ILIKE :search'
                . ' OR CAST(ImportRecords.entity_data AS TEXT) ILIKE :search',
            )->bind(':search', '%' . $search . '%', 'string');
        }
        $records = $this->paginate($recordsQuery);
        $this->attachAuditAssociations($records);
        $missing = (new ImportCoverage())->missingFromImport((string)$import->get('id'));
        $this->set(compact('import', 'missing', 'records', 'search'));
    }

    /**
     * Load associations after pagination so records without an appointment remain in the result set.
     *
     * @param iterable<\Cake\Datasource\EntityInterface> $records Paginated audit records.
     * @return void
     */
    private function attachAuditAssociations(iterable $records): void
    {
        $memberIds = [];
        $appointmentIds = [];
        foreach ($records as $record) {
            if (is_string($record->get('member_id'))) {
                $memberIds[] = $record->get('member_id');
            }
            if (is_string($record->get('appointment_id'))) {
                $appointmentIds[] = $record->get('appointment_id');
            }
        }
        $members = [];
        if ($memberIds) {
            $membersTable = $this->fetchTable('Members');
            foreach (
                $membersTable->find()->where([
                $membersTable->aliasField('id') . ' IN' => array_unique($memberIds),
                ]) as $member
            ) {
                $members[$member->get('id')] = $member;
            }
        }
        $appointments = [];
        if ($appointmentIds) {
            $appointmentsTable = $this->fetchTable('Appointments');
            foreach (
                $appointmentsTable->find()->contain(['Members', 'Roles'])->where([
                $appointmentsTable->aliasField('id') . ' IN' => array_unique($appointmentIds),
                ]) as $appointment
            ) {
                $appointments[$appointment->get('id')] = $appointment;
            }
        }
        foreach ($records as $record) {
            $record->set('member', $members[$record->get('member_id')] ?? null);
            $record->set('appointment', $appointments[$record->get('appointment_id')] ?? null);
        }
    }
}
