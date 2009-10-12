<?php // $Id: index.php 16620 2008-10-25 20:03:54Z yannoo $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004-2008 Dokeos SPRL
	Copyright (c) 2003 Ghent University (UGent)

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact: Dokeos, rue Notre Dame, 152, B-1140 Evere, Belgium, info@dokeos.com
==============================================================================
*/

/**
==============================================================================
* This file was origially the copy of document.php, but many modifications happened since then ;
* the direct file view is not any more needed, if the user uploads a scorm zip file, a directory
* will be automatically created for it, and the files will be uncompressed there for example ;
*
* @package dokeos.learnpath
* @author Yannick Warnier <ywarnier@beeznest.org>
==============================================================================
*/
/**
 * Script
 */
// name of the language file that needs to be included
$language_file = "scormdocument";

//flag to allow for anonymous user - needs to be set before global.inc.php
$use_anonymous = true;

require('back_compat.inc.php');
include('learnpath_functions.inc.php');
include_once('scorm.lib.php');
$courseDir   = api_get_course_path().'/scorm';
$baseWordDir = $courseDir;

require_once('learnpathList.class.php');
require_once('learnpath.class.php');
require_once('learnpathItem.class.php');

// storing the tables names in variables.
$tbl_document           = Database::get_course_table(TABLE_SCORMDOC);
$tbl_learnpath_main     = Database::get_course_table(TABLE_LEARNPATH_MAIN);
$tbl_tool               = Database::get_course_table(TABLE_TOOL_LIST);

$default_visibility="v";
$show_description_field=0;

/**
 * Display initialisation and security checks
 */
