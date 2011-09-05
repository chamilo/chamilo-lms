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
* @license	GNU/GPL - See Chamilo license directory for details
*/

/* INIT SECTION */

$_SESSION['whereami'] = 'lp/view';
$this_section = SECTION_COURSES;

if ($lp_controller_touched != 1){
    header('location: lp_controller.php?action=view&item_id='.$_REQUEST['item_id']);
    exit;
}

/* Libraries */

require_once 'back_compat.inc.php';
//require_once '../learnpath/learnpath_functions.inc.php';
require_once 'scorm.lib.php';
require_once 'learnpath.class.php';
require_once 'learnpathItem.class.php';
//require_once 'lp_comm.common.php'; //xajax functions

if (!$is_allowed_in_course) api_not_allowed();
$oLearnpath     = false;
$course_code    = api_get_course_id();
$user_id        = api_get_user_id();
$platform_theme = api_get_setting('stylesheets'); // Plataform's css.
$my_style       = $platform_theme;
// Escape external variables.

/*  Header  */

// Se incluye la libreria en el lp_controller.php

$htmlHeadXtra[] = api_get_jquery_ui_js(); //jQuery-UI

// se incluye la librería para el mini panel
$htmlHeadXtra[] = '<script  src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jquery.lp_minipanel.js" type="text/javascript" language="javascript"></script>';

if (api_get_setting('show_glossary_in_documents') == 'ismanual' || api_get_setting('show_glossary_in_documents') == 'isautomatic' ) {
    $htmlHeadXtra[] = '<script type="text/javascript">
<!--
    var jQueryFrameReadyConfigPath = \''.api_get_path(WEB_LIBRARY_PATH).'javascript/jquery.js\';
-->
</script>';
    $htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jquery.frameready.js" type="text/javascript" language="javascript"></script>';
    $htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jquery.highlight.js" type="text/javascript" language="javascript"></script>';

}
$htmlHeadXtra[] = '<script language="javascript" type="text/javascript">
$(document).ready(function (){
    $("div#log_content_cleaner").bind("click", function(){
      $("div#log_content").empty();
    });
});
</script>';

$htmlHeadXtra[] = '<script language="JavaScript" type="text/javascript">
      var dokeos_xajax_handler = window.oxajax;
</script>';

$_SESSION['oLP']->error = '';

$now = time();

if (!api_is_allowed_to_edit(null, true)) {
    //Adding visibility reestrinctions
    if (!empty($_SESSION['oLP']->publicated_on) && $_SESSION['oLP']->publicated_on != '0000-00-00 00:00:00') {
        if ($now < api_strtotime($_SESSION['oLP']->publicated_on, 'UTC')) {
            api_not_allowed();
        }    
    }
    
    if (!empty($_SESSION['oLP']->expired_on) && $_SESSION['oLP']->expired_on != '0000-00-00 00:00:00') {
        if ($now > api_strtotime($_SESSION['oLP']->expired_on, 'UTC')) {            
            api_not_allowed();
        }    
    }
}
$lp_item_id = $_SESSION['oLP']->get_current_item_id();
$lp_type    = $_SESSION['oLP']->get_type();
$lp_id      = intval($_GET['lp_id']);

// Check if the learning path is visible for student - (LP requisites) 
if (!api_is_allowed_to_edit(null, true) && !learnpath::is_lp_visible_for_student($lp_id, api_get_user_id())) {
    api_not_allowed();
}     

//Checking visibility (eye icon)
$visibility = api_get_item_visibility(api_get_course_info(), TOOL_LEARNPATH, $lp_id, $action, api_get_user_id(), api_get_session_id());
if (!api_is_allowed_to_edit(null, true) && intval($visibility) == 0 ) {
     api_not_allowed();
}

//$lp_item_id = learnpath::escape_string($_GET['item_id']);
//$_SESSION['oLP']->set_current_item($lp_item_id); // Already done by lp_controller.php.

// Prepare variables for the test tool (just in case) - honestly, this should disappear later on.
$_SESSION['scorm_view_id'] = $_SESSION['oLP']->get_view_id();
$_SESSION['scorm_item_id'] = $lp_item_id;
$_SESSION['lp_mode'] = $_SESSION['oLP']->mode;

