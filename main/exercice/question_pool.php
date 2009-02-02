<?php // $Id: question_pool.php 18159 2009-02-02 17:30:00Z herodoto $
 
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
*	Question Pool
* 	This script allows administrators to manage questions and add them into their exercises.
* 	One question can be in several exercises
*	@package dokeos.exercise
* 	@author Olivier Brouckaert
* 	@version $Id: question_pool.php 18159 2009-02-02 17:30:00Z herodoto $
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
if(isset($_GET['exerciseLevel'])){
	$exerciseLevel = intval($_GET['exerciseLevel']);
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

<h3><?php echo $nameTools; ?></h3>

<div class="actions">
	<?php
	if(!empty($fromExercise))
	{
		echo '<a href="admin.php?',api_get_cidreq(),'&exerciseId=',$fromExercise,'">'.Display::return_icon('quiz.gif', get_lang('GoBackToEx')),get_lang('GoBackToEx'),'</a>';
	}
	else
	{
		echo '<a href="admin.php?',api_get_cidreq(),'&newQuestion=yes">'.Display::return_icon('new_test.gif'),get_lang('NewQu'),'</a>';
	}
	?>

	<form method="get" action="<?php echo api_get_self(); ?>" style="display:inline;">
	<input type="hidden" name="fromExercise" value="<?php echo $fromExercise; ?>">
	
	<?php echo get_lang('Filter'); ?> : 
	<select name="exerciseId">
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
    </select>
    
    <?php echo get_lang('Difficulty'); ?> : <select name="exerciseLevel">
	<option value="-1">-- <?php echo get_lang('AllExercises'); ?> --</option>
	<?php 
		$sql = "SELECT MAX(level) FROM $TBL_QUESTIONS";
		$result = api_sql_query($sql,__FILE__,__LINE__);
		if ($row = Database::fetch_array($result)) {
			$maxLevel = $row[0];
		} else {
			$maxLevel = -1;
		}
		for ($level = 0; $level <= $maxLevel; $level++) {
		?>	
			<option value="<?php echo $level; ?>" <?php if($exerciseLevel == $level) echo 'selected="selected"'; ?>><?php echo $level; ?></option>	
		<?php
		}
	?> 

    </select> 


    <input type="submit" value="<?php echo get_lang('Ok'); ?>">
    </form>
</div>

<table class="data_table">
<?php
	$from=$page*$limitQuestPage;

	// if we have selected an exercise in the list-box 'Filter'
	if($exerciseId > 0)
	{
		//$sql="SELECT id,question,type FROM $TBL_EXERCICE_QUESTION,$TBL_QUESTIONS WHERE question_id=id AND exercice_id='".Database::escape_string($exerciseId)."' ORDER BY question_order LIMIT $from, ".($limitQuestPage + 1);
		$sql="SELECT id,question,type,level FROM $TBL_EXERCICE_QUESTION,$TBL_QUESTIONS WHERE question_id=id AND exercice_id='".Database::escape_string($exerciseId)."' ".
			 (!is_null($exerciseLevel) && $exerciseLevel >= 0 ? 'AND level=\''.$exerciseLevel.'\' ' : '')."ORDER BY question_order";
	} 
	// if we have selected the option 'Orphan questions' in the list-box 'Filter'
	elseif($exerciseId == -1)
	{
		// Old logic: When a test is deleted, the correspondent records in 'quiz' and 'quiz_rel_question' tables are deleted.
		//$sql='SELECT id, question, type, exercice_id FROM '.$TBL_QUESTIONS.' as questions LEFT JOIN '.$TBL_EXERCICE_QUESTION.' as quizz_questions ON questions.id=quizz_questions.question_id WHERE exercice_id IS NULL LIMIT $from, '.($limitQuestPage + 1);

		// New logic: When a test is deleted, the field 'active' takes value -1 (it is in the correspondent record in 'quiz' table).
		//$sql='SELECT questions.id, questions.question, questions.type, quizz_questions.exercice_id FROM '.$TBL_QUESTIONS.
		//	' as questions LEFT JOIN '.$TBL_EXERCICE_QUESTION.' as quizz_questions ON questions.id=quizz_questions.question_id LEFT JOIN '.$TBL_EXERCICES.
		//	' as exercices ON exercice_id=exercices.id WHERE exercices.active = -1 LIMIT $from, '.($limitQuestPage + 1);

		// This is more safe to changes, it is a mix between old and new logic.
		
		/*$sql='SELECT questions.id, questions.question, questions.type, quizz_questions.exercice_id FROM '.$TBL_QUESTIONS.
			' as questions LEFT JOIN '.$TBL_EXERCICE_QUESTION.' as quizz_questions ON questions.id=quizz_questions.question_id LEFT JOIN '.$TBL_EXERCICES.
			' as exercices ON exercice_id=exercices.id WHERE quizz_questions.exercice_id IS NULL OR exercices.active = -1 LIMIT '.$from.', '.($limitQuestPage + 1);
	
		*/
		$sql='SELECT id, question, type, exercice_id,level FROM '.$TBL_QUESTIONS.' as questions LEFT JOIN '.$TBL_EXERCICE_QUESTION.' as quizz_questions ON questions.id=quizz_questions.question_id AND exercice_id IS NULL '.
			(!is_null($exerciseLevel) && $exerciseLevel >= 0 ? 'WHERE level=\''.$exerciseLevel.'\' ' : '');

			
	}
	// if we have not selected any option in the list-box 'Filter'
	else
	{
		//$sql="SELECT id,question,type FROM $TBL_QUESTIONS LIMIT $from, ".($limitQuestPage + 1);
		$sql="SELECT id,question,type,level FROM $TBL_QUESTIONS ".
			(!is_null($exerciseLevel) && $exerciseLevel >= 0 ? 'WHERE level=\''.$exerciseLevel.'\' ' : '');
		// forces the value to 0
		$exerciseId=0;
	}

	$result=api_sql_query($sql,__FILE__,__LINE__);
	$nbrQuestions=Database::num_rows($result);
	
    echo '<tr>',
      '<td colspan="',($fromExercise?3:3),'">',
    	'<table border="0" cellpadding="0" cellspacing="0" width="100%">',
    	'<tr>',
    	  '<td>';
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
            '<th>',get_lang('Level'),'</th>',
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
	//print_r($objExercise->questionList);
	while ($row = Database::fetch_array($result))
	{		
		// if we come from the exercise administration to get a question, 
        // don't show the questions already used by that exercise
        
        /*if (!$fromExercise) {echo '1'; }   
        if (!isset($objExercise)){echo '2';}    
        if (!($objExercise instanceOf Exercise)){echo '3';}    
        if (!$objExercise->isInList($row['id'])) {echo '4';}        
        */
        
        // original recipe - 
      //if (!$fromExercise || !isset($objExercise) || !($objExercise instanceOf Exercise) || (!$objExercise->isInList($row['id'])))
		if (!$fromExercise || !isset($objExercise) || !($objExercise instanceOf Exercise) || (is_array($objExercise->questionList)) )
		{	
            echo '<tr ',($i%2==0?'class="row_odd"':'class="row_even"'),'>';
            echo '  <td><a href="admin.php?',api_get_cidreq(),'&editQuestion=',$row['id'],'&fromExercise=',$fromExercise,'">',$row['question'],'</a></td>';
            echo '  <td>';
			if (empty($fromExercise))
			{
                echo '<a href="admin.php?',api_get_cidreq(),'?editQuestion=',$row['id'],'"><img src="../img/edit.gif" border="0" alt="',get_lang('Modify'),'"></a>',
                    '</td>',
                    '<td align="center">',
                    '<a href="',api_get_self(),'?',api_get_cidreq(),'&exerciseId=',$exerciseId,'&delete=',$row['id'],'" onclick="javascript:if(!confirm(\'',addslashes(htmlentities(get_lang('ConfirmYourChoice'),ENT_QUOTES,$charset)),'\')) return false;"><img src="../img/delete.gif" border="0" alt="',get_lang('Delete'),'"></a>';
                    //'<a href="',api_get_self(),'?',api_get_cidreq(),'&exerciseId=',$exerciseId,'&delete=',$row['id'],'" onclick="javascript:if(!confirm(\'',addslashes(htmlentities(get_lang('ConfirmYourChoice'),ENT_QUOTES,$charset)),'\')) return false;"><img src="../img/delete.gif" border="0" alt="',get_lang('Delete'),'"></a>';
			}
			else
			{
                //echo $row['level'],'</td>',
//					'<td><a href="',api_get_self(),'?',api_get_cidreq(),'&recup=',$row['id'],'&fromExercise=',$fromExercise,'"><img src="../img/view_more_stats.gif" border="0" alt="',get_lang('Reuse'),'"></a>';
				echo $row['level'],'</td>',
					'<td><a href="',api_get_self(),'?',api_get_cidreq(),'&recup=',$row['id'],'&fromExercise=',$fromExercise,'"><img src="../img/view_more_stats.gif" border="0" alt="',get_lang('Reuse'),'"></a>';
			}
            echo '  </td>';
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
            '<td colspan="',($fromExercise?3:3),'">',get_lang('NoQuestion'),'</td>',
            '</tr>';
	}
    echo '</table>';
	Display::display_footer();
}
// if not admin of course
else
{
	api_not_allowed(true);
}
?>
