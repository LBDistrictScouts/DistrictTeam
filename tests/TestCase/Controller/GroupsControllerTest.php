<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\Core\Configure;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class GroupsControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Groups', 'app.Teams', 'app.Roles', 'app.Members', 'app.MemberContactMethods',
        'app.Appointments',
    ];

    public function testReportCardListsVacantRolesAndNonGroupEmails(): void
    {
        $this->get('/groups/report-card');

        $this->assertResponseOk();
        $this->assertResponseContains('Report Card');
        $this->assertResponseContains('Vacant Role');
        $this->assertResponseContains('District Team');
        $this->assertResponseContains('Ada Lovelace');
        $this->assertResponseContains('ada@example.com');
        $this->assertResponseContains('>1</strong>');
        $this->assertResponseContains('Vacant role');
        $this->assertResponseContains('Non-group email');
        $this->assertResponseContains('Trustee Board appointments');
        $this->assertResponseContains('report-card-stat-danger');
    }

    public function testGroupReportCardIsScopedToItsGroup(): void
    {
        $this->get('/groups/report-card/aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa');

        $this->assertResponseOk();
        $this->assertResponseContains('District Report Card');
        $this->assertResponseContains('Vacant Role');
        $this->assertResponseContains('Ada Lovelace');
        $this->assertResponseContains('ada@example.com');
        $this->assertResponseNotContains('First Scout Group');
        $this->assertResponseContains('Trustee Board appointments');
        $this->assertResponseContains('Group Lead Volunteer');
        $this->assertResponseContains('This Trustee Board role needs an appointment.');
        $this->assertResponseContains('5 Trustee Board member places need appointments.');
    }

    public function testGroupReportCardUsesTheConfiguredTrusteeBoardTarget(): void
    {
        $target = Configure::read('TrusteeBoard.targetAppointments');
        Configure::write('TrusteeBoard.targetAppointments', 4);

        try {
            $this->get('/groups/report-card/aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa');

            $this->assertResponseOk();
            $this->assertResponseContains('1 Trustee Board member place needs an appointment.');
        } finally {
            Configure::write('TrusteeBoard.targetAppointments', $target);
        }
    }

    public function testReportCardListsCoveredRolesWithTheirCoverageDate(): void
    {
        $this->fetchTable('Roles')->updateAll([
            'is_covered_until' => '2099-01-01',
        ], ['id' => '22222222-2222-4222-8222-222222222222']);

        $this->get('/groups/report-card');

        $this->assertResponseOk();
        $this->assertResponseContains('Covered roles');
        $this->assertResponseContains('Vacant Role');
        $this->assertResponseContains('Covered until 1 January 2099');
        $this->assertResponseContains('/roles?status=covered');
    }

    public function testReportCardListsMultiPersonRolesWithoutAppointmentsAsVacant(): void
    {
        $this->fetchTable('Roles')->updateAll([
            'multi_member_role' => true,
        ], ['id' => '22222222-2222-4222-8222-222222222222']);

        $this->get('/groups/report-card');

        $this->assertResponseOk();
        $this->assertResponseContains('Vacant Role');
        $this->assertResponseContains('>Vacant</span>');
    }

    public function testGroupReportCardListsTrusteeAppointments(): void
    {
        $this->fetchTable('Roles')->updateAll([
            'is_trustee_role' => true,
            'template' => 'group-lead-volunteer',
        ], ['id' => '22222222-2222-4222-8222-222222222221']);
        $roles = $this->fetchTable('Roles');
        $additionalTrusteeRole = $roles->saveOrFail($roles->newEntity([
            'team_id' => '11111111-1111-4111-8111-111111111111',
            'name' => 'Additional Trustee Role',
            'currently_filled' => false,
            'is_lead' => false,
            'multi_member_role' => false,
            'is_trustee_role' => true,
        ]));
        $appointments = $this->fetchTable('Appointments');
        $appointments->saveOrFail($appointments->newEntity([
            'role_id' => $additionalTrusteeRole->id,
            'member_id' => '33333333-3333-4333-8333-333333333331',
            'member_contact_method_id' => '44444444-4444-4444-8444-444444444441',
            'effective_start_date' => '2020-01-01',
        ]));

        $this->get('/groups/report-card/aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa');

        $this->assertResponseOk();
        $this->assertResponseContains('Ada Lovelace');
        $this->assertResponseContains('Appointed');
        $this->assertResponseContains('4 Trustee Board member places need appointments.');
        $this->assertMatchesRegularExpression(
            '/<strong>2<\/strong>\s*<span>Trustee Board appointments<\/span>/',
            (string)$this->_response->getBody(),
        );
    }

    public function testIndexLinksToEachGroupReportCard(): void
    {
        $this->get('/groups');

        $this->assertResponseOk();
        $this->assertResponseContains('/groups/report-card/aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa');
    }

    public function testViewLinksToItsReportCard(): void
    {
        $this->get('/groups/view/aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa');

        $this->assertResponseOk();
        $this->assertResponseContains('/groups/report-card/aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa');
    }
}
