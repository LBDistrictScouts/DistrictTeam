<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\MemberCsvImporter;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;
use Laminas\Diactoros\UploadedFile;
use PHPUnit\Framework\Attributes\DataProvider;

class MemberCsvImporterTest extends TestCase
{
    protected array $fixtures = [
        'app.Groups', 'app.Sections', 'app.Teams', 'app.Roles', 'app.Members', 'app.MemberContactMethods', 'app.Appointments', 'app.CsvRoleMappings', 'app.CsvUnitMappings',
    ];

    public function testExampleAndRepeatUpload(): void
    {
        $result = $this->importCsv(file_get_contents(CONFIG . 'Examples/directory-example.csv'));
        $this->assertSame(1, $result['members']);
        $this->assertSame(2, $result['contacts']);
        $this->assertSame(6, $result['appointments']);
        $this->assertCount(0, $result['warnings']);
        $member = $this->fetchTable('Members')->find()->where(['membership_number' => 475931])->firstOrFail();
        $this->assertSame('2024-11-07', $member->join_date->format('Y-m-d'));
        $this->assertTrue($this->fetchTable('MemberContactMethods')->exists([
            'member_id' => $member->id, 'contact_method' => '+44 7800 000000',
        ]));
        $result = $this->importCsv(file_get_contents(CONFIG . 'Examples/directory-example.csv'));
        $this->assertSame(0, $result['members']);
        $this->assertSame(0, $result['contacts']);
        $this->assertSame(0, $result['appointments']);
        $this->assertSame(6, $this->fetchTable('Appointments')->find()->where(['member_id' => $member->id])->count());
    }

    public function testInvalidLaterRowRollsBackEverything(): void
    {
        $tables = ['Members', 'MemberContactMethods', 'Appointments', 'Teams', 'Roles', 'CsvRoleMappings'];
        $before = [];
        foreach ($tables as $table) {
            $before[$table] = $this->fetchTable($table)->find()->count();
        }
        $csv = str_replace('30 Nov 2024', '31 Nov 2024', file_get_contents(CONFIG . 'Examples/directory-example.csv'));
        try {
            $this->importCsv($csv);
            $this->fail('Invalid date should fail.');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString('Row 3:', $exception->getMessage());
        }
        foreach ($tables as $table) {
            $this->assertSame($before[$table], $this->fetchTable($table)->find()->count());
        }
    }

    public function testRepeatUploadUpdatesEndDate(): void
    {
        $csv = file_get_contents(CONFIG . 'Examples/directory-example.csv');
        $this->importCsv($csv);
        $csv = str_replace('Full,Hertfordshire,', 'Full,Hertfordshire,03 Dec 2029', $csv);
        // Only change the first appointment, leaving the other CSV rows intact.
        $rows = preg_split('/\r?\n/', $csv);
        $original = preg_split('/\r?\n/', file_get_contents(CONFIG . 'Examples/directory-example.csv'));
        $original[1] = $rows[1];
        $result = $this->importCsv(implode("\n", $original));
        $this->assertSame(0, $result['appointments']);
        $appointment = $this->fetchTable('Appointments')->find()->where([
            'effective_start_date' => '2024-12-02',
        ])->firstOrFail();
        $this->assertSame('2029-12-03', $appointment->effective_end_date->format('Y-m-d'));
    }

    public function testMissingHeaders(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('column headers');
        (new MemberCsvImporter())->read($this->upload("First name,Last name\nTest,Member\n"));
    }

    public function testInvalidRoleRejected(): void
    {
        $importer = new MemberCsvImporter();
        $rows = $importer->read($this->upload(file_get_contents(CONFIG . 'Examples/directory-example.csv')));
        foreach ([array_fill_keys(array_keys($importer->sources($rows)), 'invalid-role')] as $mapping) {
            try {
                $importer->import($rows, $mapping);
                $this->fail('Invalid mapping should fail.');
            } catch (InvalidArgumentException $exception) {
                $this->assertStringContainsString('Choose an existing', $exception->getMessage());
            }
        }
        $this->assertSame(2, $this->fetchTable('Members')->find()->count());
    }

