<?php
$langFile = "document";
include('../../../../../../inc/global.inc.php');
include_once(api_get_path(INCLUDE_PATH)."lib/fileUpload.lib.php");
include(api_get_path(SYS_CODE_PATH).'document/document.inc.php');
include_once(api_get_path(LIBRARY_PATH) . 'document.lib.php');
include_once(api_get_path(LIBRARY_PATH) . 'fileDisplay.lib.php');
//echo ini_get("upload_max_filesize");
//echo api_get_path(SYS_PATH)."courses/".$_SESSION['_course']['path'].'/document/';

$courseDir   = $_course['path']."/document";
$sys_course_path = api_get_path(SYS_COURSE_PATH);
$base_work_dir = $sys_course_path.$courseDir;

$is_allowed_to_edit = api_is_allowed_to_edit();

//this needs cleaning!
if(isset($_SESSION['_gid']) && $_SESSION['_gid']!='') //if the group id is set, check if the user has the right to be here
{
	//needed for group related stuff
	include_once(api_get_path(LIBRARY_PATH) . 'groupmanager.lib.php');
	//get group info
	$group_properties = GroupManager::get_group_properties($_SESSION['_gid']);
	$noPHP_SELF=true;
		
	if($is_allowed_to_edit || GroupManager::is_user_in_group($_uid,$_SESSION['_gid'])) //only courseadmin or group members allowed
	{
		$to_group_id = $_SESSION['_gid'];
		$req_gid = '&amp;gidReq='.$_SESSION['_gid'];
		$interbreadcrumb[]= array ("url"=>"../group/group_space.php?gidReq=".$_SESSION['_gid'], "name"=> get_lang('GroupSpace'));
	}
	else
	{
		api_not_allowed();
	}
}
elseif($is_allowed_to_edit) //admin for "regular" upload, no group documents
{
	$to_group_id = 0;
	$req_gid = '';
}
else  //no course admin and no group member...
{
	api_not_allowed();
}

//what's the current path?
if(isset($_GET['path']) && $_GET['path']!='')
{
	$path = $_GET['path'];
}
elseif (isset($_POST['curdirpath']))
{
	$path = $_POST['curdirpath'];
}
else 
{
	$path = '/';
}

$max_filled_space = DocumentManager::get_course_quota();

?>
		<script src="../../dialog/common/fck_dialog_common.js" type="text/javascript"></script>
		<script src="fck_Attachment.js" type="text/javascript"></script>
		<link href="../../dialog/common/fck_dialog_common.css" type="text/css" rel="stylesheet">

<?php
if($_POST['fileupload']=="Attach File"){

	$upload_ok = process_uploaded_file($_FILES['uploadedfile']);
	if($upload_ok)
	{
		//file got on the server without problems, now process it
		$new_path = handle_uploaded_document($_course, $_FILES['uploadedfile'],$base_work_dir,$path,$_uid,$to_group_id,$to_user_id,$max_filled_space,0,'overwrite');

    	$new_comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';
    	$new_title = isset($_POST['title']) ? trim($_POST['title']) : '';
		
    	if ($new_path && ($new_comment || $new_title))
    	if (($docid = DocumentManager::get_document_id($_course, $new_path)))
    	{
        	$table_document = Database::get_course_table(DOCUMENT_TABLE);
        	$ct = '';
        	if ($new_comment) $ct .= ", comment='$new_comment'";
        	if ($new_title)   $ct .= ", title='$new_title'";
        	api_sql_query("UPDATE $table_document SET" . substr($ct, 1) . 
        	    " WHERE id = '$docid'", __FILE__, __LINE__);
    	}
		//check for missing images in html files
		$missing_files = check_for_missing_files($base_work_dir.$path.$new_path);
		if($missing_files)
		{
			//show a form to upload the missing files
			Display::display_normal_message(build_missing_files_form($missing_files,$path,$_FILES['user_upload']['name']));
		}

		$imgIcon = build_document_icon_tag('file',$base_work_dir.$path.$new_path);

		$currentCourseRepositoryWeb =  api_get_path(WEB_COURSE_PATH) . $_course["path"]."/";
		$target_url = $currentCourseRepositoryWeb.'document'.$new_path;
		$basefilename = basename( $new_path );

?>
		<SCRIPT LANGUAGE="JavaScript">
			//var content = FCK.EditorDocument.body.innerHTML;
			var content = FCK.GetXHTML( FCKConfig.FormatOutput );
			//alert(content);
			FCK.SetHTML(content+'<br><?php echo $imgIcon; ?>&nbsp;<a href="<?php echo $target_url; ?>"><?php echo $basefilename; ?></a>');
			window.parent.close();
		</script>
<?
	} else{
		display_form();
		exit;
	}
}
?>
<?php
display_form();

function display_form(){
?>
<html>
	<head>
		<title>Attachment</title>
	</head>
	<body scroll="no">
<?php
require("../loader.class.php");
$loader = new Loader('formUpload');
$loader->init();
?>
		<form name="formUpload" enctype="multipart/form-data" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST"><!--onSubmit="progress_bar();"-->
<input type="hidden" name="sent" value="1">
		<input type="hidden" name="MAX_FILE_SIZE" value="10000000" />
		<div class="title"><span>File Attachment</span></div>
		<input name="uploadedfile" id="uploadedfile" type="file" size="40"/><br />
		<div id='eUploadMessage'>(max 10MB)</div><br />
		<input type="submit" value="Attach File" id='fileupload' name='fileupload'/>
		</form>
<?php
$loader->close();
?>		 
  	</body>
</html>
<?php
}
?>