<?php // $Id: lp_view.php,v 1.33 2006/09/12 10:20:46 yannoo Exp $
/**
==============================================================================
* This file was origially the copy of document.php, but many modifications happened since then ;
* the direct file view is not needed anymore, if the user uploads a scorm zip file, a directory
* will be automatically created for it, and the files will be uncompressed there for example ;
*
* @package dokeos.learnpath
* @author Yannick Warnier <ywarnier@beeznest.org> - redesign
* @author Denes Nagy, principal author
* @author Isthvan Mandak, several new features
* @author Roan Embrechts, code improvements and refactoring
* @license	GNU/GPL - See Dokeos license directory for details
==============================================================================
*/
/**
 * Script
 */
/*
==============================================================================
		INIT SECTION
==============================================================================
*/

$_SESSION['whereami'] = 'lp/view';
$this_section=SECTION_COURSES;

if($lp_controller_touched!=1){
	header('location: lp_controller.php?action=view&item_id='.$_REQUEST['item_id']);
}

/*
-----------------------------------------------------------
	Libraries
-----------------------------------------------------------
*/
require_once('back_compat.inc.php');
//require_once('../learnpath/learnpath_functions.inc.php');
require_once('scorm.lib.php');
require_once('learnpath.class.php');
require_once('learnpathItem.class.php');
//require_once('lp_comm.common.php'); //xajax functions

if ($is_allowed_in_course == false) api_not_allowed();
/*
-----------------------------------------------------------
	Variables
-----------------------------------------------------------
*/
//$charset = 'UTF-8';
$charset = 'ISO-8859-1';
$oLearnpath = false;
$course_code = api_get_course_id();
$user_id = api_get_user_id();
$platform_theme = api_get_setting('stylesheets'); 	// plataform's css
$my_style=$platform_theme;
//escape external variables
/*
-----------------------------------------------------------
	Header
-----------------------------------------------------------
*/
//$htmlHeadXtra[] = '<script type="text/javascript" src="lp_view.lib.js"></script>';
//$htmlHeadXtra[] = $xajax->getJavascript('../inc/lib/xajax/')."\n";
$htmlHeadXtra[] = '<script src="../inc/lib/javascript/jquery.js" type="text/javascript" language="javascript"></script>'; //jQuery

$htmlHeadXtra[] = '<script language="javascript">
function cleanlog(){
  if(document.getElementById){
  	document.getElementById("log_content").innerHTML = "";
  }
}
</script>';

$htmlHeadXtra[] = '<script language="JavaScript" type="text/javascript">
  	var dokeos_xajax_handler = window.oxajax;
</script>';


$_SESSION['oLP']->error = '';
$lp_type = $_SESSION['oLP']->get_type();
$lp_item_id = $_SESSION['oLP']->get_current_item_id();
//$lp_item_id = learnpath::escape_string($_GET['item_id']);
//$_SESSION['oLP']->set_current_item($lp_item_id); // already done by lp_controller.php

//Prepare variables for the test tool (just in case) - honestly, this should disappear later on
$_SESSION['scorm_view_id'] = $_SESSION['oLP']->get_view_id();
$_SESSION['scorm_item_id'] = $lp_item_id;
$_SESSION['lp_mode'] = $_SESSION['oLP']->mode;
//reinit exercises variables to avoid spacename clashes (see exercise tool)
if(isset($exerciseResult) or isset($_SESSION['exerciseResult']))
{
    api_session_unregister($exerciseResult);
}
unset($_SESSION['objExercise']);
unset($_SESSION['questionList']);
/**
 * Get a link to the corresponding document
 */


