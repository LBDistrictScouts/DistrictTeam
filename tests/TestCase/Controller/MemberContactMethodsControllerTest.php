<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\MemberContactMethodsController Test Case
 *
 * @link \App\Controller\MemberContactMethodsController
 */
class MemberContactMethodsControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Members',
        'app.MemberContactMethods',
    ];

    /**
     * Test index method
     *
     * @return void
     * @link \App\Controller\MemberContactMethodsController::index()
     */
    public function testIndex(): void
    {
        $this->get('/member-contact-methods');
        $this->assertResponseOk();
        $this->assertResponseContains('ada@example.com');
    }

    /**
     * Test view method
     *
     * @return void
     * @link \App\Controller\MemberContactMethodsController::view()
     */
    public function testView(): void
    {
        $this->get('/member-contact-methods/view/44444444-4444-4444-8444-444444444441');
        $this->assertResponseOk();
        $this->assertResponseContains('Email');
    }

    /**
     * Test add method
     *
     * @return void
     * @link \App\Controller\MemberContactMethodsController::add()
     */
    public function testAdd(): void
    {
        $this->enableCsrfToken();
        $this->post('/member-contact-methods/add', [
            'member_id' => '33333333-3333-4333-8333-333333333331',
            'contact_method' => 'new@example.com',
            'contact_method_type' => 1,
        ]);

        $this->assertRedirect('/member-contact-methods');
        $this->assertTrue($this->getTableLocator()->get('MemberContactMethods')->exists([
            'contact_method' => 'new@example.com',
        ]));
    }

    public function testAddValidationFailure(): void
    {
        $this->enableCsrfToken();
        $this->post('/member-contact-methods/add', []);

        $this->assertResponseOk();
        $this->assertResponseContains('The member contact method could not be saved');
    }

    /**
     * Test edit method
     *
     * @return void
     * @link \App\Controller\MemberContactMethodsController::edit()
     */
    public function testEdit(): void
    {
        $this->enableCsrfToken();
        $this->put('/member-contact-methods/edit/44444444-4444-4444-8444-444444444442', [
            'member_id' => '33333333-3333-4333-8333-333333333332',
            'contact_method' => '07111111111',
            'contact_method_type' => 10,
        ]);

        $this->assertRedirect('/member-contact-methods');
        $contact = $this->getTableLocator()->get('MemberContactMethods')
            ->get('44444444-4444-4444-8444-444444444442');
        $this->assertSame('07111111111', $contact->contact_method);
    }

    public function testEditValidationFailure(): void
    {
        $this->enableCsrfToken();
        $this->put(
            '/member-contact-methods/edit/44444444-4444-4444-8444-444444444442',
            ['member_id' => '', 'contact_method' => '', 'contact_method_type' => 999],
        );

        $this->assertResponseOk();
        $this->assertResponseContains('The member contact method could not be saved');
    }

    /**
     * Test delete method
     *
     * @return void
     * @link \App\Controller\MemberContactMethodsController::delete()
     */
    public function testDelete(): void
    {
        $this->enableCsrfToken();
        $this->delete(
            '/member-contact-methods/delete/44444444-4444-4444-8444-444444444442',
        );

        $this->assertRedirect('/member-contact-methods');
        $this->assertFalse($this->getTableLocator()->get('MemberContactMethods')->exists([
            'id' => '44444444-4444-4444-8444-444444444442',
        ]));
    }

    public function testAddForMemberAjax(): void
    {
        $this->enableCsrfToken();
        $this->configRequest([
            'headers' => [
                'Accept' => 'application/json',
                'X-Requested-With' => 'XMLHttpRequest',
            ],
        ]);
        $this->post(
            '/member-contact-methods/add-for-member/33333333-3333-4333-8333-333333333331',
            [
                'contact_method' => 'ajax@example.com',
                'contact_method_type' => 1,
            ],
        );

        $this->assertResponseOk();
        $payload = json_decode((string)$this->_response->getBody(), true);
        $this->assertTrue($payload['success']);
        $this->assertSame('Email', $payload['contactMethod']['contact_method_type']);
    }

    public function testAddForMemberAjaxValidationFailure(): void
    {
        $this->enableCsrfToken();
        $this->configRequest(['headers' => ['Accept' => 'application/json']]);
        $this->post(
            '/member-contact-methods/add-for-member/33333333-3333-4333-8333-333333333331',
            ['contact_method' => '', 'contact_method_type' => 999],
        );

        $this->assertResponseCode(422);
    }
}
