<?php
/* For licensing terms, see /license.txt */

/**
 * General code for transactions.
 *
 * @package chamilo.library
 *
 * @see Migration
 */

class TransactionLog {
  protected $table;
  protected $branch_id;

  public function __construct($data) {
    $this->table = Database::get_main_table(TABLE_BRANCH_TRANSACTION);
    $this->branch_id = Database::get_main_table(TABLE_BRANCH_TRANSACTION);
    // time_insert and time_update are handled manually.
    $fields = array('id', 'transaction_id', 'branch_id', 'action', 'item_id', 'orig_id', 'dest_id', 'info', 'status_id');
    foreach ($fields as $field) {
      if (isset($data[$field])) {
        $this->$field = $data[$field];
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
   * Load by id.
   */
  public static function load_by_id($id) {
    $transaction = Database::select('*', $this->table, array('where' => array('id = ?' => $id)));
    return new TransactionLog($transaction);
  }
}
