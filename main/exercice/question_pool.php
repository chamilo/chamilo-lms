<?php
/*
    DOKEOS - elearning and course management software

    For a full list of contributors, see documentation/credits.html

    This program is free software; you can redistribute it and/or
    modify it under the terms of the GNU General Public License
    as published by the Free Software Foundation; either version 2
    of the License, or (at your option) any later version.
    See "documentation/licence.html" more details.

    Contact: Dokeos, Rue du Corbeau, 108, B-1030 Brussels - Belgium, info@dokeos.com
*/
/**
*	Question Pool
* 	This script allows administrators to manage questions and add them into their exercises.
* 	One question can be in several exercises
*	@package dokeos.exercise
* 	@author Olivier Brouckaert
* 	@version $Id: question_pool.php 15602 2008-06-18 08:52:24Z pcool $
*/

// name of the language file that needs to be included
$language_file='exercice';

include('exercise.class.php');
include('question.class.php');
include('answer.class.php');

include('../inc/global.inc.php');

$this_section=SECTION_COURSES;

$is_allowedToEdit=api_is_allowed_to_edit();

$TBL_EXERCICE_QUESTION = Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);
$TBL_EXERCICES         = Database::get_course_table(TABLE_QUIZ_TEST);
$TBL_QUESTIONS         = Database::get_course_table(TABLE_QUIZ_QUESTION);
$TBL_REPONSES          = Database::get_course_table(TABLE_QUIZ_ANSWER);

if ( empty ( $delete ) ) {
    $delete = intval($_GET['delete']);
}
if ( empty ( $recup ) ) {
    $recup = intval($_GET['recup']);
}
if ( empty ( $fromExercise ) ) {
    $fromExercise = intval($_GET['fromExercise']);
}
if(isset($_GET['exerciseId'])){
	$exerciseId = intval($_GET['exerciseId']);
}
if(!empty($_GET['page'])){
	$page = intval($_GET['page']);
}

// maximum number of questions on a same page
$limitQuestPage=50;

// document path
$documentPath=api_get_path(SYS_COURSE_PATH).$_course['path'].'/document';

// picture path
$picturePath=$documentPath.'/images';


if(!($objExcercise instanceOf Exercise) && !empty($fromExercise))
{
    $objExercise = new Exercise();
    $objExercise->read($fromExercise);
}
if(!($objExcercise instanceOf Exercise) && !empty($exerciseId))
{
    $objExercise = new Exercise();
    $objExercise->read($exerciseId);
}

if($is_allowedToEdit)
{
	// deletes a question from the data base and all exercises
	if($delete)
	{
		// construction of the Question object
		// if the question exists
		if($objQuestionTmp = Question::read($delete))
		{
			// deletes the question from all exercises
			$objQuestionTmp->delete();
		}

		// destruction of the Question object
		unset($objQuestionTmp);
	}
	// gets an existing question and copies it into a new exercise
	elseif($recup && $fromExercise)
	{
		// if the question exists
		if($objQuestionTmp = Question :: read($recup))
		{
			// adds the exercise ID represented by $fromExercise into the list of exercises for the current question
			$objQuestionTmp->addToList($fromExercise);
		}

		// destruction of the Question object
		unset($objQuestionTmp);

        if(!$objExcercise instanceOf Exercise)
        {
        	$objExercise = new Exercise();
            $objExercise->read($fromExercise);
        }
		// adds the question ID represented by $recup into the list of questions for the current exercise
		$objExercise->addToList($recup);

		api_session_register('objExercise');

		header("Location: admin.php?exerciseId=$fromExercise");
		exit();
	}
}

$nameTools=get_lang('QuestionPool');

$interbreadcrumb[]=array("url" => "exercice.php","name" => get_lang('Exercices'));

