<?php
/*
    DOKEOS - elearning and course management software

    For a full list of contributors, see documentation/credits.html

    This program is free software; you can redistribute it and/or
    modify it under the terms of the GNU General Public License
    as published by the Free Software Foundation; either version 2
    of the License, or (at your option) any later version.
    See "documentation/licence.html" more details.

    Contact:
		Dokeos
		Rue des Palais 44 Paleizenstraat
		B-1030 Brussels - Belgium
		Tel. +32 (2) 211 34 56
*/


/**
*	Code for Hotpotatoes integration.
*	@package dokeos.exercise
* 	@author Istvan Mandak
* 	@version $Id: hotpotatoes.php 19675 2009-04-09 08:46:51Z pcool $
*/


// name of the language file that needs to be included
$language_file ='exercice';

// including the global Dokeos file
include('../inc/global.inc.php');

// include additional libraries
include_once(api_get_path(LIBRARY_PATH).'fileUpload.lib.php');
include_once(api_get_path(LIBRARY_PATH).'document.lib.php');
include_once(api_get_path(LIBRARY_PATH).'fileManage.lib.php');
include_once(api_get_path(LIBRARY_PATH)."pclzip/pclzip.lib.php");
include("hotpotatoes.lib.php");

// section (for the tabs)
$this_section=SECTION_COURSES;

// access restriction: only teachers are allowed here
if(!api_is_allowed_to_edit())
{
	api_not_allowed();
}

// the breadcrumbs
$interbreadcrumb[]= array ("url"=>"./exercice.php", "name"=> get_lang('Exercices'));

$is_allowedToEdit=api_is_allowed_to_edit();

// Database table definitions
$dbTable				= Database::get_course_table(TABLE_DOCUMENT);

// setting some variables
$baseServDir = $_configuration['root_sys'];
$baseServUrl = $_configuration['url_append']."/";
$document_sys_path = api_get_path(SYS_COURSE_PATH).$_course['path']."/document";
$uploadPath = "/HotPotatoes_files";
$finish 		= (!empty($_POST['finish'])?$_POST['finish']:0);
$imgcount		= (!empty($_POST['imgcount'])?$_POST['imgcount']:null);
$fld			= (!empty($_POST['fld'])?$_POST['fld']:null);

// if user is allowed to edit
if (api_is_allowed_to_edit())
{
	//disable document parsing(?) - obviously deprecated
	$enableDocumentParsing=false;

	if(hotpotatoes_init($document_sys_path.$uploadPath))
	{//if the directory doesn't exist
		//create the "HotPotatoes" directory
		$doc_id = add_document($_course, '/HotPotatoes_files','folder',0,'HotPotatoes Files');
		//update properties in dbase (in any case)
		api_item_property_update($_course,TOOL_DOCUMENT,$doc_id,'FolderCreated',$_user['user_id']);
		//make invisible(in any case) - why?
		api_item_property_update($_course,TOOL_DOCUMENT,$doc_id,'invisible',$_user['user_id']);
	}
}

 /** display */
