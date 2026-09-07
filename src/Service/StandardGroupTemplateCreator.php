<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Enum\GroupType;
use App\Model\Table\RolesTable;
use App\Model\Table\TeamsTable;
use BackedEnum;
use Cake\ORM\Locator\LocatorAwareTrait;
use InvalidArgumentException;

/** Creates the baseline teams and roles used by every Scout Group. */
class StandardGroupTemplateCreator
{
    use LocatorAwareTrait;

    /** @return array{teams: list<array<string, mixed>>, roles: list<array<string, mixed>>} */
    public function plan(bool $reviewOverrides = false): array
    {
        [$groups, $mappedSections, $existingTeams, $existingRoles] = $this->sourceData();
        $teamIds = [];
        foreach ($existingTeams as $team) {
            if ($team['template'] !== null) {
                $teamKey = $this->key((string)$team['group_id'], $this->templateValue($team['template']));
                $teamIds[$teamKey] = (string)$team['id'];
            }
        }
        $plan = ['teams' => [], 'roles' => []];
        foreach ($groups as $group) {
            foreach ($this->definitions($group, $mappedSections[(string)$group['id']] ?? []) as $definition) {
                $teamKey = $this->key((string)$group['id'], $definition['template']);
                $teamId = $teamIds[$teamKey] ?? null;
                $teamProposal = $definition + [
                    'key' => $teamKey,
                    'parent_key' => $definition['parent_template'] === null
                        ? null
                        : $this->key((string)$group['id'], $definition['parent_template']),
                ];
                if ($teamId === null) {
                    $plan['teams'][] = $teamProposal + ['action' => 'create'];
                } elseif ($reviewOverrides && $this->teamName($existingTeams, $teamId) !== $definition['team_name']) {
                    $plan['teams'][] = $teamProposal + [
                        'action' => 'update',
                        'id' => $teamId,
                        'existing_name' => $this->teamName($existingTeams, $teamId),
                    ];
                }
                foreach ($definition['roles'] as [$roleName, $roleTemplate, $isLead, $multiMember, $isTrusteeRole]) {
                    $roleKey = $this->key((string)$group['id'], $roleTemplate);
                    $role = $this->roleByTemplate($existingRoles, $roleKey);
                    $roleProposal = [
                        'key' => $this->key($teamKey, $roleTemplate), 'team_key' => $teamKey,
                        'team_name' => $definition['team_name'], 'group_id' => $group['id'],
                        'group_name' => $group['group_name'], 'role_name' => $roleName, 'template' => $roleTemplate,
                        'is_lead' => $isLead, 'multi_member_role' => $multiMember,
                        'is_trustee_role' => $isTrusteeRole,
                    ];
                    if ($role === null) {
                        $plan['roles'][] = $roleProposal + ['action' => 'create'];
                    } elseif (
                        $reviewOverrides
                        && ($role['name'] !== $roleName || $role['is_trustee_role'] !== $isTrusteeRole)
                    ) {
                        $plan['roles'][] = $roleProposal + [
                            'action' => 'update',
                            'id' => $role['id'],
                            'existing_name' => $role['name'],
                        ];
                    }
                }
            }
        }

        return $plan;
    }

    /** @return list<array<string, mixed>> */
    public function teamPlan(bool $reviewOverrides = false): array
    {
        return $this->plan($reviewOverrides)['teams'];
    }

    /** @return list<array<string, mixed>> */
    public function rolePlan(bool $reviewOverrides = false): array
    {
        $teamTemplates = [];
        foreach ($this->teams()->find()->select(['group_id', 'template'])->enableHydration(false) as $team) {
            if ($team['template'] !== null) {
                $teamTemplates[$this->key((string)$team['group_id'], $this->templateValue($team['template']))] = true;
            }
        }

        return array_values(array_filter(
            $this->plan($reviewOverrides)['roles'],
            fn(array $role): bool => isset($teamTemplates[$role['team_key']]),
        ));
    }

    /** @param list<array{team_name?: mixed, skip?: mixed}> $selections @return int */
    public function createTeams(array $selections, bool $reviewOverrides = false): int
    {
        $plan = $this->plan($reviewOverrides);
        $skippedRoles = array_map(
            fn(array $role): array => ['role_name' => $role['role_name'], 'skip' => true],
            $plan['roles'],
        );

        return $this->create($selections, $skippedRoles, $reviewOverrides)['teams'];
    }

