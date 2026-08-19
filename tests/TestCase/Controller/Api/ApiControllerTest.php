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
        $this->assertArrayHasKey('member_contact_methods', $payload['data'][0]);
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
        $this->assertArrayHasKey('member_contact_method', $payload['data']);
    }

    public function testApiRoutesAreReadOnly(): void
    {
        $this->enableCsrfToken();
        $this->post('/api/members', []);

        $this->assertResponseCode(404);
    }
}
