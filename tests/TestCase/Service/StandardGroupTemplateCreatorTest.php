<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Model\Enum\SectionType;
use App\Service\StandardGroupTemplateCreator;
use Cake\TestSuite\TestCase;

class StandardGroupTemplateCreatorTest extends TestCase
{
    protected array $fixtures = ['app.Groups', 'app.Sections', 'app.Teams', 'app.Roles', 'app.CsvUnitMappings'];

    public function testRunCreatesMappedSectionTeamsAndStandardRolesIdempotently(): void
    {
        $mappings = $this->fetchTable('CsvUnitMappings');
        $mappings->saveOrFail($mappings->newEntity([
            'source_key' => str_repeat('a', 64), 'source_unit' => 'First Cubs', 'source_parent_unit' => '',
            'group_id' => 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', 'section_id' => 'cccccccc-cccc-4ccc-8ccc-cccccccccccc',
        ]));
        $sections = $this->fetchTable('Sections');
        $squirrels = $sections->saveOrFail($sections->newEntity([
            'group_id' => 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb',
            'section_osm_id' => 456, 'section_name' => 'First Squirrels', 'section_type' => 'earlyyears',
        ]));
        $this->assertSame(SectionType::EarlyYears, $sections->get($squirrels->id)->section_type);
        $mappings->saveOrFail($mappings->newEntity([
            'source_key' => str_repeat('b', 64), 'source_unit' => 'First Squirrels', 'source_parent_unit' => '',
            'group_id' => 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', 'section_id' => $squirrels->id,
        ]));

        $result = (new StandardGroupTemplateCreator())->run();

        $this->assertSame(['teams' => 6, 'roles' => 14], $result);
        $teams = $this->fetchTable('Teams');
        $leadership = $teams->find()->where(['team_name' => 'First Scout Group Leadership Team'])->firstOrFail();
        $cubs = $teams->find()->where(['team_name' => 'First Scout Group Cubs'])->firstOrFail();
        $this->assertSame('bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', $leadership->group_id);
        $this->assertSame('cccccccc-cccc-4ccc-8ccc-cccccccccccc', $cubs->section_id);
        $this->assertSame($leadership->id, $cubs->team_parent_id);
        $this->assertTrue($teams->exists(['team_name' => 'First Scout Group Squirrels', 'template' => 'squirrel-section']));
        $trusteeBoard = $teams->find()->where([
            'group_id' => 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb',
            'team_name' => 'Trustee Board',
        ])->firstOrFail();
        $this->assertSame($leadership->id, $trusteeBoard->team_parent_id);
        $this->assertTrue($this->fetchTable('Roles')->exists(['team_id' => $cubs->id, 'name' => 'First Scout Group Cubs Team Leader', 'is_lead' => true]));
        $this->assertTrue($this->fetchTable('Roles')->exists(['team_id' => $cubs->id, 'name' => 'First Scout Group Cubs Team Member', 'multi_member_role' => true]));
        $this->assertTrue($this->fetchTable('Roles')->exists([
            'name' => 'Group Lead Volunteer', 'template' => 'lead-volunteer', 'is_trustee_role' => true,
        ]));
        $this->assertTrue($this->fetchTable('Roles')->exists([
            'name' => 'Group Leadership Team Member', 'template' => 'leadership-team-member',
        ]));
        $this->assertTrue($this->fetchTable('Roles')->exists([
            'group_id' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',
            'name' => 'District Lead Volunteer',
            'template' => 'lead-volunteer',
            'is_trustee_role' => true,
        ]));
        $this->assertTrue($this->fetchTable('Roles')->exists([
            'name' => 'Trustee Board Chair', 'is_trustee_role' => true,
        ]));
        $this->assertTrue($this->fetchTable('Roles')->exists([
            'name' => 'Group Treasurer', 'template' => 'treasurer', 'is_trustee_role' => true,
        ]));
        $this->assertFalse($this->fetchTable('Roles')->exists([
            'name' => 'First Scout Group Cubs Team Leader', 'is_trustee_role' => true,
        ]));

        $cubs->team_name = 'Cubs Team';
        $teams->saveOrFail($cubs);
        $leader = $this->fetchTable('Roles')->find()->where(['name' => 'First Scout Group Cubs Team Leader'])->firstOrFail();
        $leader->name = 'Cubs Team Leader';
        $this->fetchTable('Roles')->saveOrFail($leader);

        $again = (new StandardGroupTemplateCreator())->run();
        $this->assertSame(['teams' => 0, 'roles' => 0], $again);
        $this->assertSame(1, $teams->find()->where(['template' => 'cub-section'])->count());
        $this->assertSame(1, $this->fetchTable('Roles')->find()->where(['template' => 'cub-section-team-leader'])->count());

        $creator = new StandardGroupTemplateCreator();
        $teamOverrides = $creator->teamPlan(true);
        $this->assertCount(1, $teamOverrides);
        $this->assertSame('Cubs Team', $teamOverrides[0]['existing_name']);
        $creator->createTeams([['team_name' => 'First Scout Group Cubs', 'apply' => true]], true);
        $this->assertSame('First Scout Group Cubs', $teams->get($cubs->id)->team_name);

        $roleOverrides = $creator->rolePlan(true);
        $this->assertCount(1, $roleOverrides);
        $this->assertSame('Cubs Team Leader', $roleOverrides[0]['existing_name']);
        $creator->createRoles([['role_name' => 'First Scout Group Cubs Team Leader', 'apply' => true]], true);
        $this->assertSame('First Scout Group Cubs Team Leader', $this->fetchTable('Roles')->get($leader->id)->name);

        $leader->is_trustee_role = true;
        $this->fetchTable('Roles')->saveOrFail($leader);
        $roleOverrides = (new StandardGroupTemplateCreator())->rolePlan(true);
        $this->assertCount(1, $roleOverrides);
        $this->assertSame('First Scout Group Cubs Team Leader', $roleOverrides[0]['role_name']);
        (new StandardGroupTemplateCreator())->createRoles([
            ['role_name' => 'First Scout Group Cubs Team Leader', 'apply' => true],
        ], true);
        $this->assertFalse($this->fetchTable('Roles')->get($leader->id)->is_trustee_role);
    }