    /** @param list<array{role_name?: mixed, skip?: mixed}> $selections @return int */
    public function createRoles(array $selections, bool $reviewOverrides = false): int
    {
        if ($this->teamPlan() !== []) {
            throw new InvalidArgumentException('Create the standard teams before creating standard roles.');
        }

        return $this->create([], $selections, $reviewOverrides)['roles'];
    }

    /** @return array{teams: int, roles: int} */
    public function run(bool $dryRun = false): array
    {
        $plan = $this->plan();
        if ($dryRun) {
            return ['teams' => count($plan['teams']), 'roles' => count($plan['roles'])];
        }

        return $this->create(
            array_map(fn(array $team): array => ['team_name' => $team['team_name']], $plan['teams']),
            array_map(fn(array $role): array => ['role_name' => $role['role_name']], $plan['roles']),
        );
    }

    /**
     * @param list<array{team_name?: mixed, skip?: mixed}> $teamSelections
     * @param list<array{role_name?: mixed, skip?: mixed}> $roleSelections
     * @return array{teams: int, roles: int}
     */
    public function create(array $teamSelections, array $roleSelections, bool $reviewOverrides = false): array
    {
        [, , $existingTeams, $existingRoles] = $this->sourceData();
        $plan = $this->plan($reviewOverrides);
        if (count($teamSelections) !== count($plan['teams']) || count($roleSelections) !== count($plan['roles'])) {
            throw new InvalidArgumentException('The proposed template has changed. Reload the page and try again.');
        }
        $selectedTeams = $this->selections($plan['teams'], $teamSelections, 'team_name');
        $selectedRoles = $this->selections($plan['roles'], $roleSelections, 'role_name');
        $this->validateSelections($plan, $selectedTeams, $selectedRoles, $existingTeams, $existingRoles);
        $teams = $this->teams();
        $roles = $teams->Roles;
        $teamIds = [];
        foreach ($existingTeams as $team) {
            if ($team['template'] !== null) {
                $teamKey = $this->key((string)$team['group_id'], $this->templateValue($team['template']));
                $teamIds[$teamKey] = (string)$team['id'];
            }
        }
        $result = ['teams' => 0, 'roles' => 0];
        $teams->getConnection()->transactional(function () use (
            $plan,
            $selectedTeams,
            $selectedRoles,
            $teams,
            $roles,
            &$teamIds,
            &$result,
        ): void {
            foreach ($plan['teams'] as $index => $team) {
                if (!$selectedTeams[$index]['apply']) {
                    continue;
                }
                if ($team['action'] === 'update') {
                    $entity = $teams->get($team['id']);
                    $entity->team_name = $selectedTeams[$index]['name'];
                    $teams->saveOrFail($entity);
                    $result['teams']++;
                    continue;
                }
                $entity = $teams->newEntity([
                    'group_id' => $team['group_id'], 'section_id' => $team['section_id'],
                    'team_name' => $selectedTeams[$index]['name'],
                    'team_parent_id' => $team['parent_key'] === null ? null : $teamIds[$team['parent_key']],
                ]);
                $entity->template = $team['template'];
                $saved = $teams->saveOrFail($entity);
                $teamIds[$team['key']] = $saved->id;
                $result['teams']++;
            }
            // Free the final names first so selected updates can safely exchange names.
            foreach ($plan['roles'] as $index => $role) {
                if (
                    $role['action'] !== 'update'
                    || !$selectedRoles[$index]['apply']
                    || $role['existing_name'] === $selectedRoles[$index]['name']
                ) {
                    continue;
                }
                $entity = $roles->get($role['id']);
                $entity->name = '__template-role-' . $role['id'];
                $roles->saveOrFail($entity);
            }
            foreach ($plan['roles'] as $index => $role) {
                $teamIndex = $this->teamIndex($plan['teams'], $role['team_key']);
                if (!$selectedRoles[$index]['apply']) {
                    continue;
                }
                if (
                    $teamIndex !== null
                    && !$selectedTeams[$teamIndex]['apply']
                    && $plan['teams'][$teamIndex]['action'] === 'create'
                ) {
                    continue;
                }
                if ($role['action'] === 'update') {
                    $entity = $roles->get($role['id']);
                    $entity->name = $selectedRoles[$index]['name'];
                    $entity->is_trustee_role = $role['is_trustee_role'];
                    $roles->saveOrFail($entity);
                    $result['roles']++;
                    continue;
                }
                $teamId = $teamIds[$role['team_key']] ?? null;
                if ($teamId === null) {
                    throw new InvalidArgumentException(
                        'The proposed template has changed. Reload the page and try again.',
                    );
                }
                $entity = $roles->newEntity([
                    'team_id' => $teamId,
                    'name' => $selectedRoles[$index]['name'],
                    'is_lead' => $role['is_lead'],
                    'multi_member_role' => $role['multi_member_role'],
                    'is_trustee_role' => $role['is_trustee_role'],
                ]);
                $entity->template = $role['template'];
                $roles->saveOrFail($entity);
                $result['roles']++;
            }
        });

        return $result;
    }

