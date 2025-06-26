<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Command;

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Repository\Node\AccessUrlRepository;
use Chamilo\CoreBundle\Helpers\ScheduledAnnouncementHelper;
use Database;
use Doctrine\ORM\EntityManager;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:send-scheduled-announcements',
    description: 'Send scheduled announcements to all users.',
)]
class SendScheduledAnnouncementsCommand extends Command
{
    public function __construct(
        private readonly AccessUrlRepository $accessUrlRepository,
        private readonly ScheduledAnnouncementHelper $scheduledAnnouncementHelper,
        private readonly EntityManager $em
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('debug', null, InputOption::VALUE_NONE, 'If set, debug messages will be shown.')
        ;
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
            $io->writeln('Portal: #'.$urlId.' - '.$url->getUrl());

            try {
                $messagesSent = $this->scheduledAnnouncementHelper->sendPendingMessages($urlId, $debug);
                $io->writeln("Messages sent: $messagesSent");

                if ($debug) {
                    $io->writeln('Debug: Processed portal with ID '.$urlId);
                }
            } catch (Exception $e) {
                $io->error('Error processing portal with ID '.$urlId.': '.$e->getMessage());

                return Command::FAILURE;
            }
        }

        $io->success('All scheduled announcements have been sent.');

        return Command::SUCCESS;
    }
}
