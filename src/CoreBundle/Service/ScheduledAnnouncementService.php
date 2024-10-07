<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Service;

use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\Node\AccessUrlRepository;
use Chamilo\CoreBundle\Repository\ScheduledAnnouncementRepository;
use Chamilo\CoreBundle\Repository\SessionRepository;
use Chamilo\CoreBundle\ServiceHelper\MessageHelper;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CoreBundle\Entity\Course;
use Doctrine\ORM\EntityManager;
use ExtraField;
use ExtraFieldValue;
use Symfony\Contracts\Translation\TranslatorInterface;
use Tracking;

class ScheduledAnnouncementService
{
    public function __construct(
        private readonly ScheduledAnnouncementRepository $announcementRepository,
        private readonly AccessUrlRepository $accessUrlRepository,
        private readonly EntityManager $em,
        private readonly SettingsManager $settingsManager,
        private readonly SessionRepository $sessionRepository,
        private readonly MessageHelper $messageHelper,
        private readonly TranslatorInterface $translator
    ) {}

    /**
     * Sends pending announcements to users and coaches.
     */
    public function sendPendingMessages(int $urlId, bool $debug = false): int
    {
        if (!$this->allowed()) {
            if ($debug) {
                error_log("Announcements not allowed.");
            }
            return 0;
        }

        $messagesSent = 0;
        $now = new \DateTime();

        if ($debug) {
            error_log("Current time: " . $now->format('Y-m-d H:i:s'));
        }

        $announcements = $this->announcementRepository->findBy(['sent' => false]);

        if ($debug) {
            error_log(count($announcements) . " pending announcements found.");
        }

        $extraField = new ExtraField('user');
        $extraFields = $extraField->get_all(['filter = ? AND visible_to_self = ?' => [1, 1]]);

        foreach ($announcements as $announcement) {
            if (!$announcement->isSent() && $announcement->getDate() < $now) {
                if ($debug) {
                    error_log("Processing announcement ID: " . $announcement->getId());
                }

                $sessionId = $announcement->getSessionId();
                $session = $this->sessionRepository->find($sessionId);

                if (!$session) {
                    if ($debug) {
                        error_log("Session not found for session ID: $sessionId");
                    }
                    continue;
                }

                $accessUrl = $this->accessUrlRepository->find($urlId);
                $sessionRelUsers = $this->sessionRepository->getUsersByAccessUrl($session, $accessUrl);
                $generalCoaches = $session->getGeneralCoaches();

                if (empty($sessionRelUsers) || $generalCoaches->count() === 0) {
                    if ($debug) {
                        error_log("No users or general coaches found for session ID: $sessionId");
                    }
                    continue;
                }

                $coachId = $generalCoaches->first()->getId();
                if ($debug) {
                    error_log("Coach ID: $coachId");
                }

                $coachList = [];

                $sendToCoaches = $this->shouldSendToCoaches($announcement->getId());

                $courseList = $session->getCourses();

                if ($debug) {
                    error_log("Number of courses in session: " . count($courseList));
                    foreach ($courseList as $sessionRelCourse) {
                        $course = $sessionRelCourse->getCourse();
                        error_log("Course ID: " . $course->getId() . ", Course Title: " . $course->getTitle());
                    }
                }

                if ($sendToCoaches) {
                    foreach ($courseList as $course) {
                        $coaches = $session->getGeneralCoaches();
                        $coachList = array_merge($coachList, $coaches->toArray());
                    }
                    $coachList = array_unique($coachList);
                }

                $announcement->setSent(true);
                $this->em->persist($announcement);
                $this->em->flush();

                $attachments = '';
                $subject = $announcement->getSubject();

                foreach ($sessionRelUsers as $sessionRelUser) {
                    $user = $sessionRelUser->getUser();

                    if ($debug) {
                        error_log('User ::: ' . $user->getId());
                    }

                    if ($user->getId() !== $coachId) {
                        $courseInfo = $this->getCourseInfo($courseList);
                        $progress = $this->getUserProgress($user->getId(), $courseInfo, $session);

                        $message = $this->buildMessage(
                            $announcement,
                            $session,
                            $user,
                            $courseInfo,
                            $attachments,
                            $progress
                        );

                        if (!empty($extraFields)) {
                            $extraFieldValue = new ExtraFieldValue('user');
                            foreach ($extraFields as $extraField) {
                                $valueExtra = $extraFieldValue->get_values_by_handler_and_field_variable($user->getId(), $extraField['variable'], true);
                                $tags['(('.strtolower($extraField['variable']).'))'] = $valueExtra['value'] ?? '';
                            }
                            $message = str_replace(array_keys($tags), $tags, $message);
                        }

                        if ($debug) {
                            error_log("Sending email to user ID: " . $user->getId());
                        }

                        $this->sendEmail($user->getId(), $subject, $message, $coachId);
                    }
                }

                $coachMessage = $this->buildCoachMessage($announcement, $generalCoaches, $message);
                foreach ($coachList as $courseCoach) {
                    if ($debug) {
                        error_log("Sending email to coach ID: " . $courseCoach->getId());
                    }
                    $this->sendEmail($courseCoach->getId(), $subject, $coachMessage, $coachId);
                }

                $messagesSent++;

                if ($debug) {
                    error_log("Messages sent for announcement ID: " . $announcement->getId());
                }
            }
        }

        if ($debug) {
            error_log("Total messages sent: $messagesSent");
        }

        return $messagesSent;
    }

