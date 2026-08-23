<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Api;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class ApiControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = [
        'app.Teams',
        'app.Roles',
        'app.Members',
        'app.MemberContactMethods',
        'app.Appointments',
    ];

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

        foreach ($payload['data'] as $team) {
            $this->assertArrayNotHasKey('roles', $team);
        }
    }

    public function testTeamViewReturnsSlimRolesWithCurrentAppointments(): void
    {
        $this->get('/api/teams/11111111-1111-4111-8111-111111111112.json');

        $this->assertResponseOk();
        $payload = json_decode((string)$this->_response->getBody(), true);

        $filledRole = $payload['data']['roles'][0];
        $this->assertSame(
            ['id', 'team_id', 'name', 'slug', 'currently_filled', 'is_lead', 'current_appointment'],
            array_keys($filledRole),
        );
        $this->assertSame('Digital Lead', $filledRole['name']);
        $this->assertTrue($filledRole['currently_filled']);
        $this->assertSame(
            ['id', 'role_id', 'member_id', 'member'],
            array_keys($filledRole['current_appointment']),
        );
        $this->assertSame('Ada Lovelace', $filledRole['current_appointment']['member']['full_name']);
        $this->assertArrayNotHasKey('member_contact_method', $filledRole['current_appointment']);
        $this->assertArrayNotHasKey('effective_start_date', $filledRole['current_appointment']);
        $this->assertArrayNotHasKey('active', $filledRole['current_appointment']);

        $this->get('/api/teams/11111111-1111-4111-8111-111111111111.json');

        $this->assertResponseOk();
        $payload = json_decode((string)$this->_response->getBody(), true);
        $roles = array_column($payload['data']['roles'], null, 'name');
        $vacantRole = $roles['Vacant Role'];
        $this->assertSame('Vacant Role', $vacantRole['name']);
        $this->assertFalse($vacantRole['currently_filled']);
        $this->assertNull($vacantRole['current_appointment']);

        $subTeamLead = $roles['Digital Lead'];
        $this->assertSame('11111111-1111-4111-8111-111111111112', $subTeamLead['team_id']);
        $this->assertTrue($subTeamLead['is_lead']);
        $this->assertSame('Ada Lovelace', $subTeamLead['current_appointment']['member']['full_name']);
    }

    public function testRoleViewReturnsCurrentAppointment(): void
    {
        $this->get('/api/roles/22222222-2222-4222-8222-222222222221.json');

        $this->assertResponseOk();
        $payload = json_decode((string)$this->_response->getBody(), true);
        $this->assertSame(
            '55555555-5555-4555-8555-555555555551',
            $payload['data']['current_appointment']['id'],
        );
        $this->assertSame('Ada', $payload['data']['current_appointment']['member']['first_name']);
        $this->assertArrayNotHasKey(
            'membership_number',
            $payload['data']['current_appointment']['member'],
        );
        $this->assertArrayHasKey(
            'member_contact_method',
            $payload['data']['current_appointment'],
        );
        $this->assertSame(
            'Email',
            $payload['data']['current_appointment']['member_contact_method']['contact_method_type'],
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
        $this->assertNull(
            $roles['22222222-2222-4222-8222-222222222222']['current_appointment'],
        );
    }

    public function testApiRoutesAreReadOnly(): void
    {
        $this->enableCsrfToken();
        $this->post('/api/members', []);

        $this->assertResponseCode(404);
    }
}