// if finish is set; it's because the user came from this script in the first place (displaying hidden "finish" field)
if((api_is_allowed_to_edit()) && (($finish == 0) || ($finish == 2)))
//if(($is_allowedToEdit) )
{
	$nameTools = get_lang('HotPotatoesTests');

	//moved this down here as the upload handling functions give output
	if (isset($_POST['submit']))
	{
		//check that the submit button was pressed when the button had the "Download" value
		//This should be updated to "upload" here and on the button, and it would be better to
		// check something else than a string displayd on a button
		if (strcmp($_POST['submit'],get_lang('Send'))===0)
		{


			//@todo: this value should be moved to the platform admin section
			$maxFilledSpace = 100000000;

			//initialise $finish
			if (!isset($finish)) {$finish = 0;}

			//if the size is not defined, it's probably because there has been an error or no file was submitted
			if(!$_FILES['userFile']['size'])
			{
				$dialogBox .= get_lang('SendFileError').'<br />'.get_lang('Notice').' : '.get_lang('MaxFileSize').' '.ini_get('upload_max_filesize');
			}
			else
			{
				/* deprecated code
				if ($enableDocumentParsing==true)
				{ $enableDocumentParsing=false;
				$oke=1;}
				else { $oke = 0; }
				*/
				//$unzip = 'unzip';
				$unzip = 0;
				if(preg_match('/\.zip$/i',$_FILES['userFile']['name'])){
					//if it's a zip, allow zip upload
					$unzip = 1;
				}
				if ($finish==0)
				{		//generate new test folder if on first step of file upload
					$filename = replace_dangerous_char(trim($_FILES['userFile']['name']),'strict');
					$fld = GenerateHpFolder($document_sys_path.$uploadPath."/");
					@mkdir($document_sys_path.$uploadPath."/".$fld);
					$perm = api_get_setting('permissions_for_new_directories');
					$perm = octdec(!empty($perm)?$perm:'0770');
					chmod ($document_sys_path.$uploadPath."/".$fld,$perm);
					$doc_id = add_document($_course, '/HotPotatoes_files/'.$fld,'folder',0,$fld);
					api_item_property_update($_course,TOOL_DOCUMENT,$doc_id,'FolderCreated',$_user['user_id']);
				}
				else
				{ //it is not the first step... get the filename directly from the system params
					$filename = $_FILES['userFile']['name'];
				}

				/*if (treat_uploaded_file($_FILES['userFile'], $document_sys_path,
							$uploadPath."/".$fld, $maxFilledSpace, $unzip))*/
				$allow_output_on_success = false;
				if (handle_uploaded_document($_course,$_FILES['userFile'],$document_sys_path,$uploadPath."/".$fld,$_user['user_id'],null,null,$maxFilledSpace,$unzip,'',$allow_output_on_success))
				{

					if ($finish==2)
					{
						$imgparams = $_POST['imgparams'];
						$checked = CheckImageName($imgparams,$filename);
						if ($checked)
						{ $imgcount = $imgcount-1; }
						else
						{
							$dialogBox .= $filename." ".get_lang('NameNotEqual');
							my_delete($document_sys_path.$uploadPath."/".$fld."/".$filename);
							update_db_info("delete", $uploadPath."/".$fld."/".$filename);
						}
						if ($imgcount==0)  // all image uploaded
						{
							$finish=1;
						}
					}
					else
					{ //if we are (still) on the first step of the upload process
						if ($finish==0)
						{
							$finish = 2;
							// get number and name of images from the files contents
							GetImgParams("/".$filename,$document_sys_path.$uploadPath."/".$fld,$imgparams,$imgcount);
							if ($imgcount==0) //there is no img link, so finish the upload process
							{ $finish = 1; }
							else //there is still one or more img missing
							{ $dialogBox .= get_lang('DownloadEnd'); }
						}
					}
					$newComment = "";

					$query = "UPDATE $dbTable SET comment='$newComment' WHERE path=\"".$uploadPath."/".$fld."/".$filename."\"";
					/*, visibility='v' */

					api_sql_query($query,__FILE__,__LINE__);
					api_item_property_update($_course, TOOL_QUIZ, $id, "QuizAdded", $_user['user_id']);
				}
				else
				{
					if ($finish==2)
					{
						// delete?
						//$dialogBox .= get_lang('NoImg');
					}
					$finish = 0;	// error

					if (api_failure::get_last_failure() == 'not_enough_space')
					{
						$dialogBox .= get_lang('NoSpace');
					}
					elseif (api_failure::get_last_failure() == 'php_file_in_zip_file')
					{
						$dialogBox .= get_lang('ZipNoPhp');
					}

				}

				/*		if ($oke==1)
				{ $enableDocumentParsing=true;  $oke=0;}
				*/
			}
		}
	}
	if ($finish == 1)
	{ /** ok -> send to main exercises page */
		header("Location: exercice.php");
		exit;
	}

	Display::display_header($nameTools,"Exercise");
	?>

<?php

	if ($finish==2) //if we are in the img upload process
	{
	 $dialogBox.= get_lang('ImgNote_st').$imgcount.get_lang('ImgNote_en')."<br>";
		while(list($key,$string)=each($imgparams))
		{
			$dialogBox.=$string."; ";
		}
	}

	if ($dialogBox)
	{
		Display::display_normal_message($dialogBox, false); //main API
	}

	/*--------------------------------------
			  UPLOAD SECTION
	 --------------------------------------*/
	echo	"<!-- upload  -->\n",
			"<form action=\"".api_get_self()."\" method=\"post\" enctype=\"multipart/form-data\" >\n",
			"<input type=\"hidden\" name=\"uploadPath\" value=\"\">\n",
			"<input type=\"hidden\" name=\"fld\" value=\"$fld\">\n",
			"<input type=\"hidden\" name=\"imgcount\" value=\"$imgcount\">\n",
			"<input type=\"hidden\" name=\"finish\" value=\"$finish\">\n";
	echo GenerateHiddenList($imgparams);
	/*if ($finish==0){ echo get_lang('DownloadFile');}
	else {echo get_lang('DownloadImg');}
	echo 	" : ",
			"<input type=\"file\" name=\"userFile\">\n",
			"<input type=\"submit\" name=\"submit\" value=\"".get_lang('Send')."\"><br/>\n";*/
	Display::display_icon('hotpotatoes.jpg','',array('align'=> 'right', 'style' => 'position: absolute; padding-top: 30px; margin-left: 500px;'));
	echo '<div class="row"><div class="form_header">'.$nameTools.'</div></div>';	
	echo '<div class="row">';
	echo '<div class="label">';
	echo '<span class="form_required">*</span>';
	if ($finish==0){
		echo get_lang('DownloadFile').' : ';
	}
	else{
		echo get_lang('DownloadImg').' : ';
	}
	echo '</div>';
	echo '<div class="formw">';
	echo '<input type="file" name="userFile">';
	echo '</div>';
	echo '</div>';
	
	echo '<div class="row">';
	echo '<div class="label">';
	echo '</div>';
	echo '<div class="formw">	<button type="submit" class="save" name="submit" value="'.get_lang('Send').'">'.get_lang('SendFile').'</button>		</div>';
	echo '</div>';	
	
?>

<?php
}
// display the footer
Display::display_footer();
?>
