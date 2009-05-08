<?php // $Id: upload.php 20420 2009-05-08 20:11:57Z herodoto $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004-2005 Dokeos S.A.
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	Copyright (c) various contributors

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact: Dokeos, 181 rue Royale, B-1000 Brussels, Belgium, info@dokeos.com
==============================================================================
*/
/**
==============================================================================
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
*			    The script always begins with the second step,
*			    so it allows to return more easily to the first step.
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
* @package dokeos.document
==============================================================================
*/

// name of the language file that needs to be included
$language_file = 'document';

// including the global Dokeos file
include("../inc/global.inc.php");

// including additional libraries
include_once(api_get_path(LIBRARY_PATH) . 'fileUpload.lib.php');
include_once(api_get_path(LIBRARY_PATH) . 'events.lib.inc.php');
include_once(api_get_path(LIBRARY_PATH) . 'document.lib.php');
require_once(api_get_path(LIBRARY_PATH) . 'specific_fields_manager.lib.php');
require_once (api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');
include('document.inc.php');

// adding extra javascript to the form
$htmlHeadXtra[] =
"<script type=\"text/javascript\">
<!-- //
function check_unzip() {
	if(document.upload.unzip.checked==true){
	document.upload.if_exists[0].disabled=true;
	document.upload.if_exists[1].checked=true;
	document.upload.if_exists[2].disabled=true;
	}
	else {
	document.upload.if_exists[0].checked=true;
	document.upload.if_exists[0].disabled=false;
	document.upload.if_exists[2].disabled=false;
	}
}
// -->
</script>";

/**
 * Obtains the text inside the file with the right parser
 */
function get_text_content($doc_path, $doc_mime) {
	// TODO: review w$ compatibility

	// use usual exec output lines array to store stdout instead of a temp file
	// because we need to store it at RAM anyway before index on DokeosIndexer object
	$ret_val = NULL;
	switch ($doc_mime) {
		case 'text/plain':
			$handle = fopen($doc_path, "r");
			$output = array(fread($handle, filesize($doc_path)));
			fclose($handle);
			break;
		case 'application/pdf':
		    exec("pdftotext $doc_path -", $output, $ret_val);
		    break;
		case 'application/postscript':
			$temp_file = tempnam(sys_get_temp_dir(), 'dokeos');
		    exec("ps2pdf $doc_path $temp_file", $output, $ret_val);
		    if ($ret_val !== 0) { // shell fail, probably 127 (command not found)
			    return FALSE;
		    }
		    exec("pdftotext $temp_file -", $output, $ret_val);
		    unlink($temp_file);
		    var_dump($output);
		    break;
		case 'application/msword':
		    exec("catdoc $doc_path", $output, $ret_val);
		    var_dump($output);
		    break;
		case 'text/html':
		    exec("html2text $doc_path", $output, $ret_val);
		    break;
        case 'text/rtf':
            // note: correct handling of code pages in unrtf
            // on debian lenny unrtf v0.19.2 can not, but unrtf v0.20.5 can
            exec("unrtf --text $doc_path", $output, $ret_val);
            if ($ret_val == 127) { // command not found
                return FALSE;
            }
            // avoid index unrtf comments
            if (is_array($output) && count($output)>1) {
                $parsed_output = array();
                foreach ($output as $line) {
                    if (!preg_match('/^###/', $line, $matches)) {
                        if (!empty($line)) {
                            $parsed_output[] = $line;
                        }
                    }
                }
                $output = $parsed_output;
            }
            break;
        case 'application/vnd.ms-powerpoint':
            exec("catppt $doc_path", $output, $ret_val);
            break;
        case 'application/vnd.ms-excel':
	        exec("xls2csv -c\" \" $doc_path", $output, $ret_val);
	        break;
	}

	$content = '';
	if (!is_null($ret_val)) {
		if ($ret_val !== 0) { // shell fail, probably 127 (command not found)
			return FALSE;
		}
	}
	if (isset($output)) {
		foreach ( $output as $line ) {
			$content .= $line ."\n";
		}
		return $content;
	}
	else {
		return FALSE;
	}
}

// variables
$is_allowed_to_edit = api_is_allowed_to_edit();

$courseDir   = $_course['path']."/document";
$sys_course_path = api_get_path(SYS_COURSE_PATH);
$base_work_dir = $sys_course_path.$courseDir;
$noPHP_SELF=true;

//this needs cleaning!
if(isset($_SESSION['_gid']) && $_SESSION['_gid']!='') //if the group id is set, check if the user has the right to be here
{
	//needed for group related stuff
	include_once(api_get_path(LIBRARY_PATH) . 'groupmanager.lib.php');
	//get group info
	$group_properties = GroupManager::get_group_properties($_SESSION['_gid']);
	$noPHP_SELF=true;

	if($is_allowed_to_edit || GroupManager::is_user_in_group($_user['user_id'],$_SESSION['_gid'])) //only courseadmin or group members allowed
	{
		$to_group_id = $_SESSION['_gid'];
		$req_gid = '&amp;gidReq='.$_SESSION['_gid'];
		$interbreadcrumb[]= array ("url"=>"../group/group_space.php?gidReq=".$_SESSION['_gid'], "name"=> get_lang('GroupSpace'));
	}
	else
	{
		api_not_allowed(true);
	}
}
elseif($is_allowed_to_edit) //admin for "regular" upload, no group documents
{
	$to_group_id = 0;
	$req_gid = '';
}
else  //no course admin and no group member...
{
	api_not_allowed(true);
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

//check the path: if the path is not found (no document id), set the path to /
if(!DocumentManager::get_document_id($_course,$path))
{
	$path = '/';
}
//group docs can only be uploaded in the group directory
if($to_group_id!=0 && $path=='/')
{
	$path = $group_properties['directory'];
}

//if we want to unzip a file, we need the library
if (isset($_POST['unzip']) && $_POST['unzip'] == 1)
{
	include(api_get_path(LIBRARY_PATH).'pclzip/pclzip.lib.php');
}

// variables
$max_filled_space = DocumentManager::get_course_quota();

// title of the tool
if($to_group_id !=0) //add group name after for group documents
{
	$add_group_to_title = ' ('.$group_properties['name'].')';
}
$nameTools = get_lang('UplUploadDocument').$add_group_to_title;

// breadcrumbs
$interbreadcrumb[] = array('url' =>'./document.php?curdirpath='.urlencode($path).$req_gid, 'name'=> get_lang('Documents'));

// display the header
Display::display_header($nameTools, 'Doc');



/*
-----------------------------------------------------------
	Here we do all the work
-----------------------------------------------------------
*/

//user has submitted a file
if(isset($_FILES['user_upload']))
{
	//echo("<pre>");
	//print_r($_FILES['user_upload']);
	//echo("</pre>");

	$upload_ok = process_uploaded_file($_FILES['user_upload']);
	if($upload_ok)
	{
		//file got on the server without problems, now process it
		$new_path = handle_uploaded_document($_course, $_FILES['user_upload'],$base_work_dir,$_POST['curdirpath'],$_user['user_id'],$to_group_id,$to_user_id,$max_filled_space,$_POST['unzip'],$_POST['if_exists']);
    	$new_comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';
    	$new_title = isset($_POST['title']) ? trim($_POST['title']) : '';

    	if ($new_path && ($new_comment || $new_title))
    	if (($docid = DocumentManager::get_document_id($_course, $new_path)))
    	{
        	$table_document = Database::get_course_table(TABLE_DOCUMENT);
        	$ct = '';
        	if ($new_comment) $ct .= ", comment='$new_comment'";
        	if ($new_title)   $ct .= ", title='$new_title'";
        	api_sql_query("UPDATE $table_document SET" . substr($ct, 1) .
        	    " WHERE id = '$docid'", __FILE__, __LINE__);
    	}

      if ( (api_get_setting('search_enabled')=='true') && ($docid = DocumentManager::get_document_id($_course, $new_path))) {
        $table_document = Database::get_course_table(TABLE_DOCUMENT);
        $result = api_sql_query("SELECT * FROM $table_document WHERE id = '$docid' LIMIT 1", __FILE__, __LINE__);
        if (Database::num_rows($result) == 1) {
          $row = Database::fetch_array($result);
          $doc_path = api_get_path(SYS_COURSE_PATH) . $courseDir. $row['path'];
          //TODO: mime_content_type is deprecated, fileinfo php extension is enabled by default as of PHP 5.3.0
          // now versions of PHP on Debian testing(5.2.6-5) and Ubuntu(5.2.6-2ubuntu) are lower, so wait for a while
          $doc_mime = mime_content_type($doc_path);
          //echo $doc_mime;
          //TODO: more mime types
          $allowed_mime_types = array('text/plain', 'application/pdf', 'application/postscript', 'application/msword', 'text/html', 'text/rtf', 'application/vnd.ms-powerpoint', 'application/vnd.ms-excel');

          // mime_content_type does not detect correctly some formats that are going to be supported for index, so an extensions array is used by the moment
          if (empty($doc_mime)) {
          	$allowed_extensions = array('ppt', 'pps', 'xls');
            $extensions = preg_split("/[\/\\.]/", $doc_path) ;
            $doc_ext = $extensions[count($extensions)-1];
            if (in_array($doc_ext, $allowed_extensions)) {
            	switch ($doc_ext) {
                    case 'ppt':
                    case 'pps':
                        $doc_mime = 'application/vnd.ms-powerpoint';
                        break;
                    case 'xls':
                        $doc_mime = 'application/vnd.ms-excel';
                        break;
            	}
            }
          }

          if (in_array($doc_mime, $allowed_mime_types) && isset($_POST['index_document']) && $_POST['index_document']) {
            $file_title = $row['title'];
            $file_content = get_text_content($doc_path, $doc_mime);
            $courseid = api_get_course_id();
            isset($_POST['language'])? $lang=Database::escape_string($_POST['language']): $lang = 'english';

            require_once(api_get_path(LIBRARY_PATH).'search/DokeosIndexer.class.php');
            require_once(api_get_path(LIBRARY_PATH).'search/IndexableChunk.class.php');

            $ic_slide = new IndexableChunk();
            $ic_slide->addValue("title", $file_title);
            $ic_slide->addCourseId($courseid);
            $ic_slide->addToolId(TOOL_DOCUMENT);
            $xapian_data = array(
              SE_COURSE_ID => $courseid,
              SE_TOOL_ID => TOOL_DOCUMENT,
              SE_DATA => array('doc_id' => (int)$docid),
              SE_USER => (int)api_get_user_id(),
            );
            $ic_slide->xapian_data = serialize($xapian_data);
            $di = new DokeosIndexer();
            $di->connectDb(NULL, NULL, $lang);

            $specific_fields = get_specific_field_list();

            // process different depending on what to do if file exists
            /**
             * FIXME: Find a way to really verify if the file had been
             * overwriten. Now all work is done at
             * handle_uploaded_document() and it's difficult to verify it
             */
			if (!empty($_POST['if_exists']) && $_POST['if_exists'] == 'overwrite') {
				// overwrite the file on search engine
				// actually, it consists on delete terms from db, insert new ones, create a new search engine document, and remove the old one 

				// get search_did
				$tbl_se_ref = Database::get_main_table(TABLE_MAIN_SEARCH_ENGINE_REF);
				$sql = 'SELECT * FROM %s WHERE course_code=\'%s\' AND tool_id=\'%s\' AND ref_id_high_level=%s LIMIT 1';
				$sql = sprintf($sql, $tbl_se_ref, $courseid, TOOL_DOCUMENT, $docid);
				$res = api_sql_query($sql, __FILE__, __LINE__);

				if (Database::num_rows($res) > 0) {
					$se_ref = Database::fetch_array($res);
					$di->remove_document((int)$se_ref['search_did']);
					$all_specific_terms = '';
					foreach ($specific_fields as $specific_field) {
						delete_all_specific_field_value($courseid, $specific_field['id'], TOOL_DOCUMENT, $docid);
						//update search engine
						$sterms = trim($_REQUEST[$specific_field['code']]);
						$all_specific_terms .= ' '. $sterms;
						$sterms = explode(',', $sterms);
						foreach ($sterms as $sterm) {
							$sterm = trim($sterm);
							if (!empty($sterm)) {
								$ic_slide->addTerm($sterm, $specific_field['code']);
								add_specific_field_value($specific_field['id'], $courseid, TOOL_DOCUMENT, $docid, $value);
							}
						}
					}
					// add terms also to content to make terms findable by probabilistic search
					$file_content = $all_specific_terms .' '. $file_content;
					$ic_slide->addValue("content", $file_content);
					$di->addChunk($ic_slide);
					//index and return a new search engine document id
					$did = $di->index();
					if ($did) {
						// update the search_did on db
						$tbl_se_ref = Database::get_main_table(TABLE_MAIN_SEARCH_ENGINE_REF);
						$sql = 'UPDATE %s SET search_did=%d WHERE id=%d LIMIT 1';
						$sql = sprintf($sql, $tbl_se_ref, (int)$did, (int)$se_ref['id']);
						api_sql_query($sql,__FILE__,__LINE__);
					}

				}
			}
			else {
				// add all terms
				$all_specific_terms = '';
				foreach ($specific_fields as $specific_field) {
					if (isset($_REQUEST[$specific_field['code']])) {
						$sterms = trim($_REQUEST[$specific_field['code']]);
						$all_specific_terms .= ' '. $sterms;
						if (!empty($sterms)) {
							$sterms = explode(',', $sterms);
							foreach ($sterms as $sterm) {
								$ic_slide->addTerm(trim($sterm), $specific_field['code']);
								add_specific_field_value($specific_field['id'], $courseid, TOOL_DOCUMENT, $docid, $sterm);
							}
						}
					}
				}
				// add terms also to content to make terms findable by probabilistic search
				$file_content = $all_specific_terms .' '. $file_content;
				$ic_slide->addValue("content", $file_content);
				$di->addChunk($ic_slide);
				//index and return search engine document id
				$did = $di->index();
				if ($did) {
					// save it to db
					$tbl_se_ref = Database::get_main_table(TABLE_MAIN_SEARCH_ENGINE_REF);
					$sql = 'INSERT INTO %s (id, course_code, tool_id, ref_id_high_level, search_did)
						VALUES (NULL , \'%s\', \'%s\', %s, %s)';
					$sql = sprintf($sql, $tbl_se_ref, $courseid, TOOL_DOCUMENT, $docid, $did);
					api_sql_query($sql,__FILE__,__LINE__);
				}
			}
          }

        }

      }

		//check for missing images in html files
		$missing_files = check_for_missing_files($base_work_dir.$new_path);
		if($missing_files)
		{
			//show a form to upload the missing files
			Display::display_normal_message(build_missing_files_form($missing_files,$_POST['curdirpath'],$_FILES['user_upload']['name']),false);
		}
	}
}
//missing images are submitted
if(isset($_POST['submit_image']))
{
	$number_of_uploaded_images = count($_FILES['img_file']['name']);
	//if images are uploaded
	if ($number_of_uploaded_images > 0)
	{
		//we could also create a function for this, I'm not sure...
		//create a directory for the missing files
		$img_directory = str_replace('.','_',$_POST['related_file']."_files");
		$missing_files_dir = create_unexisting_directory($_course,$_user['user_id'],$to_group_id,$to_user_id,$base_work_dir,$img_directory);
		//put the uploaded files in the new directory and get the paths
		$paths_to_replace_in_file = move_uploaded_file_collection_into_directory($_course, $_FILES['img_file'],$base_work_dir,$missing_files_dir,$_user['user_id'],$to_group_id,$to_user_id,$max_filled_space);
		//open the html file and replace the paths
		replace_img_path_in_html_file($_POST['img_file_path'],$paths_to_replace_in_file,$base_work_dir.$_POST['related_file']);
		//update parent folders
		item_property_update_on_folder($_course,$_POST['curdirpath'],$_user['user_id']);
	}
}
//they want to create a directory
if(isset($_POST['create_dir']) && $_POST['dirname']!='')
{
	$added_slash = ($path=='/')?'':'/';
	$dir_name = $path.$added_slash.replace_dangerous_char($_POST['dirname']);
	$created_dir = create_unexisting_directory($_course,$_user['user_id'],$to_group_id,$to_user_id,$base_work_dir,$dir_name,$_POST['dirname']);
	if($created_dir)
	{
		Display::display_confirmation_message(get_lang('DirCr'),false);
		$path = $created_dir;
	}
	else
	{
		display_error(get_lang('CannotCreateDir'));
	}
}

//tracking not needed here?
//event_access_tool(TOOL_DOCUMENT);

/*============================================================================*/
?>

<?php
//=======================================//
//they want to create a new directory//
//=======================================//

if(isset($_GET['createdir']))
{
	//create the form that asks for the directory name
	$new_folder_text = '<form action="'.api_get_self().'" method="POST">';
	$new_folder_text .= '<input type="hidden" name="curdirpath" value="'.$path.'"/>';
	$new_folder_text .= get_lang('NewDir') .' ';
	$new_folder_text .= '<input type="text" name="dirname"/>';
	$new_folder_text .= '<button type="submit" class="save" name="create_dir">'.get_lang('CreateFolder').'</button>';
	$new_folder_text .= '</form>';
	//show the form
	//Display::display_normal_message($new_folder_text, false);
	
	echo create_dir_form();
}

// actions
echo '<div class="actions">';

// link back to the documents overview
echo '<a href="document.php?curdirpath='.$path.'">'.Display::return_icon('back.png',get_lang('Back').' '.get_lang('To').' '.get_lang('DocumentsOverview')).get_lang('Back').' '.get_lang('To').' '.get_lang('DocumentsOverview').'</a>';

// link to create a folder
if(!isset($_GET['createdir']))
{
	echo '<a href="'.api_get_self().'?path='.$path.'&amp;createdir=1">'.Display::return_icon('folder_new.gif', get_lang('CreateDir')).get_lang('CreateDir').'</a>';
}
echo '</div>';

//form to select directory
$folders = DocumentManager::get_all_document_folders($_course,$to_group_id,$is_allowed_to_edit);
echo(build_directory_selector($folders,$path,$group_properties['directory']));
?>

<!-- start upload form -->

<?php



$form = new FormValidator('upload','POST',api_get_self(),'','enctype="multipart/form-data"');

// form title
$form->addElement('header', '', $nameTools);

$form->addElement('hidden','curdirpath',$path);

$form->addElement('file','user_upload',get_lang('File'),'id="user_upload" size="45"');

if(get_setting('use_document_title')=='true')
{
	$form->addElement('text','title',get_lang('Title'),'size="20" style="width:300px;"');
	$form->addElement('textarea','comment',get_lang('Comment'),'wrap="virtual" style="width:300px;"');
}

$form->addElement('checkbox','unzip',get_lang('Options'),get_lang('Uncompress'),'onclick="check_unzip()" value="1"');

if(api_get_setting('search_enabled')=='true')
{
	//TODO: include language file
	$supported_formats = 'Supported formats for index: Text plain, PDF, Postscript, MS Word, HTML, RTF, MS Power Point';
    $form -> addElement ('checkbox', 'index_document','', get_lang('SearchFeatureDoIndexDocument') . '<div style="font-size: 80%" >'. $supported_formats .'</div>');
    $form -> addElement ('html','<br /><div class="row">');
    $form -> addElement ('html', '<div class="label">'. get_lang('SearchFeatureDocumentLanguage') .'</div>');
    $form -> addElement ('html', '<div class="formw">'. api_get_languages_combo() .'</div>');
    $form -> addElement ('html','</div><div class="sub-form">');
    $specific_fields = get_specific_field_list();
    foreach ($specific_fields as $specific_field) {
        $form -> addElement ('text', $specific_field['code'], $specific_field['name'].' : ');
    }
    $form -> addElement ('html','</div>');
}

$form->addElement('radio', 'if_exists', get_lang('UplWhatIfFileExists'), get_lang('UplDoNothing'), 'nothing');
$form->addElement('radio', 'if_exists', '', get_lang('UplOverwriteLong'), 'overwrite');
$form->addElement('radio', 'if_exists', '', get_lang('UplRenameLong'), 'rename');

$form->addElement('style_submit_button', 'submitDocument', get_lang('SendDocument'),'class="save"');

$form->add_real_progress_bar('DocumentUpload','user_upload');

$defaults = array('index_document'=>'checked="checked"');
$form->setDefaults($defaults);

$form->display();

?>

<!-- end upload form -->

<?php
// footer
Display::display_footer();
?>
