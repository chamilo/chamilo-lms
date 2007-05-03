<?php // $Id: edit_document.php 12272 2007-05-03 14:40:45Z elixir_julian $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004 Dokeos S.A.
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	Copyright (c) Olivier Brouckaert
	Copyright (c) Roan Embrechts
	Copyright (c) Rene Haentjens (RH) (update 2004/09/30)
	Copyright (c) Bart Mollet, Hogeschool Gent

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
* This file allows editing documents.
*
* Based on create_document, this file allows
* - edit name
* - edit comments
* - edit metadata (requires a document table entry)
* - edit html content (only for htm/html files)
*
* For all files
* - show editable name field
* - show editable comments field
* Additionally, for html and text files
* - show RTE
*
* Remember, all files and folders must always have an entry in the
* database, regardless of wether they are visible/invisible, have
* comments or not.
*
* @package dokeos.document
* @todo improve script structure (FormValidator is used to display form, but
* not for validation at the moment)
==============================================================================
*/

// name of the language file that needs to be included 
$language_file = 'document';

/*
------------------------------------------------------------------------------
	Included libraries
------------------------------------------------------------------------------
*/
include('../inc/global.inc.php');
$this_section=SECTION_COURSES;

include(api_get_path(LIBRARY_PATH).'fileManage.lib.php');
include(api_get_path(LIBRARY_PATH).'fileUpload.lib.php');
include(api_get_path(LIBRARY_PATH).'events.lib.inc.php');
include(api_get_path(LIBRARY_PATH).'document.lib.php');
require_once(api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');

$fck_attribute['Width'] = '800';
$fck_attribute['Height'] = '450';
$fck_attribute['ToolbarSet'] = 'Full';



/*
------------------------------------------------------------------------------
	Constants & Variables
------------------------------------------------------------------------------
*/
$file = $_GET['file'];
//echo('file: '.$file.'<br>');
$doc=basename($file);
//echo('doc: '.$doc.'<br>');
$dir=$_GET['curdirpath'];
//echo('dir: '.$dir.'<br>');
$file_name = $doc;
//echo('file_name: '.$file_name.'<br>');

$baseServDir = api_get_path(SYS_COURSE_PATH);
$baseServUrl = $_configuration['url_append']."/";
$courseDir   = $_course['path']."/document";
$baseWorkDir = $baseServDir.$courseDir;
$group_document = false;

$use_document_title = (get_setting('use_document_title')=='true')?true:false;
$noPHP_SELF=true;

/*
------------------------------------------------------------------------------
	Other init code
------------------------------------------------------------------------------
*/

/* please do not modify this dirname formatting */

if(strstr($dir,'..'))
{
	$dir='/';
}

if($dir[0] == '.')
{
	$dir=substr($dir,1);
}

if($dir[0] != '/')
{
	$dir='/'.$dir;
}

if($dir[strlen($dir)-1] != '/')
{
	$dir.='/';
}

$filepath=api_get_path('SYS_COURSE_PATH').$_course['path'].'/document'.$dir;

if(!is_dir($filepath))
{
	$filepath=api_get_path('SYS_COURSE_PATH').$_course['path'].'/document/';

	$dir='/';
}

/**************************************************/

$nameTools = get_lang('EditDocument');

$dbTable = Database::get_course_table(TABLE_DOCUMENT);

if(isset($_SESSION['_gid']) && $_SESSION['_gid']!='')
{
	$req_gid = '&amp;gidReq='.$_SESSION['_gid'];
	$interbreadcrumb[]= array ("url"=>"../group/group_space.php?gidReq=".$_SESSION['_gid'], "name"=> get_lang('GroupSpace'));
	$group_document = true;
	$noPHP_SELF=true;
}

$interbreadcrumb[]=array("url"=>"./document.php?curdirpath=".urlencode($_GET['curdirpath']).$req_gid, "name"=> get_lang('Documents'));

$is_allowedToEdit = is_allowed_to_edit() || $_SESSION['group_member_with_upload_rights'];

if(!$is_allowedToEdit)
{
	api_not_allowed(true);
}

event_access_tool(TOOL_DOCUMENT);

/*
==============================================================================
	   MAIN TOOL CODE
==============================================================================
*/

/*
------------------------------------------------------------------------------
	General functions
------------------------------------------------------------------------------
*/



/*
------------------------------------------------------------------------------
	Workhorse functions

	These do the actual work that is expected from of this tool, other functions
	are only there to support these ones.
------------------------------------------------------------------------------
*/

/**
	This function changes the name of a certain file.
	It needs no global variables, it takes all info from parameters.
	It returns nothing.
*/
function change_name($baseWorkDir, $sourceFile, $renameTo, $dir, $doc)
{
	$file_name_for_change = $baseWorkDir.$dir.$sourceFile;

	//api_display_debug_info("call my_rename: params $file_name_for_change, $renameTo");
    
    $renameTo = disable_dangerous_file($renameTo); //avoid renaming to .htaccess file
	$renameTo = my_rename($file_name_for_change, stripslashes($renameTo)); //fileManage API
    
	if ($renameTo)
	{
		if (isset($dir) && $dir != "")
		{
			$sourceFile = $dir.$sourceFile;
			$new_full_file_name = dirname($sourceFile)."/".$renameTo;
		}
		else
		{
			$sourceFile = "/".$sourceFile;
			$new_full_file_name = "/".$renameTo;
		}

		update_db_info("update", $sourceFile, $new_full_file_name); //fileManage API
		$name_changed = get_lang("ElRen");
		$info_message = get_lang('fileModified');

		$GLOBALS['file_name'] = $renameTo;
		$GLOBALS['doc'] = $renameTo;

		return $info_message;
	}
	else
	{
		$dialogBox = get_lang('FileExists');

		/* return to step 1 */
		$rename = $sourceFile;
		unset($sourceFile);
	}
}


/*
------------------------------------------------------------------------------
	Code to change the comment
------------------------------------------------------------------------------
	Step 2. React on POST data
	(Step 1 see below)
*/
if (isset($_POST['newComment']))
{
	//to try to fix the path if it is wrong
	$commentPath = str_replace("//", "/", $_POST['commentPath']);

	$newComment = trim($_POST['newComment']); // remove spaces
	$newTitle = trim($_POST['newTitle']); // remove spaces
	// Check if there is already a record for this file in the DB

	$result = mysql_query ("SELECT * FROM $dbTable WHERE path='".$commentPath."'");

	while($row = mysql_fetch_array($result, MYSQL_ASSOC))
	{
		$attribute['path'      ] = $row['path'      ];
		$attribute['comment'   ] = $row['title'   ];
	}

	//Determine the correct query to the DB

	//new code always keeps document in database
	$query = "UPDATE $dbTable SET comment='".$newComment."', title='".$newTitle."' WHERE path='".$commentPath."'";
	mysql_query($query);
	//this is an UPDATE page... we shouldn't be creating new documents here.
	/*
	if (mysql_affected_rows() == 0)
	{
		mysql_query("INSERT INTO $dbTable SET path='".$commentPath."', title='".$newTitle."', comment='".$newComment."'");
	}
	*/
	$oldComment = $newComment;
	$oldTitle = $newTitle;
	$comments_updated = get_lang('ComMod');
	$info_message = get_lang('fileModified');
}

/*
------------------------------------------------------------------------------
	Code to change the name
------------------------------------------------------------------------------
	Step 2. react on POST data - change the name
	(Step 1 see below)
*/

if (isset($_POST['renameTo']))
{
	$info_message = change_name($baseWorkDir, $_GET['sourceFile'], $_POST['renameTo'], $dir, $doc);

	//assume name change was successful
}

/*
------------------------------------------------------------------------------
	Code to change the comment
------------------------------------------------------------------------------
	Step 1. Create dialog box.
*/



/* Search the old comment */  // RH: metadata: added 'id,'
$result = mysql_query ("SELECT id,comment,title FROM $dbTable WHERE path='$dir$doc'");

$message = "<i>Debug info</i><br>directory = $dir<br>";
$message .= "document = $file_name<br>";
$message .= "comments file = " . $file . "<br>";
//Display::display_normal_message($message);

while($row = mysql_fetch_array($result, MYSQL_ASSOC))
{
	$oldComment = $row['comment'];
	$oldTitle = $row['title'];
	$docId = $row['id'];  // RH: metadata
}

/*
------------------------------------------------------------------------------
	WYSIWYG HTML EDITOR - Program Logic
------------------------------------------------------------------------------
*/

if($is_allowedToEdit)
{
	if($_POST['formSent']==1)
	{
		if(isset($_POST['renameTo']))
		{
			$_POST['filename']=disable_dangerous_file($_POST['renameTo']);

			$extension=explode('.',$_POST['filename']);
			$extension=$extension[sizeof($extension)-1];

			$_POST['filename']=str_replace('.'.$extension,'',$_POST['filename']);
		}

		$filename=stripslashes($_POST['filename']);

		$texte=trim(str_replace(array("\r","\n"),"",stripslashes($_POST['texte'])));

		if(!strstr($texte,'/css/frames.css'))
		{
			$texte=str_replace('</title></head>','</title><link rel="stylesheet" href="./css/frames.css" type="text/css" /></head>',$texte);
		}

		// RH commented: $filename=replace_dangerous_char($filename,'strict');

		if($_POST['extension'] != 'htm' && $_POST['extension'] != 'html')
		{
			$extension='html';
		}
		else
		{
			$extension = $_POST['extension'];
		}

		$file=$dir.$filename.'.'.$extension;

		if(empty($texte))
		{
			$msgError=get_lang('NoText');
		}
		elseif(empty($filename))
		{
			$msgError=get_lang('NoFileName');
		}
		else
		{
			if($fp=@fopen($filepath.$filename.'.'.$extension,'w'))
			{
				$texte = text_filter($texte);

				//echo('file path: '.$filepath.$filename.'.'.$extension);
				$path_to_remove=api_get_path('WEB_COURSE_PATH').$_course['path'].'/document'.$dir;

				$texte=str_replace($path_to_remove,'./',$texte);

				$texte=str_replace('mp3player.swf?son='.urlencode($path_to_remove),'mp3player.swf?son=.%2F',$texte);
				//echo('texte: '.$texte);

				//echo (fputs($fp,$texte))?'FPUTS OK':'FPUTS NIET OK';
				fputs($fp,$texte);

				fclose($fp);

				if(!is_dir($filepath.'css'))
				{
					mkdir($filepath.'css',0777);

					$doc_id=add_document($_course,$dir.'css','folder',0,'css');

					api_item_property_update($_course, TOOL_DOCUMENT, $doc_id, 'FolderCreated', $_user['user_id']);
					api_item_property_update($_course, TOOL_DOCUMENT, $doc_id, 'invisible', $_user['user_id']);
				}

				if(!is_file($filepath.'css/frames.css'))
				{
					copy(api_get_path(SYS_CODE_PATH).'css/frames.css',$filepath.'css/frames.css');

					$doc_id=add_document($_course,$dir.'css/frames.css','file',filesize($filepath.'css/frames.css'),'frames.css');

					api_item_property_update($_course, TOOL_DOCUMENT, $doc_id, 'DocumentAdded', $_user['user_id']);
					api_item_property_update($_course, TOOL_DOCUMENT, $doc_id, 'invisible', $_user['user_id']);
				}

				// "WHAT'S NEW" notification: update table item_property (previously last_tooledit)
				$document_id = DocumentManager::get_document_id($_course,$file);
				if($document_id)
				{
					$file_size = filesize($filepath.$filename.'.'.$extension);
					update_existing_document($_course, $document_id,$file_size);
					api_item_property_update($_course, TOOL_DOCUMENT, $document_id, 'DocumentUpdated', $_user['user_id']);
					//update parent folders
					item_property_update_on_folder($_course,$dir,$_user['user_id']);
					header('Location: document.php?curdirpath='.urlencode($_GET['curdirpath']).$req_gid);
					exit();
				}
				else
				{
					//$msgError=get_lang('Impossible');
				}
			}
			else
			{
				$msgError=get_lang('Impossible');
			}
		}
	}
}

if(file_exists($filepath.$doc))
{
	$extension=explode('.',$doc);
	$extension=$extension[sizeof($extension)-1];

	$filename=str_replace('.'.$extension,'',$doc);

	$extension=strtolower($extension);

	if(!in_array($extension,array('html','htm')))
	{
		$extension=$filename=$texte='';
	}
	else
	{
		$texte=file($filepath.$doc);
		$texte=implode('',$texte);

		$path_to_append=api_get_path('WEB_COURSE_PATH').$_course['path'].'/document'.$dir;

		$texte=str_replace('="./','="'.$path_to_append,$texte);

		$texte=str_replace('mp3player.swf?son=.%2F','mp3player.swf?son='.urlencode($path_to_append),$texte);
	}
}


/*
==============================================================================
		MAIN EDIT_DOCUMENT CODE

		- react on input
		- display user interface
==============================================================================
*/
Display::display_header($nameTools,"Doc");


api_display_tool_title(get_lang("EditDocument") . ": $file_name");

if(isset($msgError))
{
	Display::display_error_message($msgError); //main API
}
if( isset($info_message))
{
	Display::display_normal_message($info_message); //main API
}
$action =  api_get_self().'?sourceFile='.urlencode($file_name).'&curdirpath='.urlencode($_GET['curdirpath']).'&file='.urlencode($_GET['file']).'&doc='.urlencode($doc);
$form = new FormValidator('formEdit','post',$action);
$form->addElement('hidden','filename');
$form->addElement('hidden','extension');
$form->addElement('hidden','file_path');
$form->addElement('hidden','commentPath');
$form->add_textfield('renameTo',get_lang('FileName'));
if($use_document_title)
{
	$form->add_textfield('newTitle',get_lang('Title'));
	$defaults['newTitle'] = $oldTitle;
}
if($extension == "htm" || $extension == "html")
{
	$form->addElement('hidden','formSent');
	$defaults['formSent'] = 1;
	$form->add_html_editor('texte',get_lang('Content'),false,true);
	$defaults['texte'] = $texte;
}
if(!$group_document)
{
	$metadata_link = '<a href="../metadata/index.php?eid='.urlencode('Document.'.$docId).'">'.get_lang('AddMetadata').'</a>';
	$form->addElement('static',null,get_lang('Metadata'),$metadata_link);
}
$form->addElement('textarea','newComment',get_lang('Comment'),'rows="3" style="width:300px;"');
$form->addElement('submit','submit',get_lang('Ok'));
$defaults['filename'] = $filename;
$defaults['extension'] = $extension;
$defaults['file_path'] = $_GET['file'];
$defaults['commentPath'] = $file;
$defaults['renameTo'] = $file_name;
$defaults['newComment'] = $oldComment;
$form->setDefaults($defaults);
$form->display();
/*
==============================================================================
	   DOKEOS FOOTER
==============================================================================
*/
Display::display_footer();
?>