<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\Transaction\Plugin;

use \Exception as Exception;
use ChamiloLMS\Transaction\TransactionLogController;

/**
 * Uses SSL signed JSON to wrap.
 */
class SslSignedJsonWrapper extends JsonWrapper
{
    /**
     * The path to the file that contains the certificates of the entity which
     * signs the transactions.
     *
     * @var string
     */
    public $certificate;

    /**
     * The path to the file containing the PKCS#12 certificate store.
     *
     * @var string
     */
    public $p12store;

    /**
     * Path to a file containing the passphrase to open the PKCS#12
     * certificate store.
     *
     * @var string
     */
    protected $p12passphrase;

    /**
     * The path to the file that contains the CA certificate trusted for the
     * verification process during unwrapping.
     *
     * @var string
     */
    public $cacertificate;

    /**
     * Static cache for sign flags.
     */
    protected static $signFlags;

    /**
     * {@inheritdoc}
     */
    public static function getMachineName()
    {
        return 'ssl_signed_json';
    }

    /**
     * Constructor.
     *
     * @param array $data
     *   An array containing the values of data members using the data member
     *   name as key.
     *
     * @throws WrapException
     *   When there is an error while instanciating the plugin.
     */
    public function __construct($data) {
        if (empty($data['certificate']) || empty($data['p12store'])) {
            throw new WrapException(self::format_log(sprintf('Cannot instanciate the plugin with data array: "%s".', print_r($data, 1))));
        }
        $this->certificate = $data['certificate'];
        $this->p12store = $data['p12store'];
        if (isset($data['p12passphrase'])) {
            $this->p12passphrase = $data['p12passphrase'];
        }
        if (isset($data['cacertificate'])) {
            $this->cacertificate = $data['cacertificate'];
        }
    }

    /**
     * Signs transactions with the PKCS#12 certificate store information.
     *
     * It includes the data inside the transaction file.
     *
     * {@inheritdoc}
     */
    public function wrap($transactions)
    {
        $json_blob = parent::wrap($transactions);
        $this->prepare('sign');

        // Read and parse the PKCS#12 file and the passphrase.
        $p12_handle = fopen($this->p12store, 'r');
        $p12_buffer = fread($p12_handle, filesize($this->p12store));
        fclose($p12_handle);
        $p12_store = array();
        if (!$p12_passphrase = file($this->p12passphrase, FILE_IGNORE_NEW_LINES)) {
            throw new WrapException(self::format_log(sprintf('Cannot parse PKCS#12 certificate store passphrase file "%s".', $this->p12passphrase)));
        }
        if (!isset($p12_passphrase[0])) {
            throw new WrapException(self::format_log(sprintf('Empty PKCS#12 certificate store passphrase file "%s".', $this->p12passphrase)));
        }
        $p12_passphrase = $p12_passphrase[0];
        if (!openssl_pkcs12_read($p12_buffer, $p12_store, $p12_passphrase)) {
            throw new WrapException(self::format_log(sprintf('Unable to decrypt PKCS#12 certificate store "%s".', $this->p12store)));
        }
        if (!$certificate_handle = openssl_x509_read($p12_store['cert'])) {
            throw new WrapException(self::format_log(sprintf('Unable read the certificate inside the PKCS#12 certificate store "%s".', $this->p12store)));
        }
        if (!$private_key = openssl_get_privatekey($p12_store['pkey'])) {
            throw new WrapException(self::format_log(sprintf('Unable read the private key inside the PKCS#12 certificate store "%s".', $this->p12store)));
        }

        // Do signing.
        $headers = array();
        // openssl_pkcs7_sign() needs a file for input data and a file to write
        // the signed data, so create temporary files for it.
        if (!$transactions_file = $this->getTemporaryFile('transactions_file')) {
            throw new WrapException(self::format_log(sprintf('Unable to create correctly the temporary transactions file on "%s".', $transactions_file)));
        }
        if (!$signed_transactions_file = $this->getTemporaryFile('signed_transactions_file')) {
            throw new WrapException(self::format_log(sprintf('Unable to create correctly the temporary signed transactions file on "%s".', $signed_transactions_file)));
        }
        if (file_put_contents($transactions_file, $json_blob) === FALSE) {
            throw new WrapException(self::format_log(sprintf('Unable to write correctly the temporary transactions file on "%s".', $transactions_file)));
        }
        // @todo realpath needed?
        $data_to_sign = realpath($transactions_file);
        $sign_flags = self::getSignFlags();
        if (!openssl_pkcs7_sign($data_to_sign, $signed_transactions_file, $certificate_handle, $private_key, $headers , $sign_flags)) {
            unlink($transactions_file);
            unlink($signed_transactions_file);
            throw new WrapException(self::format_log(sprintf('Unable sign transactions correctly using PKCS#12 certificate store "%s".', $this->p12store)));
        }
        unlink($transactions_file);
        if (!$signed_data = file_get_contents($signed_transactions_file)) {
            throw new WrapException(self::format_log(sprintf('Unable retrieve the signed data from the temporary file "%s".', $signed_transactions_file)));
        }
        unlink($signed_transactions_file);
        return $signed_data;
    }

