<?php

declare(strict_types=1);

require_once __DIR__.'/../../../0_dal/dal.global_lib.php';

use Chamilo\CoreBundle\Framework\Container;

class BigUpload
{
    /**
     * Temporary directory for uploading files.
     */
    public const TEMP_DIRECTORY = '../files/tmp/';

    /**
     * Directory files will be moved to after the upload is completed.
     */
    public const MAIN_DIRECTORY = '../files/';

    /**
     * Max allowed filesize. This is for unsupported browsers and
     * as an additional security check in case someone bypasses the js filesize check.
     *
     * This must match the value specified in main.js
     */
    public const MAX_SIZE = 2147483648;

    /**
     * Temporary directory.
     *
     * @var string
     */
    private $tempDirectory;

    /**
     * Directory for completed uploads.
     *
     * @var string
     */
    private $mainDirectory;

    /**
     * Name of the temporary file. Used as a reference to make sure chunks get written to the right file.
     *
     * @var string
     */
    private $tempName;

    /**
     * Constructor function, sets the temporary directory and main directory.
     */
    public function __construct()
    {
        $this->setTempDirectory(Container::getCacheDir().'cstudio_upload/');
        $this->setMainDirectory('CStudio/editor/import-project/files/');
    }

    /**
     * Create a random file name for the file to use as it's being uploaded.
     *
     * @param string $value Temporary filename
     */
    public function setTempName($value = null): void
    {
        if ($value) {
            $this->tempName = $value;
        } else {
            $this->tempName = mt_rand().'.tmp';
        }
    }

    /**
     * Return the name of the temporary file.
     *
     * @return string Temporary filename
     */
    public function getTempName()
    {
        return $this->tempName;
    }

    /**
     * Set the name of the temporary directory.
     *
     * @param string $value Temporary directory
     */
    public function setTempDirectory($value)
    {
        $this->tempDirectory = $value;

        return true;
    }

    /**
     * Return the name of the temporary directory.
     *
     * @return string Temporary directory
     */
    public function getTempDirectory()
    {
        return $this->tempDirectory;
    }

    /**
     * Set the name of the main directory.
     *
     * @param string $value Main directory
     */
    public function setMainDirectory($value): void
    {
        $this->mainDirectory = $value;
    }

    /**
     * Return the name of the main directory.
     *
     * @return string Main directory
     */
    public function getMainDirectory()
    {
        return $this->mainDirectory;
    }

    /**
     * Function to upload the individual file chunks.
     *
     * @return string JSON object with result of upload
     */
    public function uploadFile()
    {
        // Make sure the total file we're writing to hasn't surpassed the file size limit
        if (file_exists($this->getTempDirectory().$this->getTempName())) {
            if (filesize($this->getTempDirectory().$this->getTempName()) > self::MAX_SIZE) {
                $this->abortUpload();

                return json_encode([
                    'errorStatus' => 1,
                    'errorText' => 'File is too large.',
                ]);
            }
        }

        if (!is_dir($this->tempDirectory)) {
            mkdir($this->tempDirectory, 0777, true);
        }

        // Open the raw POST data from php://input
        $fileData = file_get_contents('php://input');

        // Write the actual chunk to the larger file
        $handle = fopen($this->getTempDirectory().$this->getTempName(), 'a');

        fwrite($handle, $fileData);
        fclose($handle);

        return json_encode([
            'key' => $this->getTempName(),
            'errorStatus' => 0,
        ]);
    }

    /**
     * Function for cancelling uploads while they're in-progress; deletes the temp file.
     *
     * @return string JSON object with result of deletion
     */
    public function abortUpload()
    {
        if (file_exists($this->getTempDirectory().$this->getTempName())) {
            if (unlink($this->getTempDirectory().$this->getTempName())) {
                return json_encode(['errorStatus' => 0]);
            }
        }

        return json_encode([
            'errorStatus' => 1,
            'errorText' => 'Unable to delete temporary file.',
        ]);
    }

    /**
     * Function to rename and move the finished file.
     *
     * @param mixed $path
     * @param mixed $mode
     *
     * @return string JSON object with result of rename
     */
    public function rmkdir($path, $mode = 0777)
    {
        $path = rtrim(preg_replace(['/\\\/', '/\/{2,}/'], '/', $path), '/');
        $e = explode('/', ltrim($path, '/'));
        if ('/' == substr($path, 0, 1)) {
            $e[0] = '/'.$e[0];
        }
        $c = count($e);
        $cp = $e[0];
        for ($i = 1; $i < $c; $i++) {
            if (!is_dir($cp) && !@mkdir($cp, $mode)) {
                return false;
            }
            $cp .= '/'.$e[$i];
        }

        return @mkdir($path, $mode);
    }

    public function finishUpload($finalName, $scormid)
    {
        $tempFilePath = $this->getTempDirectory().$this->getTempName();

        if (file_exists($tempFilePath)) {
            $pluginFileSystem = Container::getPluginsFileSystem();
            $fsDest = $this->mainDirectory.$finalName;
            $stream = fopen($tempFilePath, 'rb');
            $pluginFileSystem->writeStream($fsDest, $stream);
            fclose($stream);
            unlink($tempFilePath);

            if ($pluginFileSystem->fileExists($fsDest)) {
                return json_encode(['errorStatus' => 0, 'errorText' => 'OK', 'finalName' => $finalName]);
            }
        }

        return json_encode([
            'errorStatus' => 1,
            'errorText' => 'Unable to move file after uploading: '.$this->mainDirectory.$finalName,
        ]);
    }

    /**
     * Basic php file upload function, used for unsupported browsers.
     * The output on success/failure is very basic, and it would be best to have these errors return the user to index.html
     * with the errors printed on the form, but that is beyond the scope of this project as it is very application specific.
     *
     * @param mixed $scormid
     *
     * @return string Success or failure of upload
     */
    public function postUnsupported($scormid)
    {
        $name = $_FILES['bigUploadFile']['name'];
        $tempName = $_FILES['bigUploadFile']['tmp_name'];

        if (filesize($tempName) > self::MAX_SIZE) {
            return 'File is too large.';
        }

        $pluginFileSystem = Container::getPluginsFileSystem();
        $fsDest = $this->mainDirectory.$name;
        $stream = fopen($tempName, 'rb');
        $pluginFileSystem->writeStream($fsDest, $stream);
        fclose($stream);

        return '<div></div>';
    }
}

// Instantiate the class
$bigUpload = new BigUpload();

// Set the temporary filename
$tempName = null;
if (isset($_GET['key'])) {
    $tempName = $_GET['key'];
}
if (isset($_POST['key'])) {
    $tempName = $_POST['key'];
}
$bigUpload->setTempName($tempName);

switch ($_GET['action']) {
    case 'upload':
        print $bigUpload->uploadFile();

        break;

    case 'abort':
        print $bigUpload->abortUpload();

        break;

    case 'finish':
        print $bigUpload->finishUpload($_POST['name'], $_POST['scormid']);

        break;

    case 'post-unsupported':
        print $bigUpload->postUnsupported($_POST['scormid']);

        break;
}
