<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use Cake\TestSuite\TestCase;

class EmailGroupsTableTest extends TestCase
{
    /**
     * @var array<string>
     */
    protected array $fixtures = ['app.Groups', 'app.Sections', 'app.Teams', 'app.EmailGroups'];

    public function testEmailGroupRequiresAnExistingGroupAndMatchingTeam(): void
    {
        $emailGroups = $this->fetchTable('EmailGroups');

        $valid = $emailGroups->newEntity([
            'group_id' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',
            'team_id' => '11111111-1111-4111-8111-111111111111',
            'email_group_name' => 'District Volunteers',
            'email_address' => 'Volunteers@District.Example.org',
        ]);
        $this->assertNotFalse($emailGroups->save($valid));
        $this->assertSame('volunteers@district.example.org', $valid->email_address);

        $wrongGroup = $emailGroups->newEntity([
            'group_id' => 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb',
            'team_id' => '11111111-1111-4111-8111-111111111111',
            'email_group_name' => 'Wrong Team Scope',
            'email_address' => 'wrong-scope@group.example.org',
        ]);
        $this->assertFalse($emailGroups->save($wrongGroup));
        $this->assertArrayHasKey('team_id', $wrongGroup->getErrors());

        $sectionScoped = $emailGroups->newEntity([
            'group_id' => 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb',
            'section_id' => 'cccccccc-cccc-4ccc-8ccc-cccccccccccc',
            'email_group_name' => 'Cubs Volunteers',
            'email_address' => 'cubs-volunteers@group.example.org',
        ]);
        $this->assertNotFalse($emailGroups->save($sectionScoped));

        $wrongSection = $emailGroups->newEntity([
            'group_id' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',
            'section_id' => 'cccccccc-cccc-4ccc-8ccc-cccccccccccc',
            'email_group_name' => 'Wrong Section Scope',
            'email_address' => 'wrong-section@district.example.org',
        ]);
        $this->assertFalse($emailGroups->save($wrongSection));
        $this->assertArrayHasKey('section_id', $wrongSection->getErrors());
    }

    public function testEmailAddressIsRequiredAndMustBeValid(): void
    {
        $emailGroup = $this->fetchTable('EmailGroups')->newEntity([
            'group_id' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',
            'email_group_name' => 'Invalid Email Address',
            'email_address' => 'not-an-email-address',
        ]);

        $this->assertArrayHasKey('email_address', $emailGroup->getErrors());
    }

    public function testEmailAddressMustUseTheSelectedGroupsDomain(): void
    {
        $emailGroup = $this->fetchTable('EmailGroups')->newEntity([
            'group_id' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',
            'email_group_name' => 'Mismatched Domain',
            'email_address' => 'letchworth@group.example.org',
        ]);

        $this->assertFalse($this->fetchTable('EmailGroups')->save($emailGroup));
        $this->assertArrayHasKey('email_address', $emailGroup->getErrors());
    }
}
