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

//escape external variables
/*
-----------------------------------------------------------
	Header
-----------------------------------------------------------
*/
//$htmlHeadXtra[] = '<script type="text/javascript" src="lp_view.lib.js"></script>';
//$htmlHeadXtra[] = $xajax->getJavascript('../inc/lib/xajax/')."\n";
$htmlHeadXtra[] = '<script src="../inc/lib/javascript/jquery.js" type="text/javascript" language="javascript"></script>'; //jQuery

$_SESSION['oLP']->error = '';
$lp_type = $_SESSION['oLP']->get_type();
$lp_item_id = $_SESSION['oLP']->get_current_item_id();
//$lp_item_id = learnpath::escape_string($_GET['item_id']);
//$_SESSION['oLP']->set_current_item($lp_item_id); // already done by lp_controller.php

//Prepare variables for the test tool (just in case) - honestly, this should disappear later on
$_SESSION['scorm_view_id'] = $_SESSION['oLP']->get_view_id();
$_SESSION['scorm_item_id'] = $lp_item_id;
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
			$src = 'blank.php';
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

// update status from lp_item_view table when you finish the exercises in learning path
if (isset($_GET['lp_id']) && isset($_GET['lp_item_id'])) {
    $TBL_LP_ITEM_VIEW		= Database::get_course_table(TABLE_LP_ITEM_VIEW);
	$TBL_LP_VIEW			= Database::get_course_table(TABLE_LP_VIEW);	
	$learnpath_item_id      = Security::remove_XSS($_GET['lp_item_id']);
    $learnpath_id           = Security::remove_XSS($_GET['lp_id']);   	
	
	$sql = "UPDATE $TBL_LP_ITEM_VIEW SET status = 'completed' WHERE lp_item_id = '".Database::escape_string($learnpath_item_id)."'
			 AND lp_view_id = (SELECT lp_view.id FROM $TBL_LP_VIEW lp_view WHERE user_id = '".Database::escape_string($user_id)."' AND lp_id='".Database::escape_string($learnpath_id)."')";
	api_sql_query($sql,__FILE__,__LINE__);	
}

$_SESSION['oLP']->set_previous_item($lp_item_id);
$nameTools = $_SESSION['oLP']->get_name();
$save_setting = get_setting("show_navigation_menu");
global $_setting;
$_setting['show_navigation_menu'] = false;

$scorm_css_header=true; 	
$lp_theme_css=$_SESSION['oLP']->get_theme(); //sets the css theme of the LP this call is also use at the frames (toc, nav, message)
 
if($_SESSION['oLP']->mode == 'fullscreen')
{
	$htmlHeadXtra[] = "<script>window.open('$src','content_name','toolbar=0,location=0,status=0,scrollbars=1,resizable=1');</script>";	
	include_once('../inc/reduced_header.inc.php');
	
	//set flag to ensure lp_header.php is loaded by this script (flag is unset in lp_header.php)
	$_SESSION['loaded_lp_view'] = true;
	?>
	<frameset cols="270,*">
		<frameset rows="20,475,95,80,*">
            <frame id="header" src="lp_header.php"  border="0" frameborder="0" scrolling="no"/>
			<frame id="toc_id" name="toc_name" class="lp_toc" src="lp_toc.php" border="0" frameborder="0" scrolling="no"/>
			<frame id="nav_id" name="nav_name" class="lp_nav" src="lp_nav.php" border="0" frameborder="0" />
			<frame id="message_id" name="message_name" class="message" src="lp_message.php" border="0" frameborder="0" />
			<frame id="lp_log_id" name="lp_log_name" class="lp_log" src="lp_log.php" border="0" frameborder="0" />
		</frameset>
		<frame id="content_id_blank" name="content_name_blank" src="blank.php" border="0" frameborder="0">
		</frame>
	</frameset>
	<noframes>
	This page relies heavily on frames. If your browser doesn't support frames, please try to find a better one. Some are available for free and run on multiple platforms. We recommend you try <a href="http://www.mozilla.com/firefox/">Firefox</a>. Get it from its official website by clicking the link.
	</noframes>
</html>
<?php
}
else
{	
	include_once('../inc/reduced_header.inc.php');
	$displayAudioRecorder = (api_get_setting('service_visio','active')=='true') ? true : false;
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
	$audio_record_width='';	
	$show_audioplayer=false;
	if ($displayAudioRecorder) 
	{	// we find if there is a audioplayer
		$audio_recorder_item_id = $_SESSION['oLP']->current;		
		$docs = Database::get_course_table(TABLE_DOCUMENT);
		$select = "SELECT * FROM $docs " .
				" WHERE path like BINARY '/audio/lpi".Database::escape_string($audio_recorder_item_id)."-%' AND filetype='file' " .
				" ORDER BY path DESC";				
		$res = api_sql_query($select);
		if(Database::num_rows($res)>0)
		{ 		
			$show_audioplayer=true; 
		}
	}

	?>
	<frameset cols="270,*">
		<frameset rows="60,270,105,30,480,20">
            <frame id="header" src="lp_header.php"  border="0" frameborder="0" scrolling="no"/>                
            <frame id="author_image" name="author_image" class="lp_author_image" src="lp_author_image.php" border="0" frameborder="0" scrolling="no" />
			<frame id="nav_id" name="nav_name" class="lp_nav" src="lp_nav.php" border="0" frameborder="0" scrolling="no" />
			<frame id="message_id" name="message_name" class="message" src="lp_message.php" border="0" frameborder="0" />				
			<frame id="toc_id" name="toc_name" class="lp_toc" src="lp_toc.php" border="0" frameborder="0" scrolling="no"/>												
			<frame id="lp_log_id" name="lp_log_name" class="lp_log" src="lp_log.php" border="0" frameborder="0" />
		</frameset>
		<frame id="content_id" name="content_name" src="<?php echo $src; ?>" border="0" frameborder="0">
		</frame>
	</frameset>
		<noframes>
		This page relies heavily on frames. If your browser doesn't support frames, please try to find a better one. Some are available for free and run on multiple platforms. We recommend you try <a href="http://www.mozilla.com/firefox/">Firefox</a>. Get it from its official website by clicking the link.
		</noframes>
</html>
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
