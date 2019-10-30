<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CDocument;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * FILE UPLOAD LIBRARY.
 *
 * This is the file upload library for Chamilo.
 * Include/require it in your code to use its functionality.
 *
 * @package chamilo.library
 *
 * @todo test and reorganise
 */

/**
 * Changes the file name extension from .php to .phps
 * Useful for securing a site.
 *
 * @author Hugues Peeters <peeters@ipm.ucl.ac.be>
 *
 * @param string $file_name Name of a file
 *
 * @return string the filename phps'ized
 */
function php2phps($file_name)
{
    return preg_replace('/\.(php.?|phtml.?)(\.){0,1}.*$/i', '.phps', $file_name);
}

/**
 * Renames .htaccess & .HTACCESS to htaccess.txt.
 *
 * @param string $filename
 *
 * @return string
 */
function htaccess2txt($filename)
{
    return str_replace(['.htaccess', '.HTACCESS'], ['htaccess.txt', 'htaccess.txt'], $filename);
}

/**
 * This function executes our safety precautions
 * more functions can be added.
 *
 * @param string $filename
 *
 * @return string
 *
 * @see php2phps()
 * @see htaccess2txt()
 */
function disable_dangerous_file($filename)
{
    return htaccess2txt(php2phps($filename));
}

/**
 * Returns the name without extension, used for the title.
 *
 * @param string $name
 *
 * @return name without the extension
 */
function get_document_title($name)
{
    // If they upload .htaccess...
    $name = disable_dangerous_file($name);
    $ext = substr(strrchr($name, '.'), 0);

    if (empty($ext)) {
        return substr($name, 0, strlen($name));
    }

    return substr($name, 0, strlen($name) - strlen(strstr($name, $ext)));
}

/**
 * This function checks if the upload succeeded.
 *
 * @param array $uploaded_file ($_FILES)
 *
 * @return true if upload succeeded
 */
function process_uploaded_file($uploaded_file, $show_output = true)
{
    // Checking the error code sent with the file upload.
    if (isset($uploaded_file['error'])) {
        switch ($uploaded_file['error']) {
            case 1:
                // The uploaded file exceeds the upload_max_filesize directive in php.ini.
                if ($show_output) {
                    Display::addFlash(
                        Display::return_message(
                            get_lang('The uploaded file exceeds the maximum filesize allowed by the server:').ini_get('upload_max_filesize'),
                            'error'
                        )
                    );
                }

                return false;
            case 2:
                // The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.
                // Not used at the moment, but could be handy if we want to limit the size of an upload
                // (e.g. image upload in html editor).
                $max_file_size = (int) $_POST['MAX_FILE_SIZE'];
                if ($show_output) {
                    Display::addFlash(
                        Display::return_message(
                            get_lang('The file size exceeds the maximum allowed setting:').format_file_size($max_file_size),
                            'error'
                        )
                    );
                }

                return false;
            case 3:
                // The uploaded file was only partially uploaded.
                if ($show_output) {
                    Display::addFlash(
                        Display::return_message(
                            get_lang('The uploaded file was only partially uploaded.').' '.get_lang('Please Try Again!'),
                            'error'
                        )
                    );
                }

                return false;
            case 4:
                // No file was uploaded.
                if ($show_output) {
                    Display::addFlash(
                        Display::return_message(
                            get_lang('No file was uploaded.').' '.get_lang('Please select a file before pressing the upload button.'),
                            'error'
                        )
                    );
                }

                return false;
        }
    }

    if (!file_exists($uploaded_file['tmp_name'])) {
        // No file was uploaded.
        if ($show_output) {
            Display::addFlash(Display::return_message(get_lang('The file upload has failed.'), 'error'));
        }

        return false;
    }

    if (file_exists($uploaded_file['tmp_name'])) {
        $filesize = filesize($uploaded_file['tmp_name']);
        if (empty($filesize)) {
            // No file was uploaded.
            if ($show_output) {
                Display::addFlash(
                    Display::return_message(
                        get_lang('The file upload has failed.SizeIsZero'),
                        'error'
                    )
                );
            }

            return false;
        }
    }

    $course_id = api_get_course_id();

    //Checking course quota if we are in a course
    if (!empty($course_id)) {
        $max_filled_space = DocumentManager::get_course_quota();
        // Check if there is enough space to save the file
        if (!DocumentManager::enough_space($uploaded_file['size'], $max_filled_space)) {
            if ($show_output) {
                Display::addFlash(
                    Display::return_message(
                        get_lang('There is not enough space to upload this file.'),
                        'error'
                    )
                );
            }

            return false;
        }
    }

    // case 0: default: We assume there is no error, the file uploaded with success.
    return true;
}

/**
 * This function does the save-work for the documents.
 * It handles the uploaded file and adds the properties to the database
 * If unzip=1 and the file is a zipfile, it is extracted
 * If we decide to save ALL kinds of documents in one database,
 * we could extend this with a $type='document', 'scormdocument',...
 *
 * @param array  $courseInfo
 * @param array  $uploadedFile            ($_FILES)
 *                                        array(
 *                                        'name' => 'picture.jpg',
 *                                        'tmp_name' => '...', // absolute path
 *                                        );
 * @param string $documentDir             Example: /var/www/chamilo/courses/ABC/document
 * @param string $uploadPath              Example: /folder1/folder2/
 * @param int    $userId
 * @param int    $groupId                 group.id
 * @param int    $toUserId                User ID, or NULL for everybody
 * @param int    $unzip                   1/0
 * @param string $whatIfFileExists        overwrite, rename or warn if exists (default)
 * @param bool   $output                  optional output parameter
 * @param bool   $onlyUploadFile
 * @param string $comment
 * @param int    $sessionId
 * @param bool   $treat_spaces_as_hyphens
 * @param string $uploadKey
 * @param int    $parentId
 * @param $content
 *
 * So far only use for unzip_uploaded_document function.
 * If no output wanted on success, set to false.
 *
 * @return CDocument|false
 */
