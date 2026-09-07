<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\AppointmentsController Test Case
 *
 * @link \App\Controller\AppointmentsController
 */
class AppointmentsControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Groups', 'app.Teams',
        'app.Roles',
        'app.Members',
        'app.MemberContactMethods',
        'app.Appointments',
    ];

    /**
     * Test index method
     *
     * @return void
     * @link \App\Controller\AppointmentsController::index()
     */
    public function testIndex(): void
    {
        $this->get('/appointments');
        $this->assertResponseOk();
        $this->assertResponseContains('Digital Lead');
        $this->assertResponseContains('Non-group email');
    }

    /**
     * Test view method
     *
     * @return void
     * @link \App\Controller\AppointmentsController::view()
     */
    public function testView(): void
    {
        $this->get('/appointments/view/55555555-5555-4555-8555-555555555551');
        $this->assertResponseOk();
        $this->assertResponseContains('Ada Lovelace');
        $this->assertResponseContains('Non-group email');
    }

    /**
     * Test add method
     *
     * @return void
     * @link \App\Controller\AppointmentsController::add()
     */
    public function testAdd(): void
    {
        $contacts = $this->fetchTable('MemberContactMethods');
        $contact = $contacts->saveOrFail($contacts->newEntity([
            'member_id' => '33333333-3333-4333-8333-333333333332',
            'contact_method' => 'grace@district.example.org',
            'contact_method_type' => 1,
        ]));
        $this->enableCsrfToken();
        $this->post('/appointments/add', [
            'role_id' => '22222222-2222-4222-8222-222222222222',
            'member_id' => '33333333-3333-4333-8333-333333333332',
            'member_contact_method_id' => $contact->id,
            'effective_start_date' => '2020-01-01',
        ]);

        $this->assertRedirect('/appointments');
        $this->assertTrue($this->getTableLocator()->get('Appointments')->exists([
            'role_id' => '22222222-2222-4222-8222-222222222222',
        ]));
    }

    public function testAddValidationFailure(): void
    {
        $this->enableCsrfToken();
        $this->post('/appointments/add', []);

        $this->assertResponseOk();
        $this->assertResponseContains('The appointment could not be saved');
    }

    public function testAddRejectsAnotherMembersContactMethod(): void
    {
        $this->enableCsrfToken();
        $this->post('/appointments/add', [
            'role_id' => '22222222-2222-4222-8222-222222222222',
            'member_id' => '33333333-3333-4333-8333-333333333331',
            'member_contact_method_id' => '44444444-4444-4444-8444-444444444442',
            'effective_start_date' => '2020-01-01',
        ]);

        $this->assertResponseOk();
        $this->assertResponseContains('The appointment could not be saved');
    }

    public function testAddPreselectsMemberAndRoleFromQueryString(): void
    {
        $this->get('/appointments/add?member_id=33333333-3333-4333-8333-333333333331&role_id=22222222-2222-4222-8222-222222222221');

        $this->assertResponseOk();
        $this->assertResponseContains(
            '<option value="22222222-2222-4222-8222-222222222221" selected="selected">Digital Lead</option>',
        );
        $this->assertResponseContains(
            '<option value="33333333-3333-4333-8333-333333333331" selected="selected">Ada Lovelace</option>',
        );
    }

    public function testAppointmentFormsDoNotOfferAnActiveControl(): void
    {
        $this->get('/appointments/add');
        $this->assertResponseOk();
        $this->assertResponseNotContains('name="active"');

        $this->get('/appointments/edit/55555555-5555-4555-8555-555555555551');
        $this->assertResponseOk();
        $this->assertResponseNotContains('name="active"');
    }

    public function testEditFiltersContactMethodsBySelectedMember(): void
    {
        $this->get('/appointments/edit/55555555-5555-4555-8555-555555555551');

        $this->assertResponseOk();
        $this->assertResponseContains('contactMethods.filter');
        $this->assertResponseNotContains('value="44444444-4444-4444-8444-444444444441"');
    }

    public function testAddRejectsNonGroupEmailContactMethod(): void
    {
        $this->enableCsrfToken();
        $this->post('/appointments/add', [
            'role_id' => '22222222-2222-4222-8222-222222222222',
            'member_id' => '33333333-3333-4333-8333-333333333331',
            'member_contact_method_id' => '44444444-4444-4444-8444-444444444441',
            'effective_start_date' => '2022-01-01',
        ]);

        $this->assertResponseOk();
        $this->assertResponseContains('Only group email contact methods can be used for an appointment');
    }

    public function testAppointmentFormsDoNotOfferPhoneContacts(): void
    {
        $this->get('/appointments/add');
        $this->assertResponseOk();
        $this->assertResponseNotContains('value="44444444-4444-4444-8444-444444444442"');

        $this->get('/appointments/edit/55555555-5555-4555-8555-555555555551');
        $this->assertResponseOk();
        $this->assertResponseNotContains('value="44444444-4444-4444-8444-444444444442"');
    }

    public function testAddRejectsPhoneContactMethod(): void
    {
        $this->enableCsrfToken();
        $this->post('/appointments/add', [
            'role_id' => '22222222-2222-4222-8222-222222222222',
            'member_id' => '33333333-3333-4333-8333-333333333332',
            'member_contact_method_id' => '44444444-4444-4444-8444-444444444442',
            'effective_start_date' => '2022-01-01',
        ]);

        $this->assertResponseOk();
        $this->assertResponseContains('Only group email contact methods can be used for an appointment');
    }

    /**
     * Test edit method
     *
     * @return void
     * @link \App\Controller\AppointmentsController::edit()
     */
    public function testEdit(): void
    {
        $this->enableCsrfToken();
        $this->put('/appointments/edit/55555555-5555-4555-8555-555555555551', [
            'role_id' => '22222222-2222-4222-8222-222222222221',
            'member_id' => '33333333-3333-4333-8333-333333333331',
            'member_contact_method_id' => '44444444-4444-4444-8444-444444444441',
            'effective_start_date' => '2020-01-01',
            'effective_end_date' => '2021-01-01',
        ]);

        $this->assertRedirect('/appointments');
        $appointment = $this->getTableLocator()->get('Appointments')
            ->get('55555555-5555-4555-8555-555555555551');
        $this->assertSame('2021-01-01', $appointment->effective_end_date->format('Y-m-d'));
    }

    public function testEditValidationFailure(): void
    {
        $this->enableCsrfToken();
        $this->put('/appointments/edit/55555555-5555-4555-8555-555555555551', [
            'role_id' => '',
            'member_id' => '',
            'member_contact_method_id' => '',
            'effective_start_date' => '',
        ]);

        $this->assertResponseOk();
        $this->assertResponseContains('The appointment could not be saved');
    }

    /**
     * Test delete method
     *
     * @return void
     * @link \App\Controller\AppointmentsController::delete()
     */
    public function testDelete(): void
    {
        $this->enableCsrfToken();
        $this->delete('/appointments/delete/55555555-5555-4555-8555-555555555551');

        $this->assertRedirect('/appointments');
        $this->assertFalse($this->getTableLocator()->get('Appointments')->exists([
            'id' => '55555555-5555-4555-8555-555555555551',
        ]));
    }

    public function testAddMemberAjax(): void
    {
        $this->enableCsrfToken();
        $this->configRequest([
            'headers' => [
                'Accept' => 'application/json',
                'X-Requested-With' => 'XMLHttpRequest',
            ],
        ]);
        $this->post('/appointments/add-member', [
            'first_name' => 'Dorothy',
            'last_name' => 'Vaughan',
            'membership_number' => 3002,
            'join_date' => '2020-01-01',
            'contact_method_type' => 1,
            'contact_method' => 'dorothy@example.com',
        ]);

        $this->assertResponseOk();
        $payload = json_decode((string)$this->_response->getBody(), true);
        $this->assertTrue($payload['success']);
        $this->assertSame('Dorothy Vaughan', $payload['member']['full_name']);
        $this->assertSame('Email', $payload['contactMethod']['contact_method_type']);
    }

    public function testAddMemberAjaxValidationFailure(): void
    {
        $this->enableCsrfToken();
        $this->configRequest(['headers' => ['Accept' => 'application/json']]);
        $this->post('/appointments/add-member', [
            'first_name' => 'Invalid',
            'last_name' => 'Contact',
            'membership_number' => 3003,
            'join_date' => '2020-01-01',
            'contact_method_type' => 999,
            'contact_method' => '',
        ]);

        $this->assertResponseCode(422);
    }
}
