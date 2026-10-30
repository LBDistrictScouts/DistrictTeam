<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use Cake\TestSuite\TestCase;
use InvalidArgumentException;

class TagsTableTest extends TestCase
{
    /**
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Groups',
        'app.Teams',
        'app.Roles',
        'app.Members',
        'app.MemberContactMethods',
        'app.Appointments',
        'app.EmailGroups',
        'app.Tags',
        'app.TeamTags',
        'app.RoleTags',
        'app.AppointmentTags',
    ];

    public function testTagCodeValidationUniquenessAndFalseBoolean(): void
    {
        $tags = $this->fetchTable('Tags');
        $tag = $tags->newEntity([
            'tag_label' => 'Permission group',
            'tag_code' => 'permission-group-1',
            'is_cognito_permissions_group' => false,
        ]);

        $this->assertSame([], $tag->getErrors());
        $saved = $tags->saveOrFail($tag);
        $this->assertFalse($saved->is_cognito_permissions_group);

        $invalidCode = $tags->newEntity([
            'tag_label' => 'Invalid',
            'tag_code' => 'bad_code',
        ]);
        $this->assertArrayHasKey('tag_code', $invalidCode->getErrors());

        $invalidCodeWithSpaces = $tags->newEntity([
            'tag_label' => 'Invalid',
            'tag_code' => 'not allowed',
        ]);
        $this->assertArrayHasKey('tag_code', $invalidCodeWithSpaces->getErrors());

        $validCode = $tags->newEntity([
            'tag_label' => 'Alphanumeric',
            'tag_code' => 'A1-b2-C3',
        ]);
        $this->assertArrayNotHasKey('tag_code', $validCode->getErrors());

        $duplicateCode = $tags->newEntity([
            'tag_label' => 'Duplicate',
            'tag_code' => 'permission-group-1',
        ]);
        $this->assertFalse($tags->save($duplicateCode));
        $this->assertArrayHasKey('tag_code', $duplicateCode->getErrors());
    }

    public function testCurrentMembersAndAppointmentsMatchAllAssignmentLevelsOnce(): void
    {
        $tags = $this->fetchTable('Tags');
        $links = [
            'team' => ['TeamTags', 'team_id', '11111111-1111-4111-8111-111111111112'],
            'role' => ['RoleTags', 'role_id', '22222222-2222-4222-8222-222222222221'],
            'appointment' => ['AppointmentTags', 'appointment_id', '55555555-5555-4555-8555-555555555551'],
        ];
        foreach ($links as $level => [$joinTable, $entityColumn, $entityId]) {
            $tag = $tags->saveOrFail($tags->newEntity([
                'tag_label' => ucfirst($level) . ' tag',
                'tag_code' => $level . '-tag',
            ]));
            $join = $this->fetchTable($joinTable)->newEntity([
                $entityColumn => $entityId,
                'tag_id' => $tag->id,
            ]);
            $this->fetchTable($joinTable)->saveOrFail($join);

            $members = $tags->findCurrentMembers($tag->id)->all()->toList();
            $this->assertCount(1, $members, $level . ' assignment should find its current member.');
            $this->assertSame('33333333-3333-4333-8333-333333333331', $members[0]->id);
        }

        $combined = $tags->saveOrFail($tags->newEntity([
            'tag_label' => 'Combined tag',
            'tag_code' => 'combined-tag',
        ]));
        foreach (
            [
            ['TeamTags', 'team_id', '11111111-1111-4111-8111-111111111112'],
            ['RoleTags', 'role_id', '22222222-2222-4222-8222-222222222221'],
            ['AppointmentTags', 'appointment_id', '55555555-5555-4555-8555-555555555551'],
            ] as [$joinTable, $entityColumn, $entityId]
        ) {
            $join = $this->fetchTable($joinTable)->newEntity([
                $entityColumn => $entityId,
                'tag_id' => $combined->id,
            ]);
            $this->fetchTable($joinTable)->saveOrFail($join);
        }

        $members = $tags->findCurrentMembers($combined->id)->all()->toList();
        $appointments = $tags->findCurrentAppointments($combined->id)->all()->toList();
        $this->assertCount(1, $members, 'Multiple assignment levels must not duplicate a member.');
        $this->assertCount(1, $appointments, 'Multiple assignment levels must not duplicate an appointment.');
        $this->assertSame('Ada Lovelace', $appointments[0]->member->full_name);
        $this->assertSame('Digital Lead', $appointments[0]->role->name);
        $this->assertSame('ada@example.com', $appointments[0]->member_contact_method->contact_method);
    }

    public function testEndedAppointmentsDoNotMakeATagApplyToMembers(): void
    {
        $appointments = $this->fetchTable('Appointments');
        $ended = $appointments->saveOrFail($appointments->newEntity([
            'role_id' => '22222222-2222-4222-8222-222222222221',
            'member_id' => '33333333-3333-4333-8333-333333333331',
            'member_contact_method_id' => '44444444-4444-4444-8444-444444444441',
            'effective_start_date' => '2010-01-01',
            'effective_end_date' => '2011-01-01',
        ]));
        $tags = $this->fetchTable('Tags');
        $tag = $tags->saveOrFail($tags->newEntity([
            'tag_label' => 'Ended appointment tag',
            'tag_code' => 'ended-appointment-tag',
        ]));
        $link = $this->fetchTable('AppointmentTags')->newEntity([
            'appointment_id' => $ended->id,
            'tag_id' => $tag->id,
        ]);
        $this->fetchTable('AppointmentTags')->saveOrFail($link);

        $this->assertCount(0, $tags->findCurrentMembers($tag->id)->all()->toList());
        $this->assertCount(0, $tags->findCurrentAppointments($tag->id)->all()->toList());
    }

    public function testCurrentAppointmentsReturnOnlyTheHighestTeamAppointmentPerMember(): void
    {
        $appointments = $this->fetchTable('Appointments');
        $rootAppointment = $appointments->saveOrFail($appointments->newEntity([
            'role_id' => '22222222-2222-4222-8222-222222222222',
            'member_id' => '33333333-3333-4333-8333-333333333331',
            'member_contact_method_id' => '44444444-4444-4444-8444-444444444441',
            'effective_start_date' => '2010-01-01',
        ]));
        $appointments->saveOrFail($appointments->newEntity([
            'role_id' => '22222222-2222-4222-8222-222222222221',
            'member_id' => '33333333-3333-4333-8333-333333333331',
            'member_contact_method_id' => '44444444-4444-4444-8444-444444444441',
            'effective_start_date' => '2024-01-01',
        ]));

        $tags = $this->fetchTable('Tags');
        $tag = $tags->saveOrFail($tags->newEntity([
            'tag_label' => 'Hierarchy tag',
            'tag_code' => 'hierarchy-tag',
            'teams' => ['_ids' => [
                '11111111-1111-4111-8111-111111111111',
                '11111111-1111-4111-8111-111111111112',
            ]],
        ], ['associated' => ['Teams']]));

        $currentAppointments = $tags->findCurrentAppointments($tag->id)->all()->toList();
        $currentMembers = $tags->findCurrentMembers($tag->id)->all()->toList();

        $this->assertCount(1, $currentAppointments);
        $this->assertSame($rootAppointment->id, $currentAppointments[0]->id);
        $this->assertSame('22222222-2222-4222-8222-222222222222', $currentAppointments[0]->role_id);
        $this->assertCount(1, $currentMembers);
        $this->assertSame('33333333-3333-4333-8333-333333333331', $currentMembers[0]->id);
    }

    public function testEmailGroupHasOneTagAndTagFinderReturnsDesiredMembers(): void
    {
        $emailGroupId = '66666666-6666-4666-8666-666666666661';
        $tags = $this->fetchTable('Tags');
        $tag = $tags->saveOrFail($tags->newEntity([
            'tag_label' => 'Email group membership',
            'tag_code' => 'email-group-membership',
            'email_group_id' => $emailGroupId,
            'teams' => ['_ids' => ['11111111-1111-4111-8111-111111111112']],
        ], ['associated' => ['Teams']]));

        $linkedTag = $tags->get($tag->id, contain: ['EmailGroups']);
        $this->assertSame('Digital Team Leaders', $linkedTag->email_group->email_group_name);
        $members = $tags->findCurrentMembers($linkedTag->id)->all()->toList();
        $this->assertCount(1, $members);
        $this->assertSame('33333333-3333-4333-8333-333333333331', $members[0]->id);

        $tagFromGroupId = $tags->find()->where(['email_group_id' => $emailGroupId])->firstOrFail();
        $this->assertSame($tag->id, $tagFromGroupId->id);
        $emailGroup = $this->fetchTable('EmailGroups')->get($emailGroupId, contain: ['Tags']);
        $this->assertNotNull($emailGroup->tag);
        $this->assertSame($tag->id, $emailGroup->tag->id);

        $duplicate = $tags->newEntity([
            'tag_label' => 'Duplicate email group link',
            'tag_code' => 'duplicate-email-group-link',
            'email_group_id' => $emailGroupId,
        ]);
        $this->assertFalse($tags->save($duplicate));
        $this->assertArrayHasKey('email_group_id', $duplicate->getErrors());

        $emailGroups = $this->fetchTable('EmailGroups');
        $this->assertTrue($emailGroups->delete($emailGroups->get($emailGroupId)));
        $this->assertNull($tags->get($tag->id)->email_group_id);
    }

    public function testCurrentQueryRequiresAValidTagUuid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->fetchTable('Tags')->findCurrentMembers('not-a-uuid');
    }
}
