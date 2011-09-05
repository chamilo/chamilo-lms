<?php
/* For licensing terms, see /license.txt */

/**
 * This is the index file displayed when a user is logged in on Chamilo.
 *
 * It displays:
 * - personal course list
 * - menu bar
 * Search for CONFIGURATION parameters to modify settings
 * @todo rewrite code to separate display, logic, database code
 * @package chamilo.main
 * @todo Shouldn't the SCRIPTVAL_ and CONFVAL_ constant be moved to the config page? Has anybody any idea what the are used for?
 *       If these are really configuration settings then we can add those to the dokeos config settings.
 * @todo move display_courses and some other functions to a more appripriate place course.lib.php or user.lib.php
 * @todo use api_get_path instead of $rootAdminWeb
 * @todo check for duplication of functions with index.php (user_portal.php is orginally a copy of index.php)
 * @todo display_digest, shouldn't this be removed and be made into an extension?
 */

/**
 * INIT SECTION
 */

// Language files that should be included.
$language_file = array('courses', 'index','admin');

$cidReset = true; /* Flag forcing the 'current course' reset,
                    as we're not inside a course anymore  */

if (isset($_SESSION['this_section']))
    unset($_SESSION['this_section']); // For HTML editor repository.

/* Included libraries */

require_once './main/inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'userportal.lib.php';

api_block_anonymous_users(); // Only users who are logged in can proceed.

/* Constants and CONFIGURATION parameters */


//$load_dirs = api_get_setting('courses_list_document_dynamic_dropdown');
$load_dirs = true;

// This is the main function to get the course list.
$personal_course_list = UserManager::get_personal_session_course_list(api_get_user_id());

// Check if a user is enrolled only in one course for going directly to the course after the login.
if (api_get_setting('go_to_course_after_login') == 'true') {
    $my_session_list = array();
    $count_of_courses_no_sessions = 0;
    $count_of_courses_with_sessions = 0;
    foreach($personal_course_list as $course) {       
        if (!empty($course['id_session'])) {
            $my_session_list[$course['id_session']] = true;
            $count_of_courses_with_sessions++;
        } else {
            $count_of_courses_no_sessions++;
        }
    }
    $count_of_sessions = count($my_session_list);    

    //echo $count_of_sessions.' '.$count_of_courses_with_sessions.' '.$count_of_courses_no_sessions;
    //!isset($_SESSION['coursesAlreadyVisited'])
    if ($count_of_sessions == 1 && $count_of_courses_no_sessions == 0) {
     
        $key              = array_keys($personal_course_list);
        $course_info      = $personal_course_list[$key[0]];
        $course_directory = $course_info['d'];
        $id_session       = isset($course_info['id_session']) ? $course_info['id_session'] : 0;

        $url = api_get_path(WEB_CODE_PATH).'session/?session_id='.$id_session; 

        header('location:'.$url);            
        exit;
    }
    
    if (!isset($_SESSION['coursesAlreadyVisited']) && $count_of_sessions == 0 && $count_of_courses_no_sessions == 1) {
        $key              = array_keys($personal_course_list);
        $course_info      = $personal_course_list[$key[0]];
        $course_directory = $course_info['d'];
        $id_session       = isset($course_info['id_session']) ? $course_info['id_session'] : 0;
       
        $url = api_get_path(WEB_COURSE_PATH).$course_directory.'/?id_session='.$id_session;
        header('location:'.$url);            
        exit;
    }
   /*
        if (api_get_setting('hide_courses_in_sessions') == 'true') {
            //Check sessions
            $session_list = array();
            $only_session_id = 0;
            foreach($personal_course_list as $course_item) {
                $session_list[$course_item['id_session']] = $course_item;
                $only_session_id = $course_item['id_session'];
            }        
            if (count($session_list) == 1 && !empty($only_session_id)) {            
                header('Location:'.api_get_path(WEB_CODE_PATH).'session/?session_id='.$session_list[$only_session_id]['id_session']);    
            }
        }
    */    
}
/*
$nosession = false;
if (api_get_setting('use_session_mode') == 'true' && !$nosession) {
    $display_actives = !isset($_GET['inactives']);
}*/

