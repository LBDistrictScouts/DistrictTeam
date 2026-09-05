<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Controller\AppController as BaseController;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\Table;

/**
 * Shared read-only JSON API controller.
 */
abstract class AppController extends BaseController
{
    /**
     * Table alias handled by the endpoint.
     *
     * @var string
     */
    protected string $tableAlias;

    /**
     * Associations included in API responses.
     *
     * @var array<string>
     */
    protected array $contain = [];

    /**
     * Default collection ordering.
     *
     * @var array<string, string>
     */
    protected array $order = [];

    /**
     * Configure JSON rendering for every API action.
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();

        $this->viewBuilder()->setClassName('Json');
    }

    /**
     * Return a paginated resource collection.
     *
     * @return void
     */
    public function index(): void
    {
        $this->request->allowMethod(['get']);

        $query = $this->collectionQuery();
        if ($this->order !== []) {
            $query->orderBy($this->order);
        }

        $page = $this->paginate($query);
        $items = $page->toArray();

        $this->set([
            'data' => $items,
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
     * Return one resource by UUID.
     *
     * @param string $id Resource UUID.
     * @return void
     */
    public function view(string $id): void
    {
        $this->request->allowMethod(['get']);

        $item = $this->resourceTable()->get($id, contain: $this->contain);
        $this->set('data', $item);
        $this->viewBuilder()->setOption('serialize', ['data']);
    }

    /**
     * Build the collection query before sorting and pagination.
     *
     * @return \Cake\ORM\Query\SelectQuery
     */
    protected function collectionQuery(): SelectQuery
    {
        return $this->resourceTable()->find()->contain($this->contain);
    }

    /**
     * Get the table used by this resource.
     *
     * @return \Cake\ORM\Table
     */
    private function resourceTable(): Table
    {
        return $this->fetchTable($this->tableAlias);
    }
}