    /**
     * @param array{teams: list<array<string, mixed>>, roles: list<array<string, mixed>>} $plan
     * @param list<array{name: string, skip: bool, apply: bool}> $teams
     * @param list<array{name: string, skip: bool, apply: bool}> $roles
     * @param list<array<string, mixed>> $existingTeams
     * @param list<array<string, mixed>> $existingRoles
     */
    private function validateSelections(
        array $plan,
        array $teams,
        array $roles,
        array $existingTeams,
        array $existingRoles,
    ): void {
        $teamNames = [];
        $teamIds = [];
        foreach ($existingTeams as $team) {
            $key = $this->key((string)$team['group_id'], (string)$team['team_name']);
            $teamNames[$key] = true;
            if ($team['template'] !== null) {
                $teamKey = $this->key((string)$team['group_id'], $this->templateValue($team['template']));
                $teamIds[$teamKey] = (string)$team['id'];
            }
        }
        foreach ($plan['teams'] as $index => $team) {
            if (!$teams[$index]['apply']) {
                continue;
            }
            $key = $this->key((string)$team['group_id'], $teams[$index]['name']);
            if (isset($teamNames[$key])) {
                throw new InvalidArgumentException(sprintf(
                    'A team named "%s" already exists in this group.',
                    $teams[$index]['name'],
                ));
            }
            $teamNames[$key] = true;
            if ($team['action'] === 'create' && $team['parent_key'] !== null) {
                $parentIndex = $this->teamIndex($plan['teams'], $team['parent_key']);
                if ($parentIndex !== null && $teams[$parentIndex]['skip']) {
                    throw new InvalidArgumentException(sprintf(
                        'Keep the Leadership Team for "%s", or skip this team too.',
                        $teams[$index]['name'],
                    ));
                }
            }
        }
        $roleNames = [];
        $leadTeams = [];
        $updatedRoleIds = [];
        foreach ($plan['roles'] as $index => $role) {
            if ($role['action'] === 'update' && $roles[$index]['apply']) {
                $updatedRoleIds[(string)$role['id']] = true;
            }
        }
        foreach ($existingRoles as $role) {
            if (!isset($updatedRoleIds[(string)$role['id']])) {
                $roleNames[$this->key((string)$role['group_id'], (string)$role['name'])] = true;
            }
            if ($role['is_lead']) {
                $leadTeams[(string)$role['team_id']] = true;
            }
        }
        foreach ($plan['roles'] as $index => $role) {
            $teamIndex = $this->teamIndex($plan['teams'], $role['team_key']);
            if (!$roles[$index]['apply']) {
                continue;
            }
            if (
                $teamIndex !== null
                && !$teams[$teamIndex]['apply']
                && $plan['teams'][$teamIndex]['action'] === 'create'
            ) {
                continue;
            }
            $key = $this->key((string)$role['group_id'], $roles[$index]['name']);
            if (isset($roleNames[$key])) {
                throw new InvalidArgumentException(sprintf(
                    'A role named "%s" already exists in this group.',
                    $roles[$index]['name'],
                ));
            }
            $roleNames[$key] = true;
            if ($role['action'] === 'create' && $role['is_lead']) {
                $leadKey = $teamIds[$role['team_key']] ?? $role['team_key'];
                if (isset($leadTeams[$leadKey])) {
                    throw new InvalidArgumentException(sprintf(
                        'The team "%s" already has a lead role.',
                        $role['team_name'],
                    ));
                }
                $leadTeams[$leadKey] = true;
            }
        }
    }

