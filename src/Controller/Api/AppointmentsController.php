<?php
declare(strict_types=1);

namespace App\Controller\Api;

class AppointmentsController extends AppController
{
    protected string $tableAlias = 'Appointments';

    protected array $contain = ['Roles', 'Members', 'MemberContactMethods'];

    protected array $order = ['Appointments.effective_start_date' => 'DESC'];
}