// Reinit exercises variables to avoid spacename clashes (see exercise tool)
if (isset($exerciseResult) || isset($_SESSION['exerciseResult'])) {
    api_session_unregister($exerciseResult);
}
unset($_SESSION['objExercise']);
unset($_SESSION['questionList']);

/**
 * Get a link to the corresponding document.
 */
if (!isset($src)) {
     $src = '';
    switch($lp_type) {
        case 1:
            $_SESSION['oLP']->stop_previous_item();
            $htmlHeadXtra[] = '<script src="scorm_api.php" type="text/javascript" language="javascript"></script>';
            $prereq_check = $_SESSION['oLP']->prerequisites_match($lp_item_id);
            if ($prereq_check === true) {
                $src = $_SESSION['oLP']->get_link('http', $lp_item_id);
                //Prevents FF 3.6 + Adobe Reader 9 bug see BT#794 when calling a pdf file in a LP.
                $file_info = pathinfo($src);
                if (api_strtolower(substr($file_info['extension'], 0, 3) == 'pdf')) {
                    $src = 'lp_view_item.php?src='.$src;
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
                $src = $_SESSION['oLP']->get_link('http',$lp_item_id);
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
                $src = $_SESSION['oLP']->get_link('http',$lp_item_id);
                $_SESSION['oLP']->start_current_item(); // starts time counter manually if asset
            } else {
                $src = 'blank.php';
            }
            break;
        case 4:
            break;
    }
}

$list = $_SESSION['oLP']->get_toc();
$type_quiz = false;
/*
$current_item = $_SESSION['oLP']->items[$_SESSION['oLP']->get_current_item_id()];
$attempt_id =  $current_item->get_attempt_id();
error_log('get attempts'.$current_item->get_attempt_id());
*/


foreach($list as $toc) {
    if ($toc['id'] == $lp_item_id && ($toc['type']=='quiz')) {
        $type_quiz = true;
    }
}

$autostart = 'true';
// Update status, total_time from lp_item_view table when you finish the exercises in learning path.
if ($type_quiz && !empty($_REQUEST['exeId']) && isset($lp_id) && isset($_GET['lp_item_id'])) {
    global $src;
    $_SESSION['oLP']->items[$_SESSION['oLP']->current]->write_to_db();
    $TBL_TRACK_EXERCICES    = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_EXERCICES);
    $TBL_LP_ITEM_VIEW       = Database::get_course_table(TABLE_LP_ITEM_VIEW);
    $TBL_LP_VIEW            = Database::get_course_table(TABLE_LP_VIEW);
    $TBL_LP_ITEM            = Database::get_course_table(TABLE_LP_ITEM);
    $safe_item_id           = Database::escape_string($_GET['lp_item_id']);
    $safe_id                = $lp_id;
    $safe_exe_id            = intval($_REQUEST['exeId']);

    if ($safe_id == strval(intval($safe_id)) && $safe_item_id == strval(intval($safe_item_id))) {

        $sql = 'SELECT start_date,exe_date,exe_result,exe_weighting FROM ' . $TBL_TRACK_EXERCICES . ' WHERE exe_id = '.$safe_exe_id;
        $res = Database::query($sql);
        $row_dates = Database::fetch_array($res);

        $time_start_date = api_strtotime($row_dates['start_date'],'UTC');
        $time_exe_date   = api_strtotime($row_dates['exe_date'],'UTC');

        $mytime = ((int)$time_exe_date-(int)$time_start_date);
        $score = (float)$row_dates['exe_result'];
        $max_score = (float)$row_dates['exe_weighting'];

        /*$sql_upd_status = "UPDATE $TBL_LP_ITEM_VIEW SET status = 'completed' WHERE lp_item_id = '".(int)$safe_item_id."'
                 AND lp_view_id = (SELECT lp_view.id FROM $TBL_LP_VIEW lp_view WHERE user_id = '".(int)$_SESSION['oLP']->user_id."' AND lp_id='".(int)$safe_id."')";
        Database::query($sql_upd_status);*/

        $sql_upd_max_score = "UPDATE $TBL_LP_ITEM SET max_score = '$max_score' WHERE id = '".(int)$safe_item_id."'";
        Database::query($sql_upd_max_score);

        $sql_last_attempt = "SELECT id FROM $TBL_LP_ITEM_VIEW  WHERE lp_item_id = '$safe_item_id' AND lp_view_id = '".$_SESSION['oLP']->lp_view_id."' order by id desc limit 1";
        $res_last_attempt = Database::query($sql_last_attempt);
        $row_last_attempt = Database::fetch_row($res_last_attempt);
        $lp_item_view_id = $row_last_attempt[0];

        if (Database::num_rows($res_last_attempt) > 0) {
            $sql_upd_score = "UPDATE $TBL_LP_ITEM_VIEW SET status = 'completed' , score = $score,total_time = $mytime WHERE id='".$lp_item_view_id."'";
            Database::query($sql_upd_score);

            $update_query = "UPDATE $TBL_TRACK_EXERCICES SET  orig_lp_item_view_id = $lp_item_view_id  WHERE exe_id = ".$safe_exe_id;
            Database::query($update_query);
        }
    }

    if (intval($_GET['fb_type']) > 0) {
        $src = 'blank.php?msg=exerciseFinished';
    } else {
        $src = api_get_path(WEB_CODE_PATH).'exercice/exercise_show.php?id='.Security::remove_XSS($_REQUEST['exeId']).'&origin=learnpath&learnpath_id='.$lp_id.'&learnpath_item_id='.$lp_id.'&fb_type='.Security::remove_XSS($_GET['fb_type']);
    }
    $autostart = 'false';
}

