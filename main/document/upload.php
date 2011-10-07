<?php
/* For licensing terms, see /license.txt */

/**
 * Main script for the documents tool
 *
 * This script allows the user to manage files and directories on a remote http server.
 *
 * The user can : - navigate through files and directories.
 *				 - upload a file
 *				 - delete, copy a file or a directory
 *				 - edit properties & content (name, comments, html content)
 *
 * The script is organised in four sections.
 *
 * 1) Execute the command called by the user
 *				Note: somme commands of this section are organised in two steps.
 *				The script always begins with the second step,
 *				so it allows to return more easily to the first step.
 *
 *				Note (March 2004) some editing functions (renaming, commenting)
 *				are moved to a separate page, edit_document.php. This is also
 *				where xml and other stuff should be added.
 *
 * 2) Define the directory to display
 *
 * 3) Read files and directories from the directory defined in part 2
 * 4) Display all of that on an HTML page
 *
 * @todo eliminate code duplication between
 * document/document.php, scormdocument.php
 *
 * @package chamilo.document
 */

// Name of the language file that needs to be included
$language_file = array('document','gradebook');

// Including the global initialization file
require_once '../inc/global.inc.php';

// Including additional libraries
require_once api_get_path(LIBRARY_PATH).'document.lib.php';
require_once api_get_path(LIBRARY_PATH).'specific_fields_manager.lib.php';
require_once api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php';
require_once 'document.inc.php';

// Adding extra javascript to the form
$htmlHeadXtra[] = api_get_jquery_libraries_js(array('jquery-ui', 'jquery-upload'));
$htmlHeadXtra[] = '<script type="text/javascript">

function check_unzip() {
	if(document.upload.unzip.checked){
		document.upload.if_exists[0].disabled=true;
		document.upload.if_exists[1].checked=true;
		document.upload.if_exists[2].disabled=true;
	} else {
		document.upload.if_exists[0].checked=true;
		document.upload.if_exists[0].disabled=false;
		document.upload.if_exists[2].disabled=false;
		}
	}

