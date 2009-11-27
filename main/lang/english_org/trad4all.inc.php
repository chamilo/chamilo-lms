<?php // $Id: trad4all.inc.php 3585 2005-03-03 11:31:13Z olivierb78 $
/*
	  +----------------------------------------------------------------------+
	  | CLAROLINE version 1.4.* $Revision: 3585 $                            |
	  +----------------------------------------------------------------------+
	  | Copyright (c) 2001, 2003 Universite catholique de Louvain (UCL)      |
	  +----------------------------------------------------------------------+
	  |   English Translation                                                |
	  +----------------------------------------------------------------------+
	  |   This program is free software; you can redistribute it and/or      |
	  |   modify it under the terms of the GNU General Public License        |
	  |   as published by the Free Software Foundation; either version 2     |
	  |   of the License, or (at your option) any later version.             |
	  +----------------------------------------------------------------------+
	  | Authors: Thomas Depraetere <depraetere@ipm.ucl.ac.be>                |
	  |          Hugues Peeters    <peeters@ipm.ucl.ac.be>                   |
	  |          Christophe Gesché <gesche@ipm.ucl.ac.be>                    |
	  |          Olivier Brouckaert <oli.brouckaert@skynet.be>               |
	  +----------------------------------------------------------------------+
	  | Translator :                                                         |
	  |          Thomas Depraetere <depraetere@ipm.ucl.ac.be>                |
	  |          Andrew Lynn       <Andrew.Lynn@strath.ac.uk>                |
	  |          Olivier Brouckaert <oli.brouckaert@skynet.be>               |
	  +----------------------------------------------------------------------+
*/

$iso639_2_code = "en";
$iso639_1_code = "eng";

$langNameOfLang['arabic']="arabic";
$langNameOfLang['brazilian']="brazilian";
$langNameOfLang['bulgarian']="bulgarian";
$langNameOfLang['catalan']="catalan";
$langNameOfLang['croatian']="croatian";
$langNameOfLang['danish']="danish";
$langNameOfLang['dutch']="dutch";
$langNameOfLang['english']="english";
$langNameOfLang['finnish']="finnish";
$langNameOfLang['french']="french";
$langNameOfLang['french_corporate']="french_corporate";
$langNameOfLang['french_KM']="french_KM";
$langNameOfLang['galician']="galician";
$langNameOfLang['german']="german";
$langNameOfLang['greek']="greek";
$langNameOfLang['italian']="italian";
$langNameOfLang['japanese']="japanese";
$langNameOfLang['polish']="polish";
$langNameOfLang['portuguese']="portuguese";
$langNameOfLang['russian']="russian";
$langNameOfLang['simpl_chinese']="simpl_chinese";
$langNameOfLang['spanish']="spanish";
$langNameOfLang['spanish_latin']="spanish_latin";
$langNameOfLang['swedish']="swedish";
$langNameOfLang['thai']="thai";
$langNameOfLang['turkce']="turkish";
$langNameOfLang['vietnamese']="vietnamese";

$charset = 'iso-8859-1';
$text_dir = 'ltr'; // ('ltr' for left to right, 'rtl' for right to left)
$left_font_family = 'verdana, helvetica, arial, geneva, sans-serif';
$right_font_family = 'helvetica, arial, geneva, sans-serif';
$number_thousands_separator = ',';
$number_decimal_separator = '.';
$byteUnits = array('Bytes', 'KB', 'MB', 'GB');

$langDay_of_weekNames['init'] = array('S', 'M', 'T', 'W', 'T', 'F', 'S');
$langDay_of_weekNames['short'] = array('Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat');
$langDay_of_weekNames['long'] = array('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday');

$langMonthNames['init']  = array('J', 'F', 'M', 'A', 'M', 'J', 'J', 'A', 'S', 'O', 'N', 'D');
$langMonthNames['short'] = array('Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');
$langMonthNames['long'] = array('January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');

// Voir http://www.php.net/manual/en/function.strftime.php pour la variable
// ci-dessous

$dateFormatShort =  "%b %d, %y";
$dateFormatLong  = '%A %B %d, %Y';
$dateTimeFormatLong  = '%B %d, %Y at %I:%M %p';
$timeNoSecFormat = '%I:%M %p';

// GENERIC

$langYes="Yes";
$langNo="No";
$langBack="Back";
$langNext="Next";
$langAllowed="Allowed";
$langDenied="Denied";
$langBackHome="Back to home";
$langPropositions="Proposals for an improvement of";
$langMaj="Update";
$langModify="Modify";
$langDelete="Delete";
$langVisible="Make visible";
$langInvisible="Make invisible";
$langSave="Save";
$langMove="Move";
$langTitle="Title";
$langHelp="Help";
$langOk="OK";
$langAdd="Add";
$langAddIntro="Add introduction text";
$langBackList="Return to the list";
$langText="Text";
$langEmpty="Empty";
$langConfirmYourChoice="Please confirm your choice";
$langAnd="and";
$langChoice="Your choice";
$langFinish="Finish";
$langCancel="Cancel";
$langNotAllowed="You are not allowed here or your session has timed out. Please login again.";
$langNotLogged="You are not logged on a area";
$langManager="Manager";
$langPlatform="Powered by";
$langOptional="Optional";
$langNextPage="Next page";
$langPreviousPage="Previous page";
$langUse="Use";
$langTotal="Total";
$langTake="take";
$langOne="One";
$langSeveral="Several";
$langNotice="Notice";
$langDate="Date";
$langAmong="among";
$langShow="Show";

// banner

$langMyCourses="My areas list";
$langModifyProfile="My profile";
$langMyStats = "View my statistics";
$langLogout="Logout";
$langMyStats = "View my statistics";
$langMyAgenda = "My agenda";
$langCourseHomepage="Area Homepage";

//needed for member view
$langCourseManagerview = "Leader View";
$langStudentView = "Member View";

//needed for resource linker
$lang_add_resource="Add it";  //this should be the same as in lang/english/resourcelinker.inc.php 
$lang_added_resources="Resources added";
$lang_modify_resource="Modify / Add resources";
$lang_resource="Resource";
$lang_resources="Resources";
$lang_attachment="Attachment";
?>