<?php //$id: $
/**
 * Display part of the SCORM sub-process for upload. This script MUST BE included by upload/index.php
 * as it prepares most of the variables needed here.
 * @package dokeos.upload
 * @author Yannick Warnier <ywarnier@beeznest.org>
 */
/**
 * Small function to list files in garbage/
 */
function get_zip_files_in_garbage(){
	$list = array();
	$dh = opendir(api_get_path(SYS_CODE_PATH).'garbage/');
	if($dh === false){
		//ignore
	}else{
		while($entry = readdir($dh)){
			if(substr($entry,0,1) == '.'){/*ignore files starting with . */}
			else
			{
				if(preg_match('/^.*\.zip$/i',$entry)){
					$list[] = $entry;
				}
			}
		}
		natcasesort($list);
		closedir($dh);
	}
	return $list;
}
/**
 * Just display the form needed to upload a SCORM and give its settings
 */
$nameTools = get_lang("FileUpload");
$interbreadcrumb[]= array ("url"=>"../newscorm/lp_controller.php?action=list", "name"=> get_lang("Doc"));
Display::display_header($nameTools,"Path");
//show the title
api_display_tool_title(get_lang("Learnpath")." - ".$nameTools.$add_group_to_title);

require_once (api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');
include('../newscorm/content_makers.inc.php');

$form = new FormValidator('','POST','upload.php','','id="upload_form" enctype="multipart/form-data"');

$form->addElement('hidden', 'curdirpath', $path);
$form->addElement('hidden', 'tool', $my_tool);

$form->addElement('file','user_file',get_lang('FileToUpload'));

$select_content_marker = &$form->addElement('select','content_maker',get_lang('ContentMaker'));

foreach($content_origins as $index => $origin){
	$select_content_marker->addOption($origin,$origin);
	if($index == 1){
		$select_content_marker -> setSelected($origin);
	}
}

$select_content_proximity = &$form->addElement('select','content_proximity',get_lang('ContentProximity'));
	$select_content_proximity->addOption(get_lang('Local'),"local");
	$select_content_proximity->addOption(get_lang('Remote'),"remote");
	$select_content_proximity -> setSelected("local");

$form->addElement('submit', 'submit', get_lang('Download'));

$form->addElement('html', '<br><br><br>');
/*$list = get_zip_files_in_garbage();
if(count($list)>0){
	$select_file_name = &$form->addElement('select','file_name',get_lang('Or').' '.strtolower(get_lang('UploadLocalFileFromGarbageDir')));
	foreach($list as $file){
		$select_file_name->addOption($file,$file);
	}
	$form->addElement('submit', 'submit', get_lang('Download'));
}
else{
	$text_empty = &$form->addElement('text', 'empty', get_lang('Or').' '.strtolower(get_lang('UploadLocalFileFromGarbageDir')));
	$defaults["empty"] = get_lang('Empty');
	$text_empty->freeze();
}*/

$form->add_real_progress_bar('uploadScorm','user_file');

$form->setDefaults($defaults);
$form->display();

?>

<br/>
<?php
/*
==============================================================================
		FOOTER
==============================================================================
*/
Display::display_footer();
?>