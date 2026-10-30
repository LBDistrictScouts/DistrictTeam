<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Model\Entity\Tag;
use App\Model\Table\TagsTable;
use Cake\ORM\Query\SelectQuery;
use RuntimeException;

class TagsController extends AppController
{
    protected string $tableAlias = 'Tags';

    protected array $order = ['Tags.tag_label' => 'ASC', 'Tags.id' => 'ASC'];

    /**
     * Return tags and their current matching appointments.
     *
     * @return void
     */
    public function index(): void
    {
        $this->request->allowMethod(['get']);

        $page = $this->paginate($this->collectionQuery());
        $tags = $page->toArray();
        foreach ($tags as $tag) {
            if ($tag instanceof Tag) {
                $this->addSyncFields($tag);
            }
        }

        $this->set([
            'data' => $tags,
            'pagination' => [
                'page' => $page->currentPage(),
                'page_count' => $page->pageCount(),
                'per_page' => $page->perPage(),
                'total' => $page->totalCount(),
            ],
        ]);
        $this->viewBuilder()->setOption('serialize', ['data', 'pagination']);
    }

    /**
     * Return one tag and its current matching appointments.
     *
     * @param string $id Tag UUID.
     * @return void
     */
    public function view(string $id): void
    {
        $this->request->allowMethod(['get']);

        $tag = $this->tagTable()->get($id);
        $this->addSyncFields($tag);

        $this->set('data', $tag);
        $this->viewBuilder()->setOption('serialize', ['data']);
    }

    /**
     * @return \Cake\ORM\Query\SelectQuery<\App\Model\Entity\Tag>
     */
    protected function collectionQuery(): SelectQuery
    {
        return $this->tagTable()->find();
    }

    /**
     * Add the minimal assignment IDs and current appointment fields used for sync.
     *
     * @param \App\Model\Entity\Tag $tag Tag to decorate.
     * @return void
     */
    private function addSyncFields(Tag $tag): void
    {
        $appointments = [];
        foreach ($this->tagTable()->findCurrentAppointments($tag->id, groupEmailsOnly: true) as $appointment) {
            $appointments[] = [
                'id' => (string)$appointment->id,
                'member_id' => (string)$appointment->member_id,
                'member_contact_method_id' => (string)$appointment->member_contact_method_id,
                'member_first_name' => (string)$appointment->member->first_name,
                'member_last_name' => (string)$appointment->member->last_name,
                'role_name' => (string)$appointment->role->name,
                'contact_method' => (string)$appointment->member_contact_method->contact_method,
            ];
        }
        $tag->set('current_appointments', $appointments);
    }

    /**
     * Return the concrete tags table used by this endpoint.
     *
     * @return \App\Model\Table\TagsTable
     */
    private function tagTable(): TagsTable
    {
        $table = $this->fetchTable($this->tableAlias);
        if (!$table instanceof TagsTable) {
            throw new RuntimeException('Expected the Tags table');
        }

        return $table;
    }
}
