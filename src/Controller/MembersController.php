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
        $pending = $session->read('MemberCsvUpload');
        if ($this->request->is('post')) {
            try {
                if ($this->request->getData('step') === 'import') {
                    if (
                        !$pending || !is_string($this->request->getData('token'))
                        || !hash_equals($pending['token'], $this->request->getData('token'))
                    ) {
                        throw new InvalidArgumentException('This upload has expired. Please select the CSV again.');
                    }
                    $mapping = $this->request->getData('mapping', []);
                    if (!is_array($mapping)) {
                        throw new InvalidArgumentException('Invalid role mapping.');
                    }
                    $selectedUnits = $this->request->getData('units', []);
                    $availableUnits = array_map(
                        fn(array $row): string => 'unit:' . ($row['Unit name'] ?? ''),
                        $pending['rows'],
                    );
                    if (
                        !is_array($selectedUnits) || !$selectedUnits
                        || array_filter($selectedUnits, fn($unit): bool => !is_string($unit))
                        || array_diff($selectedUnits, $availableUnits)
                    ) {
                        throw new InvalidArgumentException('Select at least one unit from this upload.');
                    }
                    $rows = array_filter(
                        $pending['rows'],
                        fn(array $row): bool => in_array('unit:' . ($row['Unit name'] ?? ''), $selectedUnits, true),
                    );
                    $result = $importer->import($rows, $mapping);
                    $session->delete('MemberCsvUpload');
                    $pending = null;
                    $this->set(compact('result'));
                    $this->Flash->success(__('CSV imported successfully.'));
                } else {
                    $file = $this->request->getData('csv');
                    if (!$file instanceof UploadedFileInterface) {
                        throw new InvalidArgumentException('Please select a CSV file.');
                    }
                    $pending = ['token' => bin2hex(random_bytes(24)), 'rows' => $importer->read($file)];
                    $session->write('MemberCsvUpload', $pending);
                }
            } catch (InvalidArgumentException $exception) {
                $this->Flash->error(__('Nothing was imported. {0}', $exception->getMessage()));
            }
        }
        if ($pending) {
            $sources = $importer->sources($pending['rows']);
            $token = $pending['token'];
            $savedMappings = $importer->savedMappings($pending['rows']);
            $roleOptions = [];
            $roles = $this->fetchTable('Roles')->find()->contain(['Teams'])
                ->orderBy(['Teams.team_name' => 'ASC', 'Roles.name' => 'ASC']);
            foreach ($roles as $role) {
                $roleOptions[$role->id] = $role->team->team_name . ' / ' . $role->name;
            }
            $this->set(compact('sources', 'token', 'roleOptions', 'savedMappings'));
        }
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
        $member = $this->Members->get($id, contain: ['MemberContactMethods']);
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
