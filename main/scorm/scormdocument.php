<?php
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004 Dokeos S.A.
	Copyright (c) 2004 Denes Nagy
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)

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
* This file was origially the copy of document.php, but many modifications happened since then ;
* the direct file view is not any more needed, if the user uploads a scorm zip file, a directory
* will be automatically created for it, and the files will be uncompressed there for example ;
*
* @package dokeos.scorm
* @author Denes Nagy, principal author
* @author Isthvan Mandak, several new features
* @author Roan Embrechts, code improvements and refactoring
==============================================================================
*/

/*
==============================================================================
		INIT SECTION
==============================================================================
*/
$langFile = "scormdocument";

$uncompress=1;

require('../inc/global.inc.php');
$this_section=SECTION_COURSES;

require_once(api_get_path(LIBRARY_PATH) . "database.lib.php");

/*
-----------------------------------------------------------
	Variables
-----------------------------------------------------------
*/
$courseDir   = $_course['path']."/scorm";
// change this to change the 1000 MB limit in the learnpath/scorm tool
$maxFilledSpace = 1000000000;

$action = $_REQUEST['action'];
$Submit = $_POST['Submit'];
$learnpath_name = $_POST['learnpath_name'];
$learnpath_description = $_POST['learnpath_description'];
$id = $_REQUEST['id'];
$set_visibility = $_REQUEST['set_visibility'];
$createDir = $_REQUEST['createDir'];
$newDirPath = $_REQUEST['newDirPath'];
$newDirName = $_REQUEST['newDirName'];
$make_directory_visible = $_REQUEST['make_directory_visible'];
$make_directory_invisible = $_REQUEST['make_directory_invisible'];
$path = $_REQUEST['path']; //GET

$openDir = $_GET['openDir'];
$subdirs = isset($_GET['subdirs']) ? $_GET['subdirs'] : '';
$delete = isset($_GET['delete']) && $_GET['delete'] != '' ? $_GET['delete'] : null;
// Check if the passed parameter doesn't have .. in the path
// Check if the passed parameter isn't / (else the complete scorm folder gets deleted)
// See http://secunia.com/advisories/16407/ item 1.
if( strpos($delete,'..') > 0 || $delete == '/')
{
	api_not_allowed();		
}
$indexRoute = $_GET['indexRoute'];

/*
-----------------------------------------------------------
	Libraries
-----------------------------------------------------------
*/
include_once(api_get_path(LIBRARY_PATH) . "document.lib.php");
include("../learnpath/learnpath_functions.inc.php");

/*
-----------------------------------------------------------
	More Variables
-----------------------------------------------------------
*/
// storing the database names in variables.
$dbTable                = Database::get_course_table(SCORMDOC_TABLE);
$tbl_learnpath_item     = Database::get_course_table(LEARNPATH_ITEM_TABLE);
$tbl_learnpath_chapter  = Database::get_course_table(LEARNPATH_CHAPTER_TABLE);
$tbl_learnpath_main     = Database::get_course_table(LEARNPATH_MAIN_TABLE);
$tbl_tool               = Database::get_course_table(TOOL_LIST_TABLE);
$tbl_document           = $dbTable;

// checks if imsmanisfest.xml is present in the zip file
define('CHECK_FOR_SCORM',true);

$default_visibility="v";
$show_description_field=0;

