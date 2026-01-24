<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CLpItem;
use ChamiloSession as Session;

/**
 * Controller script. Prepares the common background variables to give to the scripts corresponding to
 * the requested action.
 *
 * @todo remove repeated if $lp_found redirect
 *
 * @author Yannick Warnier <ywarnier@beeznest.org>
 */

// Flag to allow for anonymous user - needs to be set before global.inc.php.
$use_anonymous = true;

require_once __DIR__.'/../inc/global.inc.php';

api_protect_course_script(true);
$oLP = null;
$debug = false;
$current_course_tool = TOOL_LEARNPATH;
$lpItemId = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;
$lpId = isset($_REQUEST['lp_id']) ? (int) $_REQUEST['lp_id'] : 0;
$courseId = isset($_REQUEST['cid']) ? (int) $_REQUEST['cid'] : api_get_course_int_id();
$sessionId = isset($_REQUEST['sid']) ? (int) $_REQUEST['sid'] : api_get_session_id();
$lpRepo = Container::getLpRepository();
$lpItemRepo = Container::getLpItemRepository();
$courseInfo = api_get_course_info_by_id($courseId);
$course = api_get_course_entity($courseId);
$userId = api_get_user_id();

$nodeId = 0;
if (isset($course) && method_exists($course, 'getResourceNode') && $course->getResourceNode()) {
    $nodeId = (int) $course->getResourceNode()->getId();
}
$qs = [
    'cid' => (int) $courseId,
];
if (!empty($sessionId)) {
    $qs['sid'] = $sessionId;
}
if (isset($_GET['gid'])) {
    $qs['gid'] = (int) $_GET['gid'];
}
if (isset($_GET['gradebook'])) {
    $qs['gradebook'] = (int) $_GET['gradebook'];
}
if (isset($_GET['isStudentView'])) {
    $qs['isStudentView'] = Security::remove_XSS($_GET['isStudentView']);
}
$listUrl = api_get_path(WEB_PATH).'resources/lp/'.$nodeId.'?'.http_build_query($qs);
$glossaryExtraTools        = api_get_setting('glossary.show_glossary_in_extra_tools');
$glossaryDocumentsMode     = api_get_setting('document.show_glossary_in_documents');
$glossaryDocumentsEnabled  = in_array(
    $glossaryDocumentsMode,
    ['ismanual', 'isautomatic'],
    true
);

$showGlossary = $glossaryDocumentsEnabled && in_array(
        $glossaryExtraTools,
        ['true', 'lp', 'exercise_and_lp'],
        true
    );

if ($showGlossary) {
    $htmlHeadXtra[] = api_get_glossary_auto_snippet(
        (int) $courseId,
        $sessionId ?: null,
        null
    );
}

$ajax_url = api_get_path(WEB_AJAX_PATH).'lp.ajax.php?lp_id='.$lpId.'&'.api_get_cidreq();

$lpfound = false;
$myrefresh = 0;
$myrefresh_id = 0;
$refresh = Session::read('refresh');
if (1 == $refresh) {
    // Check if we should do a refresh of the oLP object (for example after editing the LP).
    // If refresh is set, we regenerate the oLP object from the database (kind of flush).
    Session::erase('refresh');
    $myrefresh = 1;
}

$lp_controller_touched = 1;
$lp_found = false;
$lpObject = Session::read('lpobject');
if (!empty($lpObject)) {
    $oLP = UnserializeApi::unserialize('lp', $lpObject);
    if (isset($oLP) && is_object($oLP)) {
        if (1 == $myrefresh ||
            empty($oLP->cc) ||
            $oLP->cc != $course->getCode() ||
            $oLP->lp_view_session_id != $sessionId
        ) {
            if ($debug) {
                error_log('Course has changed, discard lp object');
                error_log('$oLP->lp_view_session_id: '.$oLP->lp_view_session_id);
                error_log('api_get_session_id(): '.$sessionId);
                error_log('$oLP->cc: '.$oLP->cc);
                error_log('api_get_course_id(): '.$course->getCode());
            }

            if (1 === $myrefresh) {
                $myrefresh_id = $oLP->get_id();
            }
            $oLP = null;
            Session::erase('oLP');
            Session::erase('lpobject');
        } else {
            Session::write('oLP', $oLP);
            $lp_found = true;
        }
    }
}

$lpItem = null;
$lp = null;
if (!empty($lpItemId)) {
    $lpItemRepo = Database::getManager()->getRepository(CLpItem::class);
    $lpItem = $lpItemRepo->find($lpItemId);
}

if ($lpId) {
    /** @var CLp $lp */
    $lp = $lpRepo->find($lpId);
    // Regenerate a new lp object? Not always as some pages don't need the object (like upload?)
    if ($lp) {
        $logInfo = [
            'tool' => TOOL_LEARNPATH,
            'action' => 'lp_load',
        ];
        Event::registerLog($logInfo);
        $type = $lp->getLpType();

        switch ($type) {
            case CLp::SCORM_TYPE:
                $oLP = new scorm($lp, $courseInfo, $userId);
                if (false !== $oLP) {
                    $lp_found = true;
                }
                break;
            case CLp::AICC_TYPE:
                $oLP = new aicc($lp, $courseInfo, $userId);
                if (false !== $oLP) {
                    $lp_found = true;
                }
                break;
            case CLp::LP_TYPE:
            default:
                $oLP = new learnpath($lp, $courseInfo, $userId);
                if (false !== $oLP) {
                    $lp_found = true;
                }
                break;
        }
        Session::write('oLP', $oLP);
    }
}

