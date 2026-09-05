<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Enum\GroupType;
use Cake\TestSuite\TestCase;

class GroupsTableTest extends TestCase
{
    /**
     * @var array<string>
     */
    protected array $fixtures = ['app.Groups'];

    /**
     * @return void
     */
    public function testGroupTypeAndDomainsRoundTrip(): void
    {
        $groups = $this->fetchTable('Groups');
        $group = $groups->newEntity([
            'group_name' => 'New District', 'type' => 'district', 'domains' => ['district.example.org'],
        ]);
        $this->assertSame(GroupType::District, $group->type);
        $groups->saveOrFail($group);
        $saved = $groups->get($group->id);
        $this->assertSame(GroupType::District, $saved->type);
        $this->assertSame(['district.example.org'], $saved->domains);
        $json = json_decode(json_encode($saved, JSON_THROW_ON_ERROR), true);
        $this->assertSame('district', $json['type']);
        $this->assertSame(['district.example.org'], $json['domains']);
    }

    /**
     * @return void
     */
    public function testInvalidCoreMetadataIsRejected(): void
    {
        $groups = $this->fetchTable('Groups');
        foreach (['county', '', null] as $type) {
            $entity = $groups->newEntity([
                'group_name' => 'Invalid', 'type' => $type, 'domains' => ['example.org'],
            ]);
            $this->assertArrayHasKey('type', $entity->getErrors());
        }
        foreach ([[], null, 'example.org', ['https://example.org'], ['a/b'], ['-invalid.org'], [123]] as $domains) {
            $entity = $groups->newEntity([
                'group_name' => 'Invalid', 'type' => GroupType::Group, 'domains' => $domains,
            ]);
            $this->assertArrayHasKey('domains', $entity->getErrors());
        }
        $missing = $groups->newEntity(['group_name' => 'Missing metadata']);
        $this->assertArrayHasKey('type', $missing->getErrors());
        $this->assertArrayHasKey('domains', $missing->getErrors());
    }
}
