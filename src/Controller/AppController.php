<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link      https://cakephp.org CakePHP(tm) Project
 * @since     0.2.9
 * @license   https://opensource.org/licenses/mit-license.php MIT License
 */
namespace App\Controller;

use Cake\Controller\Controller;
use Cake\Event\EventInterface;

/**
 * Application Controller
 *
 * Add your application-wide methods in the class below, your controllers
 * will inherit them.
 *
 * @link https://book.cakephp.org/5/en/controllers.html#the-app-controller
 */
class AppController extends Controller
{
    private const PAGINATION_LIMITS = [10, 25, 50, 75, 100];

    /**
     * Initialization hook method.
     *
     * Use this method to add common initialization code like loading components.
     *
     * e.g. `$this->loadComponent('FormProtection');`
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();

        $this->loadComponent('Flash');

        /*
         * Enable the following component for recommended CakePHP form protection settings.
         * see https://book.cakephp.org/5/en/controllers/components/form-protection.html
         */
        //$this->loadComponent('FormProtection');
    }

    /**
     * Apply the requested or session-cached page size to directory indexes.
     *
     * @param \Cake\Event\EventInterface<\Cake\Controller\Controller> $event Controller beforeFilter event.
     * @return void
     */
    public function beforeFilter(EventInterface $event): void
    {
        parent::beforeFilter($event);

        if (
            $this->request->getParam('action') !== 'index'
            || $this->request->getParam('prefix') !== null
        ) {
            return;
        }

        $session = $this->request->getSession();
        $requestedLimit = filter_var($this->request->getQuery('limit'), FILTER_VALIDATE_INT);
        if ($requestedLimit !== false && in_array($requestedLimit, self::PAGINATION_LIMITS, true)) {
            $limit = $requestedLimit;
            $session->write('Pagination.limit', $limit);
        } else {
            $cachedLimit = (int)$session->read('Pagination.limit');
            $limit = in_array($cachedLimit, self::PAGINATION_LIMITS, true) ? $cachedLimit : 25;
        }

        $query = $this->request->getQueryParams();
        $query['limit'] = $limit;
        $this->setRequest($this->request->withQueryParams($query));
        $this->paginate = ['limit' => $limit, 'maxLimit' => 100] + $this->paginate;
    }

    /**
     * Return a scalar GET value suitable for an index filter.
     *
     * @param string $name Query parameter name.
     * @return string
     */
    protected function indexFilter(string $name): string
    {
        $value = $this->request->getQuery($name);

        return is_string($value) ? trim($value) : '';
    }

    /**
     * Return a recognised GET filter value, ignoring unexpected values.
     *
     * @param string $name Query parameter name.
     * @param list<string> $allowed Accepted values.
     * @return string
     */
    protected function indexChoice(string $name, array $allowed): string
    {
        $value = $this->indexFilter($name);

        return in_array($value, $allowed, true) ? $value : '';
    }
}
