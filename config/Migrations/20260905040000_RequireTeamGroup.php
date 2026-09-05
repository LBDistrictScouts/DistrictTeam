<?php
declare(strict_types=1);

use App\Service\DistrictCoreDataService;
use App\Service\TeamScopeBackfill;
use Cake\Database\Connection;
use Cake\Database\Schema\Collection;
use Cake\ORM\Locator\TableLocator;
use Migrations\BaseMigration;

class RequireTeamGroup extends BaseMigration
{
    /**
     * Resolve missing scopes before adding the NOT NULL constraint.
     *
     * @return void
     */
    public function up(): void
    {
        $connection = $this->getAdapter()->getConnection();
        // Empty installations need no legacy import and can migrate without core credentials.
        $hasTeams = $connection->execute('SELECT id FROM teams LIMIT 1')->fetch('assoc') !== false;
        $service = $hasTeams ? new DistrictCoreDataService() : null;
        $data = $service === null ? null : $this->fetchCoreData($service);
        $connection->transactional(function () use ($connection, $service, $data): void {
            $districtId = null;
            if ($service !== null && $data !== null) {
                $districtId = $this->importCoreData($connection, $service, $data);
            }
            (new TeamScopeBackfill())->run($connection, fallbackGroupId: $districtId);
            $this->table('teams')->changeColumn('group_id', 'uuid', ['null' => false])->update();
        });
    }

    /**
     * @param \App\Service\DistrictCoreDataService $service Import service.
     * @return array{groups: list<array<string, mixed>>, sections: list<array<string, mixed>>}
     */
    protected function fetchCoreData(DistrictCoreDataService $service): array
    {
        return $service->fetch();
    }

    /**
     * Use fresh metadata and the migration connection, including for non-default databases.
     *
     * @param \Cake\Database\Connection $connection Migration connection.
     * @param \App\Service\DistrictCoreDataService $service Import service.
     * @param array{groups: list<array<string, mixed>>, sections: list<array<string, mixed>>} $data Core records.
     * @return string
     */
    protected function importCoreData(Connection $connection, DistrictCoreDataService $service, array $data): string
    {
        $districts = array_values(array_filter($data['groups'], fn($group) => ($group['type'] ?? null) === 'district'));
        if (count($districts) !== 1) {
            throw new RuntimeException('Team migration requires exactly one district group in core data.');
        }
        $locator = new TableLocator();
        $schema = new Collection($connection);
        foreach (['Groups' => 'groups', 'Sections' => 'sections'] as $alias => $table) {
            $locator->setConfig($alias, [
                'connection' => $connection,
                'schema' => $schema->describe($table),
            ]);
        }
        // Counter-cache columns are introduced by the following migration.
        // Importing sections here must therefore not invoke that behavior.
        $locator->get('Sections')->removeBehavior('CounterCache');
        $service->setTableLocator($locator);
        $service->sync($data['groups'], $data['sections']);

        return $districts[0]['id'];
    }

    /**
     * Restore nullability without discarding inherited group/section data.
     *
     * @return void
     */
    public function down(): void
    {
        $this->table('teams')->changeColumn('group_id', 'uuid', ['null' => true])->update();
    }
}
