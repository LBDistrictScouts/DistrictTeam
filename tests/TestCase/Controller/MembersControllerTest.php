<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\MembersController Test Case
 *
 * @link \App\Controller\MembersController
 */
class MembersControllerTest extends TestCase
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
     * @link \App\Controller\MembersController::index()
     */
    public function testIndex(): void
    {
        $this->get('/members');
        $this->assertResponseOk();
        $this->assertResponseContains('Ada');
    }

    /**
     * Test view method
     *
     * @return void
     * @link \App\Controller\MembersController::view()
     */
    public function testView(): void
    {
        $this->get('/members/view/33333333-3333-4333-8333-333333333331');
        $this->assertResponseOk();
        $this->assertResponseContains('Ada Lovelace');
        $this->assertResponseContains('ada@example.com');
    }

    /**
     * Test add method
     *
     * @return void
     * @link \App\Controller\MembersController::add()
     */
    public function testAdd(): void
    {
        $this->enableCsrfToken();
        $this->post('/members/add', [
            'first_name' => 'Dorothy',
            'last_name' => 'Vaughan',
            'membership_number' => 3001,
            'join_date' => '2020-01-01',
        ]);

        $this->assertRedirect('/members');
        $this->assertTrue($this->getTableLocator()->get('Members')->exists([
            'membership_number' => 3001,
            'active' => true,
        ]));
    }

    public function testAddValidationFailure(): void
    {
        $this->enableCsrfToken();
        $this->post('/members/add', []);

        $this->assertResponseOk();
        $this->assertResponseContains('The member could not be saved');
    }

    /**
     * Test edit method
     *
     * @return void
     * @link \App\Controller\MembersController::edit()
     */
    public function testEdit(): void
    {
        $this->enableCsrfToken();
        $this->put('/members/edit/33333333-3333-4333-8333-333333333331', [
            'first_name' => 'Augusta Ada',
            'last_name' => 'Lovelace',
            'membership_number' => 1001,
            'join_date' => '2020-01-01',
        ]);

        $this->assertRedirect('/members');
        $member = $this->getTableLocator()->get('Members')
            ->get('33333333-3333-4333-8333-333333333331');
        $this->assertSame('Augusta Ada', $member->first_name);
    }

    public function testEditValidationFailure(): void
    {
        $this->enableCsrfToken();
        $this->put('/members/edit/33333333-3333-4333-8333-333333333331', [
            'first_name' => '',
            'last_name' => '',
            'membership_number' => null,
            'join_date' => '',
        ]);

        $this->assertResponseOk();
        $this->assertResponseContains('The member could not be saved');
    }

    /**
     * Test delete method
     *
     * @return void
     * @link \App\Controller\MembersController::delete()
     */
    public function testDelete(): void
    {
        $this->enableCsrfToken();
        $this->delete('/members/delete/33333333-3333-4333-8333-333333333332');

        $this->assertRedirect('/members');
        $this->assertFalse($this->getTableLocator()->get('Members')->exists([
            'id' => '33333333-3333-4333-8333-333333333332',
        ]));
    }
}
