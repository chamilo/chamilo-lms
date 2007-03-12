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
*	Exercise result
*	This script gets informations from the script "exercise_submit.php",
*	through the session, and calculates the score of the student for
*	that exercise.
*	Then it shows the results on the screen.
*	@package dokeos.exercise
*	@author Olivier Brouckaert, main author
*	@author Roan Embrechts, some refactoring
* 	@version $Id: exercise_result.php 11548 2007-03-12 16:03:43Z elixir_julian $
*
*	@todo	split more code up in functions, move functions to library?
*/


/*
==============================================================================
		INIT SECTION
==============================================================================
*/
include('exercise.class.php');
include('question.class.php');
include('answer.class.php');

// answer types
define('UNIQUE_ANSWER',	1);
define('MULTIPLE_ANSWER',	2);
define('FILL_IN_BLANKS',	3);
define('MATCHING',		4);
define('FREE_ANSWER', 5);
define('HOT_SPOT', 6);
define('HOT_SPOT_ORDER', 	7);

global $_cid;
// name of the language file that needs to be included
$language_file='exercice';

include('../inc/global.inc.php');
$this_section=SECTION_COURSES;
include(api_get_path(LIBRARY_PATH).'events.lib.inc.php');
include(api_get_path(LIBRARY_PATH).'mail.lib.inc.php');

// Database table definitions
$TBL_EXERCICE_QUESTION 	= Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);
$TBL_EXERCICES         	= Database::get_course_table(TABLE_QUIZ_TEST);
$TBL_QUESTIONS         	= Database::get_course_table(TABLE_QUIZ_QUESTION);
$TBL_REPONSES          	= Database::get_course_table(TABLE_QUIZ_ANSWER);
$TABLETRACK_EXERCICES 	= $_configuration['statistics_database']."`.`track_e_exercices";
$TABLETRACK_ATTEMPT 	= $_configuration['statistics_database']."`.`track_e_attempt";
$main_user_table 		= Database :: get_main_table(TABLE_MAIN_USER);
$main_course_user_table = Database :: get_main_table(TABLE_MAIN_COURSE_USER);
$table_ans 				= Database :: get_course_table(TABLE_QUIZ_ANSWER);

