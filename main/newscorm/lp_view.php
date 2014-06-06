<?php
/* For licensing terms, see /license.txt */

/**
* This file was originally the copy of document.php, but many modifications happened since then ;
* the direct file view is not needed anymore, if the user uploads a scorm zip file, a directory
* will be automatically created for it, and the files will be uncompressed there for example ;
*
* @package chamilo.learnpath
* @author Yannick Warnier <ywarnier@beeznest.org> - redesign
* @author Denes Nagy, principal author
* @author Isthvan Mandak, several new features
* @author Roan Embrechts, code improvements and refactoring
*/
/**
 * Code
 */

use \ChamiloSession as Session;

$use_anonymous = true;

$_SESSION['whereami'] = 'lp/view';
$this_section = SECTION_COURSES;

if ($lp_controller_touched != 1) {
    header('location: lp_controller.php?action=view&item_id='.intval($_REQUEST['item_id']));
    exit;
}

/* Libraries */
require_once 'back_compat.inc.php';
require_once 'learnpath.class.php';
require_once 'learnpathItem.class.php';

//To prevent the template class
$show_learnpath = true;

api_protect_course_script();

$lp_id = intval($_GET['lp_id']);

// Check if the learning path is visible for student - (LP requisites)
if (!api_is_allowed_to_edit(null, true) && !learnpath::is_lp_visible_for_student($lp_id, api_get_user_id())) {
    api_not_allowed(true);
}

//Checking visibility (eye icon)
$visibility = api_get_item_visibility(api_get_course_info(), TOOL_LEARNPATH, $lp_id, $action, api_get_user_id(), api_get_session_id());
if (!api_is_allowed_to_edit(false, true, false, false) && intval($visibility) == 0) {
    api_not_allowed(true);
}

if (empty($_SESSION['oLP'])) {
    api_not_allowed(true);
}

$debug = 0;

if ($debug) {
    error_log('------ Entering lp_view.php -------');
}

$_SESSION['oLP']->error = '';
$lp_item_id = $_SESSION['oLP']->get_current_item_id();
$lp_type    = $_SESSION['oLP']->get_type();

$course_code    = api_get_course_id();
$course_id      = api_get_course_int_id();
$user_id        = api_get_user_id();
$platform_theme = api_get_setting('stylesheets'); // Plataform's css.
$my_style       = $platform_theme;

$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jquery.lp_minipanel.js" type="text/javascript" language="javascript"></script>';
$htmlHeadXtra[] = '<script>
$(document).ready(function() {
	$("div#log_content_cleaner").bind("click", function() {
    	$("div#log_content").empty();
	});
	//jQuery("video:not(.skip), audio:not(.skip)").mediaelementplayer();
});
var chamilo_xajax_handler = window.oxajax;
</script>';

if ($_SESSION['oLP']->mode == 'embedframe' || $_SESSION['oLP']->get_hide_toc_frame()==1 ) {
    $htmlHeadXtra[] = '<script>
    $(document).ready(function() {
        toogle_minipanel();
    });
    </script>';
}

//Impress js
if ($_SESSION['oLP']->mode == 'impress') {
    $lp_id = $_SESSION['oLP']->get_id();
    $url = api_get_path(WEB_CODE_PATH)."newscorm/lp_impress.php?lp_id=$lp_id&".api_get_cidreq();
    header("Location: $url");
    exit;
}

// Prepare variables for the test tool (just in case) - honestly, this should disappear later on.
$_SESSION['scorm_view_id'] = $_SESSION['oLP']->get_view_id();
$_SESSION['scorm_item_id'] = $lp_item_id;

// Reinit exercises variables to avoid spacename clashes (see exercise tool)
if (isset($exerciseResult) || isset($_SESSION['exerciseResult'])) {
    Session::erase('exerciseResult');
    Session::erase('objExercise');
    Session::erase('questionList');
}

// additional APIs
$htmlHeadXtra[] = '<script>
chamilo_courseCode = "'.$course_code.'";
</script>';
// Document API
$htmlHeadXtra[] = '<script src="js/documentapi.js" type="text/javascript" language="javascript"></script>';
// Storage API
$htmlHeadXtra[] = '<script>
var sv_user = \''.api_get_user_id().'\';
var sv_course = chamilo_courseCode;
var sv_sco = \''.intval($_REQUEST['lp_id']).'\';
</script>'; // FIXME fetch sco and userid from a more reliable source directly in sotrageapi.js
$htmlHeadXtra[] = '<script type="text/javascript" src="js/storageapi.js"></script>';

