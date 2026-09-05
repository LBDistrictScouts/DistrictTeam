<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * TeamsFixture
 */
class TeamsFixture extends TestFixture
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
                'id' => '11111111-1111-4111-8111-111111111111',
                'group_id' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',
                'team_name' => 'District Team',
                'slug' => 'district-team',
                'team_parent_id' => null,
                'tree_left' => 1,
                'tree_right' => 4,
                'tree_level' => 0,
            ],
            [
                'id' => '11111111-1111-4111-8111-111111111112',
                'group_id' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',
                'team_name' => 'Digital Team',
                'slug' => 'digital-team',
                'team_parent_id' => '11111111-1111-4111-8111-111111111111',
                'tree_left' => 2,
                'tree_right' => 3,
                'tree_level' => 1,
            ],
        ];
        parent::init();
    }
}