function handle_uploaded_document(
    $courseInfo,
    $uploadedFile,
    $documentDir,
    $uploadPath,
    $userId,
    $groupId = 0,
    $toUserId = null,
    $unzip = 0,
    $whatIfFileExists = '',
    $output = true,
    $onlyUploadFile = false,
    $comment = null,
    $sessionId = null,
    $treat_spaces_as_hyphens = true,
    $uploadKey = '',
    $parentId = 0,
    $content = null
) {
    if (!$userId) {
        return false;
    }

    $userInfo = api_get_user_info();
    $uploadedFile['name'] = stripslashes($uploadedFile['name']);
    // Add extension to files without one (if possible)
    $uploadedFile['name'] = add_ext_on_mime($uploadedFile['name'], $uploadedFile['type']);
    $sessionId = (int) $sessionId;
    if (empty($sessionId)) {
        $sessionId = api_get_session_id();
    }

    $group = api_get_group_entity($groupId);

    // Just in case process_uploaded_file is not called
    $maxSpace = DocumentManager::get_course_quota();

    // Check if there is enough space to save the file
    if (!DocumentManager::enough_space($uploadedFile['size'], $maxSpace)) {
        if ($output) {
            Display::addFlash(Display::return_message(get_lang('There is not enough space to upload this file.'), 'error'));
        }

        return false;
    }

    // If the want to unzip, check if the file has a .zip (or ZIP,Zip,ZiP,...) extension
    if ($unzip == 1 && preg_match('/.zip$/', strtolower($uploadedFile['name']))) {
        return unzip_uploaded_document(
            $courseInfo,
            $userInfo,
            $uploadedFile,
            $uploadPath,
            $documentDir,
            $maxSpace,
            $sessionId,
            $groupId,
            $output,
            $onlyUploadFile,
            $whatIfFileExists
        );
    } elseif ($unzip == 1 && !preg_match('/.zip$/', strtolower($uploadedFile['name']))) {
        // We can only unzip ZIP files (no gz, tar,...)
        if ($output) {
            Display::addFlash(
                Display::return_message(
                    get_lang('The file you selected was not a zip file.')." ".get_lang('Please Try Again!'),
                    'error'
                )
            );
        }

        return false;
    } else {
        // Clean up the name, only ASCII characters should stay. (and strict)
        $cleanName = api_replace_dangerous_char($uploadedFile['name'], $treat_spaces_as_hyphens);

        // No "dangerous" files
        $cleanName = disable_dangerous_file($cleanName);

        // Checking file extension
        if (!filter_extension($cleanName)) {
            if ($output) {
                Display::addFlash(
                    Display::return_message(
                        get_lang('File upload failed: this file extension or file type is prohibited'),
                        'error'
                    )
                );
            }

            return false;
        } else {
            // If the upload path differs from / (= root) it will need a slash at the end
            if ($uploadPath != '/') {
                $uploadPath = $uploadPath.'/';
            }

            // Full path to where we want to store the file with trailing slash
            $whereToSave = $documentDir.$uploadPath;

            // Just upload the file "as is"
            if ($onlyUploadFile) {
                $errorResult = moveUploadedFile($uploadedFile, $whereToSave.$cleanName);
                if ($errorResult) {
                    return $whereToSave.$cleanName;
                }

                return $errorResult;
            }

            /*
                Based in the clean name we generate a new filesystem name
                Using the session_id and group_id if values are not empty
            */
            $fileSystemName = DocumentManager::fixDocumentName(
                $cleanName,
                'file',
                $courseInfo,
                $sessionId,
                $groupId
            );

            // Name of the document without the extension (for the title)
            $documentTitle = get_document_title($uploadedFile['name']);

            // Size of the uploaded file (in bytes)
            $fileSize = $uploadedFile['size'];

            // Example: /folder/picture.jpg
            $filePath = $uploadPath.$fileSystemName;

            $docId = DocumentManager::get_document_id(
                $courseInfo,
                $filePath,
                $sessionId
            );

            $documentRepo = Container::getDocumentRepository();
            $document = $documentRepo->find($docId);

            if (!($content instanceof UploadedFile)) {
                $request = Container::getRequest();
                $content = $request->files->get($uploadKey);
                if (is_array($content)) {
                    $content = $content[0];
                }
            }

            // What to do if the target file exists
            switch ($whatIfFileExists) {
                // Overwrite the file if it exists
                case 'overwrite':
                    if ($document) {
                        // Update file size
                        update_existing_document(
                            $courseInfo,
                            $document->getIid(),
                            $uploadedFile['size']
                        );

                        $document = DocumentManager::addFileToDocument(
                            $document,
                            $filePath,
                            $content,
                            null,
                            null,
                            $group
                        );

                        // Display success message with extra info to user
                        if ($document && $output) {
                            Display::addFlash(
                                Display::return_message(
                                    get_lang('File upload succeeded!').'<br /> '.
                                    $document->getTitle().' '.get_lang(' was overwritten.'),
                                    'confirmation',
                                    false
                                )
                            );
                        }

                        return $document;
                    } else {
                        // Put the document data in the database
                        $document = DocumentManager::addDocument(
                            $courseInfo,
                            $filePath,
                            'file',
                            $fileSize,
                            $documentTitle,
                            $comment,
                            0,
                            null,
                            $groupId,
                            $sessionId,
                            0,
                            true,
                            $content
                        );

                        // Display success message to user
                        if ($document) {
                            Display::addFlash(
                                Display::return_message(
                                    get_lang('File upload succeeded!').'<br /> '.$documentTitle,
                                    'confirmation',
                                    false
                                )
                            );
                        }

                        return $document;
                    }
                    break;
                case 'rename':
                    // Rename the file if it exists
                    $cleanName = DocumentManager::getUniqueFileName(
                        $uploadPath,
                        $cleanName,
                        $courseInfo,
                        $sessionId,
                        $groupId
                    );

                    $fileSystemName = DocumentManager::fixDocumentName(
                        $cleanName,
                        'file',
                        $courseInfo,
                        $sessionId,
                        $groupId
                    );

                    $documentTitle = disable_dangerous_file($cleanName);
                    $filePath = $uploadPath.$fileSystemName;

                    // Put the document data in the database
                    $document = DocumentManager::addDocument(
                        $courseInfo,
                        $filePath,
                        'file',
                        $fileSize,
                        $documentTitle,
                        $comment, // comment
                        0, // read only
                        true, // save visibility
                        $groupId,
                        $sessionId,
                        0,
                        true,
                        $content,
                        $parentId
                    );

                    // Display success message to user
                    if ($output && $document) {
                        Display::addFlash(
                            Display::return_message(
                                get_lang('File upload succeeded!').'<br />'.
                                get_lang('File saved as').' '.$document->getTitle(),
                                'success',
                                false
                            )
                        );
                    }

                    return $document;
                    break;
                case 'nothing':
                    if ($document) {
                        if ($output) {
                            Display::addFlash(
                                Display::return_message(
                                    $uploadPath.$cleanName.' '.get_lang(' already exists.'),
                                    'warning',
                                    false
                                )
                            );
                        }
                        break;
                    }
                    // no break
                default:
                    // Only save the file if it doesn't exist or warn user if it does exist
                    if ($document) {
                        if ($output) {
                            Display::addFlash(
                                Display::return_message($cleanName.' '.get_lang(' already exists.'), 'warning', false)
                            );
                        }
                    } else {
                        // Put the document data in the database
                        $document = DocumentManager::addDocument(
                            $courseInfo,
                            $filePath,
                            'file',
                            $fileSize,
                            $documentTitle,
                            $comment,
                            0,
                            true,
                            $groupId,
                            $sessionId,
                            0,
                            true,
                            $content
                        );

                        if ($document) {
                            // Display success message to user
                            if ($output) {
                                Display::addFlash(
                                    Display::return_message(
                                        get_lang('File upload succeeded!').'<br /> '.$documentTitle,
                                        'confirm',
                                        false
                                    )
                                );
                            }

                            return $document;
                        } else {
                            if ($output) {
                                Display::addFlash(
                                    Display::return_message(
                                        get_lang('The uploaded file could not be saved (perhaps a permission problem?)'),
                                        'error',
                                        false
                                    )
                                );
                            }
                        }
                    }
                    break;
            }
        }
    }
}