//extra javascript functions for in html head:
$htmlHeadXtra[] =
"<style type=\"text/css\">
/*<![CDATA[*/
.comment { margin-left: 30px}
.invisible {color: #999999}
.invisible a {color: #999999}
/*]]>*/
</style>";

$htmlHeadXtra[] =
"<script type=\"text/javascript\">
/* <![CDATA[ */
function confirmation (name)
{
	if (confirm(\" ".get_lang('AreYouSureToDelete')." \"+ name + \" ?\"))
		{return true;}
	else
		{return false;}
}
/* ]]> */
</script>";


/*
==============================================================================
		FUNCTIONS
==============================================================================
*/

/**
 * Check if the given directory exists. If so, call removescormDir to delete it.
 * @param		string		Dir path
 * @return	boolean	True on success, false otherwise
 * @uses	removescormDir()	to actually remove the directory
 */

function scorm_delete($file)
{
	if ( check_name_exist($file) )
	{
		if ( is_dir($file) )
		{
			return removescormDir($file);
		}
	}
	else
	{
		return false; // no file or directory to delete
	}
}

/**
 * Delete a scorm directory (check for imsmanifest and if found, deletes the related rows in scorm tables also)
 * @param		string		Dir path
 * @return	boolean	True on success, false otherwise
 */
function removescormDir($dir)
{
	global $_course;

	if(!@$opendir = opendir($dir))
	{
		return false;
	}
	while($readdir = readdir($opendir))
	{
		if($readdir != '..' && $readdir != '.')
		{
			if(is_file($dir.'/'.$readdir))
			{
				$pos=strpos('/'.$readdir, 'imsmanifest.xml');
				if ($pos) {  //so we have the imsmanifest in this dir
				    //from d:/myworks/dokeos/dokeos_cvs/dokeos/dokeos/courses/CVSCODE4/scorm/LP2/LP2
					//we have to get /LP2/LP2
					$path=api_get_path('SYS_COURSE_PATH').$_course['official_code'].'/scorm';
					$pos=strpos($dir,$path);
					if ($pos==0) {

						$scormdir=substr($dir,strlen($path),strlen($dir)-strlen($path));
						$courseid=$_course['official_code'];

						$sql="SELECT * FROM ".Database :: get_scorm_table(SCORM_MAIN_TABLE)." where (contentTitle='$scormdir' and dokeosCourse='$courseid')";
						$result=api_sql_query($sql,__FILE__,__LINE__);
						while ($row=mysql_fetch_array($result))
						{
							$c=$row['contentId'];
							$sql2="DELETE FROM ".Database::get_scorm_sco_data_table()." where contentId=$c";
							$result2=api_sql_query($sql2,__FILE__,__LINE__);
						}
						$sql="DELETE FROM ".Database :: get_scorm_table(SCORM_MAIN_TABLE)." where (contentTitle='$scormdir' and dokeosCourse='$courseid')";
						$result=api_sql_query($sql,__FILE__,__LINE__);
					}
				}

				if(!@unlink($dir.'/'.$readdir))
				{
					return false;
				}
			}
			elseif(is_dir($dir.'/'.$readdir))
			{
				if(!removescormDir($dir.'/'.$readdir))
				{
					return false;
				}
			}
		}
	}
	closedir($opendir);
	if(!@rmdir($dir))
	{
		return false;
	}
	return true;
}

/*
==============================================================================
		MAIN CODE
==============================================================================
*/

/*
-----------------------------------------------------------
	EXPORTING A DOKEOS LEARNPATH
-----------------------------------------------------------
*/
if ($action == "exportpath" and $id)
{
	$export = exportpath($id);
	$dialogBox .= "This LP has been exported to the Document folder of your course.";
}

/*
-----------------------------------------------------------
	EXPORTING A SCORM DOCUMENT
-----------------------------------------------------------
*/
if ($action=="exportscorm")
{
	exportSCORM($path);
	//$dialogBox .= "This SCORM has been exported to the Document folder of your course.";
}



// Define the 'doc.inc.php' as language file
$nameTools = get_lang("Doc");

$is_allowedToEdit = api_is_allowed_to_edit();

// when GET['learnpath_id'] is defined, it means that a learnpath has been chosen
// and so we redirect to learnpath_handler - we possibly lose the $dialogBox warning here
if(isset($_GET['learnpath_id']))
{
	echo "<script type='text/javascript'>	window.location='../learnpath/learnpath_handler.php?learnpath_id=".$_GET['learnpath_id']."';</script></head><body></body></html>";
	exit();
}

/*
-----------------------------------------------------------
	More libraries inclusion
-----------------------------------------------------------
*/

include_once (api_get_path(LIBRARY_PATH)."fileDisplay.lib.php");
include_once (api_get_path(LIBRARY_PATH).'/events.lib.inc.php');

event_access_tool(TOOL_LEARNPATH);

if (! $is_allowed_in_course) api_not_allowed();

$is_allowedToEdit  = api_is_allowed_to_edit();
$is_allowedToUnzip = $is_courseAdmin;

if(api_is_allowed_to_edit()) // for teacher only
{
	include_once (api_get_path(LIBRARY_PATH).'/fileManage.lib.php');
	include_once (api_get_path(LIBRARY_PATH).'fileUpload.lib.php');

	if ($uncompress == 1)
	{
		include_once (api_get_path(LIBRARY_PATH)."pclzip/pclzip.lib.php");
	}
}

	/*==================================================
		  		DELETE A DOKEOS LEARNPATH
				and all the items in it
 	 ==================================================*/

	if ($action=="deletepath" and $id)
	{
		$l="learnpath/learnpath_handler.php?learnpath_id=$id";
		$sql="DELETE FROM $tbl_tool where (link='$l' and image='scormbuilder.gif')";
		$result=api_sql_query($sql,__FILE__,__LINE__);

		$sql="SELECT * FROM $tbl_learnpath_chapter where learnpath_id=$id";
		$result=api_sql_query($sql,__FILE__,__LINE__);
		while ($row=mysql_fetch_array($result))
		{
			$c=$row['id'];
			$sql2="DELETE FROM $tbl_learnpath_item where chapter_id=$c";
			$result2=api_sql_query($sql2,__FILE__,__LINE__);
		}
		$sql="DELETE FROM $tbl_learnpath_chapter where learnpath_id=$id";
		$result=api_sql_query($sql,__FILE__,__LINE__);

		deletepath($id);
		$dialogBox=get_lang('_learnpath_deleted');

	}

	/*==================================================================
  		PUBLISHING (SHOWING) A DOKEOS LEARNPATH
 	 ==================================================================*/

	if ($action=="publishpath" and !empty($id))
	{
		$sql="SELECT * FROM $tbl_learnpath_main where learnpath_id=$id";
		$result=api_sql_query($sql,__FILE__,__LINE__);
		$row=mysql_fetch_array($result);
		$name=domesticate($row['learnpath_name']);
		if ($set_visibility == 'i') { $s=$name." ".get_lang('_no_published'); $dialogBox=$s; $v=0; }
		if ($set_visibility == 'v') { $s=$name." ".get_lang('_published');    $dialogBox=$s; $v=1; }
		$sql="SELECT * FROM $tbl_tool where (name='$name' and image='scormbuilder.gif')";
		$result=api_sql_query($sql,__FILE__,__LINE__);
		$row2=mysql_fetch_array($result);
		$num=mysql_num_rows($result);
		if (($set_visibility == 'i') && ($num>0))
		{
			//it is visible or hidden but once was published
			if (($row2['visibility'])==1)
			{
				$sql ="DELETE FROM $tbl_tool WHERE (name='$name' and image='scormbuilder.gif')";
			}
			else
			{
				$sql ="UPDATE $tbl_tool set visibility=1 WHERE (name='$name' and image='scormbuilder.gif')";
			}
		}
		elseif (($set_visibility == 'v') && ($num==0))
		{
			$sql ="INSERT INTO $tbl_tool (id, name, link, image, visibility, admin, address, added_tool) VALUES ('$theid','$name','learnpath/learnpath_handler.php?learnpath_id=$id','scormbuilder.gif','$v','0','squaregrey.gif',0)";
		}
		else
		{
			//parameter and database incompatible, do nothing
		}
		$result=api_sql_query($sql,__FILE__,__LINE__);
	}

	/*==================================================================
  		EDITING A DOKEOS NEW LEARNPATH
 	 ==================================================================*/

	if ($action=="editpath" and $Submit)
	{
		$l="learnpath/learnpath_handler.php?learnpath_id=$id";
		$sql="UPDATE $tbl_tool set name='".domesticate($learnpath_name)."' where (link='$l' and image='scormbuilder.gif')";
		$result=api_sql_query($sql,__FILE__,__LINE__);

		$sql ="UPDATE $tbl_learnpath_main SET learnpath_name='".domesticate($learnpath_name)."', learnpath_description='".domesticate($learnpath_description)."' WHERE learnpath_id=$id";
		$result=api_sql_query($sql,__FILE__,__LINE__);
		$dialogBox=get_lang('_learnpath_edited');
	}


	/*==================================================================
  		ADDING A NEW LEARNPATH : treating the form
 	 ==================================================================*/

	if ($action=="add" and $Submit)
	{
		$sql ="INSERT INTO $tbl_learnpath_main (learnpath_name, learnpath_description) VALUES ('".domesticate($learnpath_name)."','".domesticate($learnpath_description)."')";
		api_sql_query($sql,__FILE__,__LINE__);
		$my_lp_id = Database::get_last_insert_id();

		$sql ="INSERT INTO $tbl_tool (name, link, image, visibility, admin, address, added_tool) VALUES ('".domesticate($learnpath_name)."','learnpath/learnpath_handler.php?learnpath_id=$my_lp_id','scormbuilder.gif','1','0','squaregrey.gif',0)";
		api_sql_query($sql,__FILE__,__LINE__);

		//instead of displaying this info text, get the user directly to the learnpath edit page
		//$dialogBox=get_lang('_learnpath_added');
		header('location:../learnpath/learnpath_handler.php?'.api_get_cidreq().'&learnpath_id='.$my_lp_id);
		exit();
	}

	/*==================================================================
  		EDITING A SCORM PACKAGE
 	 ==================================================================*/

	if ($action=="editscorm" and $Submit)
	{
		$sql ="UPDATE $tbl_document SET comment='".domesticate($learnpath_description)."', name='".domesticate($learnpath_name)."' WHERE path='$path'";
		$result=api_sql_query($sql,__FILE__,__LINE__);

		$dialogBox=get_lang('_learnpath_edited');
	}


/*============================================================================*/

if(api_is_allowed_to_edit()) // TEACHER ONLY
{

	/* > > > > > > MAIN SECTION  < < < < < < <*/


	/*======================================
				 UPLOAD SCORM
	  ======================================*/

	/*
	 * check the request method in place of a variable from POST
	 * because if the file size exceed the maximum file upload
	 * size set in php.ini, all variables from POST are cleared !
	 */

	if ($_SERVER['REQUEST_METHOD'] == 'POST' && count($_FILES)>0 && !$submitImage && !$cancelSubmitImage && !$action)
	{
		// A SCORM upload has been detected, now deal with the file...
		//directory creation

		$s=$_FILES['userFile']['name'];
		$newDirName=substr($s,0,strlen($s)-4);

		$newDirName = replace_dangerous_char(trim($newDirName),'strict');

		if( check_name_exist($baseWorkDir.$newDirPath.$openDir."/".$newDirName) )
		{
			/** @todo: change this output. Inaccurate at least in french. In this case, the
			 *         file might not exist or the transfer might have been wrong (no $_FILES at all)
			 *			   but we still get the error message
			 */
			$dialogBox = get_lang('FileExists');
			$createDir = $newDirPath; unset($newDirPath);// return to step 1
		}
		else
		{
			if(mkdir($baseWorkDir.$newDirPath.$openDir."/".$newDirName, 0700))
			    FileManager::set_default_settings($newDirPath.$openDir, $newDirName, "folder", $dbTable);
				// RH: was:  set_default_settings($newDirPath.$openDir,$newDirName,"folder");
			$dialogBox = get_lang('DirCr');
		}

		//directory creation end

		$uploadPath=$openDir.'/'.$newDirName;

		if(!$_FILES['userFile']['size'])
		{
			$dialogBox .= get_lang('FileError').'<br />'.get_lang('Notice').' : '.get_lang('MaxFileSize').' '.ini_get('upload_max_filesize');
		}
		else
		{
			if($uncompress == 1 && $is_allowedToUnzip)
			{
				$unzip = 'unzip';
			}
			else
			{
				$unzip = '';
			}

			if (treat_uploaded_file($_FILES['userFile'], $baseWorkDir,
									$uploadPath, $maxFilledSpace, $unzip))
			{
				if ($uncompress == 1)
				{
					//$dialogBox .= get_lang('DownloadAndZipEnd');
					//modified by darkden : I omitted this part, so the user can see
					//the scorm content message at once
				}
				else
				{
					$dialogBox = get_lang('DownloadEnd');
				}

				api_item_property_update($_course, TOOL_LEARNPATH, $id, "LearnpathAdded", $_uid);
			}
			else
			{
				if(api_failure::get_last_failure() == 'not_enough_space')
				{
					$dialogBox = get_lang('NoSpace');
				}
				elseif (api_failure::get_last_failure() == 'php_file_in_zip_file')
				{
					$dialogBox = get_lang('ZipNoPhp');
				}
				elseif(api_failure::get_last_failure() == 'not_scorm_content')
				{
					$dialogBox = get_lang('NotScormContent');
				}
			}


		}

		$uploadPath='';
		if (api_failure::get_last_failure())
		{
			rmdir($baseWorkDir.$newDirPath.$openDir."/".$newDirName);
		}

	}// end if is_uploaded_file

	/*======================================
			DELETE FILE OR DIRECTORY
	  ======================================*/

	if ( isset($delete) )
	{
		if ( scorm_delete($baseWorkDir.$delete))
		{
			//$dbTable = substr($dbTable, 1, strlen($dbTable) - 2);  // RH...
			update_db_info("delete", $delete); 
			//$dbTable = "".$dbTable."";
			$dialogBox = get_lang('DocDeleted');
		}
	}

	/*======================================
	   		CREATE DIRECTORY
	  ======================================*/

	/*
	 * The code begin with STEP 2
	 * so it allows to return to STEP 1
	 * if STEP 2 unsucceds
	 */

	/*-------------------------------------
			  		STEP 2
	--------------------------------------*/

	if (isset($newDirPath) && isset($newDirName))
	{
		// echo $newDirPath . $newDirName;

		$newDirName = replace_dangerous_char(trim(stripslashes($newDirName)),'strict');

		if( check_name_exist($baseWorkDir.$newDirPath."/".$newDirName) )
		{
			$dialogBox = get_lang('FileExists');
			$createDir = $newDirPath; unset($newDirPath);// return to step 1
		}
		else
		{
			if(mkdir($baseWorkDir.$newDirPath."/".$newDirName, 0700))
			    FileManager::set_default_settings($newDirPath, $newDirName, "folder", $dbTable);
				// RH: was:  set_default_settings($newDirPath,$newDirName,"folder");
			$dialogBox = get_lang('DirCr');
		}
	}


	/*-------------------------------------
	   			 STEP 1
	  --------------------------------------*/

	if (isset($createDir))
	{
		$dialogBox .=	 "<!-- create dir -->\n"
						."<form>\n"
						."<input type=\"hidden\" name=\"newDirPath\" value=\"$createDir\" />\n"
						.get_lang('NameDir')." : \n"
						."<input type=\"text\" name=\"newDirName\" />\n"
						."<input type=\"submit\" value=\"".get_lang('Ok')."\" />\n"
						."</form>\n";
	}

	/*======================================
	   	  VISIBILITY COMMANDS
	  ======================================*/

	if (isset($make_directory_visible) || isset($make_directory_invisible))
	{
		$visibilityPath = $make_directory_visible.$make_directory_invisible; // At least one of these variables are empty. So it's okay to proceed this way

		/* Check if there is yet a record for this file in the DB */
		$result = mysql_query ("SELECT * FROM $dbTable WHERE path LIKE '".$visibilityPath."'");
		while($row = mysql_fetch_array($result, MYSQL_ASSOC))
		{
			$attribute['path'      ] = $row['path'      ];
			$attribute['visibility'] = $row['visibility'];
			$attribute['comment'   ] = $row['comment'   ];
		}

		if ($make_directory_visible)
		{
			$newVisibilityStatus = "v";
		}
		elseif ($make_directory_invisible)
		{
			$newVisibilityStatus = "i";
		}

		/* commented by Toon, deleting is no longer allowed, all files are in the db
		* if ($attribute['comment'])
		{
			$query = "UPDATE $dbTable SET visibility='$newVisibilityStatus' WHERE path='".$visibilityPath."'";
		}
		elseif ($attribute['visibility']=="i" && $newVisibilityStatus == "v")
		{
			$query="DELETE FROM $dbTable WHERE path='".$visibilityPath."'";
		}
		else
		{
			$query="INSERT INTO $dbTable SET path='".$visibilityPath."', visibility='".$newVisibilityStatus."'";
		}
		*/

		$query = "UPDATE $dbTable SET visibility='$newVisibilityStatus' WHERE path=\"".$visibilityPath."\""; //added by Toon
		api_sql_query($query,__FILE__,__LINE__);
		if (mysql_affected_rows() == 0) // extra check added by Toon, normally not necessary anymore because all files are in the db
		{
			api_sql_query("INSERT INTO $dbTable SET path=\"".$visibilityPath."\", visibility=\"".$newVisibilityStatus."\"",__FILE__,__LINE__);
		}
		unset($attribute);

		$dialogBox = get_lang('ViMod');

	}
} // END is Allowed to Edit

/*======================================
	   DEFINE CURRENT DIRECTORY
  ======================================*/

if (isset($openDir)  || isset($moveTo) || isset($createDir) || isset($newDirPath) || isset($uploadPath) ) // $newDirPath is from createDir command (step 2) and $uploadPath from upload command
{
	$curDirPath = $openDir . $createDir . $moveTo . $newDirPath . $uploadPath;
	/*
	 * NOTE: Actually, only one of these variables is set.
	 * By concatenating them, we eschew a long list of "if" statements
	 */
}
elseif ( isset($delete) || isset($move) || isset($path) || isset($sourceFile) || isset($comment) || isset($commentPath) || isset($make_directory_visible) || isset($make_directory_invisible)) //$sourceFile is from rename command (step 2)
{
	$curDirPath = dirname($delete . $move . $path . $sourceFile . $comment . $commentPath . $make_directory_visible . $make_directory_invisible);
	/*
	 * NOTE: Actually, only one of these variables is set.
	 * By concatenating them, we eschew a long list of "if" statements
	 */
}
else
{
	$curDirPath="";
}
if ($curDirPath == "/" || $curDirPath == "\\" || strstr($curDirPath, ".."))
{
	$curDirPath =""; // manage the root directory problem

	/*
	 * The strstr($curDirPath, "..") prevent malicious users to go to the root directory
	 */
}

$curDirName = basename($curDirPath);
$parentDir  = dirname($curDirPath);

if ($parentDir == "/" || $parentDir == "\\")
{
	$parentDir =""; // manage the root directory problem
}


/*
-----------------------------------------------------------
	Header
-----------------------------------------------------------
*/

Display::display_header($nameTools,"Path");


/*======================================
  SCORM CONTENT OPENER FUNCTION
  ======================================*/

echo "",
"<script type='text/javascript'>\n/* <![CDATA[ */\n",
"openDir='".$openDir."';",
"subdirs='".$subdirs."';",
"if ((openDir != '') && (subdirs != 'yes')) { openscorm(); }",
"function openscorm ()",
"{",
	"w=screen.width-20;",
	"h=screen.height-90;",
	"window.location='showinframes.php?openfirst=yes&indexRoute=".$indexRoute."&file=".urlencode($baseWorkDir.$curDirPath)."/imsmanifest.xml&openDir=".urlencode($curDirPath)."';",

"}",
"\n/* ]]> */\n</script>";

/*======================================
	READ CURRENT DIRECTORY CONTENT
  ======================================*/

/*--------------------------------------
  SEARCHING FILES & DIRECTORIES INFOS
			  ON THE DB
  --------------------------------------*/

/* Search infos in the DB about the current directory the user is in */

$result = mysql_query ("SELECT * FROM $dbTable
						WHERE path LIKE    '".$curDirPath."/%'
						AND   path NOT LIKE '".$curDirPath."/%/%'");

if ($result) while($row = mysql_fetch_array($result, MYSQL_ASSOC))
{
	$attribute['path'      ][] = $row['path'      ];
	$attribute['visibility'][] = $row['visibility'];
	$attribute['comment'   ][] = $row['comment'   ];
}


/*--------------------------------------
  LOAD FILES AND DIRECTORIES INTO ARRAYS
  --------------------------------------*/
$scormcontent=false;

@chdir (realpath($baseWorkDir.$curDirPath))
or die("<center>
	   <b>Wrong directory !</b>
	   <br> Please contact your platform administrator.
	   </center>");
$handle = opendir(".");

define('A_DIRECTORY', 1);
define('A_FILE',      2);


// fill up $fileList for displaying the files list later on
while ($file = readdir($handle))
{
	if ($file == "." || $file == ".." || $file == '.htaccess')
	{
		continue;						// Skip current and parent directories
	}

	$fileList['name'][] = $file;

//	if ($file=='imsmanifest.xml') { $scormcontent=true; }

	if(is_dir($file))
	{
		$fileList['type'][] = A_DIRECTORY;
		$fileList['size'][] = false;
		$fileList['date'][] = false;
	}
	elseif(is_file($file))
	{
		$fileList['type'][] = A_FILE;
		$fileList['size'][] = filesize($file);
		$fileList['date'][] = filectime($file);
	}


	/*
	 * Make the correspondance between
	 * info given by the file system
	 * and info given by the DB
	 */

	if ($attribute)
	{
		$keyAttribute = array_search($curDirPath."/".$file, $attribute['path']);
	}

	if ($keyAttribute !== false)
	{
			$fileList['comment'   ][] = $attribute['comment'   ][$keyAttribute];
			$fileList['visibility'][] = $attribute['visibility'][$keyAttribute];

			unset ($attribute['comment'   ][$keyAttribute],
			 	   $attribute['visibility'][$keyAttribute],
			 	   $attribute['path'      ][$keyAttribute]);
	}
	else
	{
			$fileList['comment'   ][] = false;
			$fileList['visibility'][] = false;
	}
}				// end while ($file = readdir($handle))

/*
 * Sort alphabetically the File list
 */

if ($fileList)
{
	array_multisort($fileList['type'], $fileList['name'],
	   			 $fileList['size'], $fileList['date'],
	   			 $fileList['comment'],$fileList['visibility']);
}

/*----------------------------------------
	CHECK BASE INTEGRITY
--------------------------------------*/


if ( $attribute && ( sizeof($attribute['path']) > 0 ) )
{
	$queryClause = ' WHERE path IN ( "'.implode('" , "' , $attribute['path']).'" )';

	api_sql_query("DELETE FROM $dbTable ".$queryClause,__FILE__,__LINE__);

	api_sql_query("DELETE FROM $dbTable WHERE comment LIKE '' AND visibility LIKE 'v'",__FILE__,__LINE__);
	/* The second query clean the DB 'in case of' empty records (no comment an visibility=v)
	   These kind of records should'nt be there, but we never know... */

}	// end if sizeof($attribute['path']) > 0


closedir($handle);
unset($attribute);


/* > > > > > > END: COMMON TO TEACHERS AND STUDENTS < < < < < < <*/


	/*==========================
	   		 DISPLAY
	  ==========================*/


	$dspCurDirName = htmlentities($curDirName);
	$cmdCurDirPath = rawurlencode($curDirPath);
	$cmdParentDir  = rawurlencode($parentDir);


//api_display_tool_title($nameTools);

/*
-----------------------------------------------------------
	Introduction section
	(editable by course admins)
-----------------------------------------------------------
*/
Display::display_introduction_section(TOOL_LEARNPATH);

if(api_is_allowed_to_edit())
{
 /*--------------------------------------
		  		  UPLOAD SECTION - displays file upload box
	  --------------------------------------*/

		echo	"<!-- upload  -->",
				"<p align=\"right\">",
				"<form action=\"".$_SERVER['PHP_SELF']."?openDir=", rawurlencode($openDir), "&subdirs=$subdirs\" method=\"post\" enctype=\"multipart/form-data\">",
				"<input type=\"hidden\" name=\"uploadPath\" value=\"$curDirPath\" />",
				get_lang('DownloadFile'),"&nbsp;:&nbsp;",
				"<input type=\"file\" name=\"userFile\" />",
				"<input type=\"submit\" value=\"".get_lang('Download')."\" />&nbsp;",
				"</p></form>";

		/*--------------------------------------
	   		DIALOG BOX SECTION
		  --------------------------------------*/

		if ($dialogBox)
		{
			Display::display_normal_message($dialogBox);
		}


			/*--------------------------------------
	  		  EDIT TITLE / DESCRIPTION FOR DOKEOS LP
			  --------------------------------------*/


	if (($action=="add") or ($action=="editpath"))
	{
		if (!$Submit)
			{
			?><td>
			<form name="form1" method="post" action="">
				<h4>
			  <?php
				if ($action=="add")
					{ echo get_lang('_add_learnpath'); }
				else
					{ echo get_lang('_edit_learnpath'); }
				?>
			  <?php
			if ($action=="editpath") {
			  $sql="SELECT * FROM $tbl_learnpath_main WHERE learnpath_id=$id";
			  $result=api_sql_query($sql,__FILE__,__LINE__);
			  $row=mysql_fetch_array($result);
			}
			  ?>
				</h4>
				<table width="400" border="0" cellspacing="2" cellpadding="0">
				 <tr>
				  <td align="right"><?php echo get_lang('_title');?></td>
				  <td><input name="learnpath_name" type="text" value="<?php echo $row["learnpath_name"];?>" size="50" /></td>
					</tr>
					<?php if($show_description_field){ ?>
					<tr>
					  <td align="right" valign="top"><?php echo get_lang('_description');?></td>
					  <td><textarea name='learnpath_description' cols='45'><?php echo $row["learnpath_description"];?></textarea></td>
					</tr>
					<?php } ?>
					<tr>
						<td align="right">&nbsp;</td>
						<input type="hidden" name='action' value='<?php echo $action; ?>' />
						<input type="hidden" name='id' value='<?php echo $id; ?>' />
						<td><input type="submit" name="Submit" value="<?php echo get_lang('Ok'); ?>" /></td>
					</tr>
				</table>
			</form></td>
		<?php
		} 	// 	if (!$submit)
	} 	// if ($action=="add")
	//title and description end

			/*--------------------------------------
	  		  EDIT TITLE / DESCRIPTION FOR SCORM
			  --------------------------------------*/

	if ($action=="editscorm")
	{
		if (!$Submit)
			{
			?><td>
			<form name="form1" method="post" action="">
				<h4>
			  <?php
					echo get_lang('_edit_learnpath');
				?>
			  <?php
			if ($action=="editscorm")
			{
			  $sql="SELECT * FROM $tbl_document WHERE path='$path'";
			  $result=api_sql_query($sql,__FILE__,__LINE__);
			  $row=mysql_fetch_array($result);
			  if ($row['name'])
				  {
					$tmpname=$row['name'];
				  }
			  else
				  {
					$p=strrpos($path,'/');
					$tmpname=substr($path,$p+1,strlen($path));
				  }
			}
			  ?>
				</h4>
				<table width="400" border="0" cellspacing="2" cellpadding="0">
				 <tr>
				  <td align="right"><?php echo get_lang('_title');?></td>
				  <td><input name="learnpath_name" type="text" value="<?php echo $tmpname;?>" size="50" /></td>
					</tr>
					<tr>
					  <td align="right" valign="top"><?php echo get_lang('_description');?></td>
					  <td><textarea name="learnpath_description" cols="45"><?php echo $row["comment"];?></textarea></td>
					</tr>
					<tr>
						<td align="right">&nbsp;</td>
						<input type="hidden" name='action' value='<?php echo $action; ?>' />
						<input type="hidden" name='path' value='<?php echo $path; ?>' />
						<td><input type="submit" name="Submit" value="Submit" /></td>
					</tr>
				</table>
			</form></td>
		<?php
		} 	// 	if (!$submit)
	} 	// if ($action=="editscorm")

	} //	if ( $is_allowedToEdit ) end

	if ( api_is_allowed_to_edit() )
	{
		echo
		"<table border='0' cellspacing='2' cellpadding='4'>
			<tr>
				<td valign='bottom'>
					<a href='".$_SERVER['PHP_SELF']."?action=add'>",
					"<img src='../img/scormbuilder.gif' border=\"0\" align=\"absmiddle\" alt='scormbuilder'>".get_lang('_add_learnpath')."</a>
				</td>",
				"<td valign='bottom'>&nbsp;&nbsp;&nbsp;<a href='".$_SERVER['PHP_SELF']."?createDir=$cmdCurDirPath'>",
					"<img src=\"../img/file.gif\" border=\"0\" align=\"absmiddle\">",
					"",get_lang("CreateDir"),"</a>
				</td>
			</tr>
		</table>";
		//<table>";
	}

	echo "<table width=\"100%\" border=\"0\" cellspacing=\"2\" class='data_table'>";

	api_is_allowed_to_edit() ? $colspan = 9 : $colspan = 3;

	if ($curDirName) /* if the $curDirName is empty, we're in the root point
	   				 and we can't go to a parent dir */
	{
	?>
		<!-- parent dir -->
		 <a href="<?php echo $_SERVER['PHP_SELF'].'?'.api_get_cidreq().'&openDir'.$cmdParentDir.'&subdirs=yes'; ?>">
				<img src="../img/parent.gif" border="0" align="absbottom" hspace="5" alt="parent" />
				<?php echo get_lang("Up"); ?></a>&nbsp;
	<?php
	}

	if ($curDirPath)
	{
		$tmpcurDirPath=substr($curDirPath,1,strlen($curDirPath));
		?>
			<!-- current dir name -->
			<tr>
			<td colspan="<?php echo $colspan ?>" align="left" bgcolor="#4171B5">
				<img src="../img/opendir.gif" align="absbottom" vspace="2" hspace="3" alt="" />
						<font color="#ffffff"><b><?php echo $tmpcurDirPath ?></b></font>
				</td>
			</tr>
		<?php
	}

	/* CURRENT DIRECTORY */


	echo	"<tr>";

	echo	"<th width='290'><b>",get_lang("Name"),"</b></th>\n",
				 "<th><b>",get_lang("Description"),"</b></th>\n";

	if (api_is_allowed_to_edit())
	{
		echo
			    "<th><b>",get_lang("ExportShort"),"</b></th>\n",
				"<th width='200'><b>",get_lang("Modify"),"</b></th>\n";
	}

	echo		"</tr>\n";


	/*--------------------------------------
	   		  DISPLAY SCORM LIST
	  --------------------------------------*/

	if ($fileList)
	{
		$counter=0;

		while (list($fileKey, $fileName) = each ($fileList['name']))
		{
			$counter++;
			if (($counter % 2)==0) { $oddclass="row_odd"; } else { $oddclass="row_even"; }

			if ($fileList['type'][$fileKey] == A_FILE) continue;  // RH: added

    		$dspFileName = htmlentities($fileName);
					$cmdFileName = rawurlencode($curDirPath."/".$fileName);

			if ($fileList['visibility'][$fileKey] == "i")
			{
				if (api_is_allowed_to_edit())
				{
					$style=" class='invisible'";
				}
				else
				{
					continue; // skip the display of this file
				}
			}
			else
			{
				$style="";
			}

			$manifestRoute = $baseWorkDir.$curDirPath."/".$fileName.'/imsmanifest.xml';
			$plantyndir1 = $baseWorkDir.$curDirPath."/".$fileName.'/LMS';
			$plantyndir2 = $baseWorkDir.$curDirPath."/".$fileName.'/REF';
			$plantyndir3 = $baseWorkDir.$curDirPath."/".$fileName.'/SCO';
			$aiccdir = $baseWorkDir.$curDirPath."/".$fileName.'/aicc';
			$indexRoute1 = $indexRoute2 = $indexRouteA = '';
			if ((file_exists($plantyndir1)) and (file_exists($plantyndir2)) and (file_exists($plantyndir3))) {
				$indexRoute1 = $baseWorkDir.$curDirPath."/".$fileName.'/index.htm';
				$indexRoute2 = $baseWorkDir.$curDirPath."/".$fileName.'/index.html';
			}
			if (file_exists($aiccdir)) {
				$indexRouteA = $baseWorkDir.$curDirPath."/".$fileName.'/start.htm';
			}

			if (file_exists($indexRoute1)) {
				$urlFileName = $_SERVER['PHP_SELF'].'?'.api_get_cidreq().'&openDir='.$cmdFileName.'&indexRoute=index.htm';
				$image="<img src=\"./../img/scorm_logo.gif\" border=\"0\" align=\"absmiddle\" alt='scorm'>";
			} elseif (file_exists($indexRoute2)) {
				$urlFileName = $_SERVER['PHP_SELF'].'?'.api_get_cidreq().'&openDir='.$cmdFileName.'&indexRoute=index.html';
				$image="<img src=\"./../img/scorm_logo.gif\" border=\"0\" align=\"absmiddle\" alt='scorm'>";
			} elseif (file_exists($indexRouteA)) {
				$urlFileName = $_SERVER['PHP_SELF'].'?'.api_get_cidreq().'&openDir='.$cmdFileName.'&indexRoute=start.htm';
				$image="<img src=\"./../img/scorm_logo.gif\" border=\"0\" align=\"absmiddle\" alt='scorm'>";
			} elseif (file_exists($manifestRoute)) {
				$urlFileName = $_SERVER['PHP_SELF'].'?'.api_get_cidreq().'&openDir='.$cmdFileName;
				$image="<img src=\"./../img/scorm_logo.gif\" border=\"0\" align=\"absmiddle\" alt='scorm'>";
			} else {
				$urlFileName = $_SERVER['PHP_SELF'].'?'.api_get_cidreq().'&subdirs=yes&openDir='.$cmdFileName;
				$image="<img src=\"../img/file.gif\" border=\"0\"  hspace=\"3\" align=\"absmiddle\" alt='scorm'>";
			}

			if ($curDirPath) {
				$sqlpath=$curDirPath."/".$fileList['name'][$fileKey]."";
			} else {
				$sqlpath="/".$fileList['name'][$fileKey]."";
			}
			$sql="SELECT name FROM $tbl_document WHERE ((path='$sqlpath') and (filetype='folder'))";
			$result=api_sql_query($sql,__FILE__,__LINE__);
			$row=mysql_fetch_array($result);

			if ($row['name']) { $name=$row['name']; } else { $name=$dspFileName; }

			echo	"<tr align=\"center\"", " class=".$oddclass.">\n",
					"<td align=\"left\" valign='middle'>&nbsp;",
					"<a href=\"".$urlFileName."\" ".$style.">",
					"",$image,"</a>&nbsp;<a href=\"".$urlFileName."\" ".$style.">",$name,"</a>",
					"</td>\n";


			/* NB : Before tracking implementation the url above was simply
			 * "<a href=\"",$urlFileName,"\"",$style,">"
			 */

			$desc=$fileList['comment'][$fileKey];

			/* DESCRIPTION */
			echo 	"<td>$desc",
					"</td>\n";

			if(api_is_allowed_to_edit())
			{
				$fileExtension=explode('.',$dspFileName);
				$fileExtension=strtolower($fileExtension[sizeof($fileExtension)-1]);

				/* export */

				echo "<td align='center'><a href='".$_SERVER['PHP_SELF']."?action=exportscorm&".api_get_cidreq()."&path=".$cmdFileName."'><img src=\"../img/save_zip.gif\" border=\"0\" title=\"".get_lang('Export')."\"></a>";

				/* edit title and description */

				echo "<td align='center'>",
				"<a href='".$_SERVER['PHP_SELF']."?action=editscorm&path=".$cmdFileName."'><img src=\"../img/edit.gif\" border=\"0\" title=\"".get_lang('_edit_learnpath')."\"></a>";

				/* DELETE COMMAND */
				echo
						"<a href=\"".$_SERVER['PHP_SELF']."?delete=",$cmdFileName,"\" ",
						"onClick=\"return confirmation('",addslashes($dspFileName),"');\">",
						"<img src=\"../img/delete.gif\" border=\"0\" title=\"".get_lang('_delete_learnpath')."\" />",
						"</a>";


				/* VISIBILITY COMMAND */


				if ($fileList['visibility'][$fileKey] == "i")
				{
					echo	"<a href=\"".$_SERVER['PHP_SELF']."?make_directory_visible=",$cmdFileName,"\">",
							"<img src=\"../img/invisible.gif\" border=\"0\" title=\"".get_lang('_publish')."\" />",
							"</a>";
				}
				else
				{
					echo	"<a href=\"".$_SERVER['PHP_SELF']."?make_directory_invisible=",$cmdFileName,"\">",
							"<img src=\"../img/visible.gif\" border=\"0\" title=\"".get_lang('_no_publish')."\" />",
							"</a>";
				}

			}										// end if($is_allowedToEdit)

			echo	"</tr>\n";

		}				// end each ($fileList)
	}					// end if ( $fileList)

	//display learning paths

	if (!$curDirPath) {

		echo "<tr><td colspan='4'>&nbsp;</td></tr>";
		$sql="select * from $tbl_learnpath_main";
		$result=api_sql_query($sql,__FILE__,__LINE__);
		$counter=0;
		while ($row=mysql_fetch_array($result)) {

			$counter++;
			if (($counter % 2)==0) { $oddclass="row_odd"; } else { $oddclass="row_even"; }

			$id=$row["learnpath_id"];
			$sql2="SELECT * FROM $tbl_learnpath_main where learnpath_id=$id";
			$result2=api_sql_query($sql2,__FILE__,__LINE__);
			$row2=mysql_fetch_array($result2);
			$name=$row2['learnpath_name'];
			$sql3="SELECT * FROM $tbl_tool where (name=\"$name\" and image='scormbuilder.gif')";
			$result3=api_sql_query($sql3,__FILE__,__LINE__);
			$row3=mysql_fetch_array($result3);
			if ((api_is_allowed_to_edit()) or ((!api_is_allowed_to_edit()) and ($row3["visibility"] == '1'))) {
				$row['learnpath_name']=str_replace(' ','&nbsp;',$row['learnpath_name']);
				if ($row3["visibility"] != '1') { $style=' class="invisible"'; } else { $style=''; }
				echo "<tr align=\"center\" class=".$oddclass.">\n",
							    "<td align='left'>&nbsp;",
								"<a href=\"../learnpath/learnpath_handler.php?".api_get_cidreq()."&learnpath_id={$row['learnpath_id']}\" $style>",
								"<img src='../img/scormbuilder.gif' border=\"0\"  alt='scormbuilder'></a>&nbsp;",
								"<a href=\"../learnpath/learnpath_handler.php?".api_get_cidreq()."&learnpath_id={$row['learnpath_id']}\" $style>{$row['learnpath_name']}</a></td>",
								"<td>&nbsp;{$row['learnpath_description']}</td>";
			}

			if(api_is_allowed_to_edit()) {
				//no init of $circle1 here
				echo "<td align='center'><a href='".$_SERVER['PHP_SELF']."?action=exportpath&id=".$row["learnpath_id"]."'><img src=\"../img/save_zip.gif\" border=\"0\" title=\"".get_lang('Export')."\"></a>";

				echo "<td align='center'><a href='".$_SERVER['PHP_SELF']."?action=editpath&id=".$row["learnpath_id"]."'><img src=\"../img/edit.gif\" border=\"0\" title=\"".get_lang('_edit_learnpath')."\"></a>";

				echo "<a href='".$_SERVER['PHP_SELF']."?action=deletepath&id=".$row["learnpath_id"]."'><img src=\"../img/delete.gif\" border=\"0\" title=\"".get_lang('_delete_learnpath')."\" onClick=\"return confirmation('".$row2['learnpath_name']."');\"></a>";

				if (($row3["visibility"])=='1') {
					echo "<a href='".$_SERVER['PHP_SELF']."?action=publishpath&set_visibility=i&id=".$row["learnpath_id"]."'><img src=\"../img/visible.gif\" border=\"0\" alt=\"".get_lang('_no_publish')."\" title=\"".get_lang('_no_publish')."\"></a>";
				} else {
					echo "<a href='".$_SERVER['PHP_SELF']."?action=publishpath&set_visibility=v&id=".$row["learnpath_id"]."'><img 	src=\"../img/invisible.gif\" border=\"0\" alt=\"".get_lang('_publish')."\" title=\"".get_lang('_publish')."\"></a>";
				}
				echo "</td>";

			}
			echo		"</tr>";
		}
	}

echo "</table>";
// echo "</div>"; /* *** end of the div opened earlier, if needed then uncomment*/

echo "<br/><br/>";
/*
==============================================================================
		FOOTER
==============================================================================
*/

Display::display_footer();
?>