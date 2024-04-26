<?php

/* For licensing terms, see /license.txt */

/**
 * This script send notification messages to users that have reminders from an event in their agenda.
 */

use Chamilo\CoreBundle\Entity\AgendaReminder;
use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Entity\SessionRelCourseRelUser;
use Chamilo\CourseBundle\Entity\CCalendarEvent;

require_once __DIR__.'/../../main/inc/global.inc.php';

if ('cli' != php_sapi_name()) {
    exit; //do not run from browser
}

$batchCounter = 0;
$batchSize = 100;

$now = new DateTime('now', new DateTimeZone('UTC'));

$em = Database::getManager();
$remindersRepo = $em->getRepository(AgendaReminder::class);

/** @var array<AgendaReminder> $reminders */
$reminders = $remindersRepo->findBy(['sent' => false]);

$senderId = (int) api_get_setting('agenda.agenda_reminders_sender_id');

if (empty($senderId)) {
    $firstAdmin = current(UserManager::get_all_administrators());
    $senderId = $firstAdmin['user_id'];
}

foreach ($reminders as $reminder) {
    $event = $reminder->getEvent();

    if (null === $event) {
        continue;
    }

    $notificationDate = clone $event->getStartDate();
    $notificationDate->sub($reminder->getDateInterval());

    if ($notificationDate > $now) {
        continue;
    }

    if ('course' !== $event->determineType()) {
        $eventDetails = [];
        $eventDetails[] = '<p><strong>'.$event->getTitle().'</strong></p>';

        if ($event->isAllDay()) {
            $eventDetails[] = '<p class="small">'.get_lang('All day').'</p>';
        } else {
            $eventDetails[] = sprintf(
                '<p class="small">'.get_lang('From %s').'</p>',
                api_get_local_time($event->getStartDate(), null, null, false, true, true)
            );

            if (!empty($event->getEnddate())) {
                $eventDetails[] = sprintf(
                    '<p class="small">'.get_lang('Until %s').'</p>',
                    api_get_local_time($event->getEnddate(), null, null, false, true, true)
                );
            }
        }

        if (!empty($event->getContent())) {
            $eventDetails[] = $event->getContent();
        }

        $messageSubject = sprintf(get_lang('Reminder for event : %s'), $event->getTitle());
        $messageContent = implode(PHP_EOL, $eventDetails);

        MessageManager::send_message_simple(
            $event->getResourceNode()->getCreator()->getId(),
            $messageSubject,
            $messageContent,
            $event->getResourceNode()->getCreator()->getId()
        );

        $getInviteesForEvent = function (?CCalendarEvent $event) use ($em) {
            if (!$event) {
                return [];
            }

            $resourceLinks = $event->getResourceNode()->getResourceLinks();
            $inviteeList = [];
            foreach ($resourceLinks as $resourceLink) {
                $user = $resourceLink->getUser();
                if ($user) {
                    $inviteeList[] = [
                        'id' => $user->getId(),
                        'name' => $user->getFullname(),
                    ];
                }
            }

            return $inviteeList;
        };

        $invitees = $getInviteesForEvent($reminder->getEvent());
        $inviteesIdList = array_column($invitees, 'id');
        foreach ($inviteesIdList as $userId) {
            MessageManager::send_message_simple(
                $userId,
                $messageSubject,
                $messageContent,
                $event->getResourceNode()->getCreator()->getId()
            );
        }
    } else {
        $eventDetails = [
            sprintf('<p><strong>%s</strong></p>', $event->getTitle()),
            $event->isAllDay() ? '<p class="small">All Day</p>' : sprintf(
                '<p class="small">From %s</p>',
                $event->getStartDate()->format('Y-m-d H:i:s')
            )
        ];

        if ($event->getEndDate()) {
            $eventDetails[] = sprintf(
                '<p class="small">Until %s</p>',
                $event->getEndDate()->format('Y-m-d H:i:s')
            );
        }

        if ($event->getContent()) {
            $eventDetails[] = $event->getContent();
        }

        if ($event->getComment()) {
            $eventDetails[] = sprintf('<p class="small">%s</p>', $event->getComment());
        }

        $messageSubject = sprintf('Reminder: %s', $event->getTitle());
        $messageContent = implode(PHP_EOL, $eventDetails);

        $resourceLinks = $event->getResourceNode()->getResourceLinks();
        $userIdList = [];
        $groupUserIdList = [];

        foreach ($resourceLinks as $resourceLink) {
            if ($resourceLink->getUser()) {
                $userIdList[] = $resourceLink->getUser()->getId();
            } elseif ($resourceLink->getGroup()) {
                $groupUsers = GroupManager::get_users(
                    $resourceLink->getGroup()->getIid(),
                    false,
                    null,
                    null,
                    false,
                    $resourceLink->getCourse()?->getId()
                );
                foreach ($groupUsers as $groupUserId) {
                    $groupUserIdList[] = $groupUserId;
                }
            } else {
                $course = $resourceLink->getCourse();

                if ($session = $resourceLink->getSession()) {
                    $userSubscriptions = $session->getSessionRelCourseRelUserInCourse($course)->getValues();

                    $userIdList = array_map(
                        fn(SessionRelCourseRelUser $sessionCourseUserSubscription) => $sessionCourseUserSubscription->getUser()->getId(),
                        $userSubscriptions
                    );
                } else {
                    $userSubscriptions = $course->getUsers()->getValues();

                    $userIdList = array_map(
                        fn(CourseRelUser $courseUserSubscription) => $courseUserSubscription->getUser()->getId(),
                        $userSubscriptions
                    );
                }
            }
        }

        $userIdList = array_unique($userIdList);
        $groupUserIdList = array_unique($groupUserIdList);

        foreach ($userIdList as $userId) {
            MessageManager::send_message_simple(
                $userId,
                $messageSubject,
                $messageContent,
                $senderId
            );
        }

        foreach ($groupUserIdList as $groupUserId) {
            MessageManager::send_message_simple(
                $groupUserId,
                $messageSubject,
                $messageContent,
                $senderId
            );
        }
    }

    $reminder->setSent(true);

    $batchCounter++;

    if (($batchCounter % $batchSize) === 0) {
        $em->flush();
    }
}

$em->flush();
$em->clear();