/**
 * @param string $file
 * @param string $storePath
 *
 * @return bool
 */
function moveUploadedFile($file, $storePath)
{
    $handleFromFile = isset($file['from_file']) && $file['from_file'] ? true : false;
    $moveFile = isset($file['move_file']) && $file['move_file'] ? true : false;
    if ($moveFile) {
        $copied = copy($file['tmp_name'], $storePath);

        if (!$copied) {
            return false;
        }
    }
    if ($handleFromFile) {
        return file_exists($file['tmp_name']);
    } else {
        return move_uploaded_file($file['tmp_name'], $storePath);
    }
}

/**
 * Checks if there is enough place to add a file on a directory
 * on the base of a maximum directory size allowed
 * deprecated: use enough_space instead!
 *
 * @author Hugues Peeters <peeters@ipm.ucl.ac.be>
 *
 * @param int    $file_size     Size of the file in byte
 * @param string $dir           Path of the directory where the file should be added
 * @param int    $max_dir_space Maximum size of the diretory in byte
 *
 * @return bool true if there is enough space, false otherwise
 *
 * @see enough_size() uses  dir_total_space() function
 */
function enough_size($file_size, $dir, $max_dir_space)
{
    // If the directory is the archive directory, safely ignore the size limit
    if (api_get_path(SYS_ARCHIVE_PATH) == $dir) {
        return true;
    }

    if ($max_dir_space) {
        $already_filled_space = dir_total_space($dir);
        if (($file_size + $already_filled_space) > $max_dir_space) {
            return false;
        }
    }

    return true;
}

/**
 * Computes the size already occupied by a directory and is subdirectories.
 *
 * @author Hugues Peeters <peeters@ipm.ucl.ac.be>
 *
 * @param string $dir_path Size of the file in byte
 *
 * @return int Return the directory size in bytes
 */
function dir_total_space($dir_path)
{
    $save_dir = getcwd();
    chdir($dir_path);
    $handle = opendir($dir_path);
    $sumSize = 0;
    $dirList = [];
    while ($element = readdir($handle)) {
        if ($element == '.' || $element == '..') {
            continue; // Skip the current and parent directories
        }
        if (is_file($element)) {
            $sumSize += filesize($element);
        }
        if (is_dir($element)) {
            $dirList[] = $dir_path.'/'.$element;
        }
    }

    closedir($handle);

    if (sizeof($dirList) > 0) {
        foreach ($dirList as $j) {
            $sizeDir = dir_total_space($j); // Recursivity
            $sumSize += $sizeDir;
        }
    }
    chdir($save_dir); // Return to initial position

    return $sumSize;
}

/**
 * Tries to add an extension to files without extension
 * Some applications on Macintosh computers don't add an extension to the files.
 * This subroutine try to fix this on the basis of the MIME type sent
 * by the browser.
 *
 * Note : some browsers don't send the MIME Type (e.g. Netscape 4).
 *        We don't have solution for this kind of situation
 *
 * @author Hugues Peeters <peeters@ipm.ucl.ac.be>
 * @author Bert Vanderkimpen
 *
 * @param string $file_name Name of the file
 * @param string $file_type Type of the file
 *
 * @return string File name
 */
