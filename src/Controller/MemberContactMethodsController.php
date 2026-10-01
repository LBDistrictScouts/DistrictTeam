<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Enum\ContactMethodType;
use Cake\Datasource\EntityInterface;
use Cake\Http\Response;
use Cake\Routing\Router;

/**
 * MemberContactMethods Controller
 *
 * @property \App\Model\Table\MemberContactMethodsTable $MemberContactMethods
 */
class MemberContactMethodsController extends AppController
{
    /**
     * Add a contact method from the member view.
     *
     * @param string $memberId Member id.
     * @return \Cake\Http\Response
     */
    public function addForMember(string $memberId): Response
    {
        $this->request->allowMethod(['post']);

        $data = ['member_id' => $memberId] + $this->request->getData();
        if ((int)($data['contact_method_type'] ?? 0) === ContactMethodType::EmailGroup->value) {
            $emailGroupId = $data['email_group_id'] ?? null;
            $emailGroup = is_string($emailGroupId)
                ? $this->fetchTable('EmailGroups')->find()
                    ->select(['id', 'email_address'])
                    ->where(['id' => $emailGroupId])
                    ->first()
                : null;
            if (!$emailGroup instanceof EntityInterface || !is_string($emailGroup->get('email_address'))) {
                return $this->invalidEmailGroupResponse();
            }
            $data['contact_method_type'] = ContactMethodType::EmailGroup->value;
            $data['email_group_id'] = $emailGroup->get('id');
            $data['contact_method'] = $emailGroup->get('email_address');
        }
        $memberContactMethod = null;
        if (
            (int)($data['contact_method_type'] ?? 0) === ContactMethodType::EmailGroup->value
            && is_string($data['contact_method'] ?? null)
        ) {
            $memberContactMethod = $this->MemberContactMethods->find()
                ->where([
                    'member_id' => $memberId,
                    'contact_method' => $data['contact_method'],
                ])
                ->first();
        }
        if ($memberContactMethod instanceof EntityInterface) {
            $this->MemberContactMethods->patchEntity($memberContactMethod, $data);
        } else {
            $memberContactMethod = $this->MemberContactMethods->newEntity($data);
        }

        if ($this->MemberContactMethods->save($memberContactMethod)) {
            $payload = [
                'success' => true,
                'contactMethod' => [
                    'id' => $memberContactMethod->id,
                    'contact_method' => $memberContactMethod->contact_method,
                    'contact_method_type' => $memberContactMethod->contact_method_type->label(),
                    'is_non_group_email' => $memberContactMethod->is_non_group_email,
                    'is_appointment_email' => $this->isAppointmentEmail($memberContactMethod->contact_method_type),
                    'delete_url' => Router::url([
                        'controller' => 'MemberContactMethods',
                        'action' => 'deleteForMember',
                        $memberId,
                        $memberContactMethod->id,
                    ]),
                ],
            ];

            return $this->response
                ->withType('application/json')
                ->withStringBody((string)json_encode($payload));
        }

        $payload = [
            'success' => false,
            'errors' => $memberContactMethod->getErrors(),
        ];

        return $this->response
            ->withStatus(422)
            ->withType('application/json')
            ->withStringBody((string)json_encode($payload));
    }

    /**
     * Delete a contact method from its member profile when it is unused.
     *
     * @param string $memberId Member id.
     * @param string $id Contact method id.
     * @return \Cake\Http\Response
     */
    public function deleteForMember(string $memberId, string $id): Response
    {
        $this->request->allowMethod(['post']);

        $contactMethod = $this->MemberContactMethods->find()
            ->where(['id' => $id, 'member_id' => $memberId])
            ->firstOrFail();

        if ($this->fetchTable('Appointments')->exists(['member_contact_method_id' => $contactMethod->id])) {
            $this->Flash->error(__('This contact method cannot be deleted because it is used by an appointment.'));
        } elseif ($this->MemberContactMethods->delete($contactMethod)) {
            $this->Flash->success(__('The contact method has been deleted.'));
        } else {
            $this->Flash->error(__('The contact method could not be deleted. Please, try again.'));
        }

        return $this->redirect(['controller' => 'Members', 'action' => 'view', $memberId]);
    }

    /**
     * @param \App\Model\Enum\ContactMethodType $contactMethodType Contact method type.
     * @return bool Whether the type represents an email address.
     */
    private function isAppointmentEmail(ContactMethodType $contactMethodType): bool
    {
        return in_array($contactMethodType, [
            ContactMethodType::Email,
            ContactMethodType::EmailAlias,
            ContactMethodType::EmailGroup,
        ], true);
    }

    /**
     * @return \Cake\Http\Response
     */
    private function invalidEmailGroupResponse(): Response
    {
        return $this->response
            ->withStatus(422)
            ->withType('application/json')
            ->withStringBody((string)json_encode([
                'success' => false,
                'errors' => ['email_group_id' => ['validEmailGroup' => __('Choose an email group.')]],
            ]));
    }
}
