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

        $this->assertSame(['teams' => 4, 'roles' => 9], $result);
        $teams = $this->fetchTable('Teams');
        $leadership = $teams->find()->where(['team_name' => 'First Scout Group Leadership Team'])->firstOrFail();
        $cubs = $teams->find()->where(['team_name' => 'First Scout Group Cubs'])->firstOrFail();
        $this->assertSame('bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', $leadership->group_id);
        $this->assertSame('cccccccc-cccc-4ccc-8ccc-cccccccccccc', $cubs->section_id);
        $this->assertSame($leadership->id, $cubs->team_parent_id);
        $this->assertTrue($teams->exists(['team_name' => 'First Scout Group Squirrels', 'template' => 'squirrel-section']));
        $trusteeBoard = $teams->find()->where(['team_name' => 'Trustee Board'])->firstOrFail();
        $this->assertSame($leadership->id, $trusteeBoard->team_parent_id);
        $this->assertTrue($this->fetchTable('Roles')->exists(['team_id' => $cubs->id, 'name' => 'First Scout Group Cubs Team Leader', 'is_lead' => true]));
        $this->assertTrue($this->fetchTable('Roles')->exists(['team_id' => $cubs->id, 'name' => 'First Scout Group Cubs Team Member', 'multi_member_role' => true]));
        $this->assertTrue($this->fetchTable('Roles')->exists([
            'name' => 'Group Lead Volunteer', 'is_trustee_role' => true,
        ]));
        $this->assertTrue($this->fetchTable('Roles')->exists([
            'name' => 'Trustee Board Chair', 'is_trustee_role' => true,
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
    }
}