$_SESSION['oLP']->set_previous_item($lp_item_id);
$nameTools = Security :: remove_XSS($_SESSION['oLP']->get_name());

$save_setting = api_get_setting('show_navigation_menu');
global $_setting;
$_setting['show_navigation_menu'] = 'false';
$scorm_css_header = true;
$lp_theme_css = $_SESSION['oLP']->get_theme(); // Sets the css theme of the LP this call is also use at the frames (toc, nav, message).

if ($_SESSION['oLP']->mode == 'fullscreen') {
    $htmlHeadXtra[] = "<script>window.open('$src','content_id','toolbar=0,location=0,status=0,scrollbars=1,resizable=1');</script>";
}

    // Not in fullscreen mode.
    require_once '../inc/reduced_header.inc.php';
    //$displayAudioRecorder = (api_get_setting('service_visio', 'active') == 'true') ? true : false;
    // Check if audio recorder needs to be in studentview.
    $course_id = $_SESSION['_course']['id'];
    if ($_SESSION['status'][$course_id] == 5) {
        $audio_recorder_studentview = true;
    } else {
        $audio_recorder_studentview = false;
    }
    // Set flag to ensure lp_header.php is loaded by this script (flag is unset in lp_header.php).
    $_SESSION['loaded_lp_view'] = true;

