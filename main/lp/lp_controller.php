<?php

/* For licensing terms, see /license.txt */

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
$debug = 0;

require_once __DIR__.'/../inc/global.inc.php';

api_protect_course_script(true);

$current_course_tool = TOOL_LEARNPATH;
$_course = api_get_course_info();

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
        $htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jquery.frameready.js" type="text/javascript" language="javascript"></script>';
        $htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jquery.highlight.js" type="text/javascript" language="javascript"></script>';
    }
}

$htmlHeadXtra[] = '<script>
function setFocus(){
    $("#idTitle").focus();
}
$(window).on("load", function () {
    setFocus();
});
</script>';
$extraParams = '';
if (isset($_REQUEST['lti_launch_id'])) {
    $extraParams .= '&lti_launch_id='.Security::remove_XSS($_REQUEST['lti_launch_id']);
}
$ajax_url = api_get_path(WEB_AJAX_PATH).'lp.ajax.php?'.api_get_cidreq().$extraParams;
$htmlHeadXtra[] = '
<script>
    /*
    Script to manipulate Learning Path items with Drag and drop
     */
    var newOrderData = "";
    function buildLPtree(in_elem, in_parent_id) {
        var item_tag = in_elem.get(0).tagName;
        var item_id =  in_elem.attr("id");
        var parent_id = item_id;

        if (item_tag == "LI" && item_id != undefined) {
            // in_parent_id de la forme UL_x
            newOrderData += item_id+"|"+get_UL_integer_id(in_parent_id)+"^";
        }

        in_elem.children().each(function () {
            buildLPtree($(this), parent_id);
        });
    }

    // return the interge part of an UL id
    // (0 for lp_item_list)
    function get_UL_integer_id(in_ul_id) {
        in_parent_integer_id = in_ul_id;
        in_parent_integer_id = in_parent_integer_id.replace("lp_item_list", "0");
        in_parent_integer_id = in_parent_integer_id.replace("UL_", "");
        return in_parent_integer_id;
    }

    $(function() {
        $(".lp_resource").sortable({
            items: ".lp_resource_element ",
            handle: ".moved", //only the class "moved"
            cursor: "move",
            connectWith: "#lp_item_list",
            placeholder: "ui-state-highlight", //defines the yellow highlight
            start: function(event, ui) {
                $(ui.item).css("width", "350px");
                $(ui.item).find(".item_data").attr("style", "");
            },
            stop: function(event, ui) {
                $(ui.item).css("width", "100%");
            }
        });

        $(".li_container .order_items").click(function(e) {
            var dir = $(this).data("dir");
            var itemId = $(this).data("id");
            var jItems = $("#lp_item_list li.li_container:not(#final_item)");
            var jItem = $("#"+ itemId);
            var index = jItems.index(jItem);
            var total = jItems.length;

            switch (dir) {
                case "up":
                    if (index != 0 && jItems[index - 1]) {
                        /*var subItems = $(jItems[index - 1]).find("li.sub_item");
                        if (subItems.length >= 0) {
                            index = index - 1;
                        }*/
                        var subItems = $(jItems[index - 1]).find("li.sub_item");
                        var parentClass = $(jItems[index - 1]).parent().parent().attr("class");
                        var parentId = $(jItems[index]).parent().parent().attr("id");
                        var myParentId = $(jItems[index - 1]).parent().parent().attr("id");
                        //console.log(parentId + " - " + myParentId);

                        // We are brothers!
                        if (parentId == myParentId) {
                            if (subItems.length > 0) {
                                var lastItem = $(jItems[index - 1]).find("li.sub_item");
                                parentIndex = jItems.index(lastItem);
                                jItem.detach().insertAfter(lastItem);
                                //console.log("not classic");
                            } else {
                                //console.log("classic");
                                jItem.detach().insertBefore(jItems[index - 1]);
                            }
                            break;
                        }

                        //console.log(parentClass);
                        if (parentClass == "record li_container") {
                            // previous is a chapter
                            var lastItem = $(jItems[index - 1]).parent().parent().find("li.li_container").last();
                            parentIndex = jItems.index(lastItem);
                            //console.log(parentIndex);
                            jItem.detach().insertAfter(jItems[parentIndex]);
                        } else {
                            jItem.detach().insertBefore(jItems[index - 1]);
                        }
                    }
                    break;
                case "down":
                     if (index != total - 1) {
                        const originIndex = index;
                        // The element is a chapter with items
                        var subItems = jItem.find("li.li_container");
                        if (subItems.length > 0) {
                            index = subItems.length + index;
                            //console.log("element is a chapter with items");
                            //console.log("new index = " + index);
                        }

                        var subItems = $(jItems[index + 1]).find("li.sub_item");
                        //console.log("next subItems.length: "+subItems.length);
                        // This is an element entering in a chapter
                        if (subItems.length > 0) {
                            // Check if im a child
                            var parentClass = jItem.parent().parent().attr("class");
                            //console.log(parentClass);
                            if (parentClass == "record li_container") {
                                // Parent position
                                var parentIndex = jItems.index(jItem.parent().parent());
                                //console.log(jItem.parent().parent().attr("id"));
                                //console.log(parentIndex);
                                jItem.detach().insertAfter(jItems[parentIndex]);
                            } else {
                                jItem.detach().insertAfter(subItems);
                            }
                            break;
                        }

                        var currentSubItems = $(jItems[index]).parent().find("li.sub_item");
                        //console.log("currentSubItems"+currentSubItems.length);

                        var parentId = $(jItems[originIndex]).parent().parent().attr("id");
                        var myParentId = $(jItems[index + 1]).parent().parent().attr("id");
                        //console.log("parent ids: "+ parentId + " - " + myParentId);

                        // We are brothers!
                        if (parentId == myParentId) {
                            if ((index + 1) < total) {
                                //console.log(index + 1);
                                //console.log("We are brothers");
                                jItem.detach().insertAfter(jItems[index + 1]);
                            }
                            break;
                        }

                        if (currentSubItems.length > 0) {
                            var parentIndex = jItems.index(jItem.parent().parent());
                            //console.log("has currentSubItems");
                            //console.log("id " + jItem.parent().parent().attr("id"));
                            //console.log("parentIndex: " + parentIndex);
                            if (parentIndex >= 0) {
                                jItem.detach().insertAfter(jItems[parentIndex]);
                                break;
                            }
                            //jItem.detach().insertAfter($(jItems[index]).parent().parent());
                        }

                        //var lastItem = $(jItems[index + 1]).parent().parent().find("li.li_container").last();
                        if (subItems.length > 0) {
                            index = originIndex;
                        }

                        if ((index + 1) < total) {
                            //console.log(index + 1);
                            //console.log("changed");
                            jItem.detach().insertAfter(jItems[index + 1]);
                        }
                     }
                     break;
            }

            //console.log("rebuild");
            buildLPtree($("#lp_item_list"), 0);

            var order = "new_order="+ newOrderData + "&a=update_lp_item_order";
            $.post(
                "'.$ajax_url.'",
                order,
                function(reponse) {
                    $("#message").html(reponse);
                    order = "";
                    newOrderData = "";
                }
            );
        });

        $("#lp_item_list").sortable({
            items: "li",
            handle: ".moved", //only the class "moved"
            cursor: "move",
            placeholder: "ui-state-highlight", //defines the yellow highlight
            update: function(event, ui) {
                buildLPtree($("#lp_item_list"), 0);
                var order = "new_order="+ newOrderData + "&a=update_lp_item_order";
                $.post(
                    "'.$ajax_url.'",
                    order,
                    function(reponse) {
                        $("#message").html(reponse);
                        order = "";
                        newOrderData = "";
                    }
                );
            },
            receive: function(event, ui) {
                var id = $(ui.item).attr("data_id");
                var type = $(ui.item).attr("data_type");
                var title = $(ui.item).attr("title");
                processReceive = true;

                if (ui.item.parent()[0]) {
                    var parent_id = $(ui.item.parent()[0]).attr("id");
                    var previous_id = $(ui.item.prev()).attr("id");

                    if (parent_id) {
                        parent_id = parent_id.split("_")[1];
                        var params = {
                            "a": "add_lp_item",
                            "id": id,
                            "parent_id": parent_id,
                            "previous_id": previous_id,
                            "type": type,
                            "title" : title
                        };

                        $.ajax({
                            type: "GET",
                            url: "'.$ajax_url.'",
                            data: params,
                            success: function(data) {
                                $("#lp_item_list").html(data);
                            }
                        });
                    }
                }
            } // End receive
        });
        processReceive = false;
    });
