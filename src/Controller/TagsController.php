<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Entity\Tag;
use App\Model\Table\AppointmentsTable;
use App\Model\Table\EmailGroupsTable;
use App\Model\Table\RolesTable;
use App\Model\Table\TeamsTable;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Cake\Validation\Validation;
use RuntimeException;

/**
 * @property \App\Model\Table\TagsTable $Tags
 */
class TagsController extends AppController
{
    /**
     * List tags.
     *
     * @return \Cake\Http\Response|null|void Renders the tag index.
     */
    public function index()
    {
        $tags = $this->paginate($this->Tags->find()->orderByAsc('Tags.tag_label'));
        $this->set(compact('tags'));
    }

    /**
     * Show a tag and its current matching appointments.
     *
     * @param string|null $id Tag UUID.
     * @return \Cake\Http\Response|null|void Renders the tag detail.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When the tag does not exist.
     */
    public function view(?string $id = null)
    {
        if (!is_string($id) || !Validation::uuid($id)) {
            throw new NotFoundException('Tag not found.');
        }
        $tag = $this->Tags->get($id, contain: [
            'Teams',
            'Roles.Teams',
            'Appointments.Members',
            'Appointments.Roles.Teams',
        ]);
        $groupEmailsOnlyQuery = $this->request->getQuery('group_emails_only', '1');
        $groupEmailsOnly = !is_string($groupEmailsOnlyQuery) || $groupEmailsOnlyQuery !== '0';
        $currentAppointments = $this->Tags->findCurrentAppointments(
            $id,
            groupEmailsOnly: $groupEmailsOnly,
        )->all()->toList();
        $this->set(compact('tag', 'currentAppointments', 'groupEmailsOnly'));
    }

    /**
     * Create a tag.
     *
     * @return \Cake\Http\Response|null|void Redirects after a successful save, otherwise renders the form.
     */
    public function add()
    {
        $tag = $this->Tags->newEmptyEntity();
        if ($this->request->is('post')) {
            $tag = $this->Tags->patchEntity($tag, $this->request->getData(), [
                'associated' => ['Teams', 'Roles', 'Appointments'],
            ]);
            if ($this->Tags->save($tag)) {
                $this->Flash->success(__('The tag has been saved.'));

                return $this->redirect(['action' => 'view', $tag->id]);
            }
            $this->Flash->error(__('The tag could not be saved. Please, check the label and code.'));
        }
        $this->setAssignmentOptions($tag);
        $this->set(compact('tag'));
    }

