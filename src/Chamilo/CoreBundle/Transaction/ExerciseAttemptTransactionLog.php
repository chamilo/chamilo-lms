<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Transaction;

/**
 * Exercise tool attempt transaction.
 */
class ExerciseAttemptTransactionLog extends TransactionLog
{
    /**
     * {@inheritdoc}
     *
     * Exercise attempts have the following data depending on its scope:
     * - Normal transaction: empty.
     * - Exported transaction:
     *   - 'stat_info': return of Exercise::getStatTrackExerciseInfoByExeId();
     *   - 'attempt_info': return of get_exercise_results_by_attempt().
     */
    public $data;
    /**
     * {@inheritdoc}
     */
    public $action = 'exercise_attempt';

    /**
     * {@inheritdoc}
     */
    public function export()
    {
        if (empty($this->item_id)) {
            throw new TransactionExportException('Undefined item_id');
        }
        $attempt_id = $this->item_id;
        $attempt = get_exercise_results_by_attempt($attempt_id);
        if (empty($attempt)) {
            throw new TransactionExportException(sprintf('There is no exercise attempt information associated with exe_id "%d" in the database.', $attempt_id));
        }
        $exercise = new Exercise();
        $exercise_stat_info = $exercise->getStatTrackExerciseInfoByExeId($attempt_id);
        if (empty($exercise_stat_info)) {
            throw new TransactionExportException(sprintf('There is no exercise stat information associated with exe_id "%d" in the database.', $attempt_id));
        }
        // Exercise read expects course id set.
        $exercise->course_id = $exercise_stat_info['c_id'];
        if (!$exercise->read($exercise_stat_info['exe_exo_id'])) {
            throw new TransactionExportException(sprintf('The associated exercise id "%d" does not currently exist in the database.', $exercise_stat_info['exe_exo_id']));
        }
        // Prepare the export.
        $this->data['stat_info'] = $exercise_stat_info;
        $this->data['attempt_info'] = $attempt;
        $content = (array) $this;

        return json_encode($content);
    }

    /**
     * {@inheritdoc}
     * @todo Review.
     */
    public function import()
    {
        if ($this->branch_id == TransactionLog::BRANCH_LOCAL) {
            // Do not allow importing local transactions.
            throw new TransactionImportException('Cannot import a local transaction');
        }

        // Review basic information.
        if (empty($this->item_id)) {
            throw new TransactionImportException('Undefined item_id');
        }
        $attempt_id = $this->item_id;
        if (empty($this->data['stat_info'])) {
            throw new TransactionImportException('Undefined exercise stat information.');
        }
        $stat_info = $this->data['stat_info'];
        if (empty($this->data['attempt_info'])) {
            throw new TransactionImportException('Undefined exercise attempt information.');
        }
        $attempt_info = $this->data['attempt_info'];
        // By pass one level which does not provide useful information.
        $attempt_info = array_shift($attempt_info);

        // Review consistency of provided information.
        if (empty($stat_info['c_id'])) {
            throw new TransactionImportException('Undefined course id on stat information.');
        }
        $course_id = $stat_info['c_id'];
        $course_info = api_get_course_info_by_id($course_id);
        if (empty($course_info)) {
            throw new TransactionImportException(sprintf('The included course id "%d" does not currently exist in the database.', $course_id));
        }
        if (empty($stat_info['exe_exo_id'])) {
            throw new TransactionImportException('Undefined exercise id on stat information.');
        }
        $exercise_id = $stat_info['exe_exo_id'];
        $exercise = new Exercise($course_id);
        // Exercise read expects course id set.
        $exercise->course_id = $course_id;
        if (!$exercise->read($exercise_id)) {
            throw new TransactionImportException(sprintf('The included exercise id "%d" on course with id "%d" does not currently exist in the database.', $exercise_id, $course_id));
        }
        if (empty($stat_info['exe_user_id'])) {
            throw new TransactionImportException('Undefined user id on stat information.');
        }
        $user_id = $stat_info['exe_user_id'];
        $user_info = api_get_user_info($user_id);
        if (!$user_info) {
            throw new TransactionImportException(sprintf('The included user id "%d" does not currently exist in the database.', $user_id));
        }
        // For now assume the rest of information provided on stat_info and
        // attempt_info is good enough.

        // Process the attempt results.
        // First, create the exercise attempt to obtain an id in the
        // destination system.
        $question_list = explode(',', $stat_info['data_tracking']);
        $imported_exe_id = $exercise->save_stat_track_exercise_info($stat_info['expired_time_control'], $stat_info['orig_lp_id'], $stat_info['orig_lp_item_id'], 0, $question_list, $stat_info['exe_weighting']);
        if (!$imported_exe_id) {
            throw new TransactionImportException(sprintf('Could not create exercise stat information correctly on course with id "%d" for exercise_id "%d"', $course_id, $exercise_id));
        }
        // Then, process the results.
        foreach ($attempt_info['question_list'] as $question_id => $attempt_answer_info) {
            // Use saveExerciseAttempt($score, $answer, $question_id, $exe_id, $position, $exercise_id = 0, $nano = null, $user_id = null, $course_id = null, $session_id = null, $learnpath_id = null, $learnpath_item_id = null)
            // @fixme What nano means and there to retrieve it?
            $nano = null;
            $attempt_answer_id = saveExerciseAttempt($attempt_answer_info['marks'], $attempt_answer_info['answer'], $question_id, $imported_exe_id, $attempt_answer_info['position'], $exercise_id, $nano, $user_id, $course_id, $stat_info['session_id'], $stat_info['orig_lp_id'], $stat_info['orig_lp_item_id']);
        }
    }
}