</script>';

$session_id = api_get_session_id();
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
    /** @var learnpath $oLP */
    $oLP = UnserializeApi::unserialize('lp', $lpObject);
    if (isset($oLP) && is_object($oLP)) {
        if ($debug) {
            error_log(' oLP is object');
        }
        if ($myrefresh == 1 ||
            empty($oLP->cc) ||
            $oLP->cc != api_get_course_id() ||
            $oLP->lp_view_session_id != $session_id
        ) {
            if ($debug) {
                error_log('Course has changed, discard lp object');
                error_log('$oLP->lp_view_session_id: '.$oLP->lp_view_session_id);
                error_log('api_get_session_id(): '.$session_id);
                error_log('$oLP->cc: '.$oLP->cc);
                error_log('api_get_course_id(): '.api_get_course_id());
            }

            if ($myrefresh == 1) {
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
if ($debug) {
    error_log('$lp_found: '.$lp_found);
    error_log('$myrefresh_id: '.$myrefresh_id);
}

$course_id = api_get_course_int_id();

if (!$lp_found || (!empty($_REQUEST['lp_id']) && $_SESSION['oLP']->get_id() != $_REQUEST['lp_id'])) {
    if ($debug > 0) {
        error_log(' oLP is not object, has changed or refresh been asked, getting new');
    }
    // Regenerate a new lp object? Not always as some pages don't need the object (like upload?)
    if (!empty($_REQUEST['lp_id']) || !empty($myrefresh_id)) {
        // Select the lp in the database and check which type it is (scorm/chamilo/aicc) to generate the
        // right object.
        if (!empty($_REQUEST['lp_id'])) {
            $lp_id = $_REQUEST['lp_id'];
        } else {
            $lp_id = $myrefresh_id;
        }
        $lp_id = (int) $lp_id;

        $lp_table = Database::get_course_table(TABLE_LP_MAIN);
        if (!empty($lp_id)) {
            $sel = "SELECT iid, lp_type FROM $lp_table WHERE c_id = $course_id AND id = $lp_id";
            if ($debug > 0) {
                error_log(' querying '.$sel);
            }
            $res = Database::query($sel);
            if (Database::num_rows($res)) {
                $row = Database::fetch_array($res);
                $lpIid = $row['iid'];
                $type = $row['lp_type'];
                if ($debug > 0) {
                    error_log('Found row type '.$type);
                    error_log('Calling constructor: '.api_get_course_id().' - '.$lp_id.' - '.api_get_user_id());
                }
                $logInfo = [
                    'tool' => TOOL_LEARNPATH,
                    'tool_id' => 0,
                    'tool_id_detail' => 0,
                    'action' => 'lp_load',
                ];
                Event::registerLog($logInfo);

                switch ($type) {
                    case 1:
                        $oLP = new learnpath(api_get_course_id(), $lpIid, api_get_user_id());
                        if ($oLP !== false) {
                            $lp_found = true;
                        }
                        break;
                    case 2:
                        $oLP = new scorm(api_get_course_id(), $lpIid, api_get_user_id());
                        if ($oLP !== false) {
                            $lp_found = true;
                        }
                        break;
                    case 3:
                        $oLP = new aicc(api_get_course_id(), $lpIid, api_get_user_id());
                        if ($oLP !== false) {
                            $lp_found = true;
                        }
                        break;
                    default:
                        $oLP = new learnpath(api_get_course_id(), $lpIid, api_get_user_id());
                        if ($oLP !== false) {
                            $lp_found = true;
                        }
                        break;
                }
            }
        } else {
            if ($debug > 0) {
                error_log(' Request[lp_id] is not numeric');
            }
        }
    } else {
        if ($debug > 0) {
            error_log(' Request[lp_id] and refresh_id were empty');
        }
    }
    if ($lp_found) {
        Session::write('oLP', $oLP);
    }
}

if ($debug > 0) {
    error_log('Passed oLP creation check');
}

$is_allowed_to_edit = api_is_allowed_to_edit(false, true, false, false);

if (isset($_SESSION['oLP'])) {
    // Reinitialises array used by javascript to update items in the TOC.
    $_SESSION['oLP']->update_queue = [];
    // We check if a tool provider
    if (isset($_REQUEST['lti_launch_id'])) {
        $ltiLaunchId = Security::remove_XSS($_REQUEST['lti_launch_id']);
        $_SESSION['oLP']->lti_launch_id = $ltiLaunchId;
    }
}

$action = !empty($_REQUEST['action']) ? $_REQUEST['action'] : '';

if ($debug) {
    error_log('Entered lp_controller.php -+- (action: '.$action.')');
}

$eventLpId = $lp_id = !empty($_REQUEST['lp_id']) ? (int) $_REQUEST['lp_id'] : 0;
if (empty($lp_id)) {
    if (isset($_SESSION['oLP'])) {
        $eventLpId = $_SESSION['oLP']->get_id();
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
        TOOL_QUIZ == $_POST['type'] &&
        !empty($_POST['title'])
    ) {
        $post_title = Exercise::format_title_variable($_POST['title']);
        if (api_get_configuration_value('save_titles_as_html')) {
            $post_title = $_POST['title'];
        }
    }
}

$redirectTo = '';
if ($debug > 0) {
    error_log('action "'.$action.'" triggered');
}

$lpListUrl = api_get_self().'?action=list&'.api_get_cidreq();

switch ($action) {
    case 'author_view':
        $teachers = [];
        $field = new ExtraField('user');
        $authorLp = $field->get_handler_field_info_by_field_variable('authorlp');
        $idExtraField = isset($authorLp['id']) ? (int) $authorLp['id'] : 0;
        if ($idExtraField != 0) {
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
        Session::write('oLP', $_SESSION['oLP']);
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
        $course_info = api_get_course_info();
        $sessionId = api_get_session_id();

        $courseName = $course_info['title'];
        $courseUrl = $course_info['course_public_url'];
        if (!empty($sessionId)) {
            $sessionInfo = api_get_session_info($sessionId);
            $courseName = $sessionInfo['name'];
            $courseUrl .= '?id_session='.$sessionId;
        }

        $url = Display::url($courseName, $courseUrl, ['title' => get_lang('GoToCourse')]);
        $coachList = CourseManager::get_coachs_from_course($sessionId, api_get_course_int_id());
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
        Display::addFlash(Display::return_message(get_lang('MessageSent')));
        $url = api_get_self().'?action=list&'.api_get_cidreq();
        header('Location: '.$url);
        exit;
        break;
    case 'add_item':
        if (!$is_allowed_to_edit) {
            api_not_allowed(true);
        }
        if (!$lp_found) {
            // Check if the learnpath ID was defined, otherwise send back to list
            require 'lp_list.php';
        } else {
            Session::write('refresh', 1);

            if (isset($_POST['submit_button']) && !empty($post_title)) {
                // If a title was submitted:

                // Updating the lp.modified_on
                $_SESSION['oLP']->set_modified_on();

                if (isset($_SESSION['post_time']) && $_SESSION['post_time'] == $_POST['post_time']) {
                    // Check post_time to ensure ??? (counter-hacking measure?)
                    require 'lp_add_item.php';
                } else {
                    Session::write('post_time', $_POST['post_time']);
                    $directoryParentId = isset($_POST['directory_parent_id']) ? $_POST['directory_parent_id'] : 0;
                    $courseInfo = api_get_course_info();
                    if (empty($directoryParentId)) {
                        $_SESSION['oLP']->generate_lp_folder($courseInfo);
                    }

                    $parent = isset($_POST['parent']) ? $_POST['parent'] : '';
                    $previous = isset($_POST['previous']) ? $_POST['previous'] : '';
                    $type = isset($_POST['type']) ? $_POST['type'] : '';
                    $path = isset($_POST['path']) ? $_POST['path'] : '';
                    $description = isset($_POST['description']) ? $_POST['description'] : '';
                    $prerequisites = isset($_POST['prerequisites']) ? $_POST['prerequisites'] : '';
                    $maxTimeAllowed = isset($_POST['maxTimeAllowed']) ? $_POST['maxTimeAllowed'] : '';

                    if ($_POST['type'] == TOOL_DOCUMENT) {

                        // form validations after submit.
                        $error = false;
                        $errMsg = '';
                        if (true === api_get_configuration_value('lp_item_prerequisite_dates')) {
                            if (!isset($_POST['extra_start_date'])) {
                                $_POST['extra_start_date'] = 0;
                            }
                            if (!isset($_POST['extra_end_date'])) {
                                $_POST['extra_end_date'] = 0;
                            }
                            $extraStartDate = $_POST['extra_start_date'];
                            $extraEndDate = $_POST['extra_end_date'];
                            if (!empty($extraStartDate) && !empty($extraEndDate)) {
                                $error = !(strtotime($extraEndDate) >= strtotime($extraStartDate));
                            }
                            if ($error) {
                                $errMsg = get_lang('StartDateMustBeBeforeTheEndDate');
                            }
                        }

                        if ($error) {
                            Display::addFlash(Display::return_message($errMsg, 'error'));
                            header('Location: '.api_request_uri());
                            exit;
                        }

                        if (isset($_POST['path']) && $_GET['edit'] != 'true') {
                            $document_id = $_POST['path'];
                        } else {
                            if ($_POST['content_lp']) {
                                $document_id = $_SESSION['oLP']->create_document(
                                    $_course,
                                    $_POST['content_lp'],
                                    $_POST['title'],
                                    'html',
                                    $directoryParentId
                                );
                            }
                        }

                        $lastItemId = $_SESSION['oLP']->add_item(
                            $parent,
                            $previous,
                            $type,
                            $document_id,
                            $post_title,
                            $description,
                            $prerequisites
                        );

                        if (!empty($lastItemId)) {
                            $params = $_POST;
                            $params['item_id'] = $lastItemId;
                            $extraFieldValues = new ExtraFieldValue('lp_item');
                            $extraFieldValues->saveFieldValues($params, true);
                        }
                    } elseif ($_POST['type'] == TOOL_READOUT_TEXT) {
                        if (isset($_POST['path']) && $_GET['edit'] != 'true') {
                            $document_id = $_POST['path'];
                        } else {
                            $document_id = $_SESSION['oLP']->createReadOutText(
                                $_course,
                                $_POST['content_lp'],
                                $_POST['title'],
                                $directoryParentId
                            );
                        }

                        $_SESSION['oLP']->add_item(
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
                        $_SESSION['oLP']->add_item(
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
                    $url = api_get_self().'?action=add_item&type=step&lp_id='.intval($_SESSION['oLP']->lp_id).'&'.api_get_cidreq();
                    header('Location: '.$url);
                    exit;
                }
            } else {
                require 'lp_add_item.php';
            }
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

                // Remove audio
                if (isset($_GET['delete_file']) && $_GET['delete_file'] == 1) {
                    $lp_item_obj->removeAudio();
                    Display::addFlash(Display::return_message(get_lang('FileDeleted')));

                    $url = api_get_self().'?action=add_audio&lp_id='.intval($_SESSION['oLP']->lp_id).'&id='.$lp_item_obj->get_id().'&'.api_get_cidreq();
                    header('Location: '.$url);
                    exit;
                }

                // Upload audio
                if (isset($_FILES['file']) && !empty($_FILES['file'])) {
                    // Updating the lp.modified_on
                    $_SESSION['oLP']->set_modified_on();
                    $lp_item_obj->addAudio();
                    Display::addFlash(Display::return_message(get_lang('UplUploadSucceeded')));
                }

                //Add audio file from documents
                if (isset($_REQUEST['document_id']) && !empty($_REQUEST['document_id'])) {
                    $_SESSION['oLP']->set_modified_on();
                    $lp_item_obj->add_audio_from_documents($_REQUEST['document_id']);
                    Display::addFlash(Display::return_message(get_lang('Updated')));
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
            learnpath::moveUpCategory($_REQUEST['id']);
        }
        require 'lp_list.php';
        break;
    case 'move_down_category':
        if (!$is_allowed_to_edit) {
            api_not_allowed(true);
        }
        if (isset($_REQUEST['id'])) {
            learnpath::moveDownCategory($_REQUEST['id']);
        }
        require 'lp_list.php';
        break;
    case 'delete_lp_category':
        if (!$is_allowed_to_edit) {
            api_not_allowed(true);
        }
        if (isset($_REQUEST['id'])) {
            $result = learnpath::deleteCategory($_REQUEST['id']);
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
        if (isset($_REQUEST['lp_name']) && !empty($_REQUEST['lp_name'])) {
            $_REQUEST['lp_name'] = trim($_REQUEST['lp_name']);
            Session::write('refresh', 1);

            if (isset($_SESSION['post_time']) && $_SESSION['post_time'] == $_REQUEST['post_time']) {
                require 'lp_add.php';
            } else {
                Session::write('post_time', $_POST['post_time']);

                $publicated_on = null;
                if (isset($_REQUEST['activate_start_date_check']) &&
                    $_REQUEST['activate_start_date_check'] == 1
                ) {
                    $publicated_on = $_REQUEST['publicated_on'];
                }

                $expired_on = null;
                if (isset($_REQUEST['activate_end_date_check']) &&
                    $_REQUEST['activate_end_date_check'] == 1
                ) {
                    $expired_on = $_REQUEST['expired_on'];
                }

                $new_lp_id = learnpath::add_lp(
                    api_get_course_id(),
                    $_REQUEST['lp_name'],
                    '',
                    'chamilo',
                    'manual',
                    '',
                    $publicated_on,
                    $expired_on,
                    $_REQUEST['category_id']
                );

                if (is_numeric($new_lp_id)) {
                    // Create temp form validator to save skills
                    $form = new FormValidator('lp_add');
                    $form->addSelect('skills', 'skills');
                    Skill::saveSkills($form, ITEM_TYPE_LEARNPATH, $new_lp_id);

                    $extraFieldValue = new ExtraFieldValue('lp');
                    $_REQUEST['item_id'] = $new_lp_id;
                    $extraFieldValue->saveFieldValues($_REQUEST);

                    // TODO: Maybe create a first directory directly to avoid bugging the user with useless queries
                    $_SESSION['oLP'] = new learnpath(
                        api_get_course_id(),
                        $new_lp_id,
                        api_get_user_id()
                    );

                    $subscribeUsers = isset($_REQUEST['subscribe_users']) ? 1 : 0;
                    $_SESSION['oLP']->setSubscribeUsers($subscribeUsers);

                    $accumulateScormTime = $_REQUEST['accumulate_scorm_time'] ?? 'true';
                    $_SESSION['oLP']->setAccumulateScormTime($accumulateScormTime);

                    $url = api_get_self().'?action=add_item&type=step&lp_id='.intval($new_lp_id).'&'.api_get_cidreq();
                    header("Location: $url&isStudentView=false");
                    exit;
                }
            }
        } else {
            require 'lp_add.php';
        }
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
        if (api_get_course_setting('enable_lp_auto_launch') == 1) {
            if (!$is_allowed_to_edit) {
                api_not_allowed(true);
            }
            if (!$lp_found) {
                require 'lp_list.php';
            } else {
                $_SESSION['oLP']->set_autolaunch($_GET['lp_id'], $_GET['status']);
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
            $url = api_get_self().'?action=add_item&type=step&lp_id='.intval($_SESSION['oLP']->lp_id).'&'.api_get_cidreq();
            header('Location: '.$url);
            exit;
        }
        break;
    case 'edit_item':
        if (!$is_allowed_to_edit) {
            api_not_allowed(true);
        }
        if (!$lp_found) {
            require 'lp_list.php';
        } else {
            Session::write('refresh', 1);
            if (isset($_POST) && !empty($post_title)) {
                // Updating the lp.modified_on
                $_SESSION['oLP']->set_modified_on();

                // TODO: mp3 edit
                $audio = [];
                if (isset($_FILES['mp3'])) {
                    $audio = $_FILES['mp3'];
                }

                $description = isset($_POST['description']) ? $_POST['description'] : '';
                $prerequisites = isset($_POST['prerequisites']) ? $_POST['prerequisites'] : '';
                $maxTimeAllowed = isset($_POST['maxTimeAllowed']) ? $_POST['maxTimeAllowed'] : '';
                $url = isset($_POST['url']) ? $_POST['url'] : '';

                // form validations after submit.
                $error = false;
                $errMsg = '';
                if (true === api_get_configuration_value('lp_item_prerequisite_dates')) {
                    if (!isset($_POST['extra_start_date'])) {
                        $_POST['extra_start_date'] = 0;
                    }
                    if (!isset($_POST['extra_end_date'])) {
                        $_POST['extra_end_date'] = 0;
                    }
                    $extraStartDate = $_POST['extra_start_date'];
                    $extraEndDate = $_POST['extra_end_date'];
                    if (!empty($extraStartDate) && !empty($extraEndDate)) {
                        $error = !(strtotime($extraEndDate) >= strtotime($extraStartDate));
                    }
                    if ($error) {
                        $errMsg = get_lang('StartDateMustBeBeforeTheEndDate');
                    }
                }

                if ($error) {
                    Display::addFlash(Display::return_message($errMsg, 'error'));
                    header('Location: '.api_request_uri());
                    exit;
                }

                $_SESSION['oLP']->edit_item(
                    $_REQUEST['id'],
                    $_POST['parent'],
                    $_POST['previous'],
                    $post_title,
                    $description,
                    $prerequisites,
                    $audio,
                    $maxTimeAllowed,
                    $url
                );

                if (isset($_POST['content_lp'])) {
                    $_SESSION['oLP']->edit_document($_course);
                }
                $is_success = true;
                $extraFieldValues = new ExtraFieldValue('lp_item');
                $extraFieldValues->saveFieldValues($_POST, true);

                Display::addFlash(Display::return_message(get_lang('Updated')));
                $url = api_get_self().'?action=add_item&type=step&lp_id='.intval($_SESSION['oLP']->lp_id).'&'.api_get_cidreq();
                header('Location: '.$url);
                exit;
            }
            if (isset($_GET['view']) && $_GET['view'] === 'build') {
                require 'lp_edit_item.php';
            } else {
                require 'lp_admin_view.php';
            }
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
                $_SESSION['oLP']->set_modified_on();
                Session::write('refresh', 1);
                $min = isset($_POST['min_'.$_POST['prerequisites']]) ? $_POST['min_'.$_POST['prerequisites']] : '';
                $max = isset($_POST['max_'.$_POST['prerequisites']]) ? $_POST['max_'.$_POST['prerequisites']] : '';

                $editPrerequisite = $_SESSION['oLP']->edit_item_prereq(
                    $_GET['id'],
                    $_POST['prerequisites'],
                    $min,
                    $max
                );

                Display::addFlash(Display::return_message(get_lang('Updated')));
                $url = api_get_self().'?action=add_item&type=step&lp_id='.intval($_SESSION['oLP']->lp_id).'&'.api_get_cidreq();
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
                $_SESSION['oLP']->set_modified_on();
                $_SESSION['oLP']->edit_item(
                    $_GET['id'],
                    $_POST['parent'],
                    $_POST['previous'],
                    $post_title,
                    $_POST['description']
                );
                $url = api_get_self().'?action=add_item&type=step&lp_id='.intval($_SESSION['oLP']->lp_id).'&'.api_get_cidreq();
                header('Location: '.$url);
                exit;
            }
            if (isset($_GET['view']) && $_GET['view'] == 'build') {
                require 'lp_move_item.php';
            } else {
                // Avoids weird behaviours see CT#967.
                $check = Security::check_token('get');
                if ($check) {
                    $_SESSION['oLP']->move_item($_GET['id'], $_GET['direction']);
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
        if ($hideScormCopyLink === 'true') {
            api_not_allowed(true);
        }

        if (!$lp_found) {
            api_location($lpListUrl);
        }

        $_SESSION['oLP']->copy();
        Display::addFlash(Display::return_message(get_lang('ItemCopied')));
        api_location($lpListUrl);
        break;
    case 'export':
        $hideScormExportLink = api_get_setting('hide_scorm_export_link');

        if ($hideScormExportLink === 'true'
            || (false === api_get_configuration_value('lp_allow_export_to_students') && !$is_allowed_to_edit)
        ) {
            api_not_allowed(true);
        }

        if (!$lp_found) {
            require 'lp_list.php';
        } else {
            $_SESSION['oLP']->scormExport();
            exit();
        }
        break;
    case 'export_to_pdf':
        $hideScormPdfLink = api_get_setting('hide_scorm_pdf_link');
        if ($hideScormPdfLink === 'true') {
            api_not_allowed(true);
        }

        // Teachers can export to PDF
        if (!$is_allowed_to_edit) {
            if (!learnpath::is_lp_visible_for_student($_SESSION['oLP']->lp_id, api_get_user_id(), $_course)) {
                api_not_allowed();
            }
        }

        if (!$lp_found) {
            require 'lp_list.php';
        } else {
            $result = $_SESSION['oLP']->scorm_export_to_pdf($_GET['lp_id']);
            if (!$result) {
                require 'lp_list.php';
            }
            exit;
        }
        break;
    case 'export_to_course_build':
        $allowExport = api_get_configuration_value('allow_lp_chamilo_export');
        if (api_is_allowed_to_edit() && $allowExport) {
            if (!$lp_found) {
                require 'lp_list.php';
            } else {
                $result = $_SESSION['oLP']->exportToCourseBuildFormat($_GET['lp_id']);
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
            $_SESSION['oLP']->delete(null, $_GET['lp_id'], 'remove');
            Skill::deleteSkillsFromItem($_GET['lp_id'], ITEM_TYPE_LEARNPATH);
            Display::addFlash(Display::return_message(get_lang('Deleted')));
            Session::erase('oLP');
            api_location($lpListUrl);
        }
        break;
    case 'toggle_category_visibility':
        if (!$is_allowed_to_edit) {
            api_not_allowed(true);
        }

        learnpath::toggleCategoryVisibility($_REQUEST['id'], $_REQUEST['new_status']);
        Display::addFlash(Display::return_message(get_lang('Updated')));
        api_location($lpListUrl);
        break;
    case 'toggle_visible':
        // Change lp visibility (inside lp tool).
        if (!$is_allowed_to_edit) {
            api_not_allowed(true);
        }

        learnpath::toggle_visibility($_REQUEST['lp_id'], $_REQUEST['new_status']);
        Display::addFlash(Display::return_message(get_lang('Updated')));
        api_location($lpListUrl);

        break;
    case 'toggle_category_publish':
        if (!$is_allowed_to_edit) {
            api_not_allowed(true);
        }

        learnpath::toggleCategoryPublish($_REQUEST['id'], $_REQUEST['new_status']);
        Display::addFlash(Display::return_message(get_lang('Updated')));
        api_location($lpListUrl);
        break;
    case 'toggle_publish':
        // Change lp published status (visibility on homepage).
        if (!$is_allowed_to_edit) {
            api_not_allowed(true);
        }

        learnpath::toggle_publish($_REQUEST['lp_id'], $_REQUEST['new_status']);
        Display::addFlash(Display::return_message(get_lang('Updated')));
        api_location($lpListUrl);

        break;
    case 'move_lp_up':
        // Change lp published status (visibility on homepage)
        if (!$is_allowed_to_edit) {
            api_not_allowed(true);
        }
        if (!$lp_found) {
            require 'lp_list.php';
        } else {
            learnpath::move_up($_REQUEST['lp_id'], $_REQUEST['category_id']);
            Display::addFlash(Display::return_message(get_lang('Updated')));
            require 'lp_list.php';
        }
        break;
    case 'move_lp_down':
        // Change lp published status (visibility on homepage)
        if (!$is_allowed_to_edit) {
            api_not_allowed(true);
        }
        if (!$lp_found) {
            require 'lp_list.php';
        } else {
            learnpath::move_down($_REQUEST['lp_id'], $_REQUEST['category_id']);
            Display::addFlash(Display::return_message(get_lang('Updated')));
            require 'lp_list.php';
        }
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
    case 'update_lp':
        if (!$is_allowed_to_edit) {
            api_not_allowed(true);
        }
        if (!$lp_found) {
            require 'lp_list.php';
        } else {
            Session::write('refresh', 1);
            $_SESSION['oLP']->set_name($_REQUEST['lp_name']);
            $author = $_REQUEST['lp_author'];
            $_SESSION['oLP']->set_author($author);
            // TODO (as of Chamilo 1.8.8): Check in the future whether this field is needed.
            $_SESSION['oLP']->set_encoding($_REQUEST['lp_encoding']);

            if (isset($_REQUEST['lp_maker'])) {
                $_SESSION['oLP']->set_maker($_REQUEST['lp_maker']);
            }
            if (isset($_REQUEST['lp_proximity'])) {
                $_SESSION['oLP']->set_proximity($_REQUEST['lp_proximity']);
            }
            $_SESSION['oLP']->set_theme($_REQUEST['lp_theme']);

            $hide_toc_frame = null;
            if (isset($_REQUEST['hide_toc_frame']) && $_REQUEST['hide_toc_frame'] == 1) {
                $hide_toc_frame = $_REQUEST['hide_toc_frame'];
            }
            $_SESSION['oLP']->set_hide_toc_frame($hide_toc_frame);
            $_SESSION['oLP']->set_prerequisite(isset($_POST['prerequisites']) ? (int) $_POST['prerequisites'] : 0);
            $_SESSION['oLP']->setAccumulateWorkTime(isset($_REQUEST['accumulate_work_time']) ? $_REQUEST['accumulate_work_time'] : 0);
            $_SESSION['oLP']->set_use_max_score(isset($_POST['use_max_score']) ? 1 : 0);

            $subscribeUsers = isset($_REQUEST['subscribe_users']) ? 1 : 0;
            $_SESSION['oLP']->setSubscribeUsers($subscribeUsers);

            $accumulateScormTime = isset($_REQUEST['accumulate_scorm_time']) ? $_REQUEST['accumulate_scorm_time'] : 'true';
            $_SESSION['oLP']->setAccumulateScormTime($accumulateScormTime);

            $publicated_on = null;
            if (isset($_REQUEST['activate_start_date_check']) && $_REQUEST['activate_start_date_check'] == 1) {
                $publicated_on = $_REQUEST['publicated_on'];
            }

            $expired_on = null;
            if (isset($_REQUEST['activate_end_date_check']) && $_REQUEST['activate_end_date_check'] == 1) {
                $expired_on = $_REQUEST['expired_on'];
            }

            $_SESSION['oLP']->setCategoryId($_REQUEST['category_id']);
            $_SESSION['oLP']->set_modified_on();
            $_SESSION['oLP']->set_publicated_on($publicated_on);
            $_SESSION['oLP']->set_expired_on($expired_on);

            if (isset($_REQUEST['remove_picture']) && $_REQUEST['remove_picture']) {
                $_SESSION['oLP']->delete_lp_image();
            }

            $extraFieldValue = new ExtraFieldValue('lp');
            $_REQUEST['item_id'] = $_SESSION['oLP']->lp_id;
            $extraFieldValue->saveFieldValues($_REQUEST);

            if ($_FILES['lp_preview_image']['size'] > 0) {
                $_SESSION['oLP']->upload_image($_FILES['lp_preview_image']);
            }

            $form = new FormValidator('form1');
            $form->addSelect('skills', 'skills');
            Skill::saveSkills($form, ITEM_TYPE_LEARNPATH, $_SESSION['oLP']->get_id());

            // It saves the next learnpath id
            if (isset($_REQUEST['next_lp_id']) && true === api_get_configuration_value('lp_enable_flow')) {
                learnpath::saveTheNextLp($_SESSION['oLP']->lp_id, $_REQUEST['next_lp_id']);
            }

            if (api_get_setting('search_enabled') === 'true') {
                require_once api_get_path(LIBRARY_PATH).'specific_fields_manager.lib.php';
                $specific_fields = get_specific_field_list();
                foreach ($specific_fields as $specific_field) {
                    $_SESSION['oLP']->set_terms_by_prefix($_REQUEST[$specific_field['code']], $specific_field['code']);
                    $new_values = explode(',', trim($_REQUEST[$specific_field['code']]));
                    if (!empty($new_values)) {
                        array_walk($new_values, 'trim');
                        delete_all_specific_field_value(
                            api_get_course_id(),
                            $specific_field['id'],
                            TOOL_LEARNPATH,
                            $_SESSION['oLP']->lp_id
                        );

                        foreach ($new_values as $value) {
                            if (!empty($value)) {
                                add_specific_field_value(
                                    $specific_field['id'],
                                    api_get_course_id(),
                                    TOOL_LEARNPATH,
                                    $_SESSION['oLP']->lp_id,
                                    $value
                                );
                            }
                        }
                    }
                }
            }
            Display::addFlash(Display::return_message(get_lang('Updated')));
            $url = api_get_self().'?action=add_item&type=step&lp_id='.intval($_SESSION['oLP']->lp_id).'&'.api_get_cidreq();
            header('Location: '.$url);
            exit;
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
                $_SESSION['oLP']->delete_item($_REQUEST['id']);
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
            $_SESSION['oLP']->restart();
            require 'lp_view.php';
        }
        break;
    case 'last':
        if (!$lp_found) {
            require 'lp_list.php';
        } else {
            $_SESSION['oLP']->last();
            require 'lp_view.php';
        }
        break;
    case 'first':
        if (!$lp_found) {
            require 'lp_list.php';
        } else {
            $_SESSION['oLP']->first();
            require 'lp_view.php';
        }
        break;
    case 'next':
        if (!$lp_found) {
            require 'lp_list.php';
        } else {
            $_SESSION['oLP']->next();
            require 'lp_view.php';
        }
        break;
    case 'previous':
        if (!$lp_found) {
            require 'lp_list.php';
        } else {
            $_SESSION['oLP']->previous();
            require 'lp_view.php';
        }
        break;
    case 'content':
        if (!$lp_found) {
            require 'lp_list.php';
        } else {
            $_SESSION['oLP']->save_last();
            $_SESSION['oLP']->set_current_item($_GET['item_id']);
            $_SESSION['oLP']->start_current_item();
            require 'lp_content.php';
        }
        break;
    case 'view':
        if (!$lp_found) {
            require 'lp_list.php';
        } else {
            if (!empty($_REQUEST['item_id'])) {
                $_SESSION['oLP']->set_current_item($_REQUEST['item_id']);
            }
            require 'lp_view.php';
        }
        break;
    case 'save':
        if (!$lp_found) {
            require 'lp_list.php';
        } else {
            $_SESSION['oLP']->save_item();
            require 'lp_save.php';
        }
        break;
    case 'stats':
        if (!$lp_found) {
            require 'lp_list.php';
        } else {
            $_SESSION['oLP']->save_current();
            $_SESSION['oLP']->save_last();
            $output = require 'lp_stats.php';
            echo $output;
        }
        break;
    case 'list':
        if ($lp_found) {
            Session::write('refresh', 1);
            $_SESSION['oLP']->save_last();
        }
        require 'lp_list.php';
        break;
    case 'mode':
        // Switch between fullscreen and embedded mode.
        $mode = $_REQUEST['mode'];
        if ($mode == 'fullscreen') {
            $_SESSION['oLP']->mode = 'fullscreen';
        } elseif ($mode == 'embedded') {
            $_SESSION['oLP']->mode = 'embedded';
        } elseif ($mode == 'embedframe') {
            $_SESSION['oLP']->mode = 'embedframe';
        } elseif ($mode == 'impress') {
            $_SESSION['oLP']->mode = 'impress';
        }
        require 'lp_view.php';
        break;
    case 'switch_view_mode':
        if (!$lp_found) {
            require 'lp_list.php';
        }
        if (Security::check_token('get')) {
            Session::write('refresh', 1);
            $_SESSION['oLP']->update_default_view_mode();
        }
        require 'lp_list.php';
        break;
    case 'switch_force_commit':
        if (!$lp_found) {
            require 'lp_list.php';
        }
        Session::write('refresh', 1);
        $_SESSION['oLP']->update_default_scorm_commit();
        require 'lp_list.php';
        break;
    case 'switch_attempt_mode':
        if (!$lp_found) {
            require 'lp_list.php';
        }
        Session::write('refresh', 1);
        $_SESSION['oLP']->switch_attempt_mode();
        require 'lp_list.php';
        break;
    case 'switch_scorm_debug':
        if (!$lp_found) {
            require 'lp_list.php';
        }
        Session::write('refresh', 1);
        $_SESSION['oLP']->update_scorm_debug();
        require 'lp_list.php';
        break;
    case 'intro_cmdAdd':
        // Add introduction section page.
        break;
    case 'return_to_course_homepage':
        if (!$lp_found) {
            require 'lp_list.php';
        } else {
            $_SESSION['oLP']->save_current();
            $_SESSION['oLP']->save_last();
            if ($debug > 0) {
                error_log('save_current()');
                error_log('save_last()');
            }
            $url = api_get_path(WEB_COURSE_PATH).api_get_course_path().'/index.php?id_session='.api_get_session_id();
            $redirectTo = isset($_GET['redirectTo']) ? $_GET['redirectTo'] : '';
            switch ($redirectTo) {
                case 'lp_list':
                    $url = 'lp_controller.php?'.api_get_cidreq();
                    break;
                case 'my_courses':
                    $url = api_get_path(WEB_PATH).'user_portal.php';
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
                $_SESSION['oLP']->set_current_item($_REQUEST['item_id']);
            }
            require 'lp_impress.php';
        }
        break;
    case 'set_previous_step_as_prerequisite':
        $_SESSION['oLP']->set_previous_step_as_prerequisite_for_all_items();
        $url = api_get_self().'?action=add_item&type=step&lp_id='.intval($_SESSION['oLP']->lp_id)."&".api_get_cidreq();
        Display::addFlash(Display::return_message(get_lang('ItemUpdated')));
        header('Location: '.$url);
        exit;
        break;
    case 'clear_prerequisites':
        $_SESSION['oLP']->clear_prerequisites();
        $url = api_get_self().'?action=add_item&type=step&lp_id='.intval($_SESSION['oLP']->lp_id)."&".api_get_cidreq();
        Display::addFlash(Display::return_message(get_lang('ItemUpdated')));
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
        $_SESSION['oLP']->set_seriousgame_mode();
        require 'lp_list.php';
        break;
    case 'create_forum':
        if (!isset($_GET['id'])) {
            break;
        }

        $selectedItem = null;
        foreach ($_SESSION['oLP']->items as $item) {
            if ($item->db_id == $_GET['id']) {
                $selectedItem = $item;
            }
        }

        if (!empty($selectedItem)) {
            $forumThread = $selectedItem->getForumThread(
                $_SESSION['oLP']->course_int_id,
                $_SESSION['oLP']->lp_session_id
            );

            if (empty($forumThread)) {
                require '../forum/forumfunction.inc.php';

                $forumCategory = getForumCategoryByTitle(
                    get_lang('LearningPaths'),
                    $_SESSION['oLP']->course_int_id,
                    $_SESSION['oLP']->lp_session_id
                );

                $forumCategoryId = !empty($forumCategory) ? $forumCategory['cat_id'] : 0;

                if (empty($forumCategoryId)) {
                    $forumCategoryId = store_forumcategory(
                        [
                            'lp_id' => 0,
                            'forum_category_title' => get_lang('LearningPaths'),
                            'forum_category_comment' => null,
                        ],
                        [],
                        false
                    );
                }

                if (!empty($forumCategoryId)) {
                    $forum = $_SESSION['oLP']->getForum(
                        $_SESSION['oLP']->lp_session_id
                    );

                    $forumId = !empty($forum) ? $forum['forum_id'] : 0;

                    if (empty($forumId)) {
                        $forumId = $_SESSION['oLP']->createForum($forumCategoryId);
                    }

                    if (!empty($forumId)) {
                        $selectedItem->createForumThread($forumId);
                    }
                }
            }
        }

        header('Location:'.api_get_self().'?'.http_build_query([
            'action' => 'add_item',
            'type' => 'step',
            'lp_id' => $_SESSION['oLP']->lp_id,
        ]));
        exit;

        break;
    case 'report':
        require 'lp_report.php';
        break;
    case 'dissociate_forum':
        if (!isset($_GET['id'])) {
            break;
        }

        $selectedItem = null;
        foreach ($_SESSION['oLP']->items as $item) {
            if ($item->db_id != $_GET['id']) {
                continue;
            }
            $selectedItem = $item;
        }

        if (!empty($selectedItem)) {
            $forumThread = $selectedItem->getForumThread(
                $_SESSION['oLP']->course_int_id,
                $_SESSION['oLP']->lp_session_id
            );

            if (!empty($forumThread)) {
                $dissociated = $selectedItem->dissociateForumThread($forumThread['iid']);

                if ($dissociated) {
                    Display::addFlash(
                        Display::return_message(get_lang('ForumDissociate'), 'success')
                    );
                }
            }
        }

        header('Location:'.api_get_self().'?'.http_build_query([
            'action' => 'add_item',
            'type' => 'step',
            'lp_id' => $_SESSION['oLP']->lp_id,
        ]));
        exit;
        break;
    case 'add_final_item':
        if (!$lp_found) {
            Display::addFlash(
                Display::return_message(get_lang('NoLPFound'), 'error')
            );
            break;
        }

        Session::write('refresh', 1);
        if (!isset($_POST['submit']) || empty($post_title)) {
            break;
        }

        $_SESSION['oLP']->getFinalItemForm();
        $redirectTo = api_get_self().'?'.api_get_cidreq().'&'.http_build_query([
            'action' => 'add_item',
            'type' => 'step',
            'lp_id' => intval($_SESSION['oLP']->lp_id),
        ]);
        break;
    default:
        require 'lp_list.php';
        break;
}

if (!empty($_SESSION['oLP'])) {
    $_SESSION['lpobject'] = serialize($_SESSION['oLP']);
    if ($debug > 0) {
        error_log('lpobject is serialized in session', 0);
    }
}

if (!empty($redirectTo)) {
    header("Location: $redirectTo");
    exit;
}
