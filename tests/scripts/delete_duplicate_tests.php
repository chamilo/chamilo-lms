<?php
/* For licensing terms, see /license.txt */
/**
 * This script removes duplicated tests and questions created
 * through something gone wrong in the course backup/copy process.
 * It identifies duplicate tests and questions by title and
 * makes sure no results are associated with the duplicate test, and
 * that the duplicate test is not used in a learning path.
 * This script should be located inside the tests/scripts/ folder to work.
 * It can be run more than one time as it will only ever affect orphan
 * questions and duplicate tests.
 * If you have a very large number of tests, we recommend you temporarily
 * comment out the api_item_property_update() calls in Exercise::delete() and
 * Question::delete().
 * Chances are there is not even a registry of those tests there in the
 * first place (they were probably duplicated through a short process) and
 * this is where most of the time is spent during deletion.
 * @author Yannick Warnier <yannick.warnier@beeznest.com>
 */
exit; //remove this line to execute from the command line
use ChamiloSession as Session;

ini_set('memory_limit', '256M');

if (PHP_SAPI !== 'cli') {
    die('This script can only be executed from the command line');
}

require_once __DIR__.'/../../main/inc/global.inc.php';

$tests = [];

$debug = false;
$_user['user_id'] = 1;
Session::write('_user', $_user);

echo "[".time()."] Querying courses\n";
$sql = "SELECT id, code FROM course order by id";

$resCourse = Database::query($sql);
if ($resCourse === false) {
    exit('Could not find any course'.PHP_EOL);
}
$countCourses = Database::num_rows($resCourse);
echo "[".time()."] Found $countCourses courses".PHP_EOL;

$duplicateTestsCount = 0;
$originalTestsCount = 0;
$deletedTestsCount = 0;
$deletedQuestionsCount = 0;
$usedQuestionIds = [];
$questionsInTests = [];
$testsWithTracking = 0;
$testsInLP = 0;

// Get the questions that are still used
$sql = "SELECT question_id FROM c_quiz_rel_question WHERE exercice_id != -1";
$res = Database::query($sql);
if (Database::num_rows($res) > 0) {
    while ($row = Database::fetch_assoc($res)) {
        if (!empty($usedQuestionIds[$row['question_id']])) {
            // Do not delete questions that are otherwise used by original tests
            continue;
        }
        $usedQuestionIds[$row['question_id']] = true;
    }
}
echo "[".time()."] Found ".count($usedQuestionIds)." questions still used in a test.".PHP_EOL;

// First, proceed to the deletion of orphan questions, to get rid of
// those which are not linked to any exercise anyway
$deletedOrphanQuestionsCount = 0;
$orphanList = [];
// Delete the ones marked with exercice_id = -1 in c_quiz_rel_question
$sql = "SELECT question_id, exercice_id, c_id FROM c_quiz_rel_question WHERE exercice_id = -1";
$res = Database::query($sql);
echo "[".time()."] Deleting initial orphan questions from c_quiz_rel_question...".PHP_EOL;
if (Database::num_rows($res) > 0) {
    while ($row = Database::fetch_assoc($res)) {
        if (!empty($usedQuestionIds[$row['question_id']])) {
            // Do not delete questions that are otherwise used by original tests
            continue;
        }
        $sqlDelete = "DELETE FROM c_quiz_rel_question
            WHERE question_id = ".$row['question_id']."
            AND exercice_id = -1
            AND c_id = ".$row['c_id'];
        $resDelete = Database::query($sqlDelete);
        /*
        // This type of question duplicate didn't seem to register in
        // c_item_property, so we don't need to update it either.
        api_item_property_update(
            ['real_id' => $row['c_id']],
            TOOL_QUIZ,
            $row['question_id'],
            'QuizQuestionDeleted',
            $_user['user_id']
        );
        */
        Event::addEvent(
            LOG_QUESTION_REMOVED_FROM_QUIZ,
            LOG_QUESTION_ID,
            $row['question_id']
        );
        if ($debug) {
            echo $row['question_id'].', ';
        }
        $deletedOrphanQuestionsCount++;
        $orphanList[$row['question_id']] = true;
    }
}
echo PHP_EOL;
echo "[".time()."] Removed ".count($orphanList)." question references with test=-1 from c_quiz_rel_question.".PHP_EOL;

// Delete the questions in c_quiz_question that do not exist in
// c_quiz_rel_question at all (so they are not used in any case).
$localOrphans = 0;

