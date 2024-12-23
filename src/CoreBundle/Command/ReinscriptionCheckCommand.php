<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Command;

use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelUser;
use Chamilo\CoreBundle\Entity\SessionRelCourse;
use Chamilo\CoreBundle\Entity\GradebookCertificate;
use Chamilo\CoreBundle\Repository\SessionRepository;
use Chamilo\CoreBundle\Repository\GradebookCertificateRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

class ReinscriptionCheckCommand extends Command
{
    protected static $defaultName = 'app:reinscription-check';

    private SessionRepository $sessionRepository;
    private GradebookCertificateRepository $certificateRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(
        SessionRepository $sessionRepository,
        GradebookCertificateRepository $certificateRepository,
        EntityManagerInterface $entityManager
    ) {
        parent::__construct();
        $this->sessionRepository = $sessionRepository;
        $this->certificateRepository = $certificateRepository;
        $this->entityManager = $entityManager;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Checks for users who have validated all gradebooks and reinscribe them into new sessions if needed.')
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

        $sessions = $this->sessionRepository->findAll();

        foreach ($sessions as $session) {
            if ($session->getValidityInDays() === null) {
                continue;
            }

            $users = $this->getUsersForSession($session);

            foreach ($users as $user) {
                if ($debug) {
                    $output->writeln(sprintf('Processing user %d in session %d.', $user->getId(), $session->getId()));
                }

                if ($this->isUserReinscribed($user, $session)) {
                    continue;
                }

                if ($this->isUserAlreadyEnrolledInChildSession($user, $session)) {
                    if ($debug) {
                        $output->writeln(sprintf('User %d is already enrolled in a valid child session.', $user->getId()));
                    }
                    continue;
                }

                $certificates = $this->getUserCertificatesForSession($user, $session);

                if ($this->hasUserValidatedAllGradebooks($session, $certificates)) {
                    $latestValidationDate = $this->getLatestCertificateDate($certificates);

                    if ($latestValidationDate !== null) {
                        $reinscriptionDate = (clone $latestValidationDate)->modify("+{$session->getValidityInDays()} days");

                        if ($debug) {
                            $output->writeln(sprintf(
                                'User %d - Latest certificate date: %s, Reinscription date: %s',
                                $user->getId(),
                                $latestValidationDate->format('Y-m-d'),
                                $reinscriptionDate->format('Y-m-d')
                            ));
                        }

                        if (new \DateTime() >= $reinscriptionDate) {
                            $validSession = $this->findValidSessionInHierarchy($session);

                            if ($validSession) {
                                $this->enrollUserInSession($user, $validSession, $session);
                                if ($debug) {
                                    $output->writeln(sprintf(
                                        'User %d re-enrolled into session %d.',
                                        $user->getId(),
                                        $validSession->getId()
                                    ));
                                }
                            }
                        }
                    } else {
                        if ($debug) {
                            $output->writeln(sprintf(
                                'User %d has no valid certificates for session %d.',
                                $user->getId(),
                                $session->getId()
                            ));
                        }
                    }
                }
            }
        }

        return Command::SUCCESS;
    }

    /**
     * Retrieves all users associated with the session.
     */
    private function getUsersForSession(Session $session): array
    {
        $usersToNotify = [];
        $sessionCourses = $this->entityManager->getRepository(SessionRelCourse::class)->findBy(['session' => $session]);

        foreach ($sessionCourses as $courseRel) {
            $course = $courseRel->getCourse();

            $studentSubscriptions = $session->getSessionRelCourseRelUsersByStatus($course, Session::STUDENT);
            foreach ($studentSubscriptions as $studentSubscription) {
                $usersToNotify[$studentSubscription->getUser()->getId()] = $studentSubscription->getUser();
            }

            $coachSubscriptions = $session->getSessionRelCourseRelUsersByStatus($course, Session::COURSE_COACH);
            foreach ($coachSubscriptions as $coachSubscription) {
                $usersToNotify[$coachSubscription->getUser()->getId()] = $coachSubscription->getUser();
            }
        }

        $generalCoaches = $session->getGeneralCoaches();
        foreach ($generalCoaches as $generalCoach) {
            $usersToNotify[$generalCoach->getId()] = $generalCoach;
        }

        return array_values($usersToNotify);
    }

    /**
     * Checks if the user is already enrolled in a valid child session.
     */
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

    /**
     * Gets the user's certificates for the courses in the session.
     */
    private function getUserCertificatesForSession($user, Session $session): array
    {
        $courses = $this->entityManager->getRepository(SessionRelCourse::class)
            ->findBy(['session' => $session]);

        $courseIds = array_map(fn($rel) => $rel->getCourse()->getId(), $courses);

        return $this->certificateRepository->createQueryBuilder('gc')
            ->join('gc.category', 'cat')
            ->where('gc.user = :user')
            ->andWhere('cat.course IN (:courses)')
            ->setParameter('user', $user)
            ->setParameter('courses', $courseIds)
            ->getQuery()
            ->getResult();
    }

    /**
     * Checks if the user has validated all gradebooks in the session.
     */
    private function hasUserValidatedAllGradebooks(Session $session, array $certificates): bool
    {
        $courses = $this->entityManager->getRepository(SessionRelCourse::class)
            ->findBy(['session' => $session]);

        return count($certificates) === count($courses);
    }

    /**
     * Returns the latest certificate creation date.
     */
    private function getLatestCertificateDate(array $certificates): ?\DateTime
    {
        $dates = array_map(fn($cert) => $cert->getCreatedAt(), $certificates);

        if (empty($dates)) {
            return null;
        }

        return max($dates);
    }

    /**
     * Enrolls the user in a new session and updates the previous session subscription.
     */
    private function enrollUserInSession($user, $newSession, $oldSession): void
    {
        $existingSubscription = $this->findUserSubscriptionInSession($user, $newSession);

        if (!$existingSubscription) {
            $newSession->addUserInSession(Session::STUDENT, $user);

            $subscription = $this->findUserSubscriptionInSession($user, $oldSession);
            if ($subscription) {
                $subscription->setNewSubscriptionSessionId($newSession->getId());
            }

            $this->entityManager->persist($newSession);
            $this->entityManager->flush();
        }
    }

    /**
     * Determines if the user has already been reinscribed.
     */
    private function isUserReinscribed($user, Session $session): bool
    {
        $subscription = $this->findUserSubscriptionInSession($user, $session);
        return $subscription && $subscription->getNewSubscriptionSessionId() !== null;
    }

    /**
     * Finds the user's subscription in the specified session.
     */
    private function findUserSubscriptionInSession($user, $session)
    {
        return $this->entityManager->getRepository(SessionRelUser::class)
            ->findOneBy([
                'user' => $user,
                'session' => $session,
            ]);
    }

    /**
     * Finds a valid session within the session hierarchy.
     */
    private function findValidSessionInHierarchy(Session $session): ?Session
    {
        $childSessions = $this->sessionRepository->findChildSessions($session);

        /* @var Session $child */
        foreach ($childSessions as $child) {
            $validUntil = (clone $child->getAccessEndDate())->modify("-{$child->getDaysToReinscription()} days");
            if (new \DateTime() <= $validUntil) {
                return $child;
            }
        }

        $parentSession = $this->sessionRepository->findParentSession($session);

        if ($parentSession && new \DateTime() <= $parentSession->getAccessEndDate()) {
            return $parentSession;
        }

        return null;
    }
}
