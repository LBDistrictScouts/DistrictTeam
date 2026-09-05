<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * MembersFixture
 */
class MembersFixture extends TestFixture
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
                'id' => '33333333-3333-4333-8333-333333333331',
                'first_name' => 'Ada',
                'last_name' => 'Lovelace',
                'membership_number' => 1001,
                'join_date' => '2020-01-01',
                'leave_date' => null,
            ],
            [
                'id' => '33333333-3333-4333-8333-333333333332',
                'first_name' => 'Grace',
                'last_name' => 'Hopper',
                'membership_number' => 1002,
                'join_date' => '2020-01-01',
                'leave_date' => '2021-01-01',
            ],
        ];
        parent::init();
    }
}