    public function testMappingsPersistUpdateAndDisappearWithDeletedRole(): void
    {
        $csv = file_get_contents(CONFIG . 'Examples/directory-example.csv');
        $this->importCsv($csv);
        $importer = new MemberCsvImporter();
        $rows = $importer->read($this->upload($csv));
        $saved = $importer->savedMappings($rows);
        $this->assertCount(count($importer->sources($rows)), $saved);
        $this->assertContains('skip', $saved);
        $this->assertContains('22222222-2222-4222-8222-222222222221', $saved);

        $updated = array_fill_keys(array_keys($saved), '22222222-2222-4222-8222-222222222222');
        $importer->import($rows, $updated);
        $this->assertEquals($updated, (new MemberCsvImporter())->savedMappings($rows));
        $this->assertSame(count($saved), $this->fetchTable('CsvRoleMappings')->find()->count());

        $invalidRows = $rows;
        $invalidRows[3]['Start date'] = '31 Nov 2024';
        try {
            $importer->import($invalidRows, $saved);
            $this->fail('Invalid import must fail.');
        } catch (InvalidArgumentException) {
            $this->assertEquals($updated, $importer->savedMappings($rows));
        }

        $importer->import($rows, array_fill_keys(array_keys($updated), 'skip'));
        $this->assertSame(array_fill_keys(array_keys($updated), 'skip'), $importer->savedMappings($rows));
    }

    public function testUnitMappingsPersistForEachUnitAndParentUnit(): void
    {
        $importer = new MemberCsvImporter();
        $rows = $importer->read($this->upload(file_get_contents(CONFIG . 'Examples/directory-example.csv')));
        $key = array_key_first($importer->unitSources($rows));
        $unitMapping = [$key => [
            'group_id' => 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb',
            'section_id' => 'cccccccc-cccc-4ccc-8ccc-cccccccccccc',
        ]];

        $importer->saveUnitMappings($rows, $unitMapping);

        $this->assertSame($unitMapping, $importer->savedUnitMappings($rows));
        $saved = $this->fetchTable('CsvUnitMappings')->get($key);
        $this->assertSame('Letchworth And Baldock', $saved->source_unit);
        $this->assertSame('Programme Team', $saved->source_parent_unit);
    }

    public function testNonMemberAndDisclosureRolesDoNotCreateDefaultMappings(): void
    {
        $importer = new MemberCsvImporter();
        $rows = [
            2 => ['Unit name' => 'Unit A', 'Parent Team' => '', 'Team' => 'Support',
                'Role' => 'Non Member - Needs Disclosure', 'Roletype' => 'Volunteer'],
            3 => ['Unit name' => 'Unit A', 'Parent Team' => '', 'Team' => 'Support',
                'Role' => 'Disclosure checker', 'Roletype' => 'Volunteer'],
            4 => ['Unit name' => 'Unit A', 'Parent Team' => '', 'Team' => 'Support',
                'Role' => 'Team member', 'Roletype' => 'Volunteer'],
        ];

        $saved = $importer->savedMappings($rows);
        $this->assertSame([], $saved);
    }

    public function testUnitMappingRejectsSectionFromAnotherGroup(): void
    {
        $importer = new MemberCsvImporter();
        $rows = $importer->read($this->upload(file_get_contents(CONFIG . 'Examples/directory-example.csv')));
        $key = array_key_first($importer->unitSources($rows));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('section must belong to the selected group');
        $importer->saveUnitMappings($rows, [$key => [
            'group_id' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',
            'section_id' => 'cccccccc-cccc-4ccc-8ccc-cccccccccccc',
        ]]);
    }

    public function testRoleOptionsAreLimitedToTheMappedUnitGroup(): void
    {
        $importer = new MemberCsvImporter();
        $rows = $importer->read($this->upload(file_get_contents(CONFIG . 'Examples/directory-example.csv')));
        $unitKey = array_key_first($importer->unitSources($rows));

        $importer->saveUnitMappings($rows, [$unitKey => [
            'group_id' => 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb',
            'section_id' => '',
        ]]);
        $this->assertSame([], current($importer->roleOptionsForSources($rows)));

        $importer->saveUnitMappings($rows, [$unitKey => [
            'group_id' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',
            'section_id' => '',
        ]]);
        $this->assertSame([
            '22222222-2222-4222-8222-222222222221' => 'Digital Team / Digital Lead',
            '22222222-2222-4222-8222-222222222222' => 'District Team / Vacant Role',
        ], current($importer->roleOptionsForSources($rows)));
    }