$nameTools = get_lang('MyCourses');
$this_section = SECTION_COURSES;

/* Check configuration parameters integrity */
/*
if (CONFVAL_showExtractInfo != SCRIPTVAL_UnderCourseList and $orderKey[0] != 'keyCourse') {
    // CONFVAL_showExtractInfo must be SCRIPTVAL_UnderCourseList to accept $orderKey[0] != 'keyCourse'
    if (DEBUG || api_is_platform_admin()){ // Show bug if admin. Else force a new order.
        die('
                    <strong>config error:'.__FILE__.'</strong><br />
                    set
                    <ul>
                        <li>
                            CONFVAL_showExtractInfo = SCRIPTVAL_UnderCourseList
                            (actually : '.CONFVAL_showExtractInfo.')
                        </li>
                    </ul>
                    or
                    <ul>
                        <li>
                            $orderKey[0] != \'keyCourse\'
                            (actually : '.$orderKey[0].')
                        </li>
                    </ul>');
    } else {
        $orderKey = array('keyCourse', 'keyTools', 'keyTime');
    }
}*/


/*
    Header
    Include the HTTP, HTML headers plus the top banner.
*/

if ($load_dirs) {
	$url 			= api_get_path(WEB_AJAX_PATH).'document.ajax.php?a=document_preview';
	$folder_icon 	= api_get_path(WEB_IMG_PATH).'icons/22/folder.png';
	$close_icon 	= api_get_path(WEB_IMG_PATH).'loading1.gif';
	
	$htmlHeadXtra[] =  '<script type="text/javascript">
	
	$(document).ready( function() {
		
		$(".document_preview_container").hide();
		
		$(".document_preview").click(function() {
			var my_id = this.id;
			var course_id  = my_id.split("_")[2];
			var session_id = my_id.split("_")[3];
			
			//showing div
			$(".document_preview_container").hide();
					
			$("#document_result_" +course_id+"_" + session_id).show();	
			
			//Loading		
			var image = $("img", this);
			image.attr("src", "'.$close_icon.'");		
					
			$.ajax({
				url: "'.$url.'",
				data: "course_id="+course_id+"&session_id="+session_id,
	            success: function(return_value) {
	            	image.attr("src", "'.$folder_icon.'");
	            	$("#document_result_" +course_id+"_" + session_id).html(return_value);
	            	
	            }
	        });
	        
		});
	});
	</script>';
}

/* Sniffing system */
//by Juan Carlos Raña Trabado
?>

<script LANGUAGE="JavaScript">
var nav ="";
var screen_size_w;
var screen_size_h;
var java="";
var type_mimetypes="";
var suffixes_mimetypes="";
var list_plugins="";
var check_some_activex="";
var check_some_plugins="";
var java_sun_ver="";

<!-- check Microsoft Internet Explorer -->
if (navigator.userAgent.indexOf("MSIE") != -1) { var nav="ie";}

<!-- check Screen Size -->
screen_size_w=screen.width;
screen_size_h=screen.height;

