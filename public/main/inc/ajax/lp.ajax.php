<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Entity\CForum;
use Chamilo\CourseBundle\Entity\CForumCategory;
use Chamilo\CourseBundle\Entity\CForumThread;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CLpCategory;
use Chamilo\CourseBundle\Entity\CLpItem;
use ChamiloSession as Session;

require_once __DIR__.'/../global.inc.php';
api_protect_course_script(true);

$debug = false;
$action = $_REQUEST['a'] ?? '';
$lpId = $_REQUEST['lp_id'] ?? 0;
$lpRepo = Container::getLpRepository();
$lpItemRepo = Container::getLpItemRepository();
$lp = null;
if (!empty($lpId)) {
    $lpId = (int) $lpId;
    /** @var CLp $lp */
    $lp = $lpRepo->find($lpId);
}

$courseId = api_get_course_int_id();
$sessionId = api_get_session_id();

switch ($action) {
    case 'get_lp_export_items':
        // Close the session as we don't need it any further
        session_write_close();
        $lpItems = [];
        if ($lp) {
            $entries      = learnpath::get_flat_ordered_items_list($lp, 0, true);
            $lpItemRepo   = Container::getLpItemRepository();
            $documentRepo = Container::getDocumentRepository();

            foreach ($entries as $entry) {
                $iid           = (int) $entry['iid'];
                $exportAllowed = !empty($entry['export_allowed']);
                if (!$exportAllowed) {
                    continue;
                }

                /** @var CLpItem|null $item */
                $item = $lpItemRepo->find($iid);
                if (!$item || $item->getItemType() !== 'document') {
                    continue;
                }

                $doc = $documentRepo->find((int) $item->getPath());
                if (!$doc instanceof CDocument) {
                    continue;
                }

                try {
                    $content = $documentRepo->getResourceFileContent($doc);
                    if (!is_string($content) || stripos(ltrim($content), '<') !== 0) {
                        continue;
                    }

                    $lpItems[] = [
                        'id'    => $item->getIid(),
                        'title' => $item->getTitle(),
                    ];
                } catch (\Throwable $e) {
                    // Skip silently
                }
            }
        }

        header('Content-Type: application/json');
        echo json_encode(['items' => $lpItems], JSON_UNESCAPED_UNICODE);
        exit;
    case 'get_lp_list_by_course':
        $course_id = (isset($_GET['course_id']) && !empty($_GET['course_id'])) ? (int) $_GET['course_id'] : 0;
        $session_id = (isset($_GET['session_id']) && !empty($_GET['session_id'])) ? (int) $_GET['session_id'] : 0;
        $onlyActiveLp = !(api_is_platform_admin(true) || api_is_course_admin());
        $course = api_get_course_entity();
        $session = api_get_session_entity();
        // Close the session as we don't need it any further
        session_write_close();

        $active = null;
        if ($onlyActiveLp) {
            $active = 1;
        }

        $qb = $lpRepo->findAllByCourse($course, $session, null, $active);
        $lps = $qb->getQuery()->getResult();
        $data = [];
        if (!empty($lps)) {
            foreach ($lps as $lp) {
                $data[] = ['id' => $lp->getIid(), 'text' => html_entity_decode($lp->getTitle())];
            }
        }
        echo json_encode($data);
        break;
    case 'get_documents':
        $folderId = isset($_GET['folder_id']) ? $_GET['folder_id'] : null;
        if (empty($folderId)) {
            exit;
        }
        $url = isset($_GET['url']) ? $_GET['url'] : '';
        $addMove = isset($_GET['add_move_button']) && 1 == $_GET['add_move_button'] ? true : false;

        echo DocumentManager::get_document_preview(
            api_get_course_entity(),
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

        if (null === $lp) {
            exit;
        }

        $parent = $lpItemRepo->getRootItem($lpId);

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
                case 'video':
                    $repo = Container::getDocumentRepository();
                    /** @var CDocument $document */
                    $document = $repo->getResourceFromResourceNode($id);
                    $id = $document->getIid();
                    $title = $document->getTitle();
                    break;
            }

            $parentId = (int) ($_REQUEST['parent_id'] ?? null);
            $em = Database::getManager();
            if (!empty($parentId)) {
                $parent = $lpItemRepo->find($parentId);
            }

            $previousId = $_REQUEST['previous_id'] ?? 0;

            $itemId = $learningPath->add_item(
                $parent,
                $previousId,
                $type,
                $id,
                $title
            );

            echo $itemId;
            exit;
            //echo $learningPath->getBuildTree(true);
        }
        break;
    case 'update_lp_item_order':
        if (api_is_allowed_to_edit(null, true)) {
            // Close the session as we don't need it any further
            session_write_close();
            $newOrder = $_REQUEST['new_order'] ?? [];
            $orderList = json_decode($newOrder);
            if (empty($orderList)) {
                exit;
            }

            $lpItemRepo = Container::getLpItemRepository();
            $rootItem = $lpItemRepo->getRootItem($lpId);
            learnpath::sortItemByOrderList($rootItem, $orderList);

            echo Display::return_message(get_lang('Saved'), 'confirm');
        }
        break;
    case 'get_lp_item_tree':
        if (api_is_allowed_to_edit(null, true)) {
            $parent = $lpItemRepo->getRootItem($lpId);
            $learningPath = new learnpath($lp, api_get_course_info(), api_get_user_id());
            if ($learningPath) {
                echo $learningPath->getBuildTree(true, true);
            }
        }
        exit;
        break;
    case 'delete_item':
        if (api_is_allowed_to_edit(null, true)) {
            $learningPath = new learnpath($lp, api_get_course_info(), api_get_user_id());
            $learningPath->delete_item($_REQUEST['id']);
        }
        exit;
        break;
    case 'record_audio':
        if (false == api_is_allowed_to_edit(null, true)) {
            exit;
        }

        $learningPath = new learnpath($lp, api_get_course_info(), api_get_user_id());
        $course_info = api_get_course_info();
        $lpPathInfo = $learningPath->generate_lp_folder($course_info);
        // Close the session as we don't need it any further
        session_write_close();

        if (empty($lpPathInfo)) {
            exit;
        }

        foreach (['video', 'audio'] as $type) {
            if (isset($_FILES["{$type}-blob"])) {
                $fileName = $_POST["{$type}-filename"];
                $file = $_FILES["{$type}-blob"];
                $title = $_POST['audio-title'];
                $fileInfo = pathinfo($fileName);

                $file['name'] = $title.'.'.$fileInfo['extension'];
                $file['file'] = $file;

                /*$result = DocumentManager::upload_document(
                    $file,
                    '/audio',
                    $file['name'],
                    null,
                    0,
                    'overwrite',
                    false,
                    false
                );*/

                if (!empty($result) && is_array($result)) {
                    $newDocId = $result['id'];
                    $courseId = $result['c_id'];
                    $learningPath->set_modified_on();
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
        $forumCategoryRepo = Container::getForumCategoryRepository();
        $lpRepo = Container::getLpRepository();
        $forumRepo = Container::getForumRepository();
        $lpItemRepo = Container::getLpItemRepository();
        $forumThreadRepo = Container::getForumThreadRepository();

        $lpItemId = isset($_GET['lp_item']) ? intval($_GET['lp_item']) : 0;
        $sessionId = api_get_session_id();
        $course = api_get_course_entity();
        $session = api_get_session_entity();
        // Close the session as we don't need it any further
        session_write_close();

        /** @var CLpItem $lpItem */
        $lpItem = $lpItemRepo->find($lpItemId);

        if (empty($lpId) || empty($lpItemId)) {
            echo json_encode([
                'error' => true,
            ]);

            break;
        }

        $lp = $lpRepo->find($lpId);
        $forum = $lpRepo->findForumByCourse($lp, $course);

        if (null === $forum) {
            /** @var CForumCategory|null $forumCategory */
            $forumCategory = $forumCategoryRepo->getForumCategoryByTitle(
                get_lang('Learning paths'),
                $course,
                $session
            );

            if (null === $forumCategory) {
                $forumCategory = new CForumCategory();
                $forumCategory
                    ->setTitle(get_lang('Learning paths'))
                    ->setParent($course)
                    ->addCourseLink($course, $session)
                ;
                $forumCategoryRepo->create($forumCategory);
            }

            $forum = new CForum();
            $forum
                ->setTitle('forum2')
                ->setForumCategory($forumCategory)
                ->setParent($course)
                ->setLp($lp)
                ->setCreator(api_get_user_entity())
                ->addCourseLink($course, $session)
            ;
            $forumRepo->create($forum);
        }

        $title = $lpItem->getTitle().' '.$lpItem->getIid();
        $thread = $forumThreadRepo->getForumThread($title, $course);

        if (null === $thread) {
            $thread = new CForumThread();
            $thread
                ->setTitle($title)
                ->setForum($forum)
                ->setUser(api_get_user_entity())
                ->setParent($forum)
                ->setItem($lpItem)
                ->addCourseLink($course, $session)
            ;
            $forumThreadRepo->create($thread);
        }

        $forumThreadId = $thread->getIid();

        echo json_encode([
            'error' => false,
            'forumId' => $forum->getIid(),
            'threadId' => $forumThreadId,
        ]);
        break;
    case 'update_gamification':
        // moved inside lp_nav.php
        exit;
        $lp = Session::read('oLP');

        break;
    case 'check_item_position':
        // loaded in lp_nav.php
        exit;
        /** @var learnpath $lp */
        $lp = Session::read('oLP');
        // Close the session as we don't need it any further
        session_write_close();
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
        // Close the session as we don't need it any further
        session_write_close();
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
    case 'add_lp_ai':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'text' => 'Invalid request method.']);
            exit;
        }

        $jsonInput = file_get_contents('php://input');
        $requestData = json_decode($jsonInput, true);

        if (!isset($requestData['lp_data']) || !isset($requestData['course_code'])) {
            echo json_encode(['success' => false, 'text' => 'Invalid AI response data.']);
            exit;
        }

        require_once api_get_path(SYS_CODE_PATH).'lp/LpAiHelper.php';

        $aiHelper = new LpAiHelper();
        $result = $aiHelper->createLearningPathFromAI($requestData['lp_data'], $requestData['course_code']);

        if (!isset($result['lp_id'])) {
            $result['success'] = false;
            $result['text'] = 'Learning Path created but no ID returned.';
        }

        echo json_encode($result);
        exit;
    case 'filter_visible_lp_categories':
        header('Content-Type: application/json; charset=utf-8');

        $idsParam  = isset($_GET['ids']) ? (string) $_GET['ids'] : '';
        $ids       = array_filter(array_map('intval', explode(',', $idsParam)));
        $courseId  = isset($_GET['cid']) ? (int) $_GET['cid'] : (int) api_get_course_int_id();
        $sessionId = isset($_GET['sid']) ? (int) $_GET['sid'] : (int) api_get_session_id();

        $course  = api_get_course_entity($courseId);
        $session = $sessionId ? api_get_session_entity($sessionId) : null;
        $user    = api_get_user_entity();

        $repo = Container::getLpCategoryRepository();

        $visibleIds = [];
        foreach ($ids as $id) {
            /** @var CLpCategory|null $cat */
            $cat = $repo->find($id);
            if (!$cat) {
                continue;
            }

            if (learnpath::categoryIsVisibleForStudent($cat, $user, $course, $session)) {
                $visibleIds[] = (int) $id;
            }
        }

        echo json_encode(['ids' => $visibleIds], JSON_UNESCAPED_UNICODE);
        exit;
    case 'lp_visibility_map':
        header('Content-Type: application/json; charset=UTF-8');

        $courseId  = isset($_GET['cid']) ? (int) $_GET['cid'] : api_get_course_int_id();
        $sessionId = isset($_GET['sid']) ? (int) $_GET['sid'] : api_get_session_id();

        $course  = api_get_course_entity($courseId);
        $session = $sessionId ? Container::getSessionRepository()->find($sessionId) : null;

        $rawIds = (string) ($_GET['lp_ids'] ?? '');
        $ids = array_values(array_filter(array_map('intval', preg_split('/[,\s]+/', $rawIds)), static fn($x) => $x > 0));

        $repo = Container::getLpRepository();
        $userId = api_get_user_id();

        $map = [];
        foreach ($ids as $id) {
            $lp = $repo->find($id);
            if (!$lp) {
                $map[(string) $id] = false;
                continue;
            }
            $map[(string) $id] = (bool) learnpath::is_lp_visible_for_student($lp, $userId, $course, $session);
        }

        echo json_encode(['map' => $map], JSON_UNESCAPED_UNICODE);
        exit;
    default:
        echo '';
}
