<?php // $Id: exercice_submit.php 9665 2006-10-24 10:43:48Z elixir_inter $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004 Dokeos S.A.
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	Copyright (c) Olivier Brouckaert
	Copyright (c) Denes Nagy

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
	EXERCISE SUBMISSION
 *
 * This script allows to run an exercise. According to the exercise type, questions
 * can be on an unique page, or one per page with a Next button.
 *
 * One exercise may contain different types of answers (unique or multiple selection,
 * matching and fill in blanks).
 *
 * Questions are selected randomly or not.
 *
 * When the user has answered all questions and clicks on the button "Ok",
 * it goes to exercise_result.php
 *
 * Notice : This script is also used to show a question before modifying it by
 * the administrator
 *
 *	@author Olivier Brouckaert
 *	@package dokeos.exercise
==============================================================================
 */
include('exercise.class.php');
include('question.class.php');
include('answer.class.php');

include('exercise.lib.php');

// debug var. Set to 0 to hide all debug display. Set to 1 to display debug messages.
$debug = 0;

// answer types
define('UNIQUE_ANSWER',	1);
define('MULTIPLE_ANSWER',	2);
define('FILL_IN_BLANKS',	3);
define('MATCHING',		4);
define('FREE_ANSWER', 5);
define('HOT_SPOT', 			6);
define('HOT_SPOT_ORDER', 	7);

$langFile='exercice';

include_once('../inc/global.inc.php');
$this_section=SECTION_COURSES;

include_once(api_get_path(LIBRARY_PATH).'text.lib.php');

$is_allowedToEdit=$is_courseAdmin;

$TBL_EXERCICE_QUESTION = $_course['dbNameGlu'].'quiz_rel_question';
$TBL_EXERCICES         = $_course['dbNameGlu'].'quiz';
$TBL_QUESTIONS         = $_course['dbNameGlu'].'quiz_question';
$TBL_REPONSES          = $_course['dbNameGlu'].'quiz_answer';

// general parameters passed via POST/GET
if ( empty ( $origin ) ) {
    $origin = $_REQUEST['origin'];
}
if ( empty ( $learnpath_id ) ) {
    $learnpath_id       = mysql_real_escape_string($_REQUEST['learnpath_id']);
}
if ( empty ( $learnpath_item_id ) ) {
    $learnpath_item_id  = mysql_real_escape_string($_REQUEST['learnpath_item_id']);
}
if ( empty ( $formSent ) ) {
    $formSent       = $_REQUEST['formSent'];
}
if ( empty ( $exerciseResult ) ) {
    $exerciseResult = $_REQUEST['exerciseResult'];
}
if ( empty ( $exerciseType ) ) {
    $exerciseType = $_REQUEST['exerciseType'];
}
if ( empty ( $exerciseId ) ) {
    $exerciseId = $_REQUEST['exerciseId'];
}
if ( empty ( $choice ) ) {
    $choice = $_REQUEST['choice'];
}
if ( empty ( $questionNum ) ) {
    $questionNum    = mysql_real_escape_string($_REQUEST['questionNum']);
}
if ( empty ( $nbrQuestions ) ) {
    $nbrQuestions   = mysql_real_escape_string($_REQUEST['nbrQuestions']);
}
if ( empty ($buttonCancel) ) {
	$buttonCancel 	= $_REQUEST['buttonCancel'];
}

// if the user has clicked on the "Cancel" button
if($buttonCancel)
{
	// returns to the exercise list
	header("Location: exercice.php?origin=$origin&learnpath_id=$learnpath_id&learnpath_item_id=$learnpath_item_id");
	exit();
}

if ($origin=='builder') {
	/*******************************/
	/* Clears the exercise session */
	/*******************************/
	if(isset($_SESSION['objExercise']))		{ api_session_unregister('objExercise');	unset($objExercise); }
	if(isset($_SESSION['objQuestion']))		{ api_session_unregister('objQuestion');	unset($objQuestion); }
	if(isset($_SESSION['objAnswer']))		{ api_session_unregister('objAnswer');		unset($objAnswer);   }
	if(isset($_SESSION['questionList']))	{ api_session_unregister('questionList');	unset($questionList); }
	if(isset($_SESSION['exerciseResult']))	{ api_session_unregister('exerciseResult');	unset($exerciseResult); }
}

