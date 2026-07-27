<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\TeamsTable;
use Cake\TestSuite\TestCase;

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
        'app.Teams',
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
        $team = $this->Teams->newEntity(['team_name' => 'Operations Team']);
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
            'team_name' => 'Orphan',
            'team_parent_id' => '99999999-9999-4999-8999-999999999999',
        ]);

        $this->assertFalse($this->Teams->save($team));
        $this->assertArrayHasKey('team_parent_id', $team->getErrors());
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
    }
}
