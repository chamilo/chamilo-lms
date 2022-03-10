<?php

require_once '../../../../global.inc.php';
require_once api_get_path(SYS_CODE_PATH).'work/work.lib.php';

class BigUploadResponse
{
    /**
     * Temporary directory for uploading files.
     */
    const TEMP_DIRECTORY = '/tmp/';

    /**
     * Directory files will be moved to after the upload is completed.
     */
    const MAIN_DIRECTORY = '../files/';

    /**
     * Max allowed filesize. This is for unsupported browsers and
     * as an additional security check in case someone bypasses the js filesize check.
     */
    private $maxSize;

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
        $tempDirectory = api_get_path(SYS_ARCHIVE_PATH);
        $this->setTempDirectory($tempDirectory);
        $this->setMainDirectory(self::MAIN_DIRECTORY);
        $this->maxSize = getIniMaxFileSizeInBytes();
    }

    /**
     * Create a random file name for the file to use as it's being uploaded.
     *
     * @param string $value Temporary filename
     */
    public function setTempName($value = null)
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
    public function setMainDirectory($value)
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
        //Make sure the total file we're writing to hasn't surpassed the file size limit
        if (file_exists($this->getTempDirectory().$this->getTempName())) {
            if (filesize($this->getTempDirectory().$this->getTempName()) > $this->maxSize) {
                $this->abortUpload();

                return json_encode([
                        'errorStatus' => 1,
                        'errorText' => get_lang('UplFileTooBig'),
                    ]);
            }
        }

        //Open the raw POST data from php://input
        $fileData = file_get_contents('php://input');

        //Write the actual chunk to the larger file
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
        if (unlink($this->getTempDirectory().$this->getTempName())) {
            return json_encode(['errorStatus' => 0]);
        } else {
            return json_encode([
                'errorStatus' => 1,
                'errorText' => get_lang('UnableToDeleteTempFile'),
            ]);
        }
    }

    /**
     * Function to rename and move the finished file.
     *
     * @param string $final_name Name to rename the finished upload to
     *
     * @return string JSON object with result of rename
     */
    public function finishUpload($finalName)
    {
        $origin = $_POST['origin'];
        if ($origin == 'document') {
            $tmpFile = $this->getTempDirectory().$this->getTempName();
            chmod($tmpFile, '0777');
            $file = [
                'name' => $finalName,
                'type' => $_POST['type'],
                'tmp_name' => $tmpFile,
                'error' => 0,
                'size' => $_POST['size'],
                'copy_file' => true,
            ];
            $files = ['file' => $file];
            $unzip = isset($_POST['unzip']) ? $_POST['unzip'] : null;
            $index = isset($_POST['index_document']) ? $_POST['index_document'] : null;
            DocumentManager::upload_document(
                $files,
                $_POST['curdirpath'],
                $_POST['title'],
                $_POST['comment'],
                $unzip,
                $_POST['if_exists'],
                $index,
                true
            );
            $redirectUrl = api_get_path(WEB_CODE_PATH).'document/document.php?'.api_get_cidreq();
            if (!empty($_POST['id'])) {
                $redirectUrl .= '&'.http_build_query(
                    [
                        'id' => $_POST['id'],
                    ]
                );
            }

            return json_encode(['errorStatus' => 0, 'redirect' => $redirectUrl]);
        } elseif ($origin == 'learnpath') {
            unset($_REQUEST['origin']);
            $redirectUrl = api_get_path(WEB_CODE_PATH).'upload/upload.php?'.api_get_cidreq().'&from=bigUpload&name='.$this->getTempName();

            return json_encode(['errorStatus' => 0, 'redirect' => $redirectUrl]);
        } elseif ($origin == 'work') {
            $tmpFile = $this->getTempDirectory().$this->getTempName();
            chmod($tmpFile, '0777');
            $workInfo = get_work_data_by_id($_REQUEST['id']);
            $values = $_REQUEST;
            $courseInfo = api_get_course_info();
            $sessionId = api_get_session_id();
            $groupId = api_get_group_id();
            $userId = api_get_user_id();
            $values['contains_file'] = 1;
            $values['title'] = $finalName;
            $file = [
                'name' => $finalName,
                'type' => $_POST['type'],
                'tmp_name' => $tmpFile,
                'error' => 0,
                'size' => $_POST['size'],
                'copy_file' => true,
            ];

            // Process work
            $result = processWorkForm(
                $workInfo,
                $values,
                $courseInfo,
                $sessionId,
                $groupId,
                $userId,
                $file,
                api_get_configuration_value('assignment_prevent_duplicate_upload')
            );
            $redirectUrl = api_get_path(WEB_CODE_PATH).'work/work.php?'.api_get_cidreq();

            return json_encode(['errorStatus' => 0, 'redirect' => $redirectUrl]);
        }

        return json_encode(['errorStatus' => 0]);
    }

    /**
     * Basic php file upload function, used for unsupported browsers.
     * The output on success/failure is very basic, and it would be best to have these errors return the user to index.html
     * with the errors printed on the form, but that is beyond the scope of this project as it is very application specific.
     *
     * @return string Success or failure of upload
     */
    public function postUnsupported()
    {
        $name = $_FILES['bigUploadFile']['name'];
        $size = $_FILES['bigUploadFile']['size'];
        $tempName = $_FILES['bigUploadFile']['tmp_name'];

        if (filesize($tempName) > $this->maxSize) {
            return get_lang('UplFileTooBig');
        }

        if (move_uploaded_file($tempName, $this->getMainDirectory().$name)) {
            return get_lang('FileUploadSucces');
        } else {
            return get_lang('UplUnableToSaveFile');
        }
    }
}

//Instantiate the class
$bigUpload = new BigUploadResponse();

//Set the temporary filename
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
        print $bigUpload->finishUpload($_POST['name']);
        break;
    case 'post-unsupported':
        print $bigUpload->postUnsupported();
        break;
}
