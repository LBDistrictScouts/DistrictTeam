<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Enum\ContactMethodType;
use Cake\Datasource\EntityInterface;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\ORM\Table;
use Cake\Validation\Validation;
use DateTimeImmutable;
use InvalidArgumentException;
use Psr\Http\Message\UploadedFileInterface;

/** Imports membership exports in a single database transaction. */
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

        return $this->fetchTable('Members')->getConnection()->transactional(function () use ($rows, $mapping): array {
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
                        [['Communication email', ContactMethodType::Email],
                        ['Contact number', ContactMethodType::PhoneNumber]] as [$column, $type]
                    ) {
                        $value = $row[$column] ?? '';
                        if ($value === '') {
                            continue;
                        }
                        if ($type === ContactMethodType::Email && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            throw new InvalidArgumentException('Communication email is invalid.');
                        }
                        $contacts = $this->fetchTable('MemberContactMethods');
                        $contact = $contacts->find()->where([
                            'member_id' => $member->id, 'contact_method' => $value,
                        ])->first();
                        if (!$contact) {
                            $contact = $this->save($contacts, [
                                'member_id' => $member->id, 'contact_method' => $value,
                                'contact_method_type' => $type->value,
                            ]);
                            $result['contacts']++;
                        }
                        $contactId ??= $contact->id;
                    }
                    $roleId = $mapping[$this->sourceKey($row)] ?? '';
                    if ($roleId === 'skip' || $roleId === '') {
                        $result['warnings'][] = "Row {$line}: appointment skipped (unmapped or explicitly skipped).";
                        continue;
                    }
                    $appointments = $this->fetchTable('Appointments');
                    $key = ['member_id' => $member->id, 'role_id' => $roleId, 'effective_start_date' => $start];
                    $appointment = $appointments->find()->where($key)->first();
                    // Exports without contact columns can reuse the member's existing contact.
                    if (!$contactId) {
                        $contactId = $appointment?->member_contact_method_id;
                        $contactId ??= $this->fetchTable('MemberContactMethods')->find()->where([
                            'member_id' => $member->id,
                            'contact_method_type IN' => [
                                ContactMethodType::Email->value, ContactMethodType::PhoneNumber->value,
                            ],
                        ])->orderBy(['contact_method_type' => 'ASC', 'id' => 'ASC'])->first()?->id;
                    }
                    if (!$contactId) {
                        throw new InvalidArgumentException(
                            'Include Communication email or Contact number, or use a member with an existing contact.',
                        );
                    }
                    if (!$appointment) {
                        $result['appointments']++;
                    }
                    $data = $key + ['member_contact_method_id' => $contactId, 'active' => true];
                    if (array_key_exists('End date', $row) || !$appointment) {
                        $data['effective_end_date'] = $end;
                    }
                    $this->save($appointments, $data, $appointment);
                } catch (InvalidArgumentException $exception) {
                    throw new InvalidArgumentException("Row {$line}: " . $exception->getMessage(), 0, $exception);
                }
            }

            $mappings = $this->fetchTable('CsvRoleMappings');
            foreach ($this->sources($rows) as $key => $source) {
                if (($mapping[$key] ?? '') === '') {
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
                    'role_id' => $mapping[$key] === 'skip' ? null : $mapping[$key],
                ], $saved);
            }

            return $result;
        });
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
