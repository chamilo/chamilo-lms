<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Command;

use Chamilo\CoreBundle\Helpers\BuyCoursesExpiryHelper;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

#[AsCommand(
    name: 'buycourses:process-expiry',
    description: 'Process expired BuyCourses service benefits.',
)]
class BuyCoursesExpiryCommand extends Command
{
    public function __construct(
        private readonly BuyCoursesExpiryHelper $expiryHelper,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $processedUsers = $this->expiryHelper->processExpiredServiceBenefits();

            $output->writeln(
                sprintf(
                    '<info>BuyCourses expiry process completed. Processed users: %d</info>',
                    $processedUsers
                )
            );

            return Command::SUCCESS;
        } catch (Throwable $exception) {
            $output->writeln(
                sprintf(
                    '<error>BuyCourses expiry process failed: %s</error>',
                    $exception->getMessage()
                )
            );

            return Command::FAILURE;
        }
    }
}
