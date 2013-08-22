<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\Transaction;

use Database;
use Exception as Exception;
// See comment at getTransactionClass().
use ChamiloLMS\Transaction\ExerciseAttemptTransactionLog;

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
    /**
     * Static cache for sign flags.
     */
    protected static $signFlags;

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
                $imported_item_id = $transaction->import();
                $log_entry['message'] = 'Successfully imported.';
                $transaction->status_id = TransactionLog::STATUS_SUCCESSFUL;
                $transaction->dest_id = $imported_item_id;
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

        return '\ChamiloLMS\Transaction\\' . $map[$action];
    }

    /**
     * Exports a set of transactions to a file.
     *
     * @param string $filepath
     *   The path to the file where the exported transactions will be stored.
     * @param array $transactions
     *   The list of TransactionLog objects to be exported.
     *
     * @return array
     *   Two keys are provided:
     *   - 'success': A set of transaction ids correctly added.
     *   - 'fail': A set of transaction ids that failed to be added.
     */
    public function exportToFile($filepath, $transactions)
    {
        $transactions_to_persist = array();
        $exported_ids = array(
            'success' => array(),
            'fail' => array(),
        );

        if (!is_writable($filepath)) {
            error_log(sprintf('%s::%s(): Unable to write the requested file "%s".', __CLASS__, __METHOD__, $filepath));
            foreach ($transactions as $transaction) {
                $exported_ids['fail'][] = $transaction->id;
            }
            return $exported_ids;
        }

        foreach ($transactions as $transaction) {
            try {
                $transaction->export();
                $transactions_to_persist[] = $transaction;
                $exported_ids['success'][] = $transaction->id;
            } catch (Exception $export_exception) {
                error_log($export_exception->getMessage());
                $exported_ids['fail'][] = $transaction->id;
            }
        }

        file_put_contents($filepath, json_encode($transactions_to_persist));

        return $exported_ids;
    }

    /**
     * Retrieves the sign flags for PKCS7 functions.
     *
     * Cannot initialize with a logical AND, so use this as workaround.
     */
    protected static function getSignFlags() {
        if (isset(self::$signFlags)) {
            self::$signFlags = PKCS7_BINARY & PKCS7_DETACHED;
        }
        return self::$signFlags;
    }

    /**
     * Signs and writes a transaction file with a PKCS#12 certificate.
     *
     * It includes the data inside the transaction file.
     *
     * @param string $transactions_file
     *   The path to the file containing the exported transactions.
     * @param string $p12_certificate_store_file
     *   The path to the file containing the PKCS#12 certificate store.
     * @param string $p12_passphrase
     *   Passphrase to open the PKCS#12 certificate store.
     * @param string $signed_transactions_file
     *   The path to the file that will contain the exported transactions signed.
     *
     * @throws TransactionFileSigningException
     *   When there was a problem during the signing process.
     */
    private function signTransactionFile($transactions_file, $p12_certificate_store_file, $p12_passphrase, $signed_transactions_file)
    {
        // Filesystem verifications.
        if (!is_readable($transactions_file)) {
            throw new TransactionFileSigningException(sprintf('Unable to read transaction file "%s".', $transactions_file));
        }
        if (!is_readable($p12_certificate_store_file)) {
            throw new TransactionFileSigningException(sprintf('Unable to read certificate store file "%s".', $p12_certificate_store_file));
        }
        if (!is_writable($signed_transactions_file)) {
            throw new TransactionFileSigningException(sprintf('Unable to write file "%s".', $signed_transactions_file));
        }

        // Read and parse the PKCS#12 file.
        $p12_handle = fopen($p12_certificate_store_file, 'r');
        $p12_buffer = fread($p12_handle, filesize($p12_certificate_store_file));
        fclose($p12_handle);
        $p12_store = array();
        if (!openssl_pkcs12_read($p12_buffer, $p12_store, $p12_passphrase)) {
            throw new TransactionFileSigningException(sprintf('Unable to decrypt PKCS#12 certificate store "%s".', $p12_certificate_store_file));
        }
        if (!$certificate_handle = openssl_x509_read($p12_store['cert'])) {
            throw new TransactionFileSigningException(sprintf('Unable read the certificate inside the PKCS#12 certificate store "%s".', $p12_certificate_store_file));
        }
        if (!$private_key = openssl_get_privatekey($p12_store['pkey'])) {
            throw new TransactionFileSigningException(sprintf('Unable read the private key inside the PKCS#12 certificate store "%s".', $p12_certificate_store_file));
        }

        // Do signing.
        $headers = array();
        $data_to_sign = realpath($transactions_file);
        $sign_flags = self::getSignFlags();
        if (!openssl_pkcs7_sign($data_to_sign, $signed_transactions_file, $certificate_handle, $private_key, $headers , $sign_flags)) {
            throw new TransactionFileSigningException(sprintf('Unable sign the transaction file "%s" with data in the PKCS#12 certificate store "%s".', $transactions_file, $p12_certificate_store_file));
        }
    }

    /**
     * Verify signature and extracts contained data into a file.
     *
     * @param string $signed_transactions_file
     *   The path to the file that will contain the exported transactions
     *   signed.
     * @param string $ca_certificate_file
     *   The path to the file that contains the CA certificate trusted for the
     *   verification process.
     * @param string $signer_certificates_file
     *   The path to the file that will contain the certificates of the entities
     *   that signed the file.
     * @param string $unsigned_transactions_file
     *   The path to the file that will contain the extracted information from
     *   the signed file.
     *
     * @return int
     *   The branch id on valid identification.
     *
     * @throws TransactionFileUnsigningException
     *   When there was a problem during the unsigning process.
     */
    public function unsignTransactionFile($signed_transactions_file, $ca_certificate_file, $signer_certificates_file, $unsigned_transactions_file) {
        $signed_transactions_file = realpath($signed_transactions_file);
        $ca_certificate_file = realpath($ca_certificate_file);

        // Filesystem verifications.
        if (!is_readable($signed_transactions_file)) {
            throw new TransactionFileUnsigningException(sprintf('Unable to read signed transaction file "%s".', $signed_transactions_file));
        }
        if (!is_readable($ca_certificate_file)) {
            throw new TransactionFileUnsigningException(sprintf('Unable to read CA certificate file "%s".', $ca_certificate_file));
        }
        if (!is_writable($signer_certificates_file)) {
            throw new TransactionFileUnsigningException(sprintf('Unable to write signer certificates to file "%s".', $signer_certificates_file));
        }
        if (!is_writable($unsigned_transactions_file)) {
            throw new TransactionFileUnsigningException(sprintf('Unable to write original information to file "%s".', $unsigned_transactions_file));
        }

        // Unfortunately we cannot NULL out unwanted parameters. That's why we
        // specify $ca_certificate_file as the extracerts parameter. Although
        // it's not clean, this doesn't compromise security: The CA certificate
        // can never be a valid intermediate certificate as it is self-signed
        // already.
        $sign_flags = self::getSignFlags();
        $sign_verification = openssl_pkcs7_verify($signed_transactions_file, $sign_flags, $signer_certificates_file, array($ca_certificate_file), $ca_certificate_file, $unsigned_transactions_file);
        if ($sign_verification === TRUE) {
            return $this->identifySignerBranch($signer_certificates_file);
        } elseif ($sign_verification === FALSE) {
            throw new TransactionFileUnsigningException(sprintf('Signed file "%s" failed verification.', $signed_transactions_file));
        } else { // -1
            throw new TransactionFileUnsigningException(sprintf('There was an error during the signature verification for signed file "%s".', $signed_transactions_file));
        }
    }

    /**
     * Identify the branch associated with the certificate passed.
     *
     * @param string $signer_certificates_file
     *   The path to the file that contains the certificates of the entities
     *   that signed the file.
     *
     * @return int
     *   The branch ID corresponding to the signer or false if not found.
     *
     * @throws TransactionFileUnsigningException
     *   When there was a problem during the unsigning process.
     */
    public function identifySignerBranch($signer_certificates_file) {
        // Retrieve all branch certificate file paths.
        $branch_sync_table = Database::get_main_table(TABLE_BRANCH_SYNC);
        $results = Database::select(array('id', 'ssl_certificate'), $branch_sync_table);
        foreach ($results as $row) {
            $certificate_file = $row['ssl_certificate'];
            if (verifySignerBranch($signer_certificates_file, $certificate_file)) {
                return $row['id'];
            }
        }
        return false;
    }

    /**
     * Verify the a signer certificate with an expected branch certificate.
     *
     * @param string $signer_certificates_file
     *   The path to the file that contains the certificates of the entities
     *   that signed the file.
     * @param int $expected_certificate_file
     *   The expected branch PEM certificate file path to compare.
     *
     * @return boolean
     *   true on success, false on failure.
     *
     * @throws TransactionFileUnsigningException
     *   When there was a problem during the identification process.
     */
    public function verifySignerBranch($signer_certificates_file, $expected_certificate_file) {
        // Filesystem verifications.
        if (!is_readable($signer_certificates_file)) {
            throw new TransactionFileUnsigningException(sprintf('Unable to read signer certificate file "%s".', $signer_certificates_file));
        }
        if (!is_readable($expected_certificate_file)) {
            throw new TransactionFileUnsigningException(sprintf('Unable to read expected PKCS#12 certificate store "%s".', $expected_certificate_file));
        }

        // Read and parse the signer X.509 file.
        $x509_handle = fopen($signer_certificates_file, 'r');
        $x509_buffer = fread($x509_handle, filesize($signer_certificates_file));
        fclose($x509_handle);
        if (!$signer_x509_certificate = openssl_x509_read($x509_buffer)) {
            throw new TransactionFileUnsigningException(sprintf('Unable read correctly the contents of the signer certificate file "%s".', $signer_certificates_file));
        }
        // Generate X.509 format for the signer certificate.
        $signer_x509_string = null;
        if (!openssl_x509_export($signer_x509_certificate, $signer_x509_string)) {
            throw new TransactionFileUnsigningException(sprintf('Unable to export X.509 format for signer certificate file "%s".', $signer_certificates_file));
        }

        // Read and re-export to X.509 format.
        $expected_x509_handle = fopen($expected_certificate_file, 'r');
        $expected_x509_buffer = fread($expected_x509_handle, filesize($expected_certificate_file));
        fclose($expected_x509_handle);
        if (!$expected_x509_certificate = openssl_x509_read($expected_x509_buffer)) {
            throw new TransactionFileUnsigningException(sprintf('Unable read the expected certificate in PEM format at "%s".', $expected_certificate_file));
        }
        // Generate X.509 format for expected certificate.
        $expected_x509_string = null;
        if (!openssl_x509_export($expected_x509_handle, $expected_x509_string)) {
            throw new TransactionFileUnsigningException(sprintf('Unable to export X.509 format for expected certificate "%s" referred branch_sync entry.', $expected_certificate_file));
        }

        // Finally, compare exported strings.
        return $signer_x509_string == $expected_x509_string;
    }
}
