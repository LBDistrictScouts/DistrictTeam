<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class ImportsControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = [
        'app.Groups', 'app.Teams', 'app.Roles', 'app.Members', 'app.MemberContactMethods',
        'app.Appointments', 'app.ImportFiles', 'app.ImportRecords',
    ];

    public function testViewLoadsOrderedAuditRecordsWithoutAnEagerLoad(): void
    {
        $import = $this->fetchTable('ImportFiles')->saveOrFail(
            $this->fetchTable('ImportFiles')->newEntity([
                'filename' => 'membership-export.csv',
                'imported_at' => '2026-09-25 12:00:00',
                'source_record_count' => 1,
                'record_count' => 1,
                'member_count' => 0,
                'contact_count' => 0,
                'appointment_count' => 0,
                'warning_count' => 0,
            ]),
        );
        $this->get('/imports/view/' . $import->id);

        $this->assertResponseOk();
        $this->assertResponseContains('membership-export.csv');
        $this->assertResponseContains('Row audit');
    }
}
