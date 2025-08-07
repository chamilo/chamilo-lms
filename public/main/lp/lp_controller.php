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
$glossaryExtraTools = api_get_setting('show_glossary_in_extra_tools');
$showGlossary = in_array($glossaryExtraTools, ['true', 'lp', 'exercise_and_lp']);
if ($showGlossary) {
    if ('ismanual' === api_get_setting('show_glossary_in_documents') ||
        'isautomatic' === api_get_setting('show_glossary_in_documents')
    ) {
        $htmlHeadXtra[] = '<script>
    <!--
        var jQueryFrameReadyConfigPath = \''.api_get_jquery_web_path().'\';
    -->
    </script>';
        $htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jquery.frameready.js"></script>';
        $htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jquery.highlight.js"></script>';
    }
}

$ajax_url = api_get_path(WEB_AJAX_PATH).'lp.ajax.php?lp_id='.$lpId.'&'.api_get_cidreq();
$listUrl = api_get_self().'?action=list&'.api_get_cidreq();
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

$eventLpId = $lpId;
if (empty($lpId)) {
    if (isset($oLP)) {
        $eventLpId = $oLP->get_id();
    }
}

$lp_detail_id = 0;
$attemptId = 0;
switch ($action) {
    case '':
    case 'list':
        $eventLpId = 0;
        break;
    case 'view':
    case 'content':
        $lp_detail_id = $oLP->get_current_item_id();
        $attemptId = $oLP->getCurrentAttempt();
        break;
    default:
        $lp_detail_id = (!empty($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0);
        break;
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
            Display::addFlash(Display::return_message(get_lang('NoLpFound'), 'error'));
            header("Location: $listUrl");
            exit;
        }

        $userId = isset($_GET['user_id']) ? (int) $_GET['user_id'] : 0;

        if (0 === $userId) {
            Display::addFlash(Display::return_message(get_lang('NoUserIdProvided'), 'error'));
            header("Location: $listUrl");
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
            require 'lp_list.php';
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
                sprintf(get_lang('StudentXFinishedLp'), $studentInfo['complete_name']),
                $emailBody,
                $studentInfo['user_id']
            );
        }
        Display::addFlash(Display::return_message(get_lang('Message Sent')));
        header('Location: '.$listUrl);
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

                $oLP->add_item(
                    $parent,
                    $previous,
                    $type,
                    $document_id,
                    $post_title,
                    $description,
                    $prerequisites
                );
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

                $oLP->add_item(
                    $parent,
                    $previous,
                    TOOL_READOUT_TEXT,
                    $document_id,
                    $post_title,
                    $description,
                    $prerequisites
                );
            } else {
                // For all other item types than documents,
                // load the item using the item type and path rather than its ID.
                $oLP->add_item(
                    $parent,
                    $previous,
                    $type,
                    $path,
                    $post_title,
                    $description,
                    $prerequisites,
                    $maxTimeAllowed
                );
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
            require 'lp_list.php';
        } else {
            Session::write('refresh', 1);

            if (isset($_REQUEST['id'])) {
                $lp_item_obj = new learnpathItem($_REQUEST['id']);

                $url = api_get_self().
                    '?action=add_audio&lp_id='.$oLP->lp_id.'&id='.$lp_item_obj->get_id().'&'.api_get_cidreq();

                // Remove audio
                if (isset($_GET['delete_file']) && 1 == $_GET['delete_file']) {
                    $lp_item_obj->removeAudio();
                    Display::addFlash(Display::return_message(get_lang('FileDeleted')));
                    api_location($url);
                }

                // Upload audio.
                if (isset($_FILES['file']) && !empty($_FILES['file'])) {
                    // Updating the lp.modified_on
                    $oLP->set_modified_on();
                    $lp_item_obj->addAudio();
                    Display::addFlash(Display::return_message(get_lang('UplUploadSucceeded')));
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
        require 'lp_list.php';
        break;
    case 'move_down_category':
        if (!$is_allowed_to_edit) {
            api_not_allowed(true);
        }
        if (isset($_REQUEST['id'])) {
            learnpath::moveDownCategory((int) $_REQUEST['id']);
        }
        require 'lp_list.php';
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
        require 'lp_list.php';
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
            require 'lp_list.php';
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
                require 'lp_list.php';
            } else {
                $oLP->set_autolaunch($lpId, $_GET['status']);
                require 'lp_list.php';
                exit;
            }
        }
        break;
    case 'build':
        if (!$is_allowed_to_edit) {
            api_not_allowed(true);
        }
        if (!$lp_found) {
            require 'lp_list.php';
        } else {
            Session::write('refresh', 1);
            $url = api_get_self().'?action=add_item&type=step&lp_id='.intval($oLP->lp_id).'&'.api_get_cidreq();
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
            require 'lp_list.php';
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
            require 'lp_list.php';
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
            require 'lp_list.php';
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
        require 'lp_upload.php';
        // Reinit current working directory as many functions in upload change it.
        chdir($cwdir);
        require 'lp_list.php';
        break;
    case 'copy':
        if (!$is_allowed_to_edit) {
            api_not_allowed(true);
        }

        $hideScormCopyLink = api_get_setting('hide_scorm_copy_link');
        if ('true' === $hideScormCopyLink) {
            api_not_allowed(true);
        }

        if (!$lp_found) {
            require 'lp_list.php';
        } else {
            $oLP->copy();
        }
        require 'lp_list.php';
        break;
    case 'export':
        if (!$is_allowed_to_edit) {
            api_not_allowed(true);
        }
        $hideScormExportLink = api_get_setting('hide_scorm_export_link');
        if ('true' === $hideScormExportLink) {
            api_not_allowed(true);
        }
        if (!$lp_found) {
            require 'lp_list.php';
        } else {
            ScormExport::export($oLP);
            exit();
        }
        break;
    case 'export_to_pdf':
        $hideScormPdfLink = api_get_setting('hide_scorm_pdf_link');
        if ('true' === $hideScormPdfLink) {
            api_not_allowed(true);
        }

        // Teachers can export to PDF
        if (!$is_allowed_to_edit) {
            if (!learnpath::is_lp_visible_for_student($lp, $userId, $course)) {
                api_not_allowed();
            }
        }

        if (!$lp_found) {
            require 'lp_list.php';
        } else {
            $selectedItems = isset($_GET['items']) ? explode(',', $_GET['items']) : [];
            $result = ScormExport::exportToPdf($lpId, $courseInfo, $selectedItems);
            if (!$result) {
                require 'lp_list.php';
            }
            exit;
        }
        break;
    case 'export_to_course_build':
        $allowExport = ('true' === api_get_setting('lp.allow_lp_chamilo_export'));
        if (api_is_allowed_to_edit() && $allowExport) {
            if (!$lp_found) {
                require 'lp_list.php';
            } else {
                $result = $oLP->exportToCourseBuildFormat($lpId);
                if (!$result) {
                    require 'lp_list.php';
                }
                exit;
            }
        }
        require 'lp_list.php';
        break;
    case 'delete':
        if (!$is_allowed_to_edit) {
            api_not_allowed(true);
        }
        if (!$lp_found) {
            require 'lp_list.php';
        } else {
            Session::write('refresh', 1);
            $oLP->delete(null, $lpId, 'remove');
            SkillModel::deleteSkillsFromItem($lpId, ITEM_TYPE_LEARNPATH);
            Display::addFlash(Display::return_message(get_lang('Deleted')));
            Session::erase('oLP');
            require 'lp_list.php';
        }
        break;
    case 'toggle_category_visibility':
        if (!$is_allowed_to_edit) {
            api_not_allowed(true);
        }

        learnpath::toggleCategoryVisibility($_REQUEST['id'], $_REQUEST['new_status']);
        Display::addFlash(Display::return_message(get_lang('Update successful')));
        header('Location: '.$listUrl);
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
        header('Location: '.$listUrl);
        exit;

        break;
    case 'toggle_category_publish':
        if (!$is_allowed_to_edit) {
            api_not_allowed(true);
        }

        learnpath::toggleCategoryPublish($_REQUEST['id'], $_REQUEST['new_status']);
        Display::addFlash(Display::return_message(get_lang('Update successful')));
        header('Location: '.$listUrl);
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
        header('Location: '.$listUrl);
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
        header('Location: '.$listUrl);
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
        header('Location: '.$listUrl);
        exit;

        break;
    case 'edit':
        if (!$is_allowed_to_edit) {
            api_not_allowed(true);
        }

        if (!$lp_found) {
            require 'lp_list.php';
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
            require 'lp_list.php';
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
            require 'lp_list.php';
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
            require 'lp_list.php';
        } else {
            $oLP->restart();
            require 'lp_view.php';
        }
        break;
    case 'last':
        if (!$lp_found) {
            require 'lp_list.php';
        } else {
            $oLP->last();
            require 'lp_view.php';
        }
        break;
    case 'first':
        if (!$lp_found) {
            require 'lp_list.php';
        } else {
            $oLP->first();
            require 'lp_view.php';
        }
        break;
    case 'next':
        if (!$lp_found) {
            require 'lp_list.php';
        } else {
            $oLP->next();
            require 'lp_view.php';
        }
        break;
    case 'previous':
        if (!$lp_found) {
            require 'lp_list.php';
        } else {
            $oLP->previous();
            require 'lp_view.php';
        }
        break;
    case 'content':
        if (!$lp_found) {
            require 'lp_list.php';
        } else {
            $oLP->save_last();
            $oLP->set_current_item($_GET['item_id']);
            $oLP->start_current_item();
            require 'lp_content.php';
        }
        break;
    case 'view':
        if (!$lp_found) {
            require 'lp_list.php';
        } else {
            if (!empty($_REQUEST['item_id'])) {
                $oLP->set_current_item($_REQUEST['item_id']);
            }
            require 'lp_view.php';
        }
        break;
    case 'save':
        if (!$lp_found) {
            require 'lp_list.php';
        } else {
            $oLP->save_item();
            require 'lp_save.php';
        }
        break;
    case 'stats':
        if (!$lp_found) {
            require 'lp_list.php';
        } else {
            $oLP->save_current();
            $oLP->save_last();

            Display::display_reduced_header();
            $output = require 'lp_stats.php';
            echo $output;
            Display::display_reduced_footer();
        }
        break;
    case 'list':
        if ($lp_found) {
            Session::write('refresh', 1);
            $oLP->save_last();
        }
        require 'lp_list.php';
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

        header('Location: '.$listUrl);
        exit;

        break;
    case 'switch_force_commit':
        if ($lp_found) {
            Session::write('refresh', 1);
            $oLP->update_default_scorm_commit();
            Display::addFlash(Display::return_message(get_lang('Updated')));
        }
        header('Location: '.$listUrl);
        exit;

        break;
    case 'switch_attempt_mode':
        if ($lp_found) {
            Session::write('refresh', 1);
            $oLP->switch_attempt_mode();
            Display::addFlash(Display::return_message(get_lang('Updated')));
        }
        header('Location: '.$listUrl);
        exit;

        break;
    case 'switch_scorm_debug':
        if ($lp_found) {
            Session::write('refresh', 1);
            $oLP->update_scorm_debug();
            Display::addFlash(Display::return_message(get_lang('Updated')));
        }
        header('Location: '.$listUrl);
        exit;

        break;
    case 'return_to_course_homepage':
        if (!$lp_found) {
            require 'lp_list.php';
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
            require 'lp_list.php';
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
        Display::addFlash(Display::return_message(get_lang('ItemUpdate successful')));
        header('Location: '.$url);
        exit;
        break;
    case 'clear_prerequisites':
        $oLP->clearPrerequisites();
        $url = api_get_self().'?action=add_item&type=step&lp_id='.intval($oLP->lp_id)."&".api_get_cidreq();
        Display::addFlash(Display::return_message(get_lang('ItemUpdate successful')));
        header('Location: '.$url);
        exit;
        break;
    case 'toggle_seriousgame':
        // activate/deactive seriousgame_mode
        if (!$is_allowed_to_edit) {
            api_not_allowed(true);
        }

        if (!$lp_found) {
            require 'lp_list.php';
        }

        Session::write('refresh', 1);
        $oLP->set_seriousgame_mode();
        require 'lp_list.php';
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
        if (!$lp_found) {
            Display::addFlash(
                Display::return_message(get_lang('No learning path found'), 'error')
            );
            break;
        }

        Session::write('refresh', 1);
        if (!isset($_POST['submit']) || empty($post_title)) {
            break;
        }

        $oLP->getFinalItemForm();
        $redirectTo = api_get_self().'?'.api_get_cidreq().'&'.http_build_query([
            'action' => 'add_item',
            'type' => 'step',
            'lp_id' => $oLP->lp_id,
        ]);
        break;
    default:
        require 'lp_list.php';
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
