<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Enum\GroupType;
use App\Model\Table\MemberContactMethodsTable;
use Cake\Core\Configure;
use Cake\Datasource\EntityInterface;
use Cake\Http\Client;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\Validation\Validation;
use RuntimeException;

class DistrictCoreDataService
{
    use LocatorAwareTrait;

    private Client $client;
    private string $baseUrl;
    private string $authorization;

    /**
     * @param array<string, mixed>|null $config Service configuration.
     * @param \Cake\Http\Client|null $client HTTP client override.
     */
    public function __construct(?array $config = null, ?Client $client = null)
    {
        $config ??= (array)Configure::read('DistrictCoreData');
        $endpoint = trim((string)($config['url'] ?? ''));
        $username = (string)($config['username'] ?? '');
        $password = (string)($config['password'] ?? '');

        if ($endpoint === '' || $username === '' || $password === '') {
            throw new RuntimeException('DistrictCoreData URL and Basic Auth credentials must be configured.');
        }

        $path = (string)(parse_url($endpoint, PHP_URL_PATH) ?? '');
        $this->baseUrl = $path === '' || $path === '/'
            ? rtrim($endpoint, '/') . '/'
            : (preg_replace('#[^/]*$#', '', $endpoint) ?: rtrim($endpoint, '/') . '/');
        $this->authorization = 'Basic ' . base64_encode($username . ':' . $password);
        $this->client = $client ?? new Client();
    }

    /**
     * @return array{groups: list<array<string, mixed>>, sections: list<array<string, mixed>>}
     */
    public function fetch(): array
    {
        return [
            'groups' => $this->fetchDataset('groups.json'),
            'sections' => $this->fetchDataset('sections.json'),
        ];
    }

    /**
     * Import groups and sections atomically using their shared core data UUIDs.
     *
     * @param list<array<string, mixed>> $groupData Groups from core data.
     * @param list<array<string, mixed>> $sectionData Sections from core data.
     * @return array{groups: int, sections: int}
     */
    public function sync(array $groupData, array $sectionData): array
    {
        $this->validateDatasets($groupData, $sectionData);
        $groups = $this->fetchTable('Groups');
        $sections = $this->fetchTable('Sections');
        $emailGroups = $this->fetchTable('EmailGroups');

        return $groups->getConnection()->transactional(function () use (
            $groups,
            $sections,
            $emailGroups,
            $groupData,
            $sectionData,
        ): array {
            foreach ($groupData as $record) {
                $group = $groups->find()->where(['id' => $record['id']])->first() ?? $groups->newEmptyEntity();
                if ($group instanceof EntityInterface && !$group->isNew()) {
                    $this->assertEmailGroupsUseConfiguredDomains(
                        $emailGroups,
                        $record['id'],
                        $group->get('domains'),
                        $record['domains'],
                    );
                }
                $group->set('id', $record['id']);
                $fields = [
                    'group_name' => $record['group_name'],
                    'sort_order' => $record['sort_order'],
                    'type' => $record['type'],
                    'domains' => $record['domains'],
                ];
                if (array_key_exists('group_osm_id', $record)) {
                    $fields['group_osm_id'] = $record['group_osm_id'];
                }
                $groups->patchEntity($group, $fields);
                $groups->saveOrFail($group);
            }
            foreach ($sectionData as $record) {
                $section = $sections->find()->where(['id' => $record['id']])->first() ?? $sections->newEmptyEntity();
                $section->set('id', $record['id']);
                $sections->patchEntity($section, [
                    'group_id' => $record['group_id'],
                    'section_osm_id' => $record['section_id'],
                    'section_name' => $record['section_name'],
                    'section_type' => $record['section_type'],
                    'meeting_start_time' => $record['meeting_start_time'] ?? null,
                    'meeting_end_time' => $record['meeting_end_time'] ?? null,
                    'meeting_day' => $record['meeting_day'] ?? null,
                ]);
                $sections->saveOrFail($section);
            }

            return ['groups' => count($groupData), 'sections' => count($sectionData)];
        });
    }

