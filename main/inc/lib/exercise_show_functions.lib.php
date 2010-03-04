<?php
/* See license terms in /license.txt */
/**
==============================================================================
* EVENTS LIBRARY
*
* This is the events library for Chamilo.
* Functions of this library are used to record informations when some kind
* of event occur. Each event has his own types of informations then each event
* use its own function.
*
* @package chamilo.library
* @todo convert queries to use Database API
==============================================================================
*/

$TBL_EXERCICE_QUESTION 	= Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);
$TBL_EXERCICES         	= Database::get_course_table(TABLE_QUIZ_TEST);
$TBL_QUESTIONS         	= Database::get_course_table(TABLE_QUIZ_QUESTION);
$TBL_REPONSES          	= Database::get_course_table(TABLE_QUIZ_ANSWER);
$main_user_table 		= Database :: get_main_table(TABLE_MAIN_USER);
$main_course_user_table = Database :: get_main_table(TABLE_MAIN_COURSE_USER);
$TBL_TRACK_EXERCICES	= Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_EXERCICES);
$TBL_TRACK_ATTEMPT		= Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);

class ExerciseShowFunctions {

	/**
	 * Shows the answer to a fill-in-the-blanks question, as HTML
	 * @param string    Answer text
	 * @param int       Exercise ID
	 * @param int       Question ID
	 * @return void
	 */

	function display_fill_in_blanks_answer($answer,$id,$questionId)
	{	global $feedback_type;
		?>
			<tr>
			<td>
				<?php echo nl2br(Security::remove_XSS($answer,COURSEMANAGERLOWSECURITY)); ?>
			</td>

			<?php
			if(!api_is_allowed_to_edit(null,true) && $feedback_type != EXERCISE_FEEDBACK_TYPE_EXAM) {?>
				<td>
				<?php
				$comm = ExerciseShowFunctions::get_comments($id,$questionId);
				?>
				</td>
			<?php } ?>

				</tr>
		<?php
	}

	/**
	 * Shows the answer to a free-answer question, as HTML
	 * @param string    Answer text
	 * @param int       Exercise ID
	 * @param int       Question ID
	 * @return void
	 */
	function display_free_answer($answer,$id,$questionId) {
		global $feedback_type;
		?>
			<tr>
			<td>
				<?php if (!empty($answer)) {echo nl2br(Security::remove_XSS($answer,COURSEMANAGERLOWSECURITY));} ?>
			</td>

			<?php if(!api_is_allowed_to_edit(null,true) && $feedback_type != EXERCISE_FEEDBACK_TYPE_EXAM) {?>
	        <td>
	        <?php
	        $comm = ExerciseShowFunctions::get_comments($id,$questionId);
	        ?>
	        </td>
	    	<?php }?>


	    </tr>
	    <?php
	}

	/**
	 * Displays the answer to a hotspot question
	 *
	 * @param int $answerId
	 * @param string $answer
	 * @param string $studentChoice
	 * @param string $answerComment
	 */
	function display_hotspot_answer($answerId, $answer, $studentChoice, $answerComment) {
		global $feedback_type;
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
			<td width="100px" valign="top" align="left">
				<div style="width:100%;">
					<div style="height:11px; width:11px; background-color:<?php echo $hotspot_colors[$answerId]; ?>; display:inline; float:left; margin-top:3px;"></div>
					<div style="float:left; padding-left:5px;">
					<?php echo $answerId; ?>
					</div>
					<div><?php echo '&nbsp;'.$answer ?></div>
				</div>
			</td>
			<td width="50px" style="padding-right:15px" valign="top" align="left">
				<?php
				$my_choice = ($studentChoice)?get_lang('Correct'):get_lang('Fault'); echo $my_choice; ?>
			</td>


			<?php if ($feedback_type != EXERCISE_FEEDBACK_TYPE_EXAM) { ?>
			<td valign="top" align="left" >
				<?php
				$answerComment=api_parse_tex($answerComment);
				if($studentChoice) {
					echo '<span style="font-weight: bold; color: #008000;">'.nl2br(make_clickable($answerComment)).'</span>';
				} else {
					echo '<span style="font-weight: bold; color: #FF0000;">'.nl2br(make_clickable($answerComment)).'</span>';
				}
				?>
			</td>
			<?php } else { ?>
				<td>&nbsp;</td>
			<?php } ?>

		</tr>
		<?php
	}


