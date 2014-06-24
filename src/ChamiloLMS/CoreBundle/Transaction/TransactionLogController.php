<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\CoreBundle\Transaction;

use Database;
use ChamiloLMS\CoreBundle\Transaction\ExerciseAttemptTransactionLog;

/**
 * Controller class for transactions.
 */
class TransactionLogController
{
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

    public function __construct()
    {
        $this->table = Database::get_main_table(TABLE_BRANCH_TRANSACTION);
        $this->data_table = Database::get_main_table(TABLE_BRANCH_TRANSACTION_DATA);
        $this->log_table = Database::get_main_table(TABLE_BRANCH_TRANSACTION_LOG);
    }

    /**
     * General load method.
     *
     * @param array $db_fields
     *   An array containing equal conditions to combine wih AND to add to
     *   where. i.e array('branch_id' => 1) means WHERE 'branch_id' = 1.
     *
     * @return array
     *   A list of TransactionLog object that match passed conditions.
     */
    public function load($db_fields)
    {
        foreach ($db_fields as $db_field => $db_value) {
            $conditions[] = "$db_field = ?";
            $values[] = $db_value;
        }
        $results = Database::select('*', $this->table, array('where' => array(implode(' AND ', $conditions) => $values)));
        $objects = array();
        foreach ($results as $result) {
            $objects[] = self::createTransaction($result['action'], $result);
        }

        return $objects;
    }

    /**
     * Loads one transaction based on parameters.
     *
     * @param array $db_fields
     *   See self::load().
     *
     * @return boolean|TransactionLog
     *   false if not found, or the corresponding object.
     */
    public function loadOne($db_fields)
    {
        $transactions = $this->load($db_fields);
        if (empty($transactions)) {
            return false;
        }

        return array_shift($transactions);
    }

    /**
     * Loads by id.
     *
     * @param int
     *   branch_transaction.id
     *
     * @return boolean|TransactionLog
     *   false if not found, or the corresponding object.
     */
    public function loadById($id)
    {
        return $this->loadOne(array('id' => $id));
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
    public function importToLog($exported_transactions)
    {
        $added_transactions = array();
        foreach ($exported_transactions as $exported_transaction) {
            $transaction_data = json_decode($exported_transaction, true);
            // Set the right id in the new system.
            $transaction_data['transaction_id'] = $transaction_data['id'];
            unset($transaction_data['id']);
            $transaction_data['status_id'] = TransactionLog::STATUS_TO_BE_EXECUTED;
            $transaction = self::createTransaction($transaction_data['action'], $transaction_data);
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
    public function importToSystem($transactions)
    {
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
            } catch (Exception $import_exception) {
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
    public function importPendingToSystem($limit = 10)
    {
        // Sadly multiple values are not supported by Database::select(), aka IN
        // operation.
        $sql = sprintf('SELECT * FROM %s WHERE branch_id != %d AND status_id IN (%d, %d) LIMIT %d', $this->table, TransactionLog::BRANCH_LOCAL, TransactionLog::STATUS_TO_BE_EXECUTED, TransactionLog::STATUS_FAILED, $limit);
        $result = Database::query($sql);
        $transactions = array();
        while ($row = $result->fetch()) {
            $transactions[$row['id']] = self::createTransaction($row['action'], $row);
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
    public function addImportLog($log_entry)
    {
        $log_entry += array(
            'import_time' => api_get_utc_datetime(),
            'message' => '',
        );

        return Database::insert($this->log_table, $log_entry);
    }

    /**
     * Creates a new transaction object based on passed information.
     *
     * @param string $action
     *   The action keyword mapped to the transaction class.
     * @param array $data
     *   See the action type constructor.
     *
     * @return TransactionLog
     *   A transaction object.
     */
    public static function createTransaction($action, $data)
    {
        $class_name = self::getTransactionClass($action);

        return new $class_name($data);
    }

    /**
     * Returns the class name related with the action passed.
     *
     * @param string $action
     *   The action keyword mapped to the transaction class.
     *
     * @return string
     *   The related transaction class name.
     */
    public static function getTransactionClass($action)
    {
        // Do the mapping manually. It seems like it cannot be done dynamically
        // because on PSR-0 we need to add a 'use' clause per used class, which
        // php process on compiling, so it cannot be discovered and loaded in
        // runtime. Instead all possible transaction classes are added at the
        // start of this file.
        $map = array(
            'exercise_attempt' => 'ExerciseAttemptTransactionLog',
        );

        return '\ChamiloLMS\CoreBundle\Transaction\\' . $map[$action];
    }
}
