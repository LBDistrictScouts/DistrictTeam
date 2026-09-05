<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Enum\ContactMethodType;
use App\Service\MemberCsvImporter;
use InvalidArgumentException;
use Psr\Http\Message\UploadedFileInterface;

/**
 * Members Controller
 *
 * @property \App\Model\Table\MembersTable $Members
 */
class MembersController extends AppController
{
    /**
     * Upload a membership CSV export.
     *
     * @return void
     */
    public function upload(): void
    {
        $this->request->allowMethod(['get', 'post']);
        $session = $this->request->getSession();
        $importer = new MemberCsvImporter();
        if ($this->request->is('post')) {
            try {
                $file = $this->request->getData('csv');
                if (!$file instanceof UploadedFileInterface) {
                    throw new InvalidArgumentException('Please select a CSV file.');
                }
                $pending = ['token' => bin2hex(random_bytes(24)), 'rows' => $importer->read($file)];
                $session->write('MemberCsvUpload', $pending);

                $this->redirect(['action' => 'mapUnits']);

                return;
            } catch (InvalidArgumentException $exception) {
                $this->Flash->error(__('Nothing was imported. {0}', $exception->getMessage()));
            }
        }
    }

    /**
     * Save unit-to-group and section mappings before appointments are imported.
     *
     * @return \Cake\Http\Response|null|void
     */
    public function mapUnits()
    {
        $this->request->allowMethod(['get', 'post']);
        $pending = $this->pendingUpload();
        if (!$pending) {
            $this->Flash->error(__('This upload has expired. Please select the CSV again.'));

            return $this->redirect(['action' => 'upload']);
        }
        $importer = new MemberCsvImporter();
        if ($this->request->is('post')) {
            try {
                $this->assertUploadToken($pending);
                $unitMapping = $this->request->getData('unit_mapping', []);
                if (!is_array($unitMapping)) {
                    throw new InvalidArgumentException('Invalid unit mapping.');
                }
                $importer->saveUnitMappings($pending['rows'], $unitMapping);
                $this->Flash->success(__('Unit mappings saved.'));

                return $this->redirect(['action' => 'mapRoles']);
            } catch (InvalidArgumentException $exception) {
                $this->Flash->error(__('Unit mappings were not saved. {0}', $exception->getMessage()));
            }
        }
        $token = $pending['token'];
        $unitSources = $importer->unitSources($pending['rows']);
        $savedUnitMappings = $importer->savedUnitMappings($pending['rows']);
        $groupOptions = $this->fetchTable('Groups')->find('list')->orderBy(['group_name' => 'ASC'])->toArray();
        $sections = $this->sectionOptions();
        $sectionOptions = $sections['options'];
        $sectionGroups = $sections['groups'];
        $this->set(compact(
            'token',
            'unitSources',
            'savedUnitMappings',
            'groupOptions',
            'sectionOptions',
            'sectionGroups',
        ));
    }

    /**
     * Map appointment roles and import the selected member records.
     *
     * @return \Cake\Http\Response|null|void
     */
    public function mapRoles()
    {
        $this->request->allowMethod(['get', 'post']);
        $session = $this->request->getSession();
        $pending = $this->pendingUpload();
        if (!$pending) {
            $this->Flash->error(__('This upload has expired. Please select the CSV again.'));

            return $this->redirect(['action' => 'upload']);
        }
        $importer = new MemberCsvImporter();
        if ($this->request->is('post')) {
            try {
                $this->assertUploadToken($pending);
                $mapping = $this->request->getData('mapping', []);
                if (!is_array($mapping)) {
                    throw new InvalidArgumentException('Invalid role mapping.');
                }
                $selectedUnits = $this->request->getData('units', []);
                $rows = $this->selectedRows($pending['rows'], $selectedUnits);
                $roleResults = $importer->roleImportResults($rows, $mapping);
                $result = $importer->import($rows, $mapping);
                $session->delete('MemberCsvUpload');
                $this->set(compact('result', 'roleResults'));
                $this->Flash->success(__('CSV imported successfully.'));
            } catch (InvalidArgumentException $exception) {
                $this->Flash->error(__('Nothing was imported. {0}', $exception->getMessage()));
            }
        }
        if ($this->request->is('post') && isset($result)) {
            return;
        }
        $sources = $importer->sources($pending['rows']);
        $token = $pending['token'];
        $savedMappings = $importer->savedMappings($pending['rows']);
        $roleOptions = $importer->roleOptionsForSources($pending['rows']);
        $this->set(compact('sources', 'token', 'savedMappings', 'roleOptions'));
    }