    public function testBothExamplesImportEveryAppointmentWithDates(): void
    {
        $importer = new MemberCsvImporter();
        foreach (['directory-example.csv', 'new-roles-example.csv'] as $filename) {
            $rows = $importer->read($this->upload(file_get_contents(CONFIG . 'Examples/' . $filename)));
            $mapping = [];
            $roles = $this->fetchTable('Roles');
            foreach ($importer->sources($rows) as $key => $source) {
                $role = $roles->newEntity([
                    'team_id' => '11111111-1111-4111-8111-111111111111',
                    'name' => $filename . '-' . $key,
                ]);
                $roles->saveOrFail($role);
                $mapping[$key] = $role->id;
            }
            $result = $importer->import($rows, $mapping);
            $this->assertSame(count($rows), $result['appointments'], $filename);
            foreach ($rows as $row) {
                $key = array_key_first($importer->sources([$row]));
                $appointment = $this->fetchTable('Appointments')->find()->where([
                    'role_id' => $mapping[$key],
                ])->firstOrFail();
                $this->assertSame($row['Start date'], $appointment->effective_start_date->format('d M Y'));
                $this->assertSame(
                    $row['End date'] ?: null,
                    $appointment->effective_end_date?->format('d M Y'),
                );
            }
        }
        $this->assertSame(3, $this->fetchTable('Members')->find()->count());
    }

    public function testOptionalColumnsAndOmittedVersusBlankEndDate(): void
    {
        $importer = new MemberCsvImporter();
        // Deliberately reordered, without any source-role columns or phone column.
        $csv = "End date,Last name,Communication email,Start date,Membership number,First name\n"
            . "03 Dec 2029,Example,person@example.com,01 Aug 2026,9090,Test\n";
        $rows = $importer->read($this->upload($csv));
        $key = array_key_first($importer->sources($rows));
        $mapping = [$key => '22222222-2222-4222-8222-222222222222'];
        $importer->import($rows, $mapping);

        // No contacts or end date in this export: reuse contact and retain end date.
        $csv = "Last name,Start date,Membership number,First name\nExample,01 Aug 2026,9090,Test\n";
        $rows = $importer->read($this->upload($csv));
        $this->assertSame($mapping, $importer->savedMappings($rows));
        $result = $importer->import($rows, $mapping);
        $this->assertSame(0, $result['appointments']);
        $appointment = $this->fetchTable('Appointments')->find()->where([
            'role_id' => $mapping[$key],
        ])->firstOrFail();
        $this->assertSame('2026-08-01', $appointment->effective_start_date->format('Y-m-d'));
        $this->assertSame('2029-12-03', $appointment->effective_end_date->format('Y-m-d'));
        $rows[2]['End date'] = '';
        $importer->import($rows, $mapping);
        $this->assertSame('2029-12-03', $this->fetchTable('Appointments')->get($appointment->id)->effective_end_date->format('Y-m-d'));
    }

    /**
     * REGRESSION GUARD: Do not remove or weaken this test. CSV data must never clear or replace an
     * existing appointment end date, but an imported end date must fill a blank existing appointment.
     */
    public function testCsvEndDateOnlyFillsBlankExistingAppointment(): void
    {
        $importer = new MemberCsvImporter();
        $csv = "First name,Last name,Membership number,Start date,End date,Communication email\n"
            . "Ada,Lovelace,1001,01 Jan 2020,31 Dec 2026,ada@example.com\n";
        $rows = $importer->read($this->upload($csv));
        $mapping = [array_key_first($importer->sources($rows)) => '22222222-2222-4222-8222-222222222221'];

        // The fixture appointment begins open-ended; the CSV may fill that blank end date.
        $importer->import($rows, $mapping);
        $appointment = $this->fetchTable('Appointments')->get('55555555-5555-4555-8555-555555555551');
        $this->assertSame('2026-12-31', $appointment->effective_end_date->format('Y-m-d'));

        // A date subsequently set on the record takes priority over a blank CSV date.
        $appointment->effective_end_date = '2027-12-31';
        $this->fetchTable('Appointments')->saveOrFail($appointment);
        $rows[2]['End date'] = '';
        $importer->import($rows, $mapping);

        $appointment = $this->fetchTable('Appointments')->get($appointment->id);
        $this->assertSame('2027-12-31', $appointment->effective_end_date->format('Y-m-d'));
    }

