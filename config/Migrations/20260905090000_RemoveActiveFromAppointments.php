<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class RemoveActiveFromAppointments extends BaseMigration
{
    /**
     * Appointment activity is derived from its effective date range.
     *
     * @return void
     */
    public function change(): void
    {
        $this->table('appointments')
            ->removeColumn('active')
            ->update();
    }
}