    /**
     * Edit a tag.
     *
     * @param string|null $id Tag UUID.
     * @return \Cake\Http\Response|null|void Redirects after a successful save, otherwise renders the form.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When the tag does not exist.
     */
    public function edit(?string $id = null)
    {
        if (!is_string($id) || !Validation::uuid($id)) {
            throw new NotFoundException('Tag not found.');
        }
        $tag = $this->Tags->get($id, contain: [
            'Teams',
            'Roles.Teams',
            'Appointments.Members',
            'Appointments.Roles',
        ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $tag = $this->Tags->patchEntity($tag, $this->request->getData(), [
                'associated' => ['Teams', 'Roles', 'Appointments'],
            ]);
            if ($this->Tags->save($tag)) {
                $this->Flash->success(__('The tag has been saved.'));

                return $this->redirect(['action' => 'view', $tag->id]);
            }
            $this->Flash->error(__('The tag could not be saved. Please, check the label and code.'));
        }
        $this->setAssignmentOptions($tag);
        $this->set(compact('tag'));
    }

    /**
     * Delete a tag.
     *
     * @param string|null $id Tag UUID.
     * @return \Cake\Http\Response|null Redirects to the tag index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When the tag does not exist.
     */
    public function delete(?string $id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $tag = $this->Tags->get($id);
        if ($this->Tags->delete($tag)) {
            $this->Flash->success(__('The tag and its assignments have been deleted.'));
        } else {
            $this->Flash->error(__('The tag could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Return matching teams or appointments for the assignment picker.
     *
     * @return \Cake\Http\Response
     */
    public function assignmentOptions(): Response
    {
        $this->request->allowMethod(['get']);
        $type = $this->request->getQuery('type');
        $queryTerm = $this->request->getQuery('q', '');
        $term = is_string($queryTerm) ? strtolower(trim($queryTerm)) : '';
        $results = [];

        if (is_string($type) && $term !== '') {
            $pattern = '%' . $term . '%';
            if ($type === 'team') {
                $query = $this->teamsTable()->find()
                    ->contain(['Groups'])
                    ->innerJoinWith('Groups')
                    ->where(['OR' => [
                        'LOWER(Teams.team_name) LIKE' => $pattern,
                        'LOWER(Groups.group_name) LIKE' => $pattern,
                    ]])
                    ->orderByAsc('Teams.team_name')
                    ->limit(20);
                foreach ($query as $team) {
                    $results[] = [
                        'id' => $team->id,
                        'text' => $team->group
                            ? $team->team_name . ' · ' . $team->group->group_name
                            : $team->team_name,
                    ];
                }
            } elseif ($type === 'appointment') {
                $query = $this->appointmentsTable()->find()
                    ->contain(['Members', 'Roles'])
                    ->innerJoinWith('Members')
                    ->innerJoinWith('Roles')
                    ->where(['OR' => [
                        'LOWER(Members.first_name) LIKE' => $pattern,
                        'LOWER(Members.last_name) LIKE' => $pattern,
                        'LOWER(Roles.name) LIKE' => $pattern,
                    ]])
                    ->orderByDesc('Appointments.effective_start_date')
                    ->limit(20);
                foreach ($query as $appointment) {
                    $results[] = [
                        'id' => $appointment->id,
                        'text' => $appointment->member->full_name . ' · ' . $appointment->role->name
                            . ' · ' . $appointment->effective_start_date->format('d M Y'),
                    ];
                }
            }
        }

        return $this->response
            ->withType('application/json')
            ->withStringBody((string)json_encode(['results' => $results]));
    }

    /**
     * Set selector data and visible assignment rows for the tag form.
     *
     * @param \App\Model\Entity\Tag $tag Tag being edited or created.
     * @return void
     */
    private function setAssignmentOptions(Tag $tag): void
    {
        $roles = $this->rolesTable()->find('list', keyField: 'id', valueField: 'name')
            ->orderByAsc('name')->toArray();
        $emailGroups = [];
        foreach (
            $this->emailGroupsTable()->find()
            ->select(['id', 'email_group_name', 'email_address'])
            ->orderByAsc('email_group_name') as $emailGroup
        ) {
            $emailGroups[(string)$emailGroup->id] = $emailGroup->email_group_name . ' · ' . $emailGroup->email_address;
        }
        $roleSelection = $this->appointmentsTable()->newEmptyEntity();
        $teamSelectorData = $this->teamSelectorData();
        $roleSelectorData = $this->roleSelectorData();
        $assignmentRows = $this->assignmentRows($tag);

        $this->set(compact(
            'roles',
            'emailGroups',
            'roleSelection',
            'teamSelectorData',
            'roleSelectorData',
            'assignmentRows',
        ));
    }

    /** @return list<array{type: string, id: string, text: string}> */
    private function assignmentRows(Tag $tag): array
    {
        $rows = [];
        $teamIds = array_map(static fn($team): string => (string)$team->id, $tag->teams ?? []);
        if ($teamIds !== []) {
            foreach (
                $this->teamsTable()->find()->contain(['Groups'])
                ->where(['Teams.id IN' => $teamIds])
                ->orderByAsc('Teams.team_name') as $team
            ) {
                $rows[] = [
                    'type' => 'team',
                    'id' => (string)$team->id,
                    'text' => $team->group
                        ? $team->team_name . ' · ' . $team->group->group_name
                        : $team->team_name,
                ];
            }
        }

        $roleIds = array_map(static fn($role): string => (string)$role->id, $tag->roles ?? []);
        if ($roleIds !== []) {
            foreach (
                $this->rolesTable()->find()->contain(['Teams'])
                ->where(['Roles.id IN' => $roleIds])->orderByAsc('Roles.name') as $role
            ) {
                $rows[] = [
                    'type' => 'role',
                    'id' => (string)$role->id,
                    'text' => $role->name . ' · ' . $role->team->team_name,
                ];
            }
        }

        $appointmentIds = array_map(
            static fn($appointment): string => (string)$appointment->id,
            $tag->appointments ?? [],
        );
        if ($appointmentIds !== []) {
            foreach (
                $this->appointmentsTable()->find()->contain(['Members', 'Roles'])
                ->where(['Appointments.id IN' => $appointmentIds])
                ->orderByDesc('Appointments.effective_start_date') as $appointment
            ) {
                $rows[] = [
                    'type' => 'appointment',
                    'id' => (string)$appointment->id,
                    'text' => $appointment->member->full_name . ' · ' . $appointment->role->name
                        . ' · ' . $appointment->effective_start_date->format('d M Y'),
                ];
            }
        }

        return $rows;
    }

    /**
     * Return the configured appointments table.
     *
     * @return \App\Model\Table\AppointmentsTable
     */
    private function appointmentsTable(): AppointmentsTable
    {
        $table = $this->fetchTable('Appointments');
        if (!$table instanceof AppointmentsTable) {
            throw new RuntimeException('Expected the Appointments table');
        }

        return $table;
    }

    /**
     * Return the configured email groups table.
     *
     * @return \App\Model\Table\EmailGroupsTable
     */
    private function emailGroupsTable(): EmailGroupsTable
    {
        $table = $this->fetchTable('EmailGroups');
        if (!$table instanceof EmailGroupsTable) {
            throw new RuntimeException('Expected the EmailGroups table');
        }

        return $table;
    }

    /**
     * Return the configured roles table.
     *
     * @return \App\Model\Table\RolesTable
     */
    private function rolesTable(): RolesTable
    {
        $table = $this->fetchTable('Roles');
        if (!$table instanceof RolesTable) {
            throw new RuntimeException('Expected the Roles table');
        }

        return $table;
    }

    /**
     * Return the configured teams table.
     *
     * @return \App\Model\Table\TeamsTable
     */
    private function teamsTable(): TeamsTable
    {
        $table = $this->fetchTable('Teams');
        if (!$table instanceof TeamsTable) {
            throw new RuntimeException('Expected the Teams table');
        }

        return $table;
    }
}
