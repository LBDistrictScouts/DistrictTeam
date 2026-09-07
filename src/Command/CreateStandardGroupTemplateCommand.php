<?php
declare(strict_types=1);

namespace App\Command;

use App\Service\StandardGroupTemplateCreator;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Exception;

class CreateStandardGroupTemplateCommand extends Command
{
    /**
     * @return string
     */
    public static function defaultName(): string
    {
        return 'teams:create_standard_group_template';
    }

    /**
     * @return string
     */
    public static function getDescription(): string
    {
        return 'Create missing standard Scout Group teams.';
    }

    /**
     * @param \Cake\Console\ConsoleOptionParser $parser Parser.
     * @return \Cake\Console\ConsoleOptionParser
     */
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        return parent::buildOptionParser($parser)
            ->addOption('dry-run', ['boolean' => true, 'help' => 'Preview without creating teams or roles.']);
    }

    /**
     * @param \Cake\Console\Arguments $args Arguments.
     * @param \Cake\Console\ConsoleIo $io Console IO.
     * @return int
     */
    public function execute(Arguments $args, ConsoleIo $io): int
    {
        try {
            $dryRun = (bool)$args->getOption('dry-run');
            $creator = new StandardGroupTemplateCreator();
            $plan = $creator->teamPlan();
            $result = $dryRun
                ? count($plan)
                : $creator->createTeams(array_map(fn(array $team): array => ['team_name' => $team['team_name']], $plan));
        } catch (Exception $exception) {
            $io->error($exception->getMessage());

            return self::CODE_ERROR;
        }
        $verb = $dryRun ? 'Would create' : 'Created';
        $io->success(sprintf('%s %d teams.', $verb, $result));

        return self::CODE_SUCCESS;
    }
}