    /**
     * {@inheritdoc}
     */
    public function unwrap($envelope_blob)
    {
        $this->prepare('unsign');

        // openssl_pkcs7_verify() needs some data as files, so create temporary
        // files for it.
        if (!$signed_transactions_file = $this->getTemporaryFile('signed_transactions_file')) {
            throw new UnwrapException(self::format_log(sprintf('Unable to create correctly the temporary signed transactions file on "%s".', $signed_transactions_file)));
        }
        if (file_put_contents($signed_transactions_file, $envelope_blob) === FALSE) {
            throw new UnwrapException(self::format_log(sprintf('Unable to write correctly the temporary signed transactions file on "%s".', $signed_transactions_file)));
        }
        if (!$signer_certificates_file = $this->getTemporaryFile('signer_certificates_file')) {
            throw new UnwrapException(self::format_log(sprintf('Unable to create correctly the temporary signer certificates file on "%s".', $signer_certificates_file)));
        }
        if (!$unsigned_transactions_file = $this->getTemporaryFile('unsigned_transactions_file')) {
            throw new UnwrapException(self::format_log(sprintf('Unable to create correctly the temporary unsigned transactions file on "%s".', $unsigned_transactions_file)));
        }

        // @todo realpath needed?
        $signed_transactions_file = realpath($signed_transactions_file);
        $ca_certificate_file = realpath($this->cacertificate);

        // Unfortunately we cannot NULL out unwanted parameters. That's why we
        // specify $ca_certificate_file as the extracerts parameter. Although
        // it's not clean, this doesn't compromise security: The CA certificate
        // can never be a valid intermediate certificate as it is self-signed
        // already.
        $sign_flags = self::getSignFlags();
        $sign_verification = openssl_pkcs7_verify($signed_transactions_file, $sign_flags, $signer_certificates_file, array($ca_certificate_file), $ca_certificate_file, $unsigned_transactions_file);
        if ($sign_verification === TRUE) {
            $branch_id = $this->identifySignerBranch($signer_certificates_file);
            // @fixme Figure out a way to pass this value somewhere: received blobs table?
            if (!$json_envelope_blob = file_get_contents($unsigned_transactions_file)) {
                throw new UnwrapException(self::format_log(sprintf('Cannot get contents of temporary unsigned transactions file "%s".', $unsigned_transactions_file)));
            }
        } elseif ($sign_verification === FALSE) {
            unlink($signed_transactions_file);
            unlink($signer_certificates_file);
            unlink($unsigned_transactions_file);
            throw new UnwrapException(self::format_log(sprintf('Signed file "%s" failed verification.', $signed_transactions_file)));
        } else { // -1
            unlink($signed_transactions_file);
            unlink($signer_certificates_file);
            unlink($unsigned_transactions_file);
            throw new UnwrapException(self::format_log(sprintf('There was an error during the signature verification for signed file "%s".', $signed_transactions_file)));
        }
        unlink($signed_transactions_file);
        unlink($signer_certificates_file);
        unlink($unsigned_transactions_file);

        return parent::unwrap($json_envelope_blob);
    }

    /**
     * Base verifications before actually doing any process.
     *
     * @param string $action
     *   What to prepare for. Either 'sign', 'unsign' or 'all'.
     *
     * @throws WrapException
     *   When there is an error on the receiving process.
     */
    protected function prepare($action = 'all')
    {
        if (!is_readable($this->certificate)) {
            throw new WrapException(self::format_log(sprintf('Cannot read certificate file "%s".', $this->certificate)));
        }
        if (!is_readable($this->p12store)) {
            throw new WrapException(self::format_log(sprintf('Cannot read PKCS#12 certificate store file "%s".', $this->p12store)));
        }
        if ($action == 'sign' || $action == 'all') {
            if (empty($this->p12passphrase)) {
                throw new WrapException(self::format_log(sprintf('Cannot try to sign without a PKCS#12 certificate store passphrase for file "%s".', $this->p12store)));
            }
            if (!is_readable($this->p12passphrase)) {
                throw new WrapException(self::format_log(sprintf('Cannot read PKCS#12 certificate store passphrase file "%s".', $this->p12passphrase)));
            }
        }
        if ($action == 'unsign' || $action == 'all') {
            if (!is_readable($this->cacertificate)) {
                throw new WrapException(self::format_log(sprintf('Cannot read CA certificate file "%s".', $this->cacertificate)));
            }
        }
    }

    /**
     * Retrieves the sign flags for PKCS7 functions.
     *
     * Cannot initialize with a logical AND, so use this as workaround.
     */
    protected static function getSignFlags() {
        if (!isset(self::$signFlags)) {
            self::$signFlags = PKCS7_BINARY & PKCS7_DETACHED;
        }
        return self::$signFlags;
    }

