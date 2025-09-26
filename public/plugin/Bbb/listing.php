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
            if ($bbb->isGlobalConference()) {
                return false;
            }
            $courseInfo = api_get_course_info();
            $agenda = new Agenda('course');
            $id = (int) ($_GET['id'] ?? 0);
            $title = sprintf($plugin->get_lang('VideoConferenceXCourseX'), $id, $course->getTitle());
            $content = Display::url($plugin->get_lang('GoToTheVideoConference'), $_GET['url'] ?? '#');

            $eventId = $agenda->addEvent(
                $_REQUEST['start'] ?? null,
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
            $result = $bbb->copyRecordingToLinkTool($_GET['id'] ?? '');
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
            $recordId = $_GET['record_id'] ?? '';
            $result = $bbb->regenerateRecording($_GET['id'] ?? '', $recordId);
            if ($result) {
                $message = Display::return_message(get_lang('Success'), 'success');
            } else {
                $message = Display::return_message(get_lang('Error'), 'error');
            }

            Display::addFlash($message);
            header('Location: '.$bbb->getListingUrl());
            exit;

        case 'delete_record':
            $result = $bbb->deleteRecording($_GET['id'] ?? '');
            if ($result) {
                $message = Display::return_message(get_lang('Deleted'), 'success');
            } else {
                $message = Display::return_message(get_lang('Error'), 'error');
            }

            Display::addFlash($message);
            header('Location: '.$bbb->getListingUrl());
            exit;

        case 'end':
            $bbb->endMeeting($_GET['id'] ?? '');
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
            $bbb->publishMeeting($_GET['id'] ?? '');
            Display::addFlash(Display::return_message(get_lang('Updated')));
            header('Location: '.$bbb->getListingUrl());
            exit;

        case 'unpublish':
            $bbb->unpublishMeeting($_GET['id'] ?? '');
            Display::addFlash(Display::return_message(get_lang('Updated')));
            header('Location: '.$bbb->getListingUrl());
            exit;

        case 'logout':
            if ('true' === $plugin->get('allow_regenerate_recording')) {
                $setting = api_get_course_plugin_setting('bbb', 'bbb_force_record_generation', $courseInfo);
                $allow = (int) $setting === 1;
                if ($allow) {
                    $result = $bbb->getMeetingByRemoteId($_GET['remote_id'] ?? '');
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

            $remoteId = Database::escape_string($_GET['remote_id'] ?? '');
            $meetingData = $meetingRepository->findOneByRemoteIdAndAccessUrl($remoteId, api_get_current_access_url_id());

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
        $remoteId = Database::escape_string($_GET['remote_id'] ?? '');
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
    $ajaxUrl     = api_get_path(WEB_PATH).'main/inc/ajax/plugin.ajax.php?plugin=bbb&a=list_documents&'.api_get_cidreq();
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
        'class="btn btn--primary btn-large"'
    );
    $formHtml = $form->returnForm();
    if (!empty($messagesAboveForm)) {
        $formHtml = $messagesAboveForm.$formHtml;
        $messagesAboveForm = '';
    }
    $formToString = $formHtml;
} else {
    // Fallback: plain "Enter conference" link for non-managers (if allowed by context/template).
    $urlList[] = Display::url(
        $plugin->get_lang('EnterConference'),
        $conferenceUrl,
        ['target' => '_blank', 'class' => 'btn btn--primary btn-large']
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

// Admin toolbar.
$actionLinks = '';
if (api_is_platform_admin()) {
    $dashboardUrl = api_get_path(WEB_PLUGIN_PATH).'Bbb/webhook_dashboard.php';
    $actionLinks .= Display::toolbarButton(
        $plugin->get_lang('AdminView'),
        api_get_path(WEB_PLUGIN_PATH).'Bbb/admin.php',
        'list',
        'primary'
    );

    if ($plugin->webhooksEnabled()) {
        $actionLinks .= Display::toolbarButton(
            $plugin->get_lang('ViewActivityDashboard'),
            $dashboardUrl,
            'chart-line',
            'primary'
        );
    }

    $tpl->assign('actions', Display::toolbarAction('toolbar', [$actionLinks]));
}

$tpl->assign('content', $content);
$tpl->display_one_col_template();
