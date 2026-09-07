<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Enum\ContactMethodType;
use Cake\Datasource\EntityInterface;
use Cake\Http\Response;

/**
 * Appointments Controller
 *
 * @property \App\Model\Table\AppointmentsTable $Appointments
 */
class AppointmentsController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $query = $this->Appointments->find()
            ->contain(['Roles', 'Members', 'MemberContactMethods']);
        $appointments = $this->paginate($query);

        $this->set(compact('appointments'));
    }

    /**
     * View method
     *
     * @param string|null $id Appointment id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null)
    {
        $appointment = $this->Appointments->get($id, contain: ['Roles', 'Members', 'MemberContactMethods']);
        $this->set(compact('appointment'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $appointment = $this->Appointments->newEmptyEntity();
        $memberId = $this->request->getQuery('member_id');
        if (is_string($memberId) && $this->Appointments->Members->exists(['id' => $memberId])) {
            $appointment->member_id = $memberId;
        }
        $roleId = $this->request->getQuery('role_id');
        if (is_string($roleId) && $this->Appointments->Roles->exists(['id' => $roleId])) {
            $appointment->role_id = $roleId;
        }
        if ($this->request->is('post')) {
            $appointment = $this->Appointments->patchEntity($appointment, $this->request->getData());
            if ($this->Appointments->save($appointment)) {
                $this->Flash->success(__('The appointment has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The appointment could not be saved. Please, try again.'));
        }
        $roles = $this->Appointments->Roles->find('list', limit: 200)->all();
        $members = $this->Appointments->Members
            ->find()
            ->limit(200)
            ->all()
            ->combine('id', 'full_name')
            ->toArray();
        $memberContactMethods = [];
        if ($members) {
            $contactMethods = $this->Appointments->MemberContactMethods->find()
                ->where(['member_id IN' => array_keys($members)]);
            foreach ($contactMethods as $contactMethod) {
                $contactMethod = $contactMethod instanceof EntityInterface
                    ? $contactMethod->toArray()
                    : $contactMethod;
                $memberContactMethods[] = [
                    'value' => $contactMethod['id'],
                    'text' => $contactMethod['contact_method'],
                    'data-member-id' => $contactMethod['member_id'],
                ];
            }
        }
        $contactMethodTypes = $this->contactMethodTypes();
        $this->set(compact(
            'appointment',
            'roles',
            'members',
            'memberContactMethods',
            'contactMethodTypes',
        ));
    }

    /**
     * Create a member and their first contact method from the appointment form.
     *
     * @return \Cake\Http\Response
     */
    public function addMember(): Response
    {
        $this->request->allowMethod(['post']);

        $data = [
            'first_name' => $this->request->getData('first_name'),
            'last_name' => $this->request->getData('last_name'),
            'membership_number' => $this->request->getData('membership_number'),
            'join_date' => $this->request->getData('join_date'),
            'member_contact_methods' => [[
                'contact_method_type' => $this->request->getData('contact_method_type'),
                'contact_method' => $this->request->getData('contact_method'),
            ]],
        ];
        $member = $this->Appointments->Members->newEntity(
            $data,
            ['associated' => ['MemberContactMethods']],
        );

        if (
            $this->Appointments->Members->save(
                $member,
                ['associated' => ['MemberContactMethods']],
            )
        ) {
            $contactMethod = $member->member_contact_methods[0];
            $payload = [
                'success' => true,
                'member' => [
                    'id' => $member->id,
                    'full_name' => $member->full_name,
                ],
                'contactMethod' => [
                    'id' => $contactMethod->id,
                    'contact_method' => $contactMethod->contact_method,
                    'contact_method_type' => $contactMethod->contact_method_type->label(),
                ],
            ];

            return $this->response
                ->withType('application/json')
                ->withStringBody((string)json_encode($payload));
        }

        $errors = $member->getErrors();
        if (!empty($member->member_contact_methods[0])) {
            $errors['contact_method'] = $member->member_contact_methods[0]->getErrors();
        }

        return $this->response
            ->withStatus(422)
            ->withType('application/json')
            ->withStringBody((string)json_encode([
                'success' => false,
                'errors' => $errors,
            ]));
    }

    /**
     * Edit method
     *
     * @param string|null $id Appointment id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null)
    {
        $appointment = $this->Appointments->get($id, contain: []);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $appointment = $this->Appointments->patchEntity($appointment, $this->request->getData());
            if ($this->Appointments->save($appointment)) {
                $this->Flash->success(__('The appointment has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The appointment could not be saved. Please, try again.'));
        }
        $roles = $this->Appointments->Roles->find('list', limit: 200)->all();
        $members = $this->Appointments->Members->find('list', limit: 200)->all();
        $memberContactMethods = [];
        foreach ($this->Appointments->MemberContactMethods->find() as $contactMethod) {
            $contactMethod = $contactMethod instanceof EntityInterface
                ? $contactMethod->toArray()
                : $contactMethod;
            $memberContactMethods[] = [
                'value' => $contactMethod['id'],
                'text' => $contactMethod['contact_method'],
                'data-member-id' => $contactMethod['member_id'],
            ];
        }
        $this->set(compact('appointment', 'roles', 'members', 'memberContactMethods'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Appointment id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $appointment = $this->Appointments->get($id);
        if ($this->Appointments->delete($appointment)) {
            $this->Flash->success(__('The appointment has been deleted.'));
        } else {
            $this->Flash->error(__('The appointment could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Get contact method types for form controls.
     *
     * @return array<int, string>
     */
    private function contactMethodTypes(): array
    {
        $contactMethodTypes = [];
        foreach (ContactMethodType::cases() as $contactMethodType) {
            $contactMethodTypes[$contactMethodType->value] = $contactMethodType->label();
        }

        return $contactMethodTypes;
    }
}
