<?php
/* For licensing terms, see /license.txt */

/**
 * Responses to AJAX calls.
 */
require_once __DIR__.'/../global.inc.php';

$action = $_REQUEST['a'] ?? null;

$isAllowedToEdit = api_is_allowed_to_edit();
$courseInfo = api_get_course_info();
$courseCode = api_get_course_id();
$courseId = api_get_course_int_id();
$groupId = api_get_group_id();
$sessionId = api_get_session_id();

$isTutor = false;
if (!empty($groupId)) {
    $groupInfo = GroupManager::get_group_properties($groupId);
    $isTutor = GroupManager::is_tutor_of_group(api_get_user_id(), $groupInfo);
    if ($isTutor) {
        $isAllowedToEdit = true;
    }
}

switch ($action) {
    case 'preview':
        $allowToEdit = (
            api_is_allowed_to_edit(false, true) ||
            (api_get_course_setting('allow_user_edit_announcement') && !api_is_anonymous()) ||
            ($sessionId && api_is_coach() && api_get_configuration_value('allow_coach_to_edit_announcements'))
        );

        $drhHasAccessToSessionContent = api_drh_can_access_all_session_content();
        if (!empty($sessionId) && $drhHasAccessToSessionContent) {
            $allowToEdit = $allowToEdit || api_is_drh();
        }

        if ($allowToEdit === false && !empty($groupId)) {
            $groupProperties = GroupManager::get_group_properties($groupId);
            // Check if user is tutor group
            $isTutor = GroupManager::is_tutor_of_group(api_get_user_id(), $groupProperties, $courseId);
            if ($isTutor) {
                $allowToEdit = true;
            }

            // Last chance ... students can send announcements.
            if ($groupProperties['announcements_state'] == GroupManager::TOOL_PRIVATE_BETWEEN_USERS) {
                $allowToEdit = true;
            }
        }

        if ($allowToEdit === false) {
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
                (!empty($users) && isset($users[0]) && $users[0] == 'everyone')
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

                $groupList = GroupManager::get_group_list(null, $courseInfo, null, $sessionId);
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

            if ($sentToAllGroup === false) {
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

        if (isset($formParams['send_to_users_in_session']) && $formParams['send_to_users_in_session'] == 1) {
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

        if (isset($formParams['send_to_hrm_users']) && $formParams['send_to_hrm_users'] == 1) {
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

        if (isset($formParams['send_me_a_copy_by_email']) && $formParams['send_me_a_copy_by_email'] == 1) {
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
            if (!empty($sessionId) && api_is_allowed_to_session_edit(false, true) == false && empty($groupId)) {
                return false;
            }

            $list = explode(',', $_REQUEST['id']);
            foreach ($list as $itemId) {
                if (!api_is_session_general_coach() || api_is_element_in_the_session(TOOL_ANNOUNCEMENT, $itemId)) {
                    $result = AnnouncementManager::get_by_id(
                        api_get_course_int_id(),
                        $itemId
                    );
                    if (!empty($result)) {
                        $delete = true;
                        if (!empty($groupId) && $isTutor) {
                            if ($groupId != $result['to_group_id']) {
                                $delete = false;
                            }
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
