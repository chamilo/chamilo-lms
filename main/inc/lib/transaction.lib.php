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
  const BRANCH_LOCAL = 0;
  const TRANSACTION_LOCAL = 0;
  protected static $table;

  public function __construct($data) {
    $this->table = Database::get_main_table(TABLE_BRANCH_TRANSACTION);
    // time_insert and time_update are handled manually.
    $fields = array('id', 'action', 'branch_id', 'transaction_id', 'item_id', 'orig_id', 'dest_id', 'info', 'status_id');
    foreach ($fields as $field) {
      if (isset($data[$field])) {
        $this->$field = $data[$field];
      }
      elseif ($field == 'branch_id') {
        $this->branch_id = TransactionLog::BRANCH_LOCAL;
      }
      elseif ($field == 'transaction_id') {
        $this->transaction_id = TransactionLog::TRANSACTION_LOCAL;
      }
    }
  }

  /**
   * Adds a transaction to the database.
   */
  public function save() {
    $string_keys = array('action', 'item_id', 'orig_id', 'dest_id', 'info');
    foreach ($string_keys as $string) {
      if (isset($this->$string)) {
        $this->$string = Database::escape_string($string);
      }
    }
    if (isset($this->id)) {
      $this->time_update = api_get_utc_datetime();
      return Database::update($this->table, $this, array('id = ?' => $this->id));
    }
    else {
      $this->time_update = $this->time_insert = api_get_utc_datetime();
      return Database::insert($this->table, $params);
    }
  }

  /**
   * Deletes a transaction by id.
   */
  public function delete() {
    return Database::delete($this->table, array('where' => array('id = ?' => $this->id)));
  }

  /**
   * General load method.
   */
  public static function load($db_fields) {
    foreach ($db_fields as $db_field => $db_value) {
      $conditions[] = $db_field;
      $values[] = $db_value;
    }
    $results = Database::select('*', self::$table, array('where' => array(implode(' = ? AND ', $conditions) => $values)));
    $objects = array();
    foreach ($results as $result) {
      $objects[] = self::createInstance($transaction);
    }
    return $objects;
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
    $transactions = $this->load(array('branch_id' => $branch_id, 'transaction_id' => $transaction_id));
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
      $settings[$settings['subkey']] = $setting;
    }
    return $settings;
  }

  /**
   * Creates an object of the current class.
   */
  public static abstract function createInstance($transaction);
}

class ExerciseTestTransactionLog extends TransactionLog {
  public static function createInstance($transaction) {
    $transaction['action'] = 'exercise_test';
    return new ExerciseTestTransactionLog($transaction);
  }

  public static function load_exercise_test($exercise_id, $attempt_id, $branch_id = BRANCH_LOCAL) {
    $exercise_test_id = sprintf('%s:%s', $exercise_id, $attempt_id);
    $transactions = $this->load(array('branch_id' => $branch_id, 'item_id' => $exercise_test_id));
    if (empty($transactions)) {
      return FALSE;
    }
    return array_shift($transactions);
  }
}
