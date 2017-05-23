<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * Controller script. Prepares the common background variables to give to the scripts corresponding to
 * the requested action
 * @todo remove repeated if $lp_found redirect
 * @package chamilo.learnpath
 * @author Yannick Warnier <ywarnier@beeznest.org>
 */

// Flag to allow for anonymous user - needs to be set before global.inc.php.
$use_anonymous = true;

$debug = 0;
if ($debug > 0) error_log('New LP -+- Entered lp_controller.php -+- (action: '.$_REQUEST['action'].')', 0);

// Language files that needs to be included.
if (isset($_GET['action'])) {
    if ($_GET['action'] === 'export') {
        // Only needed on export.
        $language_file[] = 'hotspot';
    }
}

// Including the global initialization file.
require_once __DIR__.'/../inc/global.inc.php';
$current_course_tool = TOOL_LEARNPATH;
$_course = api_get_course_info();

$glossaryExtraTools = api_get_setting('show_glossary_in_extra_tools');
$showGlossary = in_array($glossaryExtraTools, array('true', 'lp', 'exercise_and_lp'));

if ($showGlossary) {
    if (api_get_setting('show_glossary_in_documents') === 'ismanual' ||
        api_get_setting('show_glossary_in_documents') === 'isautomatic'
    ) {
        $htmlHeadXtra[] = '<script>
    <!--
        var jQueryFrameReadyConfigPath = \'' . api_get_jquery_web_path().'\';
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
$(window).load(function () {
    setFocus();
});
</script>';
$ajax_url = api_get_path(WEB_AJAX_PATH).'lp.ajax.php';
$htmlHeadXtra[] = '
<script>
    /*
    Script to manipulate Learning Path items with Drag and drop
     */
    var newOrderData = "";
    var lptree_debug = "";  // for debug
    var lp_id_list = "";    // for debug

    // uncomment for some debug display utility
    /*
    $(document).ready(function() {
        buildLPtree_debug($("#lp_item_list"), 0, 0);
        alert(lp_id_list+"\n\n"+lptree_debug);
    });
    */

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

    // same than buildLPtree with some text display for debug in string lptree_debug
    function buildLPtree_debug(in_elem, in_lvl, in_parent_id) {
        var item_tag = in_elem.get(0).tagName;
        var item_id =  in_elem.attr("id");
        var parent_id = item_id;

        if (item_tag == "LI" && item_id != undefined) {
            for (i=0; i < 4 * in_lvl; i++) {
                lptree_debug += " ";
            }
            lptree_debug += " Lvl="+(in_lvl - 1)/2+" ("+item_tag+" "+item_id+" Fils de="+in_parent_id+") \n";
            // in_parent_id de la forme UL_x
            lp_id_list += item_id+"|"+get_UL_integer_id(in_parent_id)+"^";
        }

        in_elem.children().each(function () {
            buildLPtree_debug($(this), in_lvl + 1, parent_id);
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
                $(ui.item).css("width", "160px");
                $(ui.item).find(".item_data").attr("style", "");
            },
            stop: function(event, ui) {
                $(ui.item).css("width", "100%");
            }
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
                    function(reponse){
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
                            async: false,
                            success: function(data) {
                                if (data == -1) {
                                } else {
                                    $(".normal-message").hide();
                                    $(ui.item).attr("id", data);
                                    $(ui.item).addClass("lp_resource_element_new");
                                    $(ui.item).find(".item_data").attr("style", "");
                                    $(ui.item).addClass("record li_container");
                                    $(ui.item).removeClass("lp_resource_element");
                                    $(ui.item).removeClass("doc_resource");
                                }
                            }
                        });
                    }
                }//
            }//end receive
        });
        processReceive = false;
    });
</script>';

$session_id = api_get_session_id();
api_protect_course_script(true);

$lpfound = false;
$myrefresh = 0;
$myrefresh_id = 0;

if (!empty($_SESSION['refresh']) && $_SESSION['refresh'] == 1) {
    // Check if we should do a refresh of the oLP object (for example after editing the LP).
    // If refresh is set, we regenerate the oLP object from the database (kind of flush).
    Session::erase('refresh');
    $myrefresh = 1;
    if ($debug > 0) error_log('New LP - Refresh asked', 0);
}

