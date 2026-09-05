<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\MembersTable;
use Cake\I18n\Date;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\MembersTable Test Case
 */
class MembersTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\MembersTable
     */
    protected $Members;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Members',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('Members') ? [] : ['className' => MembersTable::class];
        $this->Members = $this->getTableLocator()->get('Members', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->Members);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     * @link \App\Model\Table\MembersTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $valid = $this->Members->newEntity([
            'first_name' => 'Katherine',
            'last_name' => 'Johnson',
            'membership_number' => 2001,
            'join_date' => '2020-01-01',
        ]);
        $this->assertEmpty($valid->getErrors());

        $invalid = $this->Members->newEntity([
            'first_name' => '',
            'last_name' => '',
            'membership_number' => null,
            'join_date' => 'not-a-date',
        ]);
        $this->assertArrayHasKey('first_name', $invalid->getErrors());
        $this->assertArrayHasKey('last_name', $invalid->getErrors());
        $this->assertArrayHasKey('membership_number', $invalid->getErrors());
        $this->assertArrayHasKey('join_date', $invalid->getErrors());
    }

    /**
     * Test buildRules method
     *
     * @return void
     * @link \App\Model\Table\MembersTable::buildRules()
     */
    public function testBuildRules(): void
    {
        $member = $this->Members->newEntity([
            'first_name' => 'Duplicate',
            'last_name' => 'Member',
            'membership_number' => 1001,
            'join_date' => '2020-01-01',
        ]);

        $this->assertFalse($this->Members->save($member));
        $this->assertArrayHasKey('membership_number', $member->getErrors());
    }

    public function testFullNameVirtualPropertyAndDisplayField(): void
    {
        $member = $this->Members->get('33333333-3333-4333-8333-333333333331');

        $this->assertSame('Ada Lovelace', $member->full_name);
        $this->assertSame('Ada Lovelace', $member->toArray()['full_name']);
        $this->assertSame('full_name', $this->Members->getDisplayField());
    }

    public function testActiveIsDerivedFromMembershipDates(): void
    {
        $member = $this->Members->newEntity([
            'first_name' => 'Active',
            'last_name' => 'Member',
            'membership_number' => 2002,
            'join_date' => Date::today()->subDays(1),
            'leave_date' => null,
        ]);
        $this->assertTrue($member->active);

        $member->leave_date = Date::today()->subDays(1);
        $this->assertFalse($member->active);

        $member->join_date = Date::today()->addDays(1);
        $member->leave_date = null;
        $this->assertFalse($member->active);
    }

    public function testMemberContactMethodsAssociation(): void
    {
        $association = $this->Members->getAssociation('MemberContactMethods');

        $this->assertSame('member_id', $association->getForeignKey());
        $this->assertSame('select', $association->getStrategy());
        $this->assertTrue($association->getDependent());
    }
}
