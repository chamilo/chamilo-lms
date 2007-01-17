<?php // $Id: question_list_admin.inc.php 10748 2007-01-17 08:22:39Z elixir_inter $
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

echo '<a href="question_pool.php?fromExercise='.$exerciseId.'">'.get_lang('GetExistingQuestion').'</a>';
Question :: display_type_menu ();
?>


<table border="0" align="center" cellpadding="2" cellspacing="2" width="100%">
<tr  bgcolor='#e6e6e6'><td width="65%" align="center"><b><?php echo get_lang('Question'); ?></b></td>
<td width="20%" align="center"><b><?php echo get_lang('Type');?></b></td>
<td width="15%" align="center"><b><?php echo get_lang('Modify'); ?></b></td>
</tr>

<?php 
if($nbrQuestions)
	{
	$questionList=$objExercise->selectQuestionList();

	$i=1;
	foreach($questionList as $id)
	{

		$objQuestionTmp = Question :: read($id);
		//showQuestion($id);

?> 

<tr>
  <td><?php echo "$i. ".$objQuestionTmp->selectTitle(); ?></td> <td><?php eval('echo get_lang('.get_class($objQuestionTmp).'::$explanationLangVar);'); ?></td>
  <td> <a href="<?php echo $_SERVER['PHP_SELF']; ?>?myid=1&editQuestion=<?php echo $id; ?>"><img src="../img/edit.gif" border="0" align="absmiddle" alt="<?php echo get_lang('Modify'); ?>" /></a> <a href="<?php echo $_SERVER['PHP_SELF']; ?>?deleteQuestion=<?php echo $id; ?>" onclick="javascript:if(!confirm('<?php echo addslashes(htmlentities(get_lang('ConfirmYourChoice'))); ?>')) return false;"><img src="../img/delete.gif" border="0" align="absmiddle" alt="<?php echo get_lang('Delete'); ?>" /></a>
          
	<?php
		if($i != 1)
		{
?>

	<a href="<?php echo $_SERVER['PHP_SELF']; ?>?moveUp=<?php echo $id; ?>"><img src="../img/up.gif" border="0" align="absmiddle" alt="<?php echo get_lang('MoveUp'); ?>"></a>

<?php
		}

		if($i != $nbrQuestions)
		{
?>

	<a href="<?php echo $_SERVER['PHP_SELF']; ?>?moveDown=<?php echo $id; ?>"><img src="../img/down.gif" border="0" align="absmiddle" alt="<?php echo get_lang('MoveDown'); ?>"></a>

<?php
		}
?>

 </td>

<?php
		$i++;

		unset($objQuestionTmp);
?>
</tr>
	<?php }
}
?>	
</table>
<table border="0" align="center" cellpadding="2" cellspacing="2" width="100%">

<?php 
if(!$i)
{
?>

<tr>
  <td><?php echo get_lang('NoQuestion'); ?></td>
</tr>

<?php
}
?>

</table>
