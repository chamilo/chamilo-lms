<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\Transaction;

/**
 * Controller for exercise tool attempt transactions.
 */
class ExerciseAttemptTransactionLogController extends TransactionLogController {
  /**
   * Retrieves an individual exercise attempt transaction.
   *
   * @return boolean|ExerciseAttemptTransactionLog
   *   FALSE if not found, or the corresponding object.
   */
  public function load_exercise_attempt($attempt_id, $branch_id = TransactionLog::BRANCH_LOCAL) {
    $transactions = $this->load(array('action' => 'exercise_attempt', 'branch_id' => $branch_id, 'item_id' => $attempt_id));
    if (empty($transactions)) {
      return FALSE;
    }
    return array_shift($transactions);
  }
}