$is_allowed_to_edit = api_is_allowed_to_edit(false, true, false, false);
if (isset($oLP)) {
    // Reinitialises array used by javascript to update items in the TOC.
    $oLP->update_queue = [];
}

$action = !empty($_REQUEST['action']) ? $_REQUEST['action'] : '';

if ($debug) {
    error_log('Entered lp_controller.php -+- (action: '.$action.')');
}

$__returnTo = $_GET['returnTo'] ?? '';
$__listUrlForSpa = $listUrl;
$goList = static function () use ($__listUrlForSpa, $__returnTo) {
    header('Location: '.$__listUrlForSpa);
    exit;
};

if ($action === '' || $action === 'list') {
    $goList();
}
if (in_array($action, ['view','content'], true) && (empty($lpId) || !$lp_found || !is_object($oLP))) {
    $goList();
}
$eventLpId = $lpId ?: (($lp_found && is_object($oLP)) ? $oLP->get_id() : 0);
$lp_detail_id = 0;
$attemptId = 0;
if ($lp_found && is_object($oLP) && in_array($action, ['view','content'], true)) {
    $lp_detail_id = $oLP->get_current_item_id();
    $attemptId    = $oLP->getCurrentAttempt();
} else {
    $lp_detail_id = !empty($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;
}

$logInfo = [
    'tool' => TOOL_LEARNPATH,
    'tool_id' => $eventLpId,
    'tool_id_detail' => $lp_detail_id,
    'action_details' => $attemptId,
    'action' => !empty($action) ? $action : 'list',
];
Event::registerLog($logInfo);

// format title to be displayed correctly if QUIZ
$post_title = '';
if (isset($_POST['title'])) {
    $post_title = Security::remove_XSS($_POST['title']);
    if (isset($_POST['type']) &&
        isset($_POST['title']) &&
        TOOL_QUIZ === $_POST['type'] &&
        !empty($_POST['title'])
    ) {
        $post_title = Exercise::format_title_variable($_POST['title']);
        if ('true' === api_get_setting('editor.save_titles_as_html')) {
            $post_title = $_POST['title'];
        }
    }
}

$redirectTo = '';

switch ($action) {
    case 'recalculate':
        if (!isset($oLP) || !$lp_found) {
            Display::addFlash(Display::return_message(get_lang('No learning path found'), 'error'));
            $goList();
            exit;
        }

        $userId = isset($_GET['user_id']) ? (int) $_GET['user_id'] : 0;

        if (0 === $userId) {
            Display::addFlash(Display::return_message(get_lang('User ID not provided'), 'error'));
            $goList();
            exit;
        }

        $oLP->recalculateResultsForLp($userId);

        $url = api_get_self().'?action=report&lp_id='.$lpId.'&'.api_get_cidreq();
        header("Location: $url");
        exit;
    case 'author_view':
        $teachers = [];
        $field = new ExtraField('user');
        $authorLp = $field->get_handler_field_info_by_field_variable('authorlp');
        $idExtraField = isset($authorLp['id']) ? (int) $authorLp['id'] : 0;
        if (0 != $idExtraField) {
            $extraFieldValueUser = new ExtraFieldValue('user');
            $arrayExtraFieldValueUser = $extraFieldValueUser->get_item_id_from_field_variable_and_field_value(
                'authorlp',
                1,
                true,
                false,
                true
            );
            if (!empty($arrayExtraFieldValueUser)) {
                foreach ($arrayExtraFieldValueUser as $item) {
                    $teacher = api_get_user_info($item['item_id']);
                    $teachers[] = $teacher;
                }
            }
        }
        if (!$is_allowed_to_edit) {
            api_not_allowed(true);
        }
        if (!$lp_found) {
            // Check if the learnpath ID was defined, otherwise send back to list
            $goList();
        } else {
            require 'lp_add_author.php';
        }
        break;
    case 'send_notify_teacher':
        // Send notification to the teacher
        $studentInfo = api_get_user_info();
        $courseName = $courseInfo['title'];
        $courseUrl = $courseInfo['course_public_url'];
        if (!empty($sessionId)) {
            $sessionInfo = api_get_session_info($sessionId);
            $courseName = $sessionInfo['name'];
            $courseUrl .= '?sid='.$sessionId;
        }

        $url = Display::url($courseName, $courseUrl, ['title' => get_lang('Go to the course')]);
        $coachList = CourseManager::get_coachs_from_course($sessionId, $courseId);
        foreach ($coachList as $coach_course) {
            $recipientName = $coach_course['full_name'];
            $coachInfo = api_get_user_info($coach_course['user_id']);

            if (empty($coachInfo)) {
                continue;
            }
            $email = $coachInfo['email'];

            $tplContent = new Template(null, false, false, false, false, false);
            $tplContent->assign('name_teacher', $recipientName);
            $tplContent->assign('name_student', $studentInfo['complete_name']);
            $tplContent->assign('course_name', $courseName);
            $tplContent->assign('course_url', $url);
            $layoutContent = $tplContent->get_template('mail/content_ending_learnpath.tpl');
            $emailBody = $tplContent->fetch($layoutContent);

            MessageManager::send_message_simple(
                $coachInfo['user_id'],
                sprintf(get_lang('Student %s has completed his/her learning paths.'), $studentInfo['complete_name']),
                $emailBody,
                $studentInfo['user_id']
            );
        }
        Display::addFlash(Display::return_message(get_lang('Message Sent')));
        $goList();
        exit;
        break;
    case 'add_item':
        if (!$is_allowed_to_edit || !$lp_found) {
            api_not_allowed(true);
        }

        Session::write('refresh', 1);

        if (isset($_POST['submit_button']) && !empty($post_title)) {
            Session::write('post_time', $_POST['post_time']);
            $directoryParentId = $_POST['directory_parent_id'] ?? 0;

            if (empty($directoryParentId) || '/' === $directoryParentId) {
                $result = $oLP->generate_lp_folder($courseInfo);
                $directoryParentId = $result->getIid();
            }

            $parent = $_POST['parent'] ?? null;
            $em = Database::getManager();
            if (!empty($parent)) {
                $parent = $lpItemRepo->find($parent);
            } else {
                $parent = null;
            }

            $previous = $_POST['previous'] ?? '';
            $type = $_POST['type'] ?? '';
            $path = $_POST['path'] ?? '';
            $description = $_POST['description'] ?? '';
            $prerequisites = $_POST['prerequisites'] ?? '';
            $maxTimeAllowed = $_POST['maxTimeAllowed'] ?? '';
            $exportAllowed = (isset($_POST['export_allowed']) && $_POST['export_allowed'] === '1');

            $saveExportFlag = static function (int $itemId) use ($exportAllowed) {
                if (empty($itemId)) return;

                $em   = Database::getManager();
                $repo = Container::getLpItemRepository();
                /** @var CLpItem|null $it */
                $it = $repo->find($itemId);
                if (!$it) return;

                $allowed = false;
                if ($it->getItemType() === TOOL_DOCUMENT) {
                    $docRepo = Container::getDocumentRepository();
                    $docId   = (int) $it->getPath();
                    /** @var CDocument|null $doc */
                    $doc = $docRepo->find($docId);
                    if ($doc) {
                        $node = $doc->getResourceNode();
                        $file = $node?->getFirstResourceFile();
                        $mime = (string) $file?->getMimeType();
                        $isHtmlEditable = $node->hasEditableTextContent()
                            || in_array($mime, ['text/html', 'application/xhtml+xml'], true);
                        $allowed = $isHtmlEditable && (bool) $exportAllowed;
                    }
                }

                $it->setExportAllowed($allowed);
                $em->persist($it);
                $em->flush();
            };

            if (in_array($_POST['type'], [TOOL_DOCUMENT, 'video'])) {
                if (isset($_POST['path']) && !empty($_GET['id'])) {
                    $document_id = $_POST['path'];
                } else {
                    if ($_POST['content_lp']) {
                        $document_id = $oLP->create_document(
                            $courseInfo,
                            $_POST['content_lp'],
                            $_POST['title'],
                            'html',
                            $directoryParentId
                        );
                    }
                }

                $documentRepo = Database::getManager()->getRepository(CDocument::class);
                $document = $documentRepo->find((int)$document_id);
                if ($document && $document->getFiletype() === 'video') {
                    $type = 'video';
                }

                $createdItemId = $oLP->add_item(
                    $parent,
                    $previous,
                    $type,
                    $document_id,
                    $post_title,
                    $description,
                    $prerequisites
                );
                $saveExportFlag($createdItemId);
            } elseif (TOOL_READOUT_TEXT == $_POST['type']) {
                if (isset($_POST['path']) && 'true' != $_GET['edit']) {
                    $document_id = $_POST['path'];
                } else {
                    if ($_POST['content_lp']) {
                        $document_id = $oLP->createReadOutText(
                            $courseInfo,
                            $_POST['content_lp'],
                            $_POST['title'],
                            $directoryParentId
                        );
                    }
                }

                $createdItemId = $oLP->add_item(
                    $parent,
                    $previous,
                    TOOL_READOUT_TEXT,
                    $document_id,
                    $post_title,
                    $description,
                    $prerequisites
                );
                $saveExportFlag($createdItemId);
            } else {
                // For all other item types than documents,
                // load the item using the item type and path rather than its ID.
                $createdItemId = $oLP->add_item(
                    $parent,
                    $previous,
                    $type,
                    $path,
                    $post_title,
                    $description,
                    $prerequisites,
                    $maxTimeAllowed
                );
                $saveExportFlag($createdItemId);
            }
            $url = api_get_self().'?action=add_item&type=step&lp_id='.intval($oLP->lp_id).'&'.api_get_cidreq();
            header('Location: '.$url);
            exit;
        } else {
            require 'lp_add_item.php';
        }

        break;
    case 'add_users_to_category':
        if (!$is_allowed_to_edit) {
            api_not_allowed(true);
        }
        require 'lp_subscribe_users_to_category.php';
        break;
    case 'add_audio':
        if (!$is_allowed_to_edit) {
            api_not_allowed(true);
        }
        if (!$lp_found) {
            // Check if the learnpath ID was defined, otherwise send back to list
            $goList();
        } else {
            Session::write('refresh', 1);

            if (isset($_REQUEST['id'])) {
                $lp_item_obj = new learnpathItem($_REQUEST['id']);

                $url = api_get_self().
                    '?action=add_audio&lp_id='.$oLP->lp_id.'&id='.$lp_item_obj->get_id().'&'.api_get_cidreq();

                // Remove audio
                if (isset($_GET['delete_file']) && 1 == $_GET['delete_file']) {
                    $lp_item_obj->removeAudio();
                    Display::addFlash(Display::return_message(get_lang('File deleted')));
                    api_location($url);
                }

                // Upload audio.
                if (isset($_FILES['file']) && !empty($_FILES['file'])) {
                    // Updating the lp.modified_on
                    $oLP->set_modified_on();
                    $lp_item_obj->addAudio();
                    Display::addFlash(Display::return_message(get_lang('File upload succeeded!')));
                    api_location($url);
                }

                // Add audio file from documents.
                if (isset($_REQUEST['document_id']) && !empty($_REQUEST['document_id'])) {
                    $oLP->set_modified_on();
                    $lp_item_obj->add_audio_from_documents($_REQUEST['document_id']);
                    Display::addFlash(Display::return_message(get_lang('Updated')));
                    api_location($url);
                }
                require 'lp_add_audio.php';
            } else {
                require 'lp_add_audio.php';
            }
        }
        break;
    case 'add_lp_category':
        if (!$is_allowed_to_edit) {
            api_not_allowed(true);
        }
        require 'lp_add_category.php';
        break;
    case 'ai_helper':
        if (!$is_allowed_to_edit) {
            api_not_allowed(true);
        }
        require 'lp_add_ai_helper.php';
        break;
    case 'move_up_category':
        if (!$is_allowed_to_edit) {
            api_not_allowed(true);
        }
        if (isset($_REQUEST['id'])) {
            learnpath::moveUpCategory((int) $_REQUEST['id']);
        }
        $goList();
        break;
    case 'move_down_category':
        if (!$is_allowed_to_edit) {
            api_not_allowed(true);
        }
        if (isset($_REQUEST['id'])) {
            learnpath::moveDownCategory((int) $_REQUEST['id']);
        }
        $goList();
        break;
    case 'delete_lp_category':
        if (!$is_allowed_to_edit) {
            api_not_allowed(true);
        }
        if (isset($_REQUEST['id'])) {
            $result = learnpath::deleteCategory((int) $_REQUEST['id']);
            if ($result) {
                Display::addFlash(Display::return_message(get_lang('Deleted')));
            }
        }
        $goList();
        break;
    case 'add_lp':
        if (!$is_allowed_to_edit) {
            api_not_allowed(true);
        }
        require 'lp_add.php';
        break;
    case 'admin_view':
        if (!$is_allowed_to_edit) {
            api_not_allowed(true);
        }
        if (!$lp_found) {
            $goList();
        } else {
            Session::write('refresh', 1);
            require 'lp_admin_view.php';
        }
        break;
    case 'auto_launch':
        // Redirect to a specific LP
        if (1 == api_get_course_setting('enable_lp_auto_launch')) {
            if (!$is_allowed_to_edit) {
                api_not_allowed(true);
            }
            if (!$lp_found) {
                $goList();
            } else {
                $oLP->set_autolaunch($lpId, $_GET['status']);
                $goList();
                exit;
            }
        }
        break;
    case 'build':
        if (!$is_allowed_to_edit) {
            api_not_allowed(true);
        }
        if (!$lp_found) {
            $goList();
        } else {
            Session::write('refresh', 1);
            $url = api_get_self()
                .'?action=add_item&type=step&lp_id='.intval($oLP->lp_id)
                .'&'.api_get_cidreq()
                .'&isStudentView=false';
            header('Location: '.$url);
            exit;
        }
        break;
    case 'edit_item':
        if (!$is_allowed_to_edit || !$lp_found) {
            api_not_allowed(true);
        }

        Session::write('refresh', 1);
        if (isset($_POST['submit_button']) && !empty($post_title)) {
            // TODO: mp3 edit
            $audio = [];
            if (isset($_FILES['mp3'])) {
                $audio = $_FILES['mp3'];
            }

            $previous = isset($_POST['previous']) ? $_POST['previous'] : '';
            $description = isset($_POST['description']) ? $_POST['description'] : '';
            $prerequisites = isset($_POST['prerequisites']) ? $_POST['prerequisites'] : '';
            $maxTimeAllowed = isset($_POST['maxTimeAllowed']) ? $_POST['maxTimeAllowed'] : '';
            $url = isset($_POST['url']) ? $_POST['url'] : '';

            $oLP->edit_item(
                $_REQUEST['id'],
                $_POST['parent'],
                $previous,
                $post_title,
                $description,
                $prerequisites,
                $audio,
                $maxTimeAllowed,
                $url
            );
            if (isset($_POST['content_lp'])) {
                $oLP->edit_document($courseInfo);
            }

            $exportAllowed = (isset($_POST['export_allowed']) && '1' === $_POST['export_allowed']);
            $repo = Container::getLpItemRepository();
            /** @var CLpItem $item */
            $item = $repo->find((int) $_REQUEST['id']);
            if ($item) {
                $item->setExportAllowed($exportAllowed);
                $em = Database::getManager();
                $em->persist($item);
                $em->flush();
            }

            $is_success = true;
            $extraFieldValues = new ExtraFieldValue('lp_item');
            $extraFieldValues->saveFieldValues($_POST);

            Display::addFlash(Display::return_message(get_lang('Updated')));
            $url = api_get_self().'?action=add_item&type=step&lp_id='.intval($oLP->lp_id).'&'.api_get_cidreq();
            header('Location: '.$url);
            exit;
        }
        if (isset($_GET['view']) && 'build' === $_GET['view']) {
            require 'lp_edit_item.php';
        } else {
            require 'lp_admin_view.php';
        }
        break;
    case 'edit_item_prereq':
        if (!$is_allowed_to_edit) {
            api_not_allowed(true);
        }
        if (!$lp_found) {
            $goList();
        } else {
            if (isset($_POST['submit_button'])) {
                // Updating the lp.modified_on
                $oLP->set_modified_on();
                Session::write('refresh', 1);
                $min = isset($_POST['min_'.$_POST['prerequisites']]) ? $_POST['min_'.$_POST['prerequisites']] : '';
                $max = isset($_POST['max_'.$_POST['prerequisites']]) ? $_POST['max_'.$_POST['prerequisites']] : '';

                $editPrerequisite = $oLP->edit_item_prereq(
                    $_GET['id'],
                    $_POST['prerequisites'],
                    $min,
                    $max
                );

                Display::addFlash(Display::return_message(get_lang('Update successful')));
                $url = api_get_self().'?action=add_item&type=step&lp_id='.intval($oLP->lp_id).'&'.api_get_cidreq();
                header('Location: '.$url);
                exit;
            } else {
                require 'lp_edit_item_prereq.php';
            }
        }
        break;
    case 'move_item':
        if (!$is_allowed_to_edit) {
            api_not_allowed(true);
        }

        if (!$lp_found) {
            $goList();
        } else {
            Session::write('refresh', 1);
            if (isset($_POST['submit_button'])) {
                //Updating the lp.modified_on
                $oLP->set_modified_on();
                $oLP->edit_item(
                    $_GET['id'],
                    $_POST['parent'],
                    $_POST['previous'],
                    $post_title,
                    $_POST['description']
                );
                $url = api_get_self().'?action=add_item&type=step&lp_id='.intval($oLP->lp_id).'&'.api_get_cidreq();
                header('Location: '.$url);
                exit;
            }
            if (isset($_GET['view']) && 'build' === $_GET['view']) {
                require 'lp_move_item.php';
            } else {
                // Avoids weird behaviours see CT#967.
                $check = Security::check_token('get');
                if ($check) {
                    $oLP->move_item($_GET['id'], $_GET['direction']);
                }
                Security::clear_token();
                require 'lp_admin_view.php';
            }
        }
        break;
    case 'view_item':
        if (!$is_allowed_to_edit) {
            api_not_allowed(true);
        }
        if (!$lp_found) {
            $goList();
        } else {
            Session::write('refresh', 1);
            require 'lp_view_item.php';
        }
        break;
    case 'upload':
        if (!$is_allowed_to_edit) {
            api_not_allowed(true);
        }
        $cwdir = getcwd();
        ob_start();
        require 'lp_upload.php';
        chdir($cwdir);
        $html = ob_get_clean();

        $hasUploadedFile = false;
        foreach ($_FILES as $f) {
            if (is_array($f) && isset($f['error'])) {
                if (is_array($f['error'])) {
                    foreach ($f['error'] as $err) {
                        if ((int)$err === UPLOAD_ERR_OK) { $hasUploadedFile = true; break 2; }
                    }
                } else {
                    if ((int)$f['error'] === UPLOAD_ERR_OK) { $hasUploadedFile = true; break; }
                }
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $hasUploadedFile) {
            api_location(api_get_self().'?action=add_item&type=step&lp_id='.$lpId.'&'.api_get_cidreq());
        }

        echo $html;
        break;
    case 'copy':
        if (!$is_allowed_to_edit) {
            api_not_allowed(true);
        }

        $hideScormCopyLink = api_get_setting('lp.hide_scorm_copy_link');
        if ('true' === $hideScormCopyLink) {
            api_not_allowed(true);
        }

        if (!$lp_found) {
            $goList();
        } else {
            $oLP->copy();
        }
        $goList();
        break;
    case 'export':
        if (!$is_allowed_to_edit) {
            api_not_allowed(true);
        }
        $hideScormExportLink = api_get_setting('lp.hide_scorm_export_link');
        if ('true' === $hideScormExportLink) {
            api_not_allowed(true);
        }
        if (!$lp_found) {
            $goList();
        } else {
            ScormExport::export($oLP);
            exit();
        }
        break;
    case 'export_to_pdf':
        $hideScormPdfLink = api_get_setting('lp.hide_scorm_pdf_link');
        if ('true' === $hideScormPdfLink) {
           api_not_allowed(true);
        }

        if (!$lp_found) {
            $goList();
        } else {
            $selectedItems = isset($_GET['items']) ? explode(',', $_GET['items']) : [];
            $selectedItems = array_values(array_filter(array_map('intval', $selectedItems), static function ($v) { return $v > 0; }));
            $result = ScormExport::exportToPdf($lpId, $courseInfo, $selectedItems);
            if (!$result) {
                $goList();
            }
            exit;
        }
        break;
    case 'export_to_course_build':
        $allowExport = ('true' === api_get_setting('lp.allow_lp_chamilo_export'));
        if (api_is_allowed_to_edit() && $allowExport) {
            if (!$lp_found) {
                $goList();
            } else {
                $result = $oLP->exportToCourseBuildFormat($lpId);
                if (!$result) {
                    $goList();
                }
                exit;
            }
        }
        $goList();
        break;
    case 'delete':
        if (!$is_allowed_to_edit) {
            api_not_allowed(true);
        }
        if (!$lp_found) {
            $goList();
        } else {
            Session::write('refresh', 1);
            $oLP->delete(null, $lpId, 'remove');
            SkillModel::deleteSkillsFromItem($lpId, ITEM_TYPE_LEARNPATH);
            Display::addFlash(Display::return_message(get_lang('Deleted')));
            Session::erase('oLP');
            $goList();
        }
        break;
    case 'toggle_category_visibility':
        if (!$is_allowed_to_edit) {
            api_not_allowed(true);
        }

        learnpath::toggleCategoryVisibility($_REQUEST['id'], $_REQUEST['new_status']);
        Display::addFlash(Display::return_message(get_lang('Update successful')));
        $goList();
        exit;

        break;
    case 'toggle_visible':
        // Change lp visibility (inside lp tool).
        if (!$is_allowed_to_edit) {
            api_not_allowed(true);
        }

        if ($lp_found) {
            learnpath::toggleVisibility($_REQUEST['lp_id'], $_REQUEST['new_status']);
            Display::addFlash(Display::return_message(get_lang('Update successful')));
        }
        $goList();
        exit;

        break;
    case 'toggle_category_publish':
        if (!$is_allowed_to_edit) {
            api_not_allowed(true);
        }

        learnpath::toggleCategoryPublish($_REQUEST['id'], $_REQUEST['new_status']);
        Display::addFlash(Display::return_message(get_lang('Update successful')));
        $goList();
        exit;

        break;
    case 'toggle_publish':
        // Change lp published status (visibility on homepage).
        if (!$is_allowed_to_edit) {
            api_not_allowed(true);
        }
        if ($lp_found) {
            learnpath::togglePublish($_REQUEST['lp_id'], $_REQUEST['new_status']);
            Display::addFlash(Display::return_message(get_lang('Update successful')));
        }
        $goList();
        exit;

        break;
    case 'move_lp_up':
        // Change lp published status (visibility on homepage)
        if (!$is_allowed_to_edit) {
            api_not_allowed(true);
        }
        if ($lp_found) {
            learnpath::move($_REQUEST['lp_id'], 'up');
            Display::addFlash(Display::return_message(get_lang('Update successful')));
        }
        $goList();
        exit;

        break;
    case 'move_lp_down':
        // Change lp published status (visibility on homepage)
        if (!$is_allowed_to_edit) {
            api_not_allowed(true);
        }
        if ($lp_found) {
            learnpath::move($_REQUEST['lp_id'], 'down');
            Display::addFlash(Display::return_message(get_lang('Update successful')));
        }
        $goList();
        exit;

        break;
    case 'edit':
        if (!$is_allowed_to_edit) {
            api_not_allowed(true);
        }

        if (!$lp_found) {
            $goList();
        } else {
            Session::write('refresh', 1);
            require 'lp_edit.php';
        }
        break;
    case 'add_sub_item':
        // Add an item inside a dir/chapter.
        // @todo check if this is @deprecated
        if (!$is_allowed_to_edit) {
            api_not_allowed(true);
        }
        if (!$lp_found) {
            $goList();
        } else {
            Session::write('refresh', 1);
            if (!empty($_REQUEST['parent_item_id'])) {
                $_SESSION['from_learnpath'] = 'yes';
                $_SESSION['origintoolurl'] = 'lp_controller.php?action=admin_view&lp_id='.intval($_REQUEST['lp_id']);
            } else {
                require 'lp_admin_view.php';
            }
        }
        break;
    case 'deleteitem':
    case 'delete_item':
        if (!$is_allowed_to_edit) {
            api_not_allowed(true);
        }
        if (!$lp_found) {
            $goList();
        } else {
            if (!empty($_REQUEST['id'])) {
                $oLP->delete_item($_REQUEST['id']);
            }
            $url = api_get_self().'?action=add_item&type=step&lp_id='.intval($_REQUEST['lp_id']).'&'.api_get_cidreq();
            header('Location: '.$url);
            exit;
        }
        break;
    case 'restart':
        if (!$lp_found) {
            $goList();
        } else {
            $oLP->restart();
            require 'lp_view.php';
        }
        break;
    case 'last':
        if (!$lp_found) {
            $goList();
        } else {
            $oLP->last();
            require 'lp_view.php';
        }
        break;
    case 'first':
        if (!$lp_found) {
            $goList();
        } else {
            $oLP->first();
            require 'lp_view.php';
        }
        break;
    case 'next':
        if (!$lp_found) {
            $goList();
        } else {
            $oLP->next();
            require 'lp_view.php';
        }
        break;
    case 'previous':
        if (!$lp_found) {
            $goList();
        } else {
            $oLP->previous();
            require 'lp_view.php';
        }
        break;
    case 'content':
        if (!$lp_found) {
            $goList();
        } else {
            $oLP->save_last();

            $requestedItemId = (int) ($_GET['item_id'] ?? $_GET['lp_item_id'] ?? $_GET['id'] ?? 0);
            if ($requestedItemId > 0) {
                $oLP->set_current_item($requestedItemId);
            }

            $oLP->start_current_item();
            require 'lp_content.php';
        }
        break;
    case 'view':
        if (!$lp_found) {
            $goList();
        } else {
            // Accept multiple parameter names for the requested LP item (backward/legacy compat)
            $requestedItemId = (int) ($_REQUEST['item_id'] ?? $_REQUEST['lp_item_id'] ?? $_REQUEST['id'] ?? 0);
            if ($requestedItemId > 0) {
                $oLP->set_current_item($requestedItemId);
            }
            require 'lp_view.php';
        }
        break;
    case 'save':
        if (!$lp_found) {
            $goList();
        } else {
            $oLP->save_item();
            require 'lp_save.php';
        }
        break;
    case 'stats':
        if (!$lp_found) {
            $goList();
        } else {
            $oLP->save_current();
            $oLP->save_last();

            Display::display_no_header();
            $output = require 'lp_stats.php';
            echo $output;
            Display::display_reduced_footer();
            exit;
        }
        break;
    case 'list':
        if ($lp_found) {
            Session::write('refresh', 1);
            $oLP->save_last();
        }
        $goList();
        break;
    case 'mode':
        // Switch between fullscreen and embedded mode.
        $mode = $_REQUEST['mode'];
        if ('fullscreen' === $mode) {
            $oLP->mode = 'fullscreen';
        } elseif ('embedded' === $mode) {
            $oLP->mode = 'embedded';
        } elseif ('embedframe' === $mode) {
            $oLP->mode = 'embedframe';
        } elseif ('impress' === $mode) {
            $oLP->mode = 'impress';
        }
        require 'lp_view.php';
        break;
    case 'switch_view_mode':
        if ($lp_found) {
            if (Security::check_token('get')) {
                Session::write('refresh', 1);
                $oLP->update_default_view_mode();
            }
        }

        $goList();
        exit;

        break;
    case 'switch_force_commit':
        if ($lp_found) {
            Session::write('refresh', 1);
            $oLP->update_default_scorm_commit();
            Display::addFlash(Display::return_message(get_lang('Updated')));
        }
        $goList();
        exit;

        break;
    case 'switch_attempt_mode':
        if ($lp_found) {
            Session::write('refresh', 1);
            $oLP->switch_attempt_mode();
            Display::addFlash(Display::return_message(get_lang('Updated')));
        }
        $goList();
        exit;

        break;
    case 'switch_scorm_debug':
        if ($lp_found) {
            Session::write('refresh', 1);
            $oLP->update_scorm_debug();
            Display::addFlash(Display::return_message(get_lang('Updated')));
        }
        $goList();
        exit;
    case 'update_scorm':
        if (!$is_allowed_to_edit) {
            api_not_allowed(true);
        }

        if (empty($lpId)) {
            Display::addFlash(Display::return_message(get_lang('No learning path found'), 'error'));
            $goList();
            exit;
        }

        /** @var CLp|null $lp */
        $lp = $lpRepo->find($lpId);
        if (!$lp) {
            Display::addFlash(Display::return_message(get_lang('No learning path found'), 'error'));
            $goList();
            exit;
        }

        if ((int) $lp->getLpType() !== CLp::SCORM_TYPE) {
            Display::addFlash(Display::return_message(get_lang('Not a SCORM learning path'), 'error'));
            $goList();
            exit;
        }

        $script = 'lp_update_scorm.php';
        if (!is_file(__DIR__.'/lp_update_scorm.php') && is_file(__DIR__.'/lp_upload_scorm.php')) {
            $script = 'lp_upload_scorm.php';
        }

        $target = api_get_path(WEB_CODE_PATH).'lp/'.$script.'?'.api_get_cidreq().'&lp_id='.$lpId;
        $target .= '&returnTo='.urlencode($listUrl);
        header('Location: '.$target);
        exit;
    case 'return_to_course_homepage':
        if (!$lp_found) {
            $goList();
        } else {
            $oLP->save_current();
            $oLP->save_last();
            $url = $courseInfo['course_public_url'].'?sid='.api_get_session_id();
            $redirectTo = isset($_GET['redirectTo']) ? $_GET['redirectTo'] : '';
            switch ($redirectTo) {
                case 'course_home':
                    $url = api_get_path(WEB_PATH).'course/'.$courseId.'/home?'.api_get_cidreq();
                    break;
                case 'lp_list':
                    $url = 'lp_controller.php?'.api_get_cidreq();
                    break;
                case 'my_courses':
                    $url = api_get_path(WEB_PATH).'courses';
                    break;
                case 'my_sessions':
                    $url = api_get_path(WEB_PATH).'sessions';
                    break;
                case 'portal_home':
                    $url = api_get_path(WEB_PATH);
                    break;
            }
            header('location: '.$url);
            exit;
        }
        break;
    case 'search':
        /* Include the search script, it's smart enough to know when we are
         * searching or not.
         */
        require 'lp_list_search.php';
        break;
    case 'impress':
        if (!$lp_found) {
            $goList();
        } else {
            if (!empty($_REQUEST['item_id'])) {
                $oLP->set_current_item($_REQUEST['item_id']);
            }
            require 'lp_impress.php';
        }
        break;
    case 'set_previous_step_as_prerequisite':
        $oLP->set_previous_step_as_prerequisite_for_all_items();
        $url = api_get_self().'?action=add_item&type=step&lp_id='.intval($oLP->lp_id)."&".api_get_cidreq();
        Display::addFlash(Display::return_message(get_lang('Item updated')));
        header('Location: '.$url);
        exit;
        break;
    case 'clear_prerequisites':
        $oLP->clearPrerequisites();
        $url = api_get_self().'?action=add_item&type=step&lp_id='.intval($oLP->lp_id)."&".api_get_cidreq();
        Display::addFlash(Display::return_message(get_lang('Item updated')));
        header('Location: '.$url);
        exit;
        break;
    case 'toggle_seriousgame':
        // activate/deactive seriousgame_mode
        if (!$is_allowed_to_edit) {
            api_not_allowed(true);
        }

        if (!$lp_found) {
            $goList();
        }

        Session::write('refresh', 1);
        $oLP->set_seriousgame_mode();
        $goList();
        break;
    case 'report':
        require 'lp_report.php';
        break;
    case 'dissociate_forum':
        if (!isset($_GET['id'])) {
            break;
        }

        $selectedItem = null;
        foreach ($oLP->items as $item) {
            if ($item->db_id != $_GET['id']) {
                continue;
            }
            $selectedItem = $item;
        }

        if (!empty($selectedItem)) {
            $lpItemRepo = Container::getLpItemRepository();
            $forumThreadRepo = Container::getForumThreadRepository();
            /** @var CLpItem $lpItem */
            $lpItem = $lpItemRepo->find($_GET['id']);
            if ($lpItem) {
                $title = $lpItem->getTitle().' '.$lpItem->getIid();
                $thread = $forumThreadRepo->getForumThread($title, $course);

                if (!empty($thread)) {
                    $thread->setItem(null);
                    $forumThreadRepo->update($thread);

                    Display::addFlash(
                        Display::return_message(get_lang('Dissociate forum'), 'success')
                    );
                }
            }
        }

        header('Location:'.api_get_self().'?'.http_build_query([
            'action' => 'add_item',
            'type' => 'step',
            'lp_id' => $oLP->lp_id,
        ]));
        exit;
        break;
    case 'add_final_item':
        if (!$is_allowed_to_edit || !$lp_found) {
            api_not_allowed(true);
        }

        Session::write('refresh', 1);
        $htmlHeadXtra[] = '<style>
      .lp-finalitem-wrap { padding-left: 24px; }
      .lp-finalitem-title { font-size: 18px; font-weight: 600; margin: 0 0 12px 0; }
    </style>';

        $finalItemTitle = get_lang('Final item');
        if (method_exists($oLP, 'getFinalItem')) {
            $finalItem = $oLP->getFinalItem();
            if ($finalItem && method_exists($finalItem, 'get_title')) {
                $t = trim(strip_tags((string) $finalItem->get_title()));
                if ($t !== '') {
                    $finalItemTitle = $t;
                }
            }
        }

        $right = '<div class="lp-finalitem-wrap">'
            . '<div class="lp-finalitem-title">'.Security::remove_XSS($finalItemTitle).'</div>'
            . $oLP->getFinalItemForm()
            . '</div>';

        $tpl = new Template();
        $tpl->assign('actions', $oLP->build_action_menu(true));
        $tpl->assign('left', $oLP->showBuildSideBar(null, true, 'step'));
        $tpl->assign('right', $right);
        $tpl->displayTwoColTemplate();
        exit;
    default:
        $goList();
        break;
}

if (!empty($oLP)) {
    Session::write('lpobject', serialize($oLP));
    if ($debug > 0) {
        error_log('lpobject is serialized in session', 0);
    }
}

if (!empty($redirectTo)) {
    header("Location: $redirectTo");
    exit;
}