    public function testPhoneOnlyExportWithoutEndDate(): void
    {
        $importer = new MemberCsvImporter();
        $rows = $importer->read($this->upload(
            "First name,Last name,Membership number,Start date,Contact number\n"
            . "Phone,Only,9091,01 Aug 2026,+447800000000\n",
        ));
        $mapping = [array_key_first($importer->sources($rows)) => '22222222-2222-4222-8222-222222222222'];
        $importer->import($rows, $mapping);
        $appointment = $this->fetchTable('Appointments')->find()->where([
            'role_id' => current($mapping),
        ])->firstOrFail();
        $this->assertNull($appointment->effective_end_date);
        $this->assertSame('2026-08-01', $appointment->effective_start_date->format('Y-m-d'));
        $this->assertSame('+44 7800 000000', $this->fetchTable('MemberContactMethods')->get($appointment->member_contact_method_id)->contact_method);
    }

    public function testCsvEmailIsLowercasedBeforeDuplicateMatching(): void
    {
        $importer = new MemberCsvImporter();
        $rows = $importer->read($this->upload(
            "First name,Last name,Membership number,Start date,Communication email\n"
            . "Upper,Case,9092,01 Aug 2026,TEAM.LEAD@EXAMPLE.ORG\n",
        ));
        $mapping = [array_key_first($importer->sources($rows)) => '22222222-2222-4222-8222-222222222222'];

        $importer->import($rows, $mapping);

        $this->assertTrue($this->fetchTable('MemberContactMethods')->exists([
            'contact_method' => 'team.lead@example.org',
        ]));
    }

    public function testMissingStartDateGivesActionableError(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required column headers: Start date.');
        (new MemberCsvImporter())->read($this->upload("First name,Last name,Membership number\nTest,Example,9090\n"));
    }

    public function testEndBeforeStartRollsBack(): void
    {
        $importer = new MemberCsvImporter();
        $rows = $importer->read($this->upload(file_get_contents(CONFIG . 'Examples/new-roles-example.csv')));
        $rows[2]['End date'] = '31 Jul 2026';
        $mapping = [array_key_first($importer->sources($rows)) => '22222222-2222-4222-8222-222222222222'];
        try {
            $importer->import($rows, $mapping);
            $this->fail('End before start must fail.');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString('End date must not precede Start date', $exception->getMessage());
        }
        $this->assertSame(2, $this->fetchTable('Members')->find()->count());
        $this->assertSame(0, $this->fetchTable('CsvRoleMappings')->find()->count());
    }

    public function testNameChangesUpdateSameMemberAndPreserveRelatedRecords(): void
    {
        $importer = new MemberCsvImporter();
        $memberId = '33333333-3333-4333-8333-333333333331';
        $rows = $importer->read($this->upload(
            "First name,Last name,Membership number,Start date\nAugusta Ada,Byron,00001001,01 Jan 2020\n",
        ));
        $mapping = [array_key_first($importer->sources($rows)) => '22222222-2222-4222-8222-222222222221'];
        $result = $importer->import($rows, $mapping);
        $member = $this->fetchTable('Members')->get($memberId);
        $this->assertSame('Augusta Ada', $member->first_name);
        $this->assertSame('Byron', $member->last_name);
        $this->assertSame('2020-01-01', $member->join_date->format('Y-m-d'));
        $this->assertSame(2, $this->fetchTable('Members')->find()->count());
        $this->assertSame(0, $result['members']);
        $this->assertSame(0, $result['contacts']);
        $this->assertSame(0, $result['appointments']);
        $appointment = $this->fetchTable('Appointments')->get('55555555-5555-4555-8555-555555555551');
        $this->assertSame($memberId, $appointment->member_id);
        $this->assertSame('44444444-4444-4444-8444-444444444441', $appointment->member_contact_method_id);
    }