function advanced_parameters() {
	if(document.getElementById(\'options\').style.display == \'none\') {
	document.getElementById(\'options\').style.display = \'block\';
	document.getElementById(\'img_plus_and_minus\').innerHTML=\'&nbsp;<img style="vertical-align:middle;" src="../img/div_hide.gif" alt="" />&nbsp;'.get_lang('AdvancedParameters').'\';
	} else {
			document.getElementById(\'options\').style.display = \'none\';
			document.getElementById(\'img_plus_and_minus\').innerHTML=\'&nbsp;<img style="vertical-align:middle;" src="../img/div_show.gif" alt="" />&nbsp;'.get_lang('AdvancedParameters').'\';
			}
	}
function setFocus(){
	$("#title_file").focus();
	}
	$(document).ready(function () {
 	 setFocus();
	});	
</script>';

$htmlHeadXtra[] = "
<script type=\"text/javascript\">
$(function () {
    $('#file_upload').fileUploadUI({
        uploadTable:   $('.files'),
        downloadTable: $('.files'),
        buildUploadRow: function (files, index) {
            return $('<tr><td>' + files[index].name + '<\/td>' +
                    '<td class=\"file_upload_progress\"><div><\/div><\/td>' +
                    '<td class=\"file_upload_cancel\">' +
                    '<button class=\"ui-state-default ui-corner-all\" title=\"".get_lang('Cancel')."\">' +
                    '<span class=\"ui-icon ui-icon-cancel\">".get_lang('Cancel')."<\/span>' +
                    '<\/button><\/td><\/tr>');
        },
        buildDownloadRow: function (file) {
            return $('<tr><td>' + file.name + '<\/td> <td>' + file.size + '<\/td>  <td>' + file.result + '<\/td> <\/tr>');
        }
    });    
    $('#tabs').tabs();
});
</script>";

// Variables

$is_allowed_to_edit = api_is_allowed_to_edit(null, true);

$courseDir = $_course['path'].'/document';
$sys_course_path = api_get_path(SYS_COURSE_PATH);
$base_work_dir = $sys_course_path.$courseDir;
$noPHP_SELF = true;

/*
// What's the current path?
if (isset($_GET['curdirpath']) && $_GET['curdirpath'] != '') {
	$path = $_GET['curdirpath'];
} elseif (isset($_POST['curdirpath'])) {
	$path = $_POST['curdirpath'];
} else {
	$path = '/';
}*/

$document_data  = DocumentManager::get_document_data_by_id($_REQUEST['id'], api_get_course_id());
if (empty($document_data)) {
    $document_id  = $parent_id =  0;
    $path = '/';    
} else {
    $document_id    = $document_data['id'];
    $path           = $document_data['path'];
    $parent_id      = DocumentManager::get_document_id(api_get_course_info(), dirname($path));
}

// This needs cleaning!
if (api_get_group_id()) { 
    // If the group id is set, check if the user has the right to be here
	// Needed for group related stuff
	require_once api_get_path(LIBRARY_PATH).'groupmanager.lib.php';
	// Get group info
	$group_properties = GroupManager::get_group_properties(api_get_group_id());
	$noPHP_SELF = true;

	if ($is_allowed_to_edit || GroupManager::is_user_in_group($_user['user_id'], api_get_group_id())) { // Only courseadmin or group members allowed
		$to_group_id = api_get_group_id();
		$req_gid = '&amp;gidReq='.api_get_group_id();
		$interbreadcrumb[] = array('url' => '../group/group_space.php?gidReq='.api_get_group_id(), 'name' => get_lang('GroupSpace'));
	} else {
		api_not_allowed(true);
	}
} elseif ($is_allowed_to_edit || is_my_shared_folder(api_get_user_id(), $path, api_get_session_id())) {
     
    // Admin for "regular" upload, no group documents. And check if is my shared folder
	$to_group_id = 0;
	$req_gid = '';
} else { // No course admin and no group member...
	api_not_allowed(true);
}

// Group docs can only be uploaded in the group directory
if ($to_group_id != 0 && $path == '/') {
	$path = $group_properties['directory'];
}

// I'm in the certification module?
$is_certificate_mode = false;
$is_certificate_array = explode('/', $path);
array_shift($is_certificate_array);
if ($is_certificate_array[0] == 'certificates') {
	$is_certificate_mode = true;
}

// Variables
//$max_filled_space = DocumentManager::get_course_quota();

// Title of the tool
if ($to_group_id != 0) { // Add group name after for group documents
	$add_group_to_title = ' ('.$group_properties['name'].')';
}
if (isset($_REQUEST['certificate'])) {
	$nameTools = get_lang('UploadCertificate').$add_group_to_title;
} else {
	$nameTools = get_lang('UplUploadDocument').$add_group_to_title;
}

// Breadcrumbs
if ($is_certificate_mode) {
	$interbreadcrumb[] = array('url' => '../gradebook/'.$_SESSION['gradebook_dest'], 'name' => get_lang('Gradebook'));
} else {
	$interbreadcrumb[] = array('url' => './document.php?id='.$document_id.$req_gid, 'name'=> get_lang('Documents'));
}


$this_section = SECTION_COURSES;

// Display the header
Display::display_header($nameTools, 'Doc');

/*	Here we do all the work */

// User has submitted a file
if (!empty($_FILES)) {    
    DocumentManager::upload_document($_FILES, $_POST['curdirpath'], $_POST['title'], $_POST['comment'], $_POST['unzip'], $_POST['if_exists'], $_POST['index_document'], true);
}

// Actions
echo '<div class="actions">';
// Link back to the documents overview
if ($is_certificate_mode) {
	echo '<a href="document.php?id='.$document_id.'&selectcat=' . Security::remove_XSS($_GET['selectcat']).'">'.Display::return_icon('back.png',get_lang('BackTo').' '.get_lang('CertificateOverview'),'','32').'</a>';
} else {
	echo '<a href="document.php?id='.$document_id.'">'.Display::return_icon('back.png',get_lang('BackTo').' '.get_lang('DocumentsOverview'),'','32').'</a>';
}

// Link to create a folder
/*
if (!isset($_GET['createdir']) && !is_my_shared_folder($_user['user_id'], $path, api_get_session_id()) && !$is_certificate_mode) {
	echo '<a href="'.api_get_self().'?path='.$path.'&amp;createdir=1">'.Display::return_icon('new_folder.png', get_lang('CreateDir'),'','32').'</a>';
}*/
echo '</div>';

// Form to select directory
$folders = DocumentManager::get_all_document_folders($_course, $to_group_id, $is_allowed_to_edit);
if (!$is_certificate_mode) {
	echo(build_directory_selector($folders, $path, $group_properties['directory']));
}

$form = new FormValidator('upload', 'POST', api_get_self(), '', 'enctype="multipart/form-data"');
$form->addElement('hidden', 'id', $document_id);
$form->addElement('hidden', 'curdirpath', $path);
$form->addElement('file', 'file', get_lang('File'), 'id="user_upload" size="45"');
$form->addElement('html', '<div class="row" style="font-size:smaller;font-style:italic;"><div class="label">&nbsp;</div><div class="formw">'.get_lang('MaxFileSize').': '.ini_get('upload_max_filesize').'<br/>'.get_lang('DocumentQuota').': '.(round(DocumentManager::get_course_quota()/1000000)-round(DocumentManager::documents_total_space($_course)/1000000)).' M</div></div>');
if (api_get_setting('use_document_title') == 'true') {
	$form->addElement('text', 'title', get_lang('Title'), array('size' => '20', 'style' => 'width:300px', 'id' => 'title_file'));
	$form->addElement('textarea', 'comment', get_lang('Comment'), 'wrap="virtual" style="width:300px;"');
}
// Advanced parameters
$form -> addElement('html', '<div class="row">
			<div class="label">&nbsp;</div>
			<div class="formw">
				<a href="javascript://" onclick=" return advanced_parameters()"><span id="img_plus_and_minus"><div style="vertical-align:top;" ><img style="vertical-align:middle;" src="../img/div_show.gif" alt="" />&nbsp;'.get_lang('AdvancedParameters').'</div></span></a>
			</div>
			</div>');
$form -> addElement('html', '<div id="options" style="display:none">');

// Check box options
$form->addElement('checkbox', 'unzip', get_lang('Options'), get_lang('Uncompress'), 'onclick="javascript: check_unzip();" value="1"');

if (api_get_setting('search_enabled') == 'true') {
	//TODO: include language file
	$supported_formats = get_lang('SupportedFormatsForIndex').': HTML, PDF, TXT, PDF, Postscript, MS Word, RTF, MS Power Point';
	$form->addElement('checkbox', 'index_document', '', get_lang('SearchFeatureDoIndexDocument').'<div style="font-size: 80%" >'.$supported_formats.'</div>');
	$form->addElement('html', '<br /><div class="row">');
	$form->addElement('html', '<div class="label">'.get_lang('SearchFeatureDocumentLanguage').'</div>');
	$form->addElement('html', '<div class="formw">'.api_get_languages_combo().'</div>');
	$form->addElement('html', '</div><div class="sub-form">');
	$specific_fields = get_specific_field_list();
	foreach ($specific_fields as $specific_field) {
		$form->addElement('text', $specific_field['code'], $specific_field['name'].' : ');
	}
	$form->addElement('html', '</div>');
}

$form->addElement('radio', 'if_exists', get_lang('UplWhatIfFileExists'), get_lang('UplDoNothing'), 'nothing');
$form->addElement('radio', 'if_exists', '', get_lang('UplOverwriteLong'), 'overwrite');
$form->addElement('radio', 'if_exists', '', get_lang('UplRenameLong'), 'rename');

// Close the java script and avoid the footer up
$form -> addElement('html', '</div>');

// Button upload document
$form->addElement('style_submit_button', 'submitDocument', get_lang('SendDocument'), 'class="upload"');
$form->add_real_progress_bar('DocumentUpload', 'file');

$defaults = array('index_document' => 'checked="checked"');

$form->setDefaults($defaults);

$simple_form = $form->return_form();

echo '<style>
.files {
    border-collapse: collapse;
    margin-top: 10px;
}

.files td {
    padding: 3px 10px 3px 0;
}
</style>';
$url = api_get_path(WEB_AJAX_PATH).'document.ajax.php';
$multiple_form =  get_lang('ClickToSelectOrDragAndDropMultipleFilesOnTheUploadField').'<br />';
//Adding icon replace the  <div>'.get_lang('UploadFiles').'</div> with this:
//<div style="width:50%;margin:0 auto;"> '.Display::div(Display::return_icon('folder_document.png', '', array(), 64), array('style'=>'float:left')).' '.get_lang('UploadFiles').'</div>
$multiple_form .=  '<center><form id="file_upload" action="'.$url.'" method="POST" enctype="multipart/form-data">    
    <input type="hidden" name="curdirpath" value="'.$path.'" />    
    <input type="file" name="file" multiple>
    <button>Upload</button>
    <div>'.get_lang('UploadFiles').'</div>
</center></form>';
$multiple_form  .='<table class="files"></table>';
$headers = array(get_lang('Send') , get_lang('Send').' ('.get_lang('Simple').')');
echo Display::tabs($headers, array($multiple_form, $simple_form ),'tabs');

// Footer
Display::display_footer();