if ($debug > 0) error_log('New LP - Passed refresh check', 0);

if (!empty($_REQUEST['dialog_box'])) {
    $dialog_box = stripslashes(urldecode($_REQUEST['dialog_box']));
}

$lp_controller_touched = 1;
$lp_found = false;

if (isset($_SESSION['lpobject'])) {
    if ($debug > 0) error_log('New LP - SESSION[lpobject] is defined', 0);
    $oLP = unserialize($_SESSION['lpobject']);
    if (isset($oLP) && is_object($oLP)) {
        if ($debug > 0) error_log('New LP - oLP is object', 0);
        if ($myrefresh == 1 ||
            empty($oLP->cc) ||
            $oLP->cc != api_get_course_id() ||
            $oLP->lp_view_session_id != $session_id ||
            $oLP->scorm_debug == '1'
        ) {
            if ($debug > 0) error_log('New LP - Course has changed, discard lp object', 0);
            if ($myrefresh == 1) { $myrefresh_id = $oLP->get_id(); }
            $oLP = null;
            Session::erase('oLP');
            Session::erase('lpobject');
        } else {
            $_SESSION['oLP'] = $oLP;
            $lp_found = true;
        }
    }
}

$course_id = api_get_course_int_id();

if ($debug > 0) error_log('New LP - Passed data remains check', 0);

if (!$lp_found || (!empty($_REQUEST['lp_id']) && $_SESSION['oLP']->get_id() != $_REQUEST['lp_id'])) {
    if ($debug > 0) error_log('New LP - oLP is not object, has changed or refresh been asked, getting new', 0);
    // Regenerate a new lp object? Not always as some pages don't need the object (like upload?)
    if (!empty($_REQUEST['lp_id']) || !empty($myrefresh_id)) {
        if ($debug > 0) error_log('New LP - lp_id is defined', 0);
        // Select the lp in the database and check which type it is (scorm/dokeos/aicc) to generate the
        // right object.
        if (!empty($_REQUEST['lp_id'])) {
            $lp_id = intval($_REQUEST['lp_id']);
        } else {
            $lp_id = intval($myrefresh_id);
        }

        $lp_table = Database::get_course_table(TABLE_LP_MAIN);
        if (is_numeric($lp_id)) {
            $sel = "SELECT lp_type FROM $lp_table WHERE c_id = $course_id AND id = $lp_id";
            if ($debug > 0) error_log('New LP - querying '.$sel, 0);
            $res = Database::query($sel);

            if (Database::num_rows($res)) {
                $row = Database::fetch_array($res);
                $type = $row['lp_type'];
                if ($debug > 0) error_log('New LP - found row - type '.$type.' - Calling constructor with '.api_get_course_id().' - '.$lp_id.' - '.api_get_user_id(), 0);
                switch ($type) {
                    case 1:
                        if ($debug > 0) error_log('New LP - found row - type dokeos - Calling constructor with '.api_get_course_id().' - '.$lp_id.' - '.api_get_user_id(), 0);

                        $oLP = new learnpath(api_get_course_id(), $lp_id, api_get_user_id());
                        if ($oLP !== false) {
                            $lp_found = true;
                        } else {
                            error_log($oLP->error, 0);
                        }
                        break;
                    case 2:
                        if ($debug > 0) error_log('New LP - found row - type scorm - Calling constructor with '.api_get_course_id().' - '.$lp_id.' - '.api_get_user_id(), 0);
                        $oLP = new scorm(api_get_course_id(), $lp_id, api_get_user_id());
                        if ($oLP !== false) {
                            $lp_found = true;
                        } else {
                            error_log($oLP->error, 0);
                        }
                        break;
                    case 3:
                        if ($debug > 0) error_log('New LP - found row - type aicc - Calling constructor with '.api_get_course_id().' - '.$lp_id.' - '.api_get_user_id(), 0);
                        $oLP = new aicc(api_get_course_id(), $lp_id, api_get_user_id());
                        if ($oLP !== false) {
                            $lp_found = true;
                        } else {
                            error_log($oLP->error, 0);
                        }
                        break;
                    default:
                        if ($debug > 0) error_log('New LP - found row - type other - Calling constructor with '.api_get_course_id().' - '.$lp_id.' - '.api_get_user_id(), 0);
                        $oLP = new learnpath(api_get_course_id(), $lp_id, api_get_user_id());
                        if ($oLP !== false) {
                            $lp_found = true;
                        } else {
                            error_log($oLP->error, 0);
                        }
                        break;
                }
            }
        } else {
            if ($debug > 0) error_log('New LP - Request[lp_id] is not numeric', 0);
        }
    } else {
        if ($debug > 0) error_log('New LP - Request[lp_id] and refresh_id were empty', 0);
    }
    if ($lp_found) {
        $_SESSION['oLP'] = $oLP;
    }
}