//temp values to move to AWACS
$dsp_percent = false; //false to display total score as absolute values
//debug param. 0: no display - 1: debug display
$debug=0;
if($debug>0){echo str_repeat('&nbsp;',0).'Entered exercise_result.php'."<br />\n";var_dump($_POST);}
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
     $exerciseResult = $_SESSION['exerciseResult'];
}
if ( empty ( $questionId ) ) {
    $questionId = $_REQUEST['questionId'];
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
if ( empty ( $questionList ) ) {
    $questionList = $_SESSION['questionList'];
}
if ( empty ( $objExercise ) ) {
    $objExercise = $_SESSION['objExercise'];
}
$main_user_table = Database :: get_main_table(TABLE_MAIN_USER);
$main_admin_table = Database :: get_main_table(TABLE_MAIN_ADMIN);
$courseName = $_SESSION[_course][name];
$query = "select user_id from $main_admin_table";
$admin_id = mysql_result(api_sql_query($query),0,"user_id");
$query1 = "select email,firstname,lastname from $main_user_table where user_id = $admin_id";
$rs = api_sql_query($query1);
$row = mysql_fetch_array($rs);
$from = $row['email'];
$from_name = $row['firstname'].' '.$row['lastname'];
$str = $_SERVER['REQUEST_URI'];
$arr = explode('/',$str);
$url = api_get_path(WEB_CODE_PATH).'exercice/exercice.php?'.api_get_cidreq().'&show=result';

//$url =  $_SERVER['SERVER_NAME'].'/'.$arr[1].'/';
 // if the above variables are empty or incorrect, stops the script
if(!is_array($exerciseResult) || !is_array($questionList) || !is_object($objExercise))
{

	header('Location: exercice.php');
	exit();
}
$exerciseTitle=$objExercise->selectTitle();

$nameTools=get_lang('Exercice');

$interbreadcrumb[]=array("url" => "exercice.php","name" => get_lang('Exercices'));


if ($origin != 'learnpath')
{
	//so we are not in learnpath tool
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
<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1" />
</head>

<body>
<link rel="stylesheet" type="text/css" href="<?php echo api_get_path(WEB_CODE_PATH).'css/'.api_get_setting('stylesheets').'/frames.css'; ?>" />

<?php
}


/*
==============================================================================
		FUNCTIONS
==============================================================================
*/

function display_unique_or_multiple_answer($answerType, $studentChoice, $answer, $answerComment, $answerCorrect)
{
	?>
	<tr>
	<td width="5%" align="center">
		<img src="../img/<?php echo ($answerType == UNIQUE_ANSWER)?'radio':'checkbox'; echo $studentChoice?'_on':'_off'; ?>.gif"
		border="0" alt="" />
	</td>
	<td width="5%" align="center">
		<img src="../img/<?php echo ($answerType == UNIQUE_ANSWER)?'radio':'checkbox'; echo $answerCorrect?'_on':'_off'; ?>.gif"
		border="0" alt=" " />
	</td>
	<td width="45%" style="border-bottom: 1px solid #4171B5;">
		<?php
		$answer=api_parse_tex($answer);
		echo $answer; ?>
	</td>
	<td width="45%" style="border-bottom: 1px solid #4171B5;">
		<?php
		$answerComment=api_parse_tex($answerComment);
		if($studentChoice) echo nl2br(make_clickable($answerComment)); else echo '&nbsp;'; ?>
	</td>
	</tr>
	<?php
}

function display_fill_in_blanks_answer($answer)
{
	?>
		<tr>
		<td>
			<?php echo nl2br($answer); ?>
		</td>
		</tr>
	<?php
}

function display_free_answer($answer)
{
	?>
		<tr>
		<td width="55%">
			<?php echo nl2br(stripslashes($answer)); ?>
		</td>
   <td width="45%">
    <?php echo get_lang('notCorrectedYet');?>

   </td>
		</tr>
	<?php
}

function display_hotspot_answer($answerId, $answer, $studentChoice, $answerComment)
{
	//global $hotspot_colors;
	$hotspot_colors = array("", // $i starts from 1 on next loop (ugly fix)
            						"#4271B5",
									"#FE8E16",
									"#3B3B3B",
									"#BCD631",
									"#D63173",
									"#D7D7D7",
									"#90AFDD",
									"#AF8640",
									"#4F9242",
									"#F4EB24",
									"#ED2024",
									"#45C7F0",
									"#F7BDE2");
	?>
		<tr>
				<td width="25%" valign="top">
					<div style="float:left; padding-left:5px;">
						<div style="display:inline; float:left; width:80px;"><?php echo $answer ?></div>
						<div style="height:11px; width:11px; background-color:<?php echo $hotspot_colors[$answerId]; ?>; display:inline; float:left; margin-top:3px;"></div>
					</div>
				</td>
				<td width="25%" valign="top"><?php echo $answerId; ?></td>
				<td width="25%" valign="top">
					<?php $studentChoice = ($studentChoice)?get_lang('Correct'):get_lang('Fault'); echo $studentChoice; ?>
				</td>
				<td width="25%" valign="top">
					<?php echo stripslashes($answerComment); ?>
				</td>
		</tr>
	<?php
}

/*
==============================================================================
		MAIN CODE
==============================================================================
*/
$exerciseTitle=api_parse_tex($exerciseTitle);

?>
	<h3><?php echo $exerciseTitle ?>: <?php echo get_lang("Result"); ?></h3>
	<form method="get" action="exercice.php">
	<input type="hidden" name="origin" value="<?php echo $origin; ?>" />
    <input type="hidden" name="learnpath_id" value="<?php echo $learnpath_id; ?>" />
    <input type="hidden" name="learnpath_item_id" value="<?php echo $learnpath_item_id; ?>" />

<?php
	$i=$totalScore=$totalWeighting=0;
	if($debug>0){echo "ExerciseResult: "; var_dump($exerciseResult); echo "QuestionList: ";var_dump($questionList);}


	// added by Priya Saini
	$sql = "select max(exe_Id) as id from `".$TABLETRACK_EXERCICES."`";
	$res = api_sql_query($sql, __FILE__, __LINE__);
	$exeId =mysql_result($res,0,"id");
	$exeId=$exeId+1;
	
	$counter=0;
	foreach($questionList as $questionId)
	{
		$counter++;
		// gets the student choice for this question
		$choice=$exerciseResult[$questionId];
		// creates a temporary Question object

		$objQuestionTmp = Question :: read($questionId);

		$questionName=$objQuestionTmp->selectTitle();
		$questionWeighting=$objQuestionTmp->selectWeighting();
		$answerType=$objQuestionTmp->selectType();
		$quesId =$objQuestionTmp->selectId(); //added by priya saini

		// destruction of the Question object
		unset($objQuestionTmp);

		if($answerType == UNIQUE_ANSWER || $answerType == MULTIPLE_ANSWER)
		{
			$colspan=4;
		}
		elseif($answerType == MATCHING || $answerType == FREE_ANSWER)
		{
			$colspan=2;
		}
		elseif($answerType == HOT_SPOT || $answerType == HOT_SPOT_ORDER)
		{
			$colspan=4;
			$rowspan=$nbrAnswers+1;
		}
		else
		{
			$colspan=1;
		}
		?>
			<table width="100%" border="0" cellpadding="3" cellspacing="2">
			<tr bgcolor="#E6E6E6">
			<td colspan="<?php echo $colspan; ?>">
				<?php echo get_lang("Question").' '.($counter); ?>
			</td>
			</tr>
			<tr>
			<td colspan="<?php echo $colspan; ?>">
				<?php echo $questionName; ?>
			</td>
			</tr>
		<?php
		if($answerType == UNIQUE_ANSWER || $answerType == MULTIPLE_ANSWER)
		{
			?>
				<tr>
				<td width="5%" valign="top" align="center" nowrap="nowrap">
					<i><?php echo get_lang("Choice"); ?></i>
				</td>
				<td width="5%" valign="top" nowrap="nowrap">
					<i><?php echo get_lang("ExpectedChoice"); ?></i>
				</td>
				<td width="45%" valign="top">
					<i><?php echo get_lang("Answer"); ?></i>
				</td>
				<td width="45%" valign="top">
					<i><?php echo get_lang("Comment"); ?></i>
				</td>
				</tr>
			<?php
		}
		elseif($answerType == FILL_IN_BLANKS)
		{
			?>
				<tr>
				<td>
					<i><?php echo get_lang("Answer"); ?></i>
				</td>
				</tr>
			<?php
		}
		elseif($answerType == FREE_ANSWER)
		{
			?>
				<tr>
				<td width="55%">
					<i><?php echo get_lang("Answer"); ?></i>
				</td>
				<td width="45%" valign="top">
					<i><?php echo get_lang("Comment"); ?></i>
				</td>
				</tr>
			<?php
		}
		elseif($answerType == HOT_SPOT)
		{
			?>
				<tr>
					<td width="40%">
						<i><?php echo get_lang('Hotspot'); ?></i><br /><br />
						<object type="application/x-shockwave-flash" data="../plugin/hotspot/hotspot_solution.swf?modifyAnswers=<?php echo $questionId ?>" width="380" height="400">
							<param name="movie" value="../plugin/hotspot/hotspot_solution.swf?modifyAnswers=<?php echo $questionId ?>" />
						</object>
					</td>
					<td width="60%" valign="top">
						<table width="100%" border="0">
							<tr>
								<td width="25%" valign="top">
									<i><?php echo get_lang("CorrectAnswer"); ?></i><br /><br />
								</td>
								<td width="25%" valign="top">
									<i><?php echo get_lang("ClickNumber"); ?></i><br /><br />
								</td>
								<td width="25%" valign="top">
									<i><?php echo get_lang('HotspotHit'); ?></i><br /><br />
								</td>
								<td width="25%" valign="top">
									<i><?php echo get_lang("Comment"); ?></i><br /><br />
								</td>
							</tr>
			<?php
		}
		else
		{
			?>
				<tr>
				<td width="50%">
					<i><?php echo get_lang("ElementList"); ?></i>
				</td>
				<td width="50%">
					<i><?php echo get_lang("CorrespondsTo"); ?></i>
				</td>
				</tr>
			<?php
		}

		// construction of the Answer object
		$objAnswerTmp=new Answer($questionId);
		$nbrAnswers=$objAnswerTmp->selectNbrAnswers();
		$questionScore=0;
		if($answerType == FREE_ANSWER)
			$nbrAnswers = 1;
		for($answerId=1;$answerId <= $nbrAnswers;$answerId++)
		{
			$answer=$objAnswerTmp->selectAnswer($answerId);
			$answerComment=$objAnswerTmp->selectComment($answerId);
			$answerCorrect=$objAnswerTmp->isCorrect($answerId);
			$answerWeighting=$objAnswerTmp->selectWeighting($answerId);

			switch($answerType)
			{
				// for unique answer
				case UNIQUE_ANSWER :
										$studentChoice=($choice == $answerId)?1:0;

										if($studentChoice)
										{
										  	$questionScore+=$answerWeighting;
											$totalScore+=$answerWeighting;
										}


										break;
				// for multiple answers
				case MULTIPLE_ANSWER :

										$studentChoice=$choice[$answerId];

										if($studentChoice)
										{
											$questionScore+=$answerWeighting;
											$totalScore+=$answerWeighting;
										}

										break;
				// for fill in the blanks
				case FILL_IN_BLANKS :	// splits text and weightings that are joined with the character '::'

										list($answer,$answerWeighting)=explode('::',$answer);

										// splits weightings that are joined with a comma
										$answerWeighting=explode(',',$answerWeighting);

										// we save the answer because it will be modified

										$temp=$answer;

										// TeX parsing
										// 1. find everything between the [tex] and [/tex] tags
										 $startlocations=strpos($temp,'[tex]');
										$endlocations=strpos($temp,'[/tex]');

										if($startlocations !== false && $endlocations !== false)
										{

											$texstring=substr($temp,$startlocations,$endlocations-$startlocations+6);
											// 2. replace this by {texcode}
											$temp=str_replace($texstring,'{texcode}',$temp);
										}

										$answer='';


										$j=0;

										// the loop will stop at the end of the text
										while(1)
										{

											// quits the loop if there are no more blanks
											if(($pos = strpos($temp,'[')) === false)
											{
												// adds the end of the text
												 $answer.=$temp;
												// TeX parsing
												$texstring = api_parse_tex($texstring);
												$answer=str_replace("{texcode}",$texstring,$answer);
												break;
											}

											// adds the piece of text that is before the blank and ended by [
											$answer.=substr($temp,0,$pos+1);
											$temp=substr($temp,$pos+1);

											// quits the loop if there are no more blanks
											if(($pos = strpos($temp,']')) === false)
											{

												// adds the end of the text
												$answer.=$temp;
												break;
											}

											$choice[$j]=trim($choice[$j]);

											// if the word entered by the student IS the same as the one defined by the professor
											if(strtolower(substr($temp,0,$pos)) == stripslashes(strtolower($choice[$j])))
											{
												// gives the related weighting to the student
												$questionScore+=$answerWeighting[$j];

												// increments total score
												$totalScore+=$answerWeighting[$j];

												// adds the word in green at the end of the string
												$answer.=stripslashes($choice[$j]);
											}
											// else if the word entered by the student IS NOT the same as the one defined by the professor
											elseif(!empty($choice[$j]))
											{
												// adds the word in red at the end of the string, and strikes it
												$answer.='<font color="red"><s>'.stripslashes($choice[$j]).'</s></font>';
											}
											else
											{
												// adds a tabulation if no word has been typed by the student
												$answer.='&nbsp;&nbsp;&nbsp;';
											}

											// adds the correct word, followed by ] to close the blank
											$answer.=' / <font color="green"><b>'.substr($temp,0,$pos).'</b></font>]';

											$j++;

											$temp=substr($temp,$pos+1);
										}

										break;
				// for free answer
				case FREE_ANSWER :
										$studentChoice=$choice;

										if($studentChoice)
										{
										  	$questionScore=0;
											$totalScore+=0;
										}


										break;
				// for matching
				case MATCHING :
										if($answerCorrect)
										{
											if($answerCorrect == $choice[$answerId])
											{
												$questionScore+=$answerWeighting;
												$totalScore+=$answerWeighting;
												$choice[$answerId]=$matching[$choice[$answerId]];
											}
											elseif(!$choice[$answerId])
											{
												$choice[$answerId]='&nbsp;&nbsp;&nbsp;';
											}
											else
											{
												$choice[$answerId]='<font color="red"><s>'.$matching[$choice[$answerId]].'</s></font>';
											}
										}
										else
										{
											$matching[$answerId]=$answer;
										}
										break;
				// for hotspot with no order
				case HOT_SPOT :			$studentChoice=$choice[$answerId];

										if($studentChoice)
										{
											$questionScore+=$answerWeighting;
											$totalScore+=$answerWeighting;
										}

										break;
				// for hotspot with fixed order
				case HOT_SPOT_ORDER :	$studentChoice=$choice['order'][$answerId];

										if($studentChoice == $answerId)
										{
											$questionScore+=$answerWeighting;
											$totalScore+=$answerWeighting;
											$studentChoice = true;
										}
										else
										{
											$studentChoice = false;
										}

										break;
			} // end switch Answertype
			
			if($answerType != MATCHING || $answerCorrect)
			{
				if($answerType == UNIQUE_ANSWER || $answerType == MULTIPLE_ANSWER)
				{
					display_unique_or_multiple_answer($answerType, $studentChoice, $answer, $answerComment, $answerCorrect);
				}
				elseif($answerType == FILL_IN_BLANKS)
				{
					display_fill_in_blanks_answer($answer);
				}
				elseif($answerType == FREE_ANSWER)
				{
					// to store the details of open questions in an array to be used in mail

					$arrques[] = $questionName;
					$arrans[]  = $choice;
					$firstName =   $_SESSION['_user']['firstName'];
					$lastName =   $_SESSION['_user']['lastName'];
					$mail =  $_SESSION['_user']['mail'];
					$coursecode =  $_SESSION['_course']['official_code'];
					$query1 = "SELECT user_id from $main_course_user_table where course_code= '$coursecode' and status = '1' LIMIT 0,1";
					$result1 = api_sql_query($query1, __FILE__, __LINE__);
					$temp = mysql_result($result1,0,"user_id");
					$query = "select email from $main_user_table where user_id =".$temp ;
					$result = api_sql_query($query, __FILE__, __LINE__);
					$to = mysql_result($result,0,"email");
					display_free_answer($choice);
				}
				elseif($answerType == HOT_SPOT)
				{
					display_hotspot_answer($answerId, $answer, $studentChoice, $answerComment);
				}
				elseif($answerType == HOT_SPOT_ORDER)
				{
					display_hotspot_order_answer($answerId, $answer, $studentChoice, $answerComment);
				}
				else
				{
					?>
						<tr>
						<td width="50%">
							<?php
							$answer=api_parse_tex($answer);
							echo $answer; ?>
						</td>
						<td width="50%">
							<?php echo $choice[$answerId]; ?> / <font color="green"><b>
							<?php
							$matching[$answerCorrect]=api_parse_tex($matching[$answerCorrect]);
							echo $matching[$answerCorrect]; ?></b></font>
						</td>
						</tr>
					<?php
				}
			}
		} // end for that loops over all answers of the current question

		if ($answerType == HOT_SPOT || $answerType == HOT_SPOT_ORDER)
			{
				// We made an extra table for the answers
				echo "</table></td></tr>";
			}
		?>
			<tr>
			<td colspan="<?php echo $colspan; ?>" align="right">
				<b><?php echo get_lang('Score')." : $questionScore/$questionWeighting"; ?></b>
			</td>
			</tr>
			</table>
		<?php
		// destruction of Answer
		unset($objAnswerTmp);

		$i++;

		$totalWeighting+=$questionWeighting;
		//added by priya saini
		if($_configuration['tracking_enabled'])
		{
			if(empty($choice)){
				$choice = 0;
			}
			if ($answerType==2 )
			{
				$reply = array_keys($choice);
				for ($i=0;$i<sizeof($reply);$i++)
				{
					$ans = $reply[$i];
					exercise_attempt($questionScore,$ans,$quesId,$exeId);
				}
			}
			elseif ($answerType==4)
			{
				$j=3;
				for ($i=0;$i<sizeof($choice);$i++,$j++)
				{

					$val = $choice[$j];
					if (preg_match_all ('#<font color="red"><s>([0-9a-z ]*)</s></font>#', $val, $arr1))
						$val = $arr1[1][0];
					$val=addslashes($val);
					$sql = "select position from $table_ans where question_id=$questionId and answer='$val'";
					$res = api_sql_query($sql, __FILE__, __LINE__);
					$answer = mysql_result($res,0,"position");

					exercise_attempt($questionScore,$answer,$quesId,$exeId,$j);

				}
			}
			elseif ($answerType==5)
			{
				$answer = $choice;
				exercise_attempt($questionScore,$answer,$quesId,$exeId);
			}
			elseif ($answerType==1)
			{
				$sql = "select id from $table_ans where question_id=$questionId and position=$choice";
				$res = api_sql_query($sql, __FILE__, __LINE__);
				$answer = mysql_result($res,0,"id");
				exercise_attempt($questionScore,$answer,$quesId,$exeId);
			}
			else
			{
				exercise_attempt($questionScore,$answer,$quesId,$exeId);
			}
		}
	} // end huge foreach() block that loops over all questions
	?>
		<table width="100%" border="0" cellpadding="3" cellspacing="2">
		<tr>
		<td>
			<b><?php echo get_lang('YourTotalScore')." ";
			if($dsp_percent == true){
			  echo number_format(($totalScore/$totalWeighting)*100,1,'.','')."%";
			}else{
			  echo $totalScore."/".$totalWeighting;
			}
                        ?> !</b>
		</td>
		</tr>
		<tr>
		<td>
		<br />
			<?php
			if ($origin != 'learnpath')
			{
			?>
			<input type="submit" value="<?php echo get_lang('Ok'); ?>" />
			<?php
			}
			?>
		</td>
		</tr>
		</table>

		</form>

		<br />
	<?php
/*
==============================================================================
		Tracking of results
==============================================================================
*/

if($_configuration['tracking_enabled'])
{
	//include(api_get_path(LIBRARY_PATH).'events.lib.inc.php');
	event_exercice($objExercise->selectId(),$totalScore,$totalWeighting,$answer,$question_id);

}

if ($origin != 'learnpath')
{
	//we are not in learnpath tool
	Display::display_footer();
}else{
				$user_id = (!empty($_SESSION['_user']['id'])?$_SESSION['_user']['id']:0);
                $lp_item = Database::get_course_table('lp_item');
                $lp_item_view = Database::get_course_table('lp_item_view');
                $sql2 = "UPDATE $lp_item SET max_score = '$totalWeighting'
                        WHERE id = '$learnpath_item_id'";
                api_sql_query($sql2,__FILE__,__LINE__);
                $sql2 = "UPDATE $lp_item_view SET score = '$totalScore'
                        WHERE lp_item_id = '$learnpath_item_id'
                        AND lp_view_id = '".$_SESSION['scorm_view_id']."'";
                api_sql_query($sql2,__FILE__,__LINE__);

}
$csspath = "http://portal.dokeos.com/demo/main/css/default.css";
if (!empty($arrques))
{
	$msg = "<html><head>
	 <link rel='stylesheet' href='http://www.dokeos.com/styles.css' type='text/css'>
  <meta content='text/html; charset=ISO-8859-1' http-equiv='content-type'>";


/*<style type='text/css'>

<!--
.body{
font-family: Verdana, Arial, Helvetica, sans-serif;
font-weight: Normal;
color: #000000;
}
.style8 {font-family: Verdana, Arial, Helvetica, sans-serif; font-weight: bold; color: #006699; }
.style10 {
	font-family: Verdana, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: bold;
}
.style16 {font-family: Verdana, Arial, Helvetica, sans-serif; font-size: 12px; }
-->
</style>*/

/*$msg .= "</head>
<body>
<br>
<p><span class='style8'>".get_lang('OpenQuestionsAttempted')."
</span></p>
<p><span class='style8'>Attempt Details : </span><br>
</p>
<table width='730' height='136' border='0' cellpadding='3' cellspacing='3'>
					<tr>
    <td width='229' valign='top' bgcolor='E5EDF8'>&nbsp;&nbsp;<span class='style10'>Course Name</span></td>
    <td width='469' valign='top' bgcolor='#F3F3F3'><span class='style16'>#course#</span></td>
  </tr>
  <tr>
    <td width='229' valign='top' bgcolor='E5EDF8'>&nbsp;&nbsp;<span class='style10'>".get_lang('TestAttempted')."</span></td>
    <td width='469' valign='top' bgcolor='#F3F3F3'><span class='style16'> #exercise#</span></td>
  </tr>
  <tr>
    <td valign='top' bgcolor='E5EDF8'>&nbsp;&nbsp;<span class='style10'>Student's Name </span></td>
    <td valign='top' bgcolor='#F3F3F3'><span class='style16'> #firstName# #lastName#</span></td>
  </tr>
  <tr>
    <td valign='top' bgcolor='E5EDF8'>&nbsp;&nbsp;<span class='style10'>Student's Email ID </span></td>
    <td valign='top' bgcolor='#F3F3F3'><span class='style16'> #mail#</span></td>
</tr></table>
<p><br>
<span class='style8'>".get_lang('OpenQuestionsAttemptedAre')." :</span></p>

 <table width='730' height='136' border='0' cellpadding='3' cellspacing='3'>";
  for($i=0;$i<sizeof($arrques);$i++)
  {
  $msg.="
	<tr>
    <td width='220' valign='top' bgcolor='E5EDF8'>&nbsp;&nbsp;<span class='style10'>Question</span></td>
    <td width='473' valign='top' bgcolor='F3F3F3'><span class='style16'> #questionName#</span></td>
  	</tr>
  	<tr>
    <td width='220' valign='top' bgcolor='E5EDF8'>&nbsp;&nbsp;<span class='style10'>Answer </span></td>
    <td valign='top' bgcolor='F3F3F3'><span class='style16'> #answer#</span></td>
  	</tr>";

	$msg1= str_replace("#exercise#",$exerciseTitle,$msg);
	$msg= str_replace("#firstName#",$firstName,$msg1);
	$msg1= str_replace("#lastName#",$lastName,$msg);
	$msg= str_replace("#mail#",$mail,$msg1);
	$msg1= str_replace("#questionName#",$arrques[$i],$msg);
	$msg= str_replace("#answer#",$arrans[$i],$msg1);
	$msg1= str_replace("#i#",$i,$msg);
	$msg= str_replace("#course#",$courseName,$msg1);

	}
	$msg.="</table><br>*/
	//
	$msg .= "</head>
<body><br>
<p>".get_lang('OpenQuestionsAttempted')."
</p>
<p>Attempt Details : ><br>
</p>
<table width='730' height='136' border='0' cellpadding='3' cellspacing='3'>
					<tr>
    <td width='229' valign='top'  class='mybody'>&nbsp;&nbsp;Course Name</td>
    <td width='469' valign='top'  class='mybody'>#course#</td>
  </tr>
  <tr>
    <td width='229' valign='top' class='outerframe'>&nbsp;&nbsp;".get_lang('TestAttempted')."Test Attempted</span></td>
    <td width='469' valign='top' class='outerframe'>#exercise#</td>
  </tr>
  <tr>
    <td valign='top'>&nbsp;&nbsp;<span class='style10'>Student's Name </span></td>
    <td valign='top' >#firstName# #lastName#</td>
  </tr>
  <tr>
    <td valign='top' >&nbsp;&nbsp;Student's Email ID </td>
    <td valign='top'> #mail#</td>
</tr></table>
<p><br>
".get_lang('OpenQuestionsAttemptedAre')." :</p>

 <table width='730' height='136' border='0' cellpadding='3' cellspacing='3'>";
  for($i=0;$i<sizeof($arrques);$i++)
  {
  $msg.="
	<tr>
    <td width='220' valign='top' bgcolor='E5EDF8'>&nbsp;&nbsp;<span class='style10'>Question</span></td>
    <td width='473' valign='top' bgcolor='F3F3F3'><span class='style16'> #questionName#</span></td>
  	</tr>
  	<tr>
    <td width='220' valign='top' bgcolor='E5EDF8'>&nbsp;&nbsp;<span class='style10'>Answer </span></td>
    <td valign='top' bgcolor='F3F3F3'><span class='style16'> #answer#</span></td>
  	</tr>";

	$msg1= str_replace("#exercise#",$exerciseTitle,$msg);
	$msg= str_replace("#firstName#",$firstName,$msg1);
	$msg1= str_replace("#lastName#",$lastName,$msg);
	$msg= str_replace("#mail#",$mail,$msg1);
	$msg1= str_replace("#questionName#",$arrques[$i],$msg);
	$msg= str_replace("#answer#",$arrans[$i],$msg1);
	$msg1= str_replace("#i#",$i,$msg);
	$msg= str_replace("#course#",$courseName,$msg1);

	}
	$msg.="</table><br>
 	<span class='style16'>Click the following links tp check the answer and give feedbacks,<br>
<a href='#url#'>#url#</a></span></body></html>";


	$msg1= str_replace("#url#",$url,$msg);
	$mail_content = stripslashes($msg1);
	$student_name = $_SESSION[_user][firstName].' '.$_SESSION[_user][lastName];
	$subject = get_lang('OpenQuestionsAttempted');
	$headers="From:$from_name\r\nReply-to: $to\r\nContent-type: text/html; charset=iso-8859-15";
	api_mail($student_name, $to, $subject, $mail_content, $from_name, $from, $headers);
}
?>
