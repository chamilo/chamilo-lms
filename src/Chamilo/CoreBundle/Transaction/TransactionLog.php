<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Transaction;

use Database;

/**
 * Base transaction log class.
 */
abstract class TransactionLog
{
    /**
     * Represents the local branch, as stored in branch_transaction.branch_id.
     */
    const BRANCH_LOCAL = 1;
    /**
     * Represents the local transaction, as stored in
     * branch_transaction.transaction_id.
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
    public function __construct($data)
    {
        if (empty($this->action)) {
            throw new Exception('No action set at the creation of the transaction class.');
        }
        self::$table = Database::get_main_table(TABLE_BRANCH_TRANSACTION);
        self::$data_table = Database::get_main_table(TABLE_BRANCH_TRANSACTION_DATA);
        // time_insert and time_update are handled manually.
        $fields = array(
            'id' => false,
            'branch_id' => TransactionLog::BRANCH_LOCAL,
            'transaction_id' => TransactionLog::TRANSACTION_LOCAL,
            'item_id' => false,
            'status_id' => TransactionLog::STATUS_LOCAL,
            'data' => array(),
            // @todo The following fields are legacy fields from initial
            // migration implementation and probably need to be removed from
            // the object and the table soon.
            'orig_id' => null,
            'dest_id' => null,
            'info' => null,
        );
        foreach ($fields as $field => $default_value) {
            if (isset($data[$field])) {
                $this->$field = $data[$field];
            } elseif ($default_value !== false) {
                $this->$field = $default_value;
            }
        }
    }

    public function getController()
    {
        if (empty($this->controller)) {
            $this->controller = new TransactionLogController();
        }

        return $this->controller;
    }

    /**
     * Persists a transaction to the database.
     */
    public function save()
    {
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
        } else {
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
    public function saveData()
    {
        $this->loadData();
        if (empty($this->data)) {
            // Nothing to save.
            return;
        }
        Database::delete(self::$data_table, array('id = ?' => $this->id));
        Database::insert(self::$data_table, array('id' => $this->id, 'data' => serialize($this->data)));
    }

    /**
     * Deletes a transaction by id.
     */
    public function delete()
    {
        return Database::delete(self::$table, array('id = ?' => $this->id));
    }

    /**
     * Loads information from data table into the object.
     */
    public function loadData()
    {
        if (empty($this->id)) {
            return;
        }
        $results = Database::select('data', self::$data_table, array('where' => array('id = ?' => array($this->id))));
        foreach ($results as $id => $result) {
            $results[$id]['data'] = unserialize($results[$id]['data']);
            if (!empty($results[$id]['data'])) {
                $this->data = $results[$id]['data'];
            }
        }
    }

    /**
     * Retrieves transaction settings.
     *
     * @return array
     *   Transaction log setting values identified by its action as key.
     */
    public static function getTransactionSettings($reset = false)
    {
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
     *   Every item is an array of mapping settings, keyed by transaction
     *   action, with the following keys:
     *   - max_attempts: Maximum number of attempts for trying to import the
     *     transaction into the system.
     */
    public static function getTransactionMappingSettings($action = null, $reset = false)
    {
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
     * method takes care about the missing parts not stored to avoid
     * duplication.
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
