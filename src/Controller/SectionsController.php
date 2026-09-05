<?php
declare(strict_types=1);

namespace App\Controller;

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
        $this->set('sections', $this->paginate($query));
    }
}
