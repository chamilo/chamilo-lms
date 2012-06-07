<?php
/* For licensing terms, see /license.txt */

/**
 *	This file allows record wav files.
 *
 *	@package chamilo.document
 *
 * @author Juan Carlos Raña Trabado herodoto@telefonica.net
 * @since 7/jun/2012
*/
/**
 * Code
 */

/*	INIT SECTION */

// Name of the language file that needs to be included
$language_file = array('document');

require_once '../inc/global.inc.php';

$_SESSION['whereami'] = 'document/webcamclip';
$this_section = SECTION_COURSES;

require_once api_get_path(SYS_CODE_PATH).'document/document.inc.php';
require_once api_get_path(LIBRARY_PATH).'groupmanager.lib.php';

$nameTools = get_lang('Webcam');

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
$webcamdir=$dir;
if($webcamdir=="/"){
 $webcamdir="";
}


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

///////////////////////////////////////////////////////////
//make some vars
$webcamuserid=api_get_user_id();

Display :: display_header($nameTools, 'Doc');
echo '<div class="actions">';
		echo '<a href="document.php?id='.$document_id.'">'.Display::return_icon('back.png',get_lang('BackTo').' '.get_lang('DocumentsOverview'),'',ICON_SIZE_MEDIUM).'</a>';
echo '</div>';

$webcamname = date('YmdHis') . '.jpg';
?>

<div align="center">	
    <h1><?php echo get_lang('Webcam'); ?></h1>
    <h3><?php echo get_lang('TakeYourPhotos'); ?></h3>
</div>
<div align="center" style="padding-left:50px;">
<table><tr><td valign=top>
<!-- First, include the JPEGCam JavaScript Library -->
	<script type="text/javascript" src="<?php echo api_get_path(WEB_LIBRARY_PATH); ?>jpegcam/webcam.js"></script>
	
	<!-- Configure a few settings -->
	<script language="JavaScript">
		var clip_filename='video_clip.jpg';
		webcam.set_swf_url ( '<?php echo api_get_path(WEB_LIBRARY_PATH); ?>jpegcam//webcam.swf?blackboard.png' );
		webcam.set_shutter_sound( true,'<?php echo api_get_path(WEB_LIBRARY_PATH); ?>jpegcam/shutter.mp3' ); // true play shutter click sound
		webcam.set_quality( 90 ); // JPEG quality (1 - 100)
		webcam.set_api_url( '<?php echo api_get_path(WEB_LIBRARY_PATH); ?>jpegcam/webcam_receiver.php?webcamname='+escape(clip_filename)+'&webcamdir=<?php echo $webcamdir; ?>&webcamuserid=<?php echo $webcamuserid; ?>' );
		
	</script>
	
	<!-- Next, write the movie to the page at 320x240 -->
	<script language="JavaScript">
		document.write( webcam.get_html(320, 240) );
	</script>
	
	<!-- Some buttons for controlling things -->
	<br/><form>
    <br/>
   
       <?php echo get_lang('TakingOnePhoto'); ?>
		<input type=button value="<?php echo get_lang('Shutter'); ?>" onClick="webcam.freeze()">
		<input type=button value="<?php echo get_lang('Reset'); ?>" onClick="webcam.reset()">
        <input type=button value="<?php echo get_lang('UploadClip'); ?>" onClick="do_upload()">
        <br/>
         <?php echo get_lang('BurstMode'); ?>
		<input type=button value="<?php echo get_lang('Record'); ?>" onClick="start_video()">
		<input type=button value="<?php echo get_lang('Stop'); ?>" onClick="stop_video()">
        &nbsp;&nbsp;<?php echo get_lang('Configure'); ?>&nbsp;&nbsp;
        <input type=button value="<?php echo get_lang('Flash'); ?>" onClick="webcam.configure()">
	</form>


	<!-- Code to handle the server response (see webcam_receiver.php) -->
	<script language="JavaScript">
		webcam.set_hook( 'onComplete', 'my_completion_handler' );
		
		function do_upload() {
			// upload to server
			//document.getElementById('upload_results').innerHTML = '<h1>Uploading...</h1>';
			webcam.upload();
		}
		
		function my_completion_handler(msg) {
			// extract URL out of PHP output
			if (msg.match(/(http\:\/\/\S+)/)) {
				var image_url = RegExp.$1;
				
				image_url=image_url.replace(/\\/g,'/').replace( /.*\//, '' );// extract basename
				image_url='<?php echo api_get_path(WEB_COURSE_PATH).$_course['path'].'/document/';?>'+image_url+'<?php echo '?'.api_get_cidreq(); ?>';
			
				// show JPEG image in page
				document.getElementById('upload_results').innerHTML = 
				'<div style="width: 320px;">' +
					//'<h1>Upload Successful!</h1>' + 
					//'<h3>JPEG URL: ' + image_url + '</h3>' + 
					//'<h3>Clip sent</h3>' + 
					'<img src="' + image_url + '">' +
					'</div>';
				
				// reset camera for another shot
				webcam.reset();
			}
			else alert("PHP Error: " + msg);
		}
	</script>
	
     <script language=javascript>
	   var internaval=null;
	   
	   function stop_video() {
			interval=window.clearInterval(interval);
	   }
	   
	   function start_video() {
		   	webcam.set_stealth( true ); // do not freeze image upon capture
		 	interval=window.setInterval("clip_send_video()",1000);// each second
	   }
	   
	   function clip_send_video() {
		   webcam.snap();// clip and upload
	   }
 </script>
    
    
	</td><td width=50>&nbsp;</td><td valign=top>
		<div id="upload_results" style="background-color:#eee;"></div>
	</td></tr></table>
</div>

<?php

Display :: display_footer();