// if the user has submitted the form
if($formSent)
{
    if($debug>0){echo str_repeat('&nbsp;',0).'$formSent was set'."<br />\n";}

    // initializing
    if(!is_array($exerciseResult))
    {
        $exerciseResult=array();
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
            }
        }
        if($debug>0){echo str_repeat('&nbsp;',0).'$choice is an array - end'."<br />\n";}
    }

    // the script "exercise_result.php" will take the variable $exerciseResult from the session
    api_session_register('exerciseResult');

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
if(!isset($_SESSION['objExercise']))
{
    if($debug>0){echo str_repeat('&nbsp;',0).'$_SESSION[objExercise] was unset'."<br />\n";}
    // construction of Exercise
    $objExercise=new Exercise();

    #$sql="SELECT title,description,sound,type,random,active FROM `$TBL_EXERCICES` WHERE id='$exerciseId'";

    // if the specified exercise doesn't exist or is disabled
    if(!$objExercise->read($exerciseId) || (!$objExercise->selectStatus() && !$is_allowedToEdit && ($origin != 'learnpath') ))
    {
        die(get_lang('ExerciseNotFound'));
    }

    // saves the object into the session
    api_session_register('objExercise');
    if($debug>0){echo str_repeat('&nbsp;',0).'$_SESSION[objExercise] was unset - set now - end'."<br />\n";}

}

if(!is_object($objExercise))
{
	header('Location: exercice.php');
	exit();
}

$exerciseTitle=$objExercise->selectTitle();
$exerciseDescription=$objExercise->selectDescription();
$exerciseSound=$objExercise->selectSound();
$randomQuestions=$objExercise->isRandom();
$exerciseType=$objExercise->selectType();

if(!isset($_SESSION['questionList']))
{
    if($debug>0){echo str_repeat('&nbsp;',0).'$_SESSION[questionList] was unset'."<br />\n";}
    // selects the list of question ID
    $questionList = ($randomQuestions?$objExercise->selectRandomList():$objExercise->selectQuestionList());
    // saves the question list into the session
    api_session_register('questionList');
    if($debug>0){echo str_repeat('&nbsp;',0).'$_SESSION[questionList] was unset - set now - end'."<br />\n";}
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

if ($origin != 'learnpath') { //so we are not in learnpath tool
$htmlHeadXtra[] = "<script type=\"text/javascript\" src=\"../js/JavaScriptFlashGateway.js\"></script>
					<script src=\"../js/hotspot.js\" type=\"text/javascript\"></script>					   
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
	?>
	<link rel="stylesheet" type="text/css" href="<?php echo api_get_path(WEB_CODE_PATH); ?>css/frames.css" />

<?php
}

 $exerciseTitle=api_parse_tex($exerciseTitle);

echo "<h3>".$exerciseTitle."</h3>";

if(!empty($exerciseSound))
{
	echo "<a href=\"../document/download.php?doc_url=%2Faudio%2F".$exerciseSound."\" target=\"_blank\">",
		"<img src=\"../img/sound.gif\" border=\"0\" align=\"absmiddle\" alt=",get_lang('Sound')."\" /></a>";
}


/* <ERM> */
// Get number of hotspot questions for javascript validation
$number_of_hotspot_questions = 0;
$onsubmit = '';
$i=0;
foreach($questionList as $questionId)
{
	$i++;
	$objQuestionTmp=new Question();
	$objQuestionTmp->read($questionId);
	
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
	$onsubmit = "onsubmit=\"return validateFlashVar('".$number_of_hotspot_questions."', '".get_lang('langHotspotValidateError1')."', '".get_lang('langHotspotValidateError2')."')\"";
}
$s="
<p>$exerciseDescription</p>
 <form method='post' action='".$_SERVER['PHP_SELF']."?autocomplete=off' name='frm_exercise' $onsubmit>
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
  <table width='100%' cellpadding='4' cellspacing='2' border='0'>";
echo $s;
/* </ERM> */


if (isset($_POST['submit']))
	{
	echo "Form submited";
	}
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
				$objQuestionTmp=new Question();

				// reads question informations
				$objQuestionTmp->read($questionId);

				$questionName=$objQuestionTmp->selectTitle();

				// destruction of the Question object
				unset($objQuestionTmp);

				echo '<tr><td>'.get_lang('AlreadyAnswered').' &quot;'.$questionName.'&quot;</td></tr>';

				break;
			}
		}
	}

	$s="<tr bgcolor='#e6e6e6'>
	 <td valign='top' colspan='2'>
		".get_lang('Question')." ";
	$s.=$i;
	if($exerciseType == 2) $s.=' / '.$nbrQuestions;
	$s.='</td></tr>';

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
  <td><br/>
	 <!-- <input type='submit' name='buttonCancel' value=".get_lang('Cancel')." />
   &nbsp;&nbsp; //-->
	 <input type='submit' name='submit' value='";

  if ($exerciseType == 1 || $nbrQuestions == $questionNum) {
	$s.=get_lang('Ok');
  }
  else
  {
	$s.=get_lang('Next').' &gt;';
  }
  $s.='\'&gt;';
  $s.="</td></tr></form></table>";

$b=2;
echo $s;

if ($origin != 'learnpath') { //so we are not in learnpath tool
    Display::display_footer();
} else {
	?>
	<link rel="stylesheet" type="text/css" href="<?php echo $clarolineRepositoryWeb ?>css/frames.css" />
<?php
}
?>