    public function testCreateRolesCreatesMissingDistrictTeamsAndRoles(): void
    {
        $teams = $this->fetchTable('Teams');
        $districtLeadershipTeam = $teams->saveOrFail($teams->newEntity([
            'group_id' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',
            'team_name' => 'District Leadership Team',
        ]));
        $creator = new StandardGroupTemplateCreator();
        $roles = $creator->rolePlan();
        $districtRoles = array_values(array_filter(
            $roles,
            static fn(array $role): bool => $role['group_id'] === 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',
        ));

        $this->assertCount(5, $districtRoles);
        $this->assertSame([
            'District Lead Volunteer',
            'District Leadership Team Member',
            'Trustee Board Chair',
            'District Treasurer',
            'Trustee Board Member',
        ], array_column($districtRoles, 'role_name'));

        $created = $creator->createRoles(array_map(
            static fn(array $role): array => ['role_name' => $role['role_name']],
            $roles,
        ));

        $this->assertSame(10, $created);
        $this->assertTrue($teams->exists([
            'group_id' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', 'team_name' => 'District Leadership Team',
        ]));
        $this->assertTrue($teams->exists([
            'group_id' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', 'team_name' => 'Trustee Board',
        ]));
        $this->assertTrue($this->fetchTable('Roles')->exists([
            'group_id' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',
            'team_id' => $districtLeadershipTeam->id,
            'name' => 'District Lead Volunteer',
            'template' => 'lead-volunteer',
        ]));
        $this->assertTrue($this->fetchTable('Roles')->exists([
            'group_id' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',
            'name' => 'Trustee Board Chair',
            'template' => 'trustee-board-chair',
        ]));
        $this->assertTrue($this->fetchTable('Roles')->exists([
            'group_id' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',
            'name' => 'Trustee Board Member',
            'template' => 'trustee-board-member',
        ]));
    }
}
