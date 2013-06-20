<?php
/* For licensing terms, see /license.txt */

/**
 * General code for transactions.
 *
 * @package chamilo.library
 *
 * @see Migration
 */

/**
 * Base transaction log class.
 */
abstract class TransactionLog {
  /**
   * Represents the local branch, as stored in branch_transaction.branch_id.
   */
  const BRANCH_LOCAL = 1;
  /**
   * Represents the local transaction, as stored in branch_transaction.transaction_id.
   *
   * This value means it is originated in this chamilo installation.
   */
  const TRANSACTION_LOCAL = 0;
  /**
   * branch_transaction.status_id for local transactions.
   */
  const STATUS_LOCAL = 0;
  /**
   * branch_transaction.status_id for import pending transactions.
   */
  const STATUS_TO_BE_EXECUTED = 1;
  /**
   * branch_transaction.status_id for successfully imported transactions.
   */
  const STATUS_SUCCESSFUL = 2;
  /**
   * branch_transaction.status_id for failed imported transactions.
   */
  const STATUS_FAILED = 4;
  /**
   * branch_transaction.status_id for abandoned transactions.
   *
   * i.e. after some retries?
   */
  const STATUS_ABANDONNED = 5;

  /**
   * A local place to store the branch transaction table name.
   */
  protected static $table;
  /**
   * A local place to store the branch data transaction table name.
   */
  protected static $data_table;
  /**
   * The action the transaction is performing.
   *
   * This will be normally declared on a child class.
   */
  public $action;
  /**
   * A place to store an instace of the related controller class.
   */
  protected $controller;
  /**
   * Transaction identifier.
   */
  public $id;
  /**
   * Branch id this transaction comes from.
   *
   * Use TransactionLog::BRANCH_LOCAL for local transactions.
   */
  public $branch_id;
  /**
   * The remote system branch transaction id.
   *
   * Use TransactionLog::TRANSACTION_LOCAL for local transactions.
   */
  public $transaction_id;
  /**
   * The id of the element represented by this transaction.
   */
  public $item_id;
  /**
   * The status of the transaction.
   *
   * Use TransactionLog::STATUS_LOCAL for local transactions.
   */
  public $status_id;
  /**
   * Extra information for the transaction.
   *
   * This will be serialized and stored on transaction data table.
   *
   * @var array
   */
  public $data;

  /**
   * Basic building contructor.
   *
   * @param array $data
   *   An array with some values to initialize the object. One of the
   *   following: id, branch_id, transaction_id, item_id, orig_id, dest_id,
   *   info, status_id, data.
   */
  public function __construct($data) {
    if (empty($this->action)) {
      throw new Exception('No action set at the creation of the transaction class.');
    }
    self::$table = Database::get_main_table(TABLE_BRANCH_TRANSACTION);
    self::$data_table = Database::get_main_table(TABLE_BRANCH_TRANSACTION_DATA);
    // time_insert and time_update are handled manually.
    $fields = array(
      'id' => FALSE,
      'branch_id' => TransactionLog::BRANCH_LOCAL,
      'transaction_id' => TransactionLog::TRANSACTION_LOCAL,
      'item_id' => FALSE,
      'status_id' => TransactionLog::STATUS_LOCAL,
      'data' => array(),
      // @todo The following fields are legacy fields from initial migration
      // implementation and probably need to be removed from the object and
      // the table soon.
      'orig_id' => NULL,
      'dest_id' => NULL,
      'info' => NULL,
    );
    foreach ($fields as $field => $default_value) {
      if (isset($data[$field])) {
        $this->$field = $data[$field];
      }
      elseif ($default_value !== FALSE) {
        $this->$field = $default_value;
      }
    }
  }

  public function getController() {
    if (empty($this->controller)) {
      $transaction_actions_map = TransactionLog::getTransactionMappingSettings($this->action);
      $controller_class = $transaction_actions_map['controller'];
      $this->controller = new $controller_class();
    }
    return $this->controller;
  }

