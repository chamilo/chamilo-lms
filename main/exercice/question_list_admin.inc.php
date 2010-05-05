<?php
/* For licensing terms, see /license.txt */

/**
*	Code library for HotPotatoes integration.
*	@package chamilo.exercise
* 	@author
*/


/**
*	QUESTION LIST ADMINISTRATION
*
*	This script allows to manage the question list
*	It is included from the script admin.php
*
*	@author Olivier Brouckaert
*	@package chamilo.exercise
*/

// ALLOWED_TO_INCLUDE is defined in admin.php
if(!defined('ALLOWED_TO_INCLUDE')) {
	exit();
}

// moves a question up in the list
if(isset($_GET['moveUp'])) {
	$check = Security::get_token('get');
	if ($check) {
		$objExercise->moveUp(intval($_GET['moveUp']));
		$objExercise->save();
	}
	Security::clear_token();
}

// moves a question down in the list
if(isset($_GET['moveDown'])) {
	$check = Security::get_token('get');
	if ($check) {
		$objExercise->moveDown(intval($_GET['moveDown']));
		$objExercise->save();
	}
	Security::clear_token();
}

// deletes a question from the exercise (not from the data base)
if($deleteQuestion) {

	// if the question exists
	if($objQuestionTmp = Question::read($deleteQuestion)) {
		$objQuestionTmp->delete($exerciseId);

		// if the question has been removed from the exercise
		if($objExercise->removeFromList($deleteQuestion)) {
			$nbrQuestions--;
		}
	}

	// destruction of the Question object
	unset($objQuestionTmp);
}
echo '<div class="actionsbig">';
//we filter the type of questions we can add
Question :: display_type_menu ($objExercise->feedbacktype);
echo '</div>';
?>

<div class="content_table"><table class="data_table">
	<span class="row_odd">
		<th><?php echo get_lang('Question'); ?></th>
		<th><?php echo get_lang('Type');?></th>
		<th><?php echo get_lang('Difficulty'); ?></th>
		<!--<th><?php //echo get_lang('Export'); ?></th>-->
		<th><?php echo get_lang('Modify'); ?></th>
	</span>

<?php
$token = Security::get_token();

if($nbrQuestions) {
	$questionList=$objExercise->selectQuestionList();
	$i=1;
	if (is_array($questionList)) {		
		foreach($questionList as $id) {			
			//To avoid warning messages
			if (!is_numeric($id)) {
				continue;
			}			
			$objQuestionTmp = Question :: read($id);
			
			//showQuestion($id);
		?>
			<tr <?php if($i%2==0) echo 'class="row_odd"'; else echo 'class="row_even"'; ?>>
				<td><?php echo "$i. ".$objQuestionTmp->selectTitle(); ?></td>
				<td><?php eval('echo get_lang('.get_class($objQuestionTmp).'::$explanationLangVar);'); ?></td>
			  	<td align="center"><?php echo $objQuestionTmp->selectLevel(); ?></td>		  	
			  	<td>
			  	<a href="<?php echo api_get_self(); ?>?<?php echo api_get_cidreq() ?>&type=<?php echo $objQuestionTmp->selectType(); ?>&myid=1&editQuestion=<?php echo $id; ?>"><img src="../img/edit.gif" border="0" alt="<?php echo get_lang('Modify'); ?>" /></a>
				<?php
				// this variable  $show_quiz_edition comes from admin.php blocks the exercise/quiz modifications
				if ($show_quiz_edition) { ?>
							  	 
				<a href="<?php echo api_get_self(); ?>?<?php echo api_get_cidreq() ?>&amp;deleteQuestion=<?php echo $id; ?>" onclick="javascript:if(!confirm('<?php echo addslashes(api_htmlentities(get_lang('ConfirmYourChoice'))); ?>')) return false;"><img src="../img/delete.gif" border="0" alt="<?php echo get_lang('Delete'); ?>" /></a>
				
				<?php 
				}
				if($i != 1) { ?>
				<a href="<?php echo api_get_self(); ?>?<?php echo api_get_cidreq() ?>&moveUp=<?php echo $id; ?>&token=<?php echo $token; ?>"><img src="../img/up.gif" border="0" alt="<?php echo get_lang('MoveUp'); ?>"></a>
				<?php if($i == $nbrQuestions) {
			    		echo '<img src="../img/down_na.gif">';
					}
				}
				if($i != $nbrQuestions) {
					if($i == 1){
						echo '<img src="../img/up_na.gif">';
					}
				?>
				<a href="<?php echo api_get_self(); ?>?<?php echo api_get_cidreq() ?>&moveDown=<?php echo $id; ?>&token=<?php echo $token; ?>"><img src="../img/down.gif" border="0" alt="<?php echo get_lang('MoveDown'); ?>"></a>
				<?php } ?>
			    </td>
			    <?php
				$i++;
				unset($objQuestionTmp);
			    ?>
			</tr>
				<?php
		}
	}
}
?>
</table></div>
<table border="0" align="center" cellpadding="2" cellspacing="2" width="100%">
<?php
if(!$i) {
	?>
	<tr>
  	<td><?php echo get_lang('NoQuestion'); ?></td>
	</tr>
<?php
}
?>
</table>