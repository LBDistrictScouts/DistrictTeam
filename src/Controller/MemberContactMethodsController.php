<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Http\Response;

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
        $memberContactMethod = $this->MemberContactMethods->newEntity($data);

        if ($this->MemberContactMethods->save($memberContactMethod)) {
            $payload = [
                'success' => true,
                'contactMethod' => [
                    'id' => $memberContactMethod->id,
                    'contact_method' => $memberContactMethod->contact_method,
                    'contact_method_type' => $memberContactMethod->contact_method_type->label(),
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
}