if (!isset($src))
 {
 	$src = '';
	switch($lp_type)
	{
		case 1:
			$_SESSION['oLP']->stop_previous_item();
			$htmlHeadXtra[] = '<script src="scorm_api.php" type="text/javascript" language="javascript"></script>';
			$prereq_check = $_SESSION['oLP']->prerequisites_match($lp_item_id);
			if($prereq_check === true){
				$src = $_SESSION['oLP']->get_link('http',$lp_item_id);
				$_SESSION['oLP']->start_current_item(); //starts time counter manually if asset
			}else{
				$src = 'blank.php?error=prerequisites';
			}
			break;
		case 2:
			//save old if asset
			$_SESSION['oLP']->stop_previous_item(); //save status manually if asset
			$htmlHeadXtra[] = '<script src="scorm_api.php" type="text/javascript" language="javascript"></script>';
			$prereq_check = $_SESSION['oLP']->prerequisites_match($lp_item_id);
			if($prereq_check === true){
				$src = $_SESSION['oLP']->get_link('http',$lp_item_id);
				$_SESSION['oLP']->start_current_item(); //starts time counter manually if asset
			}else{
			$src = 'blank.php?error=prerequisites';
			}
			break;
		case 3:
			//aicc
			$_SESSION['oLP']->stop_previous_item(); //save status manually if asset
			$htmlHeadXtra[] = '<script src="'.$_SESSION['oLP']->get_js_lib().'" type="text/javascript" language="javascript"></script>';
			$prereq_check = $_SESSION['oLP']->prerequisites_match($lp_item_id);
			if($prereq_check === true){
				$src = $_SESSION['oLP']->get_link('http',$lp_item_id);
				$_SESSION['oLP']->start_current_item(); //starts time counter manually if asset
			}else{
				$src = 'blank.php';
			}
			break;
		case 4:
			break;
	}
}

$list = $_SESSION['oLP']->get_toc();
$type_quiz = false;

foreach($list as $toc) {
	if ($toc['id'] == $lp_item_id && ($toc['type']=='quiz') ) {
		$type_quiz = true;		
	}	
}

// update status,total_time from lp_item_view table when you finish the exercises in learning path
if ($type_quiz && !empty($_REQUEST['exeId']) && isset($_GET['lp_id']) && isset($_GET['lp_item_id'])) {
	global $src;
	$_SESSION['oLP']->items[$_SESSION['oLP']->current]->write_to_db();	
	$TBL_TRACK_EXERCICES	= Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_EXERCICES);
	$TBL_LP_ITEM_VIEW		= Database::get_course_table(TABLE_LP_ITEM_VIEW);
	$TBL_LP_VIEW			= Database::get_course_table(TABLE_LP_VIEW);
	$TBL_LP_ITEM			= Database::get_course_table(TABLE_LP_ITEM);
	$safe_item_id      = Database::escape_string($_GET['lp_item_id']);
    $safe_id           = Database::escape_string($_GET['lp_id']);
	$safe_exe_id = Database::escape_string($_REQUEST['exeId']);

	if ($safe_id == strval(intval($safe_id)) && $safe_item_id == strval(intval($safe_item_id))) {

		$sql = 'SELECT start_date,exe_date,exe_result,exe_weighting FROM ' . $TBL_TRACK_EXERCICES . ' WHERE exe_id = '.(int)$safe_exe_id;
		$res = api_sql_query($sql,__FILE__,__LINE__);
		$row_dates = Database::fetch_array($res);

		$time_start_date = convert_mysql_date($row_dates['start_date']);
		$time_exe_date 	 = convert_mysql_date($row_dates['exe_date']);
		$mytime = ((int)$time_exe_date-(int)$time_start_date);
		$score = (float)$row_dates['exe_result'];
		$max_score = (float)$row_dates['exe_weighting'];

		$sql_upd_status = "UPDATE $TBL_LP_ITEM_VIEW SET status = 'completed' WHERE lp_item_id = '".(int)$safe_item_id."'
				 AND lp_view_id = (SELECT lp_view.id FROM $TBL_LP_VIEW lp_view WHERE user_id = '".(int)$_SESSION['oLP']->user_id."' AND lp_id='".(int)$safe_id."')";
		api_sql_query($sql_upd_status,__FILE__,__LINE__);

		$sql_upd_max_score = "UPDATE $TBL_LP_ITEM SET max_score = '$max_score' WHERE id = '".(int)$safe_item_id."'";
		api_sql_query($sql_upd_max_score,__FILE__,__LINE__);

		$sql_last_attempt = "SELECT id FROM $TBL_LP_ITEM_VIEW  WHERE lp_item_id = '$safe_item_id' AND lp_view_id = '".$_SESSION['oLP']->lp_view_id."' order by id desc limit 1";
		$res_last_attempt = api_sql_query($sql_last_attempt,__FILE__,__LINE__);
		$row_last_attempt = Database::fetch_row($res_last_attempt);

		if (Database::num_rows($res_last_attempt)>0) {
			$sql_upd_score = "UPDATE $TBL_LP_ITEM_VIEW SET score = $score,total_time = $mytime WHERE id='".$row_last_attempt[0]."'";
			api_sql_query($sql_upd_score,__FILE__,__LINE__);
		}
	}
	if($_SESSION['oLP']->mode == 'fullscreen') {
		$lp_next_item_id = $_SESSION['oLP']->get_next_item_id();
		$src = $_SESSION['oLP']->get_link('http',$lp_next_item_id);
		$_SESSION['oLP']->current = $lp_next_item_id;				
	} else {
		$src = api_get_path(WEB_CODE_PATH).'newscorm/lp_controller.php?action=stats';
	}		
		
}

