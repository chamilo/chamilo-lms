<?php
/* For licensing terms, see /license.txt */

/**
 *	This file allows creating new svg and png documents with an online editor.
 *
 *	@package chamilo.document
 *
 * @author Juan Carlos Raña Trabado herodoto@telefonica.net
 * @since 5/mar/2011
*/
/**
 * Code
 */

/*	INIT SECTION */

// Name of the language file that needs to be included
$language_file = array('document');

require_once '../inc/global.inc.php';

$_SESSION['whereami'] = 'document/voicerecord';
$this_section = SECTION_COURSES;

require_once api_get_path(SYS_CODE_PATH).'document/document.inc.php';
require_once api_get_path(LIBRARY_PATH).'groupmanager.lib.php';

$nameTools = get_lang('VoiceRecord');

api_protect_course_script();
api_block_anonymous_users();

$document_data = DocumentManager::get_document_data_by_id($_GET['id'], api_get_course_id());
if (empty($document_data)) {
    if (api_is_in_group()) {
        $group_properties   = GroupManager::get_group_properties(api_get_group_id());        
        $document_id        = DocumentManager::get_document_id(api_get_course_info(), $group_properties['directory']);
        $document_data      = DocumentManager::get_document_data_by_id($document_id, api_get_course_id());
    }
}

$document_id = $document_data['id'];
$dir = $document_data['path'];

$is_allowed_to_edit = api_is_allowed_to_edit(null, true);

// Please, do not modify this dirname formatting

if (strstr($dir, '..')) {
	$dir = '/';
}

if ($dir[0] == '.') {
	$dir = substr($dir, 1);
}

if ($dir[0] != '/') {
	$dir = '/'.$dir;
}

if ($dir[strlen($dir) - 1] != '/') {
	$dir .= '/';
}

$filepath = api_get_path(SYS_COURSE_PATH).$_course['path'].'/document'.$dir;

if (!is_dir($filepath)) {
	$filepath = api_get_path(SYS_COURSE_PATH).$_course['path'].'/document/';
	$dir = '/';
}

//groups //TODO: clean
if (isset ($_SESSION['_gid']) && $_SESSION['_gid'] != 0) {
		$req_gid = '&amp;gidReq='.$_SESSION['_gid'];
		$interbreadcrumb[] = array ("url" => "../group/group_space.php?gidReq=".$_SESSION['_gid'], "name" => get_lang('GroupSpace'));
		$noPHP_SELF = true;
		$to_group_id = $_SESSION['_gid'];
		$group = GroupManager :: get_group_properties($to_group_id);
		$path = explode('/', $dir);
		if ('/'.$path[1] != $group['directory']) {
			api_not_allowed(true);
		}
	}
	$interbreadcrumb[] = array ("url" => "./document.php?id=".$document_id.$req_gid, "name" => get_lang('Documents'));

if (!$is_allowed_in_course) {
	api_not_allowed(true);
}


if (!($is_allowed_to_edit || $_SESSION['group_member_with_upload_rights'] || is_my_shared_folder(api_get_user_id(), Security::remove_XSS($dir),api_get_session_id()))) {
	api_not_allowed(true);
}


/*	Header */
event_access_tool(TOOL_DOCUMENT);
$display_dir = $dir;
if (isset ($group)) {
	$display_dir = explode('/', $dir);
	unset ($display_dir[0]);
	unset ($display_dir[1]);
	$display_dir = implode('/', $display_dir);
}

// Interbreadcrumb for the current directory root path
	// Copied from document.php
	$dir_array = explode('/', $dir);
	$array_len = count($dir_array);
	
	/*
	TODO:check and delete this code
	if (!$is_certificate_mode) {
		if ($array_len > 1) {
			if (empty($_SESSION['_gid'])) {
				$url_dir = 'document.php?&curdirpath=/';
				$interbreadcrumb[] = array('url' => $url_dir, 'name' => get_lang('HomeDirectory'));
			}
		}
	}
	*/
	
	$dir_acum = '';
	for ($i = 0; $i < $array_len; $i++) {
		$url_dir = 'document.php?&curdirpath='.$dir_acum.$dir_array[$i];
		//Max char 80
		$url_to_who = cut($dir_array[$i],80);
		if ($is_certificate_mode) {
			$interbreadcrumb[] = array('url' => $url_dir.'&selectcat='.Security::remove_XSS($_GET['selectcat']), 'name' => $url_to_who);
		} else {
			$interbreadcrumb[] = array('url' => $url_dir, 'name' => $url_to_who);
		}
		$dir_acum .= $dir_array[$i].'/';
	}
