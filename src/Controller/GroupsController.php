<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Enum\GroupType;

class GroupsController extends AppController
{
    /**
     * List imported groups.
     *
     * @return void
     */
    public function index(): void
    {
        $this->request->allowMethod(['get']);
        $query = $this->fetchTable('Groups')->find();
        $filters = [
            'q' => $this->indexFilter('q'),
            'type' => $this->indexChoice('type', ['district', 'group']),
        ];
        if ($filters['q'] !== '') {
            $term = '%' . $filters['q'] . '%';
            $conditions = ['Groups.group_name LIKE' => $term];
            if (ctype_digit($filters['q'])) {
                $conditions['Groups.group_osm_id'] = (int)$filters['q'];
            }
            $query->where(['OR' => $conditions]);
        }
        if ($filters['type'] !== '') {
            $query->where(['Groups.type' => $filters['type']]);
        }
        $groupTypeOptions = [];
        foreach (GroupType::cases() as $type) {
            $groupTypeOptions[$type->value] = $type->label();
        }
        $filterControls = [[
            'name' => 'type', 'label' => __('Type'), 'options' => $groupTypeOptions, 'empty' => __('All types'),
        ]];
        $this->set('groups', $this->paginate($query, [
            'order' => ['Groups.sort_order' => 'ASC', 'Groups.group_name' => 'ASC'],
        ]));
        $this->set(compact('filters', 'filterControls'));
    }

    /**
     * Show a group together with its sections and teams.
     *
     * @param string|null $id Group id.
     * @return void
     */
    public function view(?string $id = null): void
    {
        $this->request->allowMethod(['get']);
        $groups = $this->fetchTable('Groups');
        $group = $groups->get($id);
        $group->sections = $groups->Sections->find()
            ->where(['Sections.group_id' => $group->id])
            ->orderByAsc('Sections.section_name')
            ->all()
            ->toList();
        $group->teams = $groups->Teams->find()
            ->where(['Teams.group_id' => $group->id])
            ->contain(['Sections', 'Roles'])
            ->orderByAsc('Teams.tree_left')
            ->orderByAsc('Teams.team_name')
            ->all()
            ->toList();
        $this->set(compact('group'));
    }
}