echo "[".time()."] Really deleting orphan questions...".PHP_EOL;
$sql = "SELECT qq.iid, qq.c_id, c.directory from c_quiz_question qq, course c WHERE qq.iid NOT IN (SELECT DISTINCT(question_id) FROM c_quiz_rel_question) and c.id = qq.c_id";
$res = Database::query($sql);
$num = Database::num_rows($res);
if ($num > 0) {
    if ($debug) {
        echo "Found $num questions to delete (if not used)...".PHP_EOL;
    }
    while ($row = Database::fetch_assoc($res)) {
        if (!empty($usedQuestonIds[$row['iid']])) {
            // Do not delete questions that are otherwise used by original tests
            continue;
        }
        $sql = "DELETE FROM c_quiz_answer
                    WHERE question_id = ".$row['iid'];
        Database::query($sql);

        // remove the category of this question in the question_rel_category table
        $sql = "DELETE FROM c_quiz_question_rel_category
                    WHERE
                        c_id = ".$row['c_id']." AND
                        question_id = ".$row['iid'];
        Database::query($sql);

        // Add extra fields.
        $extraField = new ExtraFieldValue('question');
        $extraField->deleteValuesByItem($row['iid']);

        $sql = "DELETE FROM c_quiz_question
                    WHERE iid = ".$row['iid'];
        Database::query($sql);
        /*
        // This type of question duplicate didn't seem to register in
        // c_item_property, so we don't need to update it either.
        api_item_property_update(
            ['real_id' => $row['c_id']],
            TOOL_QUIZ,
            $row['question_id'],
            'QuizQuestionDeleted',
            $_user['user_id']
        );
        */
        Event::addEvent(
            LOG_QUESTION_DELETED,
            LOG_QUESTION_ID,
            $row['iid']
        );

        if ($debug) {
            echo $row['iid'].', ';
        }
        unset($question);
        $deletedOrphanQuestionsCount++;
        $localOrphans++;
        $orphanList[$row['iid']] = true;
    }
}
echo PHP_EOL;
echo "[".time()."] Removed ".$localOrphans." questions that were not in c_quiz_rel_question anymore.".PHP_EOL;