    /** @return array<string, mixed>|null */
    private function pendingUpload(): ?array
    {
        $pending = $this->request->getSession()->read('MemberCsvUpload');

        return is_array($pending) && isset($pending['token'], $pending['rows']) ? $pending : null;
    }

    /** @param array<string, mixed> $pending */
    private function assertUploadToken(array $pending): void
    {
        $token = $this->request->getData('token');
        if (!is_string($token) || !is_string($pending['token']) || !hash_equals($pending['token'], $token)) {
            throw new InvalidArgumentException('This upload has expired. Please select the CSV again.');
        }
    }

    /**
     * @param array<int, array<string, string>> $rows
     * @param mixed $selectedUnits
     * @return array<int, array<string, string>>
     */
    private function selectedRows(array $rows, mixed $selectedUnits): array
    {
        $availableUnits = array_map(fn(array $row): string => 'unit:' . ($row['Unit name'] ?? ''), $rows);
        if (
            !is_array($selectedUnits) || !$selectedUnits
            || array_filter($selectedUnits, fn($unit): bool => !is_string($unit))
            || array_diff($selectedUnits, $availableUnits)
        ) {
            throw new InvalidArgumentException('Select at least one unit from this upload.');
        }

        return array_filter(
            $rows,
            fn(array $row): bool => in_array('unit:' . ($row['Unit name'] ?? ''), $selectedUnits, true),
        );
    }

    /** @return array{options: array<string, string>, groups: array<string, string>} */
    private function sectionOptions(): array
    {
        $sectionOptions = [];
        $sectionGroups = [];
        $sections = $this->fetchTable('Sections')->find()->contain(['Groups'])
            ->orderBy(['Groups.group_name' => 'ASC', 'Sections.section_name' => 'ASC']);
        foreach ($sections as $section) {
            $sectionOptions[$section->id] = $section->group->group_name . ' / ' . $section->section_name;
            $sectionGroups[$section->id] = $section->group_id;
        }

        return ['options' => $sectionOptions, 'groups' => $sectionGroups];
    }

    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $query = $this->Members->find();
        $members = $this->paginate($query);

        $this->set(compact('members'));
    }

    /**
     * View method
     *
     * @param string|null $id Member id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null)
    {
        $member = $this->Members->get($id, contain: [
            'Appointments' => ['Roles'],
            'MemberContactMethods',
        ]);
        $contactMethodTypes = [];
        foreach (ContactMethodType::cases() as $contactMethodType) {
            $contactMethodTypes[$contactMethodType->value] = $contactMethodType->label();
        }

        $this->set(compact('member', 'contactMethodTypes'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $member = $this->Members->newEmptyEntity();
        if ($this->request->is('post')) {
            $member = $this->Members->patchEntity($member, $this->request->getData());
            if ($this->Members->save($member)) {
                $this->Flash->success(__('The member has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The member could not be saved. Please, try again.'));
        }
        $this->set(compact('member'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Member id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null)
    {
        $member = $this->Members->get($id, contain: []);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $member = $this->Members->patchEntity($member, $this->request->getData());
            if ($this->Members->save($member)) {
                $this->Flash->success(__('The member has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The member could not be saved. Please, try again.'));
        }
        $this->set(compact('member'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Member id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $member = $this->Members->get($id);
        if ($this->Members->delete($member)) {
            $this->Flash->success(__('The member has been deleted.'));
        } else {
            $this->Flash->error(__('The member could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
