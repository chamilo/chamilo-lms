<?php
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
*	QUESTION ADMINISTRATION
*
*	This script allows to manage a question and its answers
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

$pictureName=$objQuestion->selectPicture();

if ( empty ($answerType) ) {
  $answerType = $objQuestion->selectType();
}
//added
if($_REQUEST['exerciseId'])
$myid=1;
else
$myid=0;

// if the question we are modifying is used in several exercises
if($usedInSeveralExercises)
{
?>

<h3>
  <?php echo $questionName; ?>
</h3>

<form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>?modifyQuestion=<?php echo $modifyQuestion; ?>&modifyAnswers=<?php echo $modifycAnswers; ?>">


<?php
	// submit question
	if($submitQuestion)
	{
?>

	<input type="hidden" name="questionName" value="<?php echo htmlentities($questionName); ?>">
    <input type="hidden" name="questionDescription" value="<?php echo htmlentities($questionDescription); ?>">
    <input type="hidden" name="imageUpload_size" value="<?php echo $imageUpload_size; ?>">
    <input type="hidden" name="deletePicture" value="<?php echo $deletePicture; ?>">
    <input type="hidden" name="pictureName" value="<?php echo htmlentities($pictureName); ?>">

<?php
	}
	// submit answers
	else
	{
		if($answerType == UNIQUE_ANSWER || $answerType == MULTIPLE_ANSWER)
		{
?>

	<input type="hidden" name="correct" value="<?php echo htmlentities(serialize($correct)); ?>">
	<input type="hidden" name="reponse" value="<?php echo htmlentities(serialize($reponse)); ?>">
	<input type="hidden" name="comment" value="<?php echo htmlentities(serialize($comment)); ?>">
	<input type="hidden" name="weighting" value="<?php echo htmlentities(serialize($weighting)); ?>">
	<input type="hidden" name="nbrAnswers" value="<?php echo $nbrAnswers; ?>">

<?php
		}
		elseif($answerType == MATCHING)
		{
?>

	<input type="hidden" name="option" value="<?php echo htmlentities(serialize($option)); ?>">
	<input type="hidden" name="match" value="<?php echo htmlentities(serialize($match)); ?>">
	<input type="hidden" name="sel" value="<?php echo htmlentities(serialize($sel)); ?>">
	<input type="hidden" name="weighting" value="<?php echo htmlentities(serialize($weighting)); ?>">
	<input type="hidden" name="nbrOptions" value="<?php echo $nbrOptions; ?>">
	<input type="hidden" name="nbrMatches" value="<?php echo $nbrMatches; ?>">

<?php
		}
		elseif ( $answerType == FREE_ANSWER)
		{
?>

	<input type="hidden" name="reponse" value="<?php echo htmlentities(serialize($reponse)); ?>">
	<input type="hidden" name="comment" value="<?php echo htmlentities(serialize($comment)); ?>">
	<input type="hidden" name="weighting" value="<?php echo htmlentities(serialize($weighting)); ?>">
	<input type="hidden" name="setWeighting" value="1">

<?php
		}
		else
		{
?>

	<input type="hidden" name="reponse" value="<?php echo htmlentities(serialize($reponse)); ?>">
	<input type="hidden" name="comment" value="<?php echo htmlentities(serialize($comment)); ?>">
	<input type="hidden" name="blanks" value="<?php echo htmlentities(serialize($blanks)); ?>">
	<input type="hidden" name="weighting" value="<?php echo htmlentities(serialize($weighting)); ?>">
	<input type="hidden" name="setWeighting" value="1">

<?php
		}
	} // end submit answers
?>

	<input type="hidden" name="answerType" value="<?php echo $answerType; ?>">

<?php
	$msgBox= " ".get_lang('langUsedInSeveralExercises')." :<br />
		  <input class=\"checkbox\" type=\"radio\" name=\"modifyIn\" value=\"allExercises\" checked=\"checked\"> ".get_lang('langModifyInAllExercises')."<br />
		 <input class=\"checkbox\" type=\"radio\" name=\"modifyIn\" value=\"thisExercise\"> ".get_lang('langModifyInThisExercise')."<br />
         <input type=\"submit\" name=\"".($submitQuestion?'submitQuestion':'submitAnswers')."\" value=\"".get_lang('langOk')."\">
		 ";
	Display::display_normal_message($msgBox); //main API
?>
</form>

<?php
}
else
{
	// selects question informations
	$questionName=$objQuestion->selectTitle();
	$questionDescription=$objQuestion->selectDescription();

	// is picture set ?
	$okPicture=empty($pictureName)?false:true;
?>

<h3>
  <?php echo $questionName; ?>

<?php
	// doesn't show the edit link if we come from the question pool to pick a question for an exercise
	if(!$fromExercise)
	{
//edited
?>
<a href="<?php echo $_SERVER['PHP_SELF']; ?>?myid=<?php echo $myid; ?>&modifyQuestion=<?php echo $questionId; ?>"><img src="../img/edit.gif" border="0" align="absmiddle" alt="<?php echo get_lang('langModify'); ?>"></a>

<?php
	}
?></h3>


<?php
	// show the picture of the question
	if($okPicture)
	{
?>

<center><img src="../document/download.php?doc_url=%2Fimages%2F<?php echo $pictureName; ?>" border="0"></center>

<?php
	}
?>

<blockquote>
  <?php echo $questionDescription; ?>
</blockquote>

<?php
	// doesn't show the edit link if we come from the question pool to pick a question for an exercise
	/*if(!$fromExercise)
	{
?>

<a href="<?php echo $_SERVER['PHP_SELF']; ?>?modifyQuestion=<?php echo $questionId; ?>"><img src="../img/edit.gif" border="0" align="absmiddle" alt="<?php echo get_lang('langModify'); ?>"></a>

<?php
	}*/
?>

<hr size="1" noshade="noshade">

<?php
	// we are in an exercise
	if($exerciseId)
	{
?>

<!--<a href="<?php echo $_SERVER['PHP_SELF']; ?>">&lt;&lt; <?php echo get_lang('langGoBackToQuestionList'); ?></a>-->

<?php
	}
	// we are not in an exercise, so we come from the question pool
	else
	{
?>

<a href="question_pool.php?fromExercise=<?php echo $fromExercise; ?>">&lt;&lt; <?php echo get_lang('langGoBackToQuestionPool'); ?></a>

<?php
	}
	if($answerType != FREE_ANSWER){
?>

<b><?php echo get_lang('langQuestionAnswers'); ?></b>


<a href="<?php echo $_SERVER['PHP_SELF']; ?>?myid=<?php echo $myid; ?>&modifyAnswers=<?php echo $questionId; ?>"><img src="../img/edit.gif" border="0" align="absmiddle" alt="<?php echo get_lang('langModify'); ?>"></a>
<table border="0" align="center" cellpadding="2" cellspacing="2" width="100%">
<form>

<?php
	// shows answers of the question. 'true' means that we don't show the question, only answers
	$hide_question = true;
	#if ( $answerType == FREE_ANSWER) { $hide_question = false; echo "voila"; } //show the question if free answer, because there is no set answer
	if(!showQuestion($questionId,$hide_question))
	{
?>

<tr>
  <td><?php echo get_lang('langNoAnswer'); ?></td>
</tr>

<?php
	}
?>

</form>
</table>

<br>

<?php
	}
	// doesn't show the edit link if we come from the question pool to pick a question for an exercise
	if(!$fromExercise)
	{
?>

<!--<a href="<?php //echo $_SERVER['PHP_SELF']; ?>?modifyAnswers=<?php //echo $questionId; ?>"><img src="../img/edit.gif" border="0" align="absmiddle" alt="<?php //echo get_lang('langModify'); ?>"></a>-->

<?php
	}
	if ($exerciseId)
	{
	?><a href="<?php echo $_SERVER['PHP_SELF']; ?>">&lt;&lt; <?php echo get_lang('langGoBackToQuestionList'); ?></a>

	
	<?php
	
	}
}
?>