<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class SectionsFixture extends TestFixture
{
    /**
     * @var array<array<string, mixed>>
     */
    public array $records = [
        [
            'id' => 'cccccccc-cccc-4ccc-8ccc-cccccccccccc',
            'group_id' => 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb',
            'section_osm_id' => 123,
            'section_name' => 'First Cubs',
            'section_type' => 'cubs',
            'meeting_day' => 'Monday',
            'meeting_start_time' => '18:00',
            'meeting_end_time' => '19:30',
        ],
    ];
}
