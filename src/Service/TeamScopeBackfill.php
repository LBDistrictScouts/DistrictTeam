<?php
declare(strict_types=1);

namespace App\Service;

use Cake\Database\Connection;
use RuntimeException;

/**
 * Backfill team scope using parent IDs, without depending on tree coordinates or ORM callbacks.
 */
class TeamScopeBackfill
{
    /**
     * Validate the whole hierarchy before writing any changes.
     *
     * @param \Cake\Database\Connection $connection Database connection.
     * @param bool $dryRun Only report proposed changes.
     * @param string|null $fallbackGroupId Default group for otherwise unassigned roots.
     * @return list<array{id: string, team_name: string, group_id: string, section_id: string|null}>
     */
    public function run(Connection $connection, bool $dryRun = false, ?string $fallbackGroupId = null): array
    {
        return $connection->transactional(function () use ($connection, $dryRun, $fallbackGroupId): array {
            $teams = $connection->execute('SELECT id, team_name, team_parent_id, group_id, section_id FROM teams')
                ->fetchAll('assoc');
            $sections = $connection->execute('SELECT id, group_id FROM sections')->fetchAll('assoc');
            $groups = $connection->execute('SELECT id FROM groups')->fetchAll('assoc');
            $changes = $this->plan(
                $teams,
                array_column($sections, 'group_id', 'id'),
                array_column($groups, 'id'),
                $fallbackGroupId,
            );
            if (!$dryRun) {
                foreach ($changes as $change) {
                    $connection->update('teams', [
                        'group_id' => $change['group_id'], 'section_id' => $change['section_id'],
                    ], ['id' => $change['id']]);
                }
            }

            return $changes;
        });
    }

    /**
     * Resolve parents first, preserving explicit assignments and detecting invalid hierarchies.
     *
     * @param list<array<string, mixed>> $teams Existing teams.
     * @param array<string, string> $sectionGroups Section UUID to group UUID.
     * @param list<string> $groupIds Known group UUIDs.
     * @param string|null $fallbackGroupId Default group for otherwise unassigned roots.
     * @return list<array{id: string, team_name: string, group_id: string, section_id: string|null}>
     */
    public function plan(array $teams, array $sectionGroups, array $groupIds, ?string $fallbackGroupId = null): array
    {
        $byId = array_column($teams, null, 'id');
        $knownGroups = array_fill_keys($groupIds, true);
        $resolved = [];
        $visiting = [];
        $errors = [];
        $changes = [];
        $resolve = function (string $id) use (
            &$resolve,
            &$resolved,
            &$visiting,
            &$errors,
            &$changes,
            $byId,
            $knownGroups,
            $sectionGroups,
            $fallbackGroupId,
        ): array {
            if (isset($resolved[$id])) {
                return $resolved[$id];
            }
            if (isset($visiting[$id])) {
                throw new RuntimeException('Cycle in team hierarchy at ' . $id . '. No changes were applied.');
            }
            if (!isset($byId[$id])) {
                throw new RuntimeException('Missing parent team ' . $id . '. No changes were applied.');
            }
            $visiting[$id] = true;
            $team = $byId[$id];
            $parent = $team['team_parent_id'] === null ? null : $resolve($team['team_parent_id']);
            $section = $team['section_id'];
            $group = $team['group_id'];
            // An explicit section supplies its own group, even when it differs from the parent.
            if ($section !== null) {
                if (!isset($sectionGroups[$section])) {
                    $errors[] = $team['team_name'] . ' (' . $id . '): unknown section ' . $section;
                } else {
                    $group ??= $sectionGroups[$section];
                    if ($group !== $sectionGroups[$section]) {
                        $errors[] = $team['team_name'] . ' (' . $id . '): section belongs to a different group';
                    }
                }
            }
            $group ??= $parent['group_id'] ?? $fallbackGroupId;
            // Never inherit a section across an explicitly assigned group boundary.
            if ($section === null && $group !== null && $group === ($parent['group_id'] ?? null)) {
                $section = $parent['section_id'];
            }
            if ($group === null || !isset($knownGroups[$group])) {
                $errors[] = $team['team_name'] . ' (' . $id . '): assign a valid group to this team or its parent';
            }
            $resolved[$id] = ['group_id' => $group, 'section_id' => $section];
            unset($visiting[$id]);
            if ($group !== null && ($group !== $team['group_id'] || $section !== $team['section_id'])) {
                $changes[] = [
                    'id' => $id, 'team_name' => $team['team_name'], 'group_id' => $group, 'section_id' => $section,
                ];
            }

            return $resolved[$id];
        };
        foreach (array_keys($byId) as $id) {
            $resolve($id);
        }
        if ($errors !== []) {
            throw new RuntimeException(
                "Team scope backfill could not finish. No changes were applied:\n" . implode("\n", $errors),
            );
        }

        return $changes;
    }
}