    /**
     * @param list<array<string, mixed>> $proposals
     * @param list<array<string, mixed>> $selections
     * @return list<array{name: string, skip: bool, apply: bool}>
     */
    private function selections(array $proposals, array $selections, string $field): array
    {
        $result = [];
        foreach ($proposals as $index => $proposal) {
            $selection = $selections[$index] ?? null;
            if (!is_array($selection)) {
                throw new InvalidArgumentException('Invalid template selection.');
            }
            $name = trim((string)($selection[$field] ?? ''));
            $skip = filter_var($selection['skip'] ?? false, FILTER_VALIDATE_BOOL);
            if (!$skip && $name === '') {
                throw new InvalidArgumentException('A name is required unless the item is skipped.');
            }
            $result[] = [
                'name' => $name,
                'skip' => $skip,
                'apply' => !$skip && (
                    $proposal['action'] === 'create'
                    || filter_var($selection['apply'] ?? false, FILTER_VALIDATE_BOOL)
                ),
            ];
        }

        return $result;
    }

    /** @param list<array<string, mixed>> $teams */
    private function teamName(array $teams, string $id): string
    {
        foreach ($teams as $team) {
            if ((string)$team['id'] === $id) {
                return (string)$team['team_name'];
            }
        }

        throw new InvalidArgumentException('The proposed template has changed. Reload the page and try again.');
    }

    /**
     * @param list<array<string, mixed>> $roles
     * @return array<string, mixed>|null
     */
    private function roleByTemplate(array $roles, string $key): ?array
    {
        foreach ($roles as $role) {
            if (
                $role['template'] !== null
                && $this->key((string)$role['group_id'], $this->templateValue($role['template'])) === $key
            ) {
                return $role;
            }
        }

        return null;
    }

    /**
     * @return array{
     *     list<array<string, mixed>>,
     *     array<string, list<array{id: string, section_type: string, section_name: string}>>,
     *     list<array<string, mixed>>,
     *     list<array<string, mixed>>
     * }
     */
    private function sourceData(): array
    {
        $groups = $this->fetchTable('Groups')->find()
            ->select(['id', 'group_name'])
            ->where(['type' => GroupType::Group->value])
            ->orderByAsc('sort_order')
            ->orderByAsc('group_name')
            ->enableHydration(false)
            ->all()
            ->toList();
        $teams = $this->teams();

        return [
            array_map(static fn($group): array => (array)$group, array_values($groups)),
            $this->mappedSections(),
            array_map(
                static fn($team): array => (array)$team,
                array_values($teams->find()
                    ->select(['id', 'group_id', 'team_name', 'template'])
                    ->enableHydration(false)
                    ->all()
                    ->toList()),
            ),
            array_map(
                static fn($role): array => (array)$role,
                array_values($this->roles()->find()
                    ->select(['id', 'team_id', 'group_id', 'name', 'is_lead', 'is_trustee_role', 'template'])
                    ->enableHydration(false)
                    ->all()
                    ->toList()),
            ),
        ];
    }

