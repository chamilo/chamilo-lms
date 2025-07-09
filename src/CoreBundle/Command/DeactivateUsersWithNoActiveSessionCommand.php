<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Command;

use Chamilo\CoreBundle\Entity\User;
use DateTime;
use DateTimeZone;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:deactivate-users-with-no-active-session',
    description: 'Deactivate users with role "student" who are not part of any active session (session endDate is null or in the future).'
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

        $io->title('Deactivating students without active sessions...');
        $io->text('Checking as of '.$now->format('Y-m-d H:i:s'));

        // Subquery: user IDs with at least one session where endDate > now OR endDate is null
        $subQb = $this->entityManager->createQueryBuilder();
        $subQuery = $subQb
            ->select('IDENTITY(sru.user)')
            ->from('Chamilo\CoreBundle\Entity\SessionRelUser', 'sru')
            ->join('sru.session', 's')
            ->where($subQb->expr()->orX(
                's.accessEndDate > :now',
                's.accessEndDate IS NULL'
            ))
            ->getDQL()
        ;

        // Main query: active students not in the subquery
        $qb = $this->entityManager->createQueryBuilder();
        $usersToDeactivate = $qb
            ->select('u')
            ->from(User::class, 'u')
            ->where('u.active = 1')
            ->andWhere('u.status = :studentRole')
            ->andWhere($qb->expr()->notIn('u.id', $subQuery))
            ->setParameter('now', $now, Types::DATETIME_MUTABLE)
            ->setParameter('studentRole', 5)
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
            $io->success("Successfully deactivated {$deactivatedCount} students without active sessions.");
        }

        return Command::SUCCESS;
    }
}
