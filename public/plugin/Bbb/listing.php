<?php

/* For license terms, see /license.txt */

/**
 * This script initiates a video conference session, calling the BigBlueButton API.
 */

use Chamilo\CoreBundle\Entity\ConferenceActivity;
use Chamilo\CoreBundle\Entity\ConferenceMeeting;

$course_plugin = 'bbb'; //needed in order to load the plugin lang variables
$isGlobal = isset($_GET['global']) ? true : false;
$isGlobalPerUser = isset($_GET['user_id']) ? (int) $_GET['user_id'] : false;

// If global setting is used then we delete the course sessions (cidReq/id_session)
if ($isGlobalPerUser || $isGlobal) {
    $cidReset = true;
}
require_once __DIR__.'/config.php';

$plugin = BbbPlugin::create();
$tool_name = $plugin->get_lang('Videoconference');
$em = Database::getManager();
$meetingRepository = $em->getRepository(ConferenceMeeting::class);
$activityRepo = $em->getRepository(ConferenceActivity::class);

$htmlHeadXtra[] = api_get_js_simple(api_get_path(WEB_PLUGIN_PATH).'Bbb/resources/utils.js');

$action = $_GET['action'] ?? '';
$userId = api_get_user_id();
$groupId = api_get_group_id();
$sessionId = api_get_session_id();
$courseInfo = api_get_course_info();
$course = api_get_course_entity();

$bbb = new Bbb('', '', $isGlobal, $isGlobalPerUser);

$conferenceManager = $bbb->isConferenceManager();
if ($bbb->isGlobalConference()) {
    api_block_anonymous_users();
} else {
    api_protect_course_script(true);
}

$allowStudentAsConferenceManager = false;
if (!empty($courseInfo) && !empty($groupId) && !api_is_allowed_to_edit()) {
    $groupEnabled = '1' === api_get_course_plugin_setting(
            'bbb',
            'bbb_enable_conference_in_groups',
            $courseInfo
        );
    if ($groupEnabled) {
        $group = api_get_group_entity($groupId);
        $isSubscribed = GroupManager::isUserInGroup(api_get_user_id(), $group);
        if ($isSubscribed) {
            $allowStudentAsConferenceManager = '1' === api_get_course_plugin_setting(
                    'bbb',
                    'big_blue_button_students_start_conference_in_groups',
                    $courseInfo
                );
        }
    }
}

$allowToEdit = $conferenceManager;
// Disable students edit permissions.
if ($allowStudentAsConferenceManager) {
    $allowToEdit = false;
}

$courseCode = $courseInfo['code'] ?? '';

