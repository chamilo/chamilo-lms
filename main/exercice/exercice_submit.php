<?php
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2008 Dokeos SPRL

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact address: Dokeos, rue du Corbeau, 108, B-1030 Brussels, Belgium
	Mail: info@dokeos.com
==============================================================================
*/
/**
*	Exercise submission
* 	This script allows to run an exercise. According to the exercise type, questions
* 	can be on an unique page, or one per page with a Next button.
*
* 	One exercise may contain different types of answers (unique or multiple selection,
* 	matching, fill in blanks, free answer, hot-spot).
*
* 	Questions are selected randomly or not.
*
* 	When the user has answered all questions and clicks on the button "Ok",
* 	it goes to exercise_result.php
*
* 	Notice : This script is also used to show a question before modifying it by
* 	the administrator
*	@package dokeos.exercise
* 	@author Olivier Brouckaert
* 	@author Julio Montoya multiple fill in blank option added
* 	@version $Id: exercice_submit.php 15845 2008-07-24 19:21:23Z dperales $
*/


include('exercise.class.php');
include('question.class.php');
include('answer.class.php');

include('exercise.lib.php');

// debug var. Set to 0 to hide all debug display. Set to 1 to display debug messages.
$debug = 0;

// answer types
define('UNIQUE_ANSWER',		1);
define('MULTIPLE_ANSWER',	2);
define('FILL_IN_BLANKS',	3);
define('MATCHING',			4);
define('FREE_ANSWER', 		5);
define('HOT_SPOT', 			6);
define('HOT_SPOT_ORDER', 	7);

// name of the language file that needs to be included
$language_file='exercice';

include_once('../inc/global.inc.php');
$this_section=SECTION_COURSES;

/* ------------	ACCESS RIGHTS ------------ */
// notice for unauthorized people.
api_protect_course_script(true);

include_once(api_get_path(LIBRARY_PATH).'text.lib.php');

$is_allowedToEdit=api_is_allowed_to_edit();

$TBL_EXERCICE_QUESTION = Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);
$TBL_EXERCICES         = Database::get_course_table(TABLE_QUIZ_TEST);
$TBL_QUESTIONS         = Database::get_course_table(TABLE_QUIZ_QUESTION);
$TBL_REPONSES          = Database::get_course_table(TABLE_QUIZ_ANSWER);

// general parameters passed via POST/GET

if ( empty ( $origin ) ) {
    $origin = $_REQUEST['origin'];
}
if ( empty ( $learnpath_id ) ) {
    $learnpath_id       = Database::escape_string($_REQUEST['learnpath_id']);
}
if ( empty ( $learnpath_item_id ) ) {
    $learnpath_item_id  = Database::escape_string($_REQUEST['learnpath_item_id']);
}
if ( empty ( $formSent ) ) {
    $formSent       = $_REQUEST['formSent'];
}
if ( empty ( $exerciseResult ) ) {
    $exerciseResult = $_REQUEST['exerciseResult'];
}

if ( empty ( $exerciseResultCoordinates ) ) {
    $exerciseResultCoordinates = $_REQUEST['exerciseResultCoordinates'];
}

if ( empty ( $exerciseType ) ) {
    $exerciseType = $_REQUEST['exerciseType'];
}
if ( empty ( $exerciseId ) ) {
    $exerciseId = intval($_REQUEST['exerciseId']);
}
if ( empty ( $choice ) ) {
    $choice = $_REQUEST['choice'];
}
if ( empty ( $questionNum ) ) {
    $questionNum    = Database::escape_string($_REQUEST['questionNum']);
}
if ( empty ( $nbrQuestions ) ) {
    $nbrQuestions   = Database::escape_string($_REQUEST['nbrQuestions']);
}
if ( empty ($buttonCancel) ) {
	$buttonCancel 	= $_REQUEST['buttonCancel'];
}
$error = '';

// if the user has clicked on the "Cancel" button
if($buttonCancel)
{
	// returns to the exercise list
	header("Location: exercice.php?origin=$origin&learnpath_id=$learnpath_id&learnpath_item_id=$learnpath_item_id");
	exit();
}

