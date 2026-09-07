<?php
declare(strict_types=1);

namespace App\Command;

use App\Service\TeamScopeBackfill;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Database\Connection;
use Cake\Datasource\ConnectionManager;
use Exception;
use RuntimeException;

class BackfillTeamScopesCommand extends Command
{
    /**
     * @return string
     */
    public static function defaultName(): string
    {
        return 'teams:backfill_scopes';
    }

    /**
     * @return string
     */
    public static function getDescription(): string
    {
        return 'Inherit missing team groups and sections from parents before requiring a group.';
    }

    /**
     * @param \Cake\Console\ConsoleOptionParser $parser Parser.
     * @return \Cake\Console\ConsoleOptionParser
     */
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        return parent::buildOptionParser($parser)
            ->addOption('dry-run', ['boolean' => true, 'help' => 'Validate and preview without changing records.']);
    }

    /**
     * @param \Cake\Console\Arguments $args Arguments.
     * @param \Cake\Console\ConsoleIo $io Console IO.
     * @return int
     */
    public function execute(Arguments $args, ConsoleIo $io): int
    {
        try {
            $connection = ConnectionManager::get('default');
            if (!$connection instanceof Connection) {
                throw new RuntimeException('The default connection must be a Cake database connection.');
            }
            $changes = (new TeamScopeBackfill())->run(
                $connection,
                (bool)$args->getOption('dry-run'),
            );
            foreach ($changes as $change) {
                $io->out(sprintf(
                    '%s (%s): group %s; section %s',
                    $change['team_name'],
                    $change['id'],
                    $change['group_id'],
                    $change['section_id'] ?? 'none',
                ));
            }
            $io->success(sprintf(
                '%d teams %s.',
                count($changes),
                $args->getOption('dry-run') ? 'would be updated' : 'updated',
            ));

            return self::CODE_SUCCESS;
        } catch (Exception $exception) {
            $io->error($exception->getMessage());

            return self::CODE_ERROR;
        }
    }
}
