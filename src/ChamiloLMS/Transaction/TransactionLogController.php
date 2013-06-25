<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\Transaction;

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
   * A local place to store the branch transaction data table name.
   */
  protected $log_table;

  public function __construct() {
    $this->table = Database::get_main_table(TABLE_BRANCH_TRANSACTION);
    $this->data_table = Database::get_main_table(TABLE_BRANCH_TRANSACTION_DATA);
    $this->log_table = Database::get_main_table(TABLE_BRANCH_TRANSACTION_LOG);
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
      // Set the right id in the new system.
      $transaction_data['transaction_id'] = $transaction_data['id'];
      unset($transaction_data['id']);
      $transaction_data['status_id'] = TransactionLog::STATUS_TO_BE_EXECUTED;
      $transaction = new $class_name($transaction_data);
      $transaction->save();
      $added_transactions[] = $transaction->id;
    }
    return $added_transactions;
  }

  /**
   * Imports the passed transactions to the current system.
   *
   * @param array $transactions
   *   A set of TransactionLog objects to import.
   *
   * @return array
   *   Two keys are provided:
   *   - 'success': A set of transaction ids correctly added.
   *   - 'fail': A set of transaction ids that failed to be added.
   */
  public function importToSystem($transactions) {
    $imported_ids = array(
      'success' => array(),
      'fail' => array(),
    );
    $transaction_actions_map = TransactionLog::getTransactionMappingSettings();
    $max_possible_attempts = 3;
    foreach ($transactions as $transaction) {
      $log_entry = array('transaction_id' => $transaction->id);
      if ($transaction->status_id == TransactionLog::STATUS_ABANDONNED) {
        $log_entry['message'] = 'Skipped import of abandoned transaction.';
        self::addImportLog($log_entry);
        $imported_ids['fail'][] = $transaction->id;
        continue;
      }
      if (isset($transaction_actions_map[$transaction->action]['max_attempts'])) {
        $max_possible_attempts = $transaction_actions_map[$transaction->action]['max_attempts'];
      }
      // @todo Move to group query outside of the loop if performance is not enough.
      $row = Database::select('count(id) as import_attempts', $this->log_table, array('where' => array('transaction_id = ?' => array($transaction->id))));
      $row = array_shift($row);
      if ($row['import_attempts'] >= $max_possible_attempts) {
        $log_entry['message'] = sprintf('Reached maximum number of import attempts: "%d" attempts of "%d".', $row['import_attempts'], $max_possible_attempts);
        self::addImportLog($log_entry);
        $transaction->status_id = TransactionLog::STATUS_ABANDONNED;
        $transaction->save();
        $imported_ids['fail'][] = $transaction->id;
        continue;
      }
      try {
        $transaction->import();
        $log_entry['message'] = 'Successfully imported.';
        $transaction->status_id = TransactionLog::STATUS_SUCCESSFUL;
        $imported_ids['success'][] = $transaction->id;
      }
      catch (Exception $import_exception) {
        $log_entry['message'] = $import_exception->getMessage();
        $transaction->status_id = TransactionLog::STATUS_FAILED;
        $imported_ids['fail'][] = $transaction->id;
      }
      self::addImportLog($log_entry);
      $transaction->save();
    }
    return $imported_ids;
  }

  /**
   * Imports pending transactions to the system.
   *
   * @param int $limit
   *   The maximum number of transactions to import into the system.
   *
   * @return array
   *   See TransactionLogController::importToSystem().
   */
  public function importPendingToSystem($limit = 10) {
    // Sadly multiple values are not supported by Database::select(), aka IN
    // operation.
    $transaction_actions_map = TransactionLog::getTransactionMappingSettings();
    $sql = sprintf('SELECT * FROM %s WHERE branch_id != %d AND status_id IN (%d, %d) LIMIT %d', $this->table, TransactionLog::BRANCH_LOCAL, TransactionLog::STATUS_TO_BE_EXECUTED, TransactionLog::STATUS_FAILED, $limit);
    $result = Database::query($sql);
    $transactions = array();
    while ($row = $result->fetch()) {
      $class_name = $transaction_actions_map[$row['action']]['class'];
      $transactions[$row['id']] = new $class_name($row);
      $transactions[$row['id']]->loadData();
    }
    return $this->importToSystem($transactions);
  }

  /**
   * Adds an entry on transaction import log table.
   *
   * @param array $log_entry
   *   An array with the following keys:
   *   - 'transaction_id': The related transaction id.
   *   - 'import_time': (optional) The time of the import or current time if
   *     not provided.
   *   - 'message': (optional) The related message. Usually from exception
   *     messages or manual strings on success.
   *
   * @return int
   *   The id of the inserted log, as provided by Database::insert().
   */
  public function addImportLog($log_entry) {
    $log_entry += array(
      'import_time' => api_get_utc_datetime(),
      'message' => '',
    );
    return Database::insert($this->log_table, $log_entry);
  }
}