  /**
   * Persists a transaction to the database.
   */
  public function save() {
    $transaction_row = array();
    if (isset($this->id)) {
      $this->time_update = api_get_utc_datetime();
      $fields = array('transaction_id', 'branch_id', 'action', 'item_id', 'orig_id', 'dest_id', 'info', 'status_id', 'time_update');
      $transaction_row = array();
      foreach ($fields as $field) {
        if (isset($this->$field)) {
          $transaction_row[$field] = $this->$field;
        }
      }
      Database::update(self::$table, $transaction_row, array('id = ?' => $this->id));
    }
    else {
      $this->time_insert = $this->time_update = api_get_utc_datetime();
      $fields = array('transaction_id', 'branch_id', 'action', 'item_id', 'orig_id', 'dest_id', 'info', 'status_id', 'time_insert', 'time_update');
      foreach ($fields as $field) {
        if (isset($this->$field)) {
          $transaction_row[$field] = $this->$field;
        }
      }
      $this->id = Database::insert(self::$table, $transaction_row);
    }
    if (!empty($this->data)) {
      $this->saveData();
    }
  }

  /**
   * Persists data to the transaction data table if needed.
   */
  public function saveData() {
    $data = $this->loadData();
    if (empty($this->data)) {
      // Nothing to save.
      return;
    }
    Database::delete(self::$data_table, array('where' => array('id = ?' => $this->id)));
    Database::insert(self::$data_table, array('id' => $this->id, 'data' => serialize($this->data)));
  }

  /**
   * Deletes a transaction by id.
   */
  public function delete() {
    return Database::delete(self::$table, array('where' => array('id = ?' => $this->id)));
  }

  /**
   * Loading for data table.
   *
   * @return array
   *   Branch transaction data as array corresponding to current object.
   */
  public function loadData() {
    if (empty($this->id)) {
      // No id, no data.
      return array();
    }
    $results = Database::select('id', self::$data_table, array('where' => array('id = ?' => array($this->id))));
    foreach ($results as $id => $result) {
      $results[$id]['data'] = unserialize($results[$id]['data']);
    }
  }

  /**
   * Retrieves transaction settings.
   *
   * @return array
   *   Transaction log setting values identified by its action as key.
   */
  public static function getTransactionSettings($reset = FALSE) {
    static $settings;
    if (isset($settings) && !$reset) {
      return $settings;
    }
    $settings = array();
    $log_transactions_settings = api_get_settings('LogTransactions');
    foreach ($log_transactions_settings as $setting) {
      $settings[$setting['subkey']] = $setting;
    }
    return $settings;
  }

  /**
   * Retrieves transaction mapping settings.
   *
   * @return array
   *   Every item is an array of mapping settings, keyed by transaction action,
   *   with the following keys:
   *   - class: The transaction class name associated with this action.
   *   - controller: The transaction controller class name associated with this
   *     action.
   */
  public static function getTransactionMappingSettings($action = NULL, $reset = FALSE) {
    static $settings;
    if (isset($settings) && !$reset) {
      if (!empty($action)) {
        return $settings[$action];
      }
      return $settings;
    }
    $settings = array();
    $transaction_mapping_settings = api_get_settings('TransactionMapping');
    foreach ($transaction_mapping_settings as $setting) {
      $maps = unserialize($setting['selected_value']);
      $settings[$setting['subkey']] = $maps;
    }
    if (!empty($action)) {
      return $settings[$action];
    }
    return $settings;
  }

  /**
   * Import this transaction to the system.
   *
   * @trows TransactionImportException
   *   If any step for re-creating the element fails, an exception should be
   *   raised.
   */
  abstract public function import();

  /**
   * Export this transaction out of the system.
   *
   * Notice that local transaction does not always contain all the needed
   * information to reproduce the element on another (branch) system. This
   * method takes care about the missing parts not stored to avoid duplication.
   *
   * @trows TransactionExportException
   *   If any step for exporting the element fails, an exception should be
   *   raised.
   *
   * @return string
   *   JSON string representing this transaction as expected by corresponding
   *   import().
   */
  abstract public function export();
}

