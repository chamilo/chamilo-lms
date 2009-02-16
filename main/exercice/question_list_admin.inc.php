<?php // $Id: question_list_admin.inc.php 18522 2009-02-16 20:20:29Z juliomontoya $
 
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004-2008 Dokeos SPRL
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	Copyright (c) various contributors

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
*	Code library for HotPotatoes integration.
*	@package dokeos.exercise
* 	@author
* 	@version $Id: question_list_admin.inc.php 18522 2009-02-16 20:20:29Z juliomontoya $
*/


/**
==============================================================================
*	QUESTION LIST ADMINISTRATION
*
*	This script allows to manage the question list
*	It is included from the script admin.php
*
*	@author Olivier Brouckaert
*	@package dokeos.exercise
==============================================================================
*/

// ALLOWED_TO_INCLUDE is defined in admin.php
if(!defined('ALLOWED_TO_INCLUDE'))
{
	exit();
}

// moves a question up in the list
if(isset($_GET['moveUp']))
{
	$objExercise->moveUp(intval($_GET['moveUp']));
	$objExercise->save();
}

// moves a question down in the list
if(isset($_GET['moveDown']))
{
	$objExercise->moveDown(intval($_GET['moveDown']));
	$objExercise->save();
}

// deletes a question from the exercise (not from the data base)
if($deleteQuestion)
{

	// if the question exists
	if($objQuestionTmp = Question::read($deleteQuestion))
	{
		$objQuestionTmp->delete($exerciseId);

		// if the question has been removed from the exercise
		if($objExercise->removeFromList($deleteQuestion))
		{
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

<table class="data_table">
	<tr class="row_odd">
		<th><?php echo get_lang('Question'); ?></th>
		<th><?php echo get_lang('Type');?></th>
		<th><?php echo get_lang('Difficulty'); ?></th>
		<th><?php echo get_lang('Export'); ?></th>
		<th><?php echo get_lang('Modify'); ?></th>
	</tr>

<?php
if($nbrQuestions) {
	$questionList=$objExercise->selectQuestionList();
	$i=1;
	if (is_array($questionList)) {
		foreach($questionList as $id) {
			$objQuestionTmp = Question :: read($id);
			//showQuestion($id);
		?>	
			<tr <?php if($i%2==0) echo 'class="row_odd"'; else echo 'class="row_even"'; ?>>
				<td><?php echo "$i. ".$objQuestionTmp->selectTitle(); ?></td>
				<td><?php eval('echo get_lang('.get_class($objQuestionTmp).'::$explanationLangVar);'); ?></td>
			  	<td align="center"><?php echo $objQuestionTmp->selectLevel(); ?></td>
			  	<td align="center"><a href="<?php echo api_get_self(); ?>?action=exportqti2&questionId=<?php echo $id; ?>"><img src="../img/export.png" border="0" align="absmiddle" alt="IMS/QTI" /></a></td>
			  	<td> <a href="<?php echo api_get_self(); ?>?myid=1&ed
					itQuestion=<?php echo $id; ?>"><img src="../img/edit.gif" border="0" align="absmiddle" alt="<?php echo get_lang('Modify'); ?>" /></a> <a href="<?php echo api_get_self(); ?>?deleteQuestion=<?php echo $id; ?>" onclick="javascript:if(!confirm('<?php echo addslashes(htmlentities(get_lang('ConfirmYourChoice'))); ?>')) return false;"><img src="../img/delete.gif" border="0" align="absmiddle" alt="<?php echo get_lang('Delete'); ?>" /></a>
				<?php
				if($i != 1) {
				    ?>		
					<a href="<?php echo api_get_self(); ?>?moveUp=<?php echo $id; ?>"><img src="../img/up.gif" border="0" align="absmiddle" alt="<?php echo get_lang('MoveUp'); ?>"></a>		
				    <?php
			    	if($i == $nbrQuestions) 
			    		echo '<img src="../img/down_na.gif" align="absmiddle">';		    
				}		
				
				if($i != $nbrQuestions) {
					if($i == 1) 
						echo '<img src="../img/up_na.gif" align="absmiddle">';
			    	?>				
					<a href="<?php echo api_get_self(); ?>?moveDown=<?php echo $id; ?>"><img src="../img/down.gif" border="0" align="absmiddle" alt="<?php echo get_lang('MoveDown'); ?>"></a>
				
				    <?php
				}
				?>			    
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
</table>
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
</div>