    /**
     * Identify the branch associated with the certificate passed.
     *
     * @param string $signer_certificates_file
     *   The path to the file that contains the certificates of the entities
     *   that signed the file.
     *
     * @return mixed
     *   The branch ID corresponding to the signer or false if not found.
     *
     * @throws UnwrapException
     *   When there was a problem during the unsigning process.
     */
    public function identifySignerBranch($signer_certificates_file) {
        global $app;

        // Retrieve all branches to look certificate file paths.
        $branches = $app['orm.em']->getRepository('Entity\BranchSync')->findAll();
        foreach ($branches as $branch) {
            $wrapper_plugin_data = $branch->getPluginData('wrapper');
            if (!isset($wrapper_plugin_data['certificate'])) {
                $this->log(sprintf('Warning: Failed to get a certificate filepath on branch with id "%d" during identification', $branch->getId()));
                continue;
            }
            $certificate_file = $wrapper_plugin_data['certificate'];
            try {
                if ($this->verifySignerBranch($signer_certificates_file, $certificate_file)) {
                    return $branch->getId();
                }
            }
            catch (Exception $exception) {
                $this->log(sprintf('Warning: Failed verifying signer branch with id "%d" during identification: %s', $branch->getId(), $exception->getMessage()));
            }
        }
        return false;
    }

    /**
     * Verify a signer certificate with an expected branch certificate.
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
     * @throws Exception
     *   When there was a problem during the identification process.
     */
    public function verifySignerBranch($signer_certificates_file, $expected_certificate_file) {
        // Filesystem verifications.
        if (!is_readable($signer_certificates_file)) {
            throw new Exception(sprintf('verify: Unable to read signer certificate file "%s".', $signer_certificates_file));
        }
        if (!is_readable($expected_certificate_file)) {
            throw new Exception(sprintf('verify: Unable to read expected X.509 certificate file "%s".', $expected_certificate_file));
        }

        // Read and parse the signer X.509 file.
        if (!$signer_x509_certificate = $this->getCertificate($signer_certificates_file)) {
            throw new Exception(sprintf('verify: Unable read correctly the contents of the signer certificate file "%s".', $signer_certificates_file));
        }
        // Generate X.509 format for the signer certificate.
        $signer_x509_string = null;
        if (!openssl_x509_export($signer_x509_certificate, $signer_x509_string)) {
            throw new Exception(sprintf('verify: Unable to export X.509 format for signer certificate file "%s".', $signer_certificates_file));
        }

        // Read and re-export to X.509 format.
        if (!$expected_x509_certificate = $this->getCertificate($expected_certificate_file)) {
            throw new Exception(sprintf('verify: Unable read the expected certificate in PEM format at "%s".', $expected_certificate_file));
        }
        // Generate X.509 format for expected certificate.
        $expected_x509_string = null;
        if (!openssl_x509_export($expected_x509_certificate, $expected_x509_string)) {
            throw new Exception(sprintf('verify: Unable to export X.509 format for expected certificate "%s" referred branch_sync entry.', $expected_certificate_file));
        }

        // Finally, compare exported strings.
        return $signer_x509_string == $expected_x509_string;
    }

    /**
     * Helper to format a log a message.
     *
     * @param string $message
     *   A message to format.
     *
     * @return string
     *   The formated log message.
     */
    protected static function format_log($message) {
        return sprintf('%s: %s', self::getMachineName(), $message);
    }

    /**
     * Helper to log a transaction message.
     *
     * @param string $message
     *   A message to format.
     */
    protected function log($message) {
        $log_entry = array(
            'log_type' => TransactionLogController::LOG_ENVELOPE,
            'message' => self::format_log($message),
        );
        TransactionLogController::addImportLog($log_entry);
    }

    /**
     * Read and parse a X.509 file.
     *
     * @param string $cerficate_file
     *   Path to the file with the certificate in X.509 exported format.
     *
     * @return boolean|resource
     *   As returned by openssl_x509_read() of false on failure.
     */
    public function getCertificate($cerficate_file) {
        $x509_handle = fopen($cerficate_file, 'r');
        $x509_buffer = fread($x509_handle, filesize($cerficate_file));
        fclose($x509_handle);
        if (!$x509_certificate = openssl_x509_read($x509_buffer)) {
            return false;
        }
        return $x509_certificate;
    }

    /**
     * Retrieve a valid temporary file path.
     *
     * @param $name
     *   The base name to use.
     *
     * @return
     *   The filesystem path to the file or false on error.
     *
     * @todo In theory $app['chamilo.filesystem']->createFolders could help,
     * but it anyway needs an absolute path, so not using it for now.
     */
    protected function getTemporaryFile($name) {
      static $tmp_directory;
      if (!isset($tmp_directory)) {
          $tmp_directory = api_get_path(SYS_DATA_PATH) . 'transaction_tmp_files';
          if (!is_dir($tmp_directory)) {
              if (!mkdir($tmp_directory, api_get_permissions_for_new_directories())) {
                  return false;
              }
          }
      }
      return tempnam($tmp_directory, $name);
    }
}
