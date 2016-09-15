<?php
/* For licensing terms, see /license.txt */

/**
 * Responses to AJAX calls for forum attachments
 * @package chamilo/forum
 * @author Daniel Barreto Alva <daniel.barreto@beeznest.com>
 */

require_once '../global.inc.php';
require_once api_get_path(SYS_CODE_PATH) . 'forum/forumfunction.inc.php';

// First, protect this script
api_protect_course_script(false);

/**
 * Main code
 */
// Create a default error response
$json = array(
    'error' => true,
    'errorMessage' => 'ERROR',
);
$action = isset($_REQUEST['a']) ? $_REQUEST['a'] : null;

$current_forum = get_forum_information($_REQUEST['forum']);
$current_forum_category = get_forumcategory_information($current_forum['forum_category']);
$current_thread = get_thread_information($_REQUEST['forum'], $_REQUEST['thread']);

// Check if exist action
if (!empty($action)) {
    switch ($action) {
        case 'upload_file':
            if (!empty($_FILES) && !empty($_REQUEST['forum'])) {
                // The user is not allowed here if
                // 1. the forum category, forum or thread is invisible (visibility==0)
                // 2. the forum category, forum or thread is locked (locked <>0)
                // 3. if anonymous posts are not allowed
                // The only exception is the course manager
                // They are several pieces for clarity.
                if (!api_is_allowed_to_edit(null, true) &&
                    (
                        ($current_forum_category && $current_forum_category['visibility'] == 0) ||
                        $current_forum['visibility'] == 0
                    )
                ) {
                    $json['errorMessage'] = '1. the forum category, forum or thread is invisible (visibility==0)';
                    break;
                }
                if (!api_is_allowed_to_edit(null, true) &&
                    (
                        ($current_forum_category && $current_forum_category['locked'] <> 0) ||
                        $current_forum['locked'] <> 0 || $current_thread['locked'] <> 0
                    )
                ) {
                    $json['errorMessage'] = '2. the forum category, forum or thread is locked (locked <>0)';
                    break;
                }
                if (api_is_anonymous() && $current_forum['allow_anonymous'] == 0) {
                    $json['errorMessage'] = '3. if anonymous posts are not allowed';
                    break;
                }
                // If pass all previous control, user can edit post
                $courseId = isset($_REQUEST['c_id'])? intval($_REQUEST['c_id']) : api_get_course_int_id();
                $json['courseId'] = $courseId;
                $forumId = isset($_REQUEST['forum'])? intval($_REQUEST['forum']) : null;
                $json['forum'] = $forumId;
                $threadId = isset($_REQUEST['thread'])? intval($_REQUEST['thread']) : null;
                $json['thread'] = $threadId;
                $postId = isset($_REQUEST['postId'])? intval($_REQUEST['postId']) : null;
                $json['postId'] = $postId;

                if (!empty($courseId) &&
                    !is_null($forumId) &&
                    !is_null($threadId) &&
                    !is_null($postId)
                ) {
                    // Save forum attachment
                    $attachId = add_forum_attachment_file('', $postId);
                    if ($attachId !== false) {
                        // Get prepared array of attachment data
                        $array = getAttachedFiles(
                            $forumId,
                            $threadId,
                            $postId,
                            $attachId,
                            $courseId
                        );
                        // Check if array data is consistent
                        if (isset($array['name'])) {
                            $json['error'] = false;
                            $json['errorMessage'] = 'Success';
                            $json = array_merge($json, $array);
                        }
                    }
                }
            }
            break;
        case 'delete_file':
            // Check if set attachment ID and thread ID
            if (isset($_REQUEST['attachId']) && isset($_REQUEST['thread'])) {
                api_block_course_item_locked_by_gradebook($_REQUEST['thread'], LINK_FORUM_THREAD);
                // The user is not allowed here if
                // 1. the forum category, forum or thread is invisible (visibility==0)
                // 2. the forum category, forum or thread is locked (locked <>0)
                // 3. if anonymous posts are not allowed
                // 4. if editing of replies is not allowed
                // The only exception is the course manager
                // They are several pieces for clarity.
                if (!api_is_allowed_to_edit(null, true) AND
                    (
                        ($current_forum_category && $current_forum_category['visibility'] == 0) OR
                        $current_forum['visibility'] == 0)
                ) {
                    $json['errorMessage'] = '1. the forum category, forum or thread is invisible (visibility==0)';
                    break;
                }
                if (!api_is_allowed_to_edit(null, true) AND
                    (
                        ($current_forum_category && $current_forum_category['locked'] <> 0) OR
                        $current_forum['locked'] <> 0 OR $current_thread['locked'] <> 0
                    )
                ) {
                    $json['errorMessage'] = '2. the forum category, forum or thread is locked (locked <>0)';
                    break;
                }
                if (api_is_anonymous() AND $current_forum['allow_anonymous'] == 0) {
                    $json['errorMessage'] = '3. if anonymous posts are not allowed';
                    break;
                }
                $group_id = api_get_group_id();
                $groupInfo = GroupManager::get_group_properties($group_id);
                if (!api_is_allowed_to_edit(null, true) &&
                    $current_forum['allow_edit'] == 0 &&
                    ($group_id && !GroupManager::is_tutor_of_group(api_get_user_id(), $groupInfo['iid']))
                ) {
                    $json['errorMessage'] = '4. if editing of replies is not allowed';
                    break;
                }
                // If pass all previous control, user can edit post
                $attachId = $_REQUEST['attachId'];
                $threadId = $_REQUEST['thread'];
                // Delete forum attachment from database and file system
                $affectedRows = delete_attachment(0, $attachId, false);
                if ($affectedRows > 0) {
                    $json['error'] = false;
                    $json['errorMessage'] = 'Success';
                }
            }
            break;
    }
}

echo json_encode($json);
exit;
