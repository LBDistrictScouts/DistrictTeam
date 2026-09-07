<?php
declare(strict_types=1);

namespace App\Controller\Api;

use Cake\ORM\Query\SelectQuery;

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
            $results[] = ['id' => $member->id, 'text' => $member->full_name];
        }
        $this->set([
            'results' => $results,
            'pagination' => ['more' => $page * $limit < $total],
        ]);
        $this->viewBuilder()->setOption('serialize', ['results', 'pagination']);
    }

    /** @return \Cake\ORM\Query\SelectQuery<\App\Model\Entity\Member> */
    private function memberSearchQuery(string $term): SelectQuery
    {
        $query = $this->fetchTable('Members')->find()
            ->select(['id', 'first_name', 'last_name'])
            ->orderBy($this->order);
        if ($term !== '') {
            $query->where(['OR' => [
                'LOWER(Members.first_name) LIKE' => '%' . $term . '%',
                'LOWER(Members.last_name) LIKE' => '%' . $term . '%',
            ]]);
        }

        return $query;
    }
}