    /**
     * @param array<string, mixed> $group
     * @param list<array{id: string, section_type: string, section_name: string}> $sections
     * @return list<array<string, mixed>>
     */
    private function definitions(array $group, array $sections): array
    {
        $definitions = [[
            'team_name' => $group['group_name'] . ' Leadership Team',
            'template' => 'leadership-team',
            'parent_template' => null,
            'group_id' => $group['id'],
            'group_name' => $group['group_name'],
            'section_id' => null,
            'section_name' => null,
            'roles' => [
                ['Group Lead Volunteer', 'group-lead-volunteer', true, false, true],
                ['Group Leadership Team Member', 'group-leadership-team-member', false, true, false],
            ],
        ]];
        foreach ($sections as $section) {
            $name = $this->sectionTeamName($group['group_name'], $this->templateValue($section['section_type']));
            $template = $this->sectionTemplate($this->templateValue($section['section_type']));
            $definitions[] = [
                'team_name' => $name,
                'template' => $template,
                'parent_template' => 'leadership-team',
                'group_id' => $group['id'],
                'group_name' => $group['group_name'],
                'section_id' => $section['id'],
                'section_name' => $section['section_name'],
                'roles' => [
                    [$name . ' Team Leader', $template . '-team-leader', true, false, false],
                    [$name . ' Team Member', $template . '-team-member', false, true, false],
                ],
            ];
        }
        $definitions[] = [
            'team_name' => 'Trustee Board',
            'template' => 'trustee-board',
            'parent_template' => 'leadership-team',
            'group_id' => $group['id'],
            'group_name' => $group['group_name'],
            'section_id' => null,
            'section_name' => null,
            'roles' => [
                ['Trustee Board Chair', 'trustee-board-chair', true, false, true],
                ['Group Treasurer', 'group-treasurer', false, false, true],
                ['Trustee Board Member', 'trustee-board-member', false, true, true],
            ],
        ];

        return $definitions;
    }

    /** @return \App\Model\Table\TeamsTable */
    private function teams(): TeamsTable
    {
        $table = $this->fetchTable('Teams');
        if (!$table instanceof TeamsTable) {
            throw new InvalidArgumentException('Teams table is not configured correctly.');
        }

        return $table;
    }

    /** @return \App\Model\Table\RolesTable */
    private function roles(): RolesTable
    {
        $table = $this->fetchTable('Roles');
        if (!$table instanceof RolesTable) {
            throw new InvalidArgumentException('Roles table is not configured correctly.');
        }

        return $table;
    }

    /** @param list<array<string, mixed>> $teams */
    private function teamIndex(array $teams, string $teamKey): ?int
    {
        foreach ($teams as $index => $team) {
            if ($team['key'] === $teamKey) {
                return $index;
            }
        }

        return null;
    }

    /** @return array<string, list<array{id: string, section_type: string, section_name: string}>> */
    private function mappedSections(): array
    {
        $rows = $this->fetchTable('CsvUnitMappings')->find()
            ->select([
                'section_id' => 'Sections.id',
                'group_id' => 'Sections.group_id',
                'section_type' => 'Sections.section_type',
                'section_name' => 'Sections.section_name',
            ])
            ->innerJoinWith('Sections')
            ->where([
                'CsvUnitMappings.section_id IS NOT' => null,
                'Sections.section_type IN' => ['earlyyears', 'beavers', 'cubs', 'scouts'],
            ])
            ->distinct(['Sections.id'])
            ->enableHydration(false)
            ->all()
            ->toList();
        $sections = [];
        foreach ($rows as $row) {
            $sections[(string)$row['group_id']][] = [
                'id' => (string)$row['section_id'],
                'section_type' => $this->templateValue($row['section_type']),
                'section_name' => (string)$row['section_name'],
            ];
        }

        return $sections;
    }

    /** @param string $type Section type. */
    private function sectionTemplate(string $type): string
    {
        return match ($type) {
            'earlyyears' => 'squirrel-section',
            'beavers' => 'beaver-section',
            'cubs' => 'cub-section',
            'scouts' => 'scout-section',
            default => throw new InvalidArgumentException("Unsupported section type: {$type}"),
        };
    }

    /**
     * @param string $groupName Group name.
     * @param string $type Section type.
     * @return string
     */
    private function sectionTeamName(string $groupName, string $type): string
    {
        return $groupName . ' ' . match ($type) {
            'earlyyears' => 'Squirrels', 'beavers' => 'Beavers', 'cubs' => 'Cubs', 'scouts' => 'Scouts',
            default => throw new InvalidArgumentException("Unsupported section type: {$type}"),
        };
    }

    /**
     * @param string $left First key segment.
     * @param string $right Second key segment.
     * @return string
     */
    private function key(string $left, string $right): string
    {
        return $left . "\0" . $right;
    }

    /** @return string */
    private function templateValue(mixed $template): string
    {
        return $template instanceof BackedEnum ? (string)$template->value : (string)$template;
    }
}
