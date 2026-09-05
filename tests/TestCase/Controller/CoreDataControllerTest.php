<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class CoreDataControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * @var array<string>
     */
    protected array $fixtures = ['app.Groups', 'app.Sections'];

    /**
     * @return void
     */
    public function testGroupsListingAndNavigation(): void
    {
        $this->get('/groups');
        $this->assertResponseOk();
        $this->assertResponseContains('District');
        $this->assertResponseContains('First Scout Group');
        $this->assertResponseContains('href="/groups"');
        $this->assertResponseContains('href="/sections"');
        $this->assertResponseContains('href="/groups/view/aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa"');
        $this->assertResponseContains('class="workspace-status workspace-group-type-district"');
        $this->assertResponseContains('>District</span>');
        $this->assertResponseContains('>Sections</a>');
        $this->assertResponseContains('>Teams</a>');
        $this->assertResponseContains('>Roles</a>');
        $this->assertResponseNotContains('>OSM ID</a>');
    }

    /**
     * @return void
     */
    public function testGroupViewShowsItsSectionsAndDetails(): void
    {
        $this->get('/groups/view/bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb');
        $this->assertResponseOk();
        $this->assertResponseContains('First Scout Group');
        $this->assertResponseContains('class="group-type-pill group-type-group"');
        $this->assertResponseContains('group.example.org');
        $this->assertResponseContains('First Cubs');
        $this->assertResponseContains('Monday · 18:00 – 19:30');
        $this->assertResponseContains('No teams have been added');
    }

    /**
     * @return void
     */
    public function testSectionsListingIncludesGroup(): void
    {
        $this->get('/sections');
        $this->assertResponseOk();
        $this->assertResponseContains('First Cubs');
        $this->assertResponseContains('First Scout Group');
    }

    /**
     * @return void
     */
    public function testGroupSortCanOverrideDefaultOrder(): void
    {
        $groups = $this->fetchTable('Groups');
        $groups->updateAll(['sort_order' => 3], ['group_name' => 'District']);
        $this->get('/groups');
        $body = (string)$this->_response->getBody();
        $this->assertLessThan(strpos($body, '>District</a>'), strpos($body, '>First Scout Group</a>'));

        $this->get('/groups?sort=group_name&direction=asc');
        $body = (string)$this->_response->getBody();
        $this->assertLessThan(strpos($body, '>First Scout Group</a>'), strpos($body, '>District</a>'));

        $this->get('/groups?sort=sort_order&direction=desc');
        $body = (string)$this->_response->getBody();
        $this->assertLessThan(strpos($body, '>First Scout Group</a>'), strpos($body, '>District</a>'));
    }
}