	/**
	 * Display the answers to a multiple choice question
	 *
	 * @param integer Answer type
	 * @param integer Student choice
	 * @param string  Textual answer
	 * @param string  Comment on answer
	 * @param string  Correct answer comment
	 * @param integer Exercise ID
	 * @param integer Question ID
	 * @param boolean Whether to show the answer comment or not
	 * @return void
	 */
	function display_unique_or_multiple_answer($answerType, $studentChoice, $answer, $answerComment, $answerCorrect, $id, $questionId, $ans)
	{
		global $feedback_type;
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
		<td width="40%" style="border-bottom: 1px solid #4171B5;">
			<?php
			$answer=api_parse_tex($answer);
			echo $answer;
			?>
		</td>

		<?php if ($feedback_type != EXERCISE_FEEDBACK_TYPE_EXAM) { ?>
		<td width="20%" style="border-bottom: 1px solid #4171B5;">
			<?php
			$answerComment=api_parse_tex($answerComment);
			if($studentChoice)
			{
				if(!$answerCorrect)
				{
					echo '<span style="font-weight: bold; color: #FF0000;">'.nl2br(make_clickable($answerComment)).'</span>';
				}
				else{
					echo '<span style="font-weight: bold; color: #008000;">'.nl2br(make_clickable($answerComment)).'</span>';
				}
			}
			else
			{
				echo '&nbsp;';
			}
			?>
		</td>
			<?php
		    if ($ans==1) {
		        $comm = ExerciseShowFunctions::get_comments($id,$questionId);
			}
		    ?>
		 <?php } else { ?>
			<td>&nbsp;</td>
		<?php } ?>
		</tr>
		<?php
	}

	/**
	 * This function gets the comments of an exercise
	 *
	 * @param int $id
	 * @param int $question_id
	 * @return str the comment
	 */
	function get_comments($id,$question_id)
	{
		global $TBL_TRACK_ATTEMPT;
		$sql = "SELECT teacher_comment FROM ".$TBL_TRACK_ATTEMPT." where exe_id='".Database::escape_string($id)."' and question_id = '".Database::escape_string($question_id)."' ORDER by question_id";
		$sqlres = Database::query($sql);
		$comm = Database::result($sqlres,0,"teacher_comment");
		return $comm;
	}