<!-- list mimetypes types, suffixes and plugins (no for IE) -->
if (nav!="ie"){
        
        if (navigator.mimeTypes && navigator.mimeTypes.length > 0) {
        
                for (i=0; i < navigator.mimeTypes.length; i++) {
                        type_mimetypes=type_mimetypes+" "+navigator.mimeTypes[i].type;
                        suffixes_mimetypes=suffixes_mimetypes+" "+navigator.mimeTypes[i].suffixes;
                        if (navigator.mimeTypes[i].enabledPlugin!=null) {
                                list_plugins=list_plugins+" "+navigator.mimeTypes[i].enabledPlugin.name;
                        }               
                }
        }
}
<!-- check some activex for IE -->
if (nav=="ie"){
        //TODO:check wmediaplayer are too aggressive. Then we can assume that if there Windows, there Wmediaplayer?
        
        var check_some_activex = 
        DetectActiveXObject("ShockwaveFlash.ShockwaveFlash.1", "flash_yes")+
        DetectActiveXObject("QuickTime.QTElementBehavior", "quicktime_yes")+
        //DetectActiveXObject("MediaPlayer.MediaPlayer.1","wmediaplayer_yes")+
        DetectActiveXObject("acroPDF.PDF.1","acrobatreader_yes");
        
        function DetectActiveXObject(ObjectName, name) { 
                result = false;
                        document.write('<SCRIPT LANGUAGE=VBScript\> \n');
                        document.write('on error resume next \n');
                        document.write('result = IsObject(CreateObject("' + ObjectName + '")) \n');
                        document.write('</SCRIPT\> \n');
                if (result) return name+' , '; else return '';
        }
}
<!-- check some plugins for not IE -->
if (nav!="ie"){

        if (list_plugins.indexOf("Shockwave Flash")!=-1){
                check_some_plugins=check_some_plugins+', flash_yes';
        }
        if (list_plugins.indexOf("QuickTime")!=-1){
                check_some_plugins=check_some_plugins+', quicktime_yes';
        }
        if (list_plugins.indexOf("Windows Media Player")!=-1){
                check_some_plugins=check_some_plugins+', wmediaplayer_yes';
        }
        if (list_plugins.indexOf("Adobe Acrobat")!=-1){
                check_some_plugins=check_some_plugins+',acrobatreader_yes';
        }
}
<!-- java -->
if(navigator.javaEnabled()==true){java="java_yes";}else{java="java_no";}

<!-- check java Sun ver -->
//for not IE
if (nav!="ie"){
        if (navigator.mimeTypes["application/x-java-applet"]){ java_sun_ver="javasun_yes";}
        if (navigator.mimeTypes["application/x-java-applet;jpi-version=1.6.0_24"]){ java_sun_ver=java_sun_ver+" , javasun_ver_1.6_24_yes"; }//This java version 1.6.0_24 is problematic, the user should be updated

}
//for IE
if (nav=="ie"){
        //1.5->end nov 2009
        //TODO:extract minor version
        var java_sun_ver =
        DetectActiveXObject("JavaWebStart.isInstalled","javasun_yes")+
        DetectActiveXObject("JavaWebStart.isInstalled.1.4.2.0","javasun_ver_1.4_yes")+
        DetectActiveXObject("JavaWebStart.isInstalled.1.5.0.0","javasun_ver_1.5_yes")+
        DetectActiveXObject("JavaWebStart.isInstalled.1.6.0.0","javasun_ver_1.6_yes")+
        DetectActiveXObject("JavaWebStart.isInstalled.1.7.0.0","javasun_ver_1.7_yes");
        
        function DetectActiveXObject(ObjectName, name) { 
                result = false;
                        document.write('<SCRIPT LANGUAGE=VBScript\> \n');
                        document.write('on error resume next \n');
                        document.write('result = IsObject(CreateObject("' + ObjectName + '")) \n');
                        document.write('</SCRIPT\> \n');
                if (result) return name+' , '; else return '';
        }
}

<!-- Send to server -->
function sendSniff(){
        document.forms.sniff_nav_form.sniff_navigator.value="checked";
        document.forms.sniff_nav_form.sniff_navigator_screen_size_w.value=screen_size_w;
        document.forms.sniff_nav_form.sniff_navigator_screen_size_h.value=screen_size_h;
        document.forms.sniff_nav_form.sniff_navigator_type_mimetypes.value=type_mimetypes;
        document.forms.sniff_nav_form.sniff_navigator_suffixes_mimetypes.value=suffixes_mimetypes;
        document.forms.sniff_nav_form.sniff_navigator_list_plugins.value=list_plugins;
        document.forms.sniff_nav_form.sniff_navigator_check_some_activex.value=check_some_activex;
        document.forms.sniff_nav_form.sniff_navigator_check_some_plugins.value=check_some_plugins;
        document.forms.sniff_nav_form.sniff_navigator_java.value=java;
        document.forms.sniff_nav_form.sniff_navigator_java_sun_ver.value=java_sun_ver;
        document.sniff_nav_form.submit(); 
} 

