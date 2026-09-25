<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use Cake\TestSuite\TestCase;

class EmailGroupsTableTest extends TestCase
{
    /**
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Groups',
        'app.Sections',
        'app.Teams',
        'app.EmailGroups',
        'app.Members',
        'app.MemberContactMethods',
    ];

    public function testMembersAssociationUsesMemberContactMethods(): void
    {
        $association = $this->fetchTable('EmailGroups')->getAssociation('Members');

        $this->assertSame('email_group_id', $association->getForeignKey());
        $this->assertSame('member_id', $association->getTargetForeignKey());
        $this->assertSame('MemberContactMethods', $association->getThrough());

        $contactMethods = $this->fetchTable('MemberContactMethods');
        $contactMethods->saveOrFail($contactMethods->newEntity([
            'member_id' => '33333333-3333-4333-8333-333333333331',
            'contact_method' => 'digital-leaders@district.example.org',
            'contact_method_type' => 3,
            'email_group_id' => '66666666-6666-4666-8666-666666666661',
        ]));
        $emailGroup = $this->fetchTable('EmailGroups')->find()
            ->contain(['Members'])
            ->where(['EmailGroups.id' => '66666666-6666-4666-8666-666666666661'])
            ->firstOrFail();

        $this->assertCount(1, $emailGroup->members);
        $this->assertSame('33333333-3333-4333-8333-333333333331', $emailGroup->members[0]->id);
    }

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

    public function testEmailGroupTeamMustBelongToSelectedSection(): void
    {
        $sections = $this->fetchTable('Sections');
        $firstSection = $sections->saveOrFail($sections->newEntity([
            'group_id' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',
            'section_osm_id' => 124,
            'section_name' => 'District Cubs',
            'section_type' => 'cubs',
        ]));
        $secondSection = $sections->saveOrFail($sections->newEntity([
            'group_id' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',
            'section_osm_id' => 125,
            'section_name' => 'District Scouts',
            'section_type' => 'scouts',
        ]));
        $teams = $this->fetchTable('Teams');
        $team = $teams->saveOrFail($teams->newEntity([
            'group_id' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',
            'section_id' => $firstSection->id,
            'team_name' => 'Cubs Leadership',
        ]));

        $emailGroup = $this->fetchTable('EmailGroups')->newEntity([
            'group_id' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',
            'team_id' => $team->id,
            'section_id' => $secondSection->id,
            'email_group_name' => 'Invalid Scoped Group',
            'email_address' => 'invalid-scoped-group@district.example.org',
        ]);

        $this->assertFalse($this->fetchTable('EmailGroups')->save($emailGroup));
        $this->assertArrayHasKey('team_id', $emailGroup->getErrors());
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