if ($origin=='builder') 
{
	/*******************************/
	/* Clears the exercise session */
	/*******************************/
	if(isset($_SESSION['objExercise']))		{ api_session_unregister('objExercise');	unset($objExercise); }
	if(isset($_SESSION['objQuestion']))		{ api_session_unregister('objQuestion');	unset($objQuestion); }
	if(isset($_SESSION['objAnswer']))		{ api_session_unregister('objAnswer');		unset($objAnswer);   }
	if(isset($_SESSION['questionList']))	{ api_session_unregister('questionList');	unset($questionList); }
	if(isset($_SESSION['exerciseResult']))	{ api_session_unregister('exerciseResult');	unset($exerciseResult); }
	if(isset($_SESSION['exerciseResultCoordinates']))	{ api_session_unregister('exerciseResultCoordinates');	unset($exerciseResultCoordinates); }
}

// if the user has submitted the form
if($formSent)
{
    if($debug>0){echo str_repeat('&nbsp;',0).'$formSent was set'."<br />\n";}

    // initializing
    if(!is_array($exerciseResult))
    {
        $exerciseResult=array();
        $exerciseResultCoordinates=array();
    }

    // if the user has answered at least one question
    if(is_array($choice))
    {	
        if($debug>0){echo str_repeat('&nbsp;',0).'$choice is an array'."<br />\n";}

        if($exerciseType == 1)
        {
            // $exerciseResult receives the content of the form.
            // Each choice of the student is stored into the array $choice
            $exerciseResult=$choice;

            if (isset($_POST['hotspot']))
            {
            	$exerciseResultCoordinates = $_POST['hotspot'];
            }
        }
        else
        {
            // gets the question ID from $choice. It is the key of the array
            list($key)=array_keys($choice);

            // if the user didn't already answer this question
            if(!isset($exerciseResult[$key]))
            {
                // stores the user answer into the array
                $exerciseResult[$key]=$choice[$key];

                if (isset($_POST['hotspot']))
                {
                	$exerciseResultCoordinates[$key] = $_POST['hotspot'][$key];
                }
            }
        }
        if($debug>0){echo str_repeat('&nbsp;',0).'$choice is an array - end'."<br />\n";}
    }

    // the script "exercise_result.php" will take the variable $exerciseResult from the session
    api_session_register('exerciseResult');
    api_session_register('exerciseResultCoordinates');

    // if it is the last question (only for a sequential exercise)
    if($exerciseType == 1 || $questionNum >= $nbrQuestions)
    {	
        if($debug>0){echo str_repeat('&nbsp;',0).'Redirecting to exercise_result.php - Remove debug option to let this happen'."<br />\n";}
		 // goes to the script that will show the result of the exercise
        header("Location: exercise_result.php?origin=$origin&learnpath_id=$learnpath_id&learnpath_item_id=$learnpath_item_id");
        exit();
    }
    if($debug>0){echo str_repeat('&nbsp;',0).'$formSent was set - end'."<br />\n";}
}

// if the object is not in the session
if(!isset($_SESSION['objExercise']) || $origin == 'learnpath' || $_SESSION['objExercise']->id != $_REQUEST['exerciseId'])
{
    if($debug>0){echo str_repeat('&nbsp;',0).'$_SESSION[objExercise] was unset'."<br />\n";}
    // construction of Exercise
    $objExercise=new Exercise();
    unset($_SESSION['questionList']);
	
    // if the specified exercise doesn't exist or is disabled
    if(!$objExercise->read($exerciseId) || (!$objExercise->selectStatus() && !$is_allowedToEdit && ($origin != 'learnpath') ))
    {
    	unset($objExercise);
    	$error = get_lang('ExerciseNotFound');
        //die(get_lang('ExerciseNotFound'));
    }
    else
    {
	    // saves the object into the session
	    api_session_register('objExercise');
	    if($debug>0){echo str_repeat('&nbsp;',0).'$_SESSION[objExercise] was unset - set now - end'."<br />\n";}
    }
}

if(!isset($objExcercise) && isset($_SESSION['objExercise'])){
	$objExercise = $_SESSION['objExercise'];
}
if(!is_object($objExercise))
{
	header('Location: exercice.php');
	exit();
}
$quizID = $objExercise->selectId();
$exerciseAttempts=$objExercise->selectAttempts();
$exerciseTitle=$objExercise->selectTitle();
$exerciseDescription=$objExercise->selectDescription();
$exerciseDescription=stripslashes($exerciseDescription);
$exerciseSound=$objExercise->selectSound();
$randomQuestions=$objExercise->isRandom();
$exerciseType=$objExercise->selectType();

