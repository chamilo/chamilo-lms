<?php
/* For licensing terms, see /license.txt */

/**
 *	This file allows creating audio files from a text.
 *
 *	@package chamilo.document
 *
 * @author Juan Carlos Raña Trabado
 * @since 8/January/2011
 * TODO:clean all file
*/

/*	INIT SECTION */
$language_file = array('document');

require_once '../inc/global.inc.php';
$_SESSION['whereami'] = 'document/createaudio';
$this_section = SECTION_COURSES;

require_once api_get_path(LIBRARY_PATH).'groupmanager.lib.php';

$nameTools = get_lang('CreateAudio');

api_protect_course_script();
api_block_anonymous_users();
if (api_get_setting('enabled_text2audio') == 'false'){
	api_not_allowed(true);
}
if (!isset($_GET['dir'])){
	api_not_allowed(true);
}

//javascript jquery
$htmlHeadXtra[] = '<script src="../inc/lib/javascript/jquery-1.4.4.min.js" type="text/javascript"></script>';
//jquery textareaCounter
$htmlHeadXtra[] = '<script src="../inc/lib/javascript/textareacounter/jquery.textareaCounter.plugin.js" type="text/javascript"></script>';
//need jquery for hide menus
$htmlHeadXtra[] = '<script type="text/javascript" language="javascript">
function advanced_parameters() {
	if(document.getElementById(\'options\').style.display == \'none\') {
					document.getElementById(\'options\').style.display = \'block\';
					document.getElementById(\'plus_minus\').innerHTML=\'&nbsp;'.Display::return_icon('div_hide.gif',get_lang('Hide'),array('style'=>'vertical-align:middle')).'&nbsp;'.get_lang('AdvancedParameters').'\';
	} else {
					document.getElementById(\'options\').style.display = \'none\';
					document.getElementById(\'plus_minus\').innerHTML=\'&nbsp;'.Display::return_icon('div_show.gif',get_lang('Show'),array('style'=>'vertical-align:middle')).'&nbsp;'.get_lang('AdvancedParameters').'\';
	}
}
function setFocus(){
	$("#search_title").focus();
	}
	$(document).ready(function () {
	  setFocus();
	});

</script>';

$dir = isset($_GET['dir']) ? Security::remove_XSS($_GET['dir']) : Security::remove_XSS($_POST['dir']);
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

$interbreadcrumb[] = array ("url" => "./document.php?curdirpath=".urlencode($_GET['dir']).$req_gid, "name" => get_lang('Documents'));

if (!$is_allowed_in_course) {
	api_not_allowed(true);
}


if (!($is_allowed_to_edit || $_SESSION['group_member_with_upload_rights'] || is_my_shared_folder($_user['user_id'], Security::remove_XSS($_GET['dir']),api_get_session_id()))) {
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
		echo '<a href="document.php?curdirpath='.Security::remove_XSS($_GET['dir']).'">'.Display::return_icon('back.png',get_lang('BackTo').' '.get_lang('DocumentsOverview')).get_lang('BackTo').' '.get_lang('DocumentsOverview').'</a>';
echo '</div>';

?>
<!-- javascript and styles for textareaCounter-->
<script type="text/javascript">
var info;
$(document).ready(function(){
	var options = {
		'maxCharacterSize': 100,
		'originalStyle': 'originalTextareaInfo',
		'warningStyle' : 'warningTextareaInfo',
		'warningNumber': 10,
		'displayFormat' : '#input/#max'
	};
	$('#textarea').textareaCount(options, function(data){
		$('#textareaCallBack').html(result);				
	});
});
</script>		        
<style>
.overview {
	background: #FFEC9D;
	padding: 10px;
	width: 90%;
	border: 1px solid #CCCCCC;
}

.originalTextareaInfo {
	font-size: 12px;
	color: #000000;
	text-align: right;
}

.warningTextareaInfo {
	color: #FF0000;
	font-weight:bold;
	text-align: right;	
}

#showData {
	height: 70px;
	width: 200px;
	border: 1px solid #CCCCCC;
	padding: 10px;
	margin: 10px;
}
</style>

<?php
echo '<div align="center">'; 

	Display::display_icon('sound.gif', get_lang('CreateAudio')); echo get_lang('HelpText2Audio'); 
	
	//Google services
	echo '<div>&nbsp;</div><input type="checkbox" value="1" name="checktask" onclick="javascript: if(this.checked){document.getElementById(\'option1\').style.display=\'block\';}else{document.getElementById(\'option1\').style.display=\'none\';}"/>&nbsp;<img src="../img/file_sound.gif" title="'.get_lang('HelpGoogleAudio').'" alt="'.get_lang('GoogleAudio').'"/>&nbsp;'.get_lang('GoogleAudio').'';
	echo '&nbsp;&nbsp;&nbsp;<span id="msg_error1" style="display:none;color:red"></span>';
	echo '<div id="option1" style="padding:4px; margin:5px; border:1px dotted; display:none;">';
	echo '<form id="form1" name="form1" method="post" action="'.downloadMP3($filepath, $dir).'" class="formw">';
		echo '<br/>';
		echo '<label>'.get_lang('Language').': ';
		$tbl_admin_languages 	= Database :: get_main_table(TABLE_MAIN_LANGUAGE);
		$sql_select = "SELECT * FROM $tbl_admin_languages";
		$result_select = Database::query($sql_select);		
		echo '<select name="lang" id="select">';
		while ($row = Database::fetch_array($result_select)) {			
			if (api_get_setting('platformLanguage')==$row['english_name']){
				echo '<option value="'.$row['isocode'].'" selected="selected">'.$row['original_name'].' ('.$row['english_name'].')</option>';
			}
			else{					
				echo '<option value="'.$row['isocode'].'">'.$row['original_name'].' ('.$row['english_name'].')</option>';
			}	
		}
		echo '</select>';
		echo '</label>';  
		echo '<br/><br/>';
		echo '<div>'.get_lang('InsertText2Audio').'</div>';
		echo '<br/>';
		echo '<label>';
		echo '<textarea name="text" id="textarea" cols="70" rows="2"></textarea>';
		echo '</label>';  
		echo '<br/>';
		echo '<label>'.get_lang('Title').': ';
		echo '<input name="title" type="text" size="40" maxlength="40" />';
		echo '</label>';  
		echo '<br/><br/>';  
		echo '<button class="save" type="submit" name="SendText2Audio">'.get_lang('SaveMP3').'</button>';
		echo '<br/>';
	echo '</form>';
	echo '</div>';
	
	//vozMe services
	
	echo '<div>&nbsp;</div><input type="checkbox" value="1" name="checktask" onclick="javascript: if(this.checked){document.getElementById(\'option2\').style.display=\'block\';}else{document.getElementById(\'option2\').style.display=\'none\';}"/>&nbsp;<img src="../img/file_sound.gif" title="'.get_lang('HelpvozMe').'" alt="'.get_lang('vozMe').'"/>&nbsp;'.get_lang('vozMe').'';
	echo '&nbsp;&nbsp;&nbsp;<span id="msg_error2" style="display:none;color:red"></span>';
	echo '<div id="option2" style="padding:4px; margin:5px; border:1px dotted; display:none;">';
	
	echo '<form id="form2" name="form2" method="post" action="http://vozme.com/text2voice.php" target="mymp3" class="formw">';
		echo '<br/>';
		echo '<label>'.get_lang('Language').': ';
		$tbl_admin_languages 	= Database :: get_main_table(TABLE_MAIN_LANGUAGE);
		$sql_select = "SELECT * FROM $tbl_admin_languages";
		$result_select = Database::query($sql_select);		
		echo '<select name="lang" id="select">';
		while ($row = Database::fetch_array($result_select)) {
			
			if (in_array($row['isocode'], array('ca', 'en', 'es', 'hi', 'it', 'pt'))){
			
				if (api_get_setting('platformLanguage')==$row['english_name']){
					echo '<option value="'.$row['isocode'].'" selected="selected">'.$row['original_name'].' ('.$row['english_name'].')</option>';
				}
				else{					
					echo '<option value="'.$row['isocode'].'">'.$row['original_name'].' ('.$row['english_name'].')</option>';
				}
			}
		}
		echo '</select>';
		echo '</label>';
		echo '<label>&nbsp;&nbsp;'.get_lang('Voice').': ';
		echo '<select name="gn" id="select1">';
		echo '<option value="ml">'.get_lang('Male').'</option>';
		echo '<option value="fm">'.get_lang('Female').'</option>';
		echo '</select>';
		echo '</label>';
		echo '<br/><br/>';
		echo '<div>'.get_lang('InsertText2Audio').'</div>';
		echo '<br/>';
		echo '<label>';
		echo '<textarea name="text" id="textarea" cols="70" rows="10"></textarea>';
		echo '</label>';  
		echo '<br/><br/>';  
		echo '<button class="save" type="submit" name="SendText2Audio">'.get_lang('BuildMP3').'</button>';
		echo '<br/>';
	echo '</form>';
	echo '</div>';

echo '</div>';

Display :: display_footer();

//Functions

/**
 * This function save a post into a file mp3
 *
 * @param $filepath
 * @param $dir
  * @author Juan Carlos Raña Trabado <herodoto@telefonica.net>
 * @version january 2011, chamilo 1.8.8
 */
function downloadMP3($filepath, $dir){
	//security
	if(!isset($_POST['lang']) && !isset($_POST['text']) && !isset($_POST['title']) && !isset($filepath) && !isset($dir)) {
		return;	
	}

	$clean_title=trim(Security::remove_XSS($_POST['title']));
	$clean_text=trim(Security::remove_XSS($_POST['text']));
	$clean_lang=Security::remove_XSS($_POST['lang']);

	if (strlen($clean_title) < "1") { 
		return;
	}
	
	$AudioFilename=$clean_title.'.mp3';	
	$documentPath = $filepath.$AudioFilename;
	
	//prev for a fine unicode, borrowed from main api TODO:clean
	// Safe replacements for some non-letter characters (whitout blank spaces)
	$search  = array("\0", "\t", "\n", "\r", "\x0B", '/', "\\", '"', "'", '?', '*', '>', '<', '|', ':', '$', '(', ')', '^', '[', ']', '#', '+', '&', '%');
	$replace = array('',  '_',  '_',  '_',  '_',    '-', '-',  '-', '_', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-');	
	$filename=$clean_text;
	// Encoding detection.
	$encoding = api_detect_encoding($filename);
	// Converting html-entities into encoded characters.
	$filename = api_html_entity_decode($filename, ENT_QUOTES, $encoding);
	// Transliteration to ASCII letters, they are not dangerous for filesystems.
	$filename = api_transliterate($filename, 'x', $encoding);
    // Replacing remaining dangerous non-letter characters.
    $clean_text = str_replace($search, $replace, $filename);
	
	//adding the file
	if (!file_exists($documentPath)){
		//add new file to disk
		file_put_contents($documentPath, file_get_contents("http://translate.google.com/translate_tts?tl=".$clean_lang."&q=".urlencode($clean_text).""));
		//add document to database
		$current_session_id = api_get_session_id();
		$groupId=$_SESSION['_gid'];
		$file_size = filesize($documentPath);
		$relativeUrlPath=$dir;
		$doc_id = add_document($_course, $relativeUrlPath.$AudioFilename, 'file', filesize($documentPath), $AudioFilename);
		api_item_property_update($_course, TOOL_DOCUMENT, $doc_id, 'DocumentAdded', $_user['user_id'], $groupId, null, null, null, $current_session_id);
	}	
}
?>