if ($debug > 0) error_log('New LP - Passed oLP creation check', 0);

$is_allowed_to_edit = api_is_allowed_to_edit(false, true, false, false);

if (isset($_SESSION['oLP'])) {
    $_SESSION['oLP']->update_queue = array();
    // Reinitialises array used by javascript to update items in the TOC.
}

if (isset($_GET['isStudentView']) && $_GET['isStudentView'] == 'true') {
    if (isset($_REQUEST['action']) && !in_array($_REQUEST['action'], ['list', 'view', 'view_category'])) {
        if (!empty($_REQUEST['lp_id'])) {
            $_REQUEST['action'] = 'view';
        } elseif($_REQUEST['action'] == 'view_category') {
            $_REQUEST['action'] = 'view_category';
        } else {
            $_REQUEST['action'] = 'list';
        }
    }
} else {
    if ($is_allowed_to_edit) {
        if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'view' && !isset($_REQUEST['exeId'])) {
            $_REQUEST['action'] = 'build';
        }
    }
}

$action = (!empty($_REQUEST['action']) ? $_REQUEST['action'] : '');

// format title to be displayed correctly if QUIZ
$post_title = "";
if (isset($_POST['title'])) {
    $post_title = Security::remove_XSS($_POST['title']);
    if (isset($_POST['type']) && isset($_POST['title']) && $_POST['type'] == TOOL_QUIZ && !empty($_POST['title'])) {
        $post_title = Exercise::format_title_variable($_POST['title']);
    }
}

$redirectTo = '';
if ($debug > 0) {
    error_log('New LP - action "'.$action.'" triggered');
}

