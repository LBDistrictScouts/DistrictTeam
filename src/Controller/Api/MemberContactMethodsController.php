<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Model\Enum\ContactMethodType;
use Cake\Validation\Validation;

class MemberContactMethodsController extends AppController
{
    protected string $tableAlias = 'MemberContactMethods';

    protected array $contain = ['Members'];

    protected array $order = ['MemberContactMethods.contact_method' => 'ASC'];

    /**
     * Return a member's email contact methods that may be used for an appointment.
     *
     * @return void
     */
    public function appointmentContactMethods(): void
    {
        $this->request->allowMethod(['get']);
        $memberId = (string)$this->request->getQuery('member_id', '');
        $results = [];
        if (Validation::uuid($memberId)) {
            $contactMethods = $this->fetchTable('MemberContactMethods')->find()
                ->select(['id', 'contact_method'])
                ->where([
                    'member_id' => $memberId,
                    'is_non_group_email' => false,
                    'contact_method_type IN' => [
                        ContactMethodType::Email->value,
                        ContactMethodType::EmailAlias->value,
                        ContactMethodType::EmailGroup->value,
                    ],
                ])
                ->orderByAsc('contact_method');
            foreach ($contactMethods as $contactMethod) {
                $results[] = [
                    'id' => $contactMethod->get('id'),
                    'text' => $contactMethod->get('contact_method'),
                ];
            }
        }

        $this->set('results', $results);
        $this->viewBuilder()->setOption('serialize', ['results']);
    }
}
