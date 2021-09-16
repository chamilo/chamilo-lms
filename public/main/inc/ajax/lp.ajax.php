<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Entity\CForum;
use Chamilo\CourseBundle\Entity\CForumCategory;
use Chamilo\CourseBundle\Entity\CForumThread;
use Chamilo\CourseBundle\Entity\CLp;
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
    case 'get_lp_list_by_course':
        $course_id = (isset($_GET['course_id']) && !empty($_GET['course_id'])) ? (int) $_GET['course_id'] : 0;
        $session_id = (isset($_GET['session_id']) && !empty($_GET['session_id'])) ? (int) $_GET['session_id'] : 0;
        $onlyActiveLp = !(api_is_platform_admin(true) || api_is_course_admin());
        $course = api_get_course_entity();
        $session = api_get_session_entity();
        $active = null;
        if ($onlyActiveLp) {
            $active = 1;
        }

        $qb = $lpRepo->findAllByCourse($course, $session, null, $active);
        $lps = $qb->getQuery()->getResult();
        $data = [];
        if (!empty($lps)) {
            foreach ($lps as $lp) {
                $data[] = ['id' => $lp->getIid(), 'text' => html_entity_decode($lp->getName())];
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
                    ->setCatTitle(get_lang('Learning paths'))
                    ->setParent($course)
                    ->addCourseLink($course, $session)
                ;
                $forumCategoryRepo->create($forumCategory);
            }

            $forum = new CForum();
            $forum
                ->setForumTitle('forum2')
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
                ->setThreadTitle($title)
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
