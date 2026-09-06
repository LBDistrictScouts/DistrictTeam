<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Api;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class ApiControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = [
        'app.Groups',
        'app.Sections',
        'app.Teams',
        'app.Roles',
        'app.Members',
        'app.MemberContactMethods',
        'app.Appointments',
    ];

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->fetchTable('Teams')->updateAll(['group_id' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa'], []);
    }

    public function testMembersIndexReturnsJsonWithPagination(): void
    {
        $this->configRequest(['headers' => ['Accept' => 'application/json']]);
        $this->get('/api/members');

        $this->assertResponseOk();
        $this->assertContentType('application/json');
        $payload = json_decode((string)$this->_response->getBody(), true);
        $this->assertSame('Grace', $payload['data'][0]['first_name']);
        $this->assertArrayNotHasKey('membership_number', $payload['data'][0]);
        $this->assertArrayHasKey('member_contact_methods', $payload['data'][0]);
        $this->assertSame(
            'Phone Number',
            $payload['data'][0]['member_contact_methods'][0]['contact_method_type'],
        );
        $this->assertSame(2, $payload['pagination']['total']);
    }

    public function testAppointmentViewReturnsRelatedRecords(): void
    {
        $this->get('/api/appointments/55555555-5555-4555-8555-555555555551.json');

        $this->assertResponseOk();
        $this->assertContentType('application/json');
        $payload = json_decode((string)$this->_response->getBody(), true);
        $this->assertArrayHasKey('role', $payload['data']);
        $this->assertArrayHasKey('member', $payload['data']);
        $this->assertArrayNotHasKey('membership_number', $payload['data']['member']);
        $this->assertArrayHasKey('member_contact_method', $payload['data']);
        $this->assertSame(
            'Email',
            $payload['data']['member_contact_method']['contact_method_type'],
        );
    }

    public function testTeamsIndexDoesNotReturnRoles(): void
    {
        $this->get('/api/teams.json');

        $this->assertResponseOk();
        $payload = json_decode((string)$this->_response->getBody(), true);

        foreach (['tree_left', 'tree_right', 'tree_level'] as $field) {
            $this->assertResponseNotContains('"' . $field . '"');
        }

        foreach ($payload['data'] as $team) {
            $this->assertArrayNotHasKey('roles', $team);
        }
    }

    public function testTeamViewReturnsSlimRolesWithCurrentAppointments(): void
    {
        $this->fetchTable('Roles')->updateAll(
            ['multi_member_role' => true],
            ['id' => '22222222-2222-4222-8222-222222222221'],
        );
        $this->get('/api/teams/11111111-1111-4111-8111-111111111112.json');

        $this->assertResponseOk();
        $payload = json_decode((string)$this->_response->getBody(), true);

        $filledRole = $payload['data']['roles'][0];
        $this->assertSame(
            [
                'id', 'team_id', 'name', 'slug', 'currently_filled', 'is_lead',
                'multi_member_role', 'current_appointments', 'staffing_status',
            ],
            array_keys($filledRole),
        );
        $this->assertSame('Digital Lead', $filledRole['name']);
        $this->assertTrue($filledRole['currently_filled']);
        $this->assertTrue($filledRole['multi_member_role']);
        $this->assertSame('recruiting', $filledRole['staffing_status']);
        $this->assertSame(
            ['id', 'role_id', 'member_id', 'member', 'active'],
            array_keys($filledRole['current_appointments'][0]),
        );
        $this->assertSame('Ada Lovelace', $filledRole['current_appointments'][0]['member']['full_name']);
        $this->assertArrayNotHasKey('member_contact_method', $filledRole['current_appointments'][0]);
        $this->assertArrayNotHasKey('effective_start_date', $filledRole['current_appointments'][0]);
        $this->assertTrue($filledRole['current_appointments'][0]['active']);

        $this->get('/api/teams/11111111-1111-4111-8111-111111111111.json');

        $this->assertResponseOk();
        $payload = json_decode((string)$this->_response->getBody(), true);
        $roles = array_column($payload['data']['roles'], null, 'name');
        $vacantRole = $roles['Vacant Role'];
        $this->assertSame('Vacant Role', $vacantRole['name']);
        $this->assertFalse($vacantRole['currently_filled']);
        $this->assertSame([], $vacantRole['current_appointments']);

        $subTeamLead = $roles['Digital Lead'];
        $this->assertSame('11111111-1111-4111-8111-111111111112', $subTeamLead['team_id']);
        $this->assertTrue($subTeamLead['is_lead']);
        $this->assertSame('Ada Lovelace', $subTeamLead['current_appointments'][0]['member']['full_name']);
    }

    public function testRoleViewReturnsCurrentAppointments(): void
    {
        $this->get('/api/roles/22222222-2222-4222-8222-222222222221.json');

        $this->assertResponseOk();
        $payload = json_decode((string)$this->_response->getBody(), true);
        $this->assertSame(
            '55555555-5555-4555-8555-555555555551',
            $payload['data']['current_appointments'][0]['id'],
        );
        $this->assertSame('Ada', $payload['data']['current_appointments'][0]['member']['first_name']);
        $this->assertArrayNotHasKey(
            'membership_number',
            $payload['data']['current_appointments'][0]['member'],
        );
        $this->assertArrayHasKey(
            'member_contact_method',
            $payload['data']['current_appointments'][0],
        );
        $this->assertSame(
            'Email',
            $payload['data']['current_appointments'][0]['member_contact_method']['contact_method_type'],
        );
    }

    public function testContactMethodViewUsesEnumLabelAndHidesMemberNumber(): void
    {
        $this->get('/api/member-contact-methods/44444444-4444-4444-8444-444444444441.json');

        $this->assertResponseOk();
        $payload = json_decode((string)$this->_response->getBody(), true);

        $this->assertSame('Email', $payload['data']['contact_method_type']);
        $this->assertArrayNotHasKey('membership_number', $payload['data']['member']);
    }

    public function testRolesIndexIncludesRolesWithoutCurrentAppointments(): void
    {
        $this->get('/api/roles.json');

        $this->assertResponseOk();
        $payload = json_decode((string)$this->_response->getBody(), true);
        $roles = array_column($payload['data'], null, 'id');

        $this->assertCount(2, $roles);
        $this->assertArrayHasKey('22222222-2222-4222-8222-222222222222', $roles);
        $this->assertSame(
            [],
            $roles['22222222-2222-4222-8222-222222222222']['current_appointments'],
        );
    }

    public function testApiRoutesAreReadOnly(): void
    {
        $this->enableCsrfToken();
        $this->post('/api/members', []);

        $this->assertResponseCode(404);
    }

    /**
     * @return void
     */
    public function testTeamIncludesGroupAndSection(): void
    {
        $teams = $this->fetchTable('Teams');
        $this->fetchTable('Sections')->updateAll(['group_id' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa'], []);
        $team = $teams->get('11111111-1111-4111-8111-111111111112');
        $teams->patchEntity($team, [
            'group_id' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',
            'section_id' => 'cccccccc-cccc-4ccc-8ccc-cccccccccccc',
        ]);
        $teams->saveOrFail($team);
        $this->get('/api/teams/' . $team->id . '.json');
        $this->assertResponseOk();
        $payload = json_decode((string)$this->_response->getBody(), true);
        $this->assertSame('District', $payload['data']['group']['group_name']);
        $this->assertSame('district', $payload['data']['group']['type']);
        $this->assertSame(['district.example.org'], $payload['data']['group']['domains']);
        $this->assertSame('First Cubs', $payload['data']['section']['section_name']);
    }

    /**
     * @return void
     */
    public function testTeamApiUsesAndExposesSavedSortOrder(): void
    {
        $teams = $this->fetchTable('Teams');
        $parentId = '11111111-1111-4111-8111-111111111111';
        $childId = '11111111-1111-4111-8111-111111111112';
        $sibling = $teams->saveOrFail($teams->newEntity([
            'team_name' => 'Support Team', 'team_parent_id' => $parentId,
            'group_id' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',
        ]));
        $teams->saveOrder([$parentId, $sibling->id, $childId]);
        $this->get('/api/teams.json');
        $this->assertResponseOk();
        $data = json_decode((string)$this->_response->getBody(), true)['data'];
        $this->assertSame([$parentId, $sibling->id, $childId], array_column($data, 'id'));
        $this->assertSame([1, 2, 3], array_column($data, 'sort_order'));
        $this->assertSame([$sibling->id, $childId], array_column($data[0]['sub_teams'], 'id'));
        $this->assertSame([2, 3], array_column($data[0]['sub_teams'], 'sort_order'));
        $this->assertSame(1, $data[1]['parent_team']['sort_order']);

        // Verify sort_order itself drives API order, independently of tree coordinates.
        $teams->updateAll(['sort_order' => 4], ['id' => $sibling->id]);
        $this->get('/api/teams.json?limit=1&page=2');
        $this->assertResponseOk();
        $data = json_decode((string)$this->_response->getBody(), true)['data'];
        $this->assertSame($childId, $data[0]['id']);
        $this->assertSame(3, $data[0]['sort_order']);

        $this->get('/api/teams/' . $parentId . '.json');
        $this->assertResponseOk();
        $data = json_decode((string)$this->_response->getBody(), true)['data'];
        $this->assertSame(1, $data['sort_order']);
        $this->assertSame([$childId, $sibling->id], array_column($data['sub_teams'], 'id'));
        $this->assertSame([3, 4], array_column($data['sub_teams'], 'sort_order'));
    }

    /**
     * @return void
     */
    public function testSharedCoreIdsAndRelationshipsAreExposedOnNestedTeams(): void
    {
        $teams = $this->fetchTable('Teams');
        $parentId = '11111111-1111-4111-8111-111111111111';
        $childId = '11111111-1111-4111-8111-111111111112';
        $districtId = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';
        $groupId = $districtId;
        $this->fetchTable('Sections')->updateAll(['group_id' => $districtId], []);
        $sectionId = 'cccccccc-cccc-4ccc-8ccc-cccccccccccc';
        $parent = $teams->get($parentId);
        $teams->patchEntity($parent, ['group_id' => $districtId]);
        $teams->saveOrFail($parent);
        $child = $teams->get($childId);
        $teams->patchEntity($child, ['group_id' => $groupId, 'section_id' => $sectionId]);
        $teams->saveOrFail($child);

        foreach (['/api/teams.json', '/api/teams/' . $parentId . '.json'] as $url) {
            $this->get($url);
            $this->assertResponseOk();
            $data = json_decode((string)$this->_response->getBody(), true)['data'];
            $team = $url === '/api/teams.json' ? array_column($data, null, 'id')[$parentId] : $data;
            $this->assertSame($districtId, $team['group_id']);
            $this->assertSame($districtId, $team['group']['id']);
            $this->assertNull($team['section_id']);
            $this->assertNull($team['section']);
            $nested = $team['sub_teams'][0];
            $this->assertSame($groupId, $nested['group_id']);
            $this->assertSame($sectionId, $nested['section_id']);
            $this->assertSame($groupId, $nested['group']['id']);
            $this->assertSame($sectionId, $nested['section']['id']);
            $this->assertSame($groupId, $nested['section']['group_id']);
            $this->assertSame(123, $nested['section']['section_osm_id']);
        }

        $this->get('/api/teams/' . $childId . '.json');
        $this->assertResponseOk();
        $data = json_decode((string)$this->_response->getBody(), true)['data'];
        $this->assertSame($districtId, $data['parent_team']['group']['id']);
        $this->assertNull($data['parent_team']['section']);

        $this->get('/api/roles/22222222-2222-4222-8222-222222222221.json');
        $this->assertResponseOk();
        $data = json_decode((string)$this->_response->getBody(), true)['data'];
        $this->assertSame($groupId, $data['team']['group']['id']);
        $this->assertSame($sectionId, $data['team']['section']['id']);
    }

    /**
     * @return void
     */
    public function testTeamsApiExcludesOtherGroups(): void
    {
        $teams = $this->fetchTable('Teams');
        $districtId = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';
        $parentId = '11111111-1111-4111-8111-111111111111';
        $otherId = '11111111-1111-4111-8111-111111111112';
        $teams->updateAll(['group_id' => 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb'], ['id' => $otherId]);
        $otherGroupTeam = $teams->saveOrFail($teams->newEntity([
            'team_name' => 'Other Group', 'group_id' => 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb',
        ]));
        $districtChild = $teams->saveOrFail($teams->newEntity([
            'team_name' => 'District Support', 'group_id' => $districtId, 'team_parent_id' => $otherId,
        ]));
        // Classification comes from core data, not the name, sort order, or a configured UUID.
        $this->fetchTable('Groups')->updateAll(['group_name' => 'Renamed District', 'sort_order' => 99], ['id' => $districtId]);
        $this->get('/api/teams.json?limit=1');
        $this->assertResponseOk();
        $payload = json_decode((string)$this->_response->getBody(), true);
        $this->assertSame(2, $payload['pagination']['total']);
        $this->assertCount(1, $payload['data']);
        $this->assertSame($districtId, $payload['data'][0]['group_id']);
        $this->assertSame([], $payload['data'][0]['sub_teams']);

        $this->get('/api/teams/' . $parentId . '.json');
        $this->assertResponseOk();
        $data = json_decode((string)$this->_response->getBody(), true)['data'];
        $this->assertSame([], $data['sub_teams']);
        $this->assertNotContains('Digital Lead', array_column($data['roles'], 'name'));

        $this->get('/api/teams/' . $districtChild->id . '.json');
        $this->assertResponseOk();
        $data = json_decode((string)$this->_response->getBody(), true)['data'];
        $this->assertNull($data['parent_team']);

        foreach ([$otherId, $otherGroupTeam->id] as $id) {
            $this->get('/api/teams/' . $id . '.json');
            $this->assertResponseCode(404);
        }
        $this->fetchTable('Groups')->updateAll(['type' => 'group'], ['id' => $districtId]);
        $this->get('/api/teams.json');
        $this->assertResponseOk();
        $payload = json_decode((string)$this->_response->getBody(), true);
        $this->assertSame([], $payload['data']);
        $this->assertSame(0, $payload['pagination']['total']);
        $this->fetchTable('Groups')->updateAll(['type' => 'district'], ['id' => 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb']);
        $this->get('/api/teams.json');
        $this->assertResponseOk();
        $payload = json_decode((string)$this->_response->getBody(), true);
        $this->assertEqualsCanonicalizing([$otherId, $otherGroupTeam->id], array_column($payload['data'], 'id'));
        $this->assertSame('district', $payload['data'][0]['group']['type']);
        $this->assertSame([], $payload['data'][0]['sub_teams']);
    }

    /**
     * @return void
     */
    public function testGroupTeamsReturnsScopedSortedCollection(): void
    {
        $groupId = 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb';
        $teams = $this->fetchTable('Teams');
        $parentId = '11111111-1111-4111-8111-111111111111';
        $childId = '11111111-1111-4111-8111-111111111112';
        $teams->updateAll(['group_id' => $groupId], ['id' => $childId]);
        $child = $teams->get($childId);
        $teams->patchEntity($child, ['section_id' => 'cccccccc-cccc-4ccc-8ccc-cccccccccccc']);
        $teams->saveOrFail($child);
        $sibling = $teams->saveOrFail($teams->newEntity([
            'team_name' => 'Group Leadership', 'group_id' => $groupId,
        ]));
        $teams->saveOrder([$parentId, $childId, $sibling->id]);
        $this->get('/api/group-teams/' . $groupId . '.json?limit=1');
        $this->assertResponseOk();
        $this->assertContentType('application/json');
        $payload = json_decode((string)$this->_response->getBody(), true);
        $this->assertSame(['data', 'pagination'], array_keys($payload));
        $this->assertSame(2, $payload['pagination']['total']);
        $this->assertSame(2, $payload['pagination']['page_count']);
        $this->assertCount(1, $payload['data']);
        $team = $payload['data'][0];
        $this->assertSame($childId, $team['id']);
        $this->assertSame(2, $team['sort_order']);
        $this->assertSame($groupId, $team['group']['id']);
        $this->assertSame('group', $team['group']['type']);
        $this->assertSame('cccccccc-cccc-4ccc-8ccc-cccccccccccc', $team['section']['id']);
        $this->assertNull($team['parent_team']);
        $this->assertArrayNotHasKey('roles', $team);
        $this->get('/api/group-teams/' . $groupId . '.json?limit=1&page=2');
        $this->assertResponseOk();
        $payload = json_decode((string)$this->_response->getBody(), true);
        $this->assertSame($sibling->id, $payload['data'][0]['id']);

        // The existing district endpoint keeps its independent scope.
        $this->get('/api/teams.json');
        $data = json_decode((string)$this->_response->getBody(), true)['data'];
        $this->assertSame([$parentId], array_column($data, 'id'));
        $this->assertSame([], $data[0]['sub_teams']);
    }

    /**
     * @return void
     */
    public function testGroupTeamsFiltersNestedTeamsAndHandlesEmptyOrUnknownGroups(): void
    {
        $groupId = 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb';
        $this->get('/api/group-teams/' . $groupId . '.json');
        $this->assertResponseOk();
        $payload = json_decode((string)$this->_response->getBody(), true);
        $this->assertSame([], $payload['data']);
        $this->assertSame(0, $payload['pagination']['total']);
        $this->fetchTable('Teams')->updateAll(['group_id' => $groupId], ['id' => '11111111-1111-4111-8111-111111111111']);
        $this->get('/api/group-teams/' . $groupId . '.json');
        $this->assertResponseOk();
        $payload = json_decode((string)$this->_response->getBody(), true);
        $this->assertSame([], $payload['data'][0]['sub_teams']);
        foreach (['not-a-uuid', '99999999-9999-4999-8999-999999999999'] as $id) {
            $this->get('/api/group-teams/' . $id . '.json');
            $this->assertResponseCode(404);
        }
        $this->enableCsrfToken();
        $this->post('/api/group-teams/' . $groupId . '.json', []);
        $this->assertResponseCode(404);
    }

    /**
     * @return void
     */
    public function testGroupRolesReturnsScopedSortedCollection(): void
    {
        $groupId = 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb';
        $teams = $this->fetchTable('Teams');
        $roles = $this->fetchTable('Roles');
        $teams->updateAll(['group_id' => $groupId], []);
        $roles->updateAll(['group_id' => $groupId], []);

        $this->get('/api/group-roles/' . $groupId . '.json?limit=1');

        $this->assertResponseOk();
        $this->assertContentType('application/json');
        $payload = json_decode((string)$this->_response->getBody(), true);
        $this->assertSame(['data', 'pagination'], array_keys($payload));
        $this->assertSame(2, $payload['pagination']['total']);
        $this->assertSame(2, $payload['pagination']['page_count']);
        $this->assertSame('Vacant Role', $payload['data'][0]['name']);
        $this->assertSame($groupId, $payload['data'][0]['team']['group']['id']);
        $this->assertSame([], $payload['data'][0]['current_appointments']);

        $this->get('/api/group-roles/' . $groupId . '.json?limit=1&page=2');
        $this->assertResponseOk();
        $payload = json_decode((string)$this->_response->getBody(), true);
        $this->assertSame('Digital Lead', $payload['data'][0]['name']);

        // The existing unscoped endpoint still returns both roles.
        $this->get('/api/roles.json');
        $this->assertSame(2, json_decode((string)$this->_response->getBody(), true)['pagination']['total']);
    }

    /**
     * @return void
     */
    public function testGroupRolesHandlesEmptyOrUnknownGroups(): void
    {
        $groupId = 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb';
        $this->get('/api/group-roles/' . $groupId . '.json');
        $this->assertResponseOk();
        $payload = json_decode((string)$this->_response->getBody(), true);
        $this->assertSame([], $payload['data']);
        $this->assertSame(0, $payload['pagination']['total']);

        foreach (['not-a-uuid', '99999999-9999-4999-8999-999999999999'] as $id) {
            $this->get('/api/group-roles/' . $id . '.json');
            $this->assertResponseCode(404);
        }
        $this->enableCsrfToken();
        $this->post('/api/group-roles/' . $groupId . '.json', []);
        $this->assertResponseCode(404);
    }
}