    /**
     * Checks if the announcement should be sent to coaches.
     */
    private function shouldSendToCoaches(int $announcementId): bool
    {
        $extraFieldValue = new ExtraFieldValue('scheduled_announcement');
        $sendToCoaches = $extraFieldValue->get_values_by_handler_and_field_variable($announcementId, 'send_to_coaches');
        return !empty($sendToCoaches) && !empty($sendToCoaches['value']) && (int)$sendToCoaches['value'] === 1;
    }

    /**
     * Verifies if sending scheduled announcements is allowed.
     */
    private function allowed(): bool
    {
        return 'true' === $this->settingsManager->getSetting('announcement.allow_scheduled_announcements');
    }

    /**
     * Builds the announcement message for the user.
     */
    private function buildMessage($announcement, Session $session, User $user, $courseInfo, $attachments, $progress): string
    {
        $generalCoaches = $session->getGeneralCoaches();
        $generalCoachName = [];
        $generalCoachEmail = [];

        foreach ($generalCoaches as $coach) {
            $generalCoachName[] = $coach->getFullname();
            $generalCoachEmail[] = $coach->getEmail();
        }

        $startTime = $this->getLocalTime($session->getAccessStartDate());
        $endTime = $this->getLocalTime($session->getAccessEndDate());

        $tags = [
            '((session_name))' => $session->getTitle(),
            '((session_start_date))' => $startTime,
            '((general_coach))' => implode(' - ', $generalCoachName),
            '((general_coach_email))' => implode(' - ', $generalCoachEmail),
            '((session_end_date))' => $endTime,
            '((user_complete_name))' => $user->getFirstname() . ' ' . $user->getLastname(),
            '((user_firstname))' => $user->getFirstname(),
            '((user_lastname))' => $user->getLastname(),
            '((lp_progress))' => $progress,
        ];

        $message = str_replace(array_keys($tags), $tags, $announcement->getMessage());

        return $message . $attachments;
    }

    /**
     * Builds the announcement message for the coach.
     */
    private function buildCoachMessage($announcement, $generalCoaches, $message): string
    {
        $coachNames = [];
        foreach ($generalCoaches as $coach) {
            $coachNames[] = $coach->getFullname();
        }

        $coachMessageIntro = count($generalCoaches) > 1
            ? $this->translator->trans('You are receiving a copy because you are one of the course coaches')
            : $this->translator->trans('You are receiving a copy because you are the course coach');

        $coachMessage = $coachMessageIntro . ': ' . implode(', ', $coachNames);

        return $coachMessage . '<br /><br />' . $message;
    }

    /**
     * Sends an email with the announcement to a user or coach.
     */
    private function sendEmail(int $userId, string $subject, string $message, int $coachId): void
    {
        $user = $this->em->getRepository(User::class)->find($userId);
        $coach = $this->em->getRepository(User::class)->find($coachId);

        if (!$user || !$coach) {
            throw new \Exception("User or coach not found.");
        }

        $this->messageHelper->sendMessageSimple(
            $userId,
            $subject,
            $message,
            $coachId
        );
    }

    /**
     * Retrieves course information from a list of courses.
     */
    private function getCourseInfo($courseList)
    {
        if (!empty($courseList) && current($courseList) instanceof Course) {
            $courseId = current($courseList)->getId();
            return $this->getCourseInfoById($courseId);
        } else {
            return [];
        }
    }

    /**
     * Retrieves course information by course ID.
     */
    private function getCourseInfoById(int $courseId)
    {
        $course = $this->em->getRepository(Course::class)->find($courseId);

        if ($course) {
            return [
                'real_id' => $course->getId(),
                'title' => $course->getTitle(),
                'code' => $course->getCode(),
                'description' => $course->getDescription(),
                'tutor_name' => $course->getTutorName(),
            ];
        }

        return [];
    }

    /**
     * Gets the user's progress for a specific course and session.
     */
    private function getUserProgress(int $userId, $courseInfo, Session $session): string
    {
        if (!empty($courseInfo) && $session) {
            $progress = Tracking::get_avg_student_progress($userId, $courseInfo['real_id'], [], $session->getId());
            return is_numeric($progress) ? $progress . '%' : '0%';
        }
        return '0%';
    }

    /**
     * Formats a DateTime object to a string.
     */
    private function getLocalTime(?\DateTime $datetime): string
    {
        return $datetime ? $datetime->format('Y-m-d H:i:s') : '';
    }
}
