<?php // $Id: question_list_admin.inc.php 10594 2007-01-05 13:54:24Z elixir_inter $
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
if($moveUp)
{
	$objExercise->moveUp($moveUp);
	$objExercise->save();
}

// moves a question down in the list
if($moveDown)
{
	$objExercise->moveDown($moveDown);
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
?>

<hr size="1" noshade="noshade">
  <a href="question_pool.php?fromExercise=<?php echo $exerciseId; ?>"><?php echo get_lang('GetExistingQuestion'); ?></a> &nbsp; &nbsp; &nbsp;  &nbsp; &nbsp; &nbsp;  &nbsp; &nbsp;&nbsp; <!--<a href="exercice.php?<?php echo api_get_cidreq(); ?>"><?php echo get_lang('backtoTesthome'); ?></a>--><br />
<br /><?php echo get_lang('NewQu').' : ';?><a href="admin.php?newQuestion=yes&answerType=1"><?php echo get_lang('UniqueSelect'); ?></a> | <a href="admin.php?newQuestion=yes&answerType=2"><?php echo get_lang('MultipleSelect'); ?></a> | <a href="admin.php?newQuestion=yes&answerType=3"><?php echo get_lang('FillBlanks'); ?></a> | <a href="admin.php?newQuestion=yes&answerType=4"><?php echo get_lang('Matching'); ?></a> | <a href="admin.php?newQuestion=yes&answerType=5"><?php echo get_lang('freeAnswer'); ?></a> | <a href="admin.php?newQuestion=yes&answerType=6"><?php echo get_lang('Hotspot'); ?></a>
&nbsp;&nbsp;
<br /><br />


<!--<form method="get" action="exercice.php" style="margin:10px; margin-left:0px;">
<input type="submit" value="<?php //echo htmlentities(get_lang('FinishTest')); ?>">
</form>-->

<?php //echo get_lang('QuestionList'); ?>
<table border="0" align="center" cellpadding="2" cellspacing="2" width="100%">
<tr  bgcolor='#e6e6e6'><td width="20%" align="center"><b><?php echo get_lang('Question'); ?></b></td>
<td width="20%" align="center"><b><?php echo get_lang('Type');?></b></td>
<!--<td width="20%" align="center"><b><?php echo get_lang('Addlimits'); ?> </b><br /> (time,attempts)</td>-->
<td width="20%" align="center"><b><?php echo get_lang('Feedback'); ?> </b></td>
<td width="20%" align="center"><b><?php echo get_lang('Modify'); ?></b></td>
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
  <td  width="20%"><?php echo "$i. ".$objQuestionTmp->selectTitle(); ?></td> <td width="20%"><?php echo $aType[$objQuestionTmp->selectType()-1]; ?></td>
<!--<td  width="20%" align="center"><a href="#"> <img src="../img/test_prop.gif" border="0" align="absmiddle" alt="<?php echo get_lang('Addlimits'); ?>" /></a> </td>--> <td width="20%" align="center"> <a href="feedback.php?question=<?php echo $id;?>"><img src="../img/feedback.gif" border="0" align="absmiddle" alt="<?php echo get_lang('Feedback'); ?>" /></a> </td>
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

<?php /*
if($nbrQuestions)
{
	
	$questionList=$objExercise->selectQuestionList();

	$i=1;
 //echo "<pre>";
//	print_r($questionList);
//echo "</pre>";
	foreach($questionList as $id)
	{
		$objQuestionTmp=new Question();

		$objQuestionTmp->read($id);
$s="<tr bgcolor='#e6e6e6'>
	 <td valign='top' colspan='2'>
		".get_lang('Question')." ";
	$s.=$i;
	if($exerciseType == 2) $s.=' / '.$nbrQuestions;
	//$s.="<a href=".$_SERVER['PHP_SELF']."?editQuestion=".$id."><img src='../img/edit.gif' border='0' align='absmiddle' alt=".get_lang('Modify')."></a>";
	
	$s.='</td></tr>';

	echo $s;
showQuestion($id);

?> <!--<tr>
  <td><?php echo "$i. ".$objQuestionTmp->selectTitle(); ?><br><?php echo $aType[$objQuestionTmp->selectType()-1]; ?></td>
</tr>
<tr>-->
  
  <tr><td><table>
      <tr>
        <td><a href="<?php echo $_SERVER['PHP_SELF']; ?>?myid=1&editQuestion=<?php echo $id; ?>"><img src="../img/edit.gif" border="0" align="absmiddle" alt="<?php echo get_lang('Modify'); ?>" /></a></td>
        <td><a href="<?php echo $_SERVER['PHP_SELF']; ?>?deleteQuestion=<?php echo $id; ?>" onclick="javascript:if(!confirm('<?php echo addslashes(htmlentities(get_lang('ConfirmYourChoice'))); ?>')) return false;"><img src="../img/delete.gif" border="0" align="absmiddle" alt="<?php echo get_lang('Delete'); ?>" /></a></td>
          
	

<?php
		if($i != 1)
		{
?>

	<td><a href="<?php echo $_SERVER['PHP_SELF']; ?>?moveUp=<?php echo $id; ?>"><img src="../img/up.gif" border="0" align="absmiddle" alt="<?php echo get_lang('MoveUp'); ?>"></a></td>

<?php
		}

		if($i != $nbrQuestions)
		{
?>
<td>
	<a href="<?php echo $_SERVER['PHP_SELF']; ?>?moveDown=<?php echo $id; ?>"><img src="../img/down.gif" border="0" align="absmiddle" alt="<?php echo get_lang('MoveDown'); ?>"></a></td>

<?php
		}
?>
</tr>
    </table>
 </td>
</tr>

<?php
		$i++;

		unset($objQuestionTmp);
	}
}
*/
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
