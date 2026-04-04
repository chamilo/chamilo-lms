<?php

/* For license terms, see /license.txt */

/**
 * This script initiates a video conference session, calling the BigBlueButton API.
 */

use Chamilo\CoreBundle\Entity\ConferenceActivity;
use Chamilo\CoreBundle\Entity\ConferenceMeeting;
use Chamilo\CoreBundle\Enums\ActionIcon;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CGroup;

$course_plugin = 'bbb'; // Needed to load plugin lang variables.
$isGlobal = isset($_GET['global']) ? true : false;
$isGlobalPerUser = isset($_GET['user_id']) ? (int) $_GET['user_id'] : false;

// If global setting is used then we delete the course sessions (cidReq/id_session).
if ($isGlobalPerUser || $isGlobal) {
    $cidReset = true;
}
require_once __DIR__.'/config.php';

function bbb_request_value(string $key, mixed $default = null): mixed
{
    if (array_key_exists($key, $_POST)) {
        return $_POST[$key];
    }

    if (array_key_exists($key, $_GET)) {
        return $_GET[$key];
    }

    return $default;
}

function bbb_get_request_action(): string
{
    $action = bbb_request_value('action', '');

    return is_string($action) ? $action : '';
}

function bbb_has_valid_action_token(): bool
{
    $sessionToken = $_SESSION['bbb_action_csrf_token'] ?? '';
    $requestToken = bbb_request_value('bbb_token', '');

    return is_string($sessionToken)
        && $sessionToken !== ''
        && is_string($requestToken)
        && $requestToken !== ''
        && hash_equals($sessionToken, $requestToken);
}

function bbb_require_action_token(Bbb $bbb): void
{
    if (bbb_has_valid_action_token()) {
        return;
    }

    Display::addFlash(Display::return_message(get_lang('Your session has expired. Please try again.'), 'error'));
    header('Location: '.$bbb->getListingUrl());
    exit;
}

function bbb_listing_add_classes_to_element(DOMElement $element, array $classes): void
{
    $existing = trim((string) $element->getAttribute('class'));
    $currentClasses = '' === $existing ? [] : preg_split('/\s+/', $existing);
    $currentClasses = is_array($currentClasses) ? $currentClasses : [];

    foreach ($classes as $class) {
        if (!in_array($class, $currentClasses, true)) {
            $currentClasses[] = $class;
        }
    }

    $element->setAttribute('class', trim(implode(' ', array_filter($currentClasses))));
}

function bbb_listing_get_element_inner_html(DOMElement $element): string
{
    $html = '';

    foreach ($element->childNodes as $childNode) {
        $html .= $element->ownerDocument->saveHTML($childNode);
    }

    return $html;
}