function add_ext_on_mime($file_name, $file_type)
{
    // Check whether the file has an extension AND whether the browser has sent a MIME Type

    if (!preg_match('/^.*\.[a-zA-Z_0-9]+$/', $file_name) && $file_type) {
        // Build a "MIME-types / extensions" connection table
        static $mime_type = [];

        $mime_type[] = 'application/msword';
        $extension[] = '.doc';
        $mime_type[] = 'application/rtf';
        $extension[] = '.rtf';
        $mime_type[] = 'application/vnd.ms-powerpoint';
        $extension[] = '.ppt';
        $mime_type[] = 'application/vnd.ms-excel';
        $extension[] = '.xls';
        $mime_type[] = 'application/pdf';
        $extension[] = '.pdf';
        $mime_type[] = 'application/postscript';
        $extension[] = '.ps';
        $mime_type[] = 'application/mac-binhex40';
        $extension[] = '.hqx';
        $mime_type[] = 'application/x-gzip';
        $extension[] = 'tar.gz';
        $mime_type[] = 'application/x-shockwave-flash';
        $extension[] = '.swf';
        $mime_type[] = 'application/x-stuffit';
        $extension[] = '.sit';
        $mime_type[] = 'application/x-tar';
        $extension[] = '.tar';
        $mime_type[] = 'application/zip';
        $extension[] = '.zip';
        $mime_type[] = 'application/x-tar';
        $extension[] = '.tar';
        $mime_type[] = 'text/html';
        $extension[] = '.html';
        $mime_type[] = 'text/plain';
        $extension[] = '.txt';
        $mime_type[] = 'text/rtf';
        $extension[] = '.rtf';
        $mime_type[] = 'img/gif';
        $extension[] = '.gif';
        $mime_type[] = 'img/jpeg';
        $extension[] = '.jpg';
        $mime_type[] = 'img/png';
        $extension[] = '.png';
        $mime_type[] = 'audio/midi';
        $extension[] = '.mid';
        $mime_type[] = 'audio/mpeg';
        $extension[] = '.mp3';
        $mime_type[] = 'audio/x-aiff';
        $extension[] = '.aif';
        $mime_type[] = 'audio/x-pn-realaudio';
        $extension[] = '.rm';
        $mime_type[] = 'audio/x-pn-realaudio-plugin';
        $extension[] = '.rpm';
        $mime_type[] = 'audio/x-wav';
        $extension[] = '.wav';
        $mime_type[] = 'video/mpeg';
        $extension[] = '.mpg';
        $mime_type[] = 'video/mpeg4-generic';
        $extension[] = '.mp4';
        $mime_type[] = 'video/quicktime';
        $extension[] = '.mov';
        $mime_type[] = 'video/x-msvideo';
        $extension[] = '.avi';

        $mime_type[] = 'video/x-ms-wmv';
        $extension[] = '.wmv';
        $mime_type[] = 'video/x-flv';
        $extension[] = '.flv';
        $mime_type[] = 'image/svg+xml';
        $extension[] = '.svg';
        $mime_type[] = 'image/svg+xml';
        $extension[] = '.svgz';
        $mime_type[] = 'video/ogg';
        $extension[] = '.ogv';
        $mime_type[] = 'audio/ogg';
        $extension[] = '.oga';
        $mime_type[] = 'application/ogg';
        $extension[] = '.ogg';
        $mime_type[] = 'application/ogg';
        $extension[] = '.ogx';
        $mime_type[] = 'application/x-freemind';
        $extension[] = '.mm';

        $mime_type[] = 'application/vnd.ms-word.document.macroEnabled.12';
        $extension[] = '.docm';
        $mime_type[] = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
        $extension[] = '.docx';
        $mime_type[] = 'application/vnd.ms-word.template.macroEnabled.12';
        $extension[] = '.dotm';
        $mime_type[] = 'application/vnd.openxmlformats-officedocument.wordprocessingml.template';
        $extension[] = '.dotx';
        $mime_type[] = 'application/vnd.ms-powerpoint.template.macroEnabled.12';
        $extension[] = '.potm';
        $mime_type[] = 'application/vnd.openxmlformats-officedocument.presentationml.template';
        $extension[] = '.potx';
        $mime_type[] = 'application/vnd.ms-powerpoint.addin.macroEnabled.12';
        $extension[] = '.ppam';
        $mime_type[] = 'application/vnd.ms-powerpoint.slideshow.macroEnabled.12';
        $extension[] = '.ppsm';
        $mime_type[] = 'application/vnd.openxmlformats-officedocument.presentationml.slideshow';
        $extension[] = '.ppsx';
        $mime_type[] = 'application/vnd.ms-powerpoint.presentation.macroEnabled.12';
        $extension[] = '.pptm';
        $mime_type[] = 'application/vnd.openxmlformats-officedocument.presentationml.presentation';
        $extension[] = '.pptx';
        $mime_type[] = 'application/vnd.ms-excel.addin.macroEnabled.12';
        $extension[] = '.xlam';
        $mime_type[] = 'application/vnd.ms-excel.sheet.binary.macroEnabled.12';
        $extension[] = '.xlsb';
        $mime_type[] = 'application/vnd.ms-excel.sheet.macroEnabled.12';
        $extension[] = '.xlsm';
        $mime_type[] = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
        $extension[] = '.xlsx';
        $mime_type[] = 'application/vnd.ms-excel.template.macroEnabled.12';
        $extension[] = '.xltm';
        $mime_type[] = 'application/vnd.openxmlformats-officedocument.spreadsheetml.template';
        $extension[] = '.xltx';

        // Test on PC (files with no extension get application/octet-stream)
        //$mime_type[] = 'application/octet-stream';      $extension[] = '.ext';
        // Check whether the MIME type sent by the browser is within the table
        foreach ($mime_type as $key => &$type) {
            if ($type == $file_type) {
                $file_name .= $extension[$key];
                break;
            }
        }

        unset($mime_type, $extension, $type, $key); // Delete to eschew possible collisions
    }

    return $file_name;
}

/**
 * Manages all the unzipping process of an uploaded file.
 *
 * @author Hugues Peeters <hugues.peeters@claroline.net>
 *
 * @param array  $uploaded_file    - follows the $_FILES Structure
 * @param string $upload_path      - destination of the upload.
 *                                 This path is to append to $base_work_dir
 * @param string $base_work_dir    - base working directory of the module
 * @param int    $max_filled_space - amount of bytes to not exceed in the base
 *                                 working directory
 *
 * @return bool true if it succeeds false otherwise
 */
function unzip_uploaded_file($uploaded_file, $upload_path, $base_work_dir, $max_filled_space)
{
    $zip_file = new PclZip($uploaded_file['tmp_name']);

    // Check the zip content (real size and file extension)
    if (file_exists($uploaded_file['tmp_name'])) {
        $zip_content_array = $zip_file->listContent();
        $ok_scorm = false;
        $realFileSize = 0;
        foreach ($zip_content_array as &$this_content) {
            if (preg_match('~.(php.*|phtml)$~i', $this_content['filename'])) {
                Display::addFlash(
                    Display::return_message(get_lang('The zip file can not contain .PHP files'))
                );

                return false;
            } elseif (stristr($this_content['filename'], 'imsmanifest.xml')) {
                $ok_scorm = true;
            } elseif (stristr($this_content['filename'], 'LMS')) {
                $ok_plantyn_scorm1 = true;
            } elseif (stristr($this_content['filename'], 'REF')) {
                $ok_plantyn_scorm2 = true;
            } elseif (stristr($this_content['filename'], 'SCO')) {
                $ok_plantyn_scorm3 = true;
            } elseif (stristr($this_content['filename'], 'AICC')) {
                $ok_aicc_scorm = true;
            }
            $realFileSize += $this_content['size'];
        }

        if (($ok_plantyn_scorm1 && $ok_plantyn_scorm2 && $ok_plantyn_scorm3) || $ok_aicc_scorm) {
            $ok_scorm = true;
        }

        if (!$ok_scorm && defined('CHECK_FOR_SCORM') && CHECK_FOR_SCORM) {
            Display::addFlash(
                Display::return_message(get_lang('This is not a valid SCORM ZIP file !'))
            );

            return false;
        }

        if (!enough_size($realFileSize, $base_work_dir, $max_filled_space)) {
            Display::addFlash(
                Display::return_message(get_lang('The upload has failed. Either you have exceeded your maximum quota, or there is not enough disk space.'))
            );

            return false;
        }

        // It happens on Linux that $upload_path sometimes doesn't start with '/'
        if ($upload_path[0] != '/' && substr($base_work_dir, -1, 1) != '/') {
            $upload_path = '/'.$upload_path;
        }

        if ($upload_path[strlen($upload_path) - 1] == '/') {
            $upload_path = substr($upload_path, 0, -1);
        }

        /*	Uncompressing phase */

        /*
            The first version, using OS unzip, is not used anymore
            because it does not return enough information.
            We need to process each individual file in the zip archive to
            - add it to the database
            - parse & change relative html links
        */
        if (PHP_OS == 'Linux' && !get_cfg_var('safe_mode') && false) { // *** UGent, changed by OC ***
            // Shell Method - if this is possible, it gains some speed
            exec("unzip -d \"".$base_work_dir.$upload_path."/\"".$uploaded_file['name']." ".$uploaded_file['tmp_name']);
        } else {
            // PHP method - slower...
            $save_dir = getcwd();
            chdir($base_work_dir.$upload_path);
            $unzippingState = $zip_file->extract();
            for ($j = 0; $j < count($unzippingState); $j++) {
                $state = $unzippingState[$j];

                // Fix relative links in html files
                $extension = strrchr($state['stored_filename'], '.');
            }
            if ($dir = @opendir($base_work_dir.$upload_path)) {
                while ($file = readdir($dir)) {
                    if ($file != '.' && $file != '..') {
                        $filetype = 'file';
                        if (is_dir($base_work_dir.$upload_path.'/'.$file)) {
                            $filetype = 'folder';
                        }

                        $safe_file = api_replace_dangerous_char($file);
                        @rename($base_work_dir.$upload_path.'/'.$file, $base_work_dir.$upload_path.'/'.$safe_file);
                        set_default_settings($upload_path, $safe_file, $filetype);
                    }
                }

                closedir($dir);
            } else {
                error_log('Could not create directory '.$base_work_dir.$upload_path.' to unzip files');
            }
            chdir($save_dir); // Back to previous dir position
        }
    }

    return true;
}

