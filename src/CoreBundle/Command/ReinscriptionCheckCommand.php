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

        // 1. Find all lessons with "validity_in_days" > 0
        $learningPaths = $this->lpRepository->findWithValidity();

        /* @var CLp $lp */
        foreach ($learningPaths as $lp) {
            $validityDays = $lp->getValidityInDays();
            $sessionId = $this->lpRepository->getLpSessionId($lp->getIid());

            if (!$sessionId) {
                if ($debug) {
                    $output->writeln('Session ID not found for Learning Path ID: ' . $lp->getIid());
                }
                continue;
            }

            // 2. Get the session of the lesson
            $session = $this->sessionRepository->find($sessionId);
            if (!$session) {
                if ($debug) {
                    $output->writeln('Session not found for ID: ' . $sessionId);
                }
                continue;
            }

            // Process only if the session is not the last repetition
            if ($session->getLastRepetition()) {
                if ($debug) {
                    $output->writeln('Session ' . $session->getId() . ' is the last repetition. Skipping...');
                }
                continue;
            }

            // 3. Find users who completed the lesson and whose validity has expired
            $expiredUsers = $this->findExpiredCompletions($lp, $validityDays);

            if (count($expiredUsers) === 0) {
                if ($debug) {
                    $output->writeln('No expired users found for Learning Path ID: ' . $lp->getIid());
                }
                continue;
            }

            foreach ($expiredUsers as $user) {
                if ($debug) {
                    $output->writeln('User ' . $user->getUser()->getId() . ' has expired completion for LP ' . $lp->getIid());
                }

                // 4. Find the last valid child session
                $validChildSession = $this->sessionRepository->findValidChildSession($session);

                if ($validChildSession) {
                    // Reinscribe user in the valid child session
                    $this->enrollUserInSession($user->getUser(), $validChildSession);
                    if ($debug) {
                        $output->writeln('Reinscribed user ' . $user->getUser()->getId() . ' into child session ' . $validChildSession->getId());
                    }
                } else {
                    // 5. If no valid child session, find the valid parent session
                    $validParentSession = $this->sessionRepository->findValidParentSession($session);
                    if ($validParentSession) {
                        // Reinscribe user in the valid parent session
                        $this->enrollUserInSession($user->getUser(), $validParentSession);
                        if ($debug) {
                            $output->writeln('Reinscribed user ' . $user->getUser()->getId() . ' into parent session ' . $validParentSession->getId());
                        }
                    } else {
                        if ($debug) {
                            $output->writeln('No valid parent or child session found for user ' . $user->getUser()->getId());
                        }
                    }
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
}
