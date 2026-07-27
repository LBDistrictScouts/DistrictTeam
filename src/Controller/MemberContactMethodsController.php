<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Enum\ContactMethodType;
use Cake\Http\Response;

/**
 * MemberContactMethods Controller
 *
 * @property \App\Model\Table\MemberContactMethodsTable $MemberContactMethods
 */
class MemberContactMethodsController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $query = $this->MemberContactMethods->find()
            ->contain(['Members']);
        $memberContactMethods = $this->paginate($query);

        $this->set(compact('memberContactMethods'));
    }

    /**
     * View method
     *
     * @param string|null $id Member Contact Method id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null)
    {
        $memberContactMethod = $this->MemberContactMethods->get($id, contain: ['Members']);
        $this->set(compact('memberContactMethod'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $memberContactMethod = $this->MemberContactMethods->newEmptyEntity();
        if ($this->request->is('post')) {
            $memberContactMethod = $this->MemberContactMethods->patchEntity(
                $memberContactMethod,
                $this->request->getData(),
            );
            if ($this->MemberContactMethods->save($memberContactMethod)) {
                $this->Flash->success(__('The member contact method has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The member contact method could not be saved. Please, try again.'));
        }
        $members = $this->MemberContactMethods->Members->find('list', limit: 200)->all();
        $contactMethodTypes = $this->contactMethodTypes();
        $this->set(compact('memberContactMethod', 'members', 'contactMethodTypes'));
    }

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
     * Edit method
     *
     * @param string|null $id Member Contact Method id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null)
    {
        $memberContactMethod = $this->MemberContactMethods->get($id, contain: []);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $memberContactMethod = $this->MemberContactMethods->patchEntity(
                $memberContactMethod,
                $this->request->getData(),
            );
            if ($this->MemberContactMethods->save($memberContactMethod)) {
                $this->Flash->success(__('The member contact method has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The member contact method could not be saved. Please, try again.'));
        }
        $members = $this->MemberContactMethods->Members->find('list', limit: 200)->all();
        $contactMethodTypes = $this->contactMethodTypes();
        $this->set(compact('memberContactMethod', 'members', 'contactMethodTypes'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Member Contact Method id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $memberContactMethod = $this->MemberContactMethods->get($id);
        if ($this->MemberContactMethods->delete($memberContactMethod)) {
            $this->Flash->success(__('The member contact method has been deleted.'));
        } else {
            $this->Flash->error(__('The member contact method could not be deleted. Please, try again.'));
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