/**
 * Manages all the unzipping process of an uploaded document
 * This uses the item_property table for properties of documents.
 *
 * @author Hugues Peeters <hugues.peeters@claroline.net>
 * @author Bert Vanderkimpen
 *
 * @param array  $courseInfo
 * @param array  $userInfo
 * @param array  $uploaded_file    - follows the $_FILES Structure
 * @param string $uploadPath       - destination of the upload.
 *                                 This path is to append to $base_work_dir
 * @param string $base_work_dir    - base working directory of the module
 * @param int    $maxFilledSpace   - amount of bytes to not exceed in the base
 *                                 working directory
 * @param int    $sessionId
 * @param int    $groupId          group.id
 * @param bool   $output           Optional. If no output not wanted on success, set to false.
 * @param bool   $onlyUploadFile
 * @param string $whatIfFileExists (only works if $onlyUploadFile is false)
 *
 * @return bool true if it succeeds false otherwise
 */
function unzip_uploaded_document(
    $courseInfo,
    $userInfo,
    $uploaded_file,
    $uploadPath,
    $base_work_dir,
    $maxFilledSpace,
    $sessionId = 0,
    $groupId = 0,
    $output = true,
    $onlyUploadFile = false,
    $whatIfFileExists = 'overwrite'
) {
    $zip = new PclZip($uploaded_file['tmp_name']);

    // Check the zip content (real size and file extension)
    $zip_content_array = (array) $zip->listContent();
    $realSize = 0;
    foreach ($zip_content_array as &$this_content) {
        $realSize += $this_content['size'];
    }

    if (!DocumentManager::enough_space($realSize, $maxFilledSpace)) {
        echo Display::return_message(get_lang('There is not enough space to upload this file.'), 'error');

        return false;
    }

    $folder = api_get_unique_id();
    $destinationDir = api_get_path(SYS_ARCHIVE_PATH).$folder;
    mkdir($destinationDir, api_get_permissions_for_new_directories(), true);

    // Uncompress zip file
    // We extract using a callback function that "cleans" the path
    $zip->extract(
        PCLZIP_OPT_PATH,
        $destinationDir,
        PCLZIP_CB_PRE_EXTRACT,
        'clean_up_files_in_zip',
        PCLZIP_OPT_REPLACE_NEWER
    );

    if ($onlyUploadFile === false) {
        // Add all documents in the unzipped folder to the database
        add_all_documents_in_folder_to_database(
            $courseInfo,
            $userInfo,
            $base_work_dir,
            $destinationDir,
            $sessionId,
            $groupId,
            $output,
            ['path' => $uploadPath],
            $whatIfFileExists
        );
    } else {
        // Copy result
        $fs = new \Symfony\Component\Filesystem\Filesystem();
        $fs->mirror($destinationDir, $base_work_dir.$uploadPath, null, ['overwrite']);
    }

    if (is_dir($destinationDir)) {
        rmdirr($destinationDir);
    }

    return true;
}

/**
 * This function is a callback function that is used while extracting a zipfile
 * http://www.phpconcept.net/pclzip/man/en/index.php?options-pclzip_cb_pre_extract.
 *
 * @param array $p_event
 * @param array $p_header
 *
 * @return int (If the function returns 1, then the extraction is resumed, if 0 the path was skipped)
 */
function clean_up_files_in_zip($p_event, &$p_header)
{
    $originalStoredFileName = $p_header['stored_filename'];
    $baseName = basename($originalStoredFileName);
    // Skip files
    $skipFiles = [
        '__MACOSX',
        '.Thumbs.db',
        'Thumbs.db',
    ];

    if (in_array($baseName, $skipFiles)) {
        return 0;
    }
    $modifiedStoredFileName = clean_up_path($originalStoredFileName);
    $p_header['filename'] = str_replace($originalStoredFileName, $modifiedStoredFileName, $p_header['filename']);

    return 1;
}

/**
 * Allow .htaccess file.
 *
 * @param $p_event
 * @param $p_header
 *
 * @return int
 */
function cleanZipFilesAllowHtaccess($p_event, &$p_header)
{
    $originalStoredFileName = $p_header['stored_filename'];
    $baseName = basename($originalStoredFileName);

    $allowFiles = ['.htaccess'];
    if (in_array($baseName, $allowFiles)) {
        return 1;
    }

    // Skip files
    $skipFiles = [
        '__MACOSX',
        '.Thumbs.db',
        'Thumbs.db',
    ];

    if (in_array($baseName, $skipFiles)) {
        return 0;
    }
    $modifiedStoredFileName = clean_up_path($originalStoredFileName);
    $p_header['filename'] = str_replace($originalStoredFileName, $modifiedStoredFileName, $p_header['filename']);

    return 1;
}

/**
 * This function cleans up a given path
 * by eliminating dangerous file names and cleaning them.
 *
 * @param string $path
 *
 * @return string
 *
 * @see disable_dangerous_file()
 * @see api_replace_dangerous_char()
 */
function clean_up_path($path)
{
    // Split the path in folders and files
    $path_array = explode('/', $path);
    // Clean up every folder and filename in the path
    foreach ($path_array as $key => &$val) {
        // We don't want to lose the dots in ././folder/file (cfr. zipfile)
        if ($val != '.') {
            $val = disable_dangerous_file(api_replace_dangerous_char($val));
        }
    }
    // Join the "cleaned" path (modified in-place as passed by reference)
    $path = implode('/', $path_array);
    filter_extension($path);

    return $path;
}