function bbb_listing_style_form_html(string $html): string
{
    if (!class_exists(DOMDocument::class) || '' === trim($html)) {
        return $html;
    }

    $previousUseInternalErrors = libxml_use_internal_errors(true);

    $document = new DOMDocument('1.0', 'UTF-8');
    $wrappedHtml = '<?xml encoding="utf-8" ?><div id="bbb-listing-form-root">'.$html.'</div>';

    $loaded = $document->loadHTML($wrappedHtml, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

    if (!$loaded) {
        libxml_clear_errors();
        libxml_use_internal_errors($previousUseInternalErrors);

        return $html;
    }

    $xpath = new DOMXPath($document);
    $root = $document->getElementById('bbb-listing-form-root');

    if (!$root) {
        libxml_clear_errors();
        libxml_use_internal_errors($previousUseInternalErrors);

        return $html;
    }

    $forms = $xpath->query('.//form', $root);
    if ($forms) {
        foreach ($forms as $form) {
            if ($form instanceof DOMElement) {
                bbb_listing_add_classes_to_element($form, ['space-y-5']);
            }
        }
    }

    $fieldsets = $xpath->query('.//fieldset', $root);
    if ($fieldsets) {
        foreach ($fieldsets as $fieldset) {
            if ($fieldset instanceof DOMElement) {
                bbb_listing_add_classes_to_element($fieldset, [
                    'rounded-2xl',
                    'border',
                    'border-gray-25',
                    'bg-white',
                    'p-5',
                    'shadow-sm',
                    'space-y-4',
                ]);
            }
        }
    }

    $formGroups = $xpath->query('.//*[contains(concat(" ", normalize-space(@class), " "), " form-group ")]', $root);
    if ($formGroups) {
        foreach ($formGroups as $group) {
            if ($group instanceof DOMElement) {
                bbb_listing_add_classes_to_element($group, [
                    'rounded-xl',
                    'border',
                    'border-gray-25',
                    'bg-support-2',
                    'p-4',
                    'space-y-3',
                ]);
            }
        }
    }

    $labels = $xpath->query('.//label', $root);
    if ($labels) {
        foreach ($labels as $label) {
            if ($label instanceof DOMElement) {
                bbb_listing_add_classes_to_element($label, [
                    'block',
                    'text-body-2',
                    'font-semibold',
                    'text-gray-90',
                ]);
            }
        }
    }

    $inputs = $xpath->query('.//input[not(@type="hidden") and not(@type="submit") and not(@type="button") and not(@type="checkbox") and not(@type="radio")] | .//select | .//textarea', $root);
    if ($inputs) {
        foreach ($inputs as $input) {
            if ($input instanceof DOMElement) {
                bbb_listing_add_classes_to_element($input, [
                    'mt-2',
                    'block',
                    'w-full',
                    'rounded-lg',
                    'border',
                    'border-gray-25',
                    'bg-white',
                    'px-3',
                    'py-2',
                    'text-body-2',
                    'text-gray-90',
                    'shadow-sm',
                    'focus:border-primary',
                    'focus:ring-primary',
                ]);
            }
        }
    }

    $checkboxes = $xpath->query('.//input[@type="checkbox"] | .//input[@type="radio"]', $root);
    if ($checkboxes) {
        foreach ($checkboxes as $checkbox) {
            if ($checkbox instanceof DOMElement) {
                bbb_listing_add_classes_to_element($checkbox, [
                    'h-4',
                    'w-4',
                    'rounded',
                    'border-gray-25',
                    'text-primary',
                    'focus:ring-primary',
                ]);
            }
        }
    }

    $helpBlocks = $xpath->query('.//*[contains(concat(" ", normalize-space(@class), " "), " help-block ") or contains(concat(" ", normalize-space(@class), " "), " form-control-feedback ")]', $root);
    if ($helpBlocks) {
        foreach ($helpBlocks as $helpBlock) {
            if ($helpBlock instanceof DOMElement) {
                bbb_listing_add_classes_to_element($helpBlock, [
                    'mt-2',
                    'block',
                    'text-caption',
                    'text-gray-50',
                ]);
            }
        }
    }

    $buttons = $xpath->query('.//button | .//input[@type="submit"] | .//input[@type="button"]', $root);
    if ($buttons) {
        foreach ($buttons as $button) {
            if (!$button instanceof DOMElement) {
                continue;
            }

            $type = strtolower((string) $button->getAttribute('type'));
            $buttonId = (string) $button->getAttribute('id');

            if ('bbb-pre-btn' === $buttonId) {
                bbb_listing_add_classes_to_element($button, [
                    'inline-flex',
                    'h-12',
                    'w-12',
                    'items-center',
                    'justify-center',
                    'rounded-xl',
                    'border',
                    'border-gray-25',
                    'bg-white',
                    'text-primary',
                    'shadow-sm',
                    'transition',
                    'hover:bg-support-2',
                ]);
                continue;
            }

            if (in_array($type, ['submit', 'button'], true) || '' === $type) {
                bbb_listing_add_classes_to_element($button, [
                    'inline-flex',
                    'items-center',
                    'justify-center',
                    'rounded-xl',
                    'border',
                    'border-primary',
                    'bg-primary',
                    'px-5',
                    'py-3',
                    'text-body-2',
                    'font-semibold',
                    'text-white',
                    'shadow-sm',
                    'transition',
                    'hover:opacity-90',
                    'disabled:cursor-not-allowed',
                    'disabled:border-primary-borderdisabled',
                    'disabled:bg-primary-bgdisabled',
                    'disabled:text-fontdisabled',
                ]);
            }
        }
    }

    $result = bbb_listing_get_element_inner_html($root);

    libxml_clear_errors();
    libxml_use_internal_errors($previousUseInternalErrors);

    return $result;
}

function bbb_listing_build_header_html(BbbPlugin $plugin, bool $status, int $usersOnline, int $meetingsCount): string
{
    $statusLabel = $status ? get_lang('Enabled') : get_lang('Disabled');
    $statusClasses = $status
        ? 'badge--success'
        : 'badge--warning';

    $usersLabel = htmlspecialchars((string) $usersOnline, ENT_QUOTES);
    $meetingsLabel = htmlspecialchars((string) $meetingsCount, ENT_QUOTES);

    return '
<div class="mx-auto max-w-7xl px-4 pt-6">
    <section class="rounded-2xl border border-gray-25 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div class="space-y-2">
                <div class="inline-flex items-center rounded-full bg-support-2 px-3 py-1 text-caption font-semibold text-support-4">
                    <em class="mdi mdi-video-outline mr-2"></em>'.htmlspecialchars($plugin->get_lang('Videoconference'), ENT_QUOTES).'
                </div>
                <h2 class="mb-0 text-2xl font-semibold text-gray-90">'.htmlspecialchars($plugin->get_lang('Videoconference'), ENT_QUOTES).'</h2>
                <p class="mb-0 text-body-2 text-gray-50">'.htmlspecialchars($plugin->get_lang('RecordList'), ENT_QUOTES).'</p>
            </div>
            <div class="grid gap-3 sm:grid-cols-3">
                <div class="rounded-xl border border-gray-25 bg-support-2 px-4 py-3">
                    <div class="text-caption font-semibold uppercase tracking-wide text-gray-50">'.htmlspecialchars(get_lang('Status'), ENT_QUOTES).'</div>
                    <div class="mt-2"><span class="badge '.$statusClasses.'">'.htmlspecialchars($statusLabel, ENT_QUOTES).'</span></div>
                </div>
                <div class="rounded-xl border border-gray-25 bg-support-2 px-4 py-3">
                    <div class="text-caption font-semibold uppercase tracking-wide text-gray-50">'.htmlspecialchars(get_lang('Users'), ENT_QUOTES).'</div>
                    <div class="mt-2 text-xl font-semibold text-gray-90">'.$usersLabel.'</div>
                </div>
                <div class="rounded-xl border border-gray-25 bg-support-2 px-4 py-3">
                    <div class="text-caption font-semibold uppercase tracking-wide text-gray-50">'.htmlspecialchars($plugin->get_lang('RecordList'), ENT_QUOTES).'</div>
                    <div class="mt-2 text-xl font-semibold text-gray-90">'.$meetingsLabel.'</div>
                </div>
            </div>
        </div>
    </section>
</div>';
}

function bbb_listing_build_action_button(string $url, string $label, string $icon): string
{
    return '<a href="'.htmlspecialchars($url, ENT_QUOTES).'" class="inline-flex items-center justify-center gap-2 rounded-xl border border-primary bg-primary px-4 py-3 text-body-2 font-semibold text-white shadow-sm transition hover:opacity-90">'
        .'<em class="mdi mdi-'.htmlspecialchars($icon, ENT_QUOTES).'"></em>'
        .htmlspecialchars($label, ENT_QUOTES)
        .'</a>';
}


$plugin = BbbPlugin::create();
$tool_name = $plugin->get_lang('Videoconference');
$em = Database::getManager();
$meetingRepository = $em->getRepository(ConferenceMeeting::class);
$activityRepo = $em->getRepository(ConferenceActivity::class);

$htmlHeadXtra[] = api_get_js_simple(api_get_path(WEB_PLUGIN_PATH).'Bbb/resources/utils.js');

$action = bbb_get_request_action();
$userId = api_get_user_id();
$groupId = api_get_group_id();
$sessionId = api_get_session_id();
$courseInfo = api_get_course_info();
$course = api_get_course_entity();
$canSeeShareableJoinLink = $plugin->showShareLink();

// Instantiate BBB helper (it should lazy-initialize the underlying API internally).
$bbb = new Bbb('', '', $isGlobal, $isGlobalPerUser);

// Basic access control: global conferences require authenticated users, otherwise protect course context.
$conferenceManager = $bbb->isConferenceManager();
if ($bbb->isGlobalConference()) {
    api_block_anonymous_users();
} else {
    api_protect_course_script(true);
}

// Allow students to start conferences in groups if course setting allows it.
$allowStudentAsConferenceManager = false;
if (!empty($courseInfo) && !empty($groupId) && !api_is_allowed_to_edit()) {
    $groupEnabled = '1' === api_get_course_plugin_setting('bbb', 'bbb_enable_conference_in_groups', $courseInfo);
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

// Only conference managers can edit, unless student-start-in-groups is enabled (then still disable edit UI).
$allowToEdit = $conferenceManager;
if ($allowStudentAsConferenceManager) {
    $allowToEdit = false;
}

$courseCode = $courseInfo['code'] ?? '';

$message = '';
if ($conferenceManager && $allowToEdit) {
    switch ($action) {
        case 'add_to_calendar':
            bbb_require_action_token($bbb);
            if ($bbb->isGlobalConference()) {
                return false;
            }
            $courseInfo = api_get_course_info();
            $agenda = new Agenda('course');
            $id = (int) bbb_request_value('id', 0);
            $title = sprintf($plugin->get_lang('VideoConferenceXCourseX'), $id, $course->getTitle());
            $calendarUrl = (string) bbb_request_value('url', '#');
            if (!filter_var($calendarUrl, FILTER_VALIDATE_URL)) {
                $calendarUrl = '#';
            }
            $content = Display::url($plugin->get_lang('GoToTheVideoConference'), $calendarUrl);

            $eventId = $agenda->addEvent(
                bbb_request_value('start'),
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
            bbb_require_action_token($bbb);
            $result = $bbb->copyRecordingToLinkTool(bbb_request_value('id', ''));
            if ($result) {
                $message = Display::return_message($plugin->get_lang('VideoConferenceAddedToTheLinkTool'), 'success');
            } else {
                $message = Display::return_message(get_lang('Error'), 'error');
            }
            break;
        case 'regenerate_record':
            bbb_require_action_token($bbb);
            if ('true' !== $plugin->get('allow_regenerate_recording')) {
                api_not_allowed(true);
            }
            $recordId = (string) bbb_request_value('record_id', '');
            $result = $bbb->regenerateRecording(bbb_request_value('id', ''), $recordId);
            if ($result) {
                $message = Display::return_message(get_lang('Success'), 'success');
            } else {
                $message = Display::return_message(get_lang('Error'), 'error');
            }

            Display::addFlash($message);
            header('Location: '.$bbb->getListingUrl());
            exit;

        case 'delete_record':
            bbb_require_action_token($bbb);
            $result = $bbb->deleteRecording(bbb_request_value('id', ''));
            if ($result) {
                $message = Display::return_message(get_lang('Deleted'), 'success');
            } else {
                $message = Display::return_message(get_lang('Error'), 'error');
            }

            Display::addFlash($message);
            header('Location: '.$bbb->getListingUrl());
            exit;

        case 'end':
            bbb_require_action_token($bbb);
            $bbb->endMeeting(bbb_request_value('id', ''));
            $message = Display::return_message(
                $plugin->get_lang('MeetingClosed').'<br />'.$plugin->get_lang('MeetingClosedComment'),
                'success',
                false
            );

            // Optional VM autoscaling (if configured).
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

        case 'publish':
            bbb_require_action_token($bbb);
            $bbb->publishMeeting(bbb_request_value('id', ''));
            Display::addFlash(Display::return_message(get_lang('Updated')));
            header('Location: '.$bbb->getListingUrl());
            exit;

        case 'unpublish':
            bbb_require_action_token($bbb);
            $bbb->unpublishMeeting(bbb_request_value('id', ''));
            Display::addFlash(Display::return_message(get_lang('Updated')));
            header('Location: '.$bbb->getListingUrl());
            exit;

        case 'logout':
            if ('true' === $plugin->get('allow_regenerate_recording')) {
                $setting = api_get_course_plugin_setting('bbb', 'bbb_force_record_generation', $courseInfo);
                $allow = (int) $setting === 1;
                if ($allow) {
                    $result = $bbb->getMeetingByRemoteId((string) bbb_request_value('remote_id', ''));
                    if (!empty($result)) {
                        $result = $bbb->regenerateRecording($result['id'] ?? null);
                        if ($result) {
                            Display::addFlash(Display::return_message(get_lang('Success')));
                        } else {
                            Display::addFlash(Display::return_message(get_lang('Error'), 'error'));
                        }
                    }
                }
            }

            $remoteId = Database::escape_string((string) bbb_request_value('remote_id', ''));
            $meetingData = $bbb->getMeetingByRemoteId($remoteId);

            if (empty($meetingData) || !is_array($meetingData)) {
                error_log("meeting does not exist - remote_id: $remoteId");
            } else {
                $meetingId = $meetingData['id'];

                // If creator -> update room activity tracking.
                if (($meetingData['user_id'] ?? 0) == api_get_user_id()) {
                    $pass = $bbb->getModMeetingPassword($courseCode);

                    $meetingBBB = $bbb->getMeetingInfo([
                        'meetingId' => $remoteId,
                        'password' => $pass,
                    ]);

                    if (false === $meetingBBB) {
                        // Backward support using internal meeting id.
                        $params = [
                            'meetingId' => $meetingId,
                            'password' => $pass,
                        ];
                        $meetingBBB = $bbb->getMeetingInfo($params);
                    }

                    if (!empty($meetingBBB) && isset($meetingBBB['returncode'])) {
                        $status = (string) $meetingBBB['returncode'];
                        switch ($status) {
                            case 'FAILED':
                                $bbb->endMeeting($meetingId, $courseCode);
                                break;

                            case 'SUCCESS':
                                // Close last open activity records for all participants.
                                $count = (int) ($meetingBBB['participantCount'] ?? 0);
                                for ($i = 0; $i < $count; $i++) {
                                    $participantId = $meetingBBB[$i]['userId'] ?? null;
                                    if ($participantId === null) {
                                        continue;
                                    }

                                    $qb = $activityRepo->createQueryBuilder('a')
                                        ->where('a.meeting = :meetingId')
                                        ->andWhere('a.participant = :participantId')
                                        ->andWhere('a.close = :close')
                                        ->orderBy('a.id', 'DESC')
                                        ->setMaxResults(1)
                                        ->setParameters([
                                            'meetingId' => $meetingId,
                                            'participantId' => $participantId,
                                            'close' => BbbPlugin::ROOM_OPEN,
                                        ]);

                                    $result = $qb->getQuery()->getArrayResult();
                                    $roomData = $result[0] ?? null;

                                    if (!empty($roomData)) {
                                        $roomId = $roomData['id'] ?? null;
                                        if (!empty($roomId)) {
                                            $activity = $activityRepo->find($roomId);
                                            if ($activity instanceof ConferenceActivity) {
                                                $activity->setOutAt(new \DateTime());
                                                $em->flush();
                                            }
                                        }
                                    }
                                }
                                break;
                        }
                    }
                }

                // Update out_at field for current user.
                $roomData = $activityRepo->findOneArrayByMeetingAndParticipant($meetingId, $userId);
                if (!empty($roomData)) {
                    $roomId = $roomData['id'] ?? null;
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

        default:
            break;
    }
} else {
    // Non-manager logout path: close any open activity for the user.
    if ('logout' == $action) {
        $remoteId = Database::escape_string((string) bbb_request_value('remote_id', ''));
        $meetingData = $bbb->getMeetingByRemoteId($remoteId);

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

// Safely fetch meetings (BBB helper should return array or empty on failure).
$meetings = $bbb->getMeetings(api_get_course_int_id(), api_get_session_id(), $groupId);
if (!is_array($meetings)) {
    $meetings = [];
}
if (!empty($meetings)) {
    $meetings = array_reverse($meetings);
}

// Defensive casting for UI counters.
$usersOnline = (int) $bbb->getUsersOnlineInCurrentRoom();
$maxUsers = (int) $bbb->getMaxUsersLimit();
$status = (bool) $bbb->isServerRunning();

$videoConferenceName = (string) $bbb->getCurrentVideoConferenceName();
$meetingExists = $bbb->meetingExists($videoConferenceName) ? true : false;

$showJoinButton = false;
// Only conference manager can see the join button (except global-per-user mode).
$userCanSeeJoinButton = $conferenceManager;
if ($bbb->isGlobalConference() && $bbb->isGlobalConferencePerUserEnabled()) {
    $userCanSeeJoinButton = true;
}
if (($meetingExists || $userCanSeeJoinButton) && (0 == $maxUsers || $maxUsers > $usersOnline)) {
    $showJoinButton = true;
}
$conferenceUrl = $bbb->getConferenceUrl();
$courseInfo = api_get_course_info();
$formToString = '';

// Group selector UI (if enabled by setting).
if (
    false === $bbb->isGlobalConference()
    && !empty($courseInfo)
    && 'true' === $plugin->get('enable_conference_in_course_groups')
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
        $meetingsGroup = is_array($meetingsInGroup) ? array_column($meetingsInGroup, 'status', 'group_id') : [];
        $groupList = [];
        $groupList[0] = get_lang('Select');
        foreach ($groups as $groupData) {
            if ($groupData instanceof CGroup) {
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

$messagesAboveForm = '';
try {
    $messagesAboveForm .= Display::getFlash();
    $sess = Container::getSession()->get('bbb_preupload_message');
    if ($sess) {
        $messagesAboveForm .= Display::return_message($sess['text'], $sess['type'], false);
        Container::getSession()->remove('bbb_preupload_message');
    }
} catch (\Throwable $e) {}

// Links + start form (with pre-upload document picker).
$urlList = [];
if ($conferenceManager && $allowToEdit) {
    $form = new FormValidator('start_conference', 'post', $conferenceUrl);
    $form->addElement('hidden', 'action', 'start');
    $form->addElement('hidden', 'bbb_token', $bbb->getActionCsrfToken());
    $ajaxUrl     = api_get_path(WEB_PATH).'main/inc/ajax/plugin.ajax.php?plugin=bbb&a=list_documents&'.api_get_cidreq();
    if ($isGlobal) {
        $ajaxUrl .= '&global=1';
    }
    if ($isGlobalPerUser) {
        $ajaxUrl .= '&user_id='.(int) $isGlobalPerUser;
    }
    $maxTotalMb  = (int) api_get_course_plugin_setting('bbb', 'bbb_preupload_max_total_mb', $courseInfo);
    if ($maxTotalMb <= 0) { $maxTotalMb = 20; }

    $title      = htmlspecialchars(get_lang('Pre-upload Documents'), ENT_QUOTES);
    $help       = htmlspecialchars(get_lang('Select the PDF or PPTX files you want to pre-load as slides for the conference.'), ENT_QUOTES);
    $loadingTxt = htmlspecialchars(get_lang('Loading'), ENT_QUOTES);
    $noDocsTxt  = htmlspecialchars(get_lang('No documents found'), ENT_QUOTES);
    $failTxt    = htmlspecialchars(get_lang('Failed to load documents'), ENT_QUOTES);
    $maxLabel   = htmlspecialchars(sprintf(get_lang('Max total: %d MB'), $maxTotalMb), ENT_QUOTES);

    $iconHtml = Display::getMdiIcon(
        ActionIcon::UPLOAD,
        'ch-tool-icon',
        null,
        ICON_SIZE_MEDIUM,
        $title
    );

    $preuploadHtml = '
<div class="bbb-preupload" style="position:relative;">
  <button type="button" id="bbb-pre-btn"
          class="btn btn--icon"
          title="'.$title.'"
          style="position:absolute; right:0; top:-8px;">
    '.$iconHtml.'
  </button>

  <div id="bbb-pre-pop" class="hidden"
       style="position:absolute; right:0; top:28px; z-index:50;
              width:340px; background:#fff; border:1px solid #e5e7eb;
              border-radius:8px; box-shadow:0 8px 24px rgba(0,0,0,.12);
              padding:10px;">
    <div class="text-sm" style="margin-bottom:6px; color:#475569;">'.$help.'</div>
    <div id="preupload-list"
         class="text-sm"
         style="max-height:220px; overflow:auto; border:1px solid #eef2f7;
                border-radius:6px; padding:8px; color:#0f172a;">
      '.$loadingTxt.'…
    </div>
    <div class="text-xs" style="margin-top:6px; color:#64748b;">
      '.$maxLabel.' — <span id="preupload-total">0</span> MB
    </div>
  </div>
</div>

<script>
(function(){
  var btn   = document.getElementById("bbb-pre-btn");
  var pop   = document.getElementById("bbb-pre-pop");
  var list  = document.getElementById("preupload-list");
  var loaded = false;
  var ajax   = "'.$ajaxUrl.'";
  var maxMb  = '.$maxTotalMb.';

  function esc(t){
    return String(t).replace(/[&<>\"\\\']/g, function(s){
      return {"&":"&amp;","<":"&lt;",">":"&gt;","\\"":"&quot;","\\\'":"&#39;"}[s];
    });
  }

  function togglePop(){
    if (pop.classList.contains("hidden")) {
      pop.classList.remove("hidden");
      if (!loaded) {
        loaded = true;
        fetch(ajax, {credentials:"same-origin"})
          .then(function(r){ return r.json(); })
          .then(renderList)
          .catch(function(){
            list.innerHTML = \'<p class="text-sm" style="color:#dc2626">'.$failTxt.'</p>\';
          });
      }
    } else {
      pop.classList.add("hidden");
    }
  }

  function clickOutside(e){
    if (!pop.contains(e.target) && !btn.contains(e.target)) {
      pop.classList.add("hidden");
    }
  }

  function renderList(docs){
    var items = Array.isArray(docs) ? docs.filter(function(d){
      return (d.filename||"").match(/\\.(pdf|ppt|pptx|odp)$/i);
    }) : [];

    if (!items.length) {
      list.innerHTML = \'<p class="text-sm" style="color:#64748b">'.$noDocsTxt.'</p>\';
      return;
    }

    list.innerHTML = items.map(function(doc){
      var data = JSON.stringify({url:doc.url, filename:doc.filename, size:doc.size}).replace(/"/g,"&quot;");
      return \'<label class="flex items-center gap-2" style="display:flex;align-items:center;gap:.5rem;margin:.25rem 0;">\'
           + \'<input type="checkbox" class="h-4 w-4" name="documents[]" value="\' + data + \'" />\'
           + \'<span class="truncate">\' + esc(doc.filename||"") + \'</span>\'
           + \'</label>\';
    }).join("");

    list.addEventListener("change", recalcTotal, true);
  }

  function recalcTotal(){
    var boxes = list.querySelectorAll(\'input[type="checkbox"]:checked\');
    var total = 0;
    boxes.forEach(function(b){
      try { var o = JSON.parse(b.value.replace(/&quot;/g, \'"\')); total += (o.size||0); } catch(e){}
    });
    var mb = (total/1048576).toFixed(1);
    var out = document.getElementById("preupload-total");
    if (out) out.textContent = mb;

    var submit = document.querySelector(\'form[name="start_conference"] [type="submit"]\');
    if (submit) submit.disabled = (total > maxMb * 1048576);
  }

  if (btn) btn.addEventListener("click", togglePop);
  document.addEventListener("click", clickOutside);
})();
</script>';

    $form->addElement('html', $preuploadHtml);
    $form->addElement('html', '<script>
  (function(){
    if (location.hash === "#bbb-pre-pop") {
      var btn = document.getElementById("bbb-pre-btn");
      if (btn) { setTimeout(function(){ btn.click(); }, 0); }
    }
  })();
</script>');
    $form->addElement(
        'submit',
        'start_meeting',
        $plugin->get_lang('EnterConference'),
        'class="btn btn--primary btn-large text-white"'
    );
    $formHtml = $form->returnForm();
    if (!empty($messagesAboveForm)) {
        $formHtml = $messagesAboveForm.$formHtml;
        $messagesAboveForm = '';
    }
    $formToString = bbb_listing_style_form_html($formHtml);
} else {
    // Fallback: plain "Enter conference" link for non-managers (if allowed by context/template).
    $urlList[] = Display::url(
        $plugin->get_lang('EnterConference'),
        $conferenceUrl,
        ['target' => '_blank', 'class' => 'btn btn--primary btn-large text-white']
    );
}

// Render template.
$tpl = new Template($tool_name);

$tpl->assign('allow_to_edit', $allowToEdit);
$tpl->assign('meetings', $meetings);
$tpl->assign('conference_url', $conferenceUrl);
$tpl->assign('users_online', $usersOnline);
$tpl->assign('conference_manager', $conferenceManager);
$tpl->assign('max_users_limit', $maxUsers);
$tpl->assign('bbb_status', $status);
$tpl->assign('show_join_button', $showJoinButton);
$tpl->assign('form', $formToString);
$tpl->assign('enter_conference_links', $urlList);
$tpl->assign('can_see_share_link', $canSeeShareableJoinLink);
$tpl->assign('plugin', $plugin);
$tpl->assign('message', $message);
$content = $tpl->fetch('Bbb/view/listing.tpl');

$headerHtml = bbb_listing_build_header_html($plugin, $status, $usersOnline, count($meetings));

$actionsHtml = '';
if (api_is_platform_admin()) {
    $dashboardUrl = api_get_path(WEB_PLUGIN_PATH).'Bbb/webhook_dashboard.php';
    $actionButtons = [];
    $actionButtons[] = bbb_listing_build_action_button(
        api_get_path(WEB_PLUGIN_PATH).'Bbb/admin.php',
        $plugin->get_lang('AdminView'),
        'view-dashboard-outline'
    );

    if ($plugin->webhooksEnabled()) {
        $actionButtons[] = bbb_listing_build_action_button(
            $dashboardUrl,
            $plugin->get_lang('ViewActivityDashboard'),
            'chart-line'
        );
    }

    if (!empty($actionButtons)) {
        $actionsHtml = '<div class="mx-auto max-w-7xl px-4 pt-4"><div class="flex flex-wrap gap-3">'.implode('', $actionButtons).'</div></div>';
    }
}

$tpl->assign('content', $headerHtml.$actionsHtml.$content);
$tpl->display_one_col_template();