$message = '';
if ($conferenceManager && $allowToEdit) {
    switch ($action) {
        case 'add_to_calendar':
            if ($bbb->isGlobalConference()) {
                return false;
            }
            $courseInfo = api_get_course_info();
            $agenda = new Agenda('course');
            $id = (int) $_GET['id'];
            $title = sprintf($plugin->get_lang('VideoConferenceXCourseX'), $id, $course->getTitle());
            $content = Display::url($plugin->get_lang('GoToTheVideoConference'), $_GET['url']);

            $eventId = $agenda->addEvent(
                $_REQUEST['start'],
                null,
                'true',
                $title,
                $content,
                ['everyone']
            );
            if (!empty($eventId)) {
                $message = Display::return_message($plugin->get_lang('VideoConferenceAddedToTheCalendar'), 'success');
            } else {
                $message = Display::return_message(get_lang('Error'), 'error');
            }
            break;
        case 'copy_record_to_link_tool':
            $result = $bbb->copyRecordingToLinkTool($_GET['id']);
            if ($result) {
                $message = Display::return_message($plugin->get_lang('VideoConferenceAddedToTheLinkTool'), 'success');
            } else {
                $message = Display::return_message(get_lang('Error'), 'error');
            }
            break;
        case 'regenerate_record':
            if ('true' !== $plugin->get('allow_regenerate_recording')) {
                api_not_allowed(true);
            }
            $recordId = isset($_GET['record_id']) ? $_GET['record_id'] : '';
            $result = $bbb->regenerateRecording($_GET['id'], $recordId);
            if ($result) {
                $message = Display::return_message(get_lang('Success'), 'success');
            } else {
                $message = Display::return_message(get_lang('Error'), 'error');
            }

            Display::addFlash($message);
            header('Location: '.$bbb->getListingUrl());
            exit;
            break;
        case 'delete_record':
            $result = $bbb->deleteRecording($_GET['id']);
            if ($result) {
                $message = Display::return_message(get_lang('Deleted'), 'success');
            } else {
                $message = Display::return_message(get_lang('Error'), 'error');
            }

            Display::addFlash($message);
            header('Location: '.$bbb->getListingUrl());
            exit;
            break;
        case 'end':
            $bbb->endMeeting($_GET['id']);
            $message = Display::return_message(
                $plugin->get_lang('MeetingClosed').'<br />'.$plugin->get_lang('MeetingClosedComment'),
                'success',
                false
            );

            if (file_exists(__DIR__.'/config.vm.php')) {
                require __DIR__.'/../../vendor/autoload.php';
                require __DIR__.'/lib/vm/AbstractVM.php';
                require __DIR__.'/lib/vm/VMInterface.php';
                require __DIR__.'/lib/vm/DigitalOceanVM.php';
                require __DIR__.'/lib/VM.php';
                $config = require __DIR__.'/config.vm.php';

                $vm = new VM($config);
                $vm->resizeToMinLimit();
            }

            Display::addFlash($message);
            header('Location: '.$bbb->getListingUrl());
            exit;
            break;
        case 'publish':
            $bbb->publishMeeting($_GET['id']);
            Display::addFlash(Display::return_message(get_lang('Updated')));
            header('Location: '.$bbb->getListingUrl());
            exit;
            break;
        case 'unpublish':
            $bbb->unpublishMeeting($_GET['id']);
            Display::addFlash(Display::return_message(get_lang('Updated')));
            header('Location: '.$bbb->getListingUrl());
            exit;
            break;
        case 'logout':
            if ('true' === $plugin->get('allow_regenerate_recording')) {
                $setting = api_get_course_plugin_setting('bbb', 'bbb_force_record_generation', $courseInfo);
                $allow = 1 == $setting ? true : false;
                if ($allow) {
                    $result = $bbb->getMeetingByRemoteId($_GET['remote_id']);
                    if (!empty($result)) {
                        $result = $bbb->regenerateRecording($result['id']);
                        if ($result) {
                            Display::addFlash(Display::return_message(get_lang('Success')));
                        } else {
                            Display::addFlash(Display::return_message(get_lang('Error'), 'error'));
                        }
                    }
                }
            }

            $remoteId = Database::escape_string($_GET['remote_id']);
            $meetingData = $meetingRepository->findOneByRemoteIdAndAccessUrl($remoteId, api_get_current_access_url_id());

            if (empty($meetingData) || !is_array($meetingData)) {
                error_log("meeting does not exist - remote_id: $remoteId");
            } else {
                $meetingId = $meetingData['id'];

                // If creator -> update
                if ($meetingData['user_id'] == api_get_user_id()) {
                    $pass = $bbb->getModMeetingPassword($courseCode);

                    $meetingBBB = $bbb->getMeetingInfo(
                        [
                            'meetingId' => $remoteId,
                            'password' => $pass,
                        ]
                    );

                    if (false === $meetingBBB) {
                        //checking with the remote_id didn't work, so just in case and
                        // to provide backwards support, check with the id
                        $params = [
                            'meetingId' => $meetingId,
                            //  -- REQUIRED - The unique id for the meeting
                            'password' => $pass,
                            //  -- REQUIRED - The moderator password for the meeting
                        ];
                        $meetingBBB = $bbb->getMeetingInfo($params);
                    }

                    if (!empty($meetingBBB)) {
                        if (isset($meetingBBB['returncode'])) {
                            $status = (string) $meetingBBB['returncode'];
                            switch ($status) {
                                case 'FAILED':
                                    $bbb->endMeeting($meetingId, $courseCode);
                                    break;
                                case 'SUCCESS':
                                    $i = 0;
                                    while ($i < $meetingBBB['participantCount']) {
                                        $participantId = $meetingBBB[$i]['userId'];
                                        $qb = $activityRepo->createQueryBuilder('a')
                                            ->where('a.meeting = :meetingId')
                                            ->andWhere('a.participant = :participantId')
                                            ->andWhere('a.close = :close')
                                            ->orderBy('a.id', 'DESC')
                                            ->setMaxResults(1)
                                            ->setParameters([
                                                'meetingId' => $meetingId,
                                                'participantId' => $participantId,
                                                'close' => \BbbPlugin::ROOM_OPEN,
                                            ]);

                                        $result = $qb->getQuery()->getArrayResult();
                                        $roomData = $result[0] ?? null;

                                        if (!empty($roomData)) {
                                            $roomId = $roomData['id'];
                                            if (!empty($roomId)) {
                                                $activity = $activityRepo->find($roomId);
                                                if ($activity) {
                                                    $activity->setOutAt(new \DateTime());
                                                    $em->flush();
                                                }
                                            }
                                        }
                                        $i++;
                                    }
                                    break;
                            }
                        }
                    }
                }

                // Update out_at field of user
                $roomData = $activityRepo->findOneArrayByMeetingAndParticipant($meetingId, $userId);
                if (!empty($roomData)) {
                    $roomId = $roomData['id'];
                    if (!empty($roomId)) {
                        $activity = $activityRepo->find($roomId);
                        if ($activity instanceof ConferenceActivity) {
                            $activity->setOutAt(new \DateTime());
                            $activity->setClose(BbbPlugin::ROOM_CLOSE);
                            $em->flush();
                        }
                    }
                }

                $message = Display::return_message(
                    $plugin->get_lang('RoomClosed').'<br />'.$plugin->get_lang('RoomClosedComment'),
                    'success',
                    false
                );
                Display::addFlash($message);
            }

            header('Location: '.$bbb->getListingUrl());
            exit;
            break;
        default:
            break;
    }
} else {
    if ('logout' == $action) {
        // Update out_at field of user
        $remoteId = Database::escape_string($_GET['remote_id']);
        $meetingData = $meetingRepository->findOneByRemoteIdAndAccessUrl($remoteId, api_get_current_access_url_id());

        if (empty($meetingData) || !is_array($meetingData)) {
            error_log("meeting does not exist - remote_id: $remoteId");
        } else {
            $meetingId = $meetingData['id'];
            $roomData = $activityRepo->createQueryBuilder('a')
                ->where('a.meeting = :meetingId')
                ->andWhere('a.participant = :userId')
                ->andWhere('a.close = :open')
                ->setParameter('meetingId', $meetingId)
                ->setParameter('userId', $userId)
                ->setParameter('open', BbbPlugin::ROOM_OPEN)
                ->orderBy('a.id', 'DESC')
                ->getQuery()
                ->getResult();

            $i = 0;
            foreach ($roomData as $activity) {
                if ($activity instanceof ConferenceActivity) {
                    if (0 === $i) {
                        $activity->setOutAt(new \DateTime());
                    }
                    $activity->setClose(BbbPlugin::ROOM_CLOSE);
                    $i++;
                }
            }

            $em->flush();

            $message = Display::return_message(
                $plugin->get_lang('RoomExit'),
                'success',
                false
            );
            Display::addFlash($message);
        }
        header('Location: '.$bbb->getListingUrl());
        exit;
    }
}