/**
 * Checks if the file is dangerous, based on extension and/or mimetype.
 * The list of extensions accepted/rejected can be found from
 * api_get_setting('upload_extensions_exclude') and api_get_setting('upload_extensions_include').
 *
 * @param string $filename passed by reference. The filename will be modified
 *                         if filter rules say so! (you can include path but the filename should look like 'abc.html')
 *
 * @return int 0 to skip file, 1 to keep file
 */
function filter_extension(&$filename)
{
    if (substr($filename, -1) == '/') {
        return 1; // Authorize directories
    }
    $blacklist = api_get_setting('upload_extensions_list_type');
    if ($blacklist != 'whitelist') { // if = blacklist
        $extensions = explode(';', strtolower(api_get_setting('upload_extensions_blacklist')));

        $skip = api_get_setting('upload_extensions_skip');
        $ext = strrchr($filename, '.');
        $ext = substr($ext, 1);
        if (empty($ext)) {
            return 1; // We're in blacklist mode, so accept empty extensions
        }
        if (in_array(strtolower($ext), $extensions)) {
            if ($skip == 'true') {
                return 0;
            } else {
                $new_ext = api_get_setting('upload_extensions_replace_by');
                $filename = str_replace('.'.$ext, '.'.$new_ext, $filename);

                return 1;
            }
        } else {
            return 1;
        }
    } else {
        $extensions = explode(';', strtolower(api_get_setting('upload_extensions_whitelist')));
        $skip = api_get_setting('upload_extensions_skip');
        $ext = strrchr($filename, '.');
        $ext = substr($ext, 1);
        if (empty($ext)) {
            return 1; // Accept empty extensions
        }
        if (!in_array(strtolower($ext), $extensions)) {
            if ($skip == 'true') {
                return 0;
            } else {
                $new_ext = api_get_setting('upload_extensions_replace_by');
                $filename = str_replace('.'.$ext, '.'.$new_ext, $filename);

                return 1;
            }
        } else {
            return 1;
        }
    }
}

/**
 * Updates an existing document in the database
 * as the file exists, we only need to change the size.
 *
 * @param array $_course
 * @param int   $documentId
 * @param int   $filesize
 * @param int   $readonly
 *
 * @return bool true /false
 */
function update_existing_document($_course, $documentId, $filesize, $readonly = 0)
{
    $document_table = Database::get_course_table(TABLE_DOCUMENT);
    $documentId = intval($documentId);
    $filesize = intval($filesize);
    $readonly = intval($readonly);
    $course_id = $_course['real_id'];

    $sql = "UPDATE $document_table SET
            size = '$filesize',
            readonly = '$readonly'
			WHERE c_id = $course_id AND id = $documentId";
    if (Database::query($sql)) {
        return true;
    } else {
        return false;
    }
}

/**
 * This function updates the last_edit_date, last edit user id on all folders in a given path.
 *
 * @param array  $_course
 * @param string $path
 * @param int    $user_id
 */
function item_property_update_on_folder($_course, $path, $user_id)
{
    // If we are in the root, just return... no need to update anything
    if ($path == '/') {
        return;
    }

    $user_id = intval($user_id);

    // If the given path ends with a / we remove it
    $endchar = substr($path, strlen($path) - 1, 1);
    if ($endchar == '/') {
        $path = substr($path, 0, strlen($path) - 1);
    }

    $table = Database::get_course_table(TABLE_ITEM_PROPERTY);

    // Get the time
    $time = api_get_utc_datetime();

    // Det all paths in the given path
    // /folder/subfolder/subsubfolder/file
    // if file is updated, subsubfolder, subfolder and folder are updated
    $exploded_path = explode('/', $path);
    $course_id = api_get_course_int_id();
    $newpath = '';
    foreach ($exploded_path as $key => &$value) {
        // We don't want a slash before our first slash
        if ($key != 0) {
            $newpath .= '/'.$value;
            // Select ID of given folder
            $folder_id = DocumentManager::get_document_id($_course, $newpath);

            if ($folder_id) {
                $sql = "UPDATE $table SET
				        lastedit_date = '$time',
				        lastedit_type = 'DocumentInFolderUpdated', 
				        lastedit_user_id='$user_id'
						WHERE 
						    c_id = $course_id AND 
						    tool='".TOOL_DOCUMENT."' AND 
						    ref = '$folder_id'";
                Database::query($sql);
            }
        }
    }
}

/**
 * Adds file to document table in database
 * deprecated: use file_set_default_settings instead.
 *
 * @author	Olivier Cauberghe <olivier.cauberghe@ugent.be>
 *
 * @param	path,filename
 * action:	Adds an entry to the document table with the default settings
 */
function set_default_settings($upload_path, $filename, $filetype = 'file')
{
    $dbTable = Database::get_course_table(TABLE_DOCUMENT);
    global $default_visibility;

    if (!$default_visibility) {
        $default_visibility = 'v';
    }
    $filetype = Database::escape_string($filetype);

    $upload_path = str_replace('\\', '/', $upload_path);
    $upload_path = str_replace('//', '/', $upload_path);

    if ($upload_path == '/') {
        $upload_path = '';
    } elseif (!empty($upload_path) && $upload_path[0] != '/') {
        $upload_path = "/$upload_path";
    }

    $endchar = substr($filename, strlen($filename) - 1, 1);

    if ($endchar == '/') {
        $filename = substr($filename, 0, -1);
    }
    $filename = Database::escape_string($filename);
    $query = "SELECT count(*) as bestaat FROM $dbTable
              WHERE path='$upload_path/$filename'";
    $result = Database::query($query);
    $row = Database::fetch_array($result);
    if ($row['bestaat'] > 0) {
        $query = "UPDATE $dbTable SET
		            path='$upload_path/$filename',
		            visibility='$default_visibility',
		            filetype='$filetype'
		          WHERE path='$upload_path/$filename'";
    } else {
        $query = "INSERT INTO $dbTable (path,visibility,filetype)
		          VALUES('$upload_path/$filename','$default_visibility','$filetype')";
    }
    Database::query($query);
}

/**
 * Retrieves the image path list in a html file.
 *
 * @author Hugues Peeters <hugues.peeters@claroline.net>
 *
 * @param string $html_file
 *
 * @return array -  images path list
 */
