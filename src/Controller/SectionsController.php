<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Enum\SectionType;

class SectionsController extends AppController
{
    /**
     * List imported sections.
     *
     * @return void
     */
    public function index(): void
    {
        $this->request->allowMethod(['get']);
        $query = $this->fetchTable('Sections')->find()->contain(['Groups'])->orderByAsc('Groups.sort_order')
            ->orderByAsc('Sections.section_name');
        $groups = $this->fetchTable('Groups')->find('list')->orderByAsc('sort_order')
            ->orderByAsc('group_name')->toArray();
        $filters = [
            'q' => $this->indexFilter('q'),
            'group_id' => $this->indexChoice('group_id', array_keys($groups)),
            'section_type' => $this->indexChoice('section_type', array_keys(SectionType::options())),
        ];
        if ($filters['q'] !== '') {
            $term = '%' . $filters['q'] . '%';
            $conditions = [
                'Sections.section_name LIKE' => $term,
                'Groups.group_name LIKE' => $term,
            ];
            if (ctype_digit($filters['q'])) {
                $conditions['Sections.section_osm_id'] = (int)$filters['q'];
            }
            $query->where(['OR' => $conditions]);
        }
        if ($filters['group_id'] !== '') {
            $query->where(['Sections.group_id' => $filters['group_id']]);
        }
        if ($filters['section_type'] !== '') {
            $query->where(['Sections.section_type' => $filters['section_type']]);
        }
        $filterControls = [
            ['name' => 'group_id', 'label' => __('Group'), 'options' => $groups, 'empty' => __('All groups')],
            [
                'name' => 'section_type', 'label' => __('Type'),
                'options' => SectionType::options(), 'empty' => __('All types'),
            ],
        ];
        $sections = $this->paginate($query);
        $this->set(compact('sections', 'filters', 'filterControls'));
    }
}
