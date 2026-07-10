<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Command;

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Helpers\ScheduledAnnouncementHelper;
use Chamilo\CoreBundle\Repository\Node\AccessUrlRepository;
use Chamilo\CoreBundle\State\Announcement\ScheduledCourseAnnouncementProcessor;
use Database;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

#[AsCommand(
    name: 'app:send-scheduled-announcements',
    description: 'Send scheduled announcements to all users.',
)]
class SendScheduledAnnouncementsCommand extends Command
{
    public function __construct(
        private readonly AccessUrlRepository $accessUrlRepository,
        private readonly ScheduledAnnouncementHelper $scheduledAnnouncementHelper,
        private readonly ScheduledCourseAnnouncementProcessor $scheduledCourseAnnouncementProcessor,
        private readonly EntityManager $em,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('debug', null, InputOption::VALUE_NONE, 'If set, debug messages will be shown.')
            ->addOption(
                'also-internal-message',
                null,
                InputOption::VALUE_NONE,
                'Retained for compatibility. Course scheduled announcements always store internal messages '.
                'before email delivery.'
            )
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
                $io->writeln('Session scheduled announcements sent: '.$messagesSent);
            } catch (Throwable $throwable) {
                $io->error(
                    'Error processing session scheduled announcements for portal #'.$urlId.': '.
                    $throwable->getMessage(),
                );

                return Command::FAILURE;
            }

            try {
                $courseMessagesSent = $this->scheduledCourseAnnouncementProcessor->sendPendingMessages(
                    $urlId,
                    $debug,
                    $io,
                );
                $io->writeln('Course scheduled announcements sent: '.$courseMessagesSent);
            } catch (Throwable $throwable) {
                $io->error(
                    'Error processing course scheduled announcements for portal #'.$urlId.': '.
                    $throwable->getMessage(),
                );

                return Command::FAILURE;
            }
        }

        $io->success('All scheduled announcements have been processed.');

        return Command::SUCCESS;
    }
}
