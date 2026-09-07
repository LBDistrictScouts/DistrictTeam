<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use AddGroupTypeAndDomains;
use App\Service\TeamScopeBackfill;
use Cake\Core\Configure;
use Cake\Database\Connection;
use Cake\Database\Driver\Sqlite;
use Cake\TestSuite\TestCase;
use CreateGroupsAndSections;
use Migrations\Db\Adapter\SqliteAdapter;
use RequireTeamGroup;
use RuntimeException;

class TeamScopeBackfillTest extends TestCase
{
    private mixed $coreConfig;

    protected function setUp(): void
    {
        parent::setUp();
        $this->coreConfig = Configure::read('DistrictCoreData');
        Configure::write('DistrictCoreData', ['url' => 'https://example.org', 'username' => 'test', 'password' => 'test']);
    }

    protected function tearDown(): void
    {
        Configure::write('DistrictCoreData', $this->coreConfig);
        parent::tearDown();
    }

    public function testInheritanceAndOverridesAcrossGenerations(): void
    {
        $rows = [
            $this->team('grandchild', 'child'),
            $this->team('child', 'root'),
            $this->team('root', null, 'g1', 's1'),
            $this->team('different-group', 'root', 'g2'),
            $this->team('explicit-section', 'root', null, 's2'),
            $this->team('same-group', 'root', 'g1'),
        ];
        $changes = array_column((new TeamScopeBackfill())->plan($rows, ['s1' => 'g1', 's2' => 'g2'], ['g1', 'g2']), null, 'id');
        foreach (['child', 'grandchild', 'same-group'] as $id) {
            $this->assertSame('g1', $changes[$id]['group_id']);
            $this->assertSame('s1', $changes[$id]['section_id']);
        }
        $this->assertArrayNotHasKey('root', $changes);
        $this->assertArrayNotHasKey('different-group', $changes);
        $this->assertSame('g2', $changes['explicit-section']['group_id']);
        $this->assertSame('s2', $changes['explicit-section']['section_id']);
    }

    public function testInvalidHierarchiesAreRejected(): void
    {
        $cases = [
            [[$this->team('root')], 'root'],
            [[$this->team('child', 'missing')], 'Missing parent'],
            [[$this->team('a', 'b'), $this->team('b', 'a')], 'Cycle'],
            [[$this->team('bad-section', null, 'g1', 'unknown')], 'unknown section'],
            [[$this->team('conflict', null, 'g2', 's1')], 'different group'],
            [[$this->team('bad-group', null, 'unknown')], 'valid group'],
        ];
        foreach ($cases as [$teams, $message]) {
            try {
                (new TeamScopeBackfill())->plan($teams, ['s1' => 'g1'], ['g1', 'g2']);
                $this->fail('Invalid hierarchy accepted');
            } catch (RuntimeException $e) {
                $this->assertStringContainsString($message, $e->getMessage());
            }
        }
    }

    public function testLegacyDatabaseDryRunAtomicityAndMigration(): void
    {
        $db = new Connection(['driver' => Sqlite::class, 'database' => ':memory:']);
        $db->execute('PRAGMA foreign_keys = ON');
        $db->execute('CREATE TABLE groups (id CHAR(36) PRIMARY KEY)');
        $db->execute('CREATE TABLE sections (id CHAR(36) PRIMARY KEY, group_id CHAR(36), UNIQUE(id, group_id))');
        $db->execute('CREATE TABLE teams (id CHAR(36) PRIMARY KEY, team_name TEXT, team_parent_id CHAR(36), group_id CHAR(36), section_id CHAR(36), FOREIGN KEY(group_id) REFERENCES groups(id), FOREIGN KEY(section_id, group_id) REFERENCES sections(id, group_id))');
        $db->insert('groups', ['id' => 'g1']);
        $db->insert('sections', ['id' => 's1', 'group_id' => 'g1']);
        $db->insert('teams', $this->team('root', null, 'g1', 's1'));
        $db->insert('teams', $this->team('child', 'root'));
        $db->insert('teams', $this->team('unresolved'));
        $service = new TeamScopeBackfill();
        try {
            $service->run($db);
            $this->fail('Unresolved group accepted');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('unresolved', $e->getMessage());
        }
        $this->assertNull($db->execute("SELECT group_id FROM teams WHERE id = 'child'")->fetch('assoc')['group_id']);
        $db->delete('teams', ['id' => 'unresolved']);
        $this->assertCount(1, $service->run($db, true));
        $this->assertNull($db->execute("SELECT group_id FROM teams WHERE id = 'child'")->fetch('assoc')['group_id']);

        require_once ROOT . '/config/Migrations/20260905040000_RequireTeamGroup.php';
        $migration = $this->getMockBuilder(RequireTeamGroup::class)
            ->onlyMethods(['fetchCoreData', 'importCoreData'])->getMock();
        $migration->expects($this->once())->method('fetchCoreData')->willReturn(['groups' => [], 'sections' => []]);
        $migration->expects($this->once())->method('importCoreData')->willReturn('g1');
        $migration->setAdapter(new SqliteAdapter(['connection' => $db]));
        $migration->up();
        $child = $db->execute("SELECT group_id, section_id FROM teams WHERE id = 'child'")->fetch('assoc');
        $this->assertSame(['group_id' => 'g1', 'section_id' => 's1'], $child);
        $this->assertSame([], $service->run($db));
        $columns = array_column($db->execute('PRAGMA table_info(teams)')->fetchAll('assoc'), null, 'name');
        $this->assertSame(1, $columns['group_id']['notnull']);
        $this->assertCount(3, $db->execute('PRAGMA foreign_key_list(teams)')->fetchAll('assoc'));
        $migration->down();
        $columns = array_column($db->execute('PRAGMA table_info(teams)')->fetchAll('assoc'), null, 'name');
        $this->assertSame(0, $columns['group_id']['notnull']);
        $this->assertSame($child, $db->execute("SELECT group_id, section_id FROM teams WHERE id = 'child'")->fetch('assoc'));
    }

