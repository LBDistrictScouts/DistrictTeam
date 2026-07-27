<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\AppointmentsTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\AppointmentsTable Test Case
 */
class AppointmentsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\AppointmentsTable
     */
    protected $Appointments;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Teams',
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
        $config = $this->getTableLocator()->exists('Appointments') ? [] : ['className' => AppointmentsTable::class];
        $this->Appointments = $this->getTableLocator()->get('Appointments', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->Appointments);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     * @link \App\Model\Table\AppointmentsTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $appointment = $this->Appointments->newEntity([
            'role_id' => '22222222-2222-4222-8222-222222222222',
            'member_id' => '33333333-3333-4333-8333-333333333332',
            'member_contact_method_id' => '44444444-4444-4444-8444-444444444442',
            'effective_start_date' => '2020-01-01',
            'active' => true,
        ]);
        $this->assertEmpty($appointment->getErrors());

        $invalid = $this->Appointments->newEntity([
            'role_id' => 'invalid',
            'member_id' => 'invalid',
            'member_contact_method_id' => 'invalid',
            'effective_start_date' => 'invalid',
        ]);
        $this->assertArrayHasKey('role_id', $invalid->getErrors());
        $this->assertArrayHasKey('member_id', $invalid->getErrors());
        $this->assertArrayHasKey('member_contact_method_id', $invalid->getErrors());
        $this->assertArrayHasKey('effective_start_date', $invalid->getErrors());
    }

    /**
     * Test buildRules method
     *
     * @return void
     * @link \App\Model\Table\AppointmentsTable::buildRules()
     */
    public function testBuildRules(): void
    {
        $duplicate = $this->Appointments->newEntity([
            'role_id' => '22222222-2222-4222-8222-222222222221',
            'member_id' => '33333333-3333-4333-8333-333333333331',
            'member_contact_method_id' => '44444444-4444-4444-8444-444444444441',
            'effective_start_date' => '2020-01-01',
            'active' => true,
        ]);

        $this->assertFalse($this->Appointments->save($duplicate));
        $this->assertArrayHasKey('role_id', $duplicate->getErrors());
    }

    public function testLifecycleSynchronizesRoleOccupancy(): void
    {
        $roles = $this->Appointments->Roles;
        $roleId = '22222222-2222-4222-8222-222222222222';
        $appointment = $this->Appointments->newEntity([
            'role_id' => $roleId,
            'member_id' => '33333333-3333-4333-8333-333333333332',
            'member_contact_method_id' => '44444444-4444-4444-8444-444444444442',
            'effective_start_date' => '2020-01-01',
            'active' => true,
        ]);
        $appointment = $this->Appointments->saveOrFail($appointment);
        $this->assertTrue($roles->get($roleId)->currently_filled);

        $appointment->effective_end_date = '2021-01-01';
        $this->Appointments->saveOrFail($appointment);
        $this->assertFalse($roles->get($roleId)->currently_filled);

        $appointment->effective_end_date = null;
        $this->Appointments->saveOrFail($appointment);
        $this->assertTrue($roles->get($roleId)->currently_filled);

        $this->Appointments->deleteOrFail($appointment);
        $this->assertFalse($roles->get($roleId)->currently_filled);
    }
}