$_SESSION['oLP']->set_previous_item($lp_item_id);
$nameTools = $_SESSION['oLP']->get_name();
$save_setting = get_setting("show_navigation_menu");
global $_setting;
$_setting['show_navigation_menu'] = 'false';

$scorm_css_header=true; 	
$lp_theme_css=$_SESSION['oLP']->get_theme(); //sets the css theme of the LP this call is also use at the frames (toc, nav, message)
 
if($_SESSION['oLP']->mode == 'fullscreen')
{
	$htmlHeadXtra[] = "<script>window.open('$src','content_id','toolbar=0,location=0,status=0,scrollbars=1,resizable=1');</script>";	
	include_once('../inc/reduced_header.inc.php');
	
	//set flag to ensure lp_header.php is loaded by this script (flag is unset in lp_header.php)
	$_SESSION['loaded_lp_view'] = true;
	?>
	
		<table>
	<tr><td valign="top">
	<div id="header">
	      <div id="learningPathHeader" style="font-size:14px;  ">
	      <table>
	      <tr>
	      <td>
	      <a href="lp_controller.php?action=return_to_course_homepage" target="_top" onclick="window.parent.API.save_asset();"><img src="../img/lp_arrow.gif" /></a>
	      </td>
	      <td>
	      <a class="link" href="lp_controller.php?action=return_to_course_homepage" target="_top" onclick="window.parent.API.save_asset();"><?php echo get_lang('CourseHomepageLink'); ?></a>
	      </td>
	      </tr>
	      </table>
	      </div>
    </div>
        
        
<div id="learningPathLeftZone" style="float: left; width: 320px;">
  <div id="author_image" name="author_image" class="lp_author_image">
	<?php $image = '../img/lp_author_background.gif'; ?> 
	<div>
		<div style="width: 310px; height:140px; margin-left:20px; background-image: url('../img/lp_author_background.gif');background-repeat:no-repeat; ">	
	       		<div style="width:140px; float:left;">
	       		<span style="float:right; padding-top:16px; padding-right:10px;">
		        <?php if ($_SESSION['oLP']->get_preview_image()!=''): ?>
		        <img  width="120" height="100" src="<?php echo api_get_path(WEB_COURSE_PATH).api_get_course_path().'/upload/learning_path/images/'.$_SESSION['oLP']->get_preview_image(); ?>">
		        <?php 
					else
					: echo Display :: display_icon('unknown_250_100.jpg', ' ');
					endif;
					?>
				</span>
	       		</div>
	       
	 	   <div id="nav_id" name="nav_name" class="lp_nav" style="float:left; width:155px;">
	        <?php	
									
					$display_mode = $_SESSION['oLP']->mode;
					$scorm_css_header = true;
					$lp_theme_css = $_SESSION['oLP']->get_theme();
				
					//Setting up the CSS theme if exists						
				
					if (!empty ($lp_theme_css) && !empty ($mycourselptheme) && $mycourselptheme != -1 && $mycourselptheme == 1) {
						global $lp_theme_css;
					} else {
						$lp_theme_css = $my_style;
					}
				
					$progress_bar = $_SESSION['oLP']->get_progress_bar('', -1, '', true);
					$navigation_bar = $_SESSION['oLP']->get_navigation_bar();
					$mediaplayer = $_SESSION['oLP']->get_mediaplayer();	
					
					$tbl_lp_item	= Database::get_course_table('lp_item');
					$show_audioplayer = false;
					// getting all the information about the item
					$sql = "SELECT audio FROM " . $tbl_lp_item . " WHERE lp_id = '" . $_SESSION['oLP']->lp_id."'";
					$res_media= api_sql_query($sql, __FILE__, __LINE__);					
					
					if(Database::num_rows($res_media) > 0){
						while($row_media= Database::fetch_array($res_media)) {
						     if(!empty($row_media['audio'])) {$show_audioplayer = true; break;}	
						}
					}
				?>
				
					<div id="lp_navigation_elem" class="lp_navigation_elem">
					<div style="float:left; padding-top:22px;padding-left:10px;"><?php echo $navigation_bar; ?></div>
					<div style="float:left;  padding-top:22px"><?php echo $progress_bar; ?></div>
					</div>				
			</div>  
	    </div>
	    
	    			<div id="author_name" style="margin:0;padding:0;text-align:center">
	    			<?php echo $_SESSION['oLP']->get_author() ?>
	    			</div>
	    	
   </div>
			
					<?php $style_media = (($show_audioplayer)?' style= "margin:15px 34px;padding:0;font-size:35px;height:20px"':'style="height:1px"'); ?>
					<div id="media" <?php echo $style_media ?> >
					<?php echo (!empty($mediaplayer))?$mediaplayer:'&nbsp;' ?>
					</div>
 </div>	
		
					<div id="message_id" name="message_name" class="message" style="height:12px">
	        		<div id="msg_div_id" class="message">
	        		<?php echo $error = $_SESSION['oLP']->error; ?>
	        		</div>
     				</div>
     
        			<div id="toc_id" name="toc_name" class="lp_toc" style="position:relative;top:10px;left:0">
  					<div id="learningPathToc" style="width:320px;overflow-y:auto;overflow-x:hidden;font-size:9pt;">
  					<?php echo $_SESSION['oLP']->get_html_toc(); ?></div>
        			</div>
        
        			<div id="lp_log_name" name="lp_log_name" class="lp_log" style="position:relative;top:10px;left:0;height:100px;overflow:auto">
	        		<div id="log_content"></div>
	        		<div style="color: white;" onClick="cleanlog();">.</div>
        			</div>
</div>
					<input type="hidden" id="old_item" name ="old_item" value="0"/>
    				<input type="hidden" id="current_item_id" name ="current_item_id" value="0" />
    				</td><td align="left" width="100%" valign="top">
    				<div id="learningPathRightZone">
        			<iframe id="content_id_blank" name="content_name_blank" src="blank.php" border="0" frameborder="0" style="height:600px;width: 100%"></iframe>
    				</div>
    </td></tr>
    </table>
    
	<script language="JavaScript" type="text/javascript">
	// Need to be called after the <head> to be sure window.oxajax is defined
  	var dokeos_xajax_handler = window.oxajax;
	</script>
    <script language="JavaScript" type="text/javascript">
	<!--
	var leftZoneHeightOccupied = 0;
	var rightZoneHeightOccupied = 0;
	var initialLeftZoneHeight = 0;
	var initialRightZoneHeight = 0;

	var updateContentHeight = function() {
		winHeight = (window.innerHeight != undefined ? window.innerHeight : document.documentElement.clientHeight);
		newLeftZoneHeight = winHeight - leftZoneHeightOccupied;
		newRightZoneHeight = winHeight - rightZoneHeightOccupied;
		if (newLeftZoneHeight <= initialLeftZoneHeight) {
			newLeftZoneHeight = initialLeftZoneHeight;
			newRightZoneHeight = newLeftZoneHeight + leftZoneHeightOccupied - rightZoneHeightOccupied;
		}
		if (newRightZoneHeight <= initialRightZoneHeight) {
			newRightZoneHeight = initialRightZoneHeight;
			newLeftZoneHeight = newRightZoneHeight + rightZoneHeightOccupied - leftZoneHeightOccupied;
		}
		document.getElementById('learningPathToc').style.height = newLeftZoneHeight + 'px';
		document.getElementById('learningPathRightZone').style.height = newRightZoneHeight + 'px';
				
		if(document.getElementById('content_id')) {		
		document.getElementById('content_id').style.height = newRightZoneHeight + 'px';
		} 		
		if (document.body.clientHeight > winHeight) {
			document.body.style.overflow = 'auto';
		} else {
			document.body.style.overflow = 'hidden';
		}
	};

	window.onload = function() {
		initialLeftZoneHeight = document.getElementById('learningPathToc').offsetHeight;
		initialRightZoneHeight = document.getElementById('learningPathRightZone').offsetHeight;
		docHeight = document.body.clientHeight;
		leftZoneHeightOccupied = docHeight - initialLeftZoneHeight;
		rightZoneHeightOccupied = docHeight - initialRightZoneHeight;
		document.body.style.overflow = 'hidden';
		updateContentHeight();
	}

	window.onresize = updateContentHeight;
	-->
	</script>

<?php
}
else
{	
	include_once('../inc/reduced_header.inc.php');
	//$displayAudioRecorder = (api_get_setting('service_visio','active')=='true') ? true : false;
	//check if audio recorder needs to be in studentview
	$course_id=$_SESSION["_course"]["id"];
	if($_SESSION["status"][$course_id]==5)
	{
		$audio_recorder_studentview = true;
	}
	else
	{
		$audio_recorder_studentview = false;
	}
	//set flag to ensure lp_header.php is loaded by this script (flag is unset in lp_header.php)
	$_SESSION['loaded_lp_view'] = true;	
	?>
	<style type="text/css" media="screen, projection">
	/*<![CDATA[*/
	@import "<?php echo api_get_path(WEB_CODE_PATH); ?>css/<?php echo $my_style;?>/scorm.css";
	/*]]>*/
	</style>
	<table>
	<tr><td valign="top">
	<div id="header">
	        <div id="learningPathHeader" style="font-size:14px;  ">
	            <table>
	                <tr>
	                    <td>
	                        <a href="lp_controller.php?action=return_to_course_homepage" target="_top" onclick="window.parent.API.save_asset();"><img src="../img/lp_arrow.gif" /></a>
	                    </td>
	                    <td>
	                        <a class="link" href="lp_controller.php?action=return_to_course_homepage" target="_top" onclick="window.parent.API.save_asset();"><?php echo get_lang('CourseHomepageLink'); ?></a>
	                    </td>
	                </tr>
	            </table>
	        </div>
    </div>
        
        
<div id="learningPathLeftZone" style="float: left; width: 330px;">
  <div id="author_image" name="author_image" class="lp_author_image">
	<?php $image = '../img/lp_author_background.gif'; ?> 
	<div id="image_preview;">
		<div style="width: 310px; height:140px; margin:0 20px; background-image: url('../img/lp_author_background.gif');background-repeat:no-repeat; ">
	       	
	       	
	       	
	       		<div style="width:140px; float:left;">
	       		<span style="float:right; padding-top:16px; padding-right:10px;">
		        <?php if ($_SESSION['oLP']->get_preview_image()!=''): ?>
		        <img width="120" height="100" src="<?php echo api_get_path(WEB_COURSE_PATH).api_get_course_path().'/upload/learning_path/images/'.$_SESSION['oLP']->get_preview_image(); ?>">
		        <?php 
					else
					: echo Display :: display_icon('unknown_250_100.jpg', ' ');
					endif;
					?>
				</span>
	       		</div>
	       
	 	   <div id="nav_id" name="nav_name" class="lp_nav" style="float:left; width:155px;">
	        <?php	
									
					$display_mode = $_SESSION['oLP']->mode;
					$scorm_css_header = true;
					$lp_theme_css = $_SESSION['oLP']->get_theme();
				
					//Setting up the CSS theme if exists						
				
					if (!empty ($lp_theme_css) && !empty ($mycourselptheme) && $mycourselptheme != -1 && $mycourselptheme == 1) {
						global $lp_theme_css;
					} else {
						$lp_theme_css = $my_style;
					}
				
					$progress_bar = $_SESSION['oLP']->get_progress_bar('', -1, '', true);
					$navigation_bar = $_SESSION['oLP']->get_navigation_bar();
					$mediaplayer = $_SESSION['oLP']->get_mediaplayer();	
					
					$tbl_lp_item	= Database::get_course_table('lp_item');
					$show_audioplayer = false;
					// getting all the information about the item
					$sql = "SELECT audio FROM " . $tbl_lp_item . " WHERE lp_id = '" . $_SESSION['oLP']->lp_id."'";
					$res_media= api_sql_query($sql, __FILE__, __LINE__);					
					
					if(Database::num_rows($res_media) > 0){
						while($row_media= Database::fetch_array($res_media)) {
						     if(!empty($row_media['audio'])) {$show_audioplayer = true; break;}	
						}
					}
				?>
				
					<div id="lp_navigation_elem" class="lp_navigation_elem">
					<div style="float:left; padding-top:22px;padding-left:10px;"><?php echo $navigation_bar; ?></div>
					<div style="float:left;  padding-top:22px"><?php echo $progress_bar; ?></div>
					</div>				
			</div>  
	    </div>
	    
	    			<div id="author_name" style="margin:0;padding:0;text-align:center">
	    			<?php echo $_SESSION['oLP']->get_author() ?>
	    			</div>		    
		
   </div>
			
					<?php $style_media = (($show_audioplayer)?' style= "margin:15px 34px;padding:0;font-size:35px;height:20px"':'style="height:1px"'); ?>
					<div id="media" <?php echo $style_media ?> >
					<?php echo (!empty($mediaplayer))?$mediaplayer:'&nbsp;' ?>
					</div>
 </div>	
		
					<div id="message_id" name="message_name" class="message" style="height:12px">
	        		<div id="msg_div_id" class="message">
	        		<?php echo (!empty($_SESSION['oLP']->error)?$_SESSION['oLP']->error:''); ?>
	        		</div>
     				</div>
     
        			<div id="toc_id" name="toc_name" class="lp_toc" style="position:relative;top:10px;left:0">
  					<div id="learningPathToc" style="width:320px;overflow-y:auto;overflow-x:hidden;font-size:9pt;">
  					<?php echo $_SESSION['oLP']->get_html_toc(); ?></div>
        			</div>
        
        			<div id="lp_log_name" name="lp_log_name" class="lp_log" style="position:relative;top:10px;left:0;height:100px;overflow:auto">
	        		<div id="log_content"></div>
	        		<div style="color: white;" onClick="cleanlog();">.</div>
        			</div>
</div>
					<input type="hidden" id="old_item" name ="old_item" value="0"/>
    				<input type="hidden" id="current_item_id" name ="current_item_id" value="0" />
    				</td><td align="left" width="100%" valign="top">
    				<div id="learningPathRightZone">
        			<iframe id="content_id" name="content_name" src="<?php echo $src; ?>" border="0" frameborder="0" style="height:600px;width: 100%"></iframe>
    				</div>
    </td></tr>
    </table>
    <script language="JavaScript" type="text/javascript">
	// Need to be called after the <head> to be sure window.oxajax is defined
  	var dokeos_xajax_handler = window.oxajax;
	</script>
    <script language="JavaScript" type="text/javascript">
	<!--
	var leftZoneHeightOccupied = 0;
	var rightZoneHeightOccupied = 0;
	var initialLeftZoneHeight = 0;
	var initialRightZoneHeight = 0;

	var updateContentHeight = function() {
		winHeight = (window.innerHeight != undefined ? window.innerHeight : document.documentElement.clientHeight);
		newLeftZoneHeight = winHeight - leftZoneHeightOccupied;
		newRightZoneHeight = winHeight - rightZoneHeightOccupied;
		if (newLeftZoneHeight <= initialLeftZoneHeight) {
			newLeftZoneHeight = initialLeftZoneHeight;
			newRightZoneHeight = newLeftZoneHeight + leftZoneHeightOccupied - rightZoneHeightOccupied;
		}
		if (newRightZoneHeight <= initialRightZoneHeight) {
			newRightZoneHeight = initialRightZoneHeight;
			newLeftZoneHeight = newRightZoneHeight + rightZoneHeightOccupied - leftZoneHeightOccupied;
		}
		document.getElementById('learningPathToc').style.height = newLeftZoneHeight + 'px';
		document.getElementById('learningPathRightZone').style.height = newRightZoneHeight + 'px';
		document.getElementById('content_id').style.height = newRightZoneHeight + 'px';
		if (document.body.clientHeight > winHeight) {
			document.body.style.overflow = 'auto';
		} else {
			document.body.style.overflow = 'hidden';
		}
	};

	window.onload = function() {
		initialLeftZoneHeight = document.getElementById('learningPathToc').offsetHeight;
		initialRightZoneHeight = document.getElementById('learningPathRightZone').offsetHeight;
		docHeight = document.body.clientHeight;
		leftZoneHeightOccupied = docHeight - initialLeftZoneHeight;
		rightZoneHeightOccupied = docHeight - initialRightZoneHeight;
		document.body.style.overflow = 'hidden';
		updateContentHeight();
	}

	window.onresize = updateContentHeight;
	-->
	</script>

<?php
	/*
	==============================================================================
	  FOOTER
	==============================================================================
	*/
	//Display::display_footer();
}
//restore global setting
$_setting['show_navigation_menu'] = $save_setting;
?>