$display_none = '';
$margin_left = '290px';
if ($_SESSION['oLP']->mode == 'embedframe' ||$_SESSION['oLP']->get_hide_toc_frame()==1 ) {
    $display_none = ';display:none;';
    $margin_left = '12px';
}
?>
<body dir="<?php echo api_get_text_direction(); ?>">
    <div id="learning_path_left_zone" style="float:left;width:280px;height:100%<?php echo $display_none;?>">
        <!-- header -->
        <div id="header">
            <div id="learning_path_header" style="font-size:14px;">
                <table >
                    <tr>
                        <td >
                            <a href="lp_controller.php?action=return_to_course_homepage&<?php echo api_get_cidreq(); ?>" target="_self" onclick="javascript: window.parent.API.save_asset();"><img src="../img/lp_arrow.gif" /></a>
                        </td>
                        <td >
                            <a class="link" href="lp_controller.php?action=return_to_course_homepage&<?php echo api_get_cidreq(); ?>" target="_self" onclick="javascript: window.parent.API.save_asset();">
                            <?php echo get_lang('CourseHomepageLink'); ?></a>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        <!-- end header -->

        <!-- Image preview Layout -->
        <!-- hub 26-50-2010 for lp toc height
        <div id="author_image" name="author_image" class="lp_author_image" style="height:23%; width:100%;margin-left:5px;">
        -->
        <div id="author_image" name="author_image" class="lp_author_image" style="width:100%;margin-left:5px;">
            <?php $image = '../img/lp_author_background.gif'; ?>
            <div id="preview_image" style="padding:5px;background-image: url('../img/lp_author_background.gif');background-repeat:no-repeat;height:110px">
                   <div style="width:100; float:left;height:105;margin:5px">
                       <span style="width:104px; height:96px; float:left; vertical-align:bottom;">
                    <center>
                    <?php
                    if ($_SESSION['oLP']->get_preview_image()!='') {
                        $picture = getimagesize(api_get_path(SYS_COURSE_PATH).api_get_course_path().'/upload/learning_path/images/'.$_SESSION['oLP']->get_preview_image());
                        if($picture['1'] < 96) { $style = ' style="padding-top:'.((94 -$picture['1'])/2).'px;" '; }
                        $size = ($picture['0'] > 104 && $picture['1'] > 96 )? ' width="104" height="96" ': $style;
                        $my_path = api_get_path(WEB_COURSE_PATH).api_get_course_path().'/upload/learning_path/images/'.$_SESSION['oLP']->get_preview_image();
                        echo '<img '.$size.' src="'.$my_path.'">';
                    } else {
                        echo Display :: display_icon('unknown_250_100.jpg', ' ');
                    }
                    ?>
                    </center>
                    </span>
                   </div>

                <div id="nav_id" name="nav_name" class="lp_nav" style="margin-left:105;height:90">
                    <?php
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
                        $sql = "SELECT audio FROM " . $tbl_lp_item . " WHERE lp_id = '" . $_SESSION['oLP']->lp_id."'";
                        $res_media= Database::query($sql);

                        if (Database::num_rows($res_media) > 0) {
                            while ($row_media= Database::fetch_array($res_media)) {
                                 if (!empty($row_media['audio'])) {$show_audioplayer = true; break;}
                            }
                        }
                    ?>

                    <div id="lp_navigation_elem" class="lp_navigation_elem" style="padding-left:130px;margin-top:9px;">
                        <div style="padding-top:15px;padding-bottom:50px;" ><?php echo $navigation_bar; ?></div>
                        <div id="progress_bar" style="height:20px"><?php echo $progress_bar; ?></div>
                    </div>
                </div>
            </div>
       </div>
       <!-- end image preview Layout -->
        <div id="author_name" style="position:relative;top:2px;left:0px;margin:0;padding:0;text-align:center;width:100%">
            <?php echo $_SESSION['oLP']->get_author(); ?>
        </div>

        <!-- media player layaout -->
        <?php $style_media = (($show_audioplayer) ? ' style= "position:relative;top:10px;left:10px;margin:8px;font-size:32pt;height:20px;"' : 'style="height:15px"'); ?>
        <div id="media"  <?php echo $style_media; ?>>
            <?php echo (!empty($mediaplayer)) ? $mediaplayer : '&nbsp;' ?>
        </div>
        <!-- end media player layaout -->

        <!-- toc layout -->
        <!-- hub 26-05-2010 remove height for lp toc height resizable
        <div id="toc_id" name="toc_name"  style="overflow: auto; padding:0;margin-top:20px;height:60%;width:100%">
        -->
        <div id="toc_id" name="toc_name"  style="overflow: auto; padding:0;margin-top:20px;width:100%">
            <div id="learning_path_toc" style="font-size:9pt;margin:0;"><?php echo $_SESSION['oLP']->get_html_toc(); ?>

        <?php if (!empty($_SESSION['oLP']->scorm_debug)) { //only show log
        ?>
            <!-- log message layout -->
            <div id="lp_log_name" name="lp_log_name" class="lp_log" style="height:150px;overflow:auto;margin:4px">
                <div id="log_content"></div>
                <div id="log_content_cleaner" style="color: white;">.</div>
            </div>
            <!-- end log message layout -->
       <?php } ?>
            </div>
        </div>
        <!-- end toc layout -->
    </div>
    <!-- end left Zone -->

    <!-- right Zone -->
    <div id="learning_path_right_zone" style="margin-left:<?php echo $margin_left;?>;height:100%">


    <?php
        // hub 26-05-2010 Fullscreen or not fullscreen
        if ($_SESSION['oLP']->mode == 'fullscreen') {
            echo '<iframe id="content_id_blank" name="content_name_blank" src="blank.php" border="0" frameborder="0" style="width:100%;height:600px" ></iframe>';
        } else {
            echo '<iframe id="content_id" name="content_name" src="'.$src.'" border="0" frameborder="0"  style="width:100%;height:600px" ></iframe>';
        }
    ?>
    </div>
    <!-- end right Zone -->