/**
 * Get a link to the corresponding document.
 */

if ($debug) {
    error_log(" src: $src ");
    error_log(" lp_type: $lp_type ");
}

$get_toc_list = $_SESSION['oLP']->get_toc();
$type_quiz = false;

foreach ($get_toc_list as $toc) {
    if ($toc['id'] == $lp_item_id && ($toc['type']=='quiz')) {
        $type_quiz = true;
    }
}

if (!isset($src)) {
    $src = null;
    switch ($lp_type) {
        case 1:
            $_SESSION['oLP']->stop_previous_item();
            $htmlHeadXtra[] = '<script src="scorm_api.php" type="text/javascript" language="javascript"></script>';
            $prereq_check = $_SESSION['oLP']->prerequisites_match($lp_item_id);
            if ($prereq_check === true) {
                $src = $_SESSION['oLP']->get_link('http', $lp_item_id, $get_toc_list);

                // Prevents FF 3.6 + Adobe Reader 9 bug see BT#794 when calling a pdf file in a LP.
                $file_info = parse_url($src);
                $file_info = pathinfo($file_info['path']);
                if (isset($file_info['extension']) &&
                    api_strtolower(substr($file_info['extension'], 0, 3) == 'pdf')
                ) {
                    $src = api_get_path(WEB_CODE_PATH).'newscorm/lp_view_item.php?lp_item_id='.$lp_item_id.'&'.api_get_cidreq();
                }
                $_SESSION['oLP']->start_current_item(); // starts time counter manually if asset
            } else {
                $src = 'blank.php?error=prerequisites';
            }
            break;
        case 2:
            // save old if asset
            $_SESSION['oLP']->stop_previous_item(); // save status manually if asset
            $htmlHeadXtra[] = '<script src="scorm_api.php" type="text/javascript" language="javascript"></script>';
            $prereq_check = $_SESSION['oLP']->prerequisites_match($lp_item_id);
            if ($prereq_check === true) {
                $src = $_SESSION['oLP']->get_link('http', $lp_item_id, $get_toc_list);
                $_SESSION['oLP']->start_current_item(); // starts time counter manually if asset
            } else {
                $src = 'blank.php?error=prerequisites';
            }
            break;
        case 3:
            // aicc
            $_SESSION['oLP']->stop_previous_item(); // save status manually if asset
            $htmlHeadXtra[] = '<script src="'.$_SESSION['oLP']->get_js_lib().'" type="text/javascript" language="javascript"></script>';
            $prereq_check = $_SESSION['oLP']->prerequisites_match($lp_item_id);
            if ($prereq_check === true) {
                $src = $_SESSION['oLP']->get_link('http', $lp_item_id, $get_toc_list);
                $_SESSION['oLP']->start_current_item(); // starts time counter manually if asset
            } else {
                $src = 'blank.php';
            }
            break;
        case 4:
            break;
    }
}

$autostart = 'true';
// Update status, total_time from lp_item_view table when you finish the exercises in learning path.

if ($debug) {
    error_log('$type_quiz: '.$type_quiz);
    error_log('$_REQUEST[exeId]: '.intval($_REQUEST['exeId']));
    error_log('$lp_id: '.$lp_id);
    error_log('$_GET[lp_item_id]: '.intval($_GET['lp_item_id']));
}

