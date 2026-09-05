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
        'app.Groups', 'app.Sections', 'app.Teams',
        'app.Roles', 'app.Members', 'app.MemberContactMethods', 'app.Appointments',
    ];

    /**
     * Test index method
     *
     * @return void
     * @link \App\Controller\RolesController::index()
     */
    public function testIndex(): void
    {
        $this->fetchTable('Roles')->updateAll(
            ['multi_member_role' => true],
            ['id' => '22222222-2222-4222-8222-222222222221'],
        );
        $this->assertTrue($this->fetchTable('Roles')->get(
            '22222222-2222-4222-8222-222222222221',
        )->multi_member_role);
        $this->get('/roles');
        $this->assertResponseOk();
        $this->assertResponseContains('Digital Lead');
        $this->assertResponseContains('Recruiting');
    }

    /**
     * Test view method
     *
     * @return void
     * @link \App\Controller\RolesController::view()
     */
    public function testView(): void
    {
        $this->fetchTable('Appointments')->saveOrFail(
            $this->fetchTable('Appointments')->newEntity([
                'role_id' => '22222222-2222-4222-8222-222222222221',
                'member_id' => '33333333-3333-4333-8333-333333333332',
                'member_contact_method_id' => '44444444-4444-4444-8444-444444444442',
                'effective_start_date' => '2020-01-01',
            ]),
        );

        $this->get('/roles/view/22222222-2222-4222-8222-222222222221');
        $this->assertResponseOk();
        $this->assertResponseContains('Digital Lead');
        $this->assertResponseContains('Current holders');
        $this->assertResponseContains('Ada Lovelace');
        $this->assertResponseContains('Grace Hopper');
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
            'multi_member_role' => true,
        ]);

        $this->assertRedirect('/roles');
        $role = $this->getTableLocator()->get('Roles')->find()->where([
            'slug' => 'programme-lead',
            'is_lead' => true,
        ])->firstOrFail();
        $this->assertTrue($role->multi_member_role);
    }

    public function testAddValidationFailure(): void
    {
        $this->enableCsrfToken();
        $this->post('/roles/add', []);

        $this->assertResponseOk();
        $this->assertResponseContains('The role could not be saved');
    }

    public function testAddDisplaysLeadCheckbox(): void
    {
        $this->get('/roles/add');

        $this->assertResponseOk();
        $this->assertResponseContains('type="checkbox" name="is_lead"');
        $this->assertResponseContains('type="checkbox" name="multi_member_role"');
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

    public function testEditDisplaysLeadCheckbox(): void
    {
        $this->get('/roles/edit/22222222-2222-4222-8222-222222222222');

        $this->assertResponseOk();
        $this->assertResponseContains('type="checkbox" name="is_lead"');
        $this->assertResponseContains('type="checkbox" name="multi_member_role"');
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
