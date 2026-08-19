<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\RolesController Test Case
 *
 * @link \App\Controller\RolesController
 */
class RolesControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Teams',
        'app.Roles',
    ];

    /**
     * Test index method
     *
     * @return void
     * @link \App\Controller\RolesController::index()
     */
    public function testIndex(): void
    {
        $this->get('/roles');
        $this->assertResponseOk();
        $this->assertResponseContains('Digital Lead');
    }

    /**
     * Test view method
     *
     * @return void
     * @link \App\Controller\RolesController::view()
     */
    public function testView(): void
    {
        $this->get('/roles/view/22222222-2222-4222-8222-222222222221');
        $this->assertResponseOk();
        $this->assertResponseContains('Digital Lead');
    }

    /**
     * Test add method
     *
     * @return void
     * @link \App\Controller\RolesController::add()
     */
    public function testAdd(): void
    {
        $this->enableCsrfToken();
        $this->post('/roles/add', [
            'team_id' => '11111111-1111-4111-8111-111111111111',
            'name' => 'Programme Lead',
            'description' => 'Runs programmes',
            'currently_filled' => false,
            'is_lead' => true,
        ]);

        $this->assertRedirect('/roles');
        $this->assertTrue($this->getTableLocator()->get('Roles')->exists([
            'slug' => 'programme-lead',
            'is_lead' => true,
        ]));
    }

    public function testAddValidationFailure(): void
    {
        $this->enableCsrfToken();
        $this->post('/roles/add', []);

        $this->assertResponseOk();
        $this->assertResponseContains('The role could not be saved');
    }

    /**
     * Test edit method
     *
     * @return void
     * @link \App\Controller\RolesController::edit()
     */
    public function testEdit(): void
    {
        $this->enableCsrfToken();
        $this->put('/roles/edit/22222222-2222-4222-8222-222222222222', [
            'team_id' => '11111111-1111-4111-8111-111111111111',
            'name' => 'Renamed Role',
            'currently_filled' => false,
        ]);

        $this->assertRedirect('/roles');
        $role = $this->getTableLocator()->get('Roles')
            ->get('22222222-2222-4222-8222-222222222222');
        $this->assertSame('renamed-role', $role->slug);
    }

    public function testEditValidationFailure(): void
    {
        $this->enableCsrfToken();
        $this->put('/roles/edit/22222222-2222-4222-8222-222222222222', [
            'team_id' => '',
            'name' => '',
        ]);

        $this->assertResponseOk();
        $this->assertResponseContains('The role could not be saved');
    }

    /**
     * Test delete method
     *
     * @return void
     * @link \App\Controller\RolesController::delete()
     */
    public function testDelete(): void
    {
        $this->enableCsrfToken();
        $this->delete('/roles/delete/22222222-2222-4222-8222-222222222222');

        $this->assertRedirect('/roles');
        $this->assertFalse($this->getTableLocator()->get('Roles')->exists([
            'id' => '22222222-2222-4222-8222-222222222222',
        ]));
    }
}