//extra javascript functions for in html head:
$htmlHeadXtra[] =
"<script language='javascript' type='text/javascript'>
function confirmation(name)
{
	if (confirm(\" ".trim(get_lang('AreYouSureToDelete'))." ?\"))
		{return true;}
	else
		{return false;}
}
</script>";

// Define the 'doc.inc.php' as language file
//$nameTools = get_lang("Doc");

// When GET['learnpath_id'] is defined, it means that
// a learnpath has been chosen, so we redirect to
// learnpath_handler - we possibly lose the $dialogBox warning here
if(!empty($_GET['learnpath_id']))
{
	header('location:../learnpath/learnpath_handler.php?'
		.'learnpath_id='.$_GET['learnpath_id']);
	exit();
}

event_access_tool(TOOL_LEARNPATH);

if (! $is_allowed_in_course) api_not_allowed();

/**
 * Now checks have been done, prepare the data to be displayed
 */
if(!empty($openDir))
{
  //prevent going higher than allowed in the hierarchy
  //if the requested dir is found inside the base course dir, use course dir
  //error_log($courseDir." against ".$openDir,0);
  if((strstr($courseDir,$openDir)===false) and (strstr($openDir,'.')===false) ){
  	$curDirPath = $openDir;
  }else{
  	$curDirPath = $courseDir;
  }
}
else
{
  $curDirPath= $courseDir;
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


/* Search infos in the DB about the current directory the user is in */

$result = mysql_query ("SELECT * FROM $tbl_document
				WHERE path LIKE    '".$curDirPath."/%'
				AND   path NOT LIKE '".$curDirPath."/%/%'");

if ($result) while($row = Database::fetch_array($result, 'ASSOC'))
{
  $attribute['path'      ][] = $row['path'      ];
  $attribute['visibility'][] = $row['visibility'];
  $attribute['comment'   ][] = $row['comment'   ];
}

$fileList = get_scorm_paths_from_dir($baseWorkDir.$curDirPath,$attribute);
if(!isset($fileList)){
	die("<center>
	   <b>Wrong directory !</b>
	   <br> Please contact your platform administrator.
	   </center>");
}
/*
 * Sort alphabetically the File list
 */

if (is_array($fileList) && count($fileList)>0)
{
  array_multisort($fileList['type'], $fileList['name'],
		 $fileList['size'], $fileList['date'],
		 $fileList['comment'],$fileList['visibility']);
}
/*----------------------------------------
	CHECK BASE INTEGRITY
--------------------------------------*/
/* commented until we know what it's for
if ( is_array($attribute) && ( count($attribute['path']) > 0 ) )
{
  $queryClause = ' WHERE path IN ( "'.implode('" , "' , $attribute['path']).'" )';

  Database::query("DELETE FROM $tbl_document ".$queryClause,__FILE__,__LINE__);

  Database::query("DELETE FROM $tbl_document WHERE comment LIKE '' AND visibility LIKE 'v'",__FILE__,__LINE__);
  // The second query clean the DB 'in case of' empty records (no comment an visibility=v)
  // These kind of records should'nt be there, but we never know...

}	// end if sizeof($attribute['path']) > 0
*/

unset($attribute);


/**
 * Display
 */
Display::display_header($nameTools,"Path");
$dspCurDirName = htmlentities($curDirName);
$cmdCurDirPath = rawurlencode($curDirPath);
$cmdParentDir  = rawurlencode($parentDir);

$ob_string = '';

api_display_tool_title($nameTools);

/*
-----------------------------------------------------------
	Introduction section
	(editable by course admins)
-----------------------------------------------------------
*/

if($my_version=='1.8'){
	Display::display_introduction_section(TOOL_LEARNPATH);
}else{
	api_introductionsection(TOOL_LEARNPATH);
}


if(api_is_allowed_to_edit())
{
   /*--------------------------------------
      UPLOAD SECTION - displays file upload box
     --------------------------------------*/

  echo	"<!-- upload  -->",
	"<p align=\"right\">",
	"<form action=\"".api_get_self()."?openDir=", rawurlencode($openDir),
		"&subdirs=$subdirs\" method=\"post\" enctype=\"multipart/form-data\">",
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

  echo	"<table border='0' cellspacing='2' cellpadding='4'>
    <tr>
      <td valign='bottom'>
        <a href='".api_get_self()."?action=add'>",
	"<img src='../img/scormbuilder.gif' border=\"0\" align=\"absmiddle\" alt='scormbuilder' />".get_lang('_add_learnpath')."</a>
      </td>",
      "<td valign='bottom'>&nbsp;&nbsp;&nbsp;<a href='".api_get_self()."?createDir=$cmdCurDirPath'>",
      "<img src=\"../img/dossier.gif\" border=\"0\" align=\"absmiddle\" />",
	"",get_lang("CreateDir"),"</a>
      </td>
    </tr>
  </table>";


}

echo "<table width=\"100%\" border=\"0\" cellspacing=\"2\" class='data_table'>";
api_is_allowed_to_edit() ? $colspan = 9 : $colspan = 3;

if ($curDirName) /* if the $curDirName is empty, we're in the root point
   				 and we can't go to a parent dir */
{
  ?>
  <!-- parent dir -->
  <a href="<?php echo api_get_self().'?'.api_get_cidreq().'&openDir='.$cmdParentDir.'&subdirs=yes'; ?>">
  <img src="../img/folder_up.gif" border="0" align="absbottom" hspace="5" alt="parent" />
  <?php echo get_lang("Up"); ?></a>&nbsp;
  <?php
}

if ($curDirPath)
{
  if(substr($curDirPath,1,1)=='/'){
  	$tmpcurDirPath=substr($curDirPath,1,strlen($curDirPath));
  }else{
  	$tmpcurDirPath = $curDirPath;
  }
  ?>
  <!-- current dir name -->
  <tr>
    <td colspan="<?php echo $colspan ?>" align="left" bgcolor="#4171B5">
      <img src="../img/opendir.gif" align="absbottom" vspace="2" hspace="3" alt="open_dir" />
      <?php echo $tmpcurDirPath ?>
    </td>
  </tr>
  <?php
}

/* CURRENT DIRECTORY */

echo	"<tr bgcolor=\"$color2\" align=\"center\" valign=\"top\">";
echo	"<td width='290'><b>",get_lang("Name"),"</b></td>\n",
	 "<td><b>",get_lang("Description"),"</b></td>\n";
if (api_is_allowed_to_edit())
{
  echo "<td><b>",get_lang("ExportShort"),"</b></td>\n",
	"<td width='200'><b>",get_lang("Modify"),"</b></td>\n";
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
      $urlFileName = api_get_self().'?'.api_get_cidreq().'&openDir='.$cmdFileName.'&indexRoute=index.htm';
      $image="<img src=\"./../img/scorm_logo.gif\" border=\"0\" align=\"absmiddle\" alt='scorm' />";
    } elseif (file_exists($indexRoute2)) {
      $urlFileName = api_get_self().'?'.api_get_cidreq().'&openDir='.$cmdFileName.'&indexRoute=index.html';
      $image="<img src=\"./../img/scorm_logo.gif\" border=\"0\" align=\"absmiddle\" alt='scorm'>";
    } elseif (file_exists($indexRouteA)) {
      $urlFileName = api_get_self().'?'.api_get_cidreq().'&openDir='.$cmdFileName.'&indexRoute=start.htm';
      $image="<img src=\"./../img/scorm_logo.gif\" border=\"0\" align=\"absmiddle\" alt='scorm'>";
    } elseif (file_exists($manifestRoute)) {
      $urlFileName = api_get_self().'?'.api_get_cidreq().'&openDir='.$cmdFileName;
      $image="<img src=\"./../img/scorm_logo.gif\" border=\"0\" align=\"absmiddle\" alt='scorm'>";
    } else {
      $urlFileName = api_get_self().'?'.api_get_cidreq().'&subdirs=yes&openDir='.$cmdFileName;
      $image="<img src=\"../img/dossier.gif\" border=\"0\"  hspace=\"3\" align=\"absmiddle\" alt='scorm'>";
    }

    if ($curDirPath) {
      $sqlpath=$curDirPath."/".$fileList['name'][$fileKey]."";
    } else {
      $sqlpath="/".$fileList['name'][$fileKey]."";
    }
    $sql="SELECT name FROM $tbl_document WHERE ((path='$sqlpath') and (filetype='folder'))";
    $result=Database::query($sql,__FILE__,__LINE__);
    $row=Database::fetch_array($result);
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

     echo "<td align='center'><a href='".api_get_self()."?action=exportscorm&".api_get_cidreq()."&path=".$cmdFileName."'><img src=\"../img/save_zip.gif\" border=\"0\" title=\"".get_lang('Export')."\"></a>";

     /* edit title and description */

      echo "<td align='center'>",
	"<a href='".api_get_self()."?action=editscorm&path=".$cmdFileName."'><img src=\"../img/edit.gif\" border=\"0\" title=\"".get_lang('_edit_learnpath')."\"></a>";

      /* DELETE COMMAND */
      echo
	"<a href=\"".api_get_self()."?delete=",$cmdFileName,"\" ",
	"onClick=\"return confirmation('",addslashes($dspFileName),"');\">",
	"<img src=\"../img/delete.gif\" border=\"0\" title=\"".get_lang('_delete_learnpath')."\" />",
	"</a>";

      /* VISIBILITY COMMAND */

      if ($fileList['visibility'][$fileKey] == "i")
      {
        echo	"<a href=\"".api_get_self()."?make_directory_visible=",$cmdFileName,"\">",
	  "<img src=\"../img/invisible.gif\" border=\"0\" title=\"".get_lang('_publish')."\" />",
	  "</a>";
      }
      else
      {
        echo	"<a href=\"".api_get_self()."?make_directory_invisible=",$cmdFileName,"\">",
	  "<img src=\"../img/visible.gif\" border=\"0\" title=\"".get_lang('_no_publish')."\" />",
	  "</a>";
      }

    }	// end if($is_allowedToEdit)
    echo	"</tr>\n";

  }	// end each ($fileList)
}// end if ( $fileList)

//display learning paths

if (!$curDirPath) {

  echo "<tr><td colspan='4'>&nbsp;</td></tr>";
  $sql="select * from $tbl_learnpath_main";
  $result=Database::query($sql,__FILE__,__LINE__);
  $counter=0;
  while ($row=Database::fetch_array($result)) {
    $counter++;
    if (($counter % 2)==0) { $oddclass="row_odd"; } else { $oddclass="row_even"; }

    $id=$row["learnpath_id"];
    $sql2="SELECT * FROM $tbl_learnpath_main where learnpath_id=$id";
    $result2=Database::query($sql2,__FILE__,__LINE__);
    $row2=Database::fetch_array($result2);
    $name=$row2['learnpath_name'];
    $sql3="SELECT * FROM $tbl_tool where (name=\"$name\" and image='scormbuilder.gif')";
    $result3=Database::query($sql3,__FILE__,__LINE__);
    $row3=Database::fetch_array($result3);
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
      echo "<td align='center'><a href='".api_get_self()."?action=exportpath&id=".$row["learnpath_id"]."'><img src=\"../img/save_zip.gif\" border=\"0\" title=\"".get_lang('Export')."\"></a>";

      echo "<td align='center'><a href='".api_get_self()."?action=editpath&id=".$row["learnpath_id"]."'><img src=\"../img/edit.gif\" border=\"0\" title=\"".get_lang('_edit_learnpath')."\"></a>";

      echo "<a href='".api_get_self()."?action=deletepath&id=".$row["learnpath_id"]."'><img src=\"../img/delete.gif\" border=\"0\" title=\"".get_lang('_delete_learnpath')."\" onClick=\"return confirmation('".$row2['learnpath_name']."');\"></a>";

      if (($row3["visibility"])=='1') {
        echo "<a href='".api_get_self()."?action=publishpath&set_visibility=i&id=".$row["learnpath_id"]."'><img src=\"../img/visible.gif\" border=\"0\" alt=\"".get_lang('_no_publish')."\" title=\"".get_lang('_no_publish')."\"></a>";
      } else {
        echo "<a href='".api_get_self()."?action=publishpath&set_visibility=v&id=".$row["learnpath_id"]."'><img 	src=\"../img/invisible.gif\" border=\"0\" alt=\"".get_lang('_publish')."\" title=\"".get_lang('_publish')."\"></a>";
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
