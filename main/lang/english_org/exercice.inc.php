<?php
/*
      +----------------------------------------------------------------------+
      | CLAROLINE version 1.3.0 $Revision: 1997 $                            |
      +----------------------------------------------------------------------+
      | Copyright (c) 2001, 2002 Universite catholique de Louvain (UCL)      |
      +----------------------------------------------------------------------+
      |   $Id: exercice.inc.php 1997 2004-07-07 14:55:42Z olivierb78 $     |
      |   English Translation                                                |
      +----------------------------------------------------------------------+
      |   This program is free software; you can redistribute it and/or      |
      |   modify it under the terms of the GNU General Public License        |
      |   as published by the Free Software Foundation; either version 2     |
      |   of the License, or (at your option) any later version.             |
      +----------------------------------------------------------------------+
      | Authors: Thomas Depraetere <depraetere@ipm.ucl.ac.be>                |
      |          Hugues Peeters    <peeters@ipm.ucl.ac.be>                   |
      |          Christophe Gesché <gesche@ipm.ucl.ac.be>                    |
      |          Olivier Brouckaert <oli.brouckaert@skynet.be>               |
      +----------------------------------------------------------------------+
      | Translator :                                                         |
      |          Thomas Depraetere <depraetere@ipm.ucl.ac.be>                |
      |          Andrew Lynn       <Andrew.Lynn@strath.ac.uk>                |
      |          Olivier Brouckaert <oli.brouckaert@skynet.be>               |
      +----------------------------------------------------------------------+
*/

/***************************************************************
*                   Language translation
****************************************************************
GOAL
****
Translate the interface in chosen language

*****************************************************************/

// general

$langExercice="Test";
$langExercices="Tests";
$langQuestion="Question";
$langQuestions="Questions";
$langAnswer="Answer";
$langAnswers="Answers";
$langActivate="Enable";
$langDeactivate="Disable";
$langComment="Comment";
$langUser="User";


// exercice.php

$langNoEx="There is no test for the moment";
$langNoResult="There is no result yet";
$langNewEx="New test";
$langYourResults="Your results";
$langStudentResults="Your member's results";

// exercise_admin.inc.php

$langExerciseType="Test type";
$langExerciseName="Test name";
$langExerciseDescription="Test description";
$langSimpleExercise="On an unique page";
$langSequentialExercise="One question per page (sequential)";
$langRandomQuestions="Random questions";
$langGiveExerciseName="Please give the test name";
$langSound="Audio or video file";
$langDeleteSound="Delete the audio or video file";


// question_admin.inc.php

$langNoAnswer="There is no answer for the moment";
$langGoBackToQuestionPool="Go back to the question pool";
$langGoBackToQuestionList="Go back to the question list";
$langQuestionAnswers="Answers to the question";
$langUsedInSeveralExercises="Warning ! This question and its answers are used in several tests. Would you like to modify them";
$langModifyInAllExercises="in all tests";
$langModifyInThisExercise="only in the current test";


// statement_admin.inc.php

$langAnswerType="Answer type";
$langUniqueSelect="Multiple choice (Unique answer)";
$langMultipleSelect="Multiple choice (Multiple answers)";
$langFillBlanks="Fill in blanks";
$langMatching="Matching";
$langAddPicture="Add a picture (.GIF, .JPG or .PNG)";
$langReplacePicture="Replace the picture";
$langDeletePicture="Delete the picture";
$langQuestionDescription="Optional comment";
$langGiveQuestion="Please type the question";


// answer_admin.inc.php

$langWeightingForEachBlank="Please enter a weighting for each blank";
$langUseTagForBlank="use square brackets [...] to define one or more blanks";
$langQuestionWeighting="Weighting";
$langTrue="True";
$langMoreAnswers="+answ";
$langLessAnswers="-answ";
$langMoreElements="+elem";
$langLessElements="-elem";
$langTypeTextBelow="Please type your text below";
$langDefaultTextInBlanks="[British people] live in the [United Kingdom].";
$langDefaultMatchingOptA="rich";
$langDefaultMatchingOptB="good looking";
$langDefaultMakeCorrespond1="Your dady is";
$langDefaultMakeCorrespond2="Your mother is";
$langDefineOptions="Please define the options";
$langMakeCorrespond="Match them";
$langFillLists="Please fill the two lists below";
$langGiveText="Please type the text";
$langDefineBlanks="Please define at least one blank with square brackets [...]";
$langGiveAnswers="Please type the question's answers";
$langChooseGoodAnswer="Please check the correct answer";
$langChooseGoodAnswers="Please check one or more correct answers";


// question_list_admin.inc.php

$langNewQu="Create a question";
$langQuestionList="Question list of the test";
$langMoveUp="Move up";
$langMoveDown="Move down";
$langGetExistingQuestion="Get a question from the base";
$langFinishTest="Finish Test";


// question_pool.php

$langQuestionPool="Question pool";
$langOrphanQuestions="Orphan questions";
$langNoQuestion="There is no question for the moment";
$langAllExercises="All tests";
$langFilter="Filter";
$langGoBackToEx="Go back to the test";
$langReuse="Re-use";


// admin.php

$langExerciseManagement="Tests management";
$langQuestionManagement="Question / Answer management";
$langQuestionNotFound="Question not found";


// exercice_submit.php

$langExerciseNotFound="Test not found or not visible";
$langAlreadyAnswered="You already answered the question";


// exercise_result.php

$langElementList="Elements list";
$langResult="Result";
$langScore="Score";
$langCorrespondsTo="Corresponds to";
$langExpectedChoice="Expected choice";
$langYourTotalScore="Your total score is";
?>