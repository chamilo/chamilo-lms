<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Command;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Repository\SessionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\Translation\TranslatorInterface;

class SessionRepetitionCommand extends Command
{
    protected static $defaultName = 'app:session-repetition';

    private SessionRepository $sessionRepository;
    private EntityManagerInterface $entityManager;
    private MailerInterface $mailer;
    private TranslatorInterface $translator;

    public function __construct(
        SessionRepository $sessionRepository,
        EntityManagerInterface $entityManager,
        MailerInterface $mailer,
        TranslatorInterface $translator
    ) {
        parent::__construct();
        $this->sessionRepository = $sessionRepository;
        $this->entityManager = $entityManager;
        $this->mailer = $mailer;
        $this->translator = $translator;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Automatically duplicates sessions that meet the repetition criteria.')
            ->addOption('debug', null, InputOption::VALUE_NONE, 'Enable debug mode');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $debug = $input->getOption('debug');

        // Find sessions that meet the repetition criteria
        $sessions = $this->sessionRepository->findSessionsWithoutChildAndReadyForRepetition();

        if ($debug) {
            $output->writeln(sprintf('Found %d session(s) ready for repetition.', count($sessions)));
        }

        foreach ($sessions as $session) {
            if ($debug) {
                $output->writeln(sprintf('Processing session: %d', $session->getId()));
            }

            // Duplicate session
            $newSession = $this->duplicateSession($session, $debug, $output);

            // Notify general coach of the new session
            $this->notifyGeneralCoach($newSession, $debug, $output);

            $output->writeln('Created new session: ' . $newSession->getId() . ' from session: ' . $session->getId());
        }

        return Command::SUCCESS;
    }

    /**
     * Duplicates a session and creates a new session with adjusted dates.
     */
    private function duplicateSession(Session $session, bool $debug, OutputInterface $output): Session
    {
        // Calculate new session dates based on the duration of the original session
        $duration = $session->getAccessEndDate()->diff($session->getAccessStartDate());
        $newStartDate = (clone $session->getAccessEndDate())->modify('+1 day');
        $newEndDate = (clone $newStartDate)->add($duration);

        if ($debug) {
            $output->writeln(sprintf(
                'Duplicating session %d. New start date: %s, New end date: %s',
                $session->getId(),
                $newStartDate->format('Y-m-d H:i:s'),
                $newEndDate->format('Y-m-d H:i:s')
            ));
        }

        // Create a new session with the same details as the original session
        $newSession = new Session();
        $newSession
            ->setTitle($session->getTitle() . ' (Repetition ' . $session->getId() . ' - ' . time() . ')')
            ->setAccessStartDate($newStartDate)
            ->setAccessEndDate($newEndDate)
            ->setDisplayStartDate($newStartDate)
            ->setDisplayEndDate($newEndDate)
            ->setCoachAccessStartDate($newStartDate)
            ->setCoachAccessEndDate($newEndDate)
            ->setVisibility($session->getVisibility())
            ->setDuration($session->getDuration())
            ->setDescription($session->getDescription() ?? '')
            ->setShowDescription($session->getShowDescription() ?? false)
            ->setCategory($session->getCategory())
            ->setPromotion($session->getPromotion())
            ->setDaysToReinscription($session->getDaysToReinscription())
            ->setDaysToNewRepetition($session->getDaysToNewRepetition())
            ->setParentId($session->getId())
            ->setLastRepetition(false);

        // Copy the AccessUrls from the original session
        foreach ($session->getUrls() as $accessUrl) {
            $newSession->addAccessUrl($accessUrl->getUrl());
        }

        // Copy the courses from the original session
        foreach ($session->getCourses() as $course) {
            $newSession->addCourse($course);
        }

        // Copy the general coaches from the original session
        foreach ($session->getGeneralCoaches() as $coach) {
            $newSession->addGeneralCoach($coach);
        }

        // Save the new session
        $this->entityManager->persist($newSession);
        $this->entityManager->flush();

        if ($debug) {
            $output->writeln(sprintf('New session %d created successfully.', $newSession->getId()));
        }

        return $newSession;
    }

    /**
     * Retrieves or creates a default AccessUrl for sessions.
     */
    private function getDefaultAccessUrl()
    {
        return $this->entityManager->getRepository(AccessUrl::class)->findOneBy([]);
    }


    /**
     * Notifies the general coach of the session about the new repetition.
     */
    private function notifyGeneralCoach(Session $newSession, bool $debug, OutputInterface $output): void
    {
        $generalCoach = $newSession->getGeneralCoaches()->first();
        if ($generalCoach) {
            $message = sprintf(
                'A new repetition of the session "%s" has been created. Please review the details: %s',
                $newSession->getTitle(),
                $this->generateSessionSummaryLink($newSession)
            );

            if ($debug) {
                $output->writeln(sprintf('Notifying coach (ID: %d) for session %d', $generalCoach->getId(), $newSession->getId()));
            }

            // Send message to the general coach
            $this->sendMessage($generalCoach->getEmail(), $message);

            if ($debug) {
                $output->writeln('Notification sent.');
            }
        } else {
            if ($debug) {
                $output->writeln('No general coach found for session ' . $newSession->getId());
            }
        }
    }

    /**
     * Sends an email message to a user.
     */
    private function sendMessage(string $recipientEmail, string $message): void
    {
        $subject = $this->translator->trans('New Session Repetition Created');

        $email = (new Email())
            ->from('no-reply@yourdomain.com')
            ->to($recipientEmail)
            ->subject($subject)
            ->html('<p>' . $message . '</p>');

        $this->mailer->send($email);
    }

    /**
     * Generates a link to the session summary page.
     */
    private function generateSessionSummaryLink(Session $session): string
    {
        return '/main/session/resume_session.php?id_session=' . $session->getId();
    }
}