$meetings = $bbb->getMeetings(
    api_get_course_int_id(),
    api_get_session_id(),
    $groupId
);
if (!empty($meetings)) {
    $meetings = array_reverse($meetings);
}
$usersOnline = $bbb->getUsersOnlineInCurrentRoom();
$maxUsers = $bbb->getMaxUsersLimit();
$status = $bbb->isServerRunning();
$videoConferenceName = $bbb->getCurrentVideoConferenceName();
$meetingExists = $bbb->meetingExists($videoConferenceName);
$showJoinButton = false;

// Only conference manager can see the join button
$userCanSeeJoinButton = $conferenceManager;
if ($bbb->isGlobalConference() && $bbb->isGlobalConferencePerUserEnabled()) {
    // Any user can see the "join button" BT#12620
    $userCanSeeJoinButton = true;
}

if (($meetingExists || $userCanSeeJoinButton) && (0 == $maxUsers || $maxUsers > $usersOnline)) {
    $showJoinButton = true;
}
$conferenceUrl = $bbb->getConferenceUrl();
$courseInfo = api_get_course_info();
$formToString = '';

if (false === $bbb->isGlobalConference() &&
    !empty($courseInfo) &&
    'true' === $plugin->get('enable_conference_in_course_groups')
) {
    $url = api_get_self().'?'.api_get_cidreq(true, false).'&gid=0';

    $htmlHeadXtra[] = '<script>
         $(function() {
            $("#group_select").on("change", function() {
                var groupId = $(this).find("option:selected").val();
                var url = "'.$url.'";
                window.location.replace(url+groupId);
            });
        });
        </script>';

    $form = new FormValidator(api_get_self().'?'.api_get_cidreq());
    if ($conferenceManager && false === $allowStudentAsConferenceManager) {
        $groups = GroupManager::get_group_list(null, $course, null, $sessionId);
    } else {
        if (!empty($groupId)) {
            $group = api_get_group_entity($groupId);
            if ($group) {
                $isSubscribed = GroupManager::isUserInGroup(api_get_user_id(), $group);
                if (false === $isSubscribed) {
                    api_not_allowed(true);
                }
            }
        }

        $groups = GroupManager::getAllGroupPerUserSubscription(
            api_get_user_id(),
            api_get_course_int_id(),
            api_get_session_id()
        );
    }

    if ($groups) {
        $meetingsInGroup = $bbb->getAllMeetingsInCourse(api_get_course_int_id(), api_get_session_id(), 1);
        $meetingsGroup = array_column($meetingsInGroup, 'status', 'group_id');
        $groupList[0] = get_lang('Select');
        foreach ($groups as $groupData) {
            if ($groupData instanceof \Chamilo\CourseBundle\Entity\CGroup) {
                $itemGroupId = $groupData->getIid();
                $name = $groupData->getTitle();
            } else {
                $itemGroupId = $groupData['iid'];
                $name = $groupData['title'];
            }
            if (isset($meetingsGroup[$itemGroupId]) && 1 == $meetingsGroup[$itemGroupId]) {
                $name .= ' ('.get_lang('Active').')';
            }
            $groupList[$itemGroupId] = $name;
        }

        $form->addSelect('group_id', get_lang('Groups'), $groupList, ['id' => 'group_select']);
        $form->setDefaults(['group_id' => $groupId]);
        $formToString = $form->returnForm();
    }
}