// Search for duplicate tests, by looking for tests that have the exact same
// title in the same course
echo "Iterating on courses: ";
while ($course = Database::fetch_assoc($resCourse)) {
    $course['real_id'] = $course['id'];
    if ($debug) {
        echo $course['id'].'..(';
    }
    $sql2 = "SELECT iid, title FROM c_quiz WHERE c_id = ".$course['id']." ORDER BY title, iid";
    $res2 = Database::query($sql2);
    if ($res2 === false) {
        die("Error querying tests in course code ".$course['code'].": ".Database::error($res2)."\n");
    }

    $lastTestTitle = '';
    $lastOriginalTestId = 0;
    if (Database::num_rows($res2) > 0) {
        while ($test = Database::fetch_assoc($res2)) {
            // Simply get the questions for all the tests queried
            $sqlTestQuestions = "SELECT question_id from c_quiz_rel_question WHERE c_id = ".$course['id']." AND exercice_id = ".$test['iid'];
            $resTestQuestions = Database::query($sqlTestQuestions);
            if (Database::num_rows($resTestQuestions) > 0) {
                while ($rowTestQuestions = Database::fetch_assoc($resTestQuestions)) {
                    $questionsInTests[$rowTestQuestions['question_id']] = true;
                }
            }

            if ($lastTestTitle != $test['title']) {
                //echo "New title, new test serie in course ".$course['id'].": ".$test['title'].PHP_EOL;
                // The title is different -> moving on to another test, but
                // recording questions' IDs just in case
                $lastTestTitle = $test['title'];
                $lastOriginalTestId = $test['iid'];
                $originalTestsCount++;
                $sql2b = "SELECT question_id FROM c_quiz_rel_question WHERE c_id = ".$course['id']." AND exercice_id = ".$test['iid'];
                $res2b = Database::query($sql2b);
                if (Database::num_rows($res2b) > 0) {
                    while ($row2b = Database::fetch_assoc($res2b)) {
                        // Store the question iid in the index to avoid duplicates
                        // This might have several hundred thousand records, make it concise
                        $usedQuestionIds[$row2b['question_id']] = true;
                        $questionsInTests[$row2b['question_id']] = true;
                    }
                }
            } else {
                // A likely duplicate...
                // Only bother if the test's internal ID is higher than the
                // last original test ID, which means this (duplicate) test
                // has been created *after* the original.
                if ($lastOriginalTestId < $test['iid']) {
                    // Check if some student took the test (despite it being
                    // of duplicate title)
                    $sql3 = "SELECT exe_id FROM track_e_exercises WHERE c_id = ".$course['id']." AND exe_exo_id = ".$test['iid'];
                    $res3 = Database::query($sql3);
                    if (0 === Database::num_rows($res3)) {
                        // No results in the logs. Likely to be removed, but
                        // check if included in a LP
                        $sql4 = "SELECT lp_id FROM c_lp_item WHERE c_id = ".$course['id']." AND item_type = 'quiz' AND ref = ".$test['iid'];
                        $res4 = Database::query($sql4);
                        if (0 === Database::num_rows($res4)) {
                            // Not included in any LP. Delete.
                            $sql5 = "SELECT iid, question_id FROM c_quiz_rel_question WHERE c_id = ".$course['id']." AND exercice_id = ".$test['iid'];
                            $res5 = Database::query($sql5);
                            $num5 = Database::num_rows($res5);
                            // delete questions
                            if ($num5 > 0) {
                                while ($row5 = Database::fetch_assoc($res5)) {
                                    $questionsInTests[$row5['question_id']] = true;
                                    $deletedQuestionsCount++;
                                    // questions will be disabled during the
                                    // test deletion below and can be deleted
                                    // through a second run
                                }
                            }
                            $deletedQuestionsCount += $num5;
                            // delete test
                            $exercise = new Exercise($course['id']);
                            if ($exercise->read($test['iid'])) {
                                // Delete the test and mark questions as orphan if only used there
                                $exercise->delete(true);
                                if ($debug) {
                                    echo $test['iid'].', ';
                                }
                                $deletedTestsCount++;
                            }
                            unset($exercise);
                        } else {
                            //echo "Found test ".$test['iid']." included in a learning path in ".$course['code'].". Not deleting.".PHP_EOL;
                            $sql2b = "SELECT question_id FROM c_quiz_rel_question WHERE c_id = ".$course['id']." AND exercice_id = ".$test['iid'];
                            $res2b = Database::query($sql2b);
                            if (Database::num_rows($res2b) > 0) {
                                while ($row2b = Database::fetch_assoc($res2b)) {
                                    // Store the question iid in the index to avoid duplicates
                                    // This might have several hundred thousand records, make it concise
                                    $usedQuestionIds[$row2b['question_id']] = true;
                                    $questionsInTests[$row2b['question_id']] = true;
                                }
                            }
                            $testsInLP++;
                        }
                    } else {
                        // else there are results, so do not delete
                        //echo "Found results for test ".$test['iid']." in course ".$course['code'].". Not deleting.".PHP_EOL;
                        $sql2b = "SELECT question_id FROM c_quiz_rel_question WHERE c_id = ".$course['id']." AND exercice_id = ".$test['iid'];
                        $res2b = Database::query($sql2b);
                        if (Database::num_rows($res2b) > 0) {
                            while ($row2b = Database::fetch_assoc($res2b)) {
                                // Store the question iid in the index to avoid duplicates
                                // This might have several hundred thousand records, make it concise
                                $usedQuestionIds[$row2b['question_id']] = true;
                                $questionsInTests[$row2b['question_id']] = true;
                            }
                        }
                        $testsWithTracking++;
                    }
                }
                $duplicateTestsCount++;
            }
        } // end while on c_quiz
    }
    if ($debug) {
        echo ') ';
    }
} // end while on course
echo PHP_EOL;

echo "[".time()."] Cleaning up 'new' orphans...".PHP_EOL;
// Now clean up any question left that is not inside $usedQuestionIds
$sql = "SELECT iid, c_id FROM c_quiz_question ORDER BY iid";
$res = Database::query($sql);
$localCount = 0;
if (Database::num_rows($res) > 0) {
    while ($row = Database::fetch_assoc($res)) {
        if (empty($usedQuestionIds[$row['iid']])) {
            // If this question wasn't used anywhere, delete it
            $question = Question::read($row['iid'], ['real_id' => $row['c_id']]);
            $question->delete(0, false);
            $deletedQuestionsCount++;
            $localCount++;
        }
    }
}

echo "[".time()."] Done cleaning $localCount new orphan questions.".PHP_EOL;
echo "Found $originalTestsCount original tests and $duplicateTestsCount duplicate tests...".PHP_EOL;
echo "but $testsWithTracking had results and $testsInLP were included in learning paths.".PHP_EOL;
echo "Deleted $deletedTestsCount ($duplicateTestsCount - $testsWithTracking - $testsInLP) tests and $deletedQuestionsCount questions.".PHP_EOL;
echo count($usedQuestionIds)." questions were still used".PHP_EOL;
