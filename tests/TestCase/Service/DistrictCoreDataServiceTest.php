<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Model\Enum\GroupType;
use App\Service\DistrictCoreDataService;
use Cake\Http\Client;
use Cake\Http\Client\Response;
use Cake\ORM\Exception\PersistenceFailedException;
use Cake\TestSuite\TestCase;
use RuntimeException;

class DistrictCoreDataServiceTest extends TestCase
{
    /**
     * @var array<string>
     */
    protected array $fixtures = ['app.Groups', 'app.Sections', 'app.Teams', 'app.Roles'];

    /**
     * @return void
     */
    public function testSyncUpdatesModelsAndPreservesLinkedTeams(): void
    {
        $service = new DistrictCoreDataService([
            'url' => 'https://example.org', 'username' => 'test', 'password' => 'secret',
        ]);
        $teams = $this->fetchTable('Teams');
        $team = $teams->get('11111111-1111-4111-8111-111111111112');
        $teams->patchEntity($team, [
            'group_id' => 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb',
            'section_id' => 'cccccccc-cccc-4ccc-8ccc-cccccccccccc',
        ]);
        $teams->saveOrFail($team);
        $groups = [['id' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',
            'group_name' => 'Renamed District', 'sort_order' => 1, 'type' => 'district', 'domains' => ['district.example.org']]];
        $sections = [['id' => 'cccccccc-cccc-4ccc-8ccc-cccccccccccc',
            'group_id' => $groups[0]['id'], 'section_name' => 'District Cubs',
            'section_id' => 456, 'section_type' => 'cubs',
            'meeting_start_time' => '18:00', 'meeting_end_time' => '19:30', 'meeting_day' => 'Monday']];
        $this->assertSame(['groups' => 1, 'sections' => 1], $service->sync($groups, $sections));
        $service->sync($groups, $sections);
        $this->assertSame(2, $this->fetchTable('Groups')->find()->count());
        $this->assertSame(1, $this->fetchTable('Sections')->find()->count());
        $this->assertSame(2, $teams->find()->count());
        $linkedTeam = $teams->get($team->id, contain: ['Groups', 'Sections', 'Roles']);
        $this->assertSame('Digital Team', $linkedTeam->team_name);
        $this->assertSame($team->team_parent_id, $linkedTeam->team_parent_id);
        $this->assertSame($team->tree_left, $linkedTeam->tree_left);
        $this->assertSame('Renamed District', $linkedTeam->group->group_name);
        $this->assertSame(GroupType::District, $linkedTeam->group->type);
        $this->assertSame(['district.example.org'], $linkedTeam->group->domains);
        $this->assertSame('District Cubs', $linkedTeam->section->section_name);
        $this->assertSame(456, $linkedTeam->section->section_osm_id);
        $this->assertSame('18:00', $linkedTeam->section->meeting_start_time);
        $this->assertSame('Monday', $linkedTeam->section->meeting_day);
        $this->assertNotEmpty($linkedTeam->roles);

        $groups[] = ['id' => 'dddddddd-dddd-4ddd-8ddd-dddddddddddd',
            'group_name' => 'New Group', 'sort_order' => 3, 'type' => 'group', 'domains' => ['group.example.org']];
        $sections[] = ['id' => 'eeeeeeee-eeee-4eee-8eee-eeeeeeeeeeee',
            'group_id' => $groups[1]['id'], 'section_name' => 'New Beavers',
            'section_id' => 789, 'section_type' => 'beavers'];
        $service->sync($groups, $sections);
        $service->sync($groups, $sections);
        $this->assertSame(3, $this->fetchTable('Groups')->find()->count());
        $this->assertSame(2, $this->fetchTable('Sections')->find()->count());
        $this->assertSame(2, $teams->find()->count());
        $groups[0]['type'] = 'group';
        $groups[0]['domains'] = ['renamed.example.org', 'mail.example.org'];
        $service->sync($groups, $sections);
        $updated = $this->fetchTable('Groups')->get($groups[0]['id']);
        $this->assertSame(GroupType::Group, $updated->type);
        $this->assertSame($groups[0]['domains'], $updated->domains);
    }

