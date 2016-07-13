<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 *	This file allows record wav files.
 *
 *	@package chamilo.document
 *
 * @author Juan Carlos Raña Trabado herodoto@telefonica.net
 * @since 7/jun/2012
 * @Updated 04/09/2015 Upgrade to WebCamJS
*/
require_once '../inc/global.inc.php';

$_SESSION['whereami'] = 'document/webcamclip';
$this_section = SECTION_COURSES;
$nameTools = get_lang('WebCamClip');
$groupRights = Session::read('group_member_with_upload_rights');

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

$groupId = api_get_group_id();

if (!empty($groupId)) {
	$interbreadcrumb[] = array ("url" => "../group/group_space.php?".api_get_cidreq(), "name" => get_lang('GroupSpace'));
	$noPHP_SELF = true;
	$group = GroupManager :: get_group_properties($groupId);
	$path = explode('/', $dir);
	if ('/'.$path[1] != $group['directory']) {
		api_not_allowed(true);
	}
}

$interbreadcrumb[] = array ("url" => "./document.php?id=".$document_id."&".api_get_cidreq(), "name" => get_lang('Documents'));

if (!$is_allowed_in_course) {
	api_not_allowed(true);
}

if (!($is_allowed_to_edit || $groupRights ||
	DocumentManager::is_my_shared_folder(api_get_user_id(), Security::remove_XSS($dir),api_get_session_id()))) {
	api_not_allowed(true);
}

/*	Header */
Event::event_access_tool(TOOL_DOCUMENT);

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
$webcamuserid=api_get_user_id();

Display :: display_header($nameTools, 'Doc');
echo '<div class="actions">';
echo '<a href="document.php?id='.$document_id.'">'.
	Display::return_icon('back.png',get_lang('BackTo').' '.get_lang('DocumentsOverview'),'',ICON_SIZE_MEDIUM).'</a>';
echo '</div>';
?>

<div align="center">
    <h2><?php echo get_lang('TakeYourPhotos'); ?></h2>
</div>
<div align="center">
<table><tr><td valign='top' align='center'>
<h3 align='center'><?php echo get_lang('LocalInputImage'); ?></h3>
    <!-- Including New Lib WebCamJS upgrading from JPEGCam -->
    <script type="text/javascript" src="<?php echo api_get_path(WEB_PATH); ?>web/assets/webcamjs/webcam.js"></script>
    <!-- Adding a div container for the new live camera with some cool style options-->
    <div class="webcamclip_bg">
        <div id="chamilo-camera"></div>
    </div>
	<!-- Configure a few settings and attach the camera to the div container -->
	<script language="JavaScript">
		Webcam.set({
            width: 320,
            height: 240,
            image_format: 'jpeg',
            jpeg_quality: 90
        });
        Webcam.attach( '#chamilo-camera' );
        //This line Fix a minor bug that made a conflict with a videochat feature in another module file
        $('video').addClass('skip');
	</script>

	<!-- Using now Jquery to do the webcamJS Functions and handle the server response (see webcam_receiver.php now in webcamJS directory) -->
	<script language="JavaScript">
		$(document).ready(function() {
           $('#btnCapture').click(function() {
               Webcam.freeze();
               return false;
           });

           $('#btnClean').click(function() {
               Webcam.unfreeze();
               return false;
           });

           $('#btnSave').click(function() {
               snap();
               return false;
           });

           $('#btnAuto').click(function() {
               start_video();
               return false;
           });

           $('#btnStop').click(function() {
               stop_video();
               return false;
           });
        });

        function snap() {
            Webcam.snap( function(data_uri) {
                  var clip_filename='video_clip.jpg';
                  var url = 'webcam_receiver.php?webcamname='+escape(clip_filename)+'&webcamdir=<?php echo $webcamdir; ?>&webcamuserid=<?php echo $webcamuserid; ?>';
                  Webcam.upload(data_uri, url, function(code, response){
                      $('#upload_results').html(
                          '<h3>'+response+'</h3>' +
                          '<div>'+
                          '<img src="' + data_uri + '" class="webcamclip_bg">' +
                          '</div>'+
                          '</div>'+
                          '<p hidden="true">'+code+'</p>'
                      );
                  });
              });
        }
	</script>

     <script language=javascript>
	   var interval=null;
	   var timeout=null;
	   var counter=0;
	   var fps=1000;//one frame per second
	   var maxclip=25;//maximum number of clips
	   var maxtime=60000;//stop after one minute

	   function stop_video() {
			interval=window.clearInterval(interval);
            return false;
	   }

	   function start_video() {
		 	interval=window.setInterval("clip_send_video()",fps);
	   }

	   function clip_send_video() {
		   counter++;
		   timeout=setTimeout('stop_video()',maxtime);
		   if(maxclip>=counter){
		       snap();// clip and upload
		   }
		   else {
			   interval=window.clearInterval(interval);
		   }
	   }
 </script>


	</td><td width=50></td><td valign='top' align='center'>
		<div id="upload_results" style="background-color:#ffffff;"></div>
	</td></tr></table>

    <!-- Implementing Button html5 Tags instead Inputs and some cool bootstrap button styles -->
    <div>
        <br/>
            <form>
                <br/>
                <button id="btnCapture" class="btn btn-danger">
                <em class="fa fa-camera"></em>
                <?php echo get_lang('Snapshot'); ?>
                </button>
                <button id="btnClean" class="btn btn-success">
                <em class="fa fa-refresh"></em>
                <?php echo get_lang('Clean'); ?>
                </button>
                <button id="btnSave" class="btn btn-primary">
                <em class="fa fa-save"></em>
                <?php echo get_lang('Save'); ?>
                </button>
                &nbsp;&nbsp;||&nbsp;&nbsp;
                <button id="btnAuto" class="btn btn-default">
                <?php echo get_lang('Auto'); ?>
                </button>
                <button id="btnStop" class="btn btn-default">
                <?php echo get_lang('Stop'); ?>
                </button>
                <br/>
            </form>
        <br/>
   </div>
</div>

<?php

Display :: display_footer();
