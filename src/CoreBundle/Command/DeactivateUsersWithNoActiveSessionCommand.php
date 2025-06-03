<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Command;

use Chamilo\CoreBundle\Entity\User;
use DateTime;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:deactivate-users-with-no-active-session',
    description: 'Deactivate users who are not part of any active session (where session end date has passed).'
)]
class DeactivateUsersWithNoActiveSessionCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Run without saving changes')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = $input->getOption('dry-run');
        $now = new DateTime('now', new DateTimeZone('UTC'));

        $io->title('Deactivating users without active sessions...');
        $io->text('Checking users as of '.$now->format('Y-m-d H:i:s'));

        // Subquery: user IDs with at least one session where end date is in the future
        $subQuery = $this->entityManager->createQueryBuilder()
            ->select('IDENTITY(sru.user)')
            ->from('Chamilo\CoreBundle\Entity\SessionRelUser', 'sru')
            ->join('sru.session', 's')
            ->where('s.displayEndDate > :now')
            ->getDQL()
        ;

        // Main query: get all active users not in the subquery
        $qb = $this->entityManager->createQueryBuilder();
        $usersToDeactivate = $qb
            ->select('u')
            ->from(User::class, 'u')
            ->where('u.active = 1')
            ->andWhere($qb->expr()->notIn('u.id', $subQuery))
            ->setParameter('now', $now)
            ->getQuery()
            ->getResult()
        ;

        $deactivatedCount = 0;

        /** @var User $user */
        foreach ($usersToDeactivate as $user) {
            $user->setActive(0);
            $this->entityManager->persist($user);
            $io->writeln("Deactivated user ID {$user->getId()} ({$user->getUsername()})");
            $deactivatedCount++;
        }

        if ($dryRun) {
            $io->warning("Dry run mode enabled. {$deactivatedCount} users would be deactivated.");
        } else {
            $this->entityManager->flush();
            $io->success("Successfully deactivated {$deactivatedCount} users without active sessions.");
        }

        return Command::SUCCESS;
    }
}
