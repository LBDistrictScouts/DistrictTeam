<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Model\Entity\Member;
use Cake\ORM\Query\SelectQuery;

/** @property \App\Model\Table\MembersTable $Members */
class MembersController extends AppController
{
    protected string $tableAlias = 'Members';

    protected array $contain = ['MemberContactMethods'];

    protected array $order = ['Members.last_name' => 'ASC', 'Members.first_name' => 'ASC'];

    /**
     * Return Select2-compatible member search results.
     *
     * @return void
     */
    public function search(): void
    {
        $this->request->allowMethod(['get']);
        $term = strtolower(trim((string)$this->request->getQuery('q', '')));
        $page = max(1, (int)$this->request->getQuery('page', 1));
        $limit = 20;
        $query = $this->memberSearchQuery($term);
        $total = $query->count();
        $results = [];
        foreach ($query->limit($limit)->offset(($page - 1) * $limit)->all() as $member) {
            if (!$member instanceof Member) {
                continue;
            }
            $results[] = ['id' => $member->id, 'text' => $member->full_name];
        }
        $this->set([
            'results' => $results,
            'pagination' => ['more' => $page * $limit < $total],
        ]);
        $this->viewBuilder()->setOption('serialize', ['results', 'pagination']);
    }

    /** @return \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface> */
    private function memberSearchQuery(string $term): SelectQuery
    {
        $query = $this->Members->find()
            ->select(['id', 'first_name', 'last_name'])
            ->orderBy($this->order);
        foreach (preg_split('/\s+/', $term, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $word) {
            $query->where(['OR' => [
                'LOWER(Members.first_name) LIKE' => '%' . $word . '%',
                'LOWER(Members.last_name) LIKE' => '%' . $word . '%',
            ]]);
        }

        return $query;
    }
}
