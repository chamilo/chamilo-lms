<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CDocument;
use ChamiloSession as Session;

require_once __DIR__.'/../global.inc.php';
api_protect_course_script(true);

$debug = false;
$action = $_REQUEST['a'] ?? '';
$lpId = $_REQUEST['lp_id'] ?? 0;

$courseId = api_get_course_int_id();
$sessionId = api_get_session_id();


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
        $url = isset($_GET['url']) ? $_GET['url'] : '';
        $addMove = isset($_GET['add_move_button']) && 1 == $_GET['add_move_button'] ? true : false;

        echo DocumentManager::get_document_preview(
            $courseInfo,
            $lpId,
            null,
            api_get_session_id(),
            $addMove,
            null,
            $url,
            true,
            false,
            $folderId,
            false
        );
        break;
    case 'add_lp_item':
        if (!api_is_allowed_to_edit(null, true)) {
            exit;
        }

        $lpRepo = Container::getLpRepository();
        $lp = $lpRepo->find($lpId);
        if (null === $lp) {
            exit;
        }

        /** @var learnpath $learningPath */
        //$learningPath = learnpath::read('oLP');
        $learningPath = new learnpath($lp, api_get_course_info(), api_get_user_id());
        if ($learningPath) {
            $learningPath->set_modified_on();
            $title = $_REQUEST['title'] ?? '';
            $type = $_REQUEST['type'] ?? '';
            $id = $_REQUEST['id'] ?? 0;
            switch ($type) {
                case TOOL_QUIZ:
                    $title = Exercise::format_title_variable($title);
                    break;
                case TOOL_DOCUMENT:
                    $repo = Container::getDocumentRepository();
                    /** @var CDocument $document */
                    $document = $repo->getResourceFromResourceNode($id);
                    $id = $document->getIid();
                    $title = $document->getTitle();
                    break;
            }

            $parentId = $_REQUEST['parent_id'] ?? '';
            $previousId = $_REQUEST['previous_id'] ?? '';

            $itemId = $learningPath->add_item(
                $parentId,
                $previousId,
                $type,
                $id,
                $title,
                ''
            );

            echo $learningPath->returnLpItemList(null);
        }
        break;
    case 'update_lp_item_order':
        if (api_is_allowed_to_edit(null, true)) {
            // $new_order gets a value like "647|0^648|0^649|0^"
            $new_order = $_POST['new_order'];
            $sections = explode('^', $new_order);
            $sections = array_filter($sections);
            // We have to update parent_item_id, previous_item_id, next_item_id, display_order in the database
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
        if (false == api_is_allowed_to_edit(null, true)) {
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
        // @todo fix get forum thread
        echo json_encode([
            'error' => true,
        ]);
        break;

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
        // @todo fix get forum
        //$forum = $learningPath->getForum($sessionId);
        $forum = false;

        if (empty($forum)) {
            $forumCategory = getForumCategoryByTitle(
                get_lang('Learning paths'),
                $courseId,
                $sessionId
            );

            if (empty($forumCategory)) {
                $forumCategory = store_forumcategory(
                    [
                        'lp_id' => 0,
                        'forum_category_title' => get_lang('Learning paths'),
                        'forum_category_comment' => null,
                    ],
                    [],
                    false
                );
            }

            $forumId = $learningPath->createForum($forumCategory);
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
            $jsonGamification['score'] = sprintf(get_lang('%s points'), $score);
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
        $result = $lp->prerequisites_match($itemId);
        if ($result) {
            echo '1';
        } else {
            if (!empty($lp->error)) {
                echo $lp->error;
            } else {
                echo get_lang('This learning object cannot display because the course prerequisites are not completed. This happens when a course imposes that you follow it step by step or get a minimum score in tests before you reach the next steps.');
            }
        }
        $lp->error = '';
        exit;

        break;
    default:
        echo '';
}
