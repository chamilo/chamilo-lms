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
   * The name of the related controller class.
   */
  protected $controller_class = 'TransactionLogController';
  /**
   * A place to store an instace of the related controller class.
   */
  public $controller;
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
    $this->controller = new $this->controller_class();
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
   * @return mixed
   *   String representing this transaction as expected by corresponding
   *   controller importtoLog() or FALSE if export failed.
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
  /**
   * The associated transaction class name.
   */
  public $class;

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
    foreach ($db_fields as $db_field => $db_value) {
      $conditions[] = "$db_field = ?";
      $values[] = $db_value;
    }
    $results = Database::select('*', $this->table, array('where' => array(implode(' AND ', $conditions) => $values)));
    $objects = array();
    foreach ($results as $result) {
      $objects[] = new $this->class($result);
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
   * @param array $transaction_data
   *   A set of transaction arrays. Each of them as required by
   *   TransactionLog::__construct().
   */
  public function importToLog($transaction_data) {
    foreach ($transaction_data as $item) {
      $transaction = new $this->class($item);
      $transaction->save();
    }
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
   */
  public $action = 'exercise_attempt';

  /**
   * {@inheritdoc}
   */
  public $controller_class = 'ExerciseAttemptTransactionLogController';

  /**
   * {@inheritdoc}
   */
  public function export() {
    throw new TransactionExportException('Unimplemented export method on ' . __CLASS__);
    // @fixme Actually do exporting.
    if (empty($this->item_id)) {
      throw new TransactionExportException('Undefined item_id');
    }
    list($exercise_id, $attempt_id) = explode(':', $this->item_id);
    if (empty($this->data['course_id'])) {
      throw new TransactionExportException('Undefined course_id');
    }
    $exercise = new Exercise($this->data['course_id']);
    if (!$exercise->read($exercise_id)) {
      throw new TransactionExportException(sprintf('The included exercise id "%d" on course with id "%d" does not currently exists on the database.', $exercise_id, $this->data['course_id']));
    }
    $attempt = $exercise->getStatTrackExerciseInfoByExeId($attempt_id);
    if (empty($attempt)) {
      throw new TransactionExportException(sprintf('There is no data associated with exe_id "%d" on the database.', $attempt_id));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function import() {
    throw new TransactionImportException('Unimplemented import method on ' . __CLASS__);
    // @fixme Actually do importing.
    if ($this->status_id == TransactionLog::STATUS_LOCAL) {
      return FALSE;
    }
    if (empty($this->data['course_id'])) {
      throw new TransactionImportException('Undefined course_id');
    }
    $exercise = new Exercise($this->data['course_id']);
    if (!$exercise->read($exercise_id)) {
      throw new TransactionImportException(sprintf('The included exercise id "%d" on course with id "%d" does not currently exists on the database.', $exercise_id, $this->data['course_id']));
    }
    // @todo Decide what to use to create the attempt:
    // - exercise_attempt($score, $answer, $question_id, $exe_id, $position, $exercise_id = 0, $nano = null)
    // - $objExercise->manageAnswers($exeId, $questionId, $choice, $from = 'exercise_show', $exerciseResultCoordinates = array(), $saved_results = true, $from_database = false, $show_result = true, $hotspot_delineation_result = array())
    // on fail throw new TransactionImportException(sprintf('Could not create exercise attempt: %s.', print_r($this, 1)));
  }
}

/**
 * Controller for exercise tool attempt transactions.
 */
class ExerciseAttemptTransactionLogController extends TransactionLogController {
  /**
   * {@inheritdoc}
   */
  public $class = 'ExerciseAttemptTransactionLog';

  /**
   * Retrieves an individual exercise attempt transaction.
   *
   * @return boolean|ExerciseAttemptTransactionLog
   *   FALSE if not found, or the corresponding object.
   */
  public function load_exercise_attempt($exercise_id, $attempt_id, $branch_id = TransactionLog::BRANCH_LOCAL) {
    $exercise_attempt_id = sprintf('%s:%s', $exercise_id, $attempt_id);
    $transactions = $this->load(array('branch_id' => $branch_id, 'item_id' => $exercise_attempt_id));
    if (empty($transactions)) {
      return FALSE;
    }
    return array_shift($transactions);
  }
}
