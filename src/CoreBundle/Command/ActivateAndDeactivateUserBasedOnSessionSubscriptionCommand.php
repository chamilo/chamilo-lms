<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Command;

use Chamilo\CoreBundle\Entity\SessionRelUser;
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
    name: 'app:activate-and-deactivate-user-based-on-session-subscription',
    description: 'Activate users with at least one currently active session subscription; deactivate users whose session subscriptions are all inactive.'
)]
class ActivateAndDeactivateUserBasedOnSessionSubscriptionCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Simulate changes without persisting them');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = (bool) $input->getOption('dry-run');
        $now = new DateTime('now', new DateTimeZone('UTC'));

        $io->title('Activate / deactivate users based on session subscriptions');
        $io->text('Reference date: '.$now->format('Y-m-d H:i:s').' UTC');

        if ($dryRun) {
            $io->warning('Dry-run mode — no changes will be saved.');
        }

        // DQL sub-query: user IDs subscribed to at least one currently active session
        // (accessStartDate <= now AND accessEndDate >= now)
        $activeSessionSubQb = $this->entityManager->createQueryBuilder();
        $activeSessionSubDql = $activeSessionSubQb
            ->select('IDENTITY(sru_active.user)')
            ->from(SessionRelUser::class, 'sru_active')
            ->join('sru_active.session', 's_active')
            ->where('s_active.accessStartDate <= :now')
            ->andWhere('s_active.accessEndDate >= :now')
            ->getDQL()
        ;

        // Users to ACTIVATE: inactive, subscribed to at least one currently active session
        $toActivate = $this->entityManager->createQueryBuilder()
            ->select('u')
            ->from(User::class, 'u')
            ->where('u.active = :inactive')
            ->andWhere(
                $this->entityManager->createQueryBuilder()->expr()->in('u.id', $activeSessionSubDql)
            )
            ->setParameter('inactive', User::INACTIVE, Types::INTEGER)
            ->setParameter('now', $now, Types::DATETIME_MUTABLE)
            ->getQuery()
            ->getResult()
        ;

        // Users to DEACTIVATE: active, with no currently active session (includes users with no subscription at all)
        $toDeactivate = $this->entityManager->createQueryBuilder()
            ->select('u')
            ->from(User::class, 'u')
            ->where('u.active = :active')
            ->andWhere(
                $this->entityManager->createQueryBuilder()->expr()->notIn('u.id', $activeSessionSubDql)
            )
            ->setParameter('active', User::ACTIVE, Types::INTEGER)
            ->setParameter('now', $now, Types::DATETIME_MUTABLE)
            ->getQuery()
            ->getResult()
        ;

        $activatedCount = 0;

        /** @var User $user */
        foreach ($toActivate as $user) {
            $io->writeln(\sprintf('  [ACTIVATE]   #%d %s', $user->getId(), $user->getUsername()));
            if (!$dryRun) {
                $user->setActive(User::ACTIVE);
                $this->entityManager->persist($user);
            }
            ++$activatedCount;
        }

        $deactivatedCount = 0;

        /** @var User $user */
        foreach ($toDeactivate as $user) {
            $io->writeln(\sprintf('  [DEACTIVATE] #%d %s', $user->getId(), $user->getUsername()));
            if (!$dryRun) {
                $user->setActive(User::INACTIVE);
                $this->entityManager->persist($user);
            }
            ++$deactivatedCount;
        }

        if (!$dryRun) {
            $this->entityManager->flush();
        }

        $io->newLine();
        $io->table(
            ['Action', 'Count'],
            [
                ['Activated', $activatedCount],
                ['Deactivated', $deactivatedCount],
            ]
        );

        if ($dryRun) {
            $io->note('Dry-run complete — no changes were persisted.');
        } else {
            $io->success(\sprintf(
                'Done. %d user(s) activated, %d user(s) deactivated.',
                $activatedCount,
                $deactivatedCount
            ));
        }

        return Command::SUCCESS;
    }
}
