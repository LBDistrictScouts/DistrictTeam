<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Enum\ContactMethodType;
use App\Model\Table\MemberContactMethodsTable;
use Cake\Datasource\EntityInterface;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\ORM\Table;
use Cake\Validation\Validation;
use DateTimeImmutable;
use InvalidArgumentException;
use Psr\Http\Message\UploadedFileInterface;

/** Imports membership exports and persists their mapping choices. */
class MemberCsvImporter
{
    use LocatorAwareTrait;

    /**
     * @param \Psr\Http\Message\UploadedFileInterface $upload CSV upload.
     * @return array<string, mixed>
     */
    public function read(UploadedFileInterface $upload): array
    {
        if ($upload->getError() !== UPLOAD_ERR_OK) {
            throw new InvalidArgumentException('Please select a CSV file that uploaded successfully.');
        }
        $contents = $upload->getStream()->read(10 * 1024 * 1024 + 1);
        if (strlen($contents) > 10 * 1024 * 1024) {
            throw new InvalidArgumentException('The CSV must be no larger than 10 MB.');
        }
        if (!mb_check_encoding($contents, 'UTF-8') || str_contains($contents, "\0")) {
            throw new InvalidArgumentException('Please upload a UTF-8 CSV file.');
        }
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, preg_replace('/^\xEF\xBB\xBF/', '', $contents));
        rewind($stream);
        try {
            $headers = fgetcsv($stream, escape: '');
            $required = ['Membership number', 'First name', 'Last name', 'Start date'];
            $headers = $headers === false ? [] : array_map('trim', $headers);
            if (count(array_unique($headers)) !== count($headers) || in_array('', $headers, true)) {
                throw new InvalidArgumentException('The CSV must have unique, non-empty column headers.');
            }
            $missing = array_diff($required, $headers);
            if ($missing) {
                throw new InvalidArgumentException('Missing required column headers: ' . implode(', ', $missing) . '.');
            }
            $rows = [];
            $line = 1;
            while (($values = fgetcsv($stream, escape: '')) !== false) {
                $line++;
                if ($values === [null]) {
                    continue;
                }
                if (count($values) !== count($headers)) {
                    throw new InvalidArgumentException("Row {$line}: column count does not match the header.");
                }
                $rows[$line] = array_combine($headers, array_map('trim', $values));
            }
        } finally {
            fclose($stream);
        }
        if (!$rows) {
            throw new InvalidArgumentException('The CSV contains no member rows.');
        }

