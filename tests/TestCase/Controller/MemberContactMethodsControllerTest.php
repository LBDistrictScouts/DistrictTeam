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
        'app.Groups',
        'app.Teams',
        'app.Roles',
        'app.Members',
        'app.MemberContactMethods',
        'app.Appointments',
    ];

    public function testStandaloneCrudRoutesAreUnavailable(): void
    {
        foreach (
            [
            '/member-contact-methods',
            '/member-contact-methods/view/44444444-4444-4444-8444-444444444441',
            '/member-contact-methods/add',
            '/member-contact-methods/edit/44444444-4444-4444-8444-444444444442',
            '/member-contact-methods/delete/44444444-4444-4444-8444-444444444442',
            ] as $url
        ) {
            $this->get($url);
            $this->assertResponseCode(404);
        }
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

    public function testDeleteForMemberDeletesUnusedContactMethod(): void
    {
        $this->enableCsrfToken();
        $this->post('/member-contact-methods/delete-for-member/33333333-3333-4333-8333-333333333332/44444444-4444-4444-8444-444444444442');

        $this->assertRedirect('/members/view/33333333-3333-4333-8333-333333333332');
        $this->assertFalse($this->fetchTable('MemberContactMethods')->exists([
            'id' => '44444444-4444-4444-8444-444444444442',
        ]));
    }

    public function testDeleteForMemberRetainsContactMethodUsedByAppointment(): void
    {
        $this->enableCsrfToken();
        $this->post('/member-contact-methods/delete-for-member/33333333-3333-4333-8333-333333333331/44444444-4444-4444-8444-444444444441');

        $this->assertRedirect('/members/view/33333333-3333-4333-8333-333333333331');
        $this->assertFlashMessage('This contact method cannot be deleted because it is used by an appointment.');
        $this->assertTrue($this->fetchTable('MemberContactMethods')->exists([
            'id' => '44444444-4444-4444-8444-444444444441',
        ]));
    }
}