if ($type_quiz && !empty($_REQUEST['exeId']) && isset($lp_id) && isset($_GET['lp_item_id'])) {
    global $src;

    $_SESSION['oLP']->items[$_SESSION['oLP']->current]->write_to_db();

    $TBL_TRACK_EXERCICES    = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_EXERCICES);
    $TBL_LP_ITEM_VIEW       = Database::get_course_table(TABLE_LP_ITEM_VIEW);
    $TBL_LP_ITEM            = Database::get_course_table(TABLE_LP_ITEM);
    $safe_item_id           = intval($_GET['lp_item_id']);
    $safe_id                = $lp_id;
    $safe_exe_id            = intval($_REQUEST['exeId']);

    if ($safe_id == strval(intval($safe_id)) && $safe_item_id == strval(intval($safe_item_id))) {

        $sql = 'SELECT start_date, exe_date, exe_result, exe_weighting
                FROM ' . $TBL_TRACK_EXERCICES . '
                WHERE exe_id = '.$safe_exe_id;
        $res = Database::query($sql);
        $row_dates = Database::fetch_array($res);

        $time_start_date = api_strtotime($row_dates['start_date'],'UTC');
        $time_exe_date   = api_strtotime($row_dates['exe_date'],'UTC');

        $mytime 	= ((int)$time_exe_date-(int)$time_start_date);
        $score 		= (float)$row_dates['exe_result'];
        $max_score 	= (float)$row_dates['exe_weighting'];

        $sql = "UPDATE $TBL_LP_ITEM SET max_score = '$max_score'
                WHERE c_id = $course_id AND id = '".$safe_item_id."'";
        Database::query($sql);

        $sql = "SELECT id FROM $TBL_LP_ITEM_VIEW
                WHERE
                    c_id = $course_id AND
                    lp_item_id = '$safe_item_id' AND
                    lp_view_id = '".$_SESSION['oLP']->lp_view_id."'
                ORDER BY id DESC
                LIMIT 1";
        $res_last_attempt = Database::query($sql);

        if (Database::num_rows($res_last_attempt)) {
        	$row_last_attempt = Database::fetch_row($res_last_attempt);
        	$lp_item_view_id  = $row_last_attempt[0];
            $sql_upd_score = "UPDATE $TBL_LP_ITEM_VIEW SET status = 'completed' , score = $score, total_time = $mytime
                              WHERE id='".$lp_item_view_id."' AND c_id = $course_id ";

            if ($debug) error_log($sql_upd_score);
            Database::query($sql_upd_score);

            $update_query = "UPDATE $TBL_TRACK_EXERCICES SET orig_lp_item_view_id = $lp_item_view_id  WHERE exe_id = ".$safe_exe_id;
            Database::query($update_query);
        }
    }
    if (intval($_GET['fb_type']) > 0) {
        $src = 'blank.php?msg=exerciseFinished';
    } else {
        $src = api_get_path(WEB_CODE_PATH).'exercice/result.php?origin=learnpath&id='.$safe_exe_id;

        if ($debug) error_log('Calling URL: '.$src);
    }
    $autostart = 'false';
}

$_SESSION['oLP']->set_previous_item($lp_item_id);
$nameTools = Security::remove_XSS($_SESSION['oLP']->get_name());

$save_setting = api_get_setting('show_navigation_menu');
global $_setting;
$_setting['show_navigation_menu'] = 'false';
$scorm_css_header = true;
$lp_theme_css = $_SESSION['oLP']->get_theme(); // Sets the css theme of the LP this call is also use at the frames (toc, nav, message).

if ($_SESSION['oLP']->mode == 'fullscreen') {
    $htmlHeadXtra[] = "<script>window.open('$src','content_id','toolbar=0,location=0,status=0,scrollbars=1,resizable=1');</script>";
}

// Not in fullscreen mode.
Display::display_reduced_header($nameTools);

// Check if audio recorder needs to be in studentview.
if (isset($_SESSION['status']) && $_SESSION['status'][$course_code] == 5) {
	$audio_recorder_studentview = true;
} else {
	$audio_recorder_studentview = false;
}

// Set flag to ensure lp_header.php is loaded by this script (flag is unset in lp_header.php).
$_SESSION['loaded_lp_view'] = true;

$display_none = '';
$margin_left = '340px';

//Media player code

$display_mode = $_SESSION['oLP']->mode;
$scorm_css_header = true;
$lp_theme_css = $_SESSION['oLP']->get_theme();

// Setting up the CSS theme if exists.
if (!empty ($lp_theme_css) && !empty ($mycourselptheme) && $mycourselptheme != -1 && $mycourselptheme == 1) {
    global $lp_theme_css;
} else {
    $lp_theme_css = $my_style;
}

$progress_bar   = $_SESSION['oLP']->get_progress_bar('', -1, '', true);
$navigation_bar = $_SESSION['oLP']->get_navigation_bar();
$mediaplayer    = $_SESSION['oLP']->get_mediaplayer($autostart);

$tbl_lp_item    = Database::get_course_table(TABLE_LP_ITEM);
$show_audioplayer = false;
// Getting all the information about the item.
$sql = "SELECT audio FROM " . $tbl_lp_item . " WHERE c_id = $course_id AND lp_id = '" . $_SESSION['oLP']->lp_id."'";
$res_media= Database::query($sql);

if (Database::num_rows($res_media) > 0) {
    while ($row_media= Database::fetch_array($res_media)) {
        if (!empty($row_media['audio'])) {
            $show_audioplayer = true;
            break;
        }
    }
}