</script>
<form name="sniff_nav_form" method="POST">
<input type="hidden" name="sniff_navigator">
<input type="hidden" name="sniff_navigator_screen_size_w">
<input type="hidden" name="sniff_navigator_screen_size_h">
<input type="hidden" name="sniff_navigator_type_mimetypes">
<input type="hidden" name="sniff_navigator_suffixes_mimetypes">
<input type="hidden" name="sniff_navigator_list_plugins">
<input type="hidden" name="sniff_navigator_check_some_activex">
<input type="hidden" name="sniff_navigator_check_some_plugins">
<input type="hidden" name="sniff_navigator_java">
<input type="hidden" name="sniff_navigator_java_sun_ver">
</form>

<?php
if (empty($_POST['sniff_navigator'])){
        echo '<script>';
        echo 'sendSniff();';
        echo '</script>';
}

//store posts to sessions
$_SESSION['sniff_screen_size_w']=Security::remove_XSS($_POST['sniff_navigator_screen_size_w']);
$_SESSION['sniff__screen_size_h']=Security::remove_XSS($_POST['sniff_navigator_screen_size_h']);
$_SESSION['sniff_type_mimetypes']=Security::remove_XSS($_POST['sniff_navigator_type_mimetypes']);
$_SESSION['sniff_suffixes_mimetypes']=Security::remove_XSS($_POST['sniff_navigator_suffixes_mimetypes']);
$_SESSION['sniff_list_plugins']=Security::remove_XSS($_POST['sniff_navigator_list_plugins']);
$_SESSION['sniff_check_some_activex']=Security::remove_XSS($_POST['sniff_navigator_check_some_activex']);
$_SESSION['sniff_check_some_plugins']=Security::remove_XSS($_POST['sniff_navigator_check_some_plugins']);
$_SESSION['sniff_java']=Security::remove_XSS($_POST['sniff_navigator_java']);
$_SESSION['sniff_java_sun_ver']=Security::remove_XSS($_POST['sniff_navigator_java_sun_ver']);

//var_dump($_SESSION);

// end sniffing system

//check for flash and message
if (stripos("flash_yes", $_SESSION['sniff_check_some_activex'])===0 || stripos("flash_yes", $_SESSION['sniff_check_some_plugins'])===0){
        
        Display::display_warning_message(get_lang('NoFlash'),false);
}


/* MAIN CODE */

$controller = new IndexManager(get_lang('MyCourses'));

$tpl = $controller->tpl->get_template('layout/layout_2_col.tpl');


//if (!$$controllerl->tpl->isCached($tpl, api_get_user_id())) {

//@todo all this could be moved in the IndexManager

$courses_list 			= $controller->return_courses_main_plugin();
$personal_course_list 	= UserManager::get_personal_session_course_list(api_get_user_id());


// Main courses and session list
ob_start();
$controller->return_courses_and_sessions($personal_course_list);
$courses_and_sessions = ob_get_contents();
ob_get_clean();

//
$controller->tpl->assign('content', 					$courses_and_sessions);

$controller->tpl->assign('plugin_courses_block', 		$controller->return_courses_main_plugin());
$controller->tpl->assign('profile_block', 				$controller->return_profile_block());
$controller->tpl->assign('account_block',				$controller->return_account_block());
$controller->tpl->assign('navigation_course_links', 	$controller->return_navigation_course_links($menu_navigation));
$controller->tpl->assign('plugin_courses_right_block', 	$controller->return_plugin_courses_block());
$controller->tpl->assign('reservation_block', 			$controller->return_reservation_block());
$controller->tpl->assign('search_block', 				$controller->return_search_block());
$controller->tpl->assign('classes_block', 				$controller->return_classes_block());
/*} else {
}*/
$controller->tpl->display($tpl);

// Deleting the session_id.
api_session_unregister('session_id');

