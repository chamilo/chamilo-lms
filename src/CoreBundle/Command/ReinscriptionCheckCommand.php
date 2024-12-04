<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Command;

use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelUser;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Repository\CLpRepository;
use Chamilo\CoreBundle\Repository\SessionRepository;
use Chamilo\CourseBundle\Entity\CLpView;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

class ReinscriptionCheckCommand extends Command
{
    protected static $defaultName = 'app:reinscription-check';

    private CLpRepository $lpRepository;
    private SessionRepository $sessionRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(
        CLpRepository $lpRepository,
        SessionRepository $sessionRepository,
        EntityManagerInterface $entityManager
    ) {
        parent::__construct();
        $this->lpRepository = $lpRepository;
        $this->sessionRepository = $sessionRepository;
        $this->entityManager = $entityManager;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Checks for users whose course completions have expired and reinscribe them into new sessions if needed.')
            ->addOption(
                'debug',
                null,
                InputOption::VALUE_NONE,
                'If set, debug messages will be shown.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $debug = $input->getOption('debug');

        $expiredViews = $this->lpRepository->findExpiredViews(0);

        if ($debug) {
            $output->writeln(sprintf('Found %d expired views.', count($expiredViews)));
        }

        foreach ($expiredViews as $view) {
            $user = $view->getUser();
            $session = $view->getSession();

            if ($this->isUserAlreadyEnrolledInChildSession($user, $session)) {
                if ($debug) {
                    $output->writeln(sprintf('User %d is already enrolled in a valid child session.', $user->getId()));
                }
                continue;
            }

            $lp = $view->getLp();

            if ($debug) {
                $output->writeln(sprintf(
                    'User %d completed course %d associated with session %d, and its validity has expired.',
                    $user->getId(),
                    $lp->getIid(),
                    $session->getId()
                ));
            }

            $validSession = $this->findValidSessionInHierarchy($session);

            if ($validSession) {
                $this->enrollUserInSession($user, $validSession);
                if ($debug) {
                    $output->writeln(sprintf(
                        'User %d re-enrolled into session %d.',
                        $user->getId(),
                        $validSession->getId()
                    ));
                }
            } else {
                if ($debug) {
                    $output->writeln(sprintf(
                        'No valid session found for user %d.',
                        $user->getId()
                    ));
                }
            }
        }

        return Command::SUCCESS;
    }

    /**
     * Find users with expired completion based on "validity_in_days".
     */
    private function findExpiredCompletions($lp, $validityDays)
    {
        $now = new \DateTime();
        $expirationDate = (clone $now)->modify('-' . $validityDays . ' days');

        // Find users with 100% completion and whose last access date (start_time) is older than 'validity_in_days'
        return $this->entityManager->getRepository(CLpView::class)
            ->createQueryBuilder('v')
            ->innerJoin('Chamilo\CourseBundle\Entity\CLpItemView', 'iv', 'WITH', 'iv.view = v')
            ->where('v.lp = :lp')
            ->andWhere('v.progress = 100')
            ->andWhere('iv.startTime < :expirationDate')
            ->setParameter('lp', $lp)
            ->setParameter('expirationDate', $expirationDate->getTimestamp())
            ->getQuery()
            ->getResult();
    }

    /**
     * Enrolls a user into a session.
     */
    private function enrollUserInSession($user, $session): void
    {
        $existingSubscription = $this->findUserSubscriptionInSession($user, $session);

        if (!$existingSubscription) {
            $session->addUserInSession(Session::STUDENT, $user);
            $this->entityManager->persist($session);
            $this->entityManager->flush();
        }
    }

    private function findUserSubscriptionInSession($user, $session)
    {
        return $this->entityManager->getRepository(SessionRelUser::class)
            ->findOneBy([
                'user' => $user,
                'session' => $session,
            ]);
    }

    private function findValidSessionInHierarchy(Session $session): ?Session
    {
        $childSessions = $this->sessionRepository->findChildSessions($session);

        /* @var Session $child */
        foreach ($childSessions as $child) {
            $validUntil = (clone $child->getAccessEndDate())->modify("-{$child->getDaysToReinscription()} days");
            if (new \DateTime() <= $validUntil) {
                return $child;
            }

            $validChild = $this->findValidSessionInHierarchy($child);
            if ($validChild) {
                return $validChild;
            }
        }

        /* @var Session $parentSession */
        $parentSession = $this->sessionRepository->findParentSession($session);

        if ($parentSession) {
            $validUntil = (clone $parentSession->getAccessEndDate())->modify("-{$parentSession->getDaysToReinscription()} days");
            if (new \DateTime() <= $validUntil) {
                return $parentSession;
            }
        }

        return null;
    }

    private function isUserAlreadyEnrolledInChildSession($user, $parentSession): bool
    {
        $childSessions = $this->sessionRepository->findChildSessions($parentSession);

        foreach ($childSessions as $childSession) {
            if ($this->findUserSubscriptionInSession($user, $childSession)) {
                return true;
            }
        }

        return false;
    }
}
