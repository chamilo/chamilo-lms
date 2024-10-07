<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Command;

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Repository\Node\AccessUrlRepository;
use Chamilo\CoreBundle\Service\ScheduledAnnouncementService;
use Database;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class SendScheduledAnnouncementsCommand extends Command
{
    protected static $defaultName = 'app:send-scheduled-announcements';

    public function __construct(
        private readonly AccessUrlRepository          $accessUrlRepository,
        private readonly ScheduledAnnouncementService $scheduledAnnouncementService,
        private readonly EntityManager $em
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Send scheduled announcements to all users.')
            ->addOption('debug', null, null, 'If set, debug messages will be shown.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        Database::setManager($this->em);

        $container = $this->getApplication()->getKernel()->getContainer();
        Container::setContainer($container);

        $io = new SymfonyStyle($input, $output);
        $debug = (bool) $input->getOption('debug');
        $urlList = $this->accessUrlRepository->findAll();

        if (empty($urlList)) {
            $io->warning('No access URLs found.');
            return Command::SUCCESS;
        }

        foreach ($urlList as $url) {
            $urlId = $url->getId();
            $io->writeln("Portal: #".$urlId." - ".$url->getUrl());

            try {
                $messagesSent = $this->scheduledAnnouncementService->sendPendingMessages($urlId, $debug);
                $io->writeln("Messages sent: $messagesSent");

                if ($debug) {
                    $io->writeln("Debug: Processed portal with ID ".$urlId);
                }
            } catch (\Exception $e) {
                $io->error('Error processing portal with ID ' . $urlId . ': ' . $e->getMessage());
                return Command::FAILURE;
            }
        }

        $io->success('All scheduled announcements have been sent.');
        return Command::SUCCESS;
    }
}
