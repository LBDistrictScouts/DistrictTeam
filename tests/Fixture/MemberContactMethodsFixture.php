<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * MemberContactMethodsFixture
 */
class MemberContactMethodsFixture extends TestFixture
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
                'id' => '44444444-4444-4444-8444-444444444441',
                'member_id' => '33333333-3333-4333-8333-333333333331',
                'contact_method' => 'ada@example.com',
                'contact_method_type' => 1,
            ],
            [
                'id' => '44444444-4444-4444-8444-444444444442',
                'member_id' => '33333333-3333-4333-8333-333333333332',
                'contact_method' => '07000000000',
                'contact_method_type' => 10,
            ],
        ];
        parent::init();
    }
}
