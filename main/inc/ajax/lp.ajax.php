<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * Responses to AJAX calls.
 */
require_once __DIR__.'/../global.inc.php';
api_protect_course_script(true);

$debug = false;
$action = isset($_REQUEST['a']) ? $_REQUEST['a'] : '';

$courseId = api_get_course_int_id();
$sessionId = api_get_session_id();

if ($debug) {
    error_log('----------lp.ajax-------------- action '.$action);
}

// We check if a tool provider
if (isset($_REQUEST['lti_launch_id'])) {
    $ltiLaunchId = Security::remove_XSS($_REQUEST['lti_launch_id']);
    $_SESSION['oLP']->lti_launch_id = $ltiLaunchId;
}

switch ($action) {
    case 'get_lp_list_by_course':
        $course_id = (isset($_GET['course_id']) && !empty($_GET['course_id'])) ? (int) $_GET['course_id'] : 0;
        $session_id = (isset($_GET['session_id']) && !empty($_GET['session_id'])) ? (int) $_GET['session_id'] : 0;
        $onlyActiveLp = !(api_is_platform_admin(true) || api_is_course_admin());
        $results = learnpath::getLpList($course_id, $session_id, $onlyActiveLp);
        $data = [];

        if (!empty($results)) {
            foreach ($results as $lp) {
                $data[] = ['id' => $lp['id'], 'text' => html_entity_decode($lp['name'])];
            }
        }

        echo json_encode($data);
        break;
    case 'get_documents':
        $courseInfo = api_get_course_info();
        $folderId = isset($_GET['folder_id']) ? $_GET['folder_id'] : null;
        if (empty($folderId)) {
            exit;
        }
        $lpId = isset($_GET['lp_id']) ? $_GET['lp_id'] : false;
        $url = isset($_GET['url']) ? $_GET['url'] : '';
        $addMove = isset($_GET['add_move_button']) && $_GET['add_move_button'] == 1 ? true : false;
        $showOnlyFolders = false;
        if (isset($_GET['showOnlyFolders'])) {
            $showOnlyFolders = (1 == (int) $_GET['showOnlyFolders']);
        }
        echo DocumentManager::get_document_preview(
            $courseInfo,
            $lpId,
            null,
            api_get_session_id(),
            $addMove,
            null,
            $url,
            true,
            $showOnlyFolders,
            $folderId,
            false
        );
        break;
    case 'add_lp_item':
        if (api_is_allowed_to_edit(null, true)) {
            /** @var learnpath $learningPath */
            $learningPath = Session::read('oLP');
            if ($learningPath) {
                // Updating the lp.modified_on
                $learningPath->set_modified_on();
                $title = $_REQUEST['title'];
                if ($_REQUEST['type'] == TOOL_QUIZ) {
                    $title = Exercise::format_title_variable($title);
                }

                $parentId = isset($_REQUEST['parent_id']) ? $_REQUEST['parent_id'] : '';
                $previousId = isset($_REQUEST['previous_id']) ? $_REQUEST['previous_id'] : '';

                $itemId = $learningPath->add_item(
                    $parentId,
                    $previousId,
                    $_REQUEST['type'],
                    $_REQUEST['id'],
                    $title,
                    null
                );

                /** @var learnpath $learningPath */
                $learningPath = Session::read('oLP');
                if ($learningPath) {
                    echo $learningPath->returnLpItemList(null);
                }
            }
        }
        break;
    case 'update_lp_item_order':
        if (api_is_allowed_to_edit(null, true)) {
            // $new_order gets a value like "647|0^648|0^649|0^"
            $new_order = $_POST['new_order'];
            $sections = explode('^', $new_order);
            $sections = array_filter($sections);

            $orderList = [];

            foreach ($sections as $items) {
                [$id, $parentId] = explode('|', $items);

                $orderList[$id] = $parentId;
            }

            learnpath::sortItemByOrderList($orderList);

            echo Display::return_message(get_lang('Saved'), 'confirm');
        }
        break;
    case 'record_audio':
        if (api_is_allowed_to_edit(null, true) == false) {
            exit;
        }
        /** @var Learnpath $lp */
        $lp = Session::read('oLP');
        $course_info = api_get_course_info();

        $lpPathInfo = $lp->generate_lp_folder($course_info);

        if (empty($lpPathInfo)) {
            exit;
        }

        foreach (['video', 'audio'] as $type) {
            if (isset($_FILES["${type}-blob"])) {
                $fileName = $_POST["${type}-filename"];
                $file = $_FILES["${type}-blob"];
                $title = $_POST['audio-title'];
                $fileInfo = pathinfo($fileName);
                //$file['name'] = 'rec_'.date('Y-m-d_His').'_'.uniqid().'.'.$fileInfo['extension'];
                $file['name'] = $title.'.'.$fileInfo['extension'];
                $file['file'] = $file;

                $result = DocumentManager::upload_document(
                    $file,
                    '/audio',
                    $file['name'],
                    null,
                    0,
                    'overwrite',
                    false,
                    false
                );

                if (!empty($result) && is_array($result)) {
                    $newDocId = $result['id'];
                    $courseId = $result['c_id'];

                    $lp->set_modified_on();

                    $lpItem = new learnpathItem($_REQUEST['lp_item_id']);
                    $lpItem->add_audio_from_documents($newDocId);
                    $data = DocumentManager::get_document_data_by_id($newDocId, $course_info['code']);
                    echo $data['document_url'];
                    exit;
                }
            }
        }

        break;
    case 'get_forum_thread':
        $lpId = isset($_GET['lp']) ? intval($_GET['lp']) : 0;
        $lpItemId = isset($_GET['lp_item']) ? intval($_GET['lp_item']) : 0;
        $sessionId = api_get_session_id();

        if (empty($lpId) || empty($lpItemId)) {
            echo json_encode([
                'error' => true,
            ]);

            break;
        }

        $learningPath = learnpath::getLpFromSession(
            api_get_course_id(),
            $lpId,
            api_get_user_id()
        );
        $lpItem = $learningPath->getItem($lpItemId);

        if (empty($lpItem)) {
            echo json_encode([
                'error' => true,
            ]);
            break;
        }

        $lpHasForum = $learningPath->lpHasForum();

        if (!$lpHasForum) {
            echo json_encode([
                'error' => true,
            ]);
            break;
        }

        $forum = $learningPath->getForum($sessionId);

        if (empty($forum)) {
            require_once '../../forum/forumfunction.inc.php';
            $forumCategory = getForumCategoryByTitle(
                get_lang('LearningPaths'),
                $courseId,
                $sessionId
            );

            if (empty($forumCategory)) {
                $forumCategoryId = store_forumcategory(
                    [
                        'lp_id' => 0,
                        'forum_category_title' => get_lang('LearningPaths'),
                        'forum_category_comment' => null,
                    ],
                    [],
                    false
                );
            } else {
                $forumCategoryId = $forumCategory['cat_id'];
            }

            $forumId = $learningPath->createForum($forumCategoryId);
        } else {
            $forumId = $forum['forum_id'];
        }

        $lpItemHasThread = $lpItem->lpItemHasThread($courseId);

        if (!$lpItemHasThread) {
            echo json_encode([
                'error' => true,
            ]);
            break;
        }

        $forumThread = $lpItem->getForumThread($courseId, $sessionId);
        if (empty($forumThread)) {
            $lpItem->createForumThread($forumId);
            $forumThread = $lpItem->getForumThread($courseId, $sessionId);
        }

        $forumThreadId = $forumThread['thread_id'];

        echo json_encode([
            'error' => false,
            'forumId' => intval($forum['forum_id']),
            'threadId' => intval($forumThreadId),
        ]);
        break;
    case 'update_gamification':
        $lp = Session::read('oLP');

        $jsonGamification = [
            'stars' => 0,
            'score' => 0,
        ];

        if ($lp) {
            $score = $lp->getCalculateScore($sessionId);
            $jsonGamification['stars'] = $lp->getCalculateStars($sessionId);
            $jsonGamification['score'] = sprintf(get_lang('XPoints'), $score);
        }

        echo json_encode($jsonGamification);
        break;
    case 'check_item_position':
        $lp = Session::read('oLP');
        $lpItemId = isset($_GET['lp_item']) ? intval($_GET['lp_item']) : 0;
        if ($lp) {
            $position = $lp->isFirstOrLastItem($lpItemId);
            echo json_encode($position);
        }
        break;
    case 'get_parent_names':
        $newItemId = isset($_GET['new_item']) ? intval($_GET['new_item']) : 0;

        if (!$newItemId) {
            break;
        }

        /** @var \learnpath $lp */
        $lp = Session::read('oLP');
        $parentNames = $lp->getCurrentItemParentNames($newItemId);
        $response = '';
        foreach ($parentNames as $parentName) {
            $response .= '<p class="h5 hidden-xs hidden-md">'.$parentName.'</p>';
        }

        echo $response;
        break;
    case 'get_item_prerequisites':
        /** @var learnpath $lp */
        $lp = Session::read('oLP');
        $itemId = isset($_GET['item_id']) ? (int) $_GET['item_id'] : 0;
        if (empty($lp) || empty($itemId)) {
            exit;
        }
        if ($lp->debug) {
            error_log('--------------------------------------');
            error_log('get_item_prerequisites');
        }

        $result = $lp->prerequisites_match($itemId);
        if ($result) {
            echo '1';
        } else {
            if (!empty($lp->error)) {
                echo $lp->error;
            } else {
                echo get_lang('LearnpathPrereqNotCompleted');
            }
        }
        $lp->error = '';
        exit;

        break;
    default:
        echo '';
}