    public function testFailedImportRollsBackNameUpdate(): void
    {
        $importer = new MemberCsvImporter();
        $rows = $importer->read($this->upload(
            "First name,Last name,Membership number,Start date\n"
            . "Augusta Ada,Byron,1001,01 Jan 2020\nGrace,Hopper,1002,invalid\n",
        ));
        $mapping = [array_key_first($importer->sources($rows)) => 'skip'];
        try {
            $importer->import($rows, $mapping);
            $this->fail('Invalid date must fail.');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString('Row 3:', $exception->getMessage());
        }
        $member = $this->fetchTable('Members')->get('33333333-3333-4333-8333-333333333331');
        $this->assertSame('Ada', $member->first_name);
        $this->assertSame('Lovelace', $member->last_name);
    }

    public function testUnmappedImportPreservesSavedMappings(): void
    {
        $csv = file_get_contents(CONFIG . 'Examples/new-roles-example.csv');
        $importer = new MemberCsvImporter();
        $rows = $importer->read($this->upload($csv));
        $key = array_key_first($importer->sources($rows));
        $mapping = [$key => '22222222-2222-4222-8222-222222222222'];
        $importer->import($rows, $mapping);
        $rows[2]['Membership number'] = '9099';
        $result = $importer->import($rows, [$key => '']);
        $this->assertSame(1, $result['members']);
        $this->assertSame(2, $result['contacts']);
        $this->assertSame(0, $result['appointments']);
        $this->assertSame($mapping, $importer->savedMappings($rows));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function invalidCsvFiles(): array
    {
        $header = "First name,Last name,Membership number,Start date\n";

        return [
            'empty file' => ['', 'Missing required column headers'],
            'headers only' => [$header, 'contains no member rows'],
            'duplicate columns' => ["First name,First name\nTest,Test\n", 'unique, non-empty column headers'],
            'empty column name' => ["First name,\nTest,Test\n", 'unique, non-empty column headers'],
            'short row' => [$header . "Test,Person,9090\n", 'Row 2: column count'],
            'long row' => [$header . "Test,Person,9090,01 Aug 2026,extra\n", 'Row 2: column count'],
            'invalid UTF-8' => [$header . "Test,Pers\xFFon,9090,01 Aug 2026\n", 'UTF-8 CSV'],
            'NUL byte' => [$header . "Test,Pers\0on,9090,01 Aug 2026\n", 'UTF-8 CSV'],
        ];
    }

    #[DataProvider('invalidCsvFiles')]
    public function testRejectsMalformedCsv(string $csv, string $message): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);
        (new MemberCsvImporter())->read($this->upload($csv));
    }

    public function testRejectsOversizedUpload(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('no larger than 10 MB');
        (new MemberCsvImporter())->read($this->upload(str_repeat('a', 10 * 1024 * 1024 + 1)));
    }

    public function testRejectsFailedUpload(): void
    {
        $upload = new UploadedFile('php://temp', 0, UPLOAD_ERR_PARTIAL, 'members.csv', 'text/csv');
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('uploaded successfully');
        (new MemberCsvImporter())->read($upload);
    }

    public function testReadsBomQuotedValuesAndBlankLines(): void
    {
        $rows = (new MemberCsvImporter())->read($this->upload(
            "\xEF\xBB\xBFFirst name,Last name,Membership number,Start date\r\n"
            . "\r\nTest,\"Person, Jr\",9090,01 Aug 2026\r\n",
        ));
        $this->assertSame([3 => [
            'First name' => 'Test', 'Last name' => 'Person, Jr',
            'Membership number' => '9090', 'Start date' => '01 Aug 2026',
        ]], $rows);
    }

    /**
     * @return array<string, array{string, string, string}>
     */
    public static function invalidMemberRows(): array
    {
        return [
            'non-numeric membership' => ['Membership number', '12abc', 'digits only'],
            'negative membership' => ['Membership number', '-1', 'digits only'],
            'membership overflow' => ['Membership number', '2147483648', 'too large'],
            'invalid email' => ['Communication email', 'not-an-email', 'Communication email is invalid'],
        ];
    }

    #[DataProvider('invalidMemberRows')]
    public function testInvalidMemberRowRollsBackEarlierValidRow(string $column, string $value, string $message): void
    {
        $importer = new MemberCsvImporter();
        $rows = $importer->read($this->upload(
            "First name,Last name,Membership number,Start date,Communication email\n"
            . "Valid,Person,9090,01 Aug 2026,valid@example.com\n"
            . "Invalid,Person,9091,01 Aug 2026,second@example.com\n",
        ));
        $rows[3][$column] = $value;
        $mapping = [array_key_first($importer->sources($rows)) => '22222222-2222-4222-8222-222222222222'];
        $before = [];
        foreach (['Members', 'MemberContactMethods', 'Appointments', 'CsvRoleMappings', 'Roles'] as $table) {
            $order = [$table === 'CsvRoleMappings' ? 'source_key' : 'id' => 'ASC'];
            $before[$table] = $this->fetchTable($table)->find()->orderBy($order)->disableHydration()->toArray();
        }
        try {
            $importer->import($rows, $mapping);
            $this->fail('Invalid row must reject the import.');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString('Row 3:', $exception->getMessage());
            $this->assertStringContainsString($message, $exception->getMessage());
        }
        foreach ($before as $table => $records) {
            $order = [$table === 'CsvRoleMappings' ? 'source_key' : 'id' => 'ASC'];
            $this->assertEquals(
                $records,
                $this->fetchTable($table)->find()->orderBy($order)->disableHydration()->toArray(),
                $table . ' must be unchanged after rollback',
            );
        }
    }

    public function testBlankOrInvalidPhoneNumberDoesNotBlockMemberImport(): void
    {
        $importer = new MemberCsvImporter();
        $rows = $importer->read($this->upload(
            "First name,Last name,Membership number,Start date,Communication email,Contact number\n"
            . "No,Phone,9090,01 Aug 2026,no-phone@example.com,\n"
            . "Invalid,Phone,9091,01 Aug 2026,,+33 1 42 68 53 00\n",
        ));
        $mapping = [array_key_first($importer->sources($rows)) => '22222222-2222-4222-8222-222222222222'];

        $result = $importer->import($rows, $mapping);

        $this->assertSame(2, $result['members']);
        $this->assertSame(1, $result['contacts']);
        $this->assertSame(1, $result['appointments']);
        $this->assertContains('Row 3: contact number skipped (invalid UK phone number).', $result['warnings']);
        $this->assertContains('Row 3: appointment skipped (no usable contact method).', $result['warnings']);
    }

    public function testPreferredNameForNewAndExistingMembersWithFallback(): void
    {
        $importer = new MemberCsvImporter();
        $rows = $importer->read($this->upload(
            "First name,Preferred name,Last name,Membership number,Start date\n"
            . "Robert, Bob ,Example,9090,01 Aug 2026\n",
        ));
        $importer->import($rows, []);
        $members = $this->fetchTable('Members');
        $member = $members->find()->where(['membership_number' => 9090])->firstOrFail();
        $this->assertSame('Bob', $member->first_name);
        $memberId = $member->id;

        $rows[2]['Preferred name'] = 'Rob';
        $result = $importer->import($rows, []);
        $this->assertSame('Rob', $members->get($memberId)->first_name);
        $this->assertSame(0, $result['members']);

        $rows[2]['Preferred name'] = '   ';
        $importer->import($rows, []);
        $this->assertSame('Robert', $members->get($memberId)->first_name);

        unset($rows[2]['Preferred name']);
        $rows[2]['First name'] = 'Roberto';
        $importer->import($rows, []);
        $this->assertSame('Roberto', $members->get($memberId)->first_name);
        $this->assertSame(3, $members->find()->count());
    }

    private function importCsv(string $csv): array
    {
        $importer = new MemberCsvImporter();
        $rows = $importer->read($this->upload($csv));
        $mapping = [];
        foreach ($importer->sources($rows) as $key => $source) {
            $mapping[$key] = $source['role'] === '' ? 'skip' : '22222222-2222-4222-8222-222222222221';
        }
        $result = $importer->import($rows, $mapping);
        $this->assertSame(2, $this->fetchTable('Teams')->find()->count());
        $this->assertSame(2, $this->fetchTable('Roles')->find()->count());

        return $result;
    }

    private function upload(string $contents): UploadedFile
    {
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $contents);
        rewind($stream);

        return new UploadedFile($stream, strlen($contents), UPLOAD_ERR_OK, 'members.csv', 'text/csv');
    }
}