    /**
     * @return void
     */
    public function testSaveFailureRollsBackGroupChanges(): void
    {
        $service = new DistrictCoreDataService([
            'url' => 'https://example.org', 'username' => 'test', 'password' => 'secret',
        ]);
        try {
            $service->sync([['id' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',
                'group_name' => 'Changed', 'sort_order' => 1, 'type' => 'district', 'domains' => ['district.example.org']]], [
                ['id' => 'cccccccc-cccc-4ccc-8ccc-cccccccccccc',
                    'group_id' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',
                    'section_name' => 'Cubs', 'section_id' => 123, 'section_type' => 'invalid'],
            ]);
            $this->fail('Expected validation failure.');
        } catch (PersistenceFailedException) {
            $this->assertSame('District', $this->fetchTable('Groups')->get('aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa')->group_name);
            $this->assertSame(
                'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb',
                $this->fetchTable('Sections')->get('cccccccc-cccc-4ccc-8ccc-cccccccccccc')->group_id,
            );
        }
    }

    /**
     * @return void
     */
    public function testUnknownGroupIsRejectedBeforeWrites(): void
    {
        $service = new DistrictCoreDataService([
            'url' => 'https://example.org', 'username' => 'test', 'password' => 'secret',
        ]);
        $this->expectExceptionMessage('section record is invalid');
        $service->sync([['id' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',
            'group_name' => 'Changed', 'sort_order' => 1, 'type' => 'district', 'domains' => ['district.example.org']]], [
            ['id' => 'cccccccc-cccc-4ccc-8ccc-cccccccccccc',
                'group_id' => 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb',
                'section_name' => 'Cubs', 'section_id' => 1, 'section_type' => 'cubs'],
        ]);
    }

    /**
     * @return void
     */
    public function testFetchUsesAuthenticatedSiblingDatasets(): void
    {
        foreach (['https://example.org', 'https://example.org/data/', 'https://example.org/data/index.html'] as $url) {
            $base = $url === 'https://example.org' ? $url . '/' : 'https://example.org/data/';
            $client = $this->createMock(Client::class);
            $requests = [];
            $client->expects($this->exactly(2))->method('get')->willReturnCallback(
                function ($url, $data, $options) use (&$requests): Response {
                    $requests[] = $url;
                    $this->assertSame([], $data);
                    $this->assertSame('Basic ' . base64_encode('test:secret'), $options['headers']['Authorization']);

                    return new Response(['HTTP/1.1 200 OK'], '[{"id":"example"}]');
                },
            );
            $service = new DistrictCoreDataService([
                'url' => $url, 'username' => 'test', 'password' => 'secret',
            ], $client);
            $this->assertSame([
                'groups' => [['id' => 'example']], 'sections' => [['id' => 'example']],
            ], $service->fetch());
            $this->assertSame([$base . 'groups.json', $base . 'sections.json'], $requests);
        }
    }

    /**
     * @return void
     */
    public function testMissingConfigurationFails(): void
    {
        $this->expectException(RuntimeException::class);
        new DistrictCoreDataService([]);
    }

    /**
     * @return void
     */
    public function testHttpFailureIsReported(): void
    {
        $client = $this->createMock(Client::class);
        $client->expects($this->once())->method('get')->willReturn(new Response(['HTTP/1.1 401 Unauthorized'], 'Unauthorized'));
        $service = new DistrictCoreDataService([
            'url' => 'https://example.org', 'username' => 'test', 'password' => 'secret',
        ], $client);
        $this->expectExceptionMessage('groups.json request failed with status 401');
        $service->fetch();
    }

    /**
     * @return void
     */
    public function testInvalidJsonShapeIsRejected(): void
    {
        $client = $this->createMock(Client::class);
        $client->expects($this->once())->method('get')->willReturn(new Response(['HTTP/1.1 200 OK'], '{"error":"failed"}'));
        $service = new DistrictCoreDataService([
            'url' => 'https://example.org', 'username' => 'test', 'password' => 'secret',
        ], $client);
        $this->expectExceptionMessage('did not return a JSON list');
        $service->fetch();
    }
}
