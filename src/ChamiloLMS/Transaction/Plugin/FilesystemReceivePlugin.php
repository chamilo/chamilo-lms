<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\Transaction\Plugin;

use ChamiloLMS\Transaction\Envelope;
use ChamiloLMS\Transaction\TransactionLogController;

/**
 * Receive from a directory, then move the file to other one.
 */
class FilesystemReceivePlugin implements ReceivePluginInterface
{
    /**
     * Path to the directory where to retrieve the files.
     */
    public $origin;

    /**
     * Path to the directory where to move the processed files.
     */
    public $processed;

    /**
     * {@inheritdoc}
     */
    public function getMachineName()
    {
        return 'filesystem';
    }

    /**
     * Constructor.
     *
     * @param array $data
     *   An array containing the values of the two required data members using
     *   the data member name as key.
     *
     * @throws ReceiveException
     *   When there is an error while instanciating the plugin.
     */
    public function __construct($data) {
        if (empty($data['origin']) || empty($data['processed'])) {
            throw new ReceiveException(sprintf('filesystem: Cannot instanciate the plugin with data array: "%s".', print_r($data, 1)));
        }
        $this->origin = $data['origin'];
        $this->processed = $data['processed'];
    }

    /**
     * {@inheritdoc}
     */
    public function receive($limit = 0)
    {
        $this->prepare();
        if (!$envelope_files = scandir($this->origin)) {
            throw new ReceiveException(sprintf('filesystem: Cannot read receive directory "%s".', $this->origin));
        }
        $blobs = array();
        $count = 0;
        // If one fail, abort processing.
        foreach ($envelope_files as $envelope_file) {
            if ($envelope_file[0] == '.') {
                // Ignore 'hidden' files.
                continue;
            }
            if ($limit != 0 && $count >= $limit) {
                // Reached limit.
                break;
            }
            $filepath = $this->origin . '/' . $envelope_file;
            if (is_dir($filepath)) {
                // Ignore directories.
                continue;
            }
            if (!$blob = file_get_contents($filepath)) {
                throw new ReceiveException(sprintf('filesystem: Cannot read envelope file "%s".', $filepath));
            }
            $blobs[] = $blob;
            $new_filepath_base = $this->processed . '/' . $envelope_file;
            $new_filepath = $new_filepath_base;
            $extra_suffix = 0;
            while (file_exists($new_filepath)) {
                ++$extra_suffix;
                $new_filepath = $new_filepath_base . '.' .  $extra_suffix;
            }
            if (!rename($filepath, $new_filepath)) {
                throw new ReceiveException(sprintf('filesystem: Cannot move the processed envelope file "%s" to "%s".', $filepath, $new_filepath));
            }
            else {
                $log_entry = array('log_type' => TransactionLogController::LOG_RECEIVE);
                $log_entry['message'] = sprintf('filesystem: Moved envelope file "%s" to "%s".', $filepath, $new_filepath);
                TransactionLogController::addImportLog($log_entry);
            }
            ++$count;
        }
        return $blobs;
    }

    /**
     * Base verifications before actually doing any process.
     *
     * @throws ReceiveException
     *   When there is an error on the receiving process.
     */
    protected function prepare()
    {
        if (!is_dir($this->origin)) {
            throw new ReceiveException(sprintf('filesystem: Cannot find receive directory "%s".', $this->origin));
        }
        if (!is_readable($this->origin)) {
            throw new ReceiveException(sprintf('filesystem: Cannot read receive directory "%s".', $this->origin));
        }
        if (!is_writable($this->origin)) {
            throw new ReceiveException(sprintf('filesystem: Cannot write receive directory "%s".', $this->origin));
        }
        if (!is_dir($this->processed)) {
            if (!mkdir($this->processed, api_get_permissions_for_new_directories())) {
                throw new ReceiveException(sprintf('filesystem: Cannot find processed directory "%s", nor create it.', $this->processed));
            }
        }
        if (!is_writable($this->processed)) {
            throw new ReceiveException(sprintf('filesystem: Cannot write processed directory "%s".', $this->processed));
        }
    }
}