        return $rows;
    }

    /**
     * Group source roles independently of the application's role names.
     *
     * @param array<int, array<string, string>> $rows CSV rows.
     * @return array<string, array<string, mixed>>
     */
    public function sources(array $rows): array
    {
        $sources = [];
        foreach ($rows as $row) {
            $key = $this->sourceKey($row);
            $sources[$key] ??= ['unit' => $row['Unit name'] ?? '', 'parent' => $row['Parent Team'] ?? '',
                'team' => $row['Team'] ?? '', 'role' => $row['Role'] ?? '',
                'type' => $row['Roletype'] ?? '', 'count' => 0];
            $sources[$key]['count']++;
        }

        return $sources;
    }

    /**
     * Load remembered choices for the source combinations in an upload.
     *
     * @param array<int, array<string, string>> $rows CSV rows.
     * @return array<string, string>
     */
    public function savedMappings(array $rows): array
    {
        $keys = array_keys($this->sources($rows));
        if (!$keys) {
            return [];
        }
        $mapping = [];
        foreach ($this->fetchTable('CsvRoleMappings')->find()->where(['source_key IN' => $keys]) as $saved) {
            $mapping[$saved->source_key] = $saved->role_id ?? 'skip';
        }

        return $mapping;
    }

    /**
     * Group CSV rows by their unit and parent unit.
     *
     * @param array<int, array<string, string>> $rows CSV rows.
     * @return array<string, array{unit: string, parent: string}>
     */
    public function unitSources(array $rows): array
    {
        $sources = [];
        foreach ($rows as $row) {
            $key = $this->unitSourceKey($row);
            $sources[$key] ??= [
                'unit' => $row['Unit name'] ?? '',
                'parent' => $row['Parent Team'] ?? '',
            ];
        }

        return $sources;
    }

    /**
     * Load remembered Group and Section choices for the unit sources in an upload.
     *
     * @param array<int, array<string, string>> $rows CSV rows.
     * @return array<string, array{group_id: string, section_id: string}>
     */
    public function savedUnitMappings(array $rows): array
    {
        $keys = array_keys($this->unitSources($rows));
        if (!$keys) {
            return [];
        }
        $mapping = [];
        foreach ($this->fetchTable('CsvUnitMappings')->find()->where(['source_key IN' => $keys]) as $saved) {
            $mapping[$saved->source_key] = [
                'group_id' => $saved->group_id ?? '',
                'section_id' => $saved->section_id ?? '',
            ];
        }

        return $mapping;
    }

    /**
     * Build display-ready results for every role source included in an import.
     *
     * @param array<int, array<string, string>> $rows Parsed CSV rows.
     * @param array<string, string> $mapping Source keys to existing role IDs or "skip".
     * @return array<int, array{unit: string, parent: string, team: string, role: string, type: string, count: int, status: string, detail: string}>
     */
    public function roleImportResults(array $rows, array $mapping): array
    {
        $results = [];
        foreach ($this->sources($rows) as $key => $source) {
            $roleId = $mapping[$key] ?? '';
            $results[] = $source + [
                'status' => match ($roleId) {
                    '' => 'unmapped',
                    'skip' => 'skipped',
                    default => 'successful',
                },
                'detail' => match ($roleId) {
                    'skip' => 'Appointment skipped by mapping choice.',
                    '' => 'Members and contacts imported without an appointment.',
                    default => 'Appointment rows imported successfully.',
                },
            ];
        }

        return $results;
    }

    /**
     * Return destination roles that match each source unit's saved group and section mapping.
     *
     * A unit mapped to a section can use that section's teams and group-level teams. A group-only
     * unit mapping can use every team in its group.
     *
     * @param array<int, array<string, string>> $rows Parsed CSV rows.
     * @return array<string, array<string, string>> Source keys and their available role options.
     */
    public function roleOptionsForSources(array $rows): array
    {
        $unitMappings = $this->savedUnitMappings($rows);
        $roles = $this->fetchTable('Roles')->find()->contain(['Teams'])
            ->orderBy(['Teams.team_name' => 'ASC', 'Roles.name' => 'ASC']);
        $options = [];
        foreach ($this->sources($rows) as $sourceKey => $source) {
            $unitMapping = $unitMappings[$this->unitSourceKey([
                'Unit name' => $source['unit'],
                'Parent Team' => $source['parent'],
            ])] ?? null;
            if (!$unitMapping || $unitMapping['group_id'] === '') {
                $options[$sourceKey] = [];

                continue;
            }
            foreach ($roles as $role) {
                $team = $role->team;
                if ($team->group_id !== $unitMapping['group_id']) {
                    continue;
                }
                if (
                    $unitMapping['section_id'] !== ''
                    && $team->section_id !== null
                    && $team->section_id !== $unitMapping['section_id']
                ) {
                    continue;
                }
                $options[$sourceKey][$role->id] = $team->team_name . ' / ' . $role->name;
            }
            $options[$sourceKey] ??= [];
        }

        return $options;
    }

    /**
     * @param array<string, string> $row CSV row.
     * @return string
     */
    private function sourceKey(array $row): string
    {
        return hash('sha256', json_encode([
            $row['Unit name'] ?? '', $row['Parent Team'] ?? '', $row['Team'] ?? '',
            $row['Role'] ?? '', $row['Roletype'] ?? '',
        ]));
    }

    /**
     * @param array<string, string> $row CSV row.
     * @return string
     */
    private function unitSourceKey(array $row): string
    {
        return hash('sha256', json_encode([$row['Unit name'] ?? '', $row['Parent Team'] ?? '']));
    }

    /**
     * Import only explicitly mapped appointments. The value "skip" imports member/contact details only.
     *
     * @param array<int, array<string, string>> $rows Parsed CSV rows.
     * @param array<string, string> $mapping Source keys to existing role IDs or "skip".
     * @return array<string, mixed>
     */
    public function import(array $rows, array $mapping): array
    {
        foreach ($this->sources($rows) as $key => $source) {
            $roleId = $mapping[$key] ?? '';
            if ($roleId === '') {
                continue;
            }
            if (
                !is_string($roleId) || ($roleId !== 'skip'
                && (!Validation::uuid($roleId) || !$this->fetchTable('Roles')->exists(['id' => $roleId])))
            ) {
                throw new InvalidArgumentException('Choose an existing team / role, Skip, or leave the role unmapped.');
            }
        }

        $transaction = function () use ($rows, $mapping): array {
            $result = ['members' => 0, 'contacts' => 0, 'appointments' => 0, 'warnings' => []];
            $newMembers = [];
            foreach ($rows as $line => $row) {
                try {
                    $preferredName = trim($row['Preferred name'] ?? '');
                    if ($preferredName !== '') {
                        $row['First name'] = $preferredName;
                    }
                    $start = $this->date($row['Start date']);
                    $end = empty($row['End date']) ? null : $this->date($row['End date']);
                    if ($end !== null && $end < $start) {
                        throw new InvalidArgumentException('End date must not precede Start date.');
                    }
                    if (!preg_match('/^\d+$/D', $row['Membership number'])) {
                        throw new InvalidArgumentException('Membership number must contain digits only.');
                    }
                    $number = ltrim($row['Membership number'], '0') ?: '0';
                    if (strlen($number) > 10 || (int)$number > 2147483647) {
                        throw new InvalidArgumentException('Membership number is too large.');
                    }
                    $members = $this->fetchTable('Members');
                    $member = $members->find()->where(['membership_number' => $number])->first();
                    if (!$member) {
                        $member = $this->save($members, [
                            'membership_number' => $number,
                            'first_name' => $row['First name'],
                            'last_name' => $row['Last name'],
                            'join_date' => $start,
                        ]);
                        $newMembers[$number] = true;
                        $result['members']++;
                    } else {
                        $changes = [];
                        if ($member->first_name !== $row['First name'] || $member->last_name !== $row['Last name']) {
                            $changes['first_name'] = $row['First name'];
                            $changes['last_name'] = $row['Last name'];
                        }
                        if (isset($newMembers[$number]) && $start < $member->join_date->format('Y-m-d')) {
                            $changes['join_date'] = $start;
                        }
                        if ($changes) {
                            $this->save($members, $changes, $member);
                        }
                    }
                    $contactId = null;
                    foreach (
                        [
                        ['Communication email', ContactMethodType::Email],
                        ['Contact number', ContactMethodType::PhoneNumber],
                        ] as [$column, $type]
                    ) {
                        $value = trim($row[$column] ?? '');
                        if ($value === '') {
                            continue;
                        }
                        if ($type === ContactMethodType::Email && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            throw new InvalidArgumentException('Communication email is invalid.');
                        }
                        if (
                            in_array($type, [
                            ContactMethodType::Email,
                            ContactMethodType::EmailAlias,
                            ContactMethodType::EmailGroup,
                            ], true)
                        ) {
                            $value = strtolower($value);
                        }
                        if ($type === ContactMethodType::PhoneNumber) {
                            $value = MemberContactMethodsTable::normalizePhoneNumber($value);
                            if ($value === null) {
                                $result['warnings'][] = "Row {$line}: contact number skipped "
                                    . '(invalid UK phone number).';
                                continue;
                            }
                        }
                        $contacts = $this->fetchTable('MemberContactMethods');
                        $contact = $contacts->find()
                            ->where(['member_id' => $member->id, 'contact_method' => $value])
                            ->first();
                        if (!$contact) {
                            $contact = $this->save($contacts, [
                                'member_id' => $member->id,
                                'contact_method' => $value,
                                'contact_method_type' => $type->value,
                            ]);
                            $result['contacts']++;
                        }
                        $contactId ??= $contact->id;
                    }
                    $roleId = $mapping[$this->sourceKey($row)] ?? '';
                    if ($roleId === 'skip' || $roleId === '') {
                        continue;
                    }
                    $appointments = $this->fetchTable('Appointments');
                    $key = ['member_id' => $member->id, 'role_id' => $roleId, 'effective_start_date' => $start];
                    $appointment = $appointments->find()->where($key)->first();
                    if (!$contactId) {
                        $contactId = $appointment?->member_contact_method_id;
                        $contactId ??= $this->fetchTable('MemberContactMethods')->find()
                            ->where([
                                'member_id' => $member->id,
                                'contact_method_type IN' => [
                                    ContactMethodType::Email->value,
                                    ContactMethodType::PhoneNumber->value,
                                ],
                            ])
                            ->orderBy(['contact_method_type' => 'ASC', 'id' => 'ASC'])
                            ->first()?->id;
                    }
                    if (!$contactId) {
                        $result['warnings'][] = "Row {$line}: appointment skipped (no usable contact method).";
                        continue;
                    }
                    if (!$appointment) {
                        $result['appointments']++;
                    }
                    $data = $key + ['member_contact_method_id' => $contactId];
                    if (!$appointment || ($appointment->get('effective_end_date') === null && $end !== null)) {
                        $data['effective_end_date'] = $end;
                    }
                    $this->save($appointments, $data, $appointment);
                } catch (InvalidArgumentException $exception) {
                    throw new InvalidArgumentException("Row {$line}: " . $exception->getMessage(), 0, $exception);
                }
            }

            return $result;
        };

        $result = $this->fetchTable('Members')->getConnection()->transactional($transaction);
        $this->saveRoleMappings($rows, $mapping);

        return $result;
    }

    /**
     * Save explicit role choices independently of a member import.
     *
     * @param array<int, array<string, string>> $rows Parsed CSV rows.
     * @param array<string, string> $mapping Source keys to existing role IDs or "skip".
     * @return void
     */
    public function saveRoleMappings(array $rows, array $mapping): void
    {
        $sources = $this->sources($rows);
        foreach ($sources as $key => $source) {
            $roleId = $mapping[$key] ?? '';
            if ($roleId === '') {
                continue;
            }
            if (
                !is_string($roleId) || ($roleId !== 'skip'
                && (!Validation::uuid($roleId) || !$this->fetchTable('Roles')->exists(['id' => $roleId])))
            ) {
                throw new InvalidArgumentException('Choose an existing team / role, Skip, or leave the role unmapped.');
            }
        }

        $transaction = function () use ($sources, $mapping): void {
            $mappings = $this->fetchTable('CsvRoleMappings');
            foreach ($sources as $key => $source) {
                $choice = $mapping[$key] ?? '';
                if ($choice === '') {
                    continue;
                }
                $saved = $mappings->find()->where(['source_key' => $key])->first();
                $this->save($mappings, [
                    'source_key' => $key,
                    'source_unit' => $source['unit'],
                    'source_parent' => $source['parent'],
                    'source_team' => $source['team'],
                    'source_role' => $source['role'],
                    'source_type' => $source['type'],
                    'role_id' => $choice === 'skip' ? null : $choice,
                ], $saved);
            }
        };

        $this->fetchTable('CsvRoleMappings')->getConnection()->transactional($transaction);
    }

    /**
     * Save unit mappings in their own transaction, separate from member and appointment imports.
     *
     * @param array<int, array<string, string>> $rows Parsed CSV rows.
     * @param array<string, array{group_id?: string, section_id?: string}> $unitMapping Unit mapping choices.
     * @return void
     */
    public function saveUnitMappings(array $rows, array $unitMapping): void
    {
        $sources = $this->unitSources($rows);
        foreach ($unitMapping as $key => $destination) {
            if (!isset($this->unitSources($rows)[$key]) || !is_array($destination)) {
                throw new InvalidArgumentException('Invalid unit mapping.');
            }
            $groupId = $destination['group_id'] ?? '';
            $sectionId = $destination['section_id'] ?? '';
            if (!is_string($groupId) || !is_string($sectionId)) {
                throw new InvalidArgumentException('Invalid unit mapping.');
            }
            if ($groupId !== '' && !Validation::uuid($groupId)) {
                throw new InvalidArgumentException('Choose an existing group for the unit mapping.');
            }
            if ($sectionId !== '' && !Validation::uuid($sectionId)) {
                throw new InvalidArgumentException('Choose an existing section for the unit mapping.');
            }
            if ($sectionId !== '') {
                $section = $this->fetchTable('Sections')->find()
                    ->select(['group_id'])->where(['id' => $sectionId])->first();
                if (!$section || $groupId === '' || $section->group_id !== $groupId) {
                    throw new InvalidArgumentException('The selected section must belong to the selected group.');
                }
            } elseif ($groupId !== '' && !$this->fetchTable('Groups')->exists(['id' => $groupId])) {
                throw new InvalidArgumentException('Choose an existing group for the unit mapping.');
            }
        }
        $transaction = function () use ($unitMapping, $sources): void {
            $unitMappings = $this->fetchTable('CsvUnitMappings');
            foreach ($unitMapping as $key => $destination) {
                $groupId = $destination['group_id'] ?? '';
                $sectionId = $destination['section_id'] ?? '';
                if ($groupId === '' && $sectionId === '') {
                    continue;
                }
                $source = $sources[$key];
                $saved = $unitMappings->find()->where(['source_key' => $key])->first();
                $this->save($unitMappings, [
                    'source_key' => $key,
                    'source_unit' => $source['unit'],
                    'source_parent_unit' => $source['parent'],
                    'group_id' => $groupId ?: null,
                    'section_id' => $sectionId ?: null,
                ], $saved);
            }
        };

        $this->fetchTable('CsvUnitMappings')->getConnection()->transactional($transaction);
    }

    /**
     * @param string $value Export date.
     * @return string
     */
    private function date(string $value): string
    {
        $date = DateTimeImmutable::createFromFormat('!d M Y', $value);
        if (!$date || $date->format('d M Y') !== $value) {
            throw new InvalidArgumentException('Dates must use the format 02 Dec 2024.');
        }

        return $date->format('Y-m-d');
    }

    /**
     * @param \Cake\ORM\Table $table Destination.
     * @param array<string, mixed> $data Values.
     * @param \Cake\Datasource\EntityInterface|null $entity Existing entity.
     * @return \Cake\Datasource\EntityInterface
     */
    private function save(Table $table, array $data, ?EntityInterface $entity = null): EntityInterface
    {
        $entity = $entity === null ? $table->newEntity($data)
            : ($data ? $table->patchEntity($entity, $data) : $entity);
        if ($entity->hasErrors() || !$table->save($entity)) {
            throw new InvalidArgumentException($table->getAlias() . ': ' . json_encode($entity->getErrors()));
        }

        return $entity;
    }
}