echo '<div id="learning_path_main" style="width:100%;height:100%;">';
$is_allowed_to_edit = api_is_allowed_to_edit(null, true, false, false);
if ($is_allowed_to_edit) {
    echo '<div id="learning_path_breadcrumb_zone">';
    global $interbreadcrumb;
    $interbreadcrumb[] = array('url' => 'lp_controller.php?action=list&isStudentView=false', 'name' => get_lang('LearningPaths'));
    $interbreadcrumb[] = array('url' => api_get_self()."?action=add_item&type=step&lp_id=".$_SESSION['oLP']->lp_id."&isStudentView=false", 'name' => $_SESSION['oLP']->get_name());
    $interbreadcrumb[] = array('url' => '#', 'name' => get_lang('Preview'));
    echo return_breadcrumb($interbreadcrumb, null, null);
    echo '</div>';
}
    echo '<div id="learning_path_left_zone" style="'.$display_none.'"> ';
    echo '<div id="header">
            <table>
                <tr>
                    <td>';
                        echo '<a href="lp_controller.php?action=return_to_course_homepage&'.api_get_cidreq().'" target="_self" onclick="javascript: window.parent.API.save_asset();">
                            <img src="../img/btn_home.png" />
                        </a>
                    </td>
                    <td>';
                         if ($is_allowed_to_edit) {
                            echo '<a class="link no-border" href="lp_controller.php?isStudentView=false&action=return_to_course_homepage&'.api_get_cidreq().'" target="_self" onclick="javascript: window.parent.API.save_asset();">';
                         } else {
                            echo '<a class="link no-border" href="lp_controller.php?action=return_to_course_homepage&'.api_get_cidreq().'" target="_self" onclick="javascript: window.parent.API.save_asset();">';
                         }
                        echo get_lang('CourseHomepageLink').'
                        </a>
                    </td>
                </tr>
            </table>
        </div>';
?>
        <!-- end header -->

        <!-- Author image preview -->
        <div id="author_image">
            <div id="author_icon">
                <?php
                if ($_SESSION['oLP']->get_preview_image() != '') {
                    $picture = getimagesize(api_get_path(SYS_COURSE_PATH).api_get_course_path().'/upload/learning_path/images/'.$_SESSION['oLP']->get_preview_image());
                    $style = null;
                    if ($picture['1'] < 96) {
                        $style = ' style="padding-top:'.((94 -$picture['1'])/2).'px;" ';
                    }
                    $size = ($picture['0'] > 104 && $picture['1'] > 96 )? ' width="104" height="96" ': $style;
                    $my_path = $_SESSION['oLP']->get_preview_image_path();
                    echo '<img src="'.$my_path.'">';
                } else {
                    echo Display :: display_icon('unknown_250_100.jpg');
                }
                ?>
            </div>
            <div id="lp_navigation_elem">
                <?php echo $navigation_bar; ?>
                <div id="progress_bar">
                    <?php echo $progress_bar; ?>
                </div>
            </div>
        </div>
        <!-- end image preview Layout -->

        <div id="author_name">
            <?php echo $_SESSION['oLP']->get_author(); ?>
        </div>

        <!-- media player layout -->
        <?php
        if ($show_audioplayer) {
            echo '<div id="lp_media_file">';
            echo $mediaplayer;
            echo '</div>';
        }
        ?>
        <!-- end media player layout -->

        <!-- TOC layout -->
        <div id="toc_id" name="toc_name" style="overflow: auto; padding:0;margin-top:0px;width:100%;float:left">
            <div id="learning_path_toc">
                <?php echo $_SESSION['oLP']->get_html_toc($get_toc_list); ?>
            </div>
        </div>
        <!-- end TOC layout -->
    </div>
    <!-- end left zone -->

    <!-- right zone -->
    <div id="learning_path_right_zone" style="margin-left:<?php echo $margin_left;?>;height:100%">
    <?php
        // hub 26-05-2010 Fullscreen or not fullscreen
        $height = '100%';
        if ($_SESSION['oLP']->mode == 'fullscreen') {
            echo '<iframe id="content_id_blank" name="content_name_blank" src="blank.php" border="0" frameborder="0" style="width:100%;height:'.$height.'" ></iframe>';
        } else {
            echo '<iframe id="content_id" name="content_name" src="'.$src.'" border="0" frameborder="0" style="display: block; width:100%;height:'.$height.'"></iframe>';
        }
    ?>
    </div>
    <!-- end right Zone -->
