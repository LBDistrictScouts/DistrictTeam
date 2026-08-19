<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\RolesTable;
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

    public function testSlugMustBeUniqueAcrossRolesAndTeams(): void
    {
        $duplicateRole = $this->Roles->newEntity([
            'team_id' => '11111111-1111-4111-8111-111111111111',
            'name' => 'Digital Lead',
            'currently_filled' => false,
        ]);
        $this->assertFalse($this->Roles->save($duplicateRole));
        $this->assertArrayHasKey('slug', $duplicateRole->getErrors());

        $duplicateTeam = $this->Roles->newEntity([
            'team_id' => '11111111-1111-4111-8111-111111111111',
            'name' => 'District Team',
            'currently_filled' => false,
        ]);
        $this->assertFalse($this->Roles->save($duplicateTeam));
        $this->assertArrayHasKey('slug', $duplicateTeam->getErrors());
    }

    public function testExistingRoleCanBeSavedWithItsOwnSlug(): void
    {
        $role = $this->Roles->get('22222222-2222-4222-8222-222222222221');
        $role->name = 'Digital Lead';

        $this->assertNotFalse($this->Roles->save($role));
    }
}
