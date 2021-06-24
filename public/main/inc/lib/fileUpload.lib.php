<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CDocument;
use Symfony\Component\HttpFoundation\File\UploadedFile;

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
    return preg_replace('/\.(phar.?|php.?|phtml.?)(\.){0,1}.*$/i', '.phps', $file_name);
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
 * @return true if upload succeeded
 */
function process_uploaded_file($uploadedFileData, $show_output = true)
{
    $uploadedFile = [];
    if ($uploadedFileData instanceof UploadedFile) {
        $uploadedFile['error'] = $uploadedFileData->getError();
        $uploadedFile['tmp_name'] = $uploadedFileData->getPathname();
        $uploadedFile['size'] = $uploadedFileData->getSize();
    } else {
        $uploadedFile = $uploadedFileData;
    }

    // Checking the error code sent with the file upload.
    if (isset($uploadedFile['error'])) {
        switch ($uploadedFile['error']) {
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

    if (!file_exists($uploadedFile['tmp_name'])) {
        // No file was uploaded.
        if ($show_output) {
            Display::addFlash(Display::return_message(get_lang('The file upload has failed.'), 'error'));
        }

        return false;
    }

    if (file_exists($uploadedFile['tmp_name'])) {
        $filesize = filesize($uploadedFile['tmp_name']);
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
        if (!DocumentManager::enough_space($uploadedFile['size'], $max_filled_space)) {
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

function cleanZipFilesNoRename($p_event, &$p_header)
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
    $modifiedStoredFileName = clean_up_path($originalStoredFileName, false);
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
 * @param bool   $replaceName
 *
 * @return string
 *
 * @see disable_dangerous_file()
 * @see api_replace_dangerous_char()
 */
function clean_up_path($path, $replaceName = true)
{
    // Split the path in folders and files
    $path_array = explode('/', $path);
    // Clean up every folder and filename in the path
    foreach ($path_array as $key => &$val) {
        // We don't want to lose the dots in ././folder/file (cfr. zipfile)
        if ('.' != $val) {
            if ($replaceName) {
                $val = api_replace_dangerous_char($val);
            }
            $val = disable_dangerous_file($val);
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
    if ('/' == substr($filename, -1)) {
        return 1; // Authorize directories
    }
    $blacklist = api_get_setting('upload_extensions_list_type');
    if ('whitelist' != $blacklist) { // if = blacklist
        $extensions = explode(';', strtolower(api_get_setting('upload_extensions_blacklist')));

        $skip = api_get_setting('upload_extensions_skip');
        $ext = strrchr($filename, '.');
        $ext = substr($ext, 1);
        if (empty($ext)) {
            return 1; // We're in blacklist mode, so accept empty extensions
        }
        if (in_array(strtolower($ext), $extensions)) {
            if ('true' == $skip) {
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
            if ('true' == $skip) {
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
    $parentInfo = null
) {
    $course_id = $_course['real_id'];
    $session_id = (int) $session_id;

    $parentId = 0;
    if (!empty($parentInfo)) {
        if (is_array($parentInfo) && isset($parentInfo['iid'])) {
            $parentId = $parentInfo['iid'];
        }
        if ($parentInfo instanceof CDocument) {
            $parentId = $parentInfo->getIid();
        }
    }

    $document = DocumentManager::addDocument(
        $_course,
        $desired_dir_name,
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

    $folderExists = DocumentManager::folderExists(
        $desired_dir_name,
        $_course,
        $session_id,
        $to_group_id
    );

    if (true === $folderExists) {
        if ($generateNewNameIfExists) {
            $counter = 1;
            while (1) {
                $folderExists = DocumentManager::folderExists(
                    $desired_dir_name.'_'.$counter,
                    $_course,
                    $session_id,
                    $to_group_id
                );

                if (false === $folderExists) {
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

    if (null == $title) {
        $title = basename($desired_dir_name);
    }

    // Check if pathname already exists inside document table
    $table = Database::get_course_table(TABLE_DOCUMENT);
    $sql = "SELECT iid, path FROM $table
            WHERE
                c_id = $course_id AND
                path = '".Database::escape_string($systemFolderName)."'";
    $rs = Database::query($sql);

    $parentId = 0;
    if (!empty($parentInfo) && isset($parentInfo['iid'])) {
        $parentId = $parentInfo['iid'];
    }

    if (0 == Database::num_rows($rs)) {
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
            $document['iid'],
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
