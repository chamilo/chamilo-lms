<?php
/* For licensing terms, see /license.txt */

/**
 *	This file allows record wav files.
 *
 *	@package chamilo.document
 *
 * @author Juan Carlos Raña Trabado herodoto@telefonica.net
 * @since 5/april/2012
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

$document_data = DocumentManager::get_document_data_by_id($_GET['id'], api_get_course_id(), true);
if (empty($document_data)) {
    if (api_is_in_group()) {
        $group_properties   = GroupManager::get_group_properties(api_get_group_id());        
        $document_id        = DocumentManager::get_document_id(api_get_course_info(), $group_properties['directory']);
        $document_data      = DocumentManager::get_document_data_by_id($document_id, api_get_course_id());
    }
}

$document_id = $document_data['id'];
$dir = $document_data['path'];

//make some vars
$wamidir=$dir;
if($wamidir=="/"){
 $wamidir="";
}
$wamiurlplay=api_get_path(WEB_COURSE_PATH).api_get_course_path().'/document'.$wamidir."/";


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
$counter = 0;   
if (isset($document_data['parents'])) {
    foreach($document_data['parents'] as $document_sub_data) {
        //fixing double group folder in breadcrumb
        if (api_get_group_id()) {
            if ($counter == 0) {
                $counter++;
                continue;  
            }
        }
        $interbreadcrumb[] = array('url' => $document_sub_data['document_url'], 'name' => $document_sub_data['title']);
        $counter++;
    }
}


//make some vars
$wamiuserid=api_get_user_id();

Display :: display_header($nameTools, 'Doc');
echo '<div class="actions">';
		echo '<a href="document.php?id='.$document_id.'">'.Display::return_icon('back.png',get_lang('BackTo').' '.get_lang('DocumentsOverview'),'',ICON_SIZE_MEDIUM).'</a>';
echo '</div>';


?>
<!-- swfobject is a commonly used library to embed Flash content https://ajax.googleapis.com/ajax/libs/swfobject/2.2/ -->
<script type="text/javascript" src="<?php echo api_get_path(WEB_LIBRARY_PATH) ?>swfobject/swfobject.js"></script>

<!-- Setup the recorder interface -->
<script type="text/javascript" src="<?php echo api_get_path(WEB_LIBRARY_PATH) ?>wami-recorder/recorder.js"></script>

<!-- GUI code... take it or leave it -->
<script type="text/javascript" src="<?php echo api_get_path(WEB_LIBRARY_PATH) ?>wami-recorder/gui.js"></script>

<script type="text/javascript">


	function setupRecorder() {
		var nospaces =document.getElementById("audio_title").value;
		var audioname = nospaces.replace(/ /gi, "");
		if(audioname==""){
			 return
		 }else{
			document.getElementById('audio_title').style.display='none';
			document.getElementById('audio_button').style.display='none';
			 
			Wami.setup({
				id : "wami",
				onReady : setupGUI
			});
		}
	}

	function setupGUI() {
		var waminame = document.getElementById("audio_title").value+".wav";//adding name file and extension
	    var waminame_play=waminame;
			
		var gui = new Wami.GUI({
			id : "wami",
			singleButton : true,
			recordUrl : "<?php echo api_get_path(WEB_LIBRARY_PATH) ?>wami-recorder/record_document.php?waminame="+waminame+"&wamidir=<?php echo $wamidir; ?>&wamiuserid=<?php echo $wamiuserid; ?>",
			playUrl : 	"<?php echo $wamiurlplay; ?>"+waminame_play,
			buttonUrl : "<?php echo api_get_path(WEB_LIBRARY_PATH) ?>wami-recorder/buttons.png",
			swfUrl : 	"<?php echo api_get_path(WEB_LIBRARY_PATH) ?>wami-recorder/Wami.swf"
		});
	
		gui.setPlayEnabled(false);

	}
	

</script>


<div id="wami" style="margin-left: 510px; margin-top:10px;"></div>

<div align="center" style="margin-top:140px;">
<form name="form_wami_recorder">
<input placeholder="<?php echo get_lang('Name'); ?>" type="text" id="audio_title">
<button type="button" value="" onClick="setupRecorder()" id="audio_button" /><?php echo get_lang('Activate'); ?></button>
</form>
</div>
<div align="center" id="audio_message_1" style="display:inline">
<?php
Display::display_normal_message(get_lang('WamiNeedFilename').' '.get_lang('WamiFlashDialog'), false);
?>
</div>
<div align="center" id="audio_message_2" style="display:inline;">
<?php
Display::display_normal_message(get_lang('WamiStartRecorder'), false);
?>
</div>

<?php

Display :: display_footer();