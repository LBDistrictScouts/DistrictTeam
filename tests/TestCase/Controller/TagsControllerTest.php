<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class TagsControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
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
        'app.EmailGroups',
        'app.Tags',
        'app.TeamTags',
        'app.RoleTags',
        'app.AppointmentTags',
    ];

    public function testTagFormsOfferTypeSpecificRowPicker(): void
    {
        $this->get('/tags/add');

        $this->assertResponseOk();
        $this->assertResponseContains('data-tag-assignment-picker');
        $this->assertResponseContains('data-assignment-modal="team"');
        $this->assertResponseContains('data-assignment-modal="role"');
        $this->assertResponseContains('data-assignment-modal="appointment"');
        $this->assertResponseContains('data-role-selector');
        $this->assertResponseContains('name="email_group_id"');
        $this->assertResponseContains('tags-assignment-picker.js');
        $this->assertResponseNotContains('multiple="multiple"');

        $html = (string)$this->_response->getBody();
        $this->assertSame(
            1,
            preg_match('/<dialog[^>]*data-assignment-modal="role".*?<\/dialog>/s', $html, $roleDialog),
        );
        $this->assertDoesNotMatchRegularExpression('/\srequired(?:="required")?/', $roleDialog[0]);
    }

    public function testIndexListsTagsAndDeleteRemovesAssignments(): void
    {
        $tag = $this->createTag('Delete me', 'delete-me', [
            'teams' => ['_ids' => ['11111111-1111-4111-8111-111111111112']],
            'roles' => ['_ids' => ['22222222-2222-4222-8222-222222222221']],
            'appointments' => ['_ids' => ['55555555-5555-4555-8555-555555555551']],
        ]);

        $this->get('/tags');
        $this->assertResponseOk();
        $this->assertResponseContains('Delete me');
        $this->assertResponseContains('/tags/view/' . $tag->id);

        $this->enableCsrfToken();
        $this->delete('/tags/delete/' . $tag->id);

        $this->assertRedirect('/tags');
        $this->assertFalse($this->fetchTable('Tags')->exists(['id' => $tag->id]));
        $this->assertFalse($this->fetchTable('TeamTags')->exists(['tag_id' => $tag->id]));
        $this->assertFalse($this->fetchTable('RoleTags')->exists(['tag_id' => $tag->id]));
        $this->assertFalse($this->fetchTable('AppointmentTags')->exists(['tag_id' => $tag->id]));
    }

    public function testCreateAndEditAssignAtEachLevelAndAllowFalseCognitoFlag(): void
    {
        $this->enableCsrfToken();
        $this->post('/tags/add', [
            'tag_label' => 'Workflow access',
            'tag_code' => 'workflow-access',
            'is_cognito_permissions_group' => '0',
            'email_group_id' => '66666666-6666-4666-8666-666666666661',
            'teams' => ['_ids' => ['11111111-1111-4111-8111-111111111112']],
            'roles' => ['_ids' => ['22222222-2222-4222-8222-222222222221']],
            'appointments' => ['_ids' => ['55555555-5555-4555-8555-555555555551']],
        ]);

        $tag = $this->fetchTable('Tags')->find()->where(['tag_code' => 'workflow-access'])->firstOrFail();
        $this->assertRedirect('/tags/view/' . $tag->id);
        $this->assertFalse($tag->is_cognito_permissions_group);
        $this->assertSame('66666666-6666-4666-8666-666666666661', $tag->email_group_id);
        $tag = $this->fetchTable('Tags')->get($tag->id, contain: ['Teams', 'Roles', 'Appointments']);
        $this->assertCount(1, $tag->teams);
        $this->assertCount(1, $tag->roles);
        $this->assertCount(1, $tag->appointments);

        $this->put('/tags/edit/' . $tag->id, [
            'tag_label' => 'Workflow access',
            'tag_code' => 'workflow-access',
            'is_cognito_permissions_group' => '0',
            'email_group_id' => '',
            'teams' => ['_ids' => []],
            'roles' => ['_ids' => ['22222222-2222-4222-8222-222222222221']],
            'appointments' => ['_ids' => []],
        ]);
        $this->assertRedirect('/tags/view/' . $tag->id);

        $tag = $this->fetchTable('Tags')->get($tag->id, contain: ['Teams', 'Roles', 'Appointments']);
        $this->assertFalse($tag->is_cognito_permissions_group);
        $this->assertNull($tag->email_group_id);
        $this->assertCount(0, $tag->teams);
        $this->assertCount(1, $tag->roles);
        $this->assertCount(0, $tag->appointments);
    }

    public function testTagViewShowsCurrentMemberAppointmentAndContactMethod(): void
    {
        $tag = $this->createTag('Current members', 'current-members', [
            'teams' => ['_ids' => ['11111111-1111-4111-8111-111111111112']],
        ]);

        $this->get('/tags/view/' . $tag->id);

        $this->assertResponseOk();
        $this->assertResponseContains('Members currently matched by this tag');
        $this->assertResponseContains('Show only appointments with group email addresses');
        $this->assertMatchesRegularExpression(
            '/<input\s+id="tag-group-emails-only"[^>]*checked/s',
            (string)$this->_response->getBody(),
        );
        $this->assertResponseNotContains('Ada Lovelace');
        $this->assertResponseNotContains('ada@example.com');

        $this->get('/tags/view/' . $tag->id . '?group_emails_only=0');
        $this->assertResponseOk();
        $this->assertResponseContains('Ada Lovelace');
        $this->assertResponseContains('/appointments/view/55555555-5555-4555-8555-555555555551');
        $this->assertResponseContains('ada@example.com');
        $this->assertDoesNotMatchRegularExpression(
            '/<input\s+id="tag-group-emails-only"[^>]*checked/s',
            (string)$this->_response->getBody(),
        );
        $this->assertMatchesRegularExpression(
            '/<nav class="workspace-actions".*?Edit tag.*?Delete tag.*?<\/nav>/s',
            (string)$this->_response->getBody(),
        );
        $this->assertResponseNotContains('tag-delete-action');
    }

    public function testTagAssignmentsAreVisibleInRelatedViewsButNotEditedThere(): void
    {
        $tag = $this->createTag('Operations access', 'operations-access', [
            'teams' => ['_ids' => ['11111111-1111-4111-8111-111111111112']],
            'roles' => ['_ids' => ['22222222-2222-4222-8222-222222222221']],
            'appointments' => ['_ids' => ['55555555-5555-4555-8555-555555555551']],
        ]);

        foreach (
            [
            '/teams/view/11111111-1111-4111-8111-111111111112',
            '/roles/view/22222222-2222-4222-8222-222222222221',
            '/appointments/view/55555555-5555-4555-8555-555555555551',
            '/members/view/33333333-3333-4333-8333-333333333331',
            ] as $url
        ) {
            $this->get($url);
            $this->assertResponseOk();
            $this->assertResponseContains('Operations access');
        }

        foreach (
            [
            '/teams/edit/11111111-1111-4111-8111-111111111112',
            '/roles/edit/22222222-2222-4222-8222-222222222221',
            '/appointments/edit/55555555-5555-4555-8555-555555555551',
            ] as $url
        ) {
            $this->get($url);
            $this->assertResponseOk();
            $this->assertResponseNotContains('data-tag-assignment-picker');
        }

        $this->assertTrue($this->fetchTable('Tags')->exists(['id' => $tag->id]));
    }

    public function testAssignmentSearchReturnsTeamsAndAppointmentsAsJson(): void
    {
        $this->get('/tags/assignment-options?type=team&q=digital');
        $this->assertResponseOk();
        $this->assertContentType('application/json');
        $teamResults = json_decode((string)$this->_response->getBody(), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame('Digital Team · District', $teamResults['results'][0]['text']);

        $this->get('/tags/assignment-options?type=appointment&q=ada');
        $this->assertResponseOk();
        $this->assertContentType('application/json');
        $appointmentResults = json_decode((string)$this->_response->getBody(), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame('Ada Lovelace · Digital Lead · 01 Jan 2020', $appointmentResults['results'][0]['text']);
    }

    /** @param array<string, mixed> $assignments */
    private function createTag(string $label, string $code, array $assignments = []): object
    {
        $tags = $this->fetchTable('Tags');
        $tag = $tags->newEntity([
            'tag_label' => $label,
            'tag_code' => $code,
        ] + $assignments, ['associated' => ['Teams', 'Roles', 'Appointments']]);

        return $tags->saveOrFail($tag);
    }
}
