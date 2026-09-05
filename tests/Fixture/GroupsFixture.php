<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class GroupsFixture extends TestFixture
{
    /**
     * @var array<array<string, mixed>>
     */
    public array $records = [
        ['id' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', 'group_name' => 'District', 'sort_order' => 1,
            'type' => 'district', 'domains' => ['district.example.org'], 'sections_count' => 0,
            'teams_count' => 2, 'roles_count' => 2],
        ['id' => 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', 'group_name' => 'First Scout Group', 'sort_order' => 2,
            'type' => 'group', 'domains' => ['group.example.org'], 'sections_count' => 1,
            'teams_count' => 0, 'roles_count' => 0],
    ];
}
