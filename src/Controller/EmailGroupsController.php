<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Http\Response;

class EmailGroupsController extends AppController
{
    /**
     * Create an email group.
     *
     * @return \Cake\Http\Response|null|void
     */
    public function add()
    {
        $emailGroups = $this->fetchTable('EmailGroups');
        $emailGroup = $emailGroups->newEmptyEntity();
        if ($this->request->is('post')) {
            $emailGroup = $emailGroups->patchEntity($emailGroup, $this->request->getData());
            if ($emailGroups->save($emailGroup)) {
                $this->Flash->success(__('The email group has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The email group could not be saved. Please, try again.'));
        }

        $teamSelectorData = $this->teamSelectorData();
        $this->set(compact('emailGroup', 'teamSelectorData'));
    }

    /**
     * Edit an email group.
     *
     * @param string|null $id Email group id.
     * @return \Cake\Http\Response|null|void
     */
    public function edit(?string $id = null)
    {
        $emailGroups = $this->fetchTable('EmailGroups');
        $emailGroup = $emailGroups->get($id);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $emailGroup = $emailGroups->patchEntity($emailGroup, $this->request->getData());
            if ($emailGroups->save($emailGroup)) {
                $this->Flash->success(__('The email group has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The email group could not be saved. Please, try again.'));
        }
        $teamSelectorData = $this->teamSelectorData();
        $this->set(compact('emailGroup', 'teamSelectorData'));
    }

    /**
     * Delete an email group.
     *
     * @param string|null $id Email group id.
     * @return \Cake\Http\Response|null
     */
    public function delete(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post', 'delete']);
        $emailGroups = $this->fetchTable('EmailGroups');
        $emailGroup = $emailGroups->get($id);
        if ($emailGroups->delete($emailGroup)) {
            $this->Flash->success(__('The email group has been deleted.'));
        } else {
            $this->Flash->error(__('The email group could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }

    /** @return void */
    public function index(): void
    {
        $this->request->allowMethod(['get']);
        $query = $this->fetchTable('EmailGroups')->find()
            ->contain(['Groups', 'Teams', 'Sections'])
            ->orderByAsc('Groups.sort_order')
            ->orderByAsc('EmailGroups.email_group_name');
        $groups = $this->fetchTable('Groups')->find('list')
            ->orderByAsc('sort_order')->orderByAsc('group_name')->toArray();
        $filters = [
            'q' => $this->indexFilter('q'),
            'group_id' => $this->indexChoice('group_id', array_keys($groups)),
        ];
        if ($filters['q'] !== '') {
            $term = '%' . strtolower($filters['q']) . '%';
            $query->where(['OR' => [
                'LOWER(EmailGroups.email_group_name) LIKE' => $term,
                'LOWER(EmailGroups.email_address) LIKE' => $term,
                'LOWER(Groups.group_name) LIKE' => $term,
                'LOWER(Teams.team_name) LIKE' => $term,
                'LOWER(Sections.section_name) LIKE' => $term,
            ]]);
        }
        if ($filters['group_id'] !== '') {
            $query->where(['EmailGroups.group_id' => $filters['group_id']]);
        }

        $filterControls = [[
            'name' => 'group_id', 'label' => __('Group'), 'options' => $groups, 'empty' => __('All groups'),
        ]];
        $emailGroups = $this->paginate($query);
        $this->set(compact('emailGroups', 'filters', 'filterControls'));
    }
}
