<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class EmailGroupsControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * @var array<string>
     */
    protected array $fixtures = ['app.Groups', 'app.Sections', 'app.Teams', 'app.EmailGroups'];

    public function testIndexListsEmailGroupsAndNavigation(): void
    {
        $this->get('/email-groups');

        $this->assertResponseOk();
        $this->assertResponseContains('Digital Team Leaders');
        $this->assertResponseContains('digital-leaders@district.example.org');
        $this->assertResponseContains('First Scout Group');
        $this->assertResponseContains('href="/email-groups"');
        $this->assertResponseContains('href="/email-groups/add"');
        $this->assertResponseContains('href="/email-groups/edit/66666666-6666-4666-8666-666666666661"');
        $this->assertResponseContains('/email-groups/delete/66666666-6666-4666-8666-666666666661');
    }

    public function testAddCreatesAnEmailGroup(): void
    {
        $this->enableCsrfToken();
        $this->post('/email-groups/add', [
            'group_id' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',
            'team_id' => '11111111-1111-4111-8111-111111111112',
            'section_id' => null,
            'email_group_name' => 'District Volunteers',
            'email_address' => 'volunteers@district.example.org',
        ]);

        $this->assertRedirect('/email-groups');
        $this->assertTrue($this->fetchTable('EmailGroups')->exists([
            'email_address' => 'volunteers@district.example.org',
        ]));
    }

    public function testEditUsesTheSharedTeamPicker(): void
    {
        $this->get('/email-groups/edit/66666666-6666-4666-8666-666666666661');

        $this->assertResponseOk();
        $this->assertResponseContains('data-team-selector');
        $this->assertResponseContains('team-selector.js');
    }

    public function testDeleteRemovesAnEmailGroup(): void
    {
        $this->enableCsrfToken();
        $this->delete('/email-groups/delete/66666666-6666-4666-8666-666666666661');

        $this->assertRedirect('/email-groups');
        $this->assertFalse($this->fetchTable('EmailGroups')->exists([
            'id' => '66666666-6666-4666-8666-666666666661',
        ]));
    }
}