	function send_notification($arrques, $arrans, $to) {
		global $courseName, $exerciseTitle, $url_email;
	    require_once api_get_path(LIBRARY_PATH).'usermanager.lib.php';
		$user_info = UserManager::get_user_info_by_id(api_get_user_id());

		if (api_get_course_setting('email_alert_manager_on_new_quiz') != 1 ) {
			return '';
		}

		$mycharset = api_get_system_encoding();
	    $msg = '<html><head>
	            <link rel="stylesheet" href="'.api_get_path(WEB_CODE_PATH).'css/'.api_get_setting('stylesheets').'/default.css" type="text/css">
	            <meta content="text/html; charset='.$mycharset.'" http-equiv="content-type"></head>';
		if(count($arrques)>0) {
		    $msg .= '<body>
		    <p>'.get_lang('OpenQuestionsAttempted').' :
		    </p>
		    <p>'.get_lang('AttemptDetails').' : <br />
		    </p>
		    <table width="730" height="136" border="0" cellpadding="3" cellspacing="3">
		                                            <tr>
		        <td width="229" valign="top"><h2>&nbsp;&nbsp;'.get_lang('CourseName').'</h2></td>
		        <td width="469" valign="top"><h2>#course#</h2></td>
		      </tr>
		      <tr>
		        <td width="229" valign="top" class="outerframe">&nbsp;&nbsp;'.get_lang('TestAttempted').'</span></td>
		        <td width="469" valign="top" class="outerframe">#exercise#</td>
		      </tr>
		      <tr>
		        <td valign="top">&nbsp;&nbsp;<span class="style10">'.get_lang('StudentName').'</span></td>
		        <td valign="top" >#firstName# #lastName#</td>
		      </tr>
		      <tr>
		        <td valign="top" >&nbsp;&nbsp;'.get_lang('StudentEmail').' </td>
		        <td valign="top"> #mail#</td>
		    </tr></table>
		    <p><br />'.get_lang('OpenQuestionsAttemptedAre').' :</p>
		     <table width="730" height="136" border="0" cellpadding="3" cellspacing="3">';

		    for($i=0;$i<sizeof($arrques);$i++) {
		              $msg.='
		                    <tr>
		                <td width="220" valign="top" bgcolor="#E5EDF8">&nbsp;&nbsp;<span class="style10">'.get_lang('Question').'</span></td>
		                <td width="473" valign="top" bgcolor="#F3F3F3"><span class="style16"> #questionName#</span></td>
		                    </tr>
		                    <tr>
		                <td width="220" valign="top" bgcolor="#E5EDF8">&nbsp;&nbsp;<span class="style10">'.get_lang('Answer').' </span></td>
		                <td valign="top" bgcolor="#F3F3F3"><span class="style16"> #answer#</span></td>
		                    </tr>';

		                    $msg1= str_replace("#exercise#",$exerciseTitle,$msg);
		                    $msg= str_replace("#firstName#",$user_info['firstname'],$msg1);
		                    $msg1= str_replace("#lastName#",$user_info['lastname'],$msg);
		                    $msg= str_replace("#mail#",$user_info['email'],$msg1);
		                    $msg1= str_replace("#questionName#",$arrques[$i],$msg);
		                    $msg= str_replace("#answer#",$arrans[$i],$msg1);
		                    $msg1= str_replace("#i#",$i,$msg);
		                    $msg= str_replace("#course#",$courseName,$msg1);
		    }
		    $msg.='</table><br>
		                    <span class="style16">'.get_lang('ClickToCommentAndGiveFeedback').',<br />
		                    <a href="#url#">#url#</a></span></body></html>';

		    $msg1= str_replace("#url#",$url_email,$msg);
		    $mail_content = $msg1;

		    $subject = get_lang('OpenQuestionsAttempted');


		    $sender_name = api_get_person_name(api_get_setting('administratorName'), api_get_setting('administratorSurname'), null, PERSON_NAME_EMAIL_ADDRESS);
		    $email_admin = api_get_setting('emailAdministrator');
		    $result = @api_mail_html('', $to, $subject, $mail_content, $sender_name, $email_admin, array('charset'=>$mycharset));
		} else {

			$msg .= '<body>
			<p>'.get_lang('ExerciseAttempted').' <br />
		    </p>
			<table width="730" height="136" border="0" cellpadding="3" cellspacing="3">
				<tr>
			    <td width="229" valign="top"><h2>&nbsp;&nbsp;'.get_lang('CourseName').'</h2></td>
			    <td width="469" valign="top"><h2>#course#</h2></td>
			  </tr>
			  <tr>
			    <td width="229" valign="top" class="outerframe">&nbsp;&nbsp;'.get_lang('TestAttempted').'</span></td>
			    <td width="469" valign="top" class="outerframe">#exercise#</td>
			  </tr>
			  <tr>
			    <td valign="top">&nbsp;&nbsp;<span class="style10">'.get_lang('StudentName').'</span></td>
			    '.(api_is_western_name_order() ? '<td valign="top" >#firstName# #lastName#</td>' : '<td valign="top" >#lastName# #firstName#</td>').'
			  </tr>
			  <tr>
			    <td valign="top" >&nbsp;&nbsp;'.get_lang('StudentEmail').' </td>
			    <td valign="top"> #mail#</td>
			</tr></table>';

			$msg= str_replace("#exercise#",$exerciseTitle,$msg);
			$msg= str_replace("#firstName#",$user_info['firstname'],$msg);
			$msg= str_replace("#lastName#",$user_info['lastname'],$msg);
			$msg= str_replace("#mail#",$user_info['email'],$msg);
			$msg= str_replace("#course#",$courseName,$msg);

			$msg.='<br />
		 			<span class="style16">'.get_lang('ClickToCommentAndGiveFeedback').',<br />
					<a href="#url#">#url#</a></span></body></html>';

			$msg= str_replace("#url#",$url_email,$msg);
			$mail_content = $msg;

			$sender_name = api_get_person_name(api_get_setting('administratorName'), api_get_setting('administratorSurname'), null, PERSON_NAME_EMAIL_ADDRESS);
			$email_admin = api_get_setting('emailAdministrator');

			$subject = get_lang('ExerciseAttempted');
			$result = @api_mail_html('', $to, $subject, $mail_content, $sender_name, $email_admin, array('charset'=>$mycharset));
		}
	}
}