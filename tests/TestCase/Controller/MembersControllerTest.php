<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Service\MemberCsvImporter;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Laminas\Diactoros\UploadedFile;

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
        'app.Teams',
        'app.Roles',
        'app.Members',
        'app.MemberContactMethods',
        'app.Appointments', 'app.CsvRoleMappings',
    ];

    public function testUploadForm(): void
    {
        $this->get('/members/upload');
        $this->assertResponseOk();
        $this->assertResponseContains('multipart/form-data');
    }

    public function testUploadMappingAndImport(): void
    {
        $stream = fopen(CONFIG . 'Examples/directory-example.csv', 'r');
        $upload = new UploadedFile($stream, null, UPLOAD_ERR_OK, 'members.csv', 'text/csv');
        $this->enableCsrfToken();
        $this->post('/members/upload', ['csv' => $upload]);
        $this->assertResponseOk();
        $this->assertResponseContains('Map CSV roles');
        $this->assertResponseContains('Digital Team / Digital Lead');
        $this->assertSame(2, $this->fetchTable('Members')->find()->count());
        $pending = $_SESSION['MemberCsvUpload'];
        $sources = (new MemberCsvImporter())->sources($pending['rows']);
        $mapping = array_fill_keys(array_keys($sources), '22222222-2222-4222-8222-222222222222');
        $this->session(['MemberCsvUpload' => $pending]);
        $this->post('/members/upload', [
            'step' => 'import', 'token' => $pending['token'], 'mapping' => $mapping,
            'units' => ['unit:Letchworth And Baldock'],
        ]);
        $this->assertResponseOk();
        $this->assertResponseContains('CSV imported successfully.');
        $this->assertSession(null, 'MemberCsvUpload');
        $member = $this->fetchTable('Members')->find()->where(['membership_number' => 475931])->firstOrFail();
        $this->assertTrue($this->fetchTable('Appointments')->exists([
            'member_id' => $member->id, 'role_id' => '22222222-2222-4222-8222-222222222222',
        ]));
    }

    public function testEveryMappingOffersFilledAndVacantRoles(): void
    {
        $importer = new MemberCsvImporter();
        $upload = new UploadedFile(
            fopen(CONFIG . 'Examples/directory-example.csv', 'r'),
            null,
            UPLOAD_ERR_OK,
            'members.csv',
            'text/csv',
        );
        $rows = $importer->read($upload);
        $this->session(['MemberCsvUpload' => ['token' => 'test-token', 'rows' => $rows]]);
        $this->assertFalse($this->fetchTable('Roles')->get('22222222-2222-4222-8222-222222222222')->currently_filled);
        $this->assertFalse($this->fetchTable('Appointments')->exists([
            'role_id' => '22222222-2222-4222-8222-222222222222',
        ]));

        $this->get('/members/upload');
        $this->assertResponseOk();
        $html = (string)$this->_response->getBody();
        $sourceCount = count($importer->sources($rows));
        foreach (
            [
            '22222222-2222-4222-8222-222222222221' => 'Digital Team / Digital Lead',
            '22222222-2222-4222-8222-222222222222' => 'District Team / Vacant Role',
            ] as $id => $label
        ) {
            $this->assertSame($sourceCount, substr_count($html, '<option value="' . $id . '">' . $label . '</option>'));
        }
    }

    public function testSavedMappingsArePreselectedOnNextUpload(): void
    {
        $importer = new MemberCsvImporter();
        $upload = new UploadedFile(
            fopen(CONFIG . 'Examples/directory-example.csv', 'r'),
            null,
            UPLOAD_ERR_OK,
            'members.csv',
            'text/csv',
        );
        $rows = $importer->read($upload);
        $mapping = array_fill_keys(array_keys($importer->sources($rows)), 'skip');
        $key = array_key_first($mapping);
        $mapping[$key] = '22222222-2222-4222-8222-222222222222';
        $importer->import($rows, $mapping);
        $this->session(['MemberCsvUpload' => ['token' => 'new-upload', 'rows' => $rows]]);
        $this->get('/members/upload');
        $this->assertResponseOk();
        $html = (string)$this->_response->getBody();
        $this->assertStringContainsString(
            '<option value="22222222-2222-4222-8222-222222222222" selected="selected">',
            $html,
        );
        $this->assertSame(count($mapping) - 1, substr_count($html, '<option value="skip" selected="selected">'));
    }

    public function testSelectedUnitsImportUnmappedMembersOnly(): void
    {
        $rows = [
            2 => ['First name' => 'Selected', 'Last name' => 'Person', 'Membership number' => '9090',
                'Start date' => '01 Aug 2026', 'Unit name' => 'Selected Unit',
                'Communication email' => 'selected@example.com'],
            3 => ['First name' => 'Excluded', 'Last name' => 'Person', 'Membership number' => '9091',
                'Start date' => 'invalid', 'Unit name' => 'Excluded Unit'],
        ];
        $this->session(['MemberCsvUpload' => ['token' => 'test', 'rows' => $rows]]);
        $this->enableCsrfToken();
        $this->post('/members/upload', [
            'step' => 'import', 'token' => 'test', 'units' => ['unit:Selected Unit'], 'mapping' => [],
        ]);
        $this->assertResponseOk();
        $this->assertResponseContains('CSV imported successfully.');
        $member = $this->fetchTable('Members')->find()->where(['membership_number' => 9090])->firstOrFail();
        $this->assertTrue($this->fetchTable('MemberContactMethods')->exists(['member_id' => $member->id]));
        $this->assertFalse($this->fetchTable('Members')->exists(['membership_number' => 9091]));
        $this->assertFalse($this->fetchTable('Appointments')->exists(['member_id' => $member->id]));
        $this->assertSame(0, $this->fetchTable('CsvRoleMappings')->find()->count());
    }

    public function testNoSelectedUnitsDoesNotImport(): void
    {
        $this->session(['MemberCsvUpload' => ['token' => 'test', 'rows' => [
            2 => ['Unit name' => 'Unit A'],
        ]]]);
        $this->enableCsrfToken();
        $this->post('/members/upload', ['step' => 'import', 'token' => 'test', 'units' => '']);
        $this->assertResponseOk();
        $this->assertResponseContains('Select at least one unit');
        $this->assertSame(2, $this->fetchTable('Members')->find()->count());
    }

    public function testExpiredMappingUpload(): void
    {
        $this->enableCsrfToken();
        $this->post('/members/upload', ['step' => 'import', 'token' => 'expired']);
        $this->assertResponseOk();
        $this->assertResponseContains('This upload has expired');
    }

    public function testMismatchedTokenPreservesPendingUpload(): void
    {
        $pending = ['token' => 'current-token', 'rows' => [2 => [
            'First name' => 'Test', 'Last name' => 'Person', 'Membership number' => '9090',
            'Start date' => '01 Aug 2026', 'Unit name' => 'Unit A',
        ]]];
        $this->session(['MemberCsvUpload' => $pending]);
        $this->enableCsrfToken();
        $this->post('/members/upload', [
            'step' => 'import', 'token' => 'old-token', 'units' => ['unit:Unit A'], 'mapping' => [],
        ]);

        $this->assertResponseOk();
        $this->assertResponseContains('This upload has expired');
        $this->assertSession($pending, 'MemberCsvUpload');
        $this->assertFalse($this->fetchTable('Members')->exists(['membership_number' => 9090]));
    }

    public function testFailedMappingCanBeCorrectedAndRetried(): void
    {
        $pending = ['token' => 'retry-token', 'rows' => [2 => [
            'First name' => 'Test', 'Last name' => 'Person', 'Membership number' => '9090',
            'Start date' => '01 Aug 2026', 'Unit name' => 'Unit A',
            'Communication email' => 'test@example.com',
        ]]];
        $key = array_key_first((new MemberCsvImporter())->sources($pending['rows']));
        $this->session(['MemberCsvUpload' => $pending]);
        $this->enableCsrfToken();
        $request = ['step' => 'import', 'token' => $pending['token'], 'units' => ['unit:Unit A']];
        $this->post('/members/upload', $request + ['mapping' => [$key => 'invalid-role']]);

        $this->assertResponseOk();
        $this->assertResponseContains('Nothing was imported');
        $this->assertSession($pending, 'MemberCsvUpload');
        $this->assertFalse($this->fetchTable('Members')->exists(['membership_number' => 9090]));
        $this->assertSame(0, $this->fetchTable('CsvRoleMappings')->find()->count());

        // Retry using the upload retained by the failed request.
        $this->session(['MemberCsvUpload' => $_SESSION['MemberCsvUpload']]);
        $roleId = '22222222-2222-4222-8222-222222222222';
        $this->post('/members/upload', $request + ['mapping' => [$key => $roleId]]);

        $this->assertResponseOk();
        $this->assertResponseContains('CSV imported successfully.');
        $this->assertSession(null, 'MemberCsvUpload');
        $member = $this->fetchTable('Members')->find()->where(['membership_number' => 9090])->firstOrFail();
        $this->assertTrue($this->fetchTable('Appointments')->exists(['member_id' => $member->id, 'role_id' => $roleId]));
        $this->assertSame([$key => $roleId], (new MemberCsvImporter())->savedMappings($pending['rows']));
    }

    public function testNewUploadReplacesPendingRowsAndInvalidatesOldToken(): void
    {
        $this->session(['MemberCsvUpload' => ['token' => 'old-token', 'rows' => []]]);
        $upload = new UploadedFile(
            fopen(CONFIG . 'Examples/new-roles-example.csv', 'r'),
            null,
            UPLOAD_ERR_OK,
            'members.csv',
            'text/csv',
        );
        $this->enableCsrfToken();
        $this->post('/members/upload', ['csv' => $upload]);

        $this->assertResponseOk();
        $pending = $_SESSION['MemberCsvUpload'];
        $this->assertNotSame('old-token', $pending['token']);
        $this->assertNotEmpty($pending['rows']);
        $this->assertSame(2, $this->fetchTable('Members')->find()->count());

        $this->session(['MemberCsvUpload' => $pending]);
        $this->post('/members/upload', ['step' => 'import', 'token' => 'old-token']);
        $this->assertResponseContains('This upload has expired');
        $this->assertSession($pending, 'MemberCsvUpload');
    }

    public function testUploadMissingFile(): void
    {
        $this->enableCsrfToken();
        $this->post('/members/upload', []);
        $this->assertResponseOk();
        $this->assertResponseContains('Nothing was imported.');
    }

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
