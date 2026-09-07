<?php
declare(strict_types=1);

namespace App\Command;

use App\Service\StandardGroupTemplateCreator;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Exception;

class CreateStandardGroupRolesCommand extends Command
{
    /** @return string */
    public static function defaultName(): string
    {
        return 'roles:create_standard_group_template';
    }

    /** @return string */
    public static function getDescription(): string
    {
        return 'Create missing standard roles for standard Scout Group teams.';
    }

    /** @return \Cake\Console\ConsoleOptionParser */
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        return parent::buildOptionParser($parser)
            ->addOption('dry-run', ['boolean' => true, 'help' => 'Preview without creating roles.']);
    }

    /** @return int */
    public function execute(Arguments $args, ConsoleIo $io): int
    {
        try {
            $creator = new StandardGroupTemplateCreator();
            $plan = $creator->rolePlan();
            $count = (bool)$args->getOption('dry-run')
                ? count($plan)
                : $creator->createRoles(array_map(fn(array $role): array => ['role_name' => $role['role_name']], $plan));
        } catch (Exception $exception) {
            $io->error($exception->getMessage());

            return self::CODE_ERROR;
        }
        $io->success(sprintf('%s %d roles.', $args->getOption('dry-run') ? 'Would create' : 'Created', $count));

        return self::CODE_SUCCESS;
    }
}
