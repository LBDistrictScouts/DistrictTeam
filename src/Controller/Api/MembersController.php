<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Model\Entity\Member;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Exception\NotFoundException;
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

    /**
     * Return a member profile and their appointments effective today.
     *
     * @param string $membershipNumber Member's membership number.
     * @return void
     */
    public function profile(string $membershipNumber): void
    {
        $this->request->allowMethod(['get']);

        $number = ltrim($membershipNumber, '0');
        $maxNumber = (string)PHP_INT_MAX;
        if (
            !preg_match('/^\d+$/D', $membershipNumber)
            || $number === ''
            || strlen($number) > strlen($maxNumber)
            || (strlen($number) === strlen($maxNumber) && strcmp($number, $maxNumber) > 0)
        ) {
            throw new BadRequestException('A valid membership number is required.');
        }

        $member = $this->Members->find()
            ->where(['Members.membership_number' => (int)$number])
            ->contain(['MemberContactMethods'])
            ->first();
        if (!$member instanceof Member) {
            throw new NotFoundException('Member not found.');
        }

        $appointments = $this->fetchTable('Appointments')->find('current')
            ->where(['Appointments.member_id' => $member->id])
            ->contain([
                'Roles.Teams',
                'MemberContactMethods',
                'Members' => ['fields' => ['Members.id', 'Members.public_opt_out']],
            ])
            ->orderByAsc('Appointments.effective_start_date')
            ->orderByAsc('Roles.name')
            ->all();

        $currentAppointments = [];
        foreach ($appointments as $appointment) {
            $appointment->setHidden(['member'], true);
            $appointment->set('appointment_email', $appointment->member_contact_method->contact_method);
            $currentAppointments[] = $appointment;
        }
        $member->set('current_appointments', $currentAppointments);

        $this->set('data', $member);
        $this->viewBuilder()->setOption('serialize', ['data']);
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
