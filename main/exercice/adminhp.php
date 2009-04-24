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
*	HotPotatoes administration.
*	@package dokeos.exercise
* 	@author Istvan Mandak
* 	@version $Id: adminhp.php 20089 2009-04-24 21:12:54Z cvargas1 $
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

$is_allowedToEdit=api_is_allowed_to_edit();

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
	api_not_allowed(true);
}

if (isset($_SESSION['gradebook'])){
	$gradebook=	$_SESSION['gradebook'];
}

if (!empty($gradebook) && $gradebook=='view') {	
	$interbreadcrumb[]= array (
			'url' => '../gradebook/'.$_SESSION['gradebook_dest'],
			'name' => get_lang('Gradebook')
		);
}

$interbreadcrumb[]=array("url" => "exercice.php","name" => get_lang('Exercices'));

$nameTools = get_lang('adminHP');

Display::display_header($nameTools,"Exercise");

/** @todo probably wrong !!!! */
require_once(api_get_path(SYS_CODE_PATH).'/exercice/hotpotatoes.lib.php');

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

echo "<form action=\"".api_get_self()."\" method='post' name='form1'>";
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
echo "<button type=\"submit\" class=\"save\" name=\"submit\" value=\"".get_lang('Ok')."\">".get_lang('Ok')."</button>";
echo "<button type=\"button\" class=\"cancel\" name=\"cancel\" value=\"".get_lang('Cancel')."\" onclick=\"javascript:document.form1.newName.value='';\">".get_lang('Cancel')."</button>";

echo "</form>";

?>
<?php

Display::display_footer();

?>