// Default URL
$urlList[] = Display::url(
    $plugin->get_lang('EnterConference'),
    $conferenceUrl,
    ['target' => '_blank', 'class' => 'btn btn--primary btn-large']
);

$tpl = new Template($tool_name);

$tpl->assign('allow_to_edit', $allowToEdit);
$tpl->assign('meetings', $meetings);
$tpl->assign('conference_url', $conferenceUrl);
$tpl->assign('users_online', $usersOnline);
$tpl->assign('conference_manager', $conferenceManager);
$tpl->assign('max_users_limit', $maxUsers);
$tpl->assign('bbb_status', $status);
$tpl->assign('show_join_button', $showJoinButton);
$tpl->assign('message', $message);
$tpl->assign('form', $formToString);
$tpl->assign('enter_conference_links', $urlList);

$content = $tpl->fetch('Bbb/view/listing.tpl');

$actionLinks = '';
if (api_is_platform_admin()) {
    $actionLinks .= Display::toolbarButton(
        $plugin->get_lang('AdminView'),
        api_get_path(WEB_PLUGIN_PATH).'Bbb/admin.php',
        'list',
        'primary'
    );

    $tpl->assign(
        'actions',
        Display::toolbarAction('toolbar', [$actionLinks])
    );
}

$tpl->assign('content', $content);
$tpl->display_one_col_template();