    /**
     * Refresh contact-method flags using the domains most recently synchronized
     * from DistrictCoreData.
     *
     * @return int Number of contact methods whose stored flag changed.
     */
    public function refreshNonGroupEmailFlags(): int
    {
        $contactMethods = $this->fetchTable('MemberContactMethods');
        if (!$contactMethods instanceof MemberContactMethodsTable) {
            throw new RuntimeException('Member contact methods table is not configured correctly.');
        }

        return $contactMethods->refreshNonGroupEmailFlags();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchDataset(string $filename): array
    {
        $response = $this->client->get($this->baseUrl . $filename, [], [
            'headers' => [
                'Authorization' => $this->authorization,
                'Accept' => 'application/json',
            ],
        ]);

        if (!$response->isOk()) {
            throw new RuntimeException(sprintf(
                'DistrictCoreData %s request failed with status %d.',
                $filename,
                $response->getStatusCode(),
            ));
        }

        $data = $response->getJson();
        if (!is_array($data) || !array_is_list($data)) {
            throw new RuntimeException(sprintf('DistrictCoreData %s did not return a JSON list.', $filename));
        }

        return $data;
    }

    /**
     * @param list<array<string, mixed>> $groups Group dataset.
     * @param list<array<string, mixed>> $sections Section dataset.
     */
    private function validateDatasets(array $groups, array $sections): void
    {
        if ($groups === [] || $sections === []) {
            throw new RuntimeException('DistrictCoreData groups and sections must not be empty.');
        }

        $groupIds = [];
        foreach ($groups as $group) {
            if (
                !isset($group['id'], $group['group_name'], $group['sort_order'], $group['type'], $group['domains'])
                || !is_string($group['type'])
                || GroupType::tryFrom($group['type']) === null
                || !is_array($group['domains'])
                || !array_is_list($group['domains'])
                || $group['domains'] === []
                || !is_string($group['id'])
                || !Validation::uuid($group['id'])
                || isset($groupIds[$group['id']])
                || !is_string($group['group_name'])
                || trim($group['group_name']) === ''
                || !is_int($group['sort_order'])
            ) {
                throw new RuntimeException('DistrictCoreData group record is invalid.');
            }
            $groupIds[$group['id']] = true;
        }

        $sectionIds = [];
        foreach ($sections as $section) {
            if (
                !isset(
                    $section['id'],
                    $section['group_id'],
                    $section['section_id'],
                    $section['section_name'],
                    $section['section_type'],
                )
                || !is_string($section['id'])
                || !Validation::uuid($section['id'])
                || isset($sectionIds[$section['id']])
                || isset($groupIds[$section['id']])
                || !is_string($section['group_id'])
                || !is_int($section['section_id'])
                || !is_string($section['section_name'])
                || trim($section['section_name']) === ''
                || !is_string($section['section_type'])
                || !isset($groupIds[$section['group_id']])
            ) {
                throw new RuntimeException('DistrictCoreData section record is invalid.');
            }
            $sectionIds[$section['id']] = true;
        }
    }

    /**
     * Reject a domain synchronization that would invalidate a configured email group address.
     *
     * @param \Cake\ORM\Table $emailGroups Email groups table.
     * @param string $groupId Group UUID.
     * @param mixed $currentDomains Domains currently stored for the group.
     * @param list<string> $newDomains Domains from core data.
     * @return void
     */
    private function assertEmailGroupsUseConfiguredDomains(
        \Cake\ORM\Table $emailGroups,
        string $groupId,
        mixed $currentDomains,
        array $newDomains,
    ): void {
        $normalize = static function (mixed $domains): array {
            if (!is_array($domains)) {
                return [];
            }
            $normalized = array_map(
                static fn(mixed $domain): string => strtolower((string)$domain),
                $domains,
            );
            sort($normalized);

            return $normalized;
        };
        if ($normalize($currentDomains) === $normalize($newDomains)) {
            return;
        }

        foreach ($emailGroups->find()->select(['email_address'])->where(['group_id' => $groupId]) as $emailGroup) {
            $emailAddress = $emailGroup->get('email_address');
            if (!is_string($emailAddress)) {
                continue;
            }
            $emailAddress = strtolower($emailAddress);
            $isConfigured = false;
            foreach ($newDomains as $domain) {
                if (is_string($domain) && str_ends_with($emailAddress, '@' . strtolower($domain))) {
                    $isConfigured = true;
                    break;
                }
            }
            if (!$isConfigured) {
                throw new RuntimeException(
                    'Group domains cannot be changed while an email group uses a domain being removed.',
                );
            }
        }
    }
}
