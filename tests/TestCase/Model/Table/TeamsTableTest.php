<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\TeamsTable;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;

/**
 * App\Model\Table\TeamsTable Test Case
 */
class TeamsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\TeamsTable
     */
    protected $Teams;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Groups',
        'app.Sections',
        'app.Teams',
        'app.Roles',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('Teams') ? [] : ['className' => TeamsTable::class];
        $this->Teams = $this->getTableLocator()->get('Teams', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->Teams);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     * @link \App\Model\Table\TeamsTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $team = $this->Teams->newEntity(['group_id' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', 'team_name' => 'Operations Team']);
        $this->assertEmpty($team->getErrors());
        $this->assertSame('operations-team', $team->slug);

        $invalid = $this->Teams->newEntity([
            'team_name' => '',
            'team_parent_id' => 'invalid',
        ]);
        $this->assertArrayHasKey('team_name', $invalid->getErrors());
        $this->assertArrayHasKey('team_parent_id', $invalid->getErrors());
    }

    /**
     * Test buildRules method
     *
     * @return void
     * @link \App\Model\Table\TeamsTable::buildRules()
     */
    public function testBuildRules(): void
    {
        $team = $this->Teams->newEntity([
            'group_id' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', 'team_name' => 'Orphan',
            'team_parent_id' => '99999999-9999-4999-8999-999999999999',
        ]);

        $this->assertFalse($this->Teams->save($team));
        $this->assertArrayHasKey('team_parent_id', $team->getErrors());
    }

    public function testTeamNamesAndSlugsMustBeUniqueWithinTheirGroup(): void
    {
        $duplicateTeam = $this->Teams->newEntity(['group_id' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', 'team_name' => 'District Team']);
        $this->assertFalse($this->Teams->save($duplicateTeam));
        $this->assertArrayHasKey('team_name', $duplicateTeam->getErrors());
        $this->assertArrayHasKey('slug', $duplicateTeam->getErrors());

        $sameNameInAnotherGroup = $this->Teams->newEntity([
            'group_id' => 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', 'team_name' => 'District Team',
        ]);
        $this->assertNotFalse($this->Teams->save($sameNameInAnotherGroup));
    }

    public function testTeamSlugMustNotConflictWithARoleSlug(): void
    {
        $duplicateRole = $this->Teams->newEntity(['group_id' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', 'team_name' => 'Digital Lead']);
        $this->assertFalse($this->Teams->save($duplicateRole));
        $this->assertArrayHasKey('slug', $duplicateRole->getErrors());
    }

    public function testExistingTeamCanBeSavedWithItsOwnSlug(): void
    {
        $team = $this->Teams->get('11111111-1111-4111-8111-111111111111');
        $team->team_name = 'District Team';

        $this->assertNotFalse($this->Teams->save($team));
    }

    public function testTreeConfigurationAndAssociations(): void
    {
        $tree = $this->Teams->getBehavior('Tree');

        $this->assertSame('team_parent_id', $tree->getConfig('parent'));
        $this->assertSame('tree_left', $tree->getConfig('left'));
        $this->assertSame('tree_right', $tree->getConfig('right'));
        $this->assertSame('tree_level', $tree->getConfig('level'));
        $this->assertTrue($this->Teams->hasAssociation('ParentTeam'));
        $this->assertTrue($this->Teams->hasAssociation('SubTeams'));
        $this->assertTrue($this->Teams->hasAssociation('Roles'));
        $this->assertTrue($this->Teams->hasAssociation('TeamLead'));

        $teamLead = $this->Teams->get(
            '11111111-1111-4111-8111-111111111112',
            contain: ['TeamLead'],
        )->team_lead;
        $this->assertNotNull($teamLead);
        $this->assertSame('Digital Lead', $teamLead->name);
    }

    /**
     * @return void
     */
    public function testDistrictGroupAndSectionTeamRelationships(): void
    {
        foreach (
            [
            ['group_id' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', 'section_id' => null],
            ['group_id' => 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', 'section_id' => null],
            ['group_id' => 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb',
                'section_id' => 'cccccccc-cccc-4ccc-8ccc-cccccccccccc'],
            ] as $i => $links
        ) {
            $team = $this->Teams->newEntity(['team_name' => 'Leadership ' . $i] + $links);
            $this->Teams->saveOrFail($team);
            $saved = $this->Teams->get($team->id, contain: ['Groups', 'Sections']);
            $this->assertSame($links['group_id'], $saved->group->id);
            $this->assertSame($links['section_id'], $saved->section?->id);
        }
    }

    /**
     * @return void
     */
    public function testSectionRequiresItsOwnGroup(): void
    {
        foreach ([null, 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa'] as $groupId) {
            $team = $this->Teams->newEntity([
                'team_name' => 'Invalid Team', 'group_id' => $groupId,
                'section_id' => 'cccccccc-cccc-4ccc-8ccc-cccccccccccc',
            ]);
            $this->assertFalse($this->Teams->save($team));
            $this->assertArrayHasKey($groupId === null ? 'group_id' : 'section_id', $team->getErrors());
        }
        $team = $this->Teams->newEntity([
            'team_name' => 'Valid Team', 'group_id' => 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb',
            'section_id' => 'cccccccc-cccc-4ccc-8ccc-cccccccccccc',
        ]);
        $this->Teams->saveOrFail($team);
        $this->Teams->patchEntity($team, ['group_id' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa']);
        $this->assertFalse($this->Teams->save($team));
        $this->Teams->patchEntity($team, ['section_id' => '']);
        $this->assertNotFalse($this->Teams->save($team));
        $this->assertNull($this->Teams->get($team->id)->section_id);
    }

    /**
     * @return void
     */
    public function testGroupCounterCachesFollowRelatedRecords(): void
    {
        $groupId = 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb';
        $groups = $this->fetchTable('Groups');
        $sections = $this->fetchTable('Sections');
        $roles = $this->fetchTable('Roles');

        $section = $sections->saveOrFail($sections->newEntity([
            'group_id' => $groupId,
            'section_osm_id' => 124,
            'section_name' => 'First Scouts',
            'section_type' => 'scouts',
        ]));
        $this->assertSame(2, $groups->get($groupId)->sections_count);
        $section->group_id = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';
        $sections->saveOrFail($section);
        $this->assertSame(1, $groups->get($groupId)->sections_count);
        $this->assertSame(1, $groups->get('aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa')->sections_count);
        $section->group_id = $groupId;
        $sections->saveOrFail($section);

        $team = $this->Teams->saveOrFail($this->Teams->newEntity([
            'group_id' => $groupId,
            'section_id' => $section->id,
            'team_name' => 'First Scouts Leadership',
        ]));
        $this->assertSame(1, $groups->get($groupId)->teams_count);

        $role = $roles->saveOrFail($roles->newEntity([
            'team_id' => $team->id,
            'name' => 'Scout Leader',
            'currently_filled' => false,
            'is_lead' => false,
        ]));
        $this->assertSame(1, $groups->get($groupId)->roles_count);

        $team->group_id = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';
        $team->section_id = null;
        $this->Teams->saveOrFail($team);
        $this->assertSame(0, $groups->get($groupId)->teams_count);
        $this->assertSame(0, $groups->get($groupId)->roles_count);
        $this->assertSame(3, $groups->get('aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa')->teams_count);
        $this->assertSame(3, $groups->get('aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa')->roles_count);

        $team->group_id = $groupId;
        $team->section_id = $section->id;
        $this->Teams->saveOrFail($team);

        $roles->deleteOrFail($role);
        $this->Teams->deleteOrFail($team);
        $sections->deleteOrFail($section);
        $group = $groups->get($groupId);
        $this->assertSame(1, $group->sections_count);
        $this->assertSame(0, $group->teams_count);
        $this->assertSame(0, $group->roles_count);
    }

    /**
     * @return void
     */
    public function testSiblingOrderAndNewTeamAppend(): void
    {
        $parentId = '11111111-1111-4111-8111-111111111111';
        $digitalId = '11111111-1111-4111-8111-111111111112';
        $sibling = $this->Teams->saveOrFail($this->Teams->newEntity([
            'group_id' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', 'team_name' => 'Support Team', 'team_parent_id' => $parentId,
        ]));
        $this->Teams->saveOrder([$parentId, $sibling->id, $digitalId]);
        $parent = $this->Teams->get($parentId, contain: ['SubTeams']);
        $this->assertSame([$sibling->id, $digitalId], array_column($parent->sub_teams, 'id'));
        $new = $this->Teams->saveOrFail($this->Teams->newEntity([
            'group_id' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', 'team_name' => 'New Team', 'team_parent_id' => $parentId,
        ]));
        $this->assertSame(4, $new->sort_order);
        $this->assertGreaterThan($this->Teams->get($digitalId)->tree_right, $new->tree_left);
        $this->assertSame('Digital Lead', $this->Teams->get($digitalId, contain: ['Roles'])->roles[0]->name);
    }

    /**
     * @return void
     */
    public function testDuplicateTeamOrderIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->Teams->saveOrder([
            '11111111-1111-4111-8111-111111111111',
            '11111111-1111-4111-8111-111111111111',
        ]);
    }

    /**
     * @return void
     */
    public function testTreeCoordinatesCannotBePatchedOrSerialized(): void
    {
        $team = $this->Teams->get('11111111-1111-4111-8111-111111111112');
        $fields = ['tree_left', 'tree_right', 'tree_level'];
        $before = $team->extract($fields);
        $this->Teams->patchEntity($team, array_fill_keys($fields, 999));
        $this->assertSame($before, $team->extract($fields));
        $json = json_decode(json_encode($team, JSON_THROW_ON_ERROR), true);
        foreach ($fields as $field) {
            $this->assertArrayNotHasKey($field, $json);
        }
    }
}