//
Display :: display_header($nameTools, 'Doc');

echo '<div class="actions">';
		echo '<a href="document.php?id='.$document_id.'">'.Display::return_icon('back.png',get_lang('BackTo').' '.get_lang('DocumentsOverview'),'','32').'</a>';
echo '</div>';

echo '<br/>';

?>
<script language="JavaScript"> 
function submitVoice() { 
	//lang vars
	var lang_no_applet="<?php echo get_lang('NanogongNoApplet'); ?>";
	var lang_record_before_save="<?php echo get_lang('NanogongRecordBeforeSave'); ?>";
	var lang_give_a_title="<?php echo get_lang('NanogongGiveTitle'); ?>";
	var lang_failled_to_submit="<?php echo get_lang('NanogongFailledToSubmit'); ?>";
	var lang_submitted="<?php echo get_lang('NanogongSubmitted'); ?>";
	
	//path, url and filename
	var filename = document.getElementById("audio_title").value+".wav";	
	var filename = filename.replace(/\s/g, "_");//replace spaces by _
	var filename = encodeURIComponent(filename);//TODO:implement a good encode into receiver.php	
	var filepath="<?php echo urlencode($filepath); ?>";
	var dir="<?php echo urlencode($dir); ?>";	
	var urlnanogong="../inc/lib/nanogong/receiver.php?filename="+filename+"&filepath="+filepath+"&dir="+dir;	
	//check	
	var recorder
	if (!(recorder = document.getElementById("nanogong")) || !(recorder.sendGongRequest)) {
    	alert(lang_no_applet)
	  	return
	}
	var duration = parseInt(recorder.sendGongRequest("GetMediaDuration", "audio")) || 0
	if (duration <= 0) {
	  	alert(lang_record_before_save)
	  	return
	}	
	if (!document.getElementById("audio_title").value) {
		alert(lang_give_a_title)
		return
	}
	//	
	var applet = document.getElementById("nanogong");	
	var ret = applet.sendGongRequest( "PostToForm", urlnanogong, "voicefile", "", "temp");//'PostToForm', postURL, inputname, cookie, filename
	if (ret == null) alert(lang_failled_to_submit); else alert(lang_submitted+"\n"+ret);
}
</script>

<?php
echo '<div align="center">';

Display::display_icon('microphone.png', get_lang('PressRecordButton'),'','128');
echo '<br/>';
echo '<applet id="nanogong" archive="'.api_get_path(WEB_LIBRARY_PATH).'nanogong/nanogong.jar" code="gong.NanoGong" width="250" height="40" ALIGN="middle">';
	//echo '<param name="ShowRecordButton" value="false" />'; // default true
	// echo '<param name="ShowSaveButton" value="false" />'; //you can save in local computer | (default true)
	//echo '<param name="ShowSpeedButton" value="false" />'; // default true
	//echo '<param name="ShowAudioLevel" value="false" />'; //  it displays the audiometer | (default true)
	echo '<param name="ShowTime" value="true" />'; // default false
	//echo '<param name="Color" value="#C0E0FF" />'; // default #FFFFFF
	//echo '<param name="StartTime" value="10.5" />';
	//echo '<param name="EndTime" value="65" />';	
	echo '<param name="AudioFormat" value="ImaADPCM" />';// ImaADPCM (more speed), Speex (more compression)|(default Speex)
	//echo '<param name="SamplingRate" value="32000" />';//Quality for ImaADPCM (low 8000, medium 11025, normal 22050, hight 44100) OR Quality for Speex (low 8000, medium 16000, normal 32000, hight 44100) | (default 44100)
	//echo '<param name="MaxDuration" value="60" />';
	//echo '<param name="SoundFileURL" value="http://somewhere.com/mysoundfile.wav" />';//load a file |(default "")
	
echo '</applet>';
//check browser support and load form
$array_browser=api_browser_support('check_browser');

if ($array_browser[0]=="Internet Explorer") {	
	echo '<div style="width:250px; background-color:#FFEFA7">'.get_lang('BrowserNotSupportNanogongSend').'</div>';	
} else {   
	echo '<form name="form_nanogong">';	
		echo '<input type="text" id="audio_title">';
		echo '<button class="upload" type="submit" value="'.get_lang('Send').'" onClick="submitVoice()" />'.get_lang('Send').'</button>';
	echo '</form>';
}
echo '</div>';
Display :: display_footer();