function search_img_from_html($html_file)
{
    $img_path_list = [];

    if (!$fp = fopen($html_file, 'r')) {
        return;
    }

    // Aearch and store occurences of the <img> tag in an array
    $size_file = (filesize($html_file) === 0) ? 1 : filesize($html_file);
    if (isset($fp) && $fp !== false) {
        $buffer = fread($fp, $size_file);
        if (strlen($buffer) >= 0 && $buffer !== false) {
        } else {
            die('<center>Can not read file.</center>');
        }
    } else {
        die('<center>Can not read file.</center>');
    }
    $matches = [];
    if (preg_match_all('~<[[:space:]]*img[^>]*>~i', $buffer, $matches)) {
        $img_tag_list = $matches[0];
    }

    fclose($fp);
    unset($buffer);

    // Search the image file path from all the <IMG> tag detected

    if (sizeof($img_tag_list) > 0) {
        foreach ($img_tag_list as &$this_img_tag) {
            if (preg_match('~src[[:space:]]*=[[:space:]]*[\"]{1}([^\"]+)[\"]{1}~i', $this_img_tag, $matches)) {
                $img_path_list[] = $matches[1];
            }
        }
        $img_path_list = array_unique($img_path_list); // Remove duplicate entries
    }

    return $img_path_list;
}

/**
 * Creates a new directory trying to find a directory name
 * that doesn't already exist.
 *
 * @author  Hugues Peeters <hugues.peeters@claroline.net>
 * @author  Bert Vanderkimpen
 *
 * @param array  $_course                 current course information
 * @param int    $user_id                 current user id
 * @param int    $session_id
 * @param int    $to_group_id             group.id
 * @param int    $to_user_id
 * @param string $base_work_dir           /var/www/chamilo/courses/ABC/document
 * @param string $desired_dir_name        complete path of the desired name
 *                                        Example: /folder1/folder2
 * @param string $title                   "folder2"
 * @param int    $visibility              (0 for invisible, 1 for visible, 2 for deleted)
 * @param bool   $generateNewNameIfExists
 * @param bool   $sendNotification        depends in conf setting "send_notification_when_document_added"
 * @param array  $parentInfo
 *
 * @return CDocument|false
 */
function create_unexisting_directory(
    $_course,
    $user_id,
    $session_id,
    $to_group_id,
    $to_user_id,
    $base_work_dir,
    $desired_dir_name,
    $title = '',
    $visibility = '',
    $generateNewNameIfExists = false,
    $sendNotification = true,
    $parentInfo = []
) {
    $course_id = $_course['real_id'];
    $session_id = (int) $session_id;

    $folderExists = DocumentManager::folderExists(
        $desired_dir_name,
        $_course,
        $session_id,
        $to_group_id
    );

    if ($folderExists === true) {
        if ($generateNewNameIfExists) {
            $counter = 1;
            while (1) {
                $folderExists = DocumentManager::folderExists(
                    $desired_dir_name.'_'.$counter,
                    $_course,
                    $session_id,
                    $to_group_id
                );

                if ($folderExists === false) {
                    break;
                }
                $counter++;
            }
            $desired_dir_name = $desired_dir_name.'_'.$counter;
        }
    }

    $systemFolderName = $desired_dir_name;

    // Adding suffix
    $suffix = DocumentManager::getDocumentSuffix(
        $_course,
        $session_id,
        $to_group_id
    );

    $systemFolderName .= $suffix;

    if ($title == null) {
        $title = basename($desired_dir_name);
    }

    // Check if pathname already exists inside document table
    $table = Database::get_course_table(TABLE_DOCUMENT);
    $sql = "SELECT id, path FROM $table
            WHERE
                c_id = $course_id AND
                path = '".Database::escape_string($systemFolderName)."'";
    $rs = Database::query($sql);

    $parentId = 0;
    if (!empty($parentInfo) && isset($parentInfo['iid'])) {
        $parentId = $parentInfo['iid'];
    }

    if (Database::num_rows($rs) == 0) {
        $document = DocumentManager::addDocument(
            $_course,
            $systemFolderName,
            'folder',
            0,
            $title,
            null,
            0,
            true,
            $to_group_id,
            $session_id,
            $user_id,
            $sendNotification,
            '',
            $parentId
        );

        if ($document) {
            return $document;
        }
    } else {
        $document = Database::fetch_array($rs);
        $documentData = DocumentManager::get_document_data_by_id(
            $document['id'],
            $_course['code'],
            false,
            $session_id
        );

        if ($documentData) {
            $document = Container::getDocumentRepository()->find($documentData['iid']);

            return $document;
        }
    }

    return false;
}

/**
 * Handles uploaded missing images.
 *
 * @author Hugues Peeters <hugues.peeters@claroline.net>
 * @author Bert Vanderkimpen
 *
 * @param array  $_course
 * @param array  $uploaded_file_collection - follows the $_FILES Structure
 * @param string $base_work_dir
 * @param string $missing_files_dir
 * @param int    $user_id
 * @param int    $to_group_id              group.id
 */
function move_uploaded_file_collection_into_directory(
    $_course,
    $uploaded_file_collection,
    $base_work_dir,
    $missing_files_dir,
    $user_id,
    $to_group_id,
    $to_user_id,
    $max_filled_space
) {
    $number_of_uploaded_images = count($uploaded_file_collection['name']);
    $list = [];
    for ($i = 0; $i < $number_of_uploaded_images; $i++) {
        $missing_file['name'] = $uploaded_file_collection['name'][$i];
        $missing_file['type'] = $uploaded_file_collection['type'][$i];
        $missing_file['tmp_name'] = $uploaded_file_collection['tmp_name'][$i];
        $missing_file['error'] = $uploaded_file_collection['error'][$i];
        $missing_file['size'] = $uploaded_file_collection['size'][$i];

        $upload_ok = process_uploaded_file($missing_file);
        if ($upload_ok) {
            $list[] = handle_uploaded_document(
                $_course,
                $missing_file,
                $base_work_dir,
                $missing_files_dir,
                $user_id,
                $to_group_id,
                $to_user_id,
                $max_filled_space,
                0,
                'overwrite'
            );
        }
        unset($missing_file);
    }

    return $list;
}

/**
 * Opens the old html file and replace the src path into the img tag
 * This also works for files in subdirectories.
 *
 * @param $original_img_path is an array
 * @param $new_img_path is an array
 */
function replace_img_path_in_html_file($original_img_path, $new_img_path, $html_file)
{
    // Open the file
    $fp = fopen($html_file, 'r');
    $buffer = fread($fp, filesize($html_file));
    $new_html_content = '';

    // Fix the image tags
    for ($i = 0, $fileNb = count($original_img_path); $i < $fileNb; $i++) {
        $replace_what = $original_img_path[$i];
        // We only need the directory and the filename /path/to/file_html_files/missing_file.gif -> file_html_files/missing_file.gif
        $exploded_file_path = explode('/', $new_img_path[$i]);
        $replace_by = $exploded_file_path[count($exploded_file_path) - 2].'/'.$exploded_file_path[count($exploded_file_path) - 1];
        $buffer = str_replace($replace_what, $replace_by, $buffer);
    }

    $new_html_content .= $buffer;

    @fclose($fp);

    // Write the resulted new file

    if (!$fp = fopen($html_file, 'w')) {
        return;
    }

    if (!fwrite($fp, $new_html_content)) {
        return;
    }
}