// if admin of course
if($is_allowedToEdit)
{
	Display::display_header($nameTools,"Exercise");
?>

<h3>
  <?php echo $nameTools; ?>
</h3>

<form method="get" action="<?php echo api_get_self(); ?>">
<input type="hidden" name="fromExercise" value="<?php echo $fromExercise; ?>">
<table class="data_table">
<tr>
  <td colspan="<?php echo $fromExercise?2:3; ?>" align="right">
	<?php echo get_lang('Filter'); ?> : <select name="exerciseId">
	<option value="0">-- <?php echo get_lang('AllExercises'); ?> --</option>
	<option value="-1" <?php if($exerciseId == -1) echo 'selected="selected"'; ?>>-- <?php echo get_lang('OrphanQuestions'); ?> --</option>

<?php 
	$sql="SELECT id,title FROM $TBL_EXERCICES WHERE id<>'".Database::escape_string($fromExercise)."' AND active<>'-1' ORDER BY id";
	$result=api_sql_query($sql,__FILE__,__LINE__);

	// shows a list-box allowing to filter questions
	while($row=Database::fetch_array($result))
	{
?>

	<option value="<?php echo $row['id']; ?>" <?php if($exerciseId == $row['id']) echo 'selected="selected"'; ?>><?php echo $row['title']; ?></option>

<?php
	}
?>

    </select> <input type="submit" value="<?php echo get_lang('Ok'); ?>">
  </td>
</tr>

<?php
	$from=$page*$limitQuestPage;

	// if we have selected an exercise in the list-box 'Filter'
	if($exerciseId > 0)
	{
		$sql="SELECT id,question,type FROM $TBL_EXERCICE_QUESTION,$TBL_QUESTIONS WHERE question_id=id AND exercice_id='".Database::escape_string($exerciseId)."' ORDER BY position";
	}
	// if we have selected the option 'Orphan questions' in the list-box 'Filter'
	elseif($exerciseId == -1)
	{
		$sql='SELECT id, question, type, exercice_id FROM '.$TBL_QUESTIONS.' as questions LEFT JOIN '.$TBL_EXERCICE_QUESTION.' as quizz_questions ON questions.id=quizz_questions.question_id AND exercice_id IS NULL';
	}
	// if we have not selected any option in the list-box 'Filter'
	else
	{
		$sql="SELECT id,question,type FROM $TBL_QUESTIONS";
		// forces the value to 0
		$exerciseId=0;
	}
	
	$result=api_sql_query($sql,__FILE__,__LINE__);
	$nbrQuestions=Database::num_rows($result);

    echo '<tr>',
      '<td colspan="',($fromExercise?2:3),'">',
    	'<table border="0" cellpadding="0" cellspacing="0" width="100%">',
    	'<tr>',
    	  '<td>';
	if(!empty($fromExercise))
	{
		echo '<a href="admin.php?',api_get_cidreq(),'&exerciseId=',$fromExercise,'">&lt;&lt;',get_lang('GoBackToEx'),'</a>';
	}
	else
	{
		echo '<a href="admin.php?',api_get_cidreq(),'&newQuestion=yes">',get_lang('NewQu'),'</a>';
	}
	echo '</td>',
	 '<td align="right">';
	if(!empty($page))
	{
	   echo '<a href="',api_get_self(),'?',api_get_cidreq(),'&exerciseId=',$exerciseId,'&fromExercise=',$fromExercise,'&page=',($page-1),'">&lt;&lt; ',get_lang('PreviousPage'),'</a> |';
	}
	elseif($nbrQuestions > $limitQuestPage)
	{
	   echo '&lt;&lt; ',get_lang('PreviousPage'),' |';
	}

	if($nbrQuestions > $limitQuestPage)
	{
    	echo '<a href="',api_get_self(),'?',api_get_cidreq(),'&exerciseId=',$exerciseId,'&fromExercise=',$fromExercise,'&page=',($page+1),'">',get_lang('NextPage'),' &gt;&gt;</a>';
	}
	elseif($page)
	{
	   echo '  ',get_lang('NextPage'),' &gt;&gt;';
	}
    echo '</td>
	</tr>
	</table>
  </td>
</tr>
<tr bgcolor="#e6e6e6">';

	if(!empty($fromExercise))
	{
        echo '<th>',get_lang('Question'),'</th>',
            '<th>',get_lang('Reuse'),'</th>';
	}
	else
	{
        echo '<td width="60%" align="center">',get_lang('Question'),'</td>',
            '<td width="20%" align="center">',get_lang('Modify'),'</td>',
            '<td width="20%" align="center">',get_lang('Delete'),'</td>';
	}
    echo '</tr>';
	$i=1;

	while ($row = Database::fetch_array($result))
	{
		// if we come from the exercise administration to get a question, 
        // don't show the questions already used by that exercise
		if (!$fromExercise || !isset($objExercise) || !($objExercise instanceOf Exercise) || (!$objExercise->isInList($row['id'])))
		{
            echo '<tr ',($i%2==0?'class="row_odd"':'class="row_even"'),'>';
            echo '  <td><a href="admin.php?',api_get_cidreq(),'&editQuestion=',$row['id'],'&fromExercise=',$fromExercise,'">',$row['question'],'</a></td>';
            echo '  <td>';
			if (empty($fromExercise))
			{
                echo '<a href="admin.php?',api_get_cidreq(),'?editQuestion=',$row['id'],'"><img src="../img/edit.gif" border="0" alt="',get_lang('Modify'),'"></a>',
                    '</td>',
                    '<td align="center">',
                    '<a href="',api_get_self(),'?',api_get_cidreq(),'&exerciseId=',$exerciseId,'&delete=',$row['id'],'" onclick="javascript:if(!confirm(\'',addslashes(htmlentities(get_lang('ConfirmYourChoice'),ENT_QUOTES,$charset)),'\')) return false;"><img src="../img/delete.gif" border="0" alt="',get_lang('Delete'),'"></a>',
                    '</td>';
			}
			else
			{
                echo '<a href="',api_get_self(),'?',api_get_cidreq(),'&recup=',$row['id'],'&fromExercise=',$fromExercise,'"><img src="../img/view_more_stats.gif" border="0" alt="',get_lang('Reuse'),'"></a>';
                echo '</td>';
			}
            echo '</tr>';

			// skips the last question, that is only used to know if we have or not to create a link "Next page"
			if($i == $limitQuestPage)
			{
				break;
			}

			$i++;
		}
	}

	if(!$nbrQuestions)
	{
        echo '<tr>',
            '<td colspan="',($fromExercise?2:3),'">',get_lang('NoQuestion'),'</td>',
            '</tr>';
	}
    echo '</table>',
        '</form>';
	Display::display_footer();
}
// if not admin of course
else
{
	api_not_allowed(true);
}
?>