</div>
<script language="JavaScript" type="text/javascript">
    // Need to be called after the <head> to be sure window.oxajax is defined.
    //var dokeos_xajax_handler = window.oxajax;
</script>
<script language="JavaScript" type="text/javascript">
    // Resize right and left pane to full height (HUB 20-05-2010).
    function updateContentHeight() {
        document.body.style.overflow = 'hidden';
        var IE = window.navigator.appName.match(/microsoft/i);
        var hauteurHeader = document.getElementById('header').offsetHeight;
        var hauteurAuthorImg = document.getElementById('author_image').offsetHeight;
        var hauteurAuthorName = document.getElementById('author_name').offsetHeight;
        var hauteurMedia = document.getElementById('media').offsetHeight;
        var hauteurTitre = document.getElementById('scorm_title').offsetHeight;
        var hauteurAction = 0;
        if (document.getElementById('actions_lp')) hauteurAction = document.getElementById('actions_lp').offsetHeight;
        var hauteurHaut = hauteurHeader+hauteurAuthorImg+hauteurAuthorName+hauteurMedia+hauteurTitre+hauteurAction;
        var innerHauteur = (IE) ? document.body.clientHeight : window.innerHeight ;
        var debugsize = 0;
        // -40 is a static adjustement for margin, spaces on the page
        <?php if (!empty($_SESSION['oLP']->scorm_debug)) echo 'debugsize = 150;' ?>
        document.getElementById('inner_lp_toc').style.height = innerHauteur - hauteurHaut - 40 - debugsize + "px";
        if (document.getElementById('content_id')) {
            document.getElementById('content_id').style.height = innerHauteur + 'px';
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
                  {type:"script", id:"_fr1", src:"<?php echo api_get_path(WEB_LIBRARY_PATH); ?>javascript/jquery.js"},
                  {type:"script", id:"_fr2", src:"<?php echo api_get_path(WEB_LIBRARY_PATH); ?>javascript/jquery.highlight.js"},
                  {type:"script", id:"_fr3", src:"<?php echo api_get_path(WEB_LIBRARY_PATH); ?>fckeditor/editor/plugins/glossary/fck_glossary_manual.js"}
          ] }
          );
    <?php
        } elseif (api_get_setting('show_glossary_in_documents') == 'isautomatic') {
      ?>
    $.frameReady(function(){
        //  $("<div>I am a div courses</div>").prependTo("body");
      }, "top.content_name",
      { load: [
              {type:"script", id:"_fr1", src:"<?php echo api_get_path(WEB_LIBRARY_PATH); ?>javascript/jquery.js"},
              {type:"script", id:"_fr2", src:"<?php echo api_get_path(WEB_LIBRARY_PATH); ?>javascript/jquery.highlight.js"},
              {type:"script", id:"_fr3", src:"<?php echo api_get_path(WEB_LIBRARY_PATH); ?>fckeditor/editor/plugins/glossary/fck_glossary_automatic.js"}
          ] }
          );
      <?php
           }
      }
      ?>
}
    window.onload = updateContentHeight;
    window.onresize = updateContentHeight;

-->
</script>
</body>
<?php
// Restore a global setting.
$_setting['show_navigation_menu'] = $save_setting;