switch ($action) {
    case 'add_item':
        if (!$is_allowed_to_edit) {
            api_not_allowed(true);
        }
        if (!$lp_found) {
            //check if the learnpath ID was defined, otherwise send back to list
            if ($debug > 0) {
                error_log('New LP - No learnpath given for add item', 0);
            }
            require 'lp_list.php';
        } else {
            $_SESSION['refresh'] = 1;

            if (isset($_POST['submit_button']) && !empty($post_title)) {
                // If a title was submitted:

                //Updating the lp.modified_on
                $_SESSION['oLP']->set_modified_on();

                if (isset($_SESSION['post_time']) && $_SESSION['post_time'] == $_POST['post_time']) {
                    // Check post_time to ensure ??? (counter-hacking measure?)
                    require 'lp_add_item.php';
                } else {
                    $_SESSION['post_time'] = $_POST['post_time'];
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

                        $new_item_id = $_SESSION['oLP']->add_item(
                            $parent,
                            $previous,
                            $type,
                            $document_id,
                            $post_title,
                            $description,
                            $prerequisites
                        );
                    } else {
                        // For all other item types than documents, load the item using the item type and path rather than its ID.
                        $new_item_id = $_SESSION['oLP']->add_item(
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
            //check if the learnpath ID was defined, otherwise send back to list
            if ($debug > 0) {
                error_log('New LP - No learnpath given for add audio', 0);
            }
            require 'lp_list.php';
        } else {
            $_SESSION['refresh'] = 1;

            if (isset($_REQUEST['id'])) {
                $lp_item_obj = new learnpathItem($_REQUEST['id']);

                // Remove audio
                if (isset($_GET['delete_file']) && $_GET['delete_file'] == 1) {
                    $lp_item_obj->remove_audio();

                    $url = api_get_self().'?action=add_audio&lp_id='.intval($_SESSION['oLP']->lp_id).'&id='.$lp_item_obj->get_id().'&'.api_get_cidreq();
                    header('Location: '.$url);
                    exit;
                }

                // Upload audio
                if (isset($_FILES['file']) && !empty($_FILES['file'])) {
                    // Updating the lp.modified_on
                    $_SESSION['oLP']->set_modified_on();
                    $lp_item_obj->add_audio();
                }

                //Add audio file from documents
                if (isset($_REQUEST['document_id']) && !empty($_REQUEST['document_id'])) {
                    $_SESSION['oLP']->set_modified_on();
                    $lp_item_obj->add_audio_from_documents($_REQUEST['document_id']);
                }

                // Display.
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
            learnpath::deleteCategory($_REQUEST['id']);
        }
        require 'lp_list.php';
        break;
    case 'add_lp':
        if (!$is_allowed_to_edit) {
            api_not_allowed(true);
        }
        if (isset($_REQUEST['lp_name']) && !empty($_REQUEST['lp_name'])) {
            $_REQUEST['lp_name'] = trim($_REQUEST['lp_name']);
            $_SESSION['refresh'] = 1;

            if (isset($_SESSION['post_time']) && $_SESSION['post_time'] == $_REQUEST['post_time']) {
                require 'lp_add.php';
            } else {
                $_SESSION['post_time'] = $_REQUEST['post_time'];

                if (isset($_REQUEST['activate_start_date_check']) &&
                    $_REQUEST['activate_start_date_check'] == 1
                ) {
                    $publicated_on = $_REQUEST['publicated_on'];
                } else {
                    $publicated_on = null;
                }

                if (isset($_REQUEST['activate_end_date_check']) &&
                    $_REQUEST['activate_end_date_check'] == 1
                ) {
                    $expired_on = $_REQUEST['expired_on'];
                } else {
                    $expired_on = null;
                }

                $new_lp_id = learnpath::add_lp(
                    api_get_course_id(),
                    Security::remove_XSS($_REQUEST['lp_name']),
                    '',
                    'chamilo',
                    'manual',
                    '',
                    $publicated_on,
                    $expired_on,
                    $_REQUEST['category_id']
                );

                if (is_numeric($new_lp_id)) {
                    // TODO: Maybe create a first directory directly to avoid bugging the user with useless queries
                    $_SESSION['oLP'] = new learnpath(
                        api_get_course_id(),
                        $new_lp_id,
                        api_get_user_id()
                    );

                    $accumulateScormTime = isset($_REQUEST['accumulate_scorm_time']) ? $_REQUEST['accumulate_scorm_time'] : 'true';
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
            error_log('New LP - No learnpath given for admin_view', 0);
            require 'lp_list.php';
        } else {
            $_SESSION['refresh'] = 1;
            require 'lp_admin_view.php';
        }
        break;
    case 'auto_launch':
        if (api_get_course_setting('enable_lp_auto_launch') == 1) { //Redirect to a specific LP
            if (!$is_allowed_to_edit) {
                api_not_allowed(true);
            }
            if (!$lp_found) {
                error_log('New LP - No learnpath given for set_autolaunch', 0);
                require 'lp_list.php';
            }
            else {
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
            error_log('New LP - No learnpath given for build', 0);
            require 'lp_list.php';
        } else {
            $_SESSION['refresh'] = 1;
            //require 'lp_build.php';
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
            error_log('New LP - No learnpath given for edit item', 0);
            require 'lp_list.php';
        } else {
            $_SESSION['refresh'] = 1;
            if (isset($_POST['submit_button']) && !empty($post_title)) {
                //Updating the lp.modified_on
                $_SESSION['oLP']->set_modified_on();

                // TODO: mp3 edit
                $audio = array();
                if (isset($_FILES['mp3'])) {
                    $audio = $_FILES['mp3'];
                }

                $description = isset($_POST['description']) ? $_POST['description'] : '';
                $prerequisites = isset($_POST['prerequisites']) ? $_POST['prerequisites'] : '';
                $maxTimeAllowed = isset($_POST['maxTimeAllowed']) ? $_POST['maxTimeAllowed'] : '';
                $url = isset($_POST['url']) ? $_POST['url'] : '';

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

                Display::addFlash(Display::return_message(get_lang('Updated')));

                $url = api_get_self().'?action=add_item&type=step&lp_id='.intval($_SESSION['oLP']->lp_id).'&'.api_get_cidreq();
                header('Location: '.$url);
                exit;
            }
            if (isset($_GET['view']) && $_GET['view'] == 'build') {
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
            error_log('New LP - No learnpath given for edit item prereq', 0);
            require 'lp_list.php';
        } else {
            if (isset($_POST['submit_button'])) {
                //Updating the lp.modified_on
                $_SESSION['oLP']->set_modified_on();
                $_SESSION['refresh'] = 1;
                $editPrerequisite = $_SESSION['oLP']->edit_item_prereq(
                    $_GET['id'],
                    $_POST['prerequisites'],
                    $_POST['min_'.$_POST['prerequisites']],
                    $_POST['max_'.$_POST['prerequisites']]
                );

                if ($editPrerequisite) {
                    $is_success = true;
                }

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
            error_log('New LP - No learnpath given for move item', 0);
            require 'lp_list.php';
        } else {
            $_SESSION['refresh'] = 1;
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
                $is_success = true;
                $url = api_get_self().'?action=add_item&type=step&lp_id='.intval($_SESSION['oLP']->lp_id).'&'.api_get_cidreq();
                header('Location: '.$url);
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
            error_log('New LP - No learnpath given for view item', 0); require 'lp_list.php';
        } else {
            $_SESSION['refresh'] = 1;
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
            error_log('New LP - No learnpath given for copy', 0);
            require 'lp_list.php';
        } else {
            $_SESSION['oLP']->copy();
        }
        require 'lp_list.php';
        break;
    case 'export':
        if (!$is_allowed_to_edit) {
            api_not_allowed(true);
        }
        $hideScormExportLink = api_get_setting('hide_scorm_export_link');
        if ($hideScormExportLink === 'true') {
            api_not_allowed(true);
        }
        if (!$lp_found) {
            error_log('New LP - No learnpath given for export', 0);
            require 'lp_list.php';
        } else {
            $_SESSION['oLP']->scorm_export();
            exit();
        }
        break;
    case 'export_to_pdf':
        if (!learnpath::is_lp_visible_for_student($_SESSION['oLP']->lp_id, api_get_user_id())) {
            api_not_allowed();
        }
        $hideScormPdfLink = api_get_setting('hide_scorm_pdf_link');
        if ($hideScormPdfLink === 'true') {
            api_not_allowed(true);
        }

        if (!$lp_found) {
            error_log('New LP - No learnpath given for export_to_pdf', 0);
            require 'lp_list.php';
        } else {
            $result = $_SESSION['oLP']->scorm_export_to_pdf($_GET['lp_id']);
            if (!$result) {
                require 'lp_list.php';
            }
            exit;
        }
        break;
    case 'delete':
        if (!$is_allowed_to_edit) {
            api_not_allowed(true);
        }
        if (!$lp_found) {
            error_log('New LP - No learnpath given for delete', 0);
            require 'lp_list.php';
        } else {
            $_SESSION['refresh'] = 1;
            $_SESSION['oLP']->delete(null, $_GET['lp_id'], 'remove');
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

        header('Location: '.api_get_self().'?'.api_get_cidreq());
        exit;
    case 'toggle_visible':
        // Change lp visibility (inside lp tool).
        if (!$is_allowed_to_edit) {
            api_not_allowed(true);
        }

        if (!$lp_found) {
            error_log('New LP - No learnpath given for visibility', 0);
            require 'lp_list.php';
        } else {
            learnpath::toggle_visibility($_REQUEST['lp_id'], $_REQUEST['new_status']);
            require 'lp_list.php';
        }
        break;
    case 'toggle_category_publish':
        if (!$is_allowed_to_edit) {
            api_not_allowed(true);
        }

        learnpath::toggleCategoryPublish($_REQUEST['id'], $_REQUEST['new_status']);
        require 'lp_list.php';
        break;
    case 'toggle_publish':
        // Change lp published status (visibility on homepage).
        if (!$is_allowed_to_edit) {
            api_not_allowed(true);
        }
        if (!$lp_found) {
            error_log('New LP - No learnpath given for publish', 0);
            require 'lp_list.php';
        } else {
            learnpath::toggle_publish($_REQUEST['lp_id'], $_REQUEST['new_status']);
            require 'lp_list.php';
        }
        break;
    case 'move_lp_up':
        // Change lp published status (visibility on homepage)
        if (!$is_allowed_to_edit) {
            api_not_allowed(true);
        }
        if (!$lp_found) {
            error_log('New LP - No learnpath given for publish', 0);
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
            error_log('New LP - No learnpath given for publish', 0);
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
            error_log('New LP - No learnpath given for edit', 0);
            require 'lp_list.php';
        } else {
            $_SESSION['refresh'] = 1;
            require 'lp_edit.php';
        }
        break;
    case 'update_lp':
        if (!$is_allowed_to_edit) {
            api_not_allowed(true);
        }
        if (!$lp_found) {
            error_log('New LP - No learnpath given for edit', 0);
            require 'lp_list.php';
        } else {
            $_SESSION['refresh'] = 1;
            $lp_name = Security::remove_XSS($_REQUEST['lp_name']);
            $_SESSION['oLP']->set_name($lp_name);
            $author = $_REQUEST['lp_author'];
            // Fixing the author name (no body or html tags).
            $auth_init = stripos($author, '<p>');
            if ($auth_init === false) {
                $auth_init = stripos($author, '<body>');
                $auth_end = $auth_init + stripos(substr($author, $auth_init + 6), '</body>') + 7;
                $len = $auth_end - $auth_init + 6;
            } else {
                $auth_end = strripos($author, '</p>');
                $len = $auth_end - $auth_init + 4;
            }

            $author_fixed = substr($author, $auth_init, $len);
            $_SESSION['oLP']->set_author($author_fixed);
            // TODO (as of Chamilo 1.8.8): Check in the future whether this field is needed.
            $_SESSION['oLP']->set_encoding($_REQUEST['lp_encoding']);

            if (isset($_REQUEST['lp_maker'])) {
                $_SESSION['oLP']->set_maker($_REQUEST['lp_maker']);
            }
            if (isset($_REQUEST['lp_proximity'])) {
                $_SESSION['oLP']->set_proximity($_REQUEST['lp_proximity']);
            }
            $_SESSION['oLP']->set_theme($_REQUEST['lp_theme']);

            if (isset($_REQUEST['hide_toc_frame']) && $_REQUEST['hide_toc_frame'] == 1) {
                $hide_toc_frame = $_REQUEST['hide_toc_frame'];
            } else {
                $hide_toc_frame = null;
            }
            $_SESSION['oLP']->set_hide_toc_frame($hide_toc_frame);
            $_SESSION['oLP']->set_prerequisite($_REQUEST['prerequisites']);
            $_SESSION['oLP']->set_use_max_score($_REQUEST['use_max_score']);

            $subscribeUsers = isset($_REQUEST['subscribe_users']) ? 1 : 0;
            $_SESSION['oLP']->setSubscribeUsers($subscribeUsers);

            $accumulateScormTime = isset($_REQUEST['accumulate_scorm_time']) ? $_REQUEST['accumulate_scorm_time'] : 'true';
            $_SESSION['oLP']->setAccumulateScormTime($accumulateScormTime);

            if (isset($_REQUEST['activate_start_date_check']) && $_REQUEST['activate_start_date_check'] == 1) {
                $publicated_on = $_REQUEST['publicated_on'];
            } else {
                $publicated_on = null;
            }

            if (isset($_REQUEST['activate_end_date_check']) && $_REQUEST['activate_end_date_check'] == 1) {
                $expired_on = $_REQUEST['expired_on'];
            } else {
                $expired_on = null;
            }
            $_SESSION['oLP']->setCategoryId($_REQUEST['category_id']);
            $_SESSION['oLP']->set_modified_on();
            $_SESSION['oLP']->set_publicated_on($publicated_on);
            $_SESSION['oLP']->set_expired_on($expired_on);

            if (isset($_REQUEST['remove_picture']) && $_REQUEST['remove_picture']) {
                $_SESSION['oLP']->delete_lp_image();
            }

            $extraFieldValue = new ExtraFieldValue('lp');
            $params = array(
                'lp_id' => $_SESSION['oLP']->id
            );
            $extraFieldValue->saveFieldValues($_REQUEST);

            if ($_FILES['lp_preview_image']['size'] > 0) {
                $_SESSION['oLP']->upload_image($_FILES['lp_preview_image']);
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
    case 'add_sub_item': // Add an item inside a dir/chapter.
        // @todo check if this is @deprecated
        if (!$is_allowed_to_edit) {
            api_not_allowed(true);
        }
        if (!$lp_found) {
            error_log('New LP - No learnpath given for add sub item', 0);
            require 'lp_list.php';
        } else {
            $_SESSION['refresh'] = 1;
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
            error_log('New LP - No learnpath given for delete item', 0);
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
    case 'edititemprereq':
    case 'edit_item_prereq':
        if (!$is_allowed_to_edit) {
            api_not_allowed(true);
        }
        if (!$lp_found) {
            error_log('New LP - No learnpath given for edit item prereq', 0);
            require 'lp_list.php';
        } else {
            if (!empty($_REQUEST['id']) && !empty($_REQUEST['submit_item'])) {
                $_SESSION['refresh'] = 1;
                $_SESSION['oLP']->edit_item_prereq($_REQUEST['id'], $_REQUEST['prereq']);
            }
            require 'lp_admin_view.php';
        }
        break;
    case 'restart':
        if (!$lp_found) {
            error_log('New LP - No learnpath given for restart', 0);
            require 'lp_list.php';
        } else {
            $_SESSION['oLP']->restart();
            require 'lp_view.php';
        }
        break;
    case 'last':
        if (!$lp_found) {
            error_log('New LP - No learnpath given for last', 0);
            require 'lp_list.php';
        } else {
            $_SESSION['oLP']->last();
            require 'lp_view.php';
        }
        break;
    case 'first':
        if (!$lp_found) {
            error_log('New LP - No learnpath given for first', 0);
            require 'lp_list.php';
        } else {
            $_SESSION['oLP']->first();
            require 'lp_view.php';
        }
        break;
    case 'next':
        if (!$lp_found) {
            error_log('New LP - No learnpath given for next', 0);
            require 'lp_list.php';
        } else {
            $_SESSION['oLP']->next();
            require 'lp_view.php';
        }
        break;
    case 'previous':
        if (!$lp_found) {
            error_log('New LP - No learnpath given for previous', 0);
            require 'lp_list.php';
        } else {
            $_SESSION['oLP']->previous();
            require 'lp_view.php';
        }
        break;
    case 'content':
        if ($debug > 0) error_log('New LP - Item id is '.intval($_GET['item_id']), 0);
        if (!$lp_found) {
            error_log('New LP - No learnpath given for content', 0);
            require 'lp_list.php';
        } else {
            if ($debug > 0) error_log('New LP - save_last()', 0);
            $_SESSION['oLP']->save_last();
            if ($debug > 0) error_log('New LP - set_current_item()', 0);
            $_SESSION['oLP']->set_current_item($_GET['item_id']);
            if ($debug > 0) error_log('New LP - start_current_item()', 0);
            $_SESSION['oLP']->start_current_item();
            require 'lp_content.php';
        }
        break;
    case 'view':
        if (!$lp_found) {
            error_log('New LP - No learnpath given for view', 0);
            require 'lp_list.php';
        } else {
            if ($debug > 0) {error_log('New LP - Trying to set current item to '.$_REQUEST['item_id'], 0); }
            if (!empty($_REQUEST['item_id'])) {
                $_SESSION['oLP']->set_current_item($_REQUEST['item_id']);
            }
            require 'lp_view.php';
        }
        break;
    case 'save':
        if (!$lp_found) {
            error_log('New LP - No learnpath given for save', 0);
            require 'lp_list.php';
        } else {
            $_SESSION['oLP']->save_item();
            require 'lp_save.php';
        }
        break;
    case 'stats':
        if (!$lp_found) {
            error_log('New LP - No learnpath given for stats', 0);
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
            $_SESSION['refresh'] = 1;
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
            error_log('New LP - No learnpath given for switch', 0);
            require 'lp_list.php';
        }
        if (Security::check_token('get')) {
            $_SESSION['refresh'] = 1;
            $_SESSION['oLP']->update_default_view_mode();
        }
        require 'lp_list.php';
        break;
    case 'switch_force_commit':
        if (!$lp_found) {
            error_log('New LP - No learnpath given for switch', 0);
            require 'lp_list.php';
        }
        $_SESSION['refresh'] = 1;
        $_SESSION['oLP']->update_default_scorm_commit();
        require 'lp_list.php';
        break;
    /* Those 2 switches have been replaced by switc_attempt_mode switch
    case 'switch_reinit':
        if (!$lp_found) { error_log('New LP - No learnpath given for switch', 0); require 'lp_list.php'; }
        $_SESSION['refresh'] = 1;
        $_SESSION['oLP']->update_reinit();
		require 'lp_list.php';
		break;
	case 'switch_seriousgame_mode':
		if(!$lp_found){ error_log('New LP - No learnpath given for switch',0); require 'lp_list.php'; }
		$_SESSION['refresh'] = 1;
		$_SESSION['oLP']->set_seriousgame_mode();
		require 'lp_list.php';
		break;
     */
	case 'switch_attempt_mode':
		if (!$lp_found) { error_log('New LP - No learnpath given for switch', 0); require 'lp_list.php'; }
		$_SESSION['refresh'] = 1;
		$_SESSION['oLP']->switch_attempt_mode();
        require 'lp_list.php';
        break;
    case 'switch_scorm_debug':
        if (!$lp_found) { error_log('New LP - No learnpath given for switch', 0); require 'lp_list.php'; }
        $_SESSION['refresh'] = 1;
        $_SESSION['oLP']->update_scorm_debug();
        require 'lp_list.php';
        break;
    case 'intro_cmdAdd':
        // Add introduction section page.
        break;
    case 'js_api_refresh':
        if (!$lp_found) { error_log('New LP - No learnpath given for js_api_refresh', 0); require 'lp_message.php'; }
        if (isset($_REQUEST['item_id'])) {
            $htmlHeadXtra[] = $_SESSION['oLP']->get_js_info($_REQUEST['item_id']);
        }
        require 'lp_message.php';
        break;
    case 'return_to_course_homepage':
        if (!$lp_found) { error_log('New LP - No learnpath given for stats', 0); require 'lp_list.php'; }
        else {
            $_SESSION['oLP']->save_current();
            $_SESSION['oLP']->save_last();
            $url = api_get_path(WEB_COURSE_PATH).api_get_course_path().'/index.php?id_session='.api_get_session_id();
            if (isset($_GET['redirectTo']) && $_GET['redirectTo'] == 'lp_list') {
                $url = 'lp_controller.php?'.api_get_cidreq();
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
            error_log('New LP - No learnpath given for view', 0);
            require 'lp_list.php';
        } else {
            if ($debug > 0) {error_log('New LP - Trying to impress this LP item to '.$_REQUEST['item_id'], 0); }
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
        break;
    case 'clear_prerequisites':
        $_SESSION['oLP']->clear_prerequisites();
        $url = api_get_self().'?action=add_item&type=step&lp_id='.intval($_SESSION['oLP']->lp_id)."&".api_get_cidreq();
        Display::addFlash(Display::return_message(get_lang('ItemUpdated')));
        header('Location: '.$url);
        break;
    case 'toggle_seriousgame': //activate/deactive seriousgame_mode
        if (!$is_allowed_to_edit) {
            api_not_allowed(true);
        }

        if (!$lp_found) {
            error_log('New LP - No learnpath given for visibility');

            require 'lp_list.php';
        }

        $_SESSION['refresh'] = 1;
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
                            'forum_category_comment' => null
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
            'lp_id' => $_SESSION['oLP']->lp_id
        ]));

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
            'lp_id' => $_SESSION['oLP']->lp_id
        ]));
        break;
    case 'add_final_item':
        if (!$lp_found) {
            Display::addFlash(
                Display::return_message(get_lang('NoLPFound'), 'error')
            );
            break;
        }

        $_SESSION['refresh'] = 1;
        if (!isset($_POST['submit']) || empty($post_title)) {
            break;
        }

        $_SESSION['oLP']->getFinalItemForm();
        $redirectTo = api_get_self().'?'.api_get_cidreq().'&'.http_build_query([
            'action' => 'add_item',
            'type' => 'step',
            'lp_id' => intval($_SESSION['oLP']->lp_id)
        ]);
        break;
    default:
        require 'lp_list.php';
        break;
}

if (!empty($_SESSION['oLP'])) {
    $_SESSION['lpobject'] = serialize($_SESSION['oLP']);
    if ($debug > 0) error_log('New LP - lpobject is serialized in session', 0);
}

if (!empty($redirectTo)) {
    header("Location: $redirectTo");
    exit;
}