</div>

<script>
    // Resize right and left pane to full height (HUB 20-05-2010).
    function updateContentHeight() {
        document.body.style.overflow = 'hidden';
        var IE = window.navigator.appName.match(/microsoft/i);
        var heightHeader = ($('#header').height())? $('#header').height() : 0 ;
        var heightAuthorImg = ($('#author_image').height())? $('#author_image').height() : 0 ;
        var heightAuthorName = ($('#author_name').height())? $('#author_name').height() : 0 ;
        var heightBreadcrumb = ($('#learning_path_breadcrumb_zone').height())? $('#learning_path_breadcrumb_zone').height() : 0 ;
        var heightControl = ($('#control').is(':visible'))? $('#control').height() : 0 ;
        var heightMedia = ($('#lp_media_file').length != 0)? $('#lp_media_file').height() : 0 ;
        var heightTitle = ($('#scorm_title').height())? $('#scorm_title').height() : 0 ;
        var heightAction = ($('#actions_lp').height())? $('#actions_lp').height() : 0 ;

        var heightTop = heightHeader + heightAuthorImg + heightAuthorName + heightMedia + heightTitle + heightAction + 100;
        heightTop = (heightTop < 230)? heightTop : 230;
        var innerHeight = (IE) ? document.body.clientHeight : window.innerHeight ;
        // -40 is a static adjustement for margin, spaces on the page

        $('#inner_lp_toc').css('height', innerHeight - heightTop - heightBreadcrumb - heightControl + "px");
        if ($('#content_id')) {
            $('#content_id').css('height', innerHeight - heightBreadcrumb - heightControl + "px");
        }
        if ($('#hide_bar')) {
            $('#hide_bar').css('height', innerHeight - heightBreadcrumb - heightControl + "px");
        }

    // Loads the glossary library.
    <?php
      if (api_get_setting('show_glossary_in_extra_tools') == 'true') {
           if (api_get_setting('show_glossary_in_documents') == 'ismanual') {
                ?>
            $.frameReady(function(){
                   //  $("<div>I am a div courses</div>").prependTo("body");
         }, "top.content_name",
          { load: [
                  {type:"script", id:"_fr1", src:"<?php echo api_get_path(WEB_LIBRARY_PATH); ?>javascript/jquery.min.js"},
                  {type:"script", id:"_fr4", src:"<?php echo api_get_path(WEB_LIBRARY_PATH); ?>javascript/jquery-ui/smoothness/jquery-ui-1.8.21.custom.min.js"},
                  {type:"stylesheet", id:"_fr5", src:"<?php echo api_get_path(WEB_LIBRARY_PATH); ?>javascript/jquery-ui/smoothness/jquery-ui-1.8.21.custom.css"},
                  {type:"script", id:"_fr2", src:"<?php echo api_get_path(WEB_LIBRARY_PATH); ?>javascript/jquery.highlight.js"}

          ] }
          );
    <?php
        } elseif (api_get_setting('show_glossary_in_documents') == 'isautomatic') {
      ?>
    $.frameReady(function(){
        //  $("<div>I am a div courses</div>").prependTo("body");
      },
        "top.content_name",
      {
      load: [
          {type:"script", id:"_fr1", src:"<?php echo api_get_path(WEB_LIBRARY_PATH); ?>javascript/jquery.min.js"},
          {type:"script", id:"_fr4", src:"<?php echo api_get_path(WEB_LIBRARY_PATH); ?>javascript/jquery-ui/smoothness/jquery-ui-1.8.21.custom.min.js"},
          {type:"stylesheet", id:"_fr5", src:"<?php echo api_get_path(WEB_LIBRARY_PATH); ?>javascript/jquery-ui/smoothness/jquery-ui-1.8.21.custom.css"},
          {type:"script", id:"_fr2", src:"<?php echo api_get_path(WEB_LIBRARY_PATH); ?>javascript/jquery.highlight.js"}
      ]}
      );
  <?php
       }
  }
  ?>}
    $(document).ready(function() {
        updateContentHeight();
        $('#hide_bar').children().click(function(){
            updateContentHeight();
        });
        $(window).resize(function() {
            updateContentHeight();
        });
    });
    window.onload = updateContentHeight();
    window.onresize = updateContentHeight();
</script>
<?php
// Restore a global setting.
$_setting['show_navigation_menu'] = $save_setting;

if ($debug) {
    error_log(' ------- end lp_view.php ------');
}