    public function testMigrationImportsCoreDataAndDefaultsLegacyTeamsToDistrict(): void
    {
        $db = new Connection(['driver' => Sqlite::class, 'database' => ':memory:']);
        $db->execute('PRAGMA foreign_keys = ON');
        $db->execute('CREATE TABLE teams (id CHAR(36) PRIMARY KEY, team_name TEXT, team_parent_id CHAR(36))');
        $adapter = new SqliteAdapter(['connection' => $db]);
        require_once ROOT . '/config/Migrations/20260905010000_CreateGroupsAndSections.php';
        require_once ROOT . '/config/Migrations/20260905030000_AddGroupTypeAndDomains.php';
        require_once ROOT . '/config/Migrations/20260905040000_RequireTeamGroup.php';
        foreach ([new CreateGroupsAndSections(), new AddGroupTypeAndDomains()] as $migration) {
            $migration->setAdapter($adapter);
            $migration->change();
        }
        $district = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';
        $group = 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb';
        $section = 'cccccccc-cccc-4ccc-8ccc-cccccccccccc';
        $data = ['groups' => [
            ['id' => $district, 'group_name' => 'District', 'sort_order' => 1,
                'type' => 'district', 'domains' => ['district.example.org']],
            ['id' => $group, 'group_name' => 'Scout Group', 'sort_order' => 1,
                'type' => 'group', 'domains' => ['group.example.org']],
        ], 'sections' => [
            ['id' => $section, 'group_id' => $group, 'section_id' => 123,
                'section_name' => 'Cubs', 'section_type' => 'cubs'],
        ]];
        foreach ([$this->team('root'), $this->team('child', 'root'), $this->team('grandchild', 'child')] as $team) {
            $db->insert('teams', $team);
        }
        $invalidImport = $data;
        $invalidImport['sections'][0]['section_type'] = 'invalid';
        $ambiguousDistrict = $data;
        $ambiguousDistrict['groups'][1]['type'] = 'district';
        $missingDistrict = $data;
        $missingDistrict['groups'][0]['type'] = 'group';
        foreach ([$invalidImport, $ambiguousDistrict, $missingDistrict] as $invalidData) {
            $failedMigration = $this->getMockBuilder(RequireTeamGroup::class)
                ->onlyMethods(['fetchCoreData'])->getMock();
            $failedMigration->expects($this->once())->method('fetchCoreData')->willReturn($invalidData);
            $failedMigration->setAdapter($adapter);
            try {
                $failedMigration->up();
                $this->fail('Invalid import accepted');
            } catch (RuntimeException $exception) {
                $this->assertNotEmpty($exception->getMessage());
            }
            $this->assertSame(0, (int)$db->execute('SELECT COUNT(*) FROM groups')->fetchColumn(0));
            $this->assertSame(3, (int)$db->execute('SELECT COUNT(*) FROM teams WHERE group_id IS NULL')->fetchColumn(0));
        }
        $migration = $this->getMockBuilder(RequireTeamGroup::class)->onlyMethods(['fetchCoreData'])->getMock();
        $migration->expects($this->once())->method('fetchCoreData')->willReturn($data);
        $migration->setAdapter($adapter);
        $migration->up();
        $this->assertSame(2, (int)$db->execute('SELECT COUNT(*) FROM groups')->fetchColumn(0));
        $this->assertSame(1, (int)$db->execute('SELECT COUNT(*) FROM sections')->fetchColumn(0));
        foreach ($db->execute('SELECT group_id, section_id FROM teams')->fetchAll('assoc') as $team) {
            $this->assertSame($district, $team['group_id']);
            $this->assertNull($team['section_id']);
        }
        $columns = array_column($db->execute('PRAGMA table_info(teams)')->fetchAll('assoc'), null, 'name');
        $this->assertSame(1, $columns['group_id']['notnull']);
    }

    private function team(string $id, ?string $parent = null, ?string $group = null, ?string $section = null): array
    {
        return ['id' => $id, 'team_name' => $id, 'team_parent_id' => $parent, 'group_id' => $group, 'section_id' => $section];
    }
}
