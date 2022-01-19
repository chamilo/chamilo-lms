<?php

/* For licensing terms, see /license.txt */

/**
 * This script send notification messages to users that have reminders from an event in their agenda.
 */
require_once __DIR__.'/../../main/inc/global.inc.php';

exit;

if (false === api_get_configuration_value('agenda_reminders')) {
    exit;
}

$batchCounter = 0;
$batchSize = 100;

$agendaCollectiveInvitations = api_get_configuration_value('agenda_collective_invitations');

$now = new DateTime('now', new DateTimeZone('UTC'));

$em = Database::getManager();
$remindersRepo = $em->getRepository('ChamiloCoreBundle:AgendaReminder');

$reminders = $remindersRepo->findBySent(false);

$senderId = api_get_configuration_value('agenda_reminders_sender_id');

if (empty($senderId)) {
    $firstAdmin = current(UserManager::get_all_administrators());
    $senderId = $firstAdmin['user_id'];
}

foreach ($reminders as $reminder) {
    if ('personal' === $reminder->getType()) {
        $event = $em->find('ChamiloCoreBundle:PersonalAgenda', $reminder->getEventId());

        if (null === $event) {
            continue;
        }

        $notificationDate = clone $event->getDate();
        $notificationDate->sub($reminder->getDateInterval());

        if ($notificationDate > $now) {
            continue;
        }

        $eventDetails = [];
        $eventDetails[] = '<p><strong>'.$event->getTitle().'</strong></p>';

        if ($event->getAllDay()) {
            $eventDetails[] = '<p class="small">'.get_lang('AllDay').'</p>';
        } else {
            $eventDetails[] = sprintf(
                '<p class="small">'.get_lang('FromDateX').'</p>',
                api_get_local_time($event->getDate(), null, null, false, true, true)
            );

            if (!empty($event->getEnddate())) {
                $eventDetails[] = sprintf(
                    '<p class="small">'.get_lang('UntilDateX').'</p>',
                    api_get_local_time($event->getEnddate(), null, null, false, true, true)
                );
            }
        }

        if (!empty($event->getText())) {
            $eventDetails[] = $event->getText();
        }

        $messageSubject = sprintf(get_lang('ReminderXEvent'), $event->getTitle());
        $messageContent = implode(PHP_EOL, $eventDetails);

        MessageManager::send_message_simple(
            $event->getUser(),
            $messageSubject,
            $messageContent,
            $event->getUser()
        );

        if ($agendaCollectiveInvitations) {
            $invitees = Agenda::getInviteesForPersonalEvent($reminder->getEventId());
            $inviteesIdList = array_column($invitees, 'id');

            foreach ($inviteesIdList as $userId) {
                MessageManager::send_message_simple(
                    $userId,
                    $messageSubject,
                    $messageContent,
                    $event->getUser()
                );
            }
        }
    }

    if ('course' === $reminder->getType()) {
        $event = $em->find('ChamiloCourseBundle:CCalendarEvent', $reminder->getEventId());

        if (null === $event) {
            continue;
        }

        $agenda = new Agenda('course');

        $notificationDate = clone $event->getStartDate();
        $notificationDate->sub($reminder->getDateInterval());

        if ($notificationDate > $now) {
            continue;
        }

        $eventDetails = [];
        $eventDetails[] = '<p><strong>'.$event->getTitle().'</strong></p>';

        if ($event->getAllDay()) {
            $eventDetails[] = '<p class="small">'.get_lang('AllDay').'</p>';
        } else {
            $eventDetails[] = sprintf(
                '<p class="small">'.get_lang('FromDateX').'</p>',
                api_get_local_time($event->getStartDate(), null, null, false, true, true)
            );

            if (!empty($event->getEndDate())) {
                $eventDetails[] = sprintf(
                    '<p class="small">'.get_lang('UntilDateX').'</p>',
                    api_get_local_time($event->getEndDate(), null, null, false, true, true)
                );
            }
        }

        if (!empty($event->getContent())) {
            $eventDetails[] = $event->getContent();
        }

        if (!empty($event->getComment())) {
            $eventDetails[] = '<p class="small">'.$event->getComment().'</p>';
        }

        $messageSubject = sprintf(get_lang('ReminderXEvent'), $event->getTitle());
        $messageContent = implode(PHP_EOL, $eventDetails);

        $courseInfo = api_get_course_info_by_id($event->getCId());

        $sendTo = $agenda->getUsersAndGroupSubscribedToEvent(
            $event->getIid(),
            $event->getCId(),
            $event->getSessionId()
        );

        if ($sendTo['everyone']) {
            $users = CourseManager::get_user_list_from_course_code($courseInfo['code'], $event->getSessionId());
            $userIdList = array_keys($users);

            if ($event->getSessionId()) {
                $coaches = SessionManager::getCoachesByCourseSession($event->getSessionId(), $event->getCId());
                $userIdList += $coaches;
            }

            foreach ($userIdList as $userId) {
                MessageManager::send_message_simple(
                    $userId,
                    $messageSubject,
                    $messageContent,
                    $senderId
                );
            }
        } else {
            foreach ($sendTo['groups'] as $groupId) {
                $groupUserList = GroupManager::get_users($groupId, false, null, null, false, $event->getSessionId());

                foreach ($groupUserList as $groupUserId) {
                    MessageManager::send_message_simple(
                        $groupUserId,
                        $messageSubject,
                        $messageContent,
                        $senderId
                    );
                }
            }

            foreach ($sendTo['users'] as $userId) {
                MessageManager::send_message_simple(
                    $userId,
                    $messageSubject,
                    $messageContent,
                    $senderId
                );
            }
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
