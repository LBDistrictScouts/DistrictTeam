<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * AppointmentsFixture
 */
class AppointmentsFixture extends TestFixture
{
    /**
     * Init method
     *
     * @return void
     */
    public function init(): void
    {
        $this->records = [
            [
                'id' => '55555555-5555-4555-8555-555555555551',
                'role_id' => '22222222-2222-4222-8222-222222222221',
                'member_id' => '33333333-3333-4333-8333-333333333331',
                'member_contact_method_id' => '44444444-4444-4444-8444-444444444441',
                'effective_start_date' => '2020-01-01',
                'effective_end_date' => null,
                'active' => 1,
            ],
        ];
        parent::init();
    }
}
