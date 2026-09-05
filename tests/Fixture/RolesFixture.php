<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * RolesFixture
 */
class RolesFixture extends TestFixture
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
                'id' => '22222222-2222-4222-8222-222222222221',
                'team_id' => '11111111-1111-4111-8111-111111111112',
                'group_id' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',
                'name' => 'Digital Lead',
                'slug' => 'digital-lead',
                'description' => 'Leads digital services',
                'currently_filled' => 1,
                'is_lead' => 1,
                'multi_member_role' => 0,
            ],
            [
                'id' => '22222222-2222-4222-8222-222222222222',
                'team_id' => '11111111-1111-4111-8111-111111111111',
                'group_id' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',
                'name' => 'Vacant Role',
                'slug' => 'vacant-role',
                'description' => null,
                'currently_filled' => 0,
                'is_lead' => 0,
                'multi_member_role' => 0,
            ],
        ];
        parent::init();
    }
}
