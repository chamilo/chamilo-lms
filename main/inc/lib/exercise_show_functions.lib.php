<?php
/* See license terms in /license.txt */
/**
* EVENTS LIBRARY
*
* This is the events library for Chamilo.
* Functions of this library are used to record informations when some kind
* of event occur. Each event has his own types of informations then each event
* use its own function.
*
* @package chamilo.library
* @todo convert queries to use Database API
*/

class ExerciseShowFunctions {

	/**
	 * Shows the answer to a fill-in-the-blanks question, as HTML
	 * @param string    Answer text
	 * @param int       Exercise ID
	 * @param int       Question ID
	 * @return void
	 */

	function display_fill_in_blanks_answer($answer,$id,$questionId) {
        global $feedback_type;
        if (empty($id)) {
            echo '<tr><td>'. nl2br(Security::remove_XSS($answer,COURSEMANAGERLOWSECURITY)).'</td></tr>';
        } else {
		?>
			<tr>
			<td>
				<?php echo nl2br(Security::remove_XSS($answer,COURSEMANAGERLOWSECURITY)); ?>
			</td>

			<?php
			if(!api_is_allowed_to_edit(null,true) && $feedback_type != EXERCISE_FEEDBACK_TYPE_EXAM) {?>
				<td>
				<?php
				$comm = get_comments($id,$questionId);
				?>
				</td>
			<?php } ?>

				</tr>
		<?php
        }
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
        if (empty($id)) {
            ?>
        	       <tr>
        <td width="55%">
            <?php echo nl2br(Security::remove_XSS($answer,COURSEMANAGERLOWSECURITY)); ?>
        </td>
    <?php if ($feedback_type != EXERCISE_FEEDBACK_TYPE_EXAM) { ?>
   <td width="45%">
    <?php echo get_lang('notCorrectedYet');?>

   </td>
   <?php } else { ?>
        <td>&nbsp;</td>
    <?php } ?>
        </tr>
        
        <?php 
        } else {		
		?>
			<tr>
			<td>
				<?php if (!empty($answer)) {echo nl2br(Security::remove_XSS($answer,COURSEMANAGERLOWSECURITY));} ?>
			</td>
			<?php if(!api_is_allowed_to_edit(null,true) && $feedback_type != EXERCISE_FEEDBACK_TYPE_EXAM) {?>
	        <td>
	        <?php
	        $comm = get_comments($id,$questionId);
	        ?>
	        </td>
	    	<?php }?>
	    </tr>
	    <?php
        }
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
				$answerComment=text_filter($answerComment);
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
	function display_unique_or_multiple_answer($answerType, $studentChoice, $answer, $answerComment, $answerCorrect, $id, $questionId, $ans) {
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
			$answer=text_filter($answer);
			echo $answer;
			?>
		</td>

		<?php if ($feedback_type != EXERCISE_FEEDBACK_TYPE_EXAM) { ?>
		<td width="20%" style="border-bottom: 1px solid #4171B5;">
			<?php
			$answerComment=text_filter($answerComment);
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
		        $comm = get_comments($id,$questionId);
			}
		    ?>
		 <?php } else { ?>
			<td>&nbsp;</td>
		<?php } ?>
		</tr>
		<?php
	}   
}