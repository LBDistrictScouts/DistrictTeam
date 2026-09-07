<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\RolesTable;
use Cake\I18n\Date;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\RolesTable Test Case
 */
class RolesTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\RolesTable
     */
    protected $Roles;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Groups', 'app.Teams',
        'app.Roles',
        'app.Members',
        'app.MemberContactMethods',
        'app.Appointments',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('Roles') ? [] : ['className' => RolesTable::class];
        $this->Roles = $this->getTableLocator()->get('Roles', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->Roles);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     * @link \App\Model\Table\RolesTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $role = $this->Roles->newEntity([
            'team_id' => '11111111-1111-4111-8111-111111111111',
            'name' => 'Programme Lead',
            'currently_filled' => false,
            'is_lead' => false,
        ]);
        $this->assertEmpty($role->getErrors());
        $this->assertSame('programme-lead', $role->slug);

        $invalid = $this->Roles->newEntity([
            'team_id' => 'invalid',
            'name' => '',
        ]);
        $this->assertArrayHasKey('team_id', $invalid->getErrors());
        $this->assertArrayHasKey('name', $invalid->getErrors());
    }

    /**
     * Test buildRules method
     *
     * @return void
     * @link \App\Model\Table\RolesTable::buildRules()
     */
    public function testBuildRules(): void
    {
        $role = $this->Roles->newEntity([
            'team_id' => '99999999-9999-4999-8999-999999999999',
            'name' => 'Missing Team',
            'currently_filled' => false,
        ]);

        $this->assertFalse($this->Roles->save($role));
        $this->assertArrayHasKey('team_id', $role->getErrors());
    }

    public function testRoleNamesAndSlugsMustBeUniqueWithinTheirGroup(): void
    {
        $duplicateRole = $this->Roles->newEntity([
            'team_id' => '11111111-1111-4111-8111-111111111111',
            'name' => 'Digital Lead',
            'currently_filled' => false,
        ]);
        $this->assertFalse($this->Roles->save($duplicateRole));
        $this->assertArrayHasKey('name', $duplicateRole->getErrors());
        $this->assertArrayHasKey('slug', $duplicateRole->getErrors());

        $duplicateTeam = $this->Roles->newEntity([
            'team_id' => '11111111-1111-4111-8111-111111111111',
            'name' => 'District Team',
            'currently_filled' => false,
        ]);
        $this->assertFalse($this->Roles->save($duplicateTeam));
        $this->assertArrayHasKey('slug', $duplicateTeam->getErrors());

        $team = $this->Roles->Teams->saveOrFail($this->Roles->Teams->newEntity([
            'group_id' => 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb',
            'team_name' => 'Group Leadership Team',
        ]));
        $sameRoleInAnotherGroup = $this->Roles->newEntity([
            'team_id' => $team->id,
            'name' => 'Digital Lead',
            'currently_filled' => false,
            'is_lead' => false,
        ]);
        $this->assertNotFalse($this->Roles->save($sameRoleInAnotherGroup));
        $this->assertSame('bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', $sameRoleInAnotherGroup->group_id);
    }

    public function testExistingRoleCanBeSavedWithItsOwnSlug(): void
    {
        $role = $this->Roles->get('22222222-2222-4222-8222-222222222221');
        $role->name = 'Digital Lead';

        $this->assertNotFalse($this->Roles->save($role));
    }

    public function testTeamCanHaveOnlyOneLeadRole(): void
    {
        $role = $this->Roles->newEntity([
            'team_id' => '11111111-1111-4111-8111-111111111112',
            'name' => 'Second Digital Lead',
            'currently_filled' => false,
            'is_lead' => true,
        ]);

        $this->assertFalse($this->Roles->save($role));
        $this->assertArrayHasKey('is_lead', $role->getErrors());
    }

    public function testCurrentAppointmentsAssociation(): void
    {
        $role = $this->Roles->get(
            '22222222-2222-4222-8222-222222222221',
            contain: ['CurrentAppointments.Members'],
        );

        $this->assertCount(1, $role->current_appointments);
        $this->assertSame('Ada', $role->current_appointments[0]->member->first_name);
    }

    public function testStaffingStatusDistinguishesRecruitingRoles(): void
    {
        $filledRole = $this->Roles->get('22222222-2222-4222-8222-222222222221');
        $vacantRole = $this->Roles->get('22222222-2222-4222-8222-222222222222');

        $this->assertSame('filled', $filledRole->staffing_status);
        $this->assertSame('vacant', $vacantRole->staffing_status);

        $vacantRole->is_covered_until = new Date('2099-01-01');
        $this->Roles->saveOrFail($vacantRole);
        $vacantRole = $this->Roles->get($vacantRole->id);
        $this->assertSame('2099-01-01', $vacantRole->is_covered_until->format('Y-m-d'));
        $this->assertSame('covered', $vacantRole->staffing_status);

        $filledRole->multi_member_role = true;
        $vacantRole->multi_member_role = true;

        $this->assertSame('recruiting', $filledRole->staffing_status);
        $this->assertSame('vacant', $vacantRole->staffing_status);
    }
}
