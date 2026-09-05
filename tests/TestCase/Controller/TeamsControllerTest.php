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
        'app.Groups',
        'app.Sections',
        'app.Teams',
        'app.Roles',
        'app.Members',
        'app.MemberContactMethods',
        'app.Appointments',
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
        $this->assertResponseNotContains('href="/member-contact-methods"');
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
        $this->assertResponseContains('Ada Lovelace');
        $this->assertResponseContains('Vacant Role');
        $this->assertResponseContains('No lead role has been designated');
        $this->assertResponseNotContains('Tree Left');
        $this->assertResponseNotContains('Tree Right');
        $this->assertResponseNotContains('class="side-nav"');
    }

    public function testViewShowsRecruitingMultiMemberRole(): void
    {
        $this->fetchTable('Roles')->updateAll(
            ['multi_member_role' => true],
            ['id' => '22222222-2222-4222-8222-222222222221'],
        );

        $this->get('/teams/view/11111111-1111-4111-8111-111111111112');

        $this->assertResponseOk();
        $this->assertResponseContains('Recruiting');
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
            'group_id' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', 'team_name' => 'Operations Team',
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
        $team = $teams->saveOrFail($teams->newEntity(['group_id' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', 'team_name' => 'Temporary Team']));

        $this->enableCsrfToken();
        $this->delete('/teams/delete/' . $team->id);

        $this->assertRedirect('/teams');
        $this->assertFalse($teams->exists(['id' => $team->id]));
    }

    /**
     * @return void
     */
    public function testTeamFormsSaveAndDisplayGroupAndSection(): void
    {
        $this->get('/teams/add');
        $this->assertResponseOk();
        $this->assertResponseContains('name="group_id"');
        $this->assertResponseContains('name="section_id"');
        $this->assertResponseContains('First Cubs');
        $this->enableCsrfToken();
        $this->post('/teams/edit/11111111-1111-4111-8111-111111111112', [
            'group_id' => 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb',
            'section_id' => 'cccccccc-cccc-4ccc-8ccc-cccccccccccc',
        ]);
        $this->assertRedirect('/teams');
        $this->get('/teams/view/11111111-1111-4111-8111-111111111112');
        $this->assertResponseContains('First Scout Group');
        $this->assertResponseContains('First Cubs');
        $this->get('/teams/edit/11111111-1111-4111-8111-111111111112');
        $this->assertResponseContains('value="cccccccc-cccc-4ccc-8ccc-cccccccccccc" selected="selected"');
        $this->enableCsrfToken();
        $this->post('/teams/edit/11111111-1111-4111-8111-111111111112', [
            'group_id' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',
        ]);
        $this->assertResponseOk();
        $this->assertResponseContains('Choose a section belonging to the selected group.');
        $this->assertSame(
            'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb',
            $this->fetchTable('Teams')->get('11111111-1111-4111-8111-111111111112')->group_id,
        );
    }

    /**
     * @return void
     */
    public function testReorderSavesWholeBranchesAndRejectsIncompleteLists(): void
    {
        $teams = $this->fetchTable('Teams');
        $root = $teams->saveOrFail($teams->newEntity(['group_id' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', 'team_name' => 'Operations']));
        $this->get('/teams/reorder');
        $this->assertResponseOk();
        $this->assertResponseContains('team-drag-handle');
        $this->assertResponseContains('Digital Team');
        $this->assertResponseContains('Operations');
        $order = [$root->id, '11111111-1111-4111-8111-111111111111', '11111111-1111-4111-8111-111111111112'];
        $this->enableCsrfToken();
        $this->post('/teams/reorder', ['order' => $order]);
        $this->assertRedirect('/teams');
        $this->assertSame($order, $teams->find()->orderByAsc('tree_left')->all()->extract('id')->toList());
        $this->assertSame([1, 2, 3], $teams->find()->orderByAsc('tree_left')->all()->extract('sort_order')->toList());
        $child = $teams->get($order[2]);
        $parent = $teams->get($order[1]);
        $this->assertSame($parent->id, $child->team_parent_id);
        $this->assertGreaterThan($parent->tree_left, $child->tree_left);
        $this->assertLessThan($parent->tree_right, $child->tree_right);
        $this->enableCsrfToken();
        $this->post('/teams/reorder', ['order' => [$root->id]]);
        $this->assertResponseOk();
        $this->assertResponseContains('The team list has changed');
        $this->assertSame($order, $teams->find()->orderByAsc('tree_left')->all()->extract('id')->toList());
    }

    /**
     * @return void
     */
    public function testTeamViewShowsCurrentLeadershipAndEmptyStates(): void
    {
        $this->get('/teams/view/11111111-1111-4111-8111-111111111112');
        $this->assertResponseOk();
        $this->assertResponseContains('Ada Lovelace');
        $this->assertResponseContains('Digital Lead');
        $this->assertResponseContains('This team has no child teams.');
        $this->assertResponseContains('Shared group UUID');
        $this->assertResponseContains('digital-team');
        $this->assertResponseContains('Edit team');

        $teams = $this->fetchTable('Teams');
        $empty = $teams->saveOrFail($teams->newEntity(['group_id' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', 'team_name' => 'New Team']));
        $this->get('/teams/view/' . $empty->id);
        $this->assertResponseOk();
        $this->assertResponseContains('No roles have been added to this team yet.');
        $this->assertResponseContains('This is a top-level team.');
    }

    /**
     * @return void
     */
    public function testScopedReorderKeepsOtherBranchesAndReturnsToParent(): void
    {
        $teams = $this->fetchTable('Teams');
        $parentId = '11111111-1111-4111-8111-111111111111';
        $digitalId = '11111111-1111-4111-8111-111111111112';
        $sibling = $teams->saveOrFail($teams->newEntity([
            'group_id' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', 'team_name' => 'Support Team', 'team_parent_id' => $parentId,
        ]));
        $grandchild = $teams->saveOrFail($teams->newEntity([
            'group_id' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', 'team_name' => 'Website Team', 'team_parent_id' => $digitalId,
        ]));
        $outside = $teams->saveOrFail($teams->newEntity(['group_id' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', 'team_name' => 'Outside Team']));
        $before = $teams->get($outside->id)->toArray();
        $parentBefore = $teams->get($parentId)->toArray();
        $url = '/teams/reorder/' . $parentId;
        $this->get('/teams/view/' . $parentId);
        $this->assertResponseContains('href="' . $url . '"');
        $this->get($url);
        $this->assertResponseOk();
        $this->assertResponseContains('Reorder teams within District Team');
        $this->assertResponseContains('Website Team');
        $this->assertResponseContains('Support Team');
        $this->assertResponseNotContains('Outside Team');
        $this->assertResponseNotContains('name="order[]" value="' . $parentId . '"');
        $this->assertResponseContains('action="' . $url . '"');

        $this->enableCsrfToken();
        $this->post($url, ['order' => [$sibling->id, $digitalId, $grandchild->id]]);
        $this->assertRedirect('/teams/view/' . $parentId);
        $this->assertSame(
            [$sibling->id, $digitalId, $grandchild->id],
            $teams->reorderQuery($parentId)->orderByAsc('tree_left')->all()->extract('id')->toList(),
        );
        $this->assertSame($digitalId, $teams->get($grandchild->id)->team_parent_id);
        $this->assertSame($before, $teams->get($outside->id)->toArray());
        $this->assertSame($parentBefore, $teams->get($parentId)->toArray());

        $this->enableCsrfToken();
        $this->post($url, ['order' => [$outside->id, $digitalId, $grandchild->id]]);
        $this->assertResponseOk();
        $this->assertResponseContains('The team list has changed');
        $this->assertSame($before, $teams->get($outside->id)->toArray());
        $this->get('/teams/reorder/not-a-uuid');
        $this->assertResponseCode(404);
        $this->get('/teams/reorder/99999999-9999-4999-8999-999999999999');
        $this->assertResponseCode(404);
    }

    /**
     * @return void
     */
    public function testTreeCoordinatesAreNotFormFields(): void
    {
        foreach (['/teams/add', '/teams/edit/11111111-1111-4111-8111-111111111112'] as $url) {
            $this->get($url);
            $this->assertResponseOk();
            foreach (['tree_left', 'tree_right', 'tree_level'] as $field) {
                $this->assertResponseNotContains('name="' . $field . '"');
            }
        }
    }

    /**
     * @return void
     */
    public function testParentChoicesIncludeScopeAndExcludeSelfAndDescendants(): void
    {
        $teams = $this->fetchTable('Teams');
        $parentId = '11111111-1111-4111-8111-111111111111';
        $childId = '11111111-1111-4111-8111-111111111112';
        $groupId = 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb';
        $candidate = $teams->saveOrFail($teams->newEntity([
            'team_name' => 'Group Parent', 'group_id' => $groupId,
            'section_id' => 'cccccccc-cccc-4ccc-8ccc-cccccccccccc',
        ]));
        $this->get('/teams/edit/' . $parentId);
        $this->assertResponseOk();
        $this->assertResponseNotContains('<option value="' . $parentId . '"');
        $this->assertResponseNotContains('<option value="' . $childId . '"');
        $this->assertResponseContains('value="' . $candidate->id . '"');
        $this->assertResponseContains('data-group-id="' . $groupId . '"');
        $this->assertResponseContains('data-section-id="cccccccc-cccc-4ccc-8ccc-cccccccccccc"');
        $this->assertResponseContains('team-parent-filter.js');
    }
}