/**
 * Checks the extension of a file, if it's .htm or .html
 * we use search_img_from_html to get all image paths in the file.
 *
 * @param string $file
 *
 * @return array paths
 *
 * @see check_for_missing_files() uses search_img_from_html()
 */
function check_for_missing_files($file)
{
    if (strrchr($file, '.') == '.htm' || strrchr($file, '.') == '.html') {
        $img_file_path = search_img_from_html($file);

        return $img_file_path;
    }

    return false;
}

/**
 * This function builds a form that asks for the missing images in a html file
 * maybe we should do this another way?
 *
 * @param array  $missing_files
 * @param string $upload_path
 * @param string $file_name
 *
 * @return string the form
 */
function build_missing_files_form($missing_files, $upload_path, $file_name)
{
    // Do we need a / or not?
    $added_slash = ($upload_path == '/') ? '' : '/';
    $folder_id = DocumentManager::get_document_id(api_get_course_info(), $upload_path);
    // Build the form
    $form = "<p><strong>".get_lang('Missing images detected')."</strong></p>"
        ."<form method=\"post\" action=\"".api_get_self()."\" enctype=\"multipart/form-data\">"
        // Related_file is the path to the file that has missing images
        ."<input type=\"hidden\" name=\"related_file\" value=\"".$upload_path.$added_slash.$file_name."\" />"
        ."<input type=\"hidden\" name=\"upload_path\" value=\"".$upload_path."\" />"
        ."<input type=\"hidden\" name=\"id\" value=\"".$folder_id."\" />"
        ."<table border=\"0\">";
    foreach ($missing_files as &$this_img_file_path) {
        $form .= "<tr>"
            ."<td>".basename($this_img_file_path)." : </td>"
            ."<td>"
            ."<input type=\"file\" name=\"img_file[]\"/>"
            ."<input type=\"hidden\" name=\"img_file_path[]\" value=\"".$this_img_file_path."\" />"
            ."</td>"
            ."</tr>";
    }
    $form .= "</table>"
        ."<button type='submit' name=\"cancel_submit_image\" value=\"".get_lang('Cancel')."\" class=\"cancel\">".get_lang('Cancel')."</button>"
        ."<button type='submit' name=\"submit_image\" value=\"".get_lang('Validate')."\" class=\"save\">".get_lang('Validate')."</button>"
        ."</form>";

    return $form;
}

/**
 * This recursive function can be used during the upgrade process form older
 * versions of Chamilo
 * It crawls the given directory, checks if the file is in the DB and adds
 * it if it's not.
 *
 * @param array  $courseInfo
 * @param array  $userInfo
 * @param string $base_work_dir    course document dir
 * @param string $folderPath       folder to read
 * @param int    $sessionId
 * @param int    $groupId          group.id
 * @param bool   $output
 * @param array  $parent
 * @param string $whatIfFileExists
 *
 * @return bool
 */
function add_all_documents_in_folder_to_database(
    $courseInfo,
    $userInfo,
    $base_work_dir,
    $folderPath,
    $sessionId = 0,
    $groupId = 0,
    $output = false,
    $parent = [],
    $whatIfFileExists = 'overwrite'
) {
    if (empty($userInfo) || empty($courseInfo)) {
        return false;
    }

    $userId = $userInfo['user_id'];

    // Open dir
    $handle = opendir($folderPath);

    if (is_dir($folderPath)) {
        // Run trough
        while ($file = readdir($handle)) {
            if ($file == '.' || $file == '..') {
                continue;
            }

            $parentPath = '';
            if (!empty($parent) && isset($parent['path'])) {
                $parentPath = $parent['path'];
                if ($parentPath == '/') {
                    $parentPath = '';
                }
            }

            $completePath = $parentPath.'/'.$file;
            $sysFolderPath = $folderPath.'/'.$file;

            // Is directory?
            if (is_dir($sysFolderPath)) {
                $folderExists = DocumentManager::folderExists(
                    $completePath,
                    $courseInfo,
                    $sessionId,
                    $groupId
                );

                if ($folderExists === true) {
                    switch ($whatIfFileExists) {
                        case 'overwrite':
                            $documentId = DocumentManager::get_document_id($courseInfo, $completePath, $sessionId);
                            if ($documentId) {
                                $newFolderData = DocumentManager::get_document_data_by_id(
                                    $documentId,
                                    $courseInfo['code'],
                                    false,
                                    $sessionId
                                );
                            }
                            break;
                        case 'rename':
                            $newFolderData = create_unexisting_directory(
                                $courseInfo,
                                $userId,
                                $sessionId,
                                $groupId,
                                null,
                                $base_work_dir,
                                $completePath,
                                null,
                                null,
                                true
                            );
                            break;
                        case 'nothing':
                            if ($output) {
                                $documentId = DocumentManager::get_document_id($courseInfo, $completePath, $sessionId);
                                if ($documentId) {
                                    $folderData = DocumentManager::get_document_data_by_id(
                                        $documentId,
                                        $courseInfo['code'],
                                        false,
                                        $sessionId
                                    );
                                    Display::addFlash(
                                        Display::return_message(
                                            $folderData['path'].' '.get_lang(' already exists.'),
                                            'warning'
                                        )
                                    );
                                }
                            }
                            continue 2;
                            break;
                    }
                } else {
                    $newFolderData = create_unexisting_directory(
                        $courseInfo,
                        $userId,
                        $sessionId,
                        $groupId,
                        null,
                        $base_work_dir,
                        $completePath,
                        null,
                        null,
                        false
                    );
                }

                // Recursive
                add_all_documents_in_folder_to_database(
                    $courseInfo,
                    $userInfo,
                    $base_work_dir,
                    $sysFolderPath,
                    $sessionId,
                    $groupId,
                    $output,
                    $newFolderData,
                    $whatIfFileExists
                );
            } else {
                // Rename
                $uploadedFile = [
                    'name' => $file,
                    'tmp_name' => $sysFolderPath,
                    'size' => filesize($sysFolderPath),
                    'type' => null,
                    'from_file' => true,
                    'move_file' => true,
                ];

                handle_uploaded_document(
                    $courseInfo,
                    $uploadedFile,
                    $base_work_dir,
                    $parentPath,
                    $userId,
                    $groupId,
                    null,
                    0,
                    $whatIfFileExists,
                    $output,
                    false,
                    null,
                    $sessionId
                );
            }
        }
    }
}
