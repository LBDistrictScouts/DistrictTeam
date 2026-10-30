<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class TagsFixture extends TestFixture
{
    /**
     * @var array<array<string, mixed>>
     */
    public array $records = [
        [
            'id' => 'aaaaaaaa-1111-4111-8111-aaaaaaaaaaaa',
            'tag_label' => 'API fixture tag',
            'tag_code' => 'api-fixture-tag',
            'is_cognito_permissions_group' => false,
        ],
    ];
}
