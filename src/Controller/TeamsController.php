<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Entity\Team;
use App\Service\StandardGroupTemplateCreator;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Cake\Validation\Validation;
use InvalidArgumentException;
use RuntimeException;

/**
 * Teams Controller
 *
 * @property \App\Model\Table\TeamsTable $Teams
 */
class TeamsController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $query = $this->Teams->find()
            ->orderByAsc('Teams.tree_left')
            ->contain(['ParentTeam']);
        $teams = $this->paginate($query);

        $this->set(compact('teams'));
    }

    /**
     * Reorder the complete hierarchy or a selected parent’s descendants.
     *
     * @param string|null $parentId Parent team UUID.
     * @return \Cake\Http\Response|null
     */
    public function reorder(?string $parentId = null): ?Response
    {
        $this->request->allowMethod(['get', 'post']);
        if ($parentId !== null && !Validation::uuid($parentId)) {
            throw new NotFoundException('Team not found.');
        }
        $parentTeam = $parentId === null ? null : $this->Teams->get($parentId);
        $returnUrl = $parentId === null ? ['action' => 'index'] : ['action' => 'view', $parentId];
        if ($this->request->is('post')) {
            $ids = $this->request->getData('order', []);
            try {
                if (!is_array($ids)) {
                    throw new InvalidArgumentException('Invalid team order. Reload the page and try again.');
                }
                $this->Teams->saveOrder($ids, $parentId);
                $this->Flash->success(__('Team order saved.'));

                return $this->redirect($returnUrl);
            } catch (InvalidArgumentException $exception) {
                $this->Flash->error($exception->getMessage());
            }
        }
        $teams = $this->Teams->reorderQuery($parentId)->find('threaded', parentField: 'team_parent_id')
            ->orderByAsc('Teams.tree_left')->all();
        $this->set(compact('teams', 'parentTeam', 'returnUrl'));

        return null;
    }

    /**
     * View method
     *
     * @param string|null $id Team id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null)
    {
        $team = $this->Teams->get($id, contain: [
            'Groups',
            'Sections',
            'ParentTeam',
            'TeamLead.CurrentAppointments.Members',
            'Roles' => ['sort' => ['Roles.is_lead' => 'DESC', 'Roles.name' => 'ASC']],
            'Roles.CurrentAppointments.Members',
            'SubTeams.TeamLead.CurrentAppointments.Members',
        ]);
        $this->set(compact('team'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $team = $this->Teams->newEmptyEntity();
        if ($this->request->is('post')) {
            $team = $this->Teams->patchEntity($team, $this->request->getData());
            if ($this->Teams->save($team)) {
                $this->Flash->success(__('The team has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The team could not be saved. Please, try again.'));
        }
        $this->setGroupSectionOptions($team);
        $this->set(compact('team'));
    }

    /**
     * Preview and create the standard teams and roles for every Scout Group.
     *
     * @return \Cake\Http\Response|null|void
     */
    public function createStandardGroupTemplate()
    {
        $this->request->allowMethod(['get', 'post']);
        $creator = new StandardGroupTemplateCreator();
        $reviewOverrides = filter_var(
            $this->request->is('post') ? $this->request->getData('review_overrides', false) : $this->request->getQuery('review_overrides', false),
            FILTER_VALIDATE_BOOL,
        );
        $submittedTeams = [];
        if ($this->request->is('post')) {
            try {
                $submittedTeams = $this->request->getData('teams', []);
                if (!is_array($submittedTeams)) {
                    throw new InvalidArgumentException('Invalid template selection.');
                }
                $created = $creator->createTeams(array_values($submittedTeams), $reviewOverrides);
                $this->Flash->success($reviewOverrides ? __('{0} standard team names applied.', $created) : __('{0} standard teams created.', $created));

                return $this->redirect(['action' => 'index']);
            } catch (InvalidArgumentException | RuntimeException $exception) {
                $this->Flash->error(__('The standard template was not created. {0}', $exception->getMessage()));
            }
        }
        $teams = $creator->teamPlan($reviewOverrides);
        $this->set(compact('teams', 'submittedTeams', 'reviewOverrides'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Team id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null)
    {
        $team = $this->Teams->get($id, contain: []);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $team = $this->Teams->patchEntity($team, $this->request->getData());
            if ($this->Teams->save($team)) {
                $this->Flash->success(__('The team has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The team could not be saved. Please, try again.'));
        }
        $this->setGroupSectionOptions($team);
        $this->set(compact('team'));
    }

    /**
     * Offer section and parent options with group/section metadata for filtering.
     *
     * @param \App\Model\Entity\Team $team Team being edited.
     * @return void
     */
    private function setGroupSectionOptions(Team $team): void
    {
        $groups = $this->Teams->Groups->find('list')->orderByAsc('sort_order')->orderByAsc('group_name')->toArray();
        $sections = [];
        foreach ($this->Teams->Sections->find()->contain(['Groups'])->orderByAsc('section_name') as $section) {
            $sections[$section->group->group_name][$section->id] = $section->section_name;
        }
        $parents = $this->Teams->find()->orderByAsc('Teams.tree_left');
        if (!$team->isNew()) {
            $parents->where(['NOT' => [
                'Teams.tree_left >=' => $team->tree_left,
                'Teams.tree_right <=' => $team->tree_right,
            ]]);
        }
        $parentTeam = [];
        foreach ($parents as $parent) {
            $parentTeam[] = [
                'value' => $parent->id,
                'text' => str_repeat('>> ', (int)$parent->tree_level) . $parent->team_name,
                'data-group-id' => $parent->group_id ?? '',
                'data-section-id' => $parent->section_id ?? '',
            ];
        }
        $this->set(compact('groups', 'sections', 'parentTeam'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Team id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $team = $this->Teams->get($id);
        if ($this->Teams->delete($team)) {
            $this->Flash->success(__('The team has been deleted.'));
        } else {
            $this->Flash->error(__('The team could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
