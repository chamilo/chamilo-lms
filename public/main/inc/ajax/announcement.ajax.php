<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;

require_once __DIR__.'/../global.inc.php';

$action = $_REQUEST['a'] ?? null;

$isAllowedToEdit = api_is_allowed_to_edit();
$courseInfo = api_get_course_info();
$course = api_get_course_entity();
$courseId = api_get_course_int_id();
$courseCode = api_get_course_id();
$groupId = api_get_group_id();
$sessionId = api_get_session_id();

$isTutor = false;
if (!empty($groupId)) {
    $groupInfo = GroupManager::get_group_properties($groupId);
    $groupEntity = api_get_group_entity($groupId);
    $isTutor = GroupManager::isTutorOfGroup(api_get_user_id(), $groupEntity);
    if ($isTutor) {
        $isAllowedToEdit = true;
    }
}

switch ($action) {
    case 'preview':
        $allowToEdit = (
            api_is_allowed_to_edit(false, true) ||
            (1 === (int) api_get_course_setting('allow_user_edit_announcement') && !api_is_anonymous())
        );

        $drhHasAccessToSessionContent = api_drh_can_access_all_session_content();
        if (!empty($sessionId) && $drhHasAccessToSessionContent) {
            $allowToEdit = $allowToEdit || api_is_drh();
        }

        if (false === $allowToEdit && !empty($groupId)) {
            $groupEntity = api_get_group_entity($groupId);
            // Check if user is tutor group
            $isTutor = GroupManager::isTutorOfGroup(api_get_user_id(), $groupEntity);
            if ($isTutor) {
                $allowToEdit = true;
            }

            // Last chance ... students can send announcements.
            if (GroupManager::TOOL_PRIVATE_BETWEEN_USERS == $groupEntity->getAnnouncementsState()) {
                $allowToEdit = true;
            }
        }

        if (false === $allowToEdit) {
            exit;
        }

        $users = isset($_REQUEST['users']) ? json_decode($_REQUEST['users']) : '';
        $formParams = [];
        if (isset($_REQUEST['form'])) {
            parse_str($_REQUEST['form'], $formParams);
        }

        $previewGroups = [];
        $previewUsers = [];
        $previewTotal = [];
        if (empty($groupId)) {
            if (empty($users) ||
                (!empty($users) && isset($users[0]) && 'everyone' == $users[0])
            ) {
                // All users in course session
                if (empty($sessionId)) {
                    $students = CourseManager::get_user_list_from_course_code($courseInfo['code']);
                } else {
                    $students = CourseManager::get_user_list_from_course_code($courseInfo['code'], $sessionId);
                }
                foreach ($students as $student) {
                    $previewUsers[] = $student['user_id'];
                }

                $groupList = GroupManager::get_group_list(null, $course, null, $sessionId);
                foreach ($groupList as $group) {
                    $previewGroups[] = $group['iid'];
                }
            } else {
                $send_to = CourseManager::separateUsersGroups($users);
                // Storing the selected groups
                if (is_array($send_to['groups']) &&
                    !empty($send_to['groups'])
                ) {
                    $counter = 1;
                    foreach ($send_to['groups'] as $group) {
                        $previewGroups[] = $group;
                    }
                }

                // Storing the selected users
                if (is_array($send_to['users'])) {
                    $counter = 1;
                    foreach ($send_to['users'] as $user) {
                        $previewUsers[] = $user;
                    }
                }
            }
        } else {
            $send_to_users = CourseManager::separateUsersGroups($users);
            $sentToAllGroup = false;
            if (empty($send_to_users['groups']) && empty($send_to_users['users'])) {
                $previewGroups[] = $groupId;
                $sentToAllGroup = true;
            }

            if (false === $sentToAllGroup) {
                if (!empty($send_to_users['groups'])) {
                    foreach ($send_to_users['groups'] as $group) {
                        $previewGroups[] = $group;
                    }
                }

                if (!empty($send_to_users['users'])) {
                    foreach ($send_to_users['users'] as $user) {
                        $previewUsers[] = $user;
                    }
                }
            }
        }

        if (isset($formParams['send_to_users_in_session']) && 1 == $formParams['send_to_users_in_session']) {
            $sessionList = SessionManager::get_session_by_course(api_get_course_int_id());

            if (!empty($sessionList)) {
                foreach ($sessionList as $sessionInfo) {
                    $sessionId = $sessionInfo['id'];
                    $userList = CourseManager::get_user_list_from_course_code(
                        $courseCode,
                        $sessionId
                    );

                    if (!empty($userList)) {
                        foreach ($userList as $user) {
                            $previewUsers[] = $user;
                        }
                    }
                }
            }
        }

        if (isset($formParams['send_to_hrm_users']) && 1 == $formParams['send_to_hrm_users']) {
            foreach ($previewUsers as $userId) {
                $userInfo = api_get_user_info($userId);
                $drhList = UserManager::getDrhListFromUser($userId);
                if (!empty($drhList)) {
                    foreach ($drhList as $drhInfo) {
                        $previewUsers[] = $drhInfo['id'];
                    }
                }
            }
        }

        if (isset($formParams['send_me_a_copy_by_email']) && 1 == $formParams['send_me_a_copy_by_email']) {
            $previewUsers[] = api_get_user_id();
        }

        $previewUserNames = [];
        $previewGroupNames = [];

        if (!empty($previewGroups)) {
            $previewGroups = array_unique($previewGroups);
            foreach ($previewGroups as $groupId) {
                $groupInfo = GroupManager::get_group_properties($groupId);
                $previewGroupNames[] = Display::label($groupInfo['name'], 'info');
            }
            $previewTotal = $previewGroupNames;
        }

        if (!empty($previewUsers)) {
            $previewUsers = array_unique($previewUsers);
            foreach ($previewUsers as $userId) {
                $userInfo = api_get_user_info($userId);
                $previewUserNames[] = Display::label($userInfo['complete_name']);
            }
            $previewTotal = array_merge($previewTotal, $previewUserNames);
        }

        $previewTotal = array_map(function ($value) { return ''.$value; }, $previewTotal);

        echo json_encode($previewTotal);
        break;
    case 'delete_item':
        if ($isAllowedToEdit) {
            if (empty($_REQUEST['id'])) {
                return false;
            }
            if (!empty($sessionId) && false == api_is_allowed_to_session_edit(false, true) && empty($groupId)) {
                return false;
            }

            $list = explode(',', $_REQUEST['id']);

            $repo = Container::getAnnouncementRepository();
            foreach ($list as $itemId) {
                if (!api_is_session_general_coach() || api_is_element_in_the_session(TOOL_ANNOUNCEMENT, $itemId)) {
                    $announcement = $repo->find($itemId);

                    if (!empty($announcement)) {
                        $delete = true;
                        if (!empty($groupId) && $isTutor) {
                            /*if ($groupId != $result['to_group_id']) {
                                $delete = false;
                            }*/
                        }
                        if ($delete) {
                            AnnouncementManager::delete_announcement($courseInfo, $itemId);
                        }
                    }
                }
            }
        }
        break;
    default:
        echo '';
        break;
}
exit;
