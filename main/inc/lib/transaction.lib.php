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
  const BRANCH_LOCAL = 1;
  const TRANSACTION_LOCAL = 0;
  const STATUS_LOCAL = 0;
  const STATUS_TO_BE_EXECUTED = 1;
  const STATUS_SUCCESSFUL = 2;
  const STATUS_FAILED = 4;
  const STATUS_ABANDONNED = 5;

  protected static $table;
  protected static $data_table;
  public $action;

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
      'orig_id' => NULL,
      'dest_id' => NULL,
      'info' => NULL,
      'status_id' => TransactionLog::STATUS_LOCAL,
      'data' => array(),
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

  /**
   * Adds a transaction to the database.
   */
  public function save() {
    // data field is handled in other method.
    $data = $this->data;
    unset($this->data);
    if (isset($this->id)) {
      $this->time_update = api_get_utc_datetime();
      Database::update(self::$table, $this, array('id = ?' => $this->id));
    }
    else {
      $this->time_update = $this->time_insert = api_get_utc_datetime();
      $this->id = Database::insert(self::$table, $this);
      error_log("id is $this->id");
    }
    $this->data = $data;
    if (!empty($this->data)) {
      $this->saveData();
    }
  }

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
   * General load method.
   */
  public static function load($db_fields) {
    foreach ($db_fields as $db_field => $db_value) {
      $conditions[] = "$db_field = ?";
      $values[] = $db_value;
    }
    $results = Database::select('*', self::$table, array('where' => array(implode(' AND ', $conditions) => $values)));
    $objects = array();
    foreach ($results as $result) {
      $objects[] = self::createInstance($transaction);
    }
    return $objects;
  }

  /**
   * Loading for data table.
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
   * Loads by id.
   */
  public static function load_by_id($id) {
    $transactions = self::load(array('id' => $id));
    if (empty($transactions)) {
      return FALSE;
    }
    return array_shift($transactions);
  }

  /**
   * Load by branch and transaction.
   */
  public static function load_by_branch_and_transaction($branch_id, $transaction_id) {
    $transactions = self::load(array('branch_id' => $branch_id, 'transaction_id' => $transaction_id));
    if (empty($transactions)) {
      return FALSE;
    }
    return array_shift($transactions);
  }

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
}

class ExerciseTestTransactionLog extends TransactionLog {
  public $action = 'exercise_test';

  public static function load_exercise_test($exercise_id, $attempt_id, $branch_id = TransactionLog::BRANCH_LOCAL) {
    $exercise_test_id = sprintf('%s:%s', $exercise_id, $attempt_id);
    var_dump($exercise_test_id);
    $transactions = self::load(array('branch_id' => $branch_id, 'item_id' => $exercise_test_id));
    if (empty($transactions)) {
      return FALSE;
    }
    return array_shift($transactions);
  }
}
