<?php
declare(strict_types=1);

namespace App\Command;

use App\Service\DistrictCoreDataService;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Exception;

class SyncDistrictCoreDataCommand extends Command
{
    /**
     * @return string
     */
    public static function defaultName(): string
    {
        return 'district_core_data:sync';
    }

    /**
     * @return string
     */
    public static function getDescription(): string
    {
        return 'Synchronise groups and sections from DistrictCoreData.';
    }

    /**
     * @param \Cake\Console\Arguments $args Arguments.
     * @param \Cake\Console\ConsoleIo $io Console IO.
     * @return int
     */
    public function execute(Arguments $args, ConsoleIo $io): int
    {
        try {
            $service = new DistrictCoreDataService();
            $data = $service->fetch();
            $counts = $service->sync($data['groups'], $data['sections']);
        } catch (Exception $exception) {
            $io->error($exception->getMessage());

            return self::CODE_ERROR;
        }

        $io->success(sprintf(
            'Synchronised %d groups and %d sections.',
            $counts['groups'],
            $counts['sections'],
        ));

        return self::CODE_SUCCESS;
    }
}
