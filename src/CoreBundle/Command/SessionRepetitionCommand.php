<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Command;

use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CoreBundle\Entity\GradebookEvaluation;
use Chamilo\CoreBundle\Entity\GradebookLink;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\SessionRepository;
use Chamilo\CoreBundle\ServiceHelper\MessageHelper;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class SessionRepetitionCommand extends Command
{
    /**
     * @var string
     */
    protected static $defaultName = 'app:session-repetition';
    private string $baseUrl;

    public function __construct(
        private readonly SessionRepository $sessionRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly TranslatorInterface $translator,
        private readonly MessageHelper $messageHelper
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Automatically duplicates sessions that meet the repetition criteria.')
            ->addOption('debug', null, InputOption::VALUE_NONE, 'Enable debug mode')
            ->addOption('base-url', null, InputOption::VALUE_REQUIRED, 'Base URL for generating session links')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $debug = $input->getOption('debug');
        $this->baseUrl = $input->getOption('base-url');

        if (!$this->baseUrl) {
            $output->writeln('<error>Error: You must provide --base-url</error>');

            return Command::FAILURE;
        }

        if ($debug) {
            $output->writeln('<info>Debug mode enabled</info>');
        }

        // Find sessions that meet the repetition criteria
        $sessions = $this->sessionRepository->findSessionsWithoutChildAndReadyForRepetition();

        if ($debug) {
            $output->writeln(\sprintf('Found %d session(s) ready for repetition.', \count($sessions)));
        }

        foreach ($sessions as $session) {
            if ($debug) {
                $output->writeln(\sprintf('Processing session: %d', $session->getId()));
            }

            // Duplicate session
            $newSession = $this->duplicateSession($session, $debug, $output);

            // Notify general coach of the new session
            $this->notifyGeneralCoach($newSession, $debug, $output);

            $output->writeln('Created new session: '.$newSession->getId().' from session: '.$session->getId());
        }

        return Command::SUCCESS;
    }

    /**
     * Duplicates a session and creates a new session with adjusted dates.
     *
     * @throws Exception
     */
    private function duplicateSession(Session $session, bool $debug, OutputInterface $output): Session
    {
        // Calculate new session dates based on the duration of the original session
        $duration = $session->getAccessEndDate()->diff($session->getAccessStartDate())->days;
        $newStartDate = (clone $session->getAccessEndDate())->modify('+1 day');
        $newEndDate = (clone $newStartDate)->modify("+{$duration} days");

        if ($debug) {
            $output->writeln(\sprintf(
                'Duplicating session %d. New start date: %s, New end date: %s',
                $session->getId(),
                $newStartDate->format('Y-m-d H:i:s'),
                $newEndDate->format('Y-m-d H:i:s')
            ));
        }

        // Create a new session with the same details as the original session
        $newSession = new Session();
        $newSession
            ->setTitle($session->getTitle().' (Repetition '.$session->getId().' - '.time().')')
            ->setAccessStartDate($newStartDate)
            ->setAccessEndDate($newEndDate)
            ->setDisplayStartDate($newStartDate)
            ->setDisplayEndDate($newEndDate)
            ->setCoachAccessStartDate($newStartDate)
            ->setCoachAccessEndDate($newEndDate)
            ->setVisibility($session->getVisibility())
            ->setDuration(0)
            ->setDescription($session->getDescription() ?? '')
            ->setShowDescription($session->getShowDescription() ?? false)
            ->setCategory($session->getCategory())
            ->setPromotion($session->getPromotion())
            ->setDaysToReinscription($session->getDaysToReinscription())
            ->setDaysToNewRepetition($session->getDaysToNewRepetition())
            ->setParentId($session->getId())
            ->setLastRepetition(false)
        ;

        // Copy the AccessUrls from the original session
        foreach ($session->getUrls() as $accessUrl) {
            $newSession->addAccessUrl($accessUrl->getUrl());
        }

        // Save the new session
        $this->entityManager->persist($newSession);
        $this->entityManager->flush();

        if ($debug) {
            $output->writeln(\sprintf('New session %d created successfully.', $newSession->getId()));
        }

        $courses = $session->getCourses()->toArray();

        if ($debug) {
            $output->writeln('Courses retrieved: '.\count($courses));
            foreach ($courses as $index => $sessionRelCourse) {
                $course = $sessionRelCourse->getCourse();
                $output->writeln(\sprintf(
                    'Course #%d: %s (Course ID: %s)',
                    $index + 1,
                    $course ? $course->getTitle() : 'NULL',
                    $course ? $course->getId() : 'NULL'
                ));
            }
        }

        // Extract course IDs
        $courseList = array_map(function ($sessionRelCourse) {
            $course = $sessionRelCourse->getCourse();

            return $course?->getId();
        }, $courses);

        // Remove null values
        $courseList = array_filter($courseList);

        if ($debug) {
            $output->writeln(\sprintf(
                'Extracted course IDs: %s',
                json_encode($courseList)
            ));
        }

        if (empty($courseList)) {
            $output->writeln(\sprintf('Warning: No courses found in the original session %d.', $session->getId()));
        }

        // Add courses to the new session
        $courseCount = 0;
        foreach ($courses as $sessionRelCourse) {
            $course = $sessionRelCourse->getCourse();
            if ($course) {
                $newSession->addCourse($course);
                $this->entityManager->persist($newSession);

                if ($debug) {
                    $output->writeln(\sprintf('Added course ID %d to session ID %d.', $course->getId(), $newSession->getId()));
                }

                $this->copyEvaluationsAndCategories($course->getId(), $session->getId(), $newSession->getId(), $debug, $output);

                $courseCount++;
            }
        }

        foreach ($session->getGeneralCoaches() as $coach) {
            $newSession->addGeneralCoach($coach);
        }

        $newSession->setNbrCourses($courseCount);
        $this->entityManager->persist($newSession);

        $this->entityManager->flush();

        return $newSession;
    }

    /**
     * Notifies the general coach of the session about the new repetition.
     */
    private function notifyGeneralCoach(Session $newSession, bool $debug, OutputInterface $output): void
    {
        $generalCoach = $newSession->getGeneralCoaches()->first();
        if ($generalCoach) {
            $messageSubject = $this->translator->trans('New Session Repetition Created');
            $messageContent = \sprintf(
                'A new repetition of the session "%s" has been created. Please review the details: %s',
                $newSession->getTitle(),
                $this->generateSessionSummaryLink($newSession)
            );

            if ($debug) {
                $output->writeln(\sprintf('Notifying coach (ID: %d) for session %d', $generalCoach->getId(), $newSession->getId()));
            }

            $senderId = $this->getFirstAdminId();
            $this->messageHelper->sendMessageSimple(
                $generalCoach->getId(),
                $messageSubject,
                $messageContent,
                $senderId
            );

            if ($debug) {
                $output->writeln('Notification sent using MessageHelper.');
            }
        } elseif ($debug) {
            $output->writeln('No general coach found for session '.$newSession->getId());
        }
    }

    private function getFirstAdminId(): int
    {
        $admin = $this->entityManager->getRepository(User::class)->findOneBy([]);

        return $admin && ($admin->hasRole('ROLE_ADMIN') || $admin->hasRole('ROLE_SUPER_ADMIN'))
            ? $admin->getId()
            : 1;
    }

    /**
     * Generates a link to the session summary page.
     */
    private function generateSessionSummaryLink(Session $session): string
    {
        return \sprintf('%s/main/session/resume_session.php?id_session=%d', $this->baseUrl, $session->getId());
    }

    /**
     * Copies gradebook categories, evaluations, and links from the old session to the new session.
     */
    private function copyEvaluationsAndCategories(
        int $courseId,
        int $oldSessionId,
        int $newSessionId,
        bool $debug,
        OutputInterface $output
    ): void {
        // Get existing categories of the original course and session
        $categories = $this->entityManager->getRepository(GradebookCategory::class)
            ->findBy(['course' => $courseId, 'session' => $oldSessionId])
        ;

        if ($debug) {
            $output->writeln(\sprintf('Found %d category(ies) for course ID %d in session ID %d.', \count($categories), $courseId, $oldSessionId));
        }

        foreach ($categories as $category) {
            // Create new category for the new session
            $newCategory = new GradebookCategory();
            $newCategory->setTitle($category->getTitle())
                ->setDescription($category->getDescription())
                ->setWeight($category->getWeight())
                ->setVisible($category->getVisible())
                ->setCertifMinScore($category->getCertifMinScore())
                ->setGenerateCertificates($category->getGenerateCertificates())
                ->setIsRequirement($category->getIsRequirement())
                ->setCourse($category->getCourse())
                ->setSession($this->entityManager->getReference(Session::class, $newSessionId))
                ->setParent($category->getParent())
            ;

            $this->entityManager->persist($newCategory);
            $this->entityManager->flush();

            if ($debug) {
                $output->writeln(\sprintf('Created new category ID %d for session ID %d.', $newCategory->getId(), $newSessionId));
            }

            // Copy links
            $links = $this->entityManager->getRepository(GradebookLink::class)
                ->findBy(['category' => $category->getId()])
            ;

            foreach ($links as $link) {
                $newLink = clone $link;
                $newLink->setCategory($newCategory);
                $this->entityManager->persist($newLink);
            }

            // Copy evaluations
            $evaluations = $this->entityManager->getRepository(GradebookEvaluation::class)
                ->findBy(['category' => $category->getId()])
            ;

            foreach ($evaluations as $evaluation) {
                $newEvaluation = clone $evaluation;
                $newEvaluation->setCategory($newCategory);
                $this->entityManager->persist($newEvaluation);
            }

            $this->entityManager->flush();

            if ($debug) {
                $output->writeln(\sprintf('Copied links and evaluations for category ID %d to new category ID %d.', $category->getId(), $newCategory->getId()));
            }
        }
    }
}