/**
 * Controller class for transactions.
 */
class TransactionLogController {
  /**
   * A local place to store the branch transaction table name.
   */
  protected $table;
  /**
   * A local place to store the branch transaction data table name.
   */
  protected $data_table;

  public function __construct() {
    $this->table = Database::get_main_table(TABLE_BRANCH_TRANSACTION);
    $this->data_table = Database::get_main_table(TABLE_BRANCH_TRANSACTION_DATA);
  }

  /**
   * General load method.
   *
   * @param array $db_fields
   *   An array containing equal conditions to combine wih AND to add to where.
   *   i.e array('branch_id' => 1) means WHERE 'branch_id' = 1.
   *
   * @return array
   *   A list of TransactionLog object that match passed conditions.
   */
  public function load($db_fields) {
    $transaction_actions_map = TransactionLog::getTransactionMappingSettings();
    foreach ($db_fields as $db_field => $db_value) {
      $conditions[] = "$db_field = ?";
      $values[] = $db_value;
    }
    $results = Database::select('*', $this->table, array('where' => array(implode(' AND ', $conditions) => $values)));
    $objects = array();
    foreach ($results as $result) {
      $class_name = $transaction_actions_map[$result['action']]['class'];
      $objects[] = new $class_name($result);
    }
    return $objects;
  }

  /**
   * Loads by id.
   *
   * @param int
   *   branch_transaction.id
   *
   * @return boolean|TransactionLog
   *   FALSE if not found, or the corresponding object.
   */
  public function load_by_id($id) {
    $transactions = $this->load(array('id' => $id));
    if (empty($transactions)) {
      return FALSE;
    }
    return array_shift($transactions);
  }

  /**
   * Load by branch and transaction.
   *
   * @param int $branch_id
   *   The branch_transaction.branch_id to search.
   * @param string $transaction_id
   *   The branch_transaction.transaction_id to search.
   *
   * @return boolean|TransactionLog
   *   FALSE if not found, or the corresponding object.
   */
  public function load_by_branch_and_transaction($branch_id, $transaction_id) {
    $transactions = $this->load(array('branch_id' => $branch_id, 'transaction_id' => $transaction_id));
    if (empty($transactions)) {
      return FALSE;
    }
    return array_shift($transactions);
  }

  /**
   * Adds the information to the transaction tables.
   *
   * @param array $exported_transactions
   *   A set of transactions to import. Each of them as provided by the
   *   corresponding transaction object export().
   *
   * @return array
   *   A set of transaction ids correctly added.
   */
  public function importToLog($exported_transactions) {
    $transaction_actions_map = TransactionLog::getTransactionMappingSettings();
    $added_transactions = array();
    foreach ($exported_transactions as $exported_transaction) {
      $transaction_data = json_decode($exported_transaction, TRUE);
      $class_name = $transaction_actions_map[$transaction_data['action']]['class'];
      $transaction = new $class_name($transaction_data);
      $transaction->save();
      $added_transactions[] = $transaction->id;
    }
    return $added_transactions;
  }
}

/**
 * A custom exception for transaction imports.
 */
class TransactionImportException extends Exception {
}

/**
 * A custom exception for transaction exports.
 */
class TransactionExportException extends Exception {
}

/**
 * Exercise tool attempt transaction.
 */
class ExerciseAttemptTransactionLog extends TransactionLog {
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
  public function export() {
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
   * @todo Import Log?
   */
  public function import() {
    if ($this->status_id == TransactionLog::STATUS_LOCAL) {
      // Do not allow importing local transactions.
      return FALSE;
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
      throw new TransactionImportException('Undefined course id on stat information.');
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
    // First, create the exercise attempt to obtain an id in the destination system.
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
