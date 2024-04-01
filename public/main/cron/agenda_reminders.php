<?php

/* For licensing terms, see /license.txt */

/**
 * This script send notification messages to users that have reminders from an event in their agenda.
 */
require_once __DIR__.'/../../main/inc/global.inc.php';

exit;

$batchCounter = 0;
$batchSize = 100;

$now = new DateTime('now', new DateTimeZone('UTC'));

$em = Database::getManager();
$remindersRepo = $em->getRepository(\Chamilo\CoreBundle\Entity\AgendaReminder::class);

$reminders = $remindersRepo->findBy(['sent' => false]);

$senderId = (int) api_get_setting('agenda.agenda_reminders_sender_id');

if (empty($senderId)) {
    $firstAdmin = current(UserManager::get_all_administrators());
    $senderId = $firstAdmin['user_id'];
}

foreach ($reminders as $reminder) {

    if ('personal' === $reminder->getType()) {
        $event = $em->getRepository(\Chamilo\CourseBundle\Entity\CCalendarEvent::class)->find($reminder->getEventId());

        if (null === $event) {
            continue;
        }

        $notificationDate = clone $event->getStartDate();
        $notificationDate->sub($reminder->getDateInterval());

        if ($notificationDate > $now) {
            continue;
        }

        $eventDetails = [];
        $eventDetails[] = '<p><strong>'.$event->getTitle().'</strong></p>';

        if ($event->isAllDay()) {
            $eventDetails[] = '<p class="small">'.get_lang('AllDay').'</p>';
        } else {
            $eventDetails[] = sprintf(
                '<p class="small">'.get_lang('FromDateX').'</p>',
                api_get_local_time($event->getStartDate(), null, null, false, true, true)
            );

            if (!empty($event->getEnddate())) {
                $eventDetails[] = sprintf(
                    '<p class="small">'.get_lang('UntilDateX').'</p>',
                    api_get_local_time($event->getEnddate(), null, null, false, true, true)
                );
            }
        }

        if (!empty($event->getContent())) {
            $eventDetails[] = $event->getContent();
        }

        $messageSubject = sprintf(get_lang('ReminderXEvent'), $event->getTitle());
        $messageContent = implode(PHP_EOL, $eventDetails);

        MessageManager::send_message_simple(
            $event->getResourceNode()->getCreator()->getId(),
            $messageSubject,
            $messageContent,
            $event->getResourceNode()->getCreator()->getId()
        );


        $getInviteesForEvent = function ($eventId) use ($em) {
            $event = $em->find(\Chamilo\CourseBundle\Entity\CCalendarEvent::class, $eventId);
            if (!$event) {
                return [];
            }

            $resourceLinks = $event->getResourceLinkEntityList();
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

        $invitees = $getInviteesForEvent($reminder->getEventId());
        $inviteesIdList = array_column($invitees, 'id');
        foreach ($inviteesIdList as $userId) {
            MessageManager::send_message_simple(
                $userId,
                $messageSubject,
                $messageContent,
                $event->getResourceNode()->getCreator()->getId()
            );
        }
    }

    if ('course' === $reminder->getType()) {

        $event = $em->getRepository(\Chamilo\CourseBundle\Entity\CCalendarEvent::class)->find($reminder->getEventId());
        if (null === $event) {
            continue;
        }

        $notificationDate = clone $event->getStartDate();
        $notificationDate->sub($reminder->getDateInterval());

        if ($notificationDate > $now) {
            continue;
        }

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
                $groupUsers = GroupManager::get_users($resourceLink->getGroup()->getId(), false, null, null, false, $event->getSessionId());
                foreach ($groupUsers as $groupUserId) {
                    $groupUserIdList[] = $groupUserId;
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

    $em->persist($reminder);

    $batchCounter++;

    if (($batchCounter % $batchSize) === 0) {
        $em->flush();
    }
}

$em->flush();
$em->clear();
