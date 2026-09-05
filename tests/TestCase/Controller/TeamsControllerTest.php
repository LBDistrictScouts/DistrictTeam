<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\TeamsController Test Case
 *
 * @link \App\Controller\TeamsController
 */
class TeamsControllerTest extends TestCase
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
     * @link \App\Controller\TeamsController::index()
     */
    public function testIndex(): void
    {
        $this->get('/teams');
        $this->assertResponseOk();
        $this->assertResponseContains('District Team');
        $this->assertResponseContains('>> Digital Team');
        $this->assertResponseNotContains('class="crud-sidebar"');
        $this->assertResponseContains('href="/roles"');
        $this->assertResponseContains('href="/members"');
        $this->assertResponseContains('href="/appointments"');
        $this->assertResponseContains('href="/member-contact-methods"');
    }

    /**
     * Test view method
     *
     * @return void
     * @link \App\Controller\TeamsController::view()
     */
    public function testView(): void
    {
        $this->get('/teams/view/11111111-1111-4111-8111-111111111111');
        $this->assertResponseOk();
        $this->assertResponseContains('Digital Team');
        $this->assertResponseContains('Digital Lead');
        $this->assertResponseNotContains('class="side-nav"');
    }

    /**
     * Test add method
     *
     * @return void
     * @link \App\Controller\TeamsController::add()
     */
    public function testAdd(): void
    {
        $this->enableCsrfToken();
        $this->post('/teams/add', [
            'team_name' => 'Operations Team',
            'team_parent_id' => '11111111-1111-4111-8111-111111111111',
        ]);

        $this->assertRedirect('/teams');
        $this->assertTrue($this->getTableLocator()->get('Teams')->exists([
            'slug' => 'operations-team',
        ]));
    }

    public function testAddValidationFailure(): void
    {
        $this->enableCsrfToken();
        $this->post('/teams/add', ['team_name' => '']);

        $this->assertResponseOk();
        $this->assertResponseContains('The team could not be saved');
    }

    /**
     * Test edit method
     *
     * @return void
     * @link \App\Controller\TeamsController::edit()
     */
    public function testEdit(): void
    {
        $this->enableCsrfToken();
        $this->put('/teams/edit/11111111-1111-4111-8111-111111111112', [
            'team_name' => 'Technology Team',
            'team_parent_id' => '11111111-1111-4111-8111-111111111111',
        ]);

        $this->assertRedirect('/teams');
        $team = $this->getTableLocator()->get('Teams')
            ->get('11111111-1111-4111-8111-111111111112');
        $this->assertSame('technology-team', $team->slug);
    }

    public function testEditValidationFailure(): void
    {
        $this->enableCsrfToken();
        $this->put('/teams/edit/11111111-1111-4111-8111-111111111112', [
            'team_name' => '',
        ]);

        $this->assertResponseOk();
        $this->assertResponseContains('The team could not be saved');
    }

    /**
     * Test delete method
     *
     * @return void
     * @link \App\Controller\TeamsController::delete()
     */
    public function testDelete(): void
    {
        $teams = $this->getTableLocator()->get('Teams');
        $team = $teams->saveOrFail($teams->newEntity(['team_name' => 'Temporary Team']));

        $this->enableCsrfToken();
        $this->delete('/teams/delete/' . $team->id);

        $this->assertRedirect('/teams');
        $this->assertFalse($teams->exists(['id' => $team->id]));
    }
}
