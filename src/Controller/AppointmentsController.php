<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Entity\MemberContactMethod;
use App\Model\Enum\ContactMethodType;
use Cake\Datasource\EntityInterface;
use Cake\Http\Response;
use Cake\I18n\Date;
use Cake\Validation\Validation;

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
            ->contain(['Roles', 'Members', 'MemberContactMethods', 'Roles.Groups']);
        $groups = $this->Appointments->Roles->Groups->find('list')->orderByAsc('sort_order')
            ->orderByAsc('group_name')->toArray();
        $filters = [
            'q' => $this->indexFilter('q'),
            'group_id' => $this->indexFilter('group_id'),
            'status' => $this->indexChoice('status', ['active', 'ended']),
        ];
        if ($filters['q'] !== '') {
            $term = '%' . $filters['q'] . '%';
            $query->where(['OR' => [
                'Roles.name LIKE' => $term,
                'Members.first_name LIKE' => $term,
                'Members.last_name LIKE' => $term,
                'MemberContactMethods.contact_method LIKE' => $term,
            ]]);
        }
        if ($filters['group_id'] !== '') {
            $query->where(['Roles.group_id' => $filters['group_id']]);
        }
        $today = Date::today();
        if ($filters['status'] === 'active') {
            $query->where([
                'Appointments.effective_start_date <=' => $today,
                'OR' => [
                    'Appointments.effective_end_date IS' => null,
                    'Appointments.effective_end_date >=' => $today,
                ],
            ]);
        } elseif ($filters['status'] === 'ended') {
            $query->where(['OR' => [
                'Appointments.effective_start_date >' => $today,
                'Appointments.effective_end_date <' => $today,
            ]]);
        }
        $appointments = $this->paginate($query);

        $filterControls = [
            ['name' => 'group_id', 'label' => __('Group'), 'options' => $groups, 'empty' => __('All groups')],
            ['name' => 'status', 'label' => __('Status'), 'options' => [
                'active' => __('Active'), 'ended' => __('Ended'),
            ], 'empty' => __('All appointments')],
        ];
        $this->set(compact('appointments', 'filters', 'filterControls'));
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
            if (!$this->hasNonGroupEmailContactMethod($appointment) && $this->Appointments->save($appointment)) {
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
                ->where([
                    'member_id IN' => array_keys($members),
                    'is_non_group_email' => false,
                    'contact_method_type IN' => [
                        ContactMethodType::Email->value,
                        ContactMethodType::EmailAlias->value,
                        ContactMethodType::EmailGroup->value,
                    ],
                ]);
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
                    'is_non_group_email' => $contactMethod->is_non_group_email,
                    'is_appointment_email' => $this->isAppointmentEmail($contactMethod->contact_method_type),
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
            if (!$this->hasNonGroupEmailContactMethod($appointment) && $this->Appointments->save($appointment)) {
                $this->Flash->success(__('The appointment has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The appointment could not be saved. Please, try again.'));
        }
        $roles = $this->Appointments->Roles->find('list', limit: 200)->all();
        $members = $this->Appointments->Members->find('list', limit: 200)->all();
        $memberContactMethods = [];
        foreach (
            $this->Appointments->MemberContactMethods->find()->where([
            'is_non_group_email' => false,
            'contact_method_type IN' => [
                ContactMethodType::Email->value,
                ContactMethodType::EmailAlias->value,
                ContactMethodType::EmailGroup->value,
            ],
            ]) as $contactMethod
        ) {
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

    /**
     * Reject non-group email contact methods submitted outside the selector.
     *
     * @param \Cake\Datasource\EntityInterface $appointment Appointment being saved.
     * @return bool Whether the contact method is a non-group email.
     */
    private function hasNonGroupEmailContactMethod(EntityInterface $appointment): bool
    {
        if (!$appointment->isNew() && !$appointment->isDirty('member_contact_method_id')) {
            return false;
        }

        $contactMethodId = $appointment->get('member_contact_method_id');
        if (!is_string($contactMethodId) || $contactMethodId === '') {
            return false;
        }

        if (!Validation::uuid($contactMethodId)) {
            return false;
        }

        $contactMethod = $this->Appointments->MemberContactMethods->find()
            ->where(['id' => $contactMethodId])
            ->first();
        if (!$contactMethod instanceof MemberContactMethod) {
            return false;
        }
        $isUnavailable = $contactMethod->is_non_group_email
            || !$this->isAppointmentEmail($contactMethod->contact_method_type);
        if ($isUnavailable) {
            $appointment->setError(
                'member_contact_method_id',
                __('Only group email contact methods can be used for an appointment'),
            );
        }

        return $isUnavailable;
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
}
