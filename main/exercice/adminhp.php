<?php // $Id: adminhp.php 10774 2007-01-17 21:24:24Z pcool $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004 Dokeos S.A.
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	Copyright (c) Olivier Brouckaert

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
 *	HOTPOTATOES ADMINISTRATION
 *	@author Istvan Mandak
 *	@package dokeos.exercise
 */


include('exercise.class.php');
include('question.class.php');
include('answer.class.php');

include('exercise.lib.php');

// name of the language file that needs to be included
$language_file='exercice';

include('../inc/global.inc.php');
$this_section=SECTION_COURSES;

if(isset($_REQUEST["cancel"]))
{
	if($_REQUEST["cancel"]==get_lang('Cancel'))
	{
				header("Location: exercice.php");
	}
}

//$is_courseAdmin = $_SESSION['is_courseAdmin'];
$newName = (!empty($_REQUEST['newName'])?$_REQUEST['newName']:'');
$hotpotatoesName = (!empty($_REQUEST['hotpotatoesName'])?$_REQUEST['hotpotatoesName']:'');

// answer types
define(UNIQUE_ANSWER,	1);
define(MULTIPLE_ANSWER,	2);
define(FILL_IN_BLANKS,	3);
define(MATCHING,		4);
define(FREE_ANSWER,     5);

// allows script inclusions
define(ALLOWED_TO_INCLUDE,1);

$is_allowedToEdit=$is_courseAdmin;

// document path
$documentPath=api_get_path(SYS_COURSE_PATH).$_course['path'].'/document';

// picture path
$picturePath=$documentPath.'/images';

// audio path
$audioPath=$documentPath.'/audio';



// Database table definitions
$TBL_EXERCICE_QUESTION		= Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);
$TBL_EXERCICES				= Database::get_course_table(TABLE_QUIZ_TEST);
$TBL_QUESTIONS				= Database::get_course_table(TABLE_QUIZ_QUESTION);
$TBL_REPONSES				= Database::get_course_table(TABLE_QUIZ_ANSWER);
$TBL_DOCUMENT				= Database::get_course_table(TABLE_DOCUMENT);
$dbTable					= $TBL_DOCUMENT;

if(!$is_allowedToEdit)
{
	api_not_allowed();
}

$interbreadcrumb[]=array("url" => "exercice.php","name" => get_lang('Exercices'));

$nameTools = get_lang('adminHP');

Display::display_header($nameTools,"Exercise");

/** @todo probably wrong !!!! */
require_once(api_get_path(SYS_PATH).'claroline/exercice/hotpotatoes.lib.php');

?>

<h4>
  <?php echo $nameTools; ?>
</h4>

<?php
if(isset($newName))
{
		if($newName!="")
		{
			//alter database record for that test
			SetComment($hotpotatoesName,$newName);
			echo "<script language='Javascript' type='text/javascript'> window.location='exercice.php'; </script>";
		}
}

echo "<form action=\"{$_SERVER['PHP_SELF']}\" method='post' name='form1'>";
echo "<input type=\"hidden\" name=\"hotpotatoesName\" value=\"$hotpotatoesName\">";
echo "<input type=\"text\" name=\"newName\" value=\"";


$lstrComment = "";
$lstrComment = GetComment($hotpotatoesName);
if($lstrComment=="")
	$lstrComment = GetQuizName($hotpotatoesName,$documentPath);
if($lstrComment=="")
	$lstrComment = GetFileName($hotpotatoesName,$documentPath);

echo $lstrComment;
echo "\" size=40>&nbsp;";
echo "<input type=\"submit\" name=\"submit\" value=\"".get_lang('Ok')."\">&nbsp;";
echo "<input type=\"button\" name=\"cancel\" value=\"".get_lang('Cancel')."\" onclick=\"javascript:document.form1.newName.value='';\">";

echo "</form>";

?>
<?php

Display::display_footer();

?>