if(!isset($_SESSION['questionList']) || $origin == 'learnpath')
{
    if($debug>0){echo str_repeat('&nbsp;',0).'$_SESSION[questionList] was unset'."<br />\n";}
    // selects the list of question ID
    $questionList = ($randomQuestions?$objExercise->selectRandomList():$objExercise->selectQuestionList());
    // saves the question list into the session
    api_session_register('questionList');
    if($debug>0){echo str_repeat('&nbsp;',0).'$_SESSION[questionList] was unset - set now - end'."<br />\n";}
}
if(!isset($objExcercise) && isset($_SESSION['objExercise'])){
	$questionList = $_SESSION['questionList'];
}

$nbrQuestions=sizeof($questionList);

// if questionNum comes from POST and not from GET
if(!$questionNum || $_POST['questionNum'])
{
    // only used for sequential exercises (see $exerciseType)
    if(!$questionNum)
    {
        $questionNum=1;
    }
    else
    {
        $questionNum++;
    }
}

//$nameTools=get_lang('Exercice');

$interbreadcrumb[]=array("url" => "exercice.php","name" => get_lang('Exercices'));
$interbreadcrumb[]=array("url" => "#","name" => $exerciseTitle);

if ($origin != 'learnpath') { //so we are not in learnpath tool

$htmlHeadXtra[] = "<script type=\"text/javascript\" src=\"../plugin/hotspot/JavaScriptFlashGateway.js\"></script>
					<script src=\"../plugin/hotspot/hotspot.js\" type=\"text/javascript\"></script>
					<script language=\"JavaScript\" type=\"text/javascript\">
					<!--
					// -----------------------------------------------------------------------------
					// Globals
					// Major version of Flash required
					var requiredMajorVersion = 7;
					// Minor version of Flash required
					var requiredMinorVersion = 0;
					// Minor version of Flash required
					var requiredRevision = 0;
					// the version of javascript supported
					var jsVersion = 1.0;
					// -----------------------------------------------------------------------------
					// -->
					</script>
					<script language=\"VBScript\" type=\"text/vbscript\">
					<!-- // Visual basic helper required to detect Flash Player ActiveX control version information
					Function VBGetSwfVer(i)
					  on error resume next
					  Dim swControl, swVersion
					  swVersion = 0

					  set swControl = CreateObject(\"ShockwaveFlash.ShockwaveFlash.\" + CStr(i))
					  if (IsObject(swControl)) then
					    swVersion = swControl.GetVariable(\"\$version\")
					  end if
					  VBGetSwfVer = swVersion
					End Function
					// -->
					</script>

					<script language=\"JavaScript1.1\" type=\"text/javascript\">
					<!-- // Detect Client Browser type
					var isIE  = (navigator.appVersion.indexOf(\"MSIE\") != -1) ? true : false;
					var isWin = (navigator.appVersion.toLowerCase().indexOf(\"win\") != -1) ? true : false;
					var isOpera = (navigator.userAgent.indexOf(\"Opera\") != -1) ? true : false;
					jsVersion = 1.1;
					// JavaScript helper required to detect Flash Player PlugIn version information
					function JSGetSwfVer(i){
						// NS/Opera version >= 3 check for Flash plugin in plugin array
						if (navigator.plugins != null && navigator.plugins.length > 0) {
							if (navigator.plugins[\"Shockwave Flash 2.0\"] || navigator.plugins[\"Shockwave Flash\"]) {
								var swVer2 = navigator.plugins[\"Shockwave Flash 2.0\"] ? \" 2.0\" : \"\";
					      		var flashDescription = navigator.plugins[\"Shockwave Flash\" + swVer2].description;
								descArray = flashDescription.split(\" \");
								tempArrayMajor = descArray[2].split(\".\");
								versionMajor = tempArrayMajor[0];
								versionMinor = tempArrayMajor[1];
								if ( descArray[3] != \"\" ) {
									tempArrayMinor = descArray[3].split(\"r\");
								} else {
									tempArrayMinor = descArray[4].split(\"r\");
								}
					      		versionRevision = tempArrayMinor[1] > 0 ? tempArrayMinor[1] : 0;
					            flashVer = versionMajor + \".\" + versionMinor + \".\" + versionRevision;
					      	} else {
								flashVer = -1;
							}
						}
						// MSN/WebTV 2.6 supports Flash 4
						else if (navigator.userAgent.toLowerCase().indexOf(\"webtv/2.6\") != -1) flashVer = 4;
						// WebTV 2.5 supports Flash 3
						else if (navigator.userAgent.toLowerCase().indexOf(\"webtv/2.5\") != -1) flashVer = 3;
						// older WebTV supports Flash 2
						else if (navigator.userAgent.toLowerCase().indexOf(\"webtv\") != -1) flashVer = 2;
						// Can't detect in all other cases
						else {

							flashVer = -1;
						}
						return flashVer;
					}
					// When called with reqMajorVer, reqMinorVer, reqRevision returns true if that version or greater is available
					function DetectFlashVer(reqMajorVer, reqMinorVer, reqRevision)
					{
					 	reqVer = parseFloat(reqMajorVer + \".\" + reqRevision);
					   	// loop backwards through the versions until we find the newest version
						for (i=25;i>0;i--) {
							if (isIE && isWin && !isOpera) {
								versionStr = VBGetSwfVer(i);
							} else {
								versionStr = JSGetSwfVer(i);
							}
							if (versionStr == -1 ) {
								return false;
							} else if (versionStr != 0) {
								if(isIE && isWin && !isOpera) {
									tempArray         = versionStr.split(\" \");
									tempString        = tempArray[1];
									versionArray      = tempString .split(\",\");
								} else {
									versionArray      = versionStr.split(\".\");
								}
								versionMajor      = versionArray[0];
								versionMinor      = versionArray[1];
								versionRevision   = versionArray[2];

								versionString     = versionMajor + \".\" + versionRevision;   // 7.0r24 == 7.24
								versionNum        = parseFloat(versionString);
					        	// is the major.revision >= requested major.revision AND the minor version >= requested minor
								if ( (versionMajor > reqMajorVer) && (versionNum >= reqVer) ) {
									return true;
								} else {
									return ((versionNum >= reqVer && versionMinor >= reqMinorVer) ? true : false );
								}
							}
						}
					}
					// -->
					</script>";
	Display::display_header($nameTools,"Exercise");
}
else
{
	if(empty($charset))
	{
		$charset = 'ISO-8859-15';
	}
	header('Content-Type: text/html; charset='. $charset);

	@$document_language = Database::get_language_isocode($language_interface);
	if(empty($document_language))
	{
	  //if there was no valid iso-code, use the english one
	  $document_language = 'en';
	}

	/*
	 * HTML HEADER
	 */

?>
<!DOCTYPE html
     PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
     "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $document_language; ?>" lang="<?php echo $document_language; ?>">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset; ?>" />
</head>

<body>
<link rel="stylesheet" type="text/css" href="<?php echo api_get_path(WEB_CODE_PATH).'css/'.api_get_setting('stylesheets').'/frames.css'; ?>" />

<?php
}

 $exerciseTitle=api_parse_tex($exerciseTitle);

echo "<h3>".$exerciseTitle."</h3>";

if( $exerciseAttempts > 0){
	$user_id = api_get_user_id();
	$course_code = api_get_course_id();
	$sql = 'SELECT count(*)
			FROM '.Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_EXERCICES).'
			WHERE exe_exo_id = '.$quizID.' '.
			'and exe_user_id = '.$user_id.' '.
			"and exe_cours_id = '$course_code'";
	$aquery = api_sql_query($sql, __FILE__, __LINE__);
	$attempt = Database::fetch_array($aquery);
	
	if( $attempt[0] >= $exerciseAttempts ){ 
		if(api_is_allowed_to_edit()){
			Display::display_warning_message(sprintf(get_lang('ReachedMaxAttemptsAdmin'),$exerciseTitle,$exerciseAttempts));
		} else {
			Display::display_warning_message(sprintf(get_lang('ReachedMaxAttempts'),$exerciseTitle,$exerciseAttempts));
		    Display::display_footer();	
		    exit;
		}
	}
	
	
}	




if(!empty($error))
{
	Display::display_error_message($error,false);
}
else
{

	if(!empty($exerciseSound))
	{
		echo "<a href=\"../document/download.php?doc_url=%2Faudio%2F".$exerciseSound."\" target=\"_blank\">",
			"<img src=\"../img/sound.gif\" border=\"0\" align=\"absmiddle\" alt=",get_lang('Sound')."\" /></a>";
	}
	
	
	// Get number of hotspot questions for javascript validation
	$number_of_hotspot_questions = 0;
	$onsubmit = '';
	$i=0;

	foreach($questionList as $questionId)
	{
		$i++;
		$objQuestionTmp = Question :: read($questionId);
	
		// for sequential exercises
		if($exerciseType == 2)
		{
			// if it is not the right question, goes to the next loop iteration
			if($questionNum != $i)
			{
				continue;
			}
			else
			{
				if ($objQuestionTmp->selectType() == HOT_SPOT)
				{
					$number_of_hotspot_questions++;
				}
				break;
			}
		}
		else
		{
			if ($objQuestionTmp->selectType() == HOT_SPOT)
			{
				$number_of_hotspot_questions++;
			}
		}
	}
	
	if($number_of_hotspot_questions > 0)
	{
		$onsubmit = "onsubmit=\"return validateFlashVar('".$number_of_hotspot_questions."', '".get_lang('HotspotValidateError1')."', '".get_lang('HotspotValidateError2')."');\"";
	}
	$s="<p>$exerciseDescription</p>";
	
	if($origin == 'learnpath' && $exerciseType==2){
		$s2 = "&exerciseId=".$exerciseId;
	}
	$s.=" <form method='post' action='".api_get_self()."?autocomplete=off".$s2."' name='frm_exercise' $onsubmit>
	 <input type='hidden' name='formSent' value='1' />
	 <input type='hidden' name='exerciseType' value='".$exerciseType."' />
	 <input type='hidden' name='questionNum' value='".$questionNum."' />
	 <input type='hidden' name='nbrQuestions' value='".$nbrQuestions."' />
	 <input type='hidden' name='origin' value='".$origin."' />
	 <input type='hidden' name='learnpath_id' value='".$learnpath_id."' />
	 <input type='hidden' name='learnpath_item_id' value='".$learnpath_item_id."' />
	<table width='100%' border='0' cellpadding='1' cellspacing='0'>
	 <tr>
	  <td>
	  <table width='100%' cellpadding='3' cellspacing='0' border='0'>";
	echo $s;
	
	$i=0;
	
	foreach($questionList as $questionId)
	{
		$i++;
	
		// for sequential exercises
		if($exerciseType == 2)
		{
			// if it is not the right question, goes to the next loop iteration
			if($questionNum != $i)
			{
				continue;
			}
			else
			{
				// if the user has already answered this question
				if(isset($exerciseResult[$questionId]))
				{
					// construction of the Question object
					$objQuestionTmp = Question::read($questionId);
	
					$questionName=$objQuestionTmp->selectTitle();
	
					// destruction of the Question object
					unset($objQuestionTmp);
	
					echo '<tr><td>'.get_lang('AlreadyAnswered').' &quot;'.$questionName.'&quot;</td></tr>';
	
					break;
				}
			}
		}
	
		$s="<tr>
		 <td width='3%' bgcolor='#e6e6e6'><img src=\"".api_get_path(WEB_IMG_PATH)."test.gif\" align=\"absmiddle\"></td>
		 <td valign='middle' bgcolor='#e6e6e6'>
			".get_lang('Question')." ";
		$s.=$i.' : ';
		if($exerciseType == 2) $s.=' / '.$nbrQuestions;
	
		echo $s;
	
		// shows the question and its answers
		showQuestion($questionId, false, $origin);
	
		// for sequential exercises
		if($exerciseType == 2)
		{
			// quits the loop
			break;
		}
	}	// end foreach()
	
	$s="</table>
	  </td>
	 </tr>
	 <tr>
	  <td>
		 <!-- <input type='submit' name='buttonCancel' value=".get_lang('Cancel')." />
	   &nbsp;&nbsp; //-->
		 <input type='submit' name='submit' value='";
	
	  if ($exerciseType == 1 || $nbrQuestions == $questionNum) 
	  {
		$s.=get_lang('ValidateAnswer'); 
	  }
	  else
	  {
		$s.=get_lang('Next').' &gt;';
	  }
	  //$s.='\'&gt;';
	  $s.= '\' />';
	  $s.="</td></tr></form></table>";
	
	$b=2;
	echo $s;
}

if ($origin != 'learnpath') { //so we are not in learnpath tool
    Display::display_footer();
} 
?>
