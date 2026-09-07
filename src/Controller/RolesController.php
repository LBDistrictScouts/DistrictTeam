<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\StandardGroupTemplateCreator;
use Cake\Http\Response;
use InvalidArgumentException;

/**
 * Roles Controller
 *
 * @property \App\Model\Table\RolesTable $Roles
 */
class RolesController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $query = $this->Roles->find()
            ->orderByAsc('Teams.tree_left')
            ->contain(['Teams']);
        $roles = $this->paginate($query);

        $this->set(compact('roles'));
    }

    /**
     * View method
     *
     * @param string|null $id Role id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null)
    {
        $role = $this->Roles->get($id, contain: [
            'Teams.Groups',
            'Teams.Sections',
            'CurrentAppointments.Members',
        ]);
        $this->set(compact('role'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $role = $this->Roles->newEmptyEntity();
        if ($this->request->is('post')) {
            $role = $this->Roles->patchEntity($role, $this->request->getData());
            if ($this->Roles->save($role)) {
                $this->Flash->success(__('The role has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The role could not be saved. Please, try again.'));
        }
        $teams = $this->Roles->Teams->find('treeList', limit: 200, spacer: '>> ')->toArray();
        $this->set(compact('role', 'teams'));
    }

    /**
     * Review and create standard roles for already-created standard teams.
     *
     * @return \Cake\Http\Response|null
     */
    public function createStandardGroupTemplate(): ?Response
    {
        $this->request->allowMethod(['get', 'post']);
        $creator = new StandardGroupTemplateCreator();
        $reviewOverrides = filter_var(
            $this->request->is('post') ? $this->request->getData('review_overrides', false) : $this->request->getQuery('review_overrides', false),
            FILTER_VALIDATE_BOOL,
        );
        $submittedRoles = [];
        if ($this->request->is('post')) {
            try {
                $submittedRoles = $this->request->getData('roles', []);
                if (!is_array($submittedRoles)) {
                    throw new InvalidArgumentException('Invalid template selection.');
                }
                $count = $creator->createRoles(array_values($submittedRoles), $reviewOverrides);
                $this->Flash->success($reviewOverrides ? __('{0} standard role names applied.', $count) : __('{0} standard roles created.', $count));

                return $this->redirect(['action' => 'index']);
            } catch (InvalidArgumentException $exception) {
                $this->Flash->error(__('The standard roles were not created. {0}', $exception->getMessage()));
            }
        }
        $roles = $creator->rolePlan($reviewOverrides);
        $this->set(compact('roles', 'submittedRoles', 'reviewOverrides'));

        return null;
    }

    /**
     * Edit method
     *
     * @param string|null $id Role id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null)
    {
        $role = $this->Roles->get($id, contain: []);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $role = $this->Roles->patchEntity($role, $this->request->getData());
            if ($this->Roles->save($role)) {
                $this->Flash->success(__('The role has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The role could not be saved. Please, try again.'));
        }
        $teams = $this->Roles->Teams->find('list', limit: 200)->all();
        $this->set(compact('role', 'teams'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Role id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $role = $this->Roles->get($id);
        if ($this->Roles->delete($role)) {
            $this->Flash->success(__('The role has been deleted.'));
        } else {
            $this->Flash->error(__('The role could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
