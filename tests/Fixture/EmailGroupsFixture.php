<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class EmailGroupsFixture extends TestFixture
{
    /**
     * @var array<array<string, mixed>>
     */
    public array $records = [
        [
            'id' => '66666666-6666-4666-8666-666666666661',
            'group_id' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',
            'team_id' => '11111111-1111-4111-8111-111111111112',
            'section_id' => null,
            'email_group_name' => 'Digital Team Leaders',
            'email_address' => 'digital-leaders@district.example.org',
        ],
        [
            'id' => '66666666-6666-4666-8666-666666666662',
            'group_id' => 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb',
            'team_id' => null,
            'section_id' => null,
            'email_group_name' => 'Group Trustees',
            'email_address' => 'trustees@group.example.org',
        ],
    ];
}
