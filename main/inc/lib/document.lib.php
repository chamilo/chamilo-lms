<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Resource\ResourceFile;
use Chamilo\CoreBundle\Entity\Resource\ResourceLink;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Entity\CGroupInfo;
use Chamilo\UserBundle\Entity\User;
use ChamiloSession as Session;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 *  Class DocumentManager
 *  This is the document library for Chamilo.
 *  It is / will be used to provide a service layer to all document-using tools.
 *  and eliminate code duplication fro group documents, scorm documents, main documents.
 *  Include/require it in your code to use its functionality.
 */
class DocumentManager
{
    /**
     * Construct.
     */
    private function __construct()
    {
    }

    /**
     * @param string $course_code
     *
     * @return int the document folder quota for the current course in bytes
     *             or the default quota
     */
    public static function get_course_quota($course_code = null)
    {
        if (empty($course_code)) {
            $course_info = api_get_course_info();
        } else {
            $course_info = api_get_course_info($course_code);
        }

        $course_quota = null;
        if (empty($course_info)) {
            return DEFAULT_DOCUMENT_QUOTA;
        } else {
            $course_quota = $course_info['disk_quota'];
        }
        if (is_null($course_quota) || empty($course_quota)) {
            // Course table entry for quota was null, then use default value
            $course_quota = DEFAULT_DOCUMENT_QUOTA;
        }

        return $course_quota;
    }

    /**
     * Get the content type of a file by checking the extension
     * We could use mime_content_type() with php-versions > 4.3,
     * but this doesn't work as it should on Windows installations.
     *
     * @param string $filename or boolean TRUE to return complete array
     *
     * @author ? first version
     * @author Bert Vanderkimpen
     *
     * @return string
     */
    public static function file_get_mime_type($filename)
    {
        // All MIME types in an array (from 1.6, this is the authorative source)
        // Please, keep this alphabetical if you add something to this list!
        $mimeTypes = [
            'ai' => 'application/postscript',
            'aif' => 'audio/x-aiff',
            'aifc' => 'audio/x-aiff',
            'aiff' => 'audio/x-aiff',
            'asf' => 'video/x-ms-asf',
            'asc' => 'text/plain',
            'au' => 'audio/basic',
            'avi' => 'video/x-msvideo',
            'bcpio' => 'application/x-bcpio',
            'bin' => 'application/octet-stream',
            'bmp' => 'image/bmp',
            'cdf' => 'application/x-netcdf',
            'class' => 'application/octet-stream',
            'cpio' => 'application/x-cpio',
            'cpt' => 'application/mac-compactpro',
            'csh' => 'application/x-csh',
            'css' => 'text/css',
            'dcr' => 'application/x-director',
            'dir' => 'application/x-director',
            'djv' => 'image/vnd.djvu',
            'djvu' => 'image/vnd.djvu',
            'dll' => 'application/octet-stream',
            'dmg' => 'application/x-diskcopy',
            'dms' => 'application/octet-stream',
            'doc' => 'application/msword',
            'docm' => 'application/vnd.ms-word.document.macroEnabled.12',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'dotx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.template',
            'dvi' => 'application/x-dvi',
            'dwg' => 'application/vnd.dwg',
            'dwf' => 'application/vnd.dwf',
            'dxf' => 'application/vnd.dxf',
            'dxr' => 'application/x-director',
            'eps' => 'application/postscript',
            'epub' => 'application/epub+zip',
            'etx' => 'text/x-setext',
            'exe' => 'application/octet-stream',
            'ez' => 'application/andrew-inset',
            'flv' => 'video/flv',
            'gif' => 'image/gif',
            'gtar' => 'application/x-gtar',
            'gz' => 'application/x-gzip',
            'hdf' => 'application/x-hdf',
            'hqx' => 'application/mac-binhex40',
            'htm' => 'text/html',
            'html' => 'text/html',
            'ice' => 'x-conference-xcooltalk',
            'ief' => 'image/ief',
            'iges' => 'model/iges',
            'igs' => 'model/iges',
            'jar' => 'application/java-archiver',
            'jpe' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'jpg' => 'image/jpeg',
            'js' => 'application/x-javascript',
            'kar' => 'audio/midi',
            'lam' => 'application/vnd.ms-excel.addin.macroEnabled.12',
            'latex' => 'application/x-latex',
            'lha' => 'application/octet-stream',
            'log' => 'text/plain',
            'lzh' => 'application/octet-stream',
            'm1a' => 'audio/mpeg',
            'm2a' => 'audio/mpeg',
            'm3u' => 'audio/x-mpegurl',
            'man' => 'application/x-troff-man',
            'me' => 'application/x-troff-me',
            'mesh' => 'model/mesh',
            'mid' => 'audio/midi',
            'midi' => 'audio/midi',
            'mov' => 'video/quicktime',
            'movie' => 'video/x-sgi-movie',
            'mp2' => 'audio/mpeg',
            'mp3' => 'audio/mpeg',
            'mp4' => 'video/mp4',
            'mpa' => 'audio/mpeg',
            'mpe' => 'video/mpeg',
            'mpeg' => 'video/mpeg',
            'mpg' => 'video/mpeg',
            'mpga' => 'audio/mpeg',
            'ms' => 'application/x-troff-ms',
            'msh' => 'model/mesh',
            'mxu' => 'video/vnd.mpegurl',
            'nc' => 'application/x-netcdf',
            'oda' => 'application/oda',
            'oga' => 'audio/ogg',
            'ogg' => 'application/ogg',
            'ogx' => 'application/ogg',
            'ogv' => 'video/ogg',
            'pbm' => 'image/x-portable-bitmap',
            'pct' => 'image/pict',
            'pdb' => 'chemical/x-pdb',
            'pdf' => 'application/pdf',
            'pgm' => 'image/x-portable-graymap',
            'pgn' => 'application/x-chess-pgn',
            'pict' => 'image/pict',
            'png' => 'image/png',
            'pnm' => 'image/x-portable-anymap',
            'potm' => 'application/vnd.ms-powerpoint.template.macroEnabled.12',
            'potx' => 'application/vnd.openxmlformats-officedocument.presentationml.template',
            'pps' => 'application/vnd.ms-powerpoint',
            'ppam' => 'application/vnd.ms-powerpoint.addin.macroEnabled.12',
            'ppsm' => 'application/vnd.ms-powerpoint.slideshow.macroEnabled.12',
            'ppsx' => 'application/vnd.openxmlformats-officedocument.presentationml.slideshow',
            'pptm' => 'application/vnd.ms-powerpoint.presentation.macroEnabled.12',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'ppm' => 'image/x-portable-pixmap',
            'ppt' => 'application/vnd.ms-powerpoint',
            'ps' => 'application/postscript',
            'qt' => 'video/quicktime',
            'ra' => 'audio/x-realaudio',
            'ram' => 'audio/x-pn-realaudio',
            'rar' => 'image/x-rar-compressed',
            'ras' => 'image/x-cmu-raster',
            'rgb' => 'image/x-rgb',
            'rm' => 'audio/x-pn-realaudio',
            'roff' => 'application/x-troff',
            'rpm' => 'audio/x-pn-realaudio-plugin',
            'rtf' => 'text/rtf',
            'rtx' => 'text/richtext',
            'sgm' => 'text/sgml',
            'sgml' => 'text/sgml',
            'sh' => 'application/x-sh',
            'shar' => 'application/x-shar',
            'silo' => 'model/mesh',
            'sib' => 'application/X-Sibelius-Score',
            'sit' => 'application/x-stuffit',
            'skd' => 'application/x-koan',
            'skm' => 'application/x-koan',
            'skp' => 'application/x-koan',
            'skt' => 'application/x-koan',
            'smi' => 'application/smil',
            'smil' => 'application/smil',
            'snd' => 'audio/basic',
            'so' => 'application/octet-stream',
            'spl' => 'application/x-futuresplash',
            'src' => 'application/x-wais-source',
            'sv4cpio' => 'application/x-sv4cpio',
            'sv4crc' => 'application/x-sv4crc',
            'svf' => 'application/vnd.svf',
            'svg' => 'image/svg+xml',
            //'svgz' => 'image/svg+xml',
            'swf' => 'application/x-shockwave-flash',
            'sxc' => 'application/vnd.sun.xml.calc',
            'sxi' => 'application/vnd.sun.xml.impress',
            'sxw' => 'application/vnd.sun.xml.writer',
            't' => 'application/x-troff',
            'tar' => 'application/x-tar',
            'tcl' => 'application/x-tcl',
            'tex' => 'application/x-tex',
            'texi' => 'application/x-texinfo',
            'texinfo' => 'application/x-texinfo',
            'tga' => 'image/x-targa',
            'tif' => 'image/tif',
            'tiff' => 'image/tiff',
            'tr' => 'application/x-troff',
            'tsv' => 'text/tab-seperated-values',
            'txt' => 'text/plain',
            'ustar' => 'application/x-ustar',
            'vcd' => 'application/x-cdlink',
            'vrml' => 'model/vrml',
            'wav' => 'audio/x-wav',
            'wbmp' => 'image/vnd.wap.wbmp',
            'wbxml' => 'application/vnd.wap.wbxml',
            'webp' => 'image/webp',
            'wml' => 'text/vnd.wap.wml',
            'wmlc' => 'application/vnd.wap.wmlc',
            'wmls' => 'text/vnd.wap.wmlscript',
            'wmlsc' => 'application/vnd.wap.wmlscriptc',
            'wma' => 'audio/x-ms-wma',
            'wmv' => 'video/x-ms-wmv',
            'wrl' => 'model/vrml',
            'xbm' => 'image/x-xbitmap',
            'xht' => 'application/xhtml+xml',
            'xhtml' => 'application/xhtml+xml',
            'xls' => 'application/vnd.ms-excel',
            'xlsb' => 'application/vnd.ms-excel.sheet.binary.macroEnabled.12',
            'xlsm' => 'application/vnd.ms-excel.sheet.macroEnabled.12',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'xltm' => 'application/vnd.ms-excel.template.macroEnabled.12',
            'xltx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.template',
            'xml' => 'text/xml',
            'xpm' => 'image/x-xpixmap',
            'xsl' => 'text/xml',
            'xwd' => 'image/x-windowdump',
            'xyz' => 'chemical/x-xyz',
            'zip' => 'application/zip',
        ];

        if ($filename === true) {
            return $mimeTypes;
        }

        // Get the extension of the file
        $extension = explode('.', $filename);

        // $filename will be an array if a . was found
        if (is_array($extension)) {
            $extension = strtolower($extension[count($extension) - 1]);
        } else {
            //file without extension
            $extension = 'empty';
        }

        //if the extension is found, return the content type
        if (isset($mimeTypes[$extension])) {
            return $mimeTypes[$extension];
        }

        return 'application/octet-stream';
    }

    /**
     * This function smart streams a file to the client using HTTP headers.
     *
     * @param string $fullFilename The full path of the file to be sent
     * @param string $filename     The name of the file as shown to the client
     * @param string $contentType  The MIME type of the file
     *
     * @return bool false if file doesn't exist, true if stream succeeded
     */
    public static function smartReadFile($fullFilename, $filename, $contentType = 'application/octet-stream')
    {
        if (!file_exists($fullFilename)) {
            header("HTTP/1.1 404 Not Found");

            return false;
        }

        $size = filesize($fullFilename);
        $time = date('r', filemtime($fullFilename));

        $fm = @fopen($fullFilename, 'rb');
        if (!$fm) {
            header("HTTP/1.1 505 Internal server error");

            return false;
        }

        $begin = 0;
        $end = $size - 1;

        if (isset($_SERVER['HTTP_RANGE'])) {
            if (preg_match('/bytes=\h*(\d+)-(\d*)[\D.*]?/i', $_SERVER['HTTP_RANGE'], $matches)) {
                $begin = intval($matches[1]);
                if (!empty($matches[2])) {
                    $end = intval($matches[2]);
                }
            }
        }

        if (isset($_SERVER['HTTP_RANGE'])) {
            header('HTTP/1.1 206 Partial Content');
        } else {
            header('HTTP/1.1 200 OK');
        }

        header("Content-Type: $contentType");
        header('Cache-Control: public, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Accept-Ranges: bytes');
        header('Content-Length:'.(($end - $begin) + 1));
        if (isset($_SERVER['HTTP_RANGE'])) {
            header("Content-Range: bytes $begin-$end/$size");
        }
        header("Content-Disposition: inline; filename=$filename");
        header("Content-Transfer-Encoding: binary");
        header("Last-Modified: $time");

        $cur = $begin;
        fseek($fm, $begin, 0);

        while (!feof($fm) && $cur <= $end && (connection_status() == 0)) {
            echo fread($fm, min(1024 * 16, ($end - $cur) + 1));
            $cur += 1024 * 16;
        }
    }

    /**
     * This function streams a file to the client.
     *
     * @param string $full_file_name
     * @param bool   $forced
     * @param string $name
     * @param bool   $fixLinksHttpToHttps change file content from http to https
     *
     * @return false if file doesn't exist, true if stream succeeded
     */
    public static function file_send_for_download(
        $full_file_name,
        $forced = false,
        $name = '',
        $fixLinksHttpToHttps = false
    ) {
        session_write_close(); //we do not need write access to session anymore
        if (!is_file($full_file_name)) {
            return false;
        }
        $filename = $name == '' ? basename($full_file_name) : api_replace_dangerous_char($name);
        $len = filesize($full_file_name);
        // Fixing error when file name contains a ","
        $filename = str_replace(',', '', $filename);
        $sendFileHeaders = api_get_configuration_value('enable_x_sendfile_headers');

        // Allows chrome to make videos and audios seekable
        header('Accept-Ranges: bytes');

        if ($forced) {
            // Force the browser to save the file instead of opening it
            if (isset($sendFileHeaders) &&
                !empty($sendFileHeaders)) {
                header("X-Sendfile: $filename");
            }

            header('Content-type: application/octet-stream');
            header('Content-length: '.$len);
            if (preg_match("/MSIE 5.5/", $_SERVER['HTTP_USER_AGENT'])) {
                header('Content-Disposition: filename= '.$filename);
            } else {
                header('Content-Disposition: attachment; filename= '.$filename);
            }
            if (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE')) {
                header('Pragma: ');
                header('Cache-Control: ');
                header('Cache-Control: public'); // IE cannot download from sessions without a cache
            }
            header('Content-Description: '.$filename);
            header('Content-Transfer-Encoding: binary');

            if (function_exists('ob_end_clean') && ob_get_length()) {
                // Use ob_end_clean() to avoid weird buffering situations
                // where file is sent broken/incomplete for download
                ob_end_clean();
            }

            $res = fopen($full_file_name, 'r');
            fpassthru($res);

            return true;
        } else {
            // no forced download, just let the browser decide what to do according to the mimetype
            $lpFixedEncoding = api_get_setting('lp.fixed_encoding') === 'true';

            // Commented to let courses content to be cached in order to improve performance:
            //header('Expires: Wed, 01 Jan 1990 00:00:00 GMT');
            //header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');

            // Commented to avoid double caching declaration when playing with IE and HTTPS
            //header('Cache-Control: no-cache, must-revalidate');
            //header('Pragma: no-cache');

            $contentType = self::file_get_mime_type($filename);

            switch ($contentType) {
                case 'text/html':
                    if (isset($lpFixedEncoding) && $lpFixedEncoding === 'true') {
                        $contentType .= '; charset=UTF-8';
                    } else {
                        $encoding = @api_detect_encoding_html(file_get_contents($full_file_name));
                        if (!empty($encoding)) {
                            $contentType .= '; charset='.$encoding;
                        }
                    }
                    break;
                case 'text/plain':
                    if (isset($lpFixedEncoding) && $lpFixedEncoding === 'true') {
                        $contentType .= '; charset=UTF-8';
                    } else {
                        $encoding = @api_detect_encoding(strip_tags(file_get_contents($full_file_name)));
                        if (!empty($encoding)) {
                            $contentType .= '; charset='.$encoding;
                        }
                    }
                    break;
                case 'video/mp4':
                case 'audio/mpeg':
                case 'audio/mp4':
                case 'audio/ogg':
                case 'audio/webm':
                case 'audio/wav':
                case 'video/ogg':
                case 'video/webm':
                    self::smartReadFile($full_file_name, $filename, $contentType);
                    exit;
                case 'application/vnd.dwg':
                case 'application/vnd.dwf':
                    header('Content-type: application/octet-stream');
                    break;
            }

            header('Content-type: '.$contentType);
            header('Content-Length: '.$len);
            $userAgent = strtolower($_SERVER['HTTP_USER_AGENT']);

            if (strpos($userAgent, 'msie')) {
                header('Content-Disposition: ; filename= '.$filename);
            } else {
                //header('Content-Disposition: inline');
                header('Content-Disposition: inline;');
            }

            if ($fixLinksHttpToHttps) {
                $content = file_get_contents($full_file_name);
                $content = str_replace(
                    ['http%3A%2F%2F', 'http://'],
                    ['https%3A%2F%2F', 'https://'],
                    $content
                );
                echo $content;
            } else {
                if (function_exists('ob_end_clean') && ob_get_length()) {
                    // Use ob_end_clean() to avoid weird buffering situations
                    // where file is sent broken/incomplete for download
                    ob_end_clean();
                }

                readfile($full_file_name);
            }

            return true;
        }
    }

    /**
     * Session folder filters.
     *
     * @param string $path
     * @param int    $sessionId
     *
     * @return string|null
     */
    public static function getSessionFolderFilters($path, $sessionId)
    {
        $sessionId = (int) $sessionId;
        $condition = null;

        if (!empty($sessionId)) {
            // Chat folder filter
            if ($path == '/chat_files') {
                $condition .= " AND (docs.session_id = '$sessionId') ";
            }
            // share_folder filter
            $condition .= " AND docs.path != '/shared_folder' ";
        }

        return $condition;
    }

    /**
     * Fetches all document data for the given user/group.
     *
     * @param array     $courseInfo
     * @param string    $path
     * @param int       $toGroupId       iid
     * @param int       $toUserId
     * @param bool      $canSeeInvisible
     * @param bool      $search
     * @param int       $sessionId
     * @param User|null $currentUser
     *
     * @return array with all document data
     */
    public static function getAllDocumentData(
        $courseInfo,
        $path = '/',
        $toGroupId = 0,
        $toUserId = null,
        $canSeeInvisible = false,
        $search = false,
        $sessionId = 0,
        User $currentUser = null
    ) {
        if (empty($courseInfo)) {
            return [];
        }

        $tblDocument = Database::get_course_table(TABLE_DOCUMENT);
        $currentUser = $currentUser ?: api_get_current_user();

        $userGroupFilter = '';
        if (!is_null($toUserId)) {
            $toUserId = (int) $toUserId;
            $userGroupFilter = "last.to_user_id = $toUserId";
            if (empty($toUserId)) {
                $userGroupFilter = ' (last.to_user_id = 0 OR last.to_user_id IS NULL) ';
            }
        } else {
            $toGroupId = (int) $toGroupId;
            $userGroupFilter = "last.to_group_id = $toGroupId";
            if (empty($toGroupId)) {
                $userGroupFilter = '( last.to_group_id = 0 OR last.to_group_id IS NULL) ';
            }
        }

        // Escape underscores in the path so they don't act as a wildcard
        $originalPath = $path;
        $path = str_replace('_', '\_', $path);

        $visibilityBit = ' <> 2';

        // The given path will not end with a slash, unless it's the root '/'
        // so no root -> add slash
        $addedSlash = $path == '/' ? '' : '/';

        // Condition for the session
        $sessionId = $sessionId ?: api_get_session_id();
        $conditionSession = " AND (last.session_id = '$sessionId' OR (last.session_id = '0' OR last.session_id IS NULL) )";
        $conditionSession .= self::getSessionFolderFilters($originalPath, $sessionId);

        $sharedCondition = null;
        if ($originalPath == '/shared_folder') {
            $students = CourseManager::get_user_list_from_course_code($courseInfo['code'], $sessionId);
            if (!empty($students)) {
                $conditionList = [];
                foreach ($students as $studentInfo) {
                    $conditionList[] = '/shared_folder/sf_user_'.$studentInfo['user_id'];
                }
                $sharedCondition .= ' AND docs.path IN ("'.implode('","', $conditionList).'")';
            }
        }

        $sql = "SELECT
                    docs.id,
                    docs.filetype,
                    docs.path,
                    docs.title,
                    docs.comment,
                    docs.size,
                    docs.readonly,
                    docs.session_id,
                    creator_id,
                    visibility,
                    n.updated_at,
                    n.created_at,
                    n.creator_id                                     
                FROM resource_node AS n
                INNER JOIN $tblDocument AS docs
                ON (docs.resource_node_id = n.id)
                INNER JOIN resource_link l
                ON (l.resource_node_id = n.id)                
                WHERE
                    docs.c_id = {$courseInfo['real_id']} AND                    
                    docs.path LIKE '".Database::escape_string($path.$addedSlash.'%')."' AND
                    docs.path NOT LIKE '".Database::escape_string($path.$addedSlash.'%/%')."' AND
                    docs.path NOT LIKE '%_DELETED_%' AND
                    l.visibility NOT IN ('".ResourceLink::VISIBILITY_DELETED."')
                    $sharedCondition               
                ";
        //$userGroupFilter AND
        //$conditionSession
        $result = Database::query($sql);

        $documentData = [];
        $isAllowedToEdit = api_is_allowed_to_edit(null, true);
        $isCoach = api_is_coach();
        if ($result !== false && Database::num_rows($result) != 0) {
            $rows = [];

            $hideInvisibleDocuments = api_get_configuration_value('hide_invisible_course_documents_in_sessions');

            while ($row = Database::fetch_array($result, 'ASSOC')) {
                if (isset($rows[$row['id']])) {
                    continue;
                }

                // If we are in session and hide_invisible_course_documents_in_sessions is enabled
                // Then we avoid the documents that have visibility in session but that they come from a base course
                if ($hideInvisibleDocuments && $sessionId) {
                    if ($row['item_property_session_id'] == $sessionId && empty($row['session_id'])) {
                        continue;
                    }
                }

                if (self::isBasicCourseFolder($row['path'], $sessionId)) {
                    $basicCourseDocumentsContent = self::getAllDocumentData(
                        $courseInfo,
                        $row['path']
                    );

                    if (empty($basicCourseDocumentsContent)) {
                        continue;
                    }
                }

                $rows[$row['id']] = $row;
            }

            // If we are in session and hide_invisible_course_documents_in_sessions is enabled
            // Or if we are students
            // Then don't list the invisible or deleted documents
            if (($sessionId && $hideInvisibleDocuments) || (!$isCoach && !$isAllowedToEdit)) {
                $rows = array_filter($rows, function ($row) {
                    if (in_array(
                        $row['visibility'],
                        [
                            ResourceLink::VISIBILITY_DELETED,
                            ResourceLink::VISIBILITY_DRAFT,
                        ]
                    )) {
                        return false;
                    }

                    return true;
                });
            }

            foreach ($rows as $row) {
                if ($row['filetype'] == 'file' &&
                    pathinfo($row['path'], PATHINFO_EXTENSION) == 'html'
                ) {
                    // Templates management
                    $tblTemplate = Database::get_main_table(TABLE_MAIN_TEMPLATES);
                    $sql = "SELECT id FROM $tblTemplate
                            WHERE
                                c_id = '".$courseInfo['real_id']."' AND
                                user_id = '".$currentUser->getId()."' AND
                                ref_doc = '".$row['id']."'";
                    $templateResult = Database::query($sql);
                    $row['is_template'] = (Database::num_rows($templateResult) > 0) ? 1 : 0;
                }
                $row['basename'] = basename($row['path']);
                // Just filling $document_data.
                $documentData[$row['id']] = $row;
            }

            // Only for the student we filter the results see BT#1652
            if (!$isCoach && !$isAllowedToEdit) {
                // Checking parents visibility.
                $finalDocumentData = [];
                foreach ($documentData as $row) {
                    $isVisible = self::check_visibility_tree(
                        $row['id'],
                        $courseInfo,
                        $sessionId,
                        $currentUser->getId(),
                        $toGroupId
                    );
                    if ($isVisible) {
                        $finalDocumentData[$row['id']] = $row;
                    }
                }
            } else {
                $finalDocumentData = $documentData;
            }

            return $finalDocumentData;
        } else {
            return [];
        }
    }

    /**
     * Gets the paths of all folders in a course
     * can show all folders (except for the deleted ones) or only visible ones.
     *
     * @param array  $courseInfo
     * @param int    $groupIid          iid
     * @param bool   $can_see_invisible
     * @param bool   $getInvisibleList
     * @param string $path              current path
     *
     * @return array with paths
     */
    public static function get_all_document_folders(
        $courseInfo,
        $groupIid = 0,
        $can_see_invisible = false,
        $getInvisibleList = false,
        $path = ''
    ) {
        if (empty($courseInfo)) {
            return [];
        }

        $TABLE_DOCUMENT = Database::get_course_table(TABLE_DOCUMENT);
        $groupIid = (int) $groupIid;
        $courseId = $courseInfo['real_id'];
        $sessionId = api_get_session_id();

        $folders = [];
        $students = CourseManager::get_user_list_from_course_code(
            $courseInfo['code'],
            api_get_session_id()
        );

        $conditionList = [];
        if (!empty($students)) {
            foreach ($students as $studentId => $studentInfo) {
                $conditionList[] = '/shared_folder/sf_user_'.$studentInfo['user_id'];
            }
        }

        $groupCondition = " l.group_id = $groupIid";
        if (empty($groupIid)) {
            $groupCondition = ' (l.group_id = 0 OR l.group_id IS NULL)';
        }

        $show_users_condition = '';
        if (api_get_setting('show_users_folders') === 'false') {
            $show_users_condition = " AND docs.path NOT LIKE '%shared_folder%'";
        }

        if ($can_see_invisible) {
            $sessionId = $sessionId ?: api_get_session_id();
            $condition_session = " AND (l.session_id = '$sessionId' OR (l.session_id = '0' OR l.session_id IS NULL) )";
            $condition_session .= self::getSessionFolderFilters($path, $sessionId);

            $sql = "SELECT DISTINCT docs.id, docs.path
                    FROM resource_node AS n
                    INNER JOIN $TABLE_DOCUMENT  AS docs
                    ON (docs.resource_node_id = n.id)
                    INNER JOIN resource_link l
                    ON (l.resource_node_id = n.id)
                    WHERE                      
                        docs.c_id = $courseId AND
                        docs.filetype = 'folder' AND
                        $groupCondition AND
                        docs.path NOT LIKE '%shared_folder%' AND
                        docs.path NOT LIKE '%_DELETED_%' AND
                        l.visibility NOT IN ('".ResourceLink::VISIBILITY_DELETED."')                           
                        $condition_session ";

            if ($groupIid != 0) {
                $sql .= " AND docs.path NOT LIKE '%shared_folder%' ";
            } else {
                $sql .= $show_users_condition;
            }

            $result = Database::query($sql);
            if ($result && Database::num_rows($result) != 0) {
                while ($row = Database::fetch_array($result, 'ASSOC')) {
                    if (self::is_folder_to_avoid($row['path'])) {
                        continue;
                    }

                    if (strpos($row['path'], '/shared_folder/') !== false) {
                        if (!in_array($row['path'], $conditionList)) {
                            continue;
                        }
                    }

                    $folders[$row['id']] = $row['path'];
                }

                if (!empty($folders)) {
                    natsort($folders);
                }

                return $folders;
            } else {
                return false;
            }
        } else {
            // No invisible folders
            // Condition for the session
            $condition_session = api_get_session_condition(
                $sessionId,
                true,
                false,
                'docs.session_id'
            );

            $visibilityCondition = 'l.visibility = 1';
            $fileType = "docs.filetype = 'folder' AND";
            if ($getInvisibleList) {
                $visibilityCondition = 'l.visibility = 0';
                $fileType = '';
            }

            //get visible folders
            $sql = "SELECT DISTINCT docs.id, docs.path
                    FROM resource_node AS n
                    INNER JOIN $TABLE_DOCUMENT  AS docs
                    ON (docs.resource_node_id = n.id)
                    INNER JOIN resource_link l
                    ON (l.resource_node_id = n.id)
                    WHERE
                        $fileType                        
                        $groupCondition AND
                        $visibilityCondition
                        $show_users_condition
                        $condition_session AND                        
                        docs.c_id = $courseId ";
            $result = Database::query($sql);
            $visibleFolders = [];
            while ($row = Database::fetch_array($result, 'ASSOC')) {
                $visibleFolders[$row['id']] = $row['path'];
            }

            if ($getInvisibleList) {
                return $visibleFolders;
            }

            // get invisible folders
            $sql = "SELECT DISTINCT docs.id, docs.path
                    FROM resource_node AS n
                    INNER JOIN $TABLE_DOCUMENT  AS docs
                    ON (docs.resource_node_id = n.id)
                    INNER JOIN resource_link l
                    ON (l.resource_node_id = n.id)
                    WHERE                        
                        docs.filetype = 'folder' AND                        
                        $groupCondition AND                        
                        l.visibility IN ('".ResourceLink::VISIBILITY_PENDING."') 
                        $condition_session AND                        
                        docs.c_id = $courseId ";
            $result = Database::query($sql);
            $invisibleFolders = [];
            while ($row = Database::fetch_array($result, 'ASSOC')) {
                //get visible folders in the invisible ones -> they are invisible too
                $sql = "SELECT DISTINCT docs.id, docs.path
                        FROM resource_node AS n
                        INNER JOIN $TABLE_DOCUMENT  AS docs
                        ON (docs.resource_node_id = n.id)
                        INNER JOIN resource_link l
                        ON (l.resource_node_id = n.id)
                        WHERE                            
                            docs.path LIKE '".Database::escape_string($row['path'].'/%')."' AND
                            docs.filetype = 'folder' AND                            
                            $groupCondition AND
                            l.visibility NOT IN ('".ResourceLink::VISIBILITY_DELETED."') 
                            $condition_session AND                            
                            docs.c_id = $courseId ";
                $folder_in_invisible_result = Database::query($sql);
                while ($folders_in_invisible_folder = Database::fetch_array($folder_in_invisible_result, 'ASSOC')) {
                    $invisibleFolders[$folders_in_invisible_folder['id']] = $folders_in_invisible_folder['path'];
                }
            }

            // If both results are arrays -> //calculate the difference between the 2 arrays -> only visible folders are left :)
            if (is_array($visibleFolders) && is_array($invisibleFolders)) {
                $folders = array_diff($visibleFolders, $invisibleFolders);
                natsort($folders);

                return $folders;
            }

            if (is_array($visibleFolders)) {
                natsort($visibleFolders);

                return $visibleFolders;
            }

            // no visible folders found
            return false;
        }
    }

    /**
     * This check if a document has the readonly property checked, then see if the user
     * is the owner of this file, if all this is true then return true.
     *
     * @param array  $_course
     * @param int    $user_id     id of the current user
     * @param string $file        path stored in the database (if not defined, $documentId must be used)
     * @param int    $document_id in case you don't have the file path ,
     *                            insert the id of the file here and leave $file in blank ''
     * @param bool   $to_delete
     * @param int    $sessionId
     *
     * @return bool true/false
     * */
    public static function check_readonly(
        $_course,
        $user_id,
        $file = null,
        $document_id = 0,
        $to_delete = false,
        $sessionId = null
    ) {
        $sessionId = (int) $sessionId;
        if (empty($sessionId)) {
            $sessionId = api_get_session_id();
        }
        $document_id = (int) $document_id;
        if (empty($document_id)) {
            $document_id = self::get_document_id($_course, $file, $sessionId);
        }

        $TABLE_PROPERTY = Database::get_course_table(TABLE_ITEM_PROPERTY);
        $TABLE_DOCUMENT = Database::get_course_table(TABLE_DOCUMENT);
        $course_id = $_course['real_id'];

        if ($to_delete) {
            if (self::isFolder($_course, $document_id)) {
                if (!empty($file)) {
                    $path = Database::escape_string($file);
                    // Check
                    $sql = "SELECT td.id, readonly, tp.insert_user_id
                            FROM $TABLE_DOCUMENT td 
                            INNER JOIN $TABLE_PROPERTY tp
                            ON (td.c_id = tp.c_id AND tp.ref= td.id)
                            WHERE
                                td.c_id = $course_id AND
                                tp.c_id = $course_id AND
                                td.session_id = $sessionId AND                                
                                (path='".$path."' OR path LIKE BINARY '".$path."/%' ) ";
                    // Get all id's of documents that are deleted
                    $what_to_check_result = Database::query($sql);

                    if ($what_to_check_result && Database::num_rows($what_to_check_result) != 0) {
                        // file with readonly set to 1 exist?
                        $readonly_set = false;
                        while ($row = Database::fetch_array($what_to_check_result)) {
                            //query to delete from item_property table
                            if ($row['readonly'] == 1) {
                                if (!($row['insert_user_id'] == $user_id)) {
                                    $readonly_set = true;
                                    break;
                                }
                            }
                        }

                        if ($readonly_set) {
                            return true;
                        }
                    }
                }

                return false;
            }
        }

        if (!empty($document_id)) {
            $sql = "SELECT a.insert_user_id, b.readonly
                   FROM $TABLE_PROPERTY a 
                   INNER JOIN $TABLE_DOCUMENT b
                   ON (a.c_id = b.c_id AND a.ref= b.id)
                   WHERE
            			a.c_id = $course_id AND
                        b.c_id = $course_id AND
            			a.ref = $document_id 
                    LIMIT 1";
            $result = Database::query($sql);
            $doc_details = Database::fetch_array($result, 'ASSOC');

            if ($doc_details['readonly'] == 1) {
                return !($doc_details['insert_user_id'] == $user_id || api_is_platform_admin());
            }
        }

        return false;
    }

    /**
     * This check if a document is a folder or not.
     *
     * @param array $_course
     * @param int   $id      document id
     *
     * @return bool true/false
     * */
    public static function isFolder($_course, $id)
    {
        $table = Database::get_course_table(TABLE_DOCUMENT);
        if (empty($_course)) {
            return false;
        }
        $course_id = $_course['real_id'];
        $id = (int) $id;
        $sql = "SELECT filetype FROM $table
                WHERE c_id = $course_id AND id= $id";
        $result = Database::fetch_array(Database::query($sql), 'ASSOC');

        return $result['filetype'] === 'folder';
    }

    /**
     * @param int   $document_id
     * @param array $course_info
     * @param int   $session_id
     * @param bool  $remove_content_from_db
     */
    public static function deleteDocumentFromDb(
        $document_id,
        $course_info = [],
        $session_id = 0,
        $remove_content_from_db = false
    ) {
        // Deleting from the DB
        $user_id = api_get_user_id();
        $document_id = intval($document_id);

        if (empty($course_info)) {
            $course_info = api_get_course_info();
        }

        if (empty($session_id)) {
            $session_id = api_get_session_id();
        }
        // Soft DB delete
        /*api_item_property_update(
            $course_info,
            TOOL_DOCUMENT,
            $document_id,
            'delete',
            $user_id,
            null,
            null,
            null,
            null,
            $session_id
        );*/
        self::delete_document_from_search_engine($course_info['code'], $document_id);
        self::unsetDocumentAsTemplate($document_id, $course_info['real_id'], $user_id);

        // Hard DB delete
        if ($remove_content_from_db) {
            $repo = Container::getDocumentRepository();
            /** @var CDocument $document */
            $document = $repo->find($document_id);
            $repo->softDelete($document);

            return true;

            /*$sql = "DELETE FROM $TABLE_ITEMPROPERTY
                    WHERE
                        c_id = {$course_info['real_id']} AND
                        ref = ".$document_id." AND
                        tool='".TOOL_DOCUMENT."'";
            Database::query($sql);

            $sql = "DELETE FROM $TABLE_DOCUMENT
                    WHERE c_id = {$course_info['real_id']} AND id = ".$document_id;
            Database::query($sql);*/
        }
    }

    /**
     * This deletes a document by changing visibility to 2, renaming it to filename_DELETED_#id
     * Files/folders that are inside a deleted folder get visibility 2.
     *
     * @param array  $_course
     * @param string $path          Path stored in the database
     * @param string $base_work_dir Path to the documents folder (if not defined, $documentId must be used)
     * @param int    $sessionId     The ID of the session, if any
     * @param int    $documentId    The document id, if available
     * @param int    $groupId       iid
     *
     * @return bool true/false
     *
     * @todo now only files/folders in a folder get visibility 2, we should rename them too.
     * @todo We should be able to get rid of this later when using only documentId (check further usage)
     */
    public static function delete_document(
        $_course,
        $path = null,
        $base_work_dir = null,
        $sessionId = null,
        $documentId = null,
        $groupId = 0
    ) {
        $groupId = (int) $groupId;
        if (empty($groupId)) {
            $groupId = api_get_group_id();
        }

        $sessionId = (int) $sessionId;
        if (empty($sessionId)) {
            $sessionId = api_get_session_id();
        }

        $course_id = $_course['real_id'];

        if (empty($course_id)) {
            return false;
        }

        if (empty($documentId)) {
            $documentId = self::get_document_id($_course, $path, $sessionId);
            $docInfo = self::get_document_data_by_id(
                $documentId,
                $_course['code'],
                false,
                $sessionId
            );
            $path = $docInfo['path'];
        } else {
            $docInfo = self::get_document_data_by_id(
                $documentId,
                $_course['code'],
                false,
                $sessionId
            );
            if (empty($docInfo)) {
                return false;
            }
            $path = $docInfo['path'];
        }

        $documentId = (int) $documentId;

        if (empty($path) || empty($docInfo) || empty($documentId)) {
            return false;
        }

        $repo = Container::getDocumentRepository();
        /** @var CDocument $document */
        $document = $repo->find($docInfo['iid']);
        //$repo->softDelete($document);
        $repo->getEntityManager()->remove($document);
        $repo->getEntityManager()->flush();

        return true;
    }

    /**
     * Removes documents from search engine database.
     *
     * @param string $course_id   Course code
     * @param int    $document_id Document id to delete
     */
    public static function delete_document_from_search_engine($course_id, $document_id)
    {
        // remove from search engine if enabled
        if (api_get_setting('search_enabled') === 'true') {
            $tbl_se_ref = Database::get_main_table(TABLE_MAIN_SEARCH_ENGINE_REF);
            $sql = 'SELECT * FROM %s WHERE course_code=\'%s\' AND tool_id=\'%s\' AND ref_id_high_level=%s LIMIT 1';
            $sql = sprintf($sql, $tbl_se_ref, $course_id, TOOL_DOCUMENT, $document_id);
            $res = Database::query($sql);
            if (Database::num_rows($res) > 0) {
                $row2 = Database::fetch_array($res);
                $di = new ChamiloIndexer();
                $di->remove_document($row2['search_did']);
            }
            $sql = 'DELETE FROM %s WHERE course_code=\'%s\' AND tool_id=\'%s\' AND ref_id_high_level=%s LIMIT 1';
            $sql = sprintf($sql, $tbl_se_ref, $course_id, TOOL_DOCUMENT, $document_id);
            Database::query($sql);

            // remove terms from db
            require_once api_get_path(LIBRARY_PATH).'specific_fields_manager.lib.php';
            delete_all_values_for_item($course_id, TOOL_DOCUMENT, $document_id);
        }
    }

    /**
     * Gets the id of a document with a given path.
     *
     * @param array  $courseInfo
     * @param string $path
     * @param int    $sessionId
     *
     * @return int id of document / false if no doc found
     */
    public static function get_document_id($courseInfo, $path, $sessionId = 0)
    {
        $table = Database::get_course_table(TABLE_DOCUMENT);
        $courseId = $courseInfo['real_id'];

        $sessionId = empty($sessionId) ? api_get_session_id() : (int) $sessionId;
        $sessionCondition = api_get_session_condition($sessionId, true);

        $path = Database::escape_string($path);
        if (!empty($courseId) && !empty($path)) {
            $sql = "SELECT id FROM $table
                    WHERE
                        c_id = $courseId AND
                        path LIKE BINARY '$path'
                        $sessionCondition
                    LIMIT 1";

            $result = Database::query($sql);
            if (Database::num_rows($result)) {
                $row = Database::fetch_array($result);

                return (int) $row['id'];
            }
        }

        return false;
    }

    /**
     * Gets the document data with a given id.
     *
     * @param int    $id            Document Id (id field in c_document table)
     * @param string $course_code   Course code
     * @param bool   $load_parents  load folder parents
     * @param int    $session_id    The session ID,
     *                              0 if requires context *out of* session, and null to use global context
     * @param bool   $ignoreDeleted
     *
     * @return array document content
     */
    public static function get_document_data_by_id(
        $id,
        $course_code,
        $load_parents = false,
        $session_id = null,
        $ignoreDeleted = false
    ) {
        $course_info = api_get_course_info($course_code);
        $course_id = $course_info['real_id'];

        if (empty($course_info)) {
            return false;
        }

        $session_id = empty($session_id) ? api_get_session_id() : (int) $session_id;
        $groupId = api_get_group_id();

        $www = api_get_path(WEB_COURSE_PATH).$course_info['path'].'/document';
        $TABLE_DOCUMENT = Database::get_course_table(TABLE_DOCUMENT);
        $id = (int) $id;
        $sessionCondition = api_get_session_condition($session_id, true, true);

        $sql = "SELECT * FROM $TABLE_DOCUMENT
                WHERE c_id = $course_id $sessionCondition AND id = $id";

        if ($ignoreDeleted) {
            $sql .= " AND path NOT LIKE '%_DELETED_%' ";
        }

        $result = Database::query($sql);
        $courseParam = '&cidReq='.$course_code.'&id='.$id.'&id_session='.$session_id.'&gidReq='.$groupId;
        if ($result && Database::num_rows($result) == 1) {
            $row = Database::fetch_array($result, 'ASSOC');
            //@todo need to clarify the name of the URLs not nice right now
            $url_path = urlencode($row['path']);
            $path = str_replace('%2F', '/', $url_path);
            $pathinfo = pathinfo($row['path']);

            $row['url'] = api_get_path(WEB_CODE_PATH).'document/showinframes.php?id='.$id.$courseParam;
            $row['document_url'] = api_get_path(WEB_CODE_PATH).'document/document.php?id='.$id.$courseParam;
            $row['absolute_path'] = api_get_path(SYS_COURSE_PATH).$course_info['path'].'/document'.$row['path'];
            $row['absolute_path_from_document'] = '/document'.$row['path'];
            $row['absolute_parent_path'] = api_get_path(SYS_COURSE_PATH).$course_info['path'].'/document'.$pathinfo['dirname'].'/';
            $row['direct_url'] = $www.$path;
            $row['basename'] = basename($row['path']);

            if (dirname($row['path']) == '.') {
                $row['parent_id'] = '0';
            } else {
                $row['parent_id'] = self::get_document_id($course_info, dirname($row['path']), $session_id);
                if (empty($row['parent_id'])) {
                    // Try one more with session id = 0
                    $row['parent_id'] = self::get_document_id($course_info, dirname($row['path']), 0);
                }
            }
            $parents = [];

            //Use to generate parents (needed for the breadcrumb)
            //@todo sorry but this for is here because there's not a parent_id in the document table so we parsed the path!!
            if ($load_parents) {
                $dir_array = explode('/', $row['path']);
                $dir_array = array_filter($dir_array);
                $array_len = count($dir_array) + 1;
                $real_dir = '';

                for ($i = 1; $i < $array_len; $i++) {
                    $real_dir .= '/'.(isset($dir_array[$i]) ? $dir_array[$i] : '');
                    $parent_id = self::get_document_id($course_info, $real_dir);
                    if ($session_id != 0 && empty($parent_id)) {
                        $parent_id = self::get_document_id($course_info, $real_dir, 0);
                    }
                    if (!empty($parent_id)) {
                        $sub_document_data = self::get_document_data_by_id(
                            $parent_id,
                            $course_code,
                            false,
                            $session_id
                        );
                        if ($session_id != 0 and !$sub_document_data) {
                            $sub_document_data = self::get_document_data_by_id(
                                $parent_id,
                                $course_code,
                                false,
                                0
                            );
                        }
                        //@todo add visibility here
                        $parents[] = $sub_document_data;
                    }
                }
            }
            $row['parents'] = $parents;

            return $row;
        }

        return false;
    }

    /**
     * Allow to set a specific document as a new template for CKeditor
     * for a particular user in a particular course.
     *
     * @param string $title
     * @param string $description
     * @param int    $document_id_for_template the document id
     * @param int    $courseId
     * @param int    $user_id
     * @param string $image
     *
     * @return bool
     */
    public static function setDocumentAsTemplate(
        $title,
        $description,
        $document_id_for_template,
        $courseId,
        $user_id,
        $image
    ) {
        // Database table definition
        $table_template = Database::get_main_table(TABLE_MAIN_TEMPLATES);
        $params = [
            'title' => $title,
            'description' => $description,
            'c_id' => $courseId,
            'user_id' => $user_id,
            'ref_doc' => $document_id_for_template,
            'image' => $image,
        ];
        Database::insert($table_template, $params);

        return true;
    }

    /**
     * Unset a document as template.
     *
     * @param int $document_id
     * @param int $courseId
     * @param int $user_id
     */
    public static function unsetDocumentAsTemplate(
        $document_id,
        $courseId,
        $user_id
    ) {
        $table_template = Database::get_main_table(TABLE_MAIN_TEMPLATES);
        $courseId = (int) $courseId;
        $user_id = (int) $user_id;
        $document_id = (int) $document_id;

        $sql = 'SELECT id FROM '.$table_template.'
                WHERE
                    c_id = "'.$courseId.'" AND
                    user_id = "'.$user_id.'" AND
                    ref_doc = "'.$document_id.'"';
        $result = Database::query($sql);
        $template_id = Database::result($result, 0, 0);

        my_delete(api_get_path(SYS_CODE_PATH).'upload/template_thumbnails/'.$template_id.'.jpg');

        $sql = 'DELETE FROM '.$table_template.'
                WHERE
                    c_id ="'.$courseId.'" AND
                    user_id="'.$user_id.'" AND
                    ref_doc="'.$document_id.'"';

        Database::query($sql);
    }

    /**
     * Return true if the documentpath have visibility=1 as
     * item_property (you should use the is_visible_by_id).
     *
     * @param string $doc_path the relative complete path of the document
     * @param array  $course   the _course array info of the document's course
     * @param int
     * @param string
     *
     * @return bool
     */
    public static function is_visible(
        $doc_path,
        $course,
        $session_id = 0,
        $file_type = 'file'
    ) {
        $docTable = Database::get_course_table(TABLE_DOCUMENT);

        $course_id = $course['real_id'];
        // note the extra / at the end of doc_path to match every path in
        // the document table that is part of the document path

        $session_id = intval($session_id);
        $condition = " AND d.session_id IN  ('$session_id', '0') ";
        // The " d.filetype='file' " let the user see a file even if the folder is hidden see #2198

        /*
          When using hotpotatoes files, a new html files are generated
          in the hotpotatoes folder to display the test.
          The genuine html file is copied to math4.htm(user_id).t.html
          Images files are not copied, and keep same name.
          To check the html file visibility, we don't have to check file math4.htm(user_id).t.html but file math4.htm
          In this case, we have to remove (user_id).t.html to check the visibility of the file
          For images, we just check the path of the image file.

          Exemple of hotpotatoes folder :
          A.jpg
          maths4-consigne.jpg
          maths4.htm
          maths4.htm1.t.html
          maths4.htm52.t.html
          maths4.htm654.t.html
          omega.jpg
          theta.jpg
         */

        if (strpos($doc_path, 'HotPotatoes_files') && preg_match("/\.t\.html$/", $doc_path)) {
            $doc_path = substr($doc_path, 0, strlen($doc_path) - 7 - strlen(api_get_user_id()));
        }

        if (!in_array($file_type, ['file', 'folder'])) {
            $file_type = 'file';
        }
        $doc_path = Database::escape_string($doc_path).'/';

        $sql = "SELECT iid
                FROM $docTable d
        		WHERE
        		    d.c_id  = $course_id AND 
        		    $condition AND
        			filetype = '$file_type' AND
        			locate(concat(path,'/'), '$doc_path')=1
                ";

        $result = Database::query($sql);
        $is_visible = false;
        if (Database::num_rows($result) > 0) {
            $row = Database::fetch_array($result, 'ASSOC');

            $em = Database::getManager();

            $repo = $em->getRepository('ChamiloCourseBundle:CDocument');
            /** @var \Chamilo\CourseBundle\Entity\CDocument $document */
            $document = $repo->find($row['iid']);
            if ($document->getVisibility() === ResourceLink::VISIBILITY_PUBLISHED) {
                $is_visible = api_is_allowed_in_course() || api_is_platform_admin();
            }
        }

        /* improved protection of documents viewable directly through the url:
            incorporates the same protections of the course at the url of
            documents:
            access allowed for the whole world Open, access allowed for
            users registered on the platform Private access, document accessible
            only to course members (see the Users list), Completely closed;
            the document is only accessible to the course admin and
            teaching assistants.*/
        //return $_SESSION ['is_allowed_in_course'] || api_is_platform_admin();
        return $is_visible;
    }

    /**
     * Return true if user can see a file.
     *
     * @param   int     document id
     * @param   array   course info
     * @param   int
     * @param   int
     * @param bool
     *
     * @return bool
     */
    public static function is_visible_by_id(
        $doc_id,
        $course_info,
        $session_id,
        $user_id,
        $admins_can_see_everything = true,
        $userIsSubscribed = null
    ) {
        $user_in_course = false;

        //1. Checking the course array
        if (empty($course_info)) {
            $course_info = api_get_course_info();
            if (empty($course_info)) {
                return false;
            }
        }

        $doc_id = (int) $doc_id;
        $session_id = (int) $session_id;
        // 2. Course and Session visibility are handle in local.inc.php/global.inc.php
        // 3. Checking if user exist in course/session
        if ($session_id == 0) {
            if (is_null($userIsSubscribed)) {
                $userIsSubscribed = CourseManager::is_user_subscribed_in_course(
                    $user_id,
                    $course_info['code']
                );
            }

            if ($userIsSubscribed === true || api_is_platform_admin()) {
                $user_in_course = true;
            }

            // Check if course is open then we can consider that the student is registered to the course
            if (isset($course_info) &&
                in_array(
                    $course_info['visibility'],
                    [COURSE_VISIBILITY_OPEN_PLATFORM, COURSE_VISIBILITY_OPEN_WORLD]
                )
            ) {
                $user_in_course = true;
            }
        } else {
            $user_status = SessionManager::get_user_status_in_course_session(
                $user_id,
                $course_info['real_id'],
                $session_id
            );

            if (in_array($user_status, ['0', '2', '6'])) {
                //is true if is an student, course session teacher or coach
                $user_in_course = true;
            }

            if (api_is_platform_admin()) {
                $user_in_course = true;
            }
        }

        $em = Database::getManager();

        // 4. Checking document visibility (i'm repeating the code in order to be more clear when reading ) - jm
        if ($user_in_course) {
            $repo = $em->getRepository('ChamiloCourseBundle:CDocument');
            /** @var \Chamilo\CourseBundle\Entity\CDocument $document */
            $document = $repo->find($doc_id);

            if ($document->isVisible()) {
                return true;
            }

            return false;

            // 4.1 Checking document visibility for a Course
            if ($session_id == 0) {
                $link = $document->getCourseSessionResourceLink();

                if ($link && $link->getVisibility() == ResourceLink::VISIBILITY_PUBLISHED) {
                    return true;
                }

                return false;

                $item_info = api_get_item_property_info(
                    $course_info['real_id'],
                    'document',
                    $doc_id,
                    0
                );

                if (isset($item_info['visibility'])) {
                    // True for admins if document exists
                    if ($admins_can_see_everything && api_is_platform_admin()) {
                        return true;
                    }
                    if ($item_info['visibility'] == 1) {
                        return true;
                    }
                }
            } else {
                // 4.2 Checking document visibility for a Course in a Session
                $item_info = api_get_item_property_info(
                    $course_info['real_id'],
                    'document',
                    $doc_id,
                    0
                );

                $item_info_in_session = api_get_item_property_info(
                    $course_info['real_id'],
                    'document',
                    $doc_id,
                    $session_id
                );

                // True for admins if document exists
                if (isset($item_info['visibility'])) {
                    if ($admins_can_see_everything && api_is_platform_admin()) {
                        return true;
                    }
                }

                if (isset($item_info_in_session['visibility'])) {
                    if ($item_info_in_session['visibility'] == 1) {
                        return true;
                    }
                } else {
                    if ($item_info['visibility'] == 1) {
                        return true;
                    }
                }
            }
        } elseif ($admins_can_see_everything && api_is_platform_admin()) {
            return true;
        }

        return false;
    }

    /**
     * Allow attach a certificate to a course.
     *
     * @todo move to certificate.lib.php
     *
     * @param int $courseId
     * @param int $document_id
     * @param int $session_id
     */
    public static function attach_gradebook_certificate($courseId, $document_id, $session_id = 0)
    {
        $tbl_category = Database::get_main_table(TABLE_MAIN_GRADEBOOK_CATEGORY);
        $session_id = intval($session_id);
        $courseId = (int) $courseId;
        if (empty($session_id)) {
            $session_id = api_get_session_id();
        }

        if (empty($session_id)) {
            $sql_session = 'AND (session_id = 0 OR isnull(session_id)) ';
        } elseif ($session_id > 0) {
            $sql_session = 'AND session_id='.$session_id;
        } else {
            $sql_session = '';
        }
        $sql = 'UPDATE '.$tbl_category.' SET document_id="'.intval($document_id).'"
                WHERE c_id ="'.$courseId.'" '.$sql_session;
        Database::query($sql);
    }

    /**
     * get the document id of default certificate.
     *
     * @todo move to certificate.lib.php
     *
     * @param int $courseId
     * @param int $session_id
     *
     * @return int The default certificate id
     */
    public static function get_default_certificate_id($courseId, $session_id = 0)
    {
        $tbl_category = Database::get_main_table(TABLE_MAIN_GRADEBOOK_CATEGORY);
        $session_id = (int) $session_id;
        $courseId = (int) $courseId;
        if (empty($session_id)) {
            $session_id = api_get_session_id();
        }

        if (empty($session_id)) {
            $sql_session = 'AND (session_id = 0 OR isnull(session_id)) ';
        } elseif ($session_id > 0) {
            $sql_session = 'AND session_id='.$session_id;
        } else {
            $sql_session = '';
        }

        $sql = 'SELECT document_id FROM '.$tbl_category.'
                WHERE c_id ="'.$courseId.'" '.$sql_session;

        $rs = Database::query($sql);
        $num = Database::num_rows($rs);
        if ($num == 0) {
            return null;
        }
        $row = Database::fetch_array($rs);

        return $row['document_id'];
    }

    /**
     * Allow replace user info in file html.
     *
     * @param int   $user_id
     * @param array $courseInfo
     * @param int   $sessionId
     * @param bool  $is_preview
     *
     * @return array
     */
    public static function replace_user_info_into_html(
        $user_id,
        $courseInfo,
        $sessionId,
        $is_preview = false
    ) {
        $user_id = intval($user_id);
        $tbl_document = Database::get_course_table(TABLE_DOCUMENT);
        $course_id = $courseInfo['real_id'];

        $document_id = self::get_default_certificate_id(
            $course_id,
            $sessionId
        );

        $my_content_html = null;
        if ($document_id) {
            $sql = "SELECT path FROM $tbl_document
                    WHERE c_id = $course_id AND id = $document_id";
            $rs = Database::query($sql);
            $new_content = '';
            $all_user_info = [];
            if (Database::num_rows($rs)) {
                $row = Database::fetch_array($rs);
                $filepath = api_get_path(SYS_COURSE_PATH).$courseInfo['path'].'/document'.$row['path'];
                if (is_file($filepath)) {
                    $my_content_html = file_get_contents($filepath);
                }
                $all_user_info = self::get_all_info_to_certificate(
                    $user_id,
                    $courseInfo,
                    $is_preview
                );

                $info_to_be_replaced_in_content_html = $all_user_info[0];
                $info_to_replace_in_content_html = $all_user_info[1];
                $new_content = str_replace(
                    $info_to_be_replaced_in_content_html,
                    $info_to_replace_in_content_html,
                    $my_content_html
                );
            }

            return [
                'content' => $new_content,
                'variables' => $all_user_info,
            ];
        }

        return [];
    }

    /**
     * Return all content to replace and all content to be replace.
     *
     * @param int  $user_id
     * @param int  $course_id
     * @param bool $is_preview
     *
     * @return array
     */
    public static function get_all_info_to_certificate($user_id, $course_id, $is_preview = false)
    {
        $info_list = [];
        $user_id = intval($user_id);
        $course_info = api_get_course_info($course_id);

        // Portal info
        $organization_name = api_get_setting('Institution');
        $portal_name = api_get_setting('siteName');

        // Extra user data information
        $extra_user_info_data = UserManager::get_extra_user_data(
            $user_id,
            false,
            false,
            false,
            true
        );

        // get extra fields
        $extraField = new ExtraField('user');
        $extraFields = $extraField->get_all(['filter = ? AND visible_to_self = ?' => [1, 1]]);

        // Student information
        $user_info = api_get_user_info($user_id);
        $first_name = $user_info['firstname'];
        $last_name = $user_info['lastname'];
        $username = $user_info['username'];
        $official_code = $user_info['official_code'];

        // Teacher information
        $info_teacher_id = UserManager::get_user_id_of_course_admin_or_session_admin($course_info);
        $teacher_info = api_get_user_info($info_teacher_id);
        $teacher_first_name = $teacher_info['firstname'];
        $teacher_last_name = $teacher_info['lastname'];

        // info gradebook certificate
        $info_grade_certificate = UserManager::get_info_gradebook_certificate($course_id, $user_id);
        $date_certificate = $info_grade_certificate['created_at'];
        $date_long_certificate = '';

        $date_no_time = api_convert_and_format_date(api_get_utc_datetime(), DATE_FORMAT_LONG_NO_DAY);
        if (!empty($date_certificate)) {
            $date_long_certificate = api_convert_and_format_date($date_certificate);
            $date_no_time = api_convert_and_format_date($date_certificate, DATE_FORMAT_LONG_NO_DAY);
        }

        if ($is_preview) {
            $date_long_certificate = api_convert_and_format_date(api_get_utc_datetime());
            $date_no_time = api_convert_and_format_date(api_get_utc_datetime(), DATE_FORMAT_LONG_NO_DAY);
        }

        $url = api_get_path(WEB_PATH).'certificates/index.php?id='.$info_grade_certificate['id'];
        $externalStyleFile = api_get_path(SYS_CSS_PATH).'themes/'.api_get_visual_theme().'/certificate.css';
        $externalStyle = '';
        if (is_file($externalStyleFile)) {
            $externalStyle = file_get_contents($externalStyleFile);
        }

        // Replace content
        $info_to_replace_in_content_html = [
            $first_name,
            $last_name,
            $username,
            $organization_name,
            $portal_name,
            $teacher_first_name,
            $teacher_last_name,
            $official_code,
            $date_long_certificate,
            $date_no_time,
            $course_id,
            $course_info['name'],
            $info_grade_certificate['grade'],
            $url,
            '<a href="'.$url.'" target="_blank">'.get_lang('Online link to certificate').'</a>',
            '((certificate_barcode))',
            $externalStyle,
        ];

        $tags = [
            '((user_firstname))',
            '((user_lastname))',
            '((user_username))',
            '((gradebook_institution))',
            '((gradebook_sitename))',
            '((teacher_firstname))',
            '((teacher_lastname))',
            '((official_code))',
            '((date_certificate))',
            '((date_certificate_no_time))',
            '((course_code))',
            '((course_title))',
            '((gradebook_grade))',
            '((certificate_link))',
            '((certificate_link_html))',
            '((certificate_barcode))',
            '((external_style))',
        ];

        if (!empty($extraFields)) {
            foreach ($extraFields as $extraField) {
                $valueExtra = isset($extra_user_info_data[$extraField['variable']]) ? $extra_user_info_data[$extraField['variable']] : '';
                $tags[] = '(('.strtolower($extraField['variable']).'))';
                $info_to_replace_in_content_html[] = $valueExtra;
            }
        }

        $info_list[] = $tags;
        $info_list[] = $info_to_replace_in_content_html;

        return $info_list;
    }

    /**
     * Remove default certificate.
     *
     * @param int $course_id              The course code
     * @param int $default_certificate_id The document id of the default certificate
     */
    public static function remove_attach_certificate($course_id, $default_certificate_id)
    {
        if (empty($default_certificate_id)) {
            return false;
        }

        $default_certificate = self::get_default_certificate_id($course_id);
        if ((int) $default_certificate == (int) $default_certificate_id) {
            $tbl_category = Database::get_main_table(TABLE_MAIN_GRADEBOOK_CATEGORY);
            $session_id = api_get_session_id();
            if ($session_id == 0 || is_null($session_id)) {
                $sql_session = 'AND (session_id='.intval($session_id).' OR isnull(session_id)) ';
            } elseif ($session_id > 0) {
                $sql_session = 'AND session_id='.intval($session_id);
            } else {
                $sql_session = '';
            }

            $sql = 'UPDATE '.$tbl_category.' SET document_id = null
                    WHERE
                        c_id = "'.Database::escape_string($course_id).'" AND
                        document_id="'.$default_certificate_id.'" '.$sql_session;
            Database::query($sql);
        }
    }

    /**
     * Create directory certificate.
     *
     * @param array $courseInfo
     */
    public static function create_directory_certificate_in_course($courseInfo)
    {
        if (!empty($courseInfo)) {
            $to_group_id = 0;
            $to_user_id = null;
            $course_dir = $courseInfo['path']."/document/";
            $sys_course_path = api_get_path(SYS_COURSE_PATH);
            $base_work_dir = $sys_course_path.$course_dir;
            $dir_name = '/certificates';
            $post_dir_name = get_lang('Certificates');
            $visibility_command = 'invisible';

            $id = self::get_document_id_of_directory_certificate();

            if (empty($id)) {
                create_unexisting_directory(
                    $courseInfo,
                    api_get_user_id(),
                    api_get_session_id(),
                    $to_group_id,
                    $to_user_id,
                    $base_work_dir,
                    $dir_name,
                    $post_dir_name,
                    null,
                    false,
                    false
                );

                $id = self::get_document_id_of_directory_certificate();

                if (empty($id)) {
                    self::addDocument(
                        $courseInfo,
                        $dir_name,
                        'folder',
                        0,
                        $post_dir_name,
                        null,
                        0,
                        true,
                        $to_group_id,
                        0,
                        0,
                        false
                    );
                }
            }
        }
    }

    /**
     * Get the document id of the directory certificate.
     *
     * @return int The document id of the directory certificate
     *
     * @todo move to certificate.lib.php
     */
    public static function get_document_id_of_directory_certificate()
    {
        $tbl_document = Database::get_course_table(TABLE_DOCUMENT);
        $course_id = api_get_course_int_id();
        $sql = "SELECT id FROM $tbl_document 
                WHERE c_id = $course_id AND path='/certificates' ";
        $rs = Database::query($sql);
        $row = Database::fetch_array($rs);

        return $row['id'];
    }

    /**
     * Check if a directory given is for certificate.
     *
     * @todo move to certificate.lib.php
     *
     * @param string $dir path of directory
     *
     * @return bool true if is a certificate or false otherwise
     */
    public static function is_certificate_mode($dir)
    {
        // I'm in the certification module?
        $is_certificate_mode = false;
        $is_certificate_array = explode('/', $dir);
        array_shift($is_certificate_array);
        if (isset($is_certificate_array[0]) && $is_certificate_array[0] == 'certificates') {
            $is_certificate_mode = true;
        }

        return $is_certificate_mode || (isset($_GET['certificate']) && $_GET['certificate'] === 'true');
    }

    /**
     * Gets the list of included resources as a list of absolute or relative paths from a html file or string html
     * This allows for a better SCORM export or replace urls inside content html from copy course
     * The list will generally include pictures, flash objects, java applets, or any other
     * stuff included in the source of the current item. The current item is expected
     * to be an HTML file or string html. If it is not, then the function will return and empty list.
     *
     * @param    string  source html (content or path)
     * @param    bool    is file or string html
     * @param    string    type (one of the app tools) - optional (otherwise takes the current item's type)
     * @param    int        level of recursivity we're in
     *
     * @return array List of file paths. An additional field containing 'local' or 'remote' helps determine
     *               if the file should be copied into the zip or just linked
     */
    public static function get_resources_from_source_html(
        $source_html,
        $is_file = false,
        $type = null,
        $recursivity = 1
    ) {
        $max = 5;
        $attributes = [];
        $wanted_attributes = [
            'src',
            'url',
            '@import',
            'href',
            'value',
            'flashvars',
            'poster',
        ];
        $explode_attributes = ['flashvars' => 'file'];
        $abs_path = '';

        if ($recursivity > $max) {
            return [];
        }

        if (!isset($type)) {
            $type = TOOL_DOCUMENT;
        }

        if (!$is_file) {
            $attributes = self::parse_HTML_attributes(
                $source_html,
                $wanted_attributes,
                $explode_attributes
            );
        } else {
            if (is_file($source_html)) {
                $abs_path = $source_html;
                //for now, read the whole file in one go (that's gonna be a problem when the file is too big)
                $info = pathinfo($abs_path);
                $ext = $info['extension'];
                switch (strtolower($ext)) {
                    case 'html':
                    case 'htm':
                    case 'shtml':
                    case 'css':
                        $file_content = file_get_contents($abs_path);
                        // get an array of attributes from the HTML source
                        $attributes = self::parse_HTML_attributes(
                            $file_content,
                            $wanted_attributes,
                            $explode_attributes
                        );
                        break;
                    default:
                        break;
                }
            } else {
                return [];
            }
        }

        $files_list = [];
        switch ($type) {
            case TOOL_DOCUMENT:
            case TOOL_QUIZ:
            case 'sco':
                foreach ($wanted_attributes as $attr) {
                    if (isset($attributes[$attr])) {
                        //find which kind of path these are (local or remote)
                        $sources = $attributes[$attr];
                        foreach ($sources as $source) {
                            //skip what is obviously not a resource
                            if (strpos($source, '+this.')) {
                                continue; //javascript code - will still work unaltered
                            }
                            if (strpos($source, '.') === false) {
                                continue; //no dot, should not be an external file anyway
                            }
                            if (strpos($source, 'mailto:')) {
                                continue; //mailto link
                            }
                            if (strpos($source, ';') && !strpos($source, '&amp;')) {
                                continue; //avoid code - that should help
                            }

                            if ($attr == 'value') {
                                if (strpos($source, 'mp3file')) {
                                    $files_list[] = [
                                        substr($source, 0, strpos($source, '.swf') + 4),
                                        'local',
                                        'abs',
                                    ];
                                    $mp3file = substr($source, strpos($source, 'mp3file=') + 8);
                                    if (substr($mp3file, 0, 1) == '/') {
                                        $files_list[] = [$mp3file, 'local', 'abs'];
                                    } else {
                                        $files_list[] = [$mp3file, 'local', 'rel'];
                                    }
                                } elseif (strpos($source, 'flv=') === 0) {
                                    $source = substr($source, 4);
                                    if (strpos($source, '&') > 0) {
                                        $source = substr($source, 0, strpos($source, '&'));
                                    }
                                    if (strpos($source, '://') > 0) {
                                        if (strpos($source, api_get_path(WEB_PATH)) !== false) {
                                            //we found the current portal url
                                            $files_list[] = [$source, 'local', 'url'];
                                        } else {
                                            //we didn't find any trace of current portal
                                            $files_list[] = [$source, 'remote', 'url'];
                                        }
                                    } else {
                                        $files_list[] = [$source, 'local', 'abs'];
                                    }
                                    /* skipping anything else to avoid two entries
                                    (while the others can have sub-files in their url, flv's can't)*/
                                    continue;
                                }
                            }
                            if (strpos($source, '://') > 0) {
                                //cut at '?' in a URL with params
                                if (strpos($source, '?') > 0) {
                                    $second_part = substr($source, strpos($source, '?'));
                                    if (strpos($second_part, '://') > 0) {
                                        //if the second part of the url contains a url too, treat the second one before cutting
                                        $pos1 = strpos($second_part, '=');
                                        $pos2 = strpos($second_part, '&');
                                        $second_part = substr($second_part, $pos1 + 1, $pos2 - ($pos1 + 1));
                                        if (strpos($second_part, api_get_path(WEB_PATH)) !== false) {
                                            //we found the current portal url
                                            $files_list[] = [$second_part, 'local', 'url'];
                                            $in_files_list[] = self::get_resources_from_source_html(
                                                $second_part,
                                                true,
                                                TOOL_DOCUMENT,
                                                $recursivity + 1
                                            );
                                            if (count($in_files_list) > 0) {
                                                $files_list = array_merge($files_list, $in_files_list);
                                            }
                                        } else {
                                            //we didn't find any trace of current portal
                                            $files_list[] = [$second_part, 'remote', 'url'];
                                        }
                                    } elseif (strpos($second_part, '=') > 0) {
                                        if (substr($second_part, 0, 1) === '/') {
                                            //link starts with a /, making it absolute (relative to DocumentRoot)
                                            $files_list[] = [$second_part, 'local', 'abs'];
                                            $in_files_list[] = self::get_resources_from_source_html(
                                                $second_part,
                                                true,
                                                TOOL_DOCUMENT,
                                                $recursivity + 1
                                            );
                                            if (count($in_files_list) > 0) {
                                                $files_list = array_merge($files_list, $in_files_list);
                                            }
                                        } elseif (strstr($second_part, '..') === 0) {
                                            //link is relative but going back in the hierarchy
                                            $files_list[] = [$second_part, 'local', 'rel'];
                                            //$dir = api_get_path(SYS_CODE_PATH);//dirname($abs_path);
                                            //$new_abs_path = realpath($dir.'/'.$second_part);
                                            $dir = '';
                                            if (!empty($abs_path)) {
                                                $dir = dirname($abs_path).'/';
                                            }
                                            $new_abs_path = realpath($dir.$second_part);
                                            $in_files_list[] = self::get_resources_from_source_html(
                                                $new_abs_path,
                                                true,
                                                TOOL_DOCUMENT,
                                                $recursivity + 1
                                            );
                                            if (count($in_files_list) > 0) {
                                                $files_list = array_merge($files_list, $in_files_list);
                                            }
                                        } else {
                                            //no starting '/', making it relative to current document's path
                                            if (substr($second_part, 0, 2) == './') {
                                                $second_part = substr($second_part, 2);
                                            }
                                            $files_list[] = [$second_part, 'local', 'rel'];
                                            $dir = '';
                                            if (!empty($abs_path)) {
                                                $dir = dirname($abs_path).'/';
                                            }
                                            $new_abs_path = realpath($dir.$second_part);
                                            $in_files_list[] = self::get_resources_from_source_html(
                                                $new_abs_path,
                                                true,
                                                TOOL_DOCUMENT,
                                                $recursivity + 1
                                            );
                                            if (count($in_files_list) > 0) {
                                                $files_list = array_merge($files_list, $in_files_list);
                                            }
                                        }
                                    }
                                    //leave that second part behind now
                                    $source = substr($source, 0, strpos($source, '?'));
                                    if (strpos($source, '://') > 0) {
                                        if (strpos($source, api_get_path(WEB_PATH)) !== false) {
                                            //we found the current portal url
                                            $files_list[] = [$source, 'local', 'url'];
                                            $in_files_list[] = self::get_resources_from_source_html(
                                                $source,
                                                true,
                                                TOOL_DOCUMENT,
                                                $recursivity + 1
                                            );
                                            if (count($in_files_list) > 0) {
                                                $files_list = array_merge($files_list, $in_files_list);
                                            }
                                        } else {
                                            //we didn't find any trace of current portal
                                            $files_list[] = [$source, 'remote', 'url'];
                                        }
                                    } else {
                                        //no protocol found, make link local
                                        if (substr($source, 0, 1) === '/') {
                                            //link starts with a /, making it absolute (relative to DocumentRoot)
                                            $files_list[] = [$source, 'local', 'abs'];
                                            $in_files_list[] = self::get_resources_from_source_html(
                                                $source,
                                                true,
                                                TOOL_DOCUMENT,
                                                $recursivity + 1
                                            );
                                            if (count($in_files_list) > 0) {
                                                $files_list = array_merge($files_list, $in_files_list);
                                            }
                                        } elseif (strstr($source, '..') === 0) {
                                            //link is relative but going back in the hierarchy
                                            $files_list[] = [$source, 'local', 'rel'];
                                            $dir = '';
                                            if (!empty($abs_path)) {
                                                $dir = dirname($abs_path).'/';
                                            }
                                            $new_abs_path = realpath($dir.$source);
                                            $in_files_list[] = self::get_resources_from_source_html(
                                                $new_abs_path,
                                                true,
                                                TOOL_DOCUMENT,
                                                $recursivity + 1
                                            );
                                            if (count($in_files_list) > 0) {
                                                $files_list = array_merge($files_list, $in_files_list);
                                            }
                                        } else {
                                            //no starting '/', making it relative to current document's path
                                            if (substr($source, 0, 2) == './') {
                                                $source = substr($source, 2);
                                            }
                                            $files_list[] = [$source, 'local', 'rel'];
                                            $dir = '';
                                            if (!empty($abs_path)) {
                                                $dir = dirname($abs_path).'/';
                                            }
                                            $new_abs_path = realpath($dir.$source);
                                            $in_files_list[] = self::get_resources_from_source_html(
                                                $new_abs_path,
                                                true,
                                                TOOL_DOCUMENT,
                                                $recursivity + 1
                                            );
                                            if (count($in_files_list) > 0) {
                                                $files_list = array_merge($files_list, $in_files_list);
                                            }
                                        }
                                    }
                                }
                                //found some protocol there
                                if (strpos($source, api_get_path(WEB_PATH)) !== false) {
                                    //we found the current portal url
                                    $files_list[] = [$source, 'local', 'url'];
                                    $in_files_list[] = self::get_resources_from_source_html(
                                        $source,
                                        true,
                                        TOOL_DOCUMENT,
                                        $recursivity + 1
                                    );
                                    if (count($in_files_list) > 0) {
                                        $files_list = array_merge($files_list, $in_files_list);
                                    }
                                } else {
                                    //we didn't find any trace of current portal
                                    $files_list[] = [$source, 'remote', 'url'];
                                }
                            } else {
                                //no protocol found, make link local
                                if (substr($source, 0, 1) === '/') {
                                    //link starts with a /, making it absolute (relative to DocumentRoot)
                                    $files_list[] = [$source, 'local', 'abs'];
                                    $in_files_list[] = self::get_resources_from_source_html(
                                        $source,
                                        true,
                                        TOOL_DOCUMENT,
                                        $recursivity + 1
                                    );
                                    if (count($in_files_list) > 0) {
                                        $files_list = array_merge($files_list, $in_files_list);
                                    }
                                } elseif (strpos($source, '..') === 0) {
                                    //link is relative but going back in the hierarchy
                                    $files_list[] = [$source, 'local', 'rel'];
                                    $dir = '';
                                    if (!empty($abs_path)) {
                                        $dir = dirname($abs_path).'/';
                                    }
                                    $new_abs_path = realpath($dir.$source);
                                    $in_files_list[] = self::get_resources_from_source_html(
                                        $new_abs_path,
                                        true,
                                        TOOL_DOCUMENT,
                                        $recursivity + 1
                                    );
                                    if (count($in_files_list) > 0) {
                                        $files_list = array_merge($files_list, $in_files_list);
                                    }
                                } else {
                                    //no starting '/', making it relative to current document's path
                                    if (substr($source, 0, 2) == './') {
                                        $source = substr($source, 2);
                                    }
                                    $files_list[] = [$source, 'local', 'rel'];
                                    $dir = '';
                                    if (!empty($abs_path)) {
                                        $dir = dirname($abs_path).'/';
                                    }
                                    $new_abs_path = realpath($dir.$source);
                                    $in_files_list[] = self::get_resources_from_source_html(
                                        $new_abs_path,
                                        true,
                                        TOOL_DOCUMENT,
                                        $recursivity + 1
                                    );
                                    if (count($in_files_list) > 0) {
                                        $files_list = array_merge($files_list, $in_files_list);
                                    }
                                }
                            }
                        }
                    }
                }
                break;
            default: //ignore
                break;
        }

        $checked_files_list = [];
        $checked_array_list = [];

        if (count($files_list) > 0) {
            foreach ($files_list as $idx => $file) {
                if (!empty($file[0])) {
                    if (!in_array($file[0], $checked_files_list)) {
                        $checked_files_list[] = $files_list[$idx][0];
                        $checked_array_list[] = $files_list[$idx];
                    }
                }
            }
        }

        return $checked_array_list;
    }

    /**
     * Parses the HTML attributes given as string.
     *
     * @param string HTML attribute string
     * @param array List of attributes that we want to get back
     * @param array
     *
     * @return array An associative array of attributes
     *
     * @author Based on a function from the HTML_Common2 PEAR module     *
     */
    public static function parse_HTML_attributes($attrString, $wanted = [], $explode_variables = [])
    {
        $attributes = [];
        $regs = [];
        $reduced = false;
        if (count($wanted) > 0) {
            $reduced = true;
        }
        try {
            //Find all occurences of something that looks like a URL
            // The structure of this regexp is:
            // (find protocol) then
            // (optionally find some kind of space 1 or more times) then
            // find (either an equal sign or a bracket) followed by an optional space
            // followed by some text without quotes (between quotes itself or not)
            // then possible closing brackets if we were in the opening bracket case
            // OR something like @import()
            $res = preg_match_all(
                '/(((([A-Za-z_:])([A-Za-z0-9_:\.-]*))'.
                // '/(((([A-Za-z_:])([A-Za-z0-9_:\.-]|[^\x00-\x7F])*)' . -> seems to be taking too much
                // '/(((([A-Za-z_:])([^\x00-\x7F])*)' . -> takes only last letter of parameter name
                '([ \n\t\r]+)?('.
                // '(=([ \n\t\r]+)?("[^"]+"|\'[^\']+\'|[^ \n\t\r]+))' . -> doesn't restrict close enough to the url itself
                '(=([ \n\t\r]+)?("[^"\)]+"|\'[^\'\)]+\'|[^ \n\t\r\)]+))'.
                '|'.
                // '(\(([ \n\t\r]+)?("[^"]+"|\'[^\']+\'|[^ \n\t\r]+)\))' . -> doesn't restrict close enough to the url itself
                '(\(([ \n\t\r]+)?("[^"\)]+"|\'[^\'\)]+\'|[^ \n\t\r\)]+)\))'.
                '))'.
                '|'.
                // '(@import([ \n\t\r]+)?("[^"]+"|\'[^\']+\'|[^ \n\t\r]+)))?/', -> takes a lot (like 100's of thousands of empty possibilities)
                '(@import([ \n\t\r]+)?("[^"]+"|\'[^\']+\'|[^ \n\t\r]+)))/',
                $attrString,
                $regs
            );
        } catch (Exception $e) {
            error_log('Caught exception: '.$e->getMessage(), 0);
        }
        if ($res) {
            for ($i = 0; $i < count($regs[1]); $i++) {
                $name = trim($regs[3][$i]);
                $check = trim($regs[0][$i]);
                $value = trim($regs[10][$i]);
                if (empty($value) and !empty($regs[13][$i])) {
                    $value = $regs[13][$i];
                }
                if (empty($name) && !empty($regs[16][$i])) {
                    $name = '@import';
                    $value = trim($regs[16][$i]);
                }
                if (!empty($name)) {
                    if (!$reduced || in_array(strtolower($name), $wanted)) {
                        if ($name == $check) {
                            $attributes[strtolower($name)][] = strtolower($name);
                        } else {
                            if (!empty($value) && ($value[0] == '\'' || $value[0] == '"')) {
                                $value = substr($value, 1, -1);
                            }

                            if ($value == 'API.LMSGetValue(name') {
                                $value = 'API.LMSGetValue(name)';
                            }
                            //Gets the xx.flv value from the string flashvars="width=320&height=240&autostart=false&file=xxx.flv&repeat=false"
                            if (isset($explode_variables[$name])) {
                                $value_modified = str_replace('&amp;', '&', $value);
                                $value_array = explode('&', $value_modified);
                                foreach ($value_array as $item) {
                                    $itemParts = explode('=', $item);
                                    $key = $itemParts[0];
                                    $item_value = !empty($itemParts[1]) ? $itemParts[1] : '';
                                    if ($key == $explode_variables[$name]) {
                                        $attributes[strtolower($name)][] = $item_value;
                                    }
                                }
                            }
                            $attributes[strtolower($name)][] = $value;
                        }
                    }
                }
            }
        }

        return $attributes;
    }

    /**
     * Replace urls inside content html from a copy course.
     *
     * @param string $content_html
     * @param string $origin_course_code
     * @param string $destination_course_directory
     * @param string $origin_course_path_from_zip
     * @param string $origin_course_info_path
     *
     * @return string new content html with replaced urls or return false if content is not a string
     */
    public static function replaceUrlWithNewCourseCode(
        $content_html,
        $origin_course_code,
        $destination_course_directory,
        $origin_course_path_from_zip = null,
        $origin_course_info_path = null
    ) {
        if (empty($content_html)) {
            return false;
        }

        $orig_source_html = self::get_resources_from_source_html($content_html);
        $orig_course_info = api_get_course_info($origin_course_code);

        // Course does not exist in the current DB probably this came from a zip file?
        if (empty($orig_course_info)) {
            if (!empty($origin_course_path_from_zip)) {
                $orig_course_path = $origin_course_path_from_zip.'/';
                $orig_course_info_path = $origin_course_info_path;
            }
        } else {
            $orig_course_path = api_get_path(SYS_COURSE_PATH).$orig_course_info['path'].'/';
            $orig_course_info_path = $orig_course_info['path'];
        }

        $destination_course_code = CourseManager::getCourseCodeFromDirectory($destination_course_directory);
        $destination_course_info = api_get_course_info($destination_course_code);
        $dest_course_path = api_get_path(SYS_COURSE_PATH).$destination_course_directory.'/';
        $dest_course_path_rel = api_get_path(REL_COURSE_PATH).$destination_course_directory.'/';

        $user_id = api_get_user_id();

        if (!empty($orig_source_html)) {
            foreach ($orig_source_html as $source) {
                // Get information about source url
                $real_orig_url = $source[0]; // url
                $scope_url = $source[1]; // scope (local, remote)
                $type_url = $source[2]; // type (rel, abs, url)

                // Get path and query from origin url
                $orig_parse_url = parse_url($real_orig_url);
                $real_orig_path = isset($orig_parse_url['path']) ? $orig_parse_url['path'] : null;
                $real_orig_query = isset($orig_parse_url['query']) ? $orig_parse_url['query'] : null;

                // Replace origin course code by destination course code from origin url query
                $dest_url_query = '';

                if (!empty($real_orig_query)) {
                    $dest_url_query = '?'.$real_orig_query;
                    if (strpos($dest_url_query, $origin_course_code) !== false) {
                        $dest_url_query = str_replace($origin_course_code, $destination_course_code, $dest_url_query);
                    }
                }

                if ($scope_url == 'local') {
                    if ($type_url == 'abs' || $type_url == 'rel') {
                        $document_file = strstr($real_orig_path, 'document');

                        if (strpos($real_orig_path, $document_file) !== false) {
                            $origin_filepath = $orig_course_path.$document_file;
                            $destination_filepath = $dest_course_path.$document_file;

                            // copy origin file inside destination course
                            if (file_exists($origin_filepath)) {
                                $filepath_dir = dirname($destination_filepath);

                                if (!is_dir($filepath_dir)) {
                                    $perm = api_get_permissions_for_new_directories();
                                    $result = @mkdir($filepath_dir, $perm, true);
                                    if ($result) {
                                        $filepath_to_add = str_replace(
                                            [$dest_course_path, 'document'],
                                            '',
                                            $filepath_dir
                                        );

                                        // Add to item properties to the new folder
                                        self::addDocument(
                                            $destination_course_info,
                                            $filepath_to_add,
                                            'folder',
                                            0,
                                            basename($filepath_to_add)
                                        );
                                    }
                                }

                                if (!file_exists($destination_filepath)) {
                                    $result = @copy($origin_filepath, $destination_filepath);
                                    if ($result) {
                                        $filepath_to_add = str_replace(
                                            [$dest_course_path, 'document'],
                                            '',
                                            $destination_filepath
                                        );
                                        $size = filesize($destination_filepath);

                                        // Add to item properties to the file
                                        self::addDocument(
                                            $destination_course_info,
                                            $filepath_to_add,
                                            'file',
                                            $size,
                                            basename($filepath_to_add)
                                        );
                                    }
                                }
                            }

                            // Replace origin course path by destination course path.
                            if (strpos($content_html, $real_orig_url) !== false) {
                                $url_course_path = str_replace(
                                    $orig_course_info_path.'/'.$document_file,
                                    '',
                                    $real_orig_path
                                );
                                // See BT#7780
                                $destination_url = $dest_course_path_rel.$document_file.$dest_url_query;
                                // If the course code doesn't exist in the path? what we do? Nothing! see BT#1985
                                if (strpos($real_orig_path, $origin_course_code) === false) {
                                    $url_course_path = $real_orig_path;
                                    $destination_url = $real_orig_path;
                                }
                                $content_html = str_replace($real_orig_url, $destination_url, $content_html);
                            }
                        }

                        // replace origin course code by destination course code  from origin url
                        if (strpos($real_orig_url, '?') === 0) {
                            $dest_url = str_replace($origin_course_code, $destination_course_code, $real_orig_url);
                            $content_html = str_replace($real_orig_url, $dest_url, $content_html);
                        }
                    }
                }
            }
        }

        return $content_html;
    }

    /**
     * Export document to PDF.
     *
     * @param int    $documentId
     * @param string $courseCode
     * @param string $orientation
     * @param bool   $showHeaderAndFooter
     */
    public static function export_to_pdf(
        $documentId,
        $courseCode,
        $orientation = 'landscape',
        $showHeaderAndFooter = true
    ) {
        $repo = Container::getDocumentRepository();
        $document = $repo->find($documentId);

        if (empty($document)) {
            return false;
        }

        $filePath = $repo->getDocumentPath($documentId);

        if (empty($filePath)) {
            return false;
        }

        $title = $document->getTitle();
        //$filePath = api_get_path(SYS_COURSE_PATH).$course_data['path'].'/document'.$document_data['path'];
        $pageFormat = 'A4';
        $pdfOrientation = 'P';
        if ($orientation === 'landscape') {
            $pageFormat = 'A4-L';
            $pdfOrientation = 'L';
        }

        $pdf = new PDF(
            $pageFormat,
            $pdfOrientation,
            $showHeaderAndFooter ? [] : ['top' => 0, 'left' => 0, 'bottom' => 0, 'right' => 0]
        );

        if (api_get_configuration_value('use_alternative_document_pdf_footer')) {
            $view = new Template('', false, false, false, true, false, false);
            $template = $view->get_template('export/alt_pdf_footer.tpl');

            $pdf->set_custom_footer([
                'html' => $view->fetch($template),
            ]);
        }

        $pdf->html_to_pdf(
            $filePath,
            $title,
            $courseCode,
            false,
            $showHeaderAndFooter
        );
        exit;
    }

    /**
     * Uploads a document.
     *
     * @param array  $files                   the $_FILES variable
     * @param string $path
     * @param string $title
     * @param string $comment
     * @param int    $unzip                   unzip or not the file
     * @param string $ifExists                overwrite, rename or warn (default)
     * @param bool   $index_document          index document (search xapian module)
     * @param bool   $show_output             print html messages
     * @param string $fileKey
     * @param bool   $treat_spaces_as_hyphens
     * @param int    $parentId
     * @param $content
     *
     * @return CDocument|false
     */
    public static function upload_document(
        $files,
        $path,
        $title = '',
        $comment = '',
        $unzip = 0,
        $ifExists = '',
        $index_document = false,
        $show_output = false,
        $fileKey = 'file',
        $treat_spaces_as_hyphens = true,
        $parentId = 0,
        $content = null
    ) {
        $course_info = api_get_course_info();
        $sessionId = api_get_session_id();
        $course_dir = $course_info['path'].'/document';
        $sys_course_path = api_get_path(SYS_COURSE_PATH);
        $base_work_dir = $sys_course_path.$course_dir;

        if (isset($files[$fileKey])) {
            $uploadOk = process_uploaded_file($files[$fileKey], $show_output);

            if ($uploadOk) {
                $document = handle_uploaded_document(
                    $course_info,
                    $files[$fileKey],
                    $base_work_dir,
                    $path,
                    api_get_user_id(),
                    api_get_group_id(),
                    null,
                    $unzip,
                    $ifExists,
                    $show_output,
                    false,
                    null,
                    $sessionId,
                    $treat_spaces_as_hyphens,
                    $fileKey,
                    $parentId,
                    $content
                );

                // Showing message when sending zip files
                if ($document && $unzip == 1) {
                    if ($show_output) {
                        echo Display::return_message(
                            get_lang('File upload succeeded!').'<br />',
                            'confirm',
                            false
                        );
                    }

                    return $document;
                }

                if ($document) {
                    if ($index_document) {
                        self::index_document(
                            $document->getId(),
                            $course_info['code'],
                            null,
                            $_POST['language'] ?? '',
                            $_REQUEST,
                            $ifExists
                        );
                    }

                    return $document;
                }
            }
        }

        return false;
    }

    /**
     * Obtains the text inside the file with the right parser.
     */
    public static function get_text_content($doc_path, $doc_mime)
    {
        // TODO: review w$ compatibility
        // Use usual exec output lines array to store stdout instead of a temp file
        // because we need to store it at RAM anyway before index on ChamiloIndexer object
        $ret_val = null;
        switch ($doc_mime) {
            case 'text/plain':
                $handle = fopen($doc_path, 'r');
                $output = [fread($handle, filesize($doc_path))];
                fclose($handle);
                break;
            case 'application/pdf':
                exec("pdftotext $doc_path -", $output, $ret_val);
                break;
            case 'application/postscript':
                $temp_file = tempnam(sys_get_temp_dir(), 'chamilo');
                exec("ps2pdf $doc_path $temp_file", $output, $ret_val);
                if ($ret_val !== 0) { // shell fail, probably 127 (command not found)
                    return false;
                }
                exec("pdftotext $temp_file -", $output, $ret_val);
                unlink($temp_file);
                break;
            case 'application/msword':
                exec("catdoc $doc_path", $output, $ret_val);
                break;
            case 'text/html':
                exec("html2text $doc_path", $output, $ret_val);
                break;
            case 'text/rtf':
                // Note: correct handling of code pages in unrtf
                // on debian lenny unrtf v0.19.2 can not, but unrtf v0.20.5 can
                exec("unrtf --text $doc_path", $output, $ret_val);
                if ($ret_val == 127) { // command not found
                    return false;
                }
                // Avoid index unrtf comments
                if (is_array($output) && count($output) > 1) {
                    $parsed_output = [];
                    foreach ($output as &$line) {
                        if (!preg_match('/^###/', $line, $matches)) {
                            if (!empty($line)) {
                                $parsed_output[] = $line;
                            }
                        }
                    }
                    $output = $parsed_output;
                }
                break;
            case 'application/vnd.ms-powerpoint':
                exec("catppt $doc_path", $output, $ret_val);
                break;
            case 'application/vnd.ms-excel':
                exec("xls2csv -c\" \" $doc_path", $output, $ret_val);
                break;
        }

        $content = '';
        if (!is_null($ret_val)) {
            if ($ret_val !== 0) { // shell fail, probably 127 (command not found)
                return false;
            }
        }
        if (isset($output)) {
            foreach ($output as &$line) {
                $content .= $line."\n";
            }

            return $content;
        } else {
            return false;
        }
    }

    /**
     * Display the document quota in a simple way.
     *
     *  Here we count 1 Kilobyte = 1024 Bytes, 1 Megabyte = 1048576 Bytes
     */
    public static function displaySimpleQuota($course_quota, $already_consumed_space)
    {
        $course_quota_m = round($course_quota / 1048576);
        $already_consumed_space_m = round($already_consumed_space / 1048576, 2);
        $percentage = $already_consumed_space / $course_quota * 100;
        $percentage = round($percentage, 1);
        $message = get_lang('You are currently using %s MB (%s) of your %s MB.');
        $message = sprintf($message, $already_consumed_space_m, $percentage.'%', $course_quota_m.' ');

        return Display::div($message, ['id' => 'document_quota', 'class' => 'card-quota']);
    }

    /**
     * Checks if there is enough place to add a file on a directory
     * on the base of a maximum directory size allowed.
     *
     * @author Bert Vanderkimpen
     *
     * @param int $file_size     size of the file in byte
     * @param int $max_dir_space maximum size
     *
     * @return bool true if there is enough space, false otherwise
     */
    public static function enough_space($file_size, $max_dir_space)
    {
        if ($max_dir_space) {
            $repo = Container::getDocumentRepository();
            $total = $repo->getTotalSpace(api_get_course_int_id());

            if (($file_size + $total) > $max_dir_space) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array $params count, url, extension
     *
     * @return string
     */
    public static function generateAudioJavascript($params = [])
    {
        $js = '
            $(\'audio.audio_preview\').mediaelementplayer({
                features: [\'playpause\'],
                audioWidth: 30,
                audioHeight: 30,
                success: function(mediaElement, originalNode, instance) {                
                }
            });';

        return $js;
    }

    /**
     * Shows a play icon next to the document title in the document list.
     *
     * @param string $documentWebPath
     * @param array  $documentInfo
     *
     * @return string
     */
    public static function generateAudioPreview($documentWebPath, $documentInfo)
    {
        $filePath = $documentWebPath.$documentInfo['path'];
        $extension = $documentInfo['file_extension'];
        $html = '<span class="preview"> <audio class="audio_preview skip" src="'.$filePath.'" type="audio/'.$extension.'" > </audio></span>';

        return $html;
    }

    /**
     * @param string $file
     * @param string $extension
     *
     * @return string
     */
    public static function generateMediaPreview($file, $extension)
    {
        $id = api_get_unique_id();
        switch ($extension) {
            case 'mp3':
                $document_data['file_extension'] = $extension;
                $html = '<div style="margin: 0; position: absolute; top: 50%; left: 35%;">';
                $html .= '<audio id="'.$id.'" controls="controls" src="'.$file.'" type="audio/mp3" ></audio></div>';
                break;
            default:
                $html = '<video id="'.$id.'" controls>';
                $html .= '<source src="'.$file.'" >';
                $html .= '</video>';
                break;
        }

        return $html;
    }

    /**
     * @param array  $course_info
     * @param bool   $lp_id
     * @param string $target
     * @param int    $session_id
     * @param bool   $add_move_button
     * @param string $filter_by_folder
     * @param string $overwrite_url
     * @param bool   $showInvisibleFiles
     * @param bool   $showOnlyFolders
     * @param int    $folderId
     * @param bool   $addCloseButton
     *
     * @return string
     */
    public static function get_document_preview(
        $course_info,
        $lp_id = false,
        $target = '',
        $session_id = 0,
        $add_move_button = false,
        $filter_by_folder = null,
        $overwrite_url = '',
        $showInvisibleFiles = false,
        $showOnlyFolders = false,
        $folderId = false,
        $addCloseButton = true
    ) {
        if (empty($course_info['real_id']) || empty($course_info['code']) || !is_array($course_info)) {
            return '';
        }

        $user_id = api_get_user_id();
        $userInfo = api_get_user_info();
        $user_in_course = api_is_platform_admin();
        if (!$user_in_course) {
            if (CourseManager::is_course_teacher($user_id, $course_info['code'])) {
                $user_in_course = true;
            }
        }

        // Condition for the session
        $session_id = (int) $session_id;

        if (!$user_in_course) {
            if (empty($session_id)) {
                if (CourseManager::is_user_subscribed_in_course($user_id, $course_info['code'])) {
                    $user_in_course = true;
                }
                // Check if course is open then we can consider that the student is registered to the course
                if (isset($course_info) && in_array($course_info['visibility'], [2, 3])) {
                    $user_in_course = true;
                }
            } else {
                $user_status = SessionManager::get_user_status_in_course_session(
                    $user_id,
                    $course_info['real_id'],
                    $session_id
                );
                //is true if is an student, course session teacher or coach
                if (in_array($user_status, ['0', '2', '6'])) {
                    $user_in_course = true;
                }
            }
        }

        $tbl_doc = Database::get_course_table(TABLE_DOCUMENT);
        $tbl_item_prop = Database::get_course_table(TABLE_ITEM_PROPERTY);
        $condition_session = " AND (l.session_id = '$session_id' OR l.session_id = '0' OR l.session_id IS NULL)";

        $add_folder_filter = null;
        if (!empty($filter_by_folder)) {
            $add_folder_filter = " AND docs.path LIKE '".Database::escape_string($filter_by_folder)."%'";
        }

        // If we are in LP display hidden folder https://support.chamilo.org/issues/6679
        $lp_visibility_condition = null;
        if ($lp_id) {
            if ($showInvisibleFiles) {
                $lp_visibility_condition .= ' OR l.visibility = 0';
            }
        }

        $folderCondition = " AND docs.path LIKE '/%' ";
        if (!api_is_allowed_to_edit()) {
            $protectedFolders = self::getProtectedFolderFromStudent();
            foreach ($protectedFolders as $folder) {
                $folderCondition .= " AND docs.path NOT LIKE '$folder' ";
            }
        }

        $parentData = [];
        if ($folderId !== false) {
            $parentData = self::get_document_data_by_id(
                $folderId,
                $course_info['code'],
                false,
                $session_id
            );
            if (!empty($parentData)) {
                $cleanedPath = $parentData['path'];
                $num = substr_count($cleanedPath, '/');

                $notLikeCondition = '';
                for ($i = 1; $i <= $num; $i++) {
                    $repeat = str_repeat('/%', $i + 1);
                    $notLikeCondition .= " AND docs.path NOT LIKE '".Database::escape_string($cleanedPath.$repeat)."' ";
                }

                $folderId = (int) $folderId;
                $folderCondition = " AND
                    docs.id <> $folderId AND
                    docs.path LIKE '".$cleanedPath."/%'
                    $notLikeCondition
                ";
            } else {
                $folderCondition = " AND docs.filetype = 'file' ";
            }
        }

        $levelCondition = '';
        if ($folderId === false) {
            $levelCondition = " AND docs.path NOT LIKE'/%/%'";
        }

        $sql = "SELECT DISTINCT l.visibility, docs.*
                FROM resource_node AS n
                INNER JOIN $tbl_doc AS docs
                ON (docs.resource_node_id = n.id)
                INNER JOIN resource_link l
                ON (l.resource_node_id = n.id)    
                WHERE                    
                    docs.path NOT LIKE '%_DELETED_%' AND                    
                    docs.c_id = {$course_info['real_id']} AND
                    l.visibility NOT IN ('".ResourceLink::VISIBILITY_DELETED."')                    
                    $folderCondition
                    $levelCondition
                    $add_folder_filter
                ORDER BY docs.filetype DESC, docs.title ASC";

        $res_doc = Database::query($sql);
        $resources = Database::store_result($res_doc, 'ASSOC');

        $return = '';
        if ($lp_id == false && $addCloseButton) {
            if ($folderId === false) {
                $return .= Display::div(
                    Display::url(
                        Display::return_icon('close.png', get_lang('Close'), [], ICON_SIZE_SMALL),
                        ' javascript:void(0);',
                        ['id' => 'close_div_'.$course_info['real_id'].'_'.$session_id, 'class' => 'close_div']
                    ),
                    ['style' => 'position:absolute;right:10px']
                );
            }
        }

        // If you want to debug it, I advise you to do "echo" on the eval statements.
        $newResources = [];
        if (!empty($resources) && $user_in_course) {
            foreach ($resources as $resource) {
                $is_visible = self::is_visible_by_id(
                    $resource['id'],
                    $course_info,
                    $session_id,
                    api_get_user_id()
                );

                if ($showInvisibleFiles === false) {
                    if (!$is_visible) {
                        continue;
                    }
                }

                $newResources[] = $resource;
            }
        }

        $label = get_lang('Documents');

        $documents = [];
        if ($folderId === false) {
            $documents[$label] = [
                'id' => 0,
                'files' => $newResources,
            ];
        } else {
            if (is_array($parentData)) {
                $documents[$parentData['title']] = [
                    'id' => (int) $folderId,
                    'files' => $newResources,
                ];
            }
        }

        $writeResult = self::write_resources_tree(
            $userInfo,
            $course_info,
            $session_id,
            $documents,
            $lp_id,
            $target,
            $add_move_button,
            $overwrite_url,
            $folderId
        );

        $return .= $writeResult;
        $lpAjaxUrl = api_get_path(WEB_AJAX_PATH).'lp.ajax.php';
        if ($lp_id === false) {
            $url = $lpAjaxUrl.'?a=get_documents&lp_id=&cidReq='.$course_info['code'];
            $return .= "<script>
            $(function() {
                $('.close_div').click(function() {
                    var course_id = this.id.split('_')[2];
                    var session_id = this.id.split('_')[3];
                    $('#document_result_'+course_id+'_'+session_id).hide();
                    $('.lp_resource').remove();
                    $('.document_preview_container').html('');
                });
            });
            </script>";
        } else {
            // For LPs
            $url = $lpAjaxUrl.'?a=get_documents&lp_id='.$lp_id.'&'.api_get_cidreq();
        }

        if (!empty($overwrite_url)) {
            $url .= '&url='.Security::remove_XSS($overwrite_url);
        }

        if ($add_move_button) {
            $url .= '&add_move_button=1';
        }

        $return .= "<script>
            function testResources(id, img) {
                var numericId = id.split('_')[1];
                var parentId = 'doc_id_'+numericId;
                var tempId = 'temp_'+numericId;
                var image = $('#'+img);

                if (image.hasClass('open')) {
                    image.removeClass('open');
                    image.attr('src', '".Display::returnIconPath('nolines_plus.gif')."');
                    $('#'+id).show();
                    $('#'+tempId).hide();
                } else {
                    image.addClass('open');
                    image.attr('src', '".Display::returnIconPath('nolines_minus.gif')."');
                    $('#'+id).hide();
                    $('#'+tempId).show();
                    var tempDiv = $('#'+parentId).find('#'+tempId);
                    if (tempDiv.length == 0) {
                        $.ajax({
                            type: 'GET',
                            async: false,
                            url:  '".$url."',
                            data: 'folder_id='+numericId,
                            success: function(data) {
                                tempDiv = $('#doc_id_'+numericId).append('<div id='+tempId+'>'+data+'</div>');
                            }
                        });
                    }
                }
            }
            </script>";

        if (!$user_in_course) {
            $return = '';
        }

        return $return;
    }

    /**
     * Generate and return an HTML list of resources based on a given array.
     * This list is used to show the course creator a list of available resources to choose from
     * when creating a learning path.
     *
     * @param array  $userInfo        current user info
     * @param array  $course_info
     * @param int    $session_id
     * @param array  $documents
     * @param bool   $lp_id
     * @param string $target
     * @param bool   $add_move_button
     * @param string $overwrite_url
     * @param int    $folderId
     *
     * @return string
     */
    public static function write_resources_tree(
        $userInfo,
        $course_info,
        $session_id,
        $documents,
        $lp_id = false,
        $target = '',
        $add_move_button = false,
        $overwrite_url = '',
        $folderId = false
    ) {
        $return = '';
        if (!empty($documents)) {
            foreach ($documents as $key => $resource) {
                if (isset($resource['id']) && is_int($resource['id'])) {
                    $mainFolderResource = [
                        'id' => $resource['id'],
                        'title' => $key,
                    ];

                    if ($folderId === false) {
                        $return .= self::parseFolder($folderId, $mainFolderResource, $lp_id);
                    }

                    if (isset($resource['files'])) {
                        $return .= self::write_resources_tree(
                            $userInfo,
                            $course_info,
                            $session_id,
                            $resource['files'],
                            $lp_id,
                            $target,
                            $add_move_button,
                            $overwrite_url
                        );
                    }
                    $return .= '</div>';
                    $return .= '</ul>';
                } else {
                    if ($resource['filetype'] === 'folder') {
                        $return .= self::parseFolder($folderId, $resource, $lp_id);
                    } else {
                        $return .= self::parseFile(
                            $userInfo,
                            $course_info,
                            $session_id,
                            $resource,
                            $lp_id,
                            $add_move_button,
                            $target,
                            $overwrite_url
                        );
                    }
                }
            }
        }

        return $return;
    }

    /**
     * @param int   $doc_id
     * @param array $courseInfo
     * @param int   $sessionId
     * @param int   $user_id
     * @param int   $groupId               iid
     * @param bool  $checkParentVisibility
     *
     * @return bool
     */
    public static function check_visibility_tree(
        $doc_id,
        $courseInfo,
        $sessionId,
        $user_id,
        $groupId = 0,
        $checkParentVisibility = true
    ) {
        if (empty($courseInfo)) {
            return false;
        }

        $courseCode = $courseInfo['code'];

        if (empty($courseCode)) {
            return false;
        }

        $document_data = self::get_document_data_by_id(
            $doc_id,
            $courseCode,
            null,
            $sessionId
        );

        if ($sessionId != 0 && !$document_data) {
            $document_data = self::get_document_data_by_id(
                $doc_id,
                $courseCode,
                null,
                0
            );
        }

        if (!empty($document_data)) {
            // If admin or course teacher, allow anyway
            if (api_is_platform_admin() || CourseManager::is_course_teacher($user_id, $courseCode)) {
                return true;
            }

            if ($document_data['parent_id'] == false || empty($document_data['parent_id'])) {
                if (!empty($groupId)) {
                    return true;
                }
                $visible = self::is_visible_by_id($doc_id, $courseInfo, $sessionId, $user_id);

                return $visible;
            } else {
                $visible = self::is_visible_by_id($doc_id, $courseInfo, $sessionId, $user_id);

                if (!$visible) {
                    return false;
                } else {
                    if ($checkParentVisibility) {
                        return self::check_visibility_tree(
                            $document_data['parent_id'],
                            $courseInfo,
                            $sessionId,
                            $user_id,
                            $groupId
                        );
                    }

                    return true;
                }
            }
        } else {
            return false;
        }
    }

    /**
     * Index a given document.
     *
     * @param   int     Document ID inside its corresponding course
     * @param   string  Course code
     * @param   int     Session ID (not used yet)
     * @param   string  Language of document's content (defaults to course language)
     * @param   array   Array of specific fields (['code'=>'value',...])
     * @param   string  What to do if the file already exists (default or overwrite)
     * @param   bool    When set to true, this runs the indexer without actually saving anything to any database
     *
     * @return bool Returns true on presumed success, false on failure
     */
    public static function index_document(
        $docid,
        $course_code,
        $session_id = 0,
        $lang = 'english',
        $specific_fields_values = [],
        $if_exists = '',
        $simulation = false
    ) {
        if (api_get_setting('search_enabled') !== 'true') {
            return false;
        }
        if (empty($docid) or $docid != intval($docid)) {
            return false;
        }
        if (empty($session_id)) {
            $session_id = api_get_session_id();
        }
        $course_info = api_get_course_info($course_code);
        $course_dir = $course_info['path'].'/document';
        $sys_course_path = api_get_path(SYS_COURSE_PATH);
        $base_work_dir = $sys_course_path.$course_dir;

        $course_id = $course_info['real_id'];
        $table_document = Database::get_course_table(TABLE_DOCUMENT);

        $qry = "SELECT path, title FROM $table_document WHERE c_id = $course_id AND id = '$docid' LIMIT 1";
        $result = Database::query($qry);
        if (Database::num_rows($result) == 1) {
            $row = Database::fetch_array($result);
            $doc_path = api_get_path(SYS_COURSE_PATH).$course_dir.$row['path'];
            //TODO: mime_content_type is deprecated, fileinfo php extension is enabled by default as of PHP 5.3.0
            // now versions of PHP on Debian testing(5.2.6-5) and Ubuntu(5.2.6-2ubuntu) are lower, so wait for a while
            $doc_mime = mime_content_type($doc_path);
            $allowed_mime_types = self::file_get_mime_type(true);

            // mime_content_type does not detect correctly some formats that
            // are going to be supported for index, so an extensions array is used for the moment
            if (empty($doc_mime)) {
                $allowed_extensions = [
                    'doc',
                    'docx',
                    'ppt',
                    'pptx',
                    'pps',
                    'ppsx',
                    'xls',
                    'xlsx',
                    'odt',
                    'odp',
                    'ods',
                    'pdf',
                    'txt',
                    'rtf',
                    'msg',
                    'csv',
                    'html',
                    'htm',
                ];
                $extensions = preg_split("/[\/\\.]/", $doc_path);
                $doc_ext = strtolower($extensions[count($extensions) - 1]);
                if (in_array($doc_ext, $allowed_extensions)) {
                    switch ($doc_ext) {
                        case 'ppt':
                        case 'pps':
                            $doc_mime = 'application/vnd.ms-powerpoint';
                            break;
                        case 'xls':
                            $doc_mime = 'application/vnd.ms-excel';
                            break;
                    }
                }
            }

            //@todo move this nightmare in a search controller or something like that!!! J.M

            if (in_array($doc_mime, $allowed_mime_types)) {
                $file_title = $row['title'];
                $file_content = self::get_text_content($doc_path, $doc_mime);
                $course_code = Database::escape_string($course_code);
                $ic_slide = new IndexableChunk();
                $ic_slide->addValue('title', $file_title);
                $ic_slide->addCourseId($course_code);
                $ic_slide->addToolId(TOOL_DOCUMENT);
                $xapian_data = [
                    SE_COURSE_ID => $course_code,
                    SE_TOOL_ID => TOOL_DOCUMENT,
                    SE_DATA => ['doc_id' => $docid],
                    SE_USER => api_get_user_id(),
                ];

                $ic_slide->xapian_data = serialize($xapian_data);
                $di = new ChamiloIndexer();
                $return = $di->connectDb(null, null, $lang);

                require_once api_get_path(LIBRARY_PATH).'specific_fields_manager.lib.php';
                $specific_fields = get_specific_field_list();

                // process different depending on what to do if file exists
                /**
                 * @TODO Find a way to really verify if the file had been
                 * overwriten. Now all work is done at
                 * handle_uploaded_document() and it's difficult to verify it
                 */
                if (!empty($if_exists) && $if_exists == 'overwrite') {
                    // Overwrite the file on search engine
                    // Actually, it consists on a delete of terms from db,
                    // insert new ones, create a new search engine document,
                    // and remove the old one
                    // Get search_did
                    $tbl_se_ref = Database::get_main_table(TABLE_MAIN_SEARCH_ENGINE_REF);
                    $sql = 'SELECT * FROM %s WHERE course_code=\'%s\' AND tool_id=\'%s\' AND ref_id_high_level=%s LIMIT 1';
                    $sql = sprintf($sql, $tbl_se_ref, $course_code, TOOL_DOCUMENT, $docid);

                    $res = Database::query($sql);

                    if (Database::num_rows($res) > 0) {
                        $se_ref = Database::fetch_array($res);
                        if (!$simulation) {
                            $di->remove_document($se_ref['search_did']);
                        }
                        $all_specific_terms = '';
                        foreach ($specific_fields as $specific_field) {
                            if (!$simulation) {
                                delete_all_specific_field_value($course_code, $specific_field['id'], TOOL_DOCUMENT, $docid);
                            }
                            // Update search engine
                            if (isset($specific_fields_values[$specific_field['code']])) {
                                $sterms = trim($specific_fields_values[$specific_field['code']]);
                            } else { //if the specific field is not defined, force an empty one
                                $sterms = '';
                            }
                            $all_specific_terms .= ' '.$sterms;
                            $sterms = explode(',', $sterms);
                            foreach ($sterms as $sterm) {
                                $sterm = trim($sterm);
                                if (!empty($sterm)) {
                                    $ic_slide->addTerm($sterm, $specific_field['code']);
                                    // updated the last param here from $value to $sterm without being sure - see commit15464
                                    if (!$simulation) {
                                        add_specific_field_value(
                                            $specific_field['id'],
                                            $course_code,
                                            TOOL_DOCUMENT,
                                            $docid,
                                            $sterm
                                        );
                                    }
                                }
                            }
                        }
                        // Add terms also to content to make terms findable by probabilistic search
                        $file_content = $all_specific_terms.' '.$file_content;

                        if (!$simulation) {
                            $ic_slide->addValue('content', $file_content);
                            $di->addChunk($ic_slide);
                            // Index and return a new search engine document id
                            $did = $di->index();

                            if ($did) {
                                // update the search_did on db
                                $tbl_se_ref = Database::get_main_table(TABLE_MAIN_SEARCH_ENGINE_REF);
                                $sql = 'UPDATE %s SET search_did=%d WHERE id=%d LIMIT 1';
                                $sql = sprintf($sql, $tbl_se_ref, (int) $did, (int) $se_ref['id']);
                                Database::query($sql);
                            }
                        }
                    }
                } else {
                    // Add all terms
                    $all_specific_terms = '';
                    foreach ($specific_fields as $specific_field) {
                        if (isset($specific_fields_values[$specific_field['code']])) {
                            $sterms = trim($specific_fields_values[$specific_field['code']]);
                        } else { //if the specific field is not defined, force an empty one
                            $sterms = '';
                        }
                        $all_specific_terms .= ' '.$sterms;
                        if (!empty($sterms)) {
                            $sterms = explode(',', $sterms);
                            foreach ($sterms as $sterm) {
                                if (!$simulation) {
                                    $ic_slide->addTerm(trim($sterm), $specific_field['code']);
                                    add_specific_field_value(
                                        $specific_field['id'],
                                        $course_code,
                                        TOOL_DOCUMENT,
                                        $docid,
                                        $sterm
                                    );
                                }
                            }
                        }
                    }
                    // Add terms also to content to make terms findable by probabilistic search
                    $file_content = $all_specific_terms.' '.$file_content;
                    if (!$simulation) {
                        $ic_slide->addValue('content', $file_content);
                        $di->addChunk($ic_slide);
                        // Index and return search engine document id
                        $did = $di->index();
                        if ($did) {
                            // Save it to db
                            $tbl_se_ref = Database::get_main_table(TABLE_MAIN_SEARCH_ENGINE_REF);
                            $sql = 'INSERT INTO %s (id, course_code, tool_id, ref_id_high_level, search_did)
                            VALUES (NULL , \'%s\', \'%s\', %s, %s)';
                            $sql = sprintf($sql, $tbl_se_ref, $course_code, TOOL_DOCUMENT, $docid, $did);
                            Database::query($sql);
                        } else {
                            return false;
                        }
                    }
                }
            } else {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array
     */
    public static function get_web_odf_extension_list()
    {
        return ['ods', 'odt', 'odp'];
    }

    /**
     * Set of extension allowed to use Jodconverter.
     *
     * @param $mode 'from'
     *              'to'
     *              'all'
     * @param $format   'text'
     *                  'spreadsheet'
     *                  'presentation'
     *                  'drawing'
     *                  'all'
     *
     * @return array
     */
    public static function getJodconverterExtensionList($mode, $format)
    {
        $extensionList = [];
        $extensionListFromText = [
            'odt',
            'sxw',
            'rtf',
            'doc',
            'docx',
            'wpd',
            'txt',
        ];
        $extensionListToText = [
            'pdf',
            'odt',
            'sxw',
            'rtf',
            'doc',
            'docx',
            'txt',
        ];
        $extensionListFromSpreadsheet = [
            'ods',
            'sxc',
            'xls',
            'xlsx',
            'csv',
            'tsv',
        ];
        $extensionListToSpreadsheet = [
            'pdf',
            'ods',
            'sxc',
            'xls',
            'xlsx',
            'csv',
            'tsv',
        ];
        $extensionListFromPresentation = [
            'odp',
            'sxi',
            'ppt',
            'pptx',
        ];
        $extensionListToPresentation = [
            'pdf',
            'swf',
            'odp',
            'sxi',
            'ppt',
            'pptx',
        ];
        $extensionListFromDrawing = ['odg'];
        $extensionListToDrawing = ['svg', 'swf'];

        if ($mode === 'from') {
            if ($format === 'text') {
                $extensionList = array_merge($extensionList, $extensionListFromText);
            } elseif ($format === 'spreadsheet') {
                $extensionList = array_merge($extensionList, $extensionListFromSpreadsheet);
            } elseif ($format === 'presentation') {
                $extensionList = array_merge($extensionList, $extensionListFromPresentation);
            } elseif ($format === 'drawing') {
                $extensionList = array_merge($extensionList, $extensionListFromDrawing);
            } elseif ($format === 'all') {
                $extensionList = array_merge($extensionList, $extensionListFromText);
                $extensionList = array_merge($extensionList, $extensionListFromSpreadsheet);
                $extensionList = array_merge($extensionList, $extensionListFromPresentation);
                $extensionList = array_merge($extensionList, $extensionListFromDrawing);
            }
        } elseif ($mode === 'to') {
            if ($format === 'text') {
                $extensionList = array_merge($extensionList, $extensionListToText);
            } elseif ($format === 'spreadsheet') {
                $extensionList = array_merge($extensionList, $extensionListToSpreadsheet);
            } elseif ($format === 'presentation') {
                $extensionList = array_merge($extensionList, $extensionListToPresentation);
            } elseif ($format === 'drawing') {
                $extensionList = array_merge($extensionList, $extensionListToDrawing);
            } elseif ($format === 'all') {
                $extensionList = array_merge($extensionList, $extensionListToText);
                $extensionList = array_merge($extensionList, $extensionListToSpreadsheet);
                $extensionList = array_merge($extensionList, $extensionListToPresentation);
                $extensionList = array_merge($extensionList, $extensionListToDrawing);
            }
        } elseif ($mode === 'all') {
            if ($format === 'text') {
                $extensionList = array_merge($extensionList, $extensionListFromText);
                $extensionList = array_merge($extensionList, $extensionListToText);
            } elseif ($format === 'spreadsheet') {
                $extensionList = array_merge($extensionList, $extensionListFromSpreadsheet);
                $extensionList = array_merge($extensionList, $extensionListToSpreadsheet);
            } elseif ($format === 'presentation') {
                $extensionList = array_merge($extensionList, $extensionListFromPresentation);
                $extensionList = array_merge($extensionList, $extensionListToPresentation);
            } elseif ($format === 'drawing') {
                $extensionList = array_merge($extensionList, $extensionListFromDrawing);
                $extensionList = array_merge($extensionList, $extensionListToDrawing);
            } elseif ($format === 'all') {
                $extensionList = array_merge($extensionList, $extensionListFromText);
                $extensionList = array_merge($extensionList, $extensionListToText);
                $extensionList = array_merge($extensionList, $extensionListFromSpreadsheet);
                $extensionList = array_merge($extensionList, $extensionListToSpreadsheet);
                $extensionList = array_merge($extensionList, $extensionListFromPresentation);
                $extensionList = array_merge($extensionList, $extensionListToPresentation);
                $extensionList = array_merge($extensionList, $extensionListFromDrawing);
                $extensionList = array_merge($extensionList, $extensionListToDrawing);
            }
        }

        return $extensionList;
    }

    /**
     * Get Format type list by extension and mode.
     *
     * @param string $mode Mode to search format type list
     *
     * @example 'from'
     * @example 'to'
     *
     * @param string $extension file extension to check file type
     *
     * @return array
     */
    public static function getFormatTypeListConvertor($mode = 'from', $extension)
    {
        $formatTypesList = [];
        $formatTypes = ['text', 'spreadsheet', 'presentation', 'drawing'];
        foreach ($formatTypes as $formatType) {
            if (in_array($extension, self::getJodconverterExtensionList($mode, $formatType))) {
                $formatTypesList[] = $formatType;
            }
        }

        return $formatTypesList;
    }

    /**
     * @param string $path
     * @param bool   $is_certificate_mode
     *
     * @return bool
     */
    public static function is_folder_to_avoid($path, $is_certificate_mode = false)
    {
        $foldersToAvoid = [
            '/HotPotatoes_files',
            '/certificates',
        ];
        $systemFolder = api_get_course_setting('show_system_folders');

        if ($systemFolder == 1) {
            $foldersToAvoid = [];
        }

        if (basename($path) == 'css') {
            return true;
        }

        if ($is_certificate_mode == false) {
            //Certificate results
            if (strstr($path, 'certificates')) {
                return true;
            }
        }

        // Admin setting for Hide/Show the folders of all users
        if (api_get_setting('show_users_folders') == 'false') {
            $foldersToAvoid[] = '/shared_folder';

            if (strstr($path, 'shared_folder_session_')) {
                return true;
            }
        }

        // Admin setting for Hide/Show Default folders to all users
        if (api_get_setting('show_default_folders') == 'false') {
            $foldersToAvoid[] = '/images';
            $foldersToAvoid[] = '/flash';
            $foldersToAvoid[] = '/audio';
            $foldersToAvoid[] = '/video';
        }

        // Admin setting for Hide/Show chat history folder
        if (api_get_setting('show_chat_folder') == 'false') {
            $foldersToAvoid[] = '/chat_files';
        }

        if (is_array($foldersToAvoid)) {
            return in_array($path, $foldersToAvoid);
        } else {
            return false;
        }
    }

    /**
     * @return array
     */
    public static function get_system_folders()
    {
        return [
            '/certificates',
            '/HotPotatoes_files',
            '/chat_files',
            '/images',
            '/flash',
            '/audio',
            '/video',
            '/shared_folder',
            '/learning_path',
        ];
    }

    /**
     * @return array
     */
    public static function getProtectedFolderFromStudent()
    {
        return [
            '/certificates',
            '/HotPotatoes_files',
            '/chat_files',
            '/shared_folder',
            '/learning_path',
        ];
    }

    /**
     * @param string $courseCode
     *
     * @return string 'visible' or 'invisible' string
     */
    public static function getDocumentDefaultVisibility($courseCode)
    {
        $settings = api_get_setting('tool_visible_by_default_at_creation');
        $defaultVisibility = 'visible';

        if (isset($settings['documents'])) {
            $portalDefaultVisibility = 'invisible';
            if ($settings['documents'] == 'true') {
                $portalDefaultVisibility = 'visible';
            }

            $defaultVisibility = $portalDefaultVisibility;
        }

        if (api_get_setting('documents_default_visibility_defined_in_course') == 'true') {
            $courseVisibility = api_get_course_setting('documents_default_visibility', $courseCode);
            if (!empty($courseVisibility) && in_array($courseVisibility, ['visible', 'invisible'])) {
                $defaultVisibility = $courseVisibility;
            }
        }

        return $defaultVisibility;
    }

    /**
     * @param array  $courseInfo
     * @param int    $id         doc id
     * @param string $visibility visible/invisible
     * @param int    $userId
     */
    public static function updateVisibilityFromAllSessions($courseInfo, $id, $visibility, $userId)
    {
        $sessionList = SessionManager::get_session_by_course($courseInfo['real_id']);

        if (!empty($sessionList)) {
            foreach ($sessionList as $session) {
                $sessionId = $session['id'];
                api_item_property_update(
                    $courseInfo,
                    TOOL_DOCUMENT,
                    $id,
                    $visibility,
                    $userId,
                    null,
                    null,
                    null,
                    null,
                    $sessionId
                );
            }
        }
    }

    /**
     * @param string $filePath
     * @param string $path
     * @param array  $courseInfo
     * @param int    $sessionId
     * @param string $whatIfFileExists overwrite|rename
     * @param int    $userId
     * @param int    $groupId
     * @param int    $toUserId
     * @param string $comment
     *
     * @return bool|path
     */
    public static function addFileToDocumentTool(
        $filePath,
        $path,
        $courseInfo,
        $sessionId,
        $userId,
        $whatIfFileExists = 'overwrite',
        $groupId = null,
        $toUserId = null,
        $comment = null
    ) {
        if (!file_exists($filePath)) {
            return false;
        }

        $fileInfo = pathinfo($filePath);

        $file = [
            'name' => $fileInfo['basename'],
            'tmp_name' => $filePath,
            'size' => filesize($filePath),
            'from_file' => true,
        ];

        $course_dir = $courseInfo['path'].'/document';
        $baseWorkDir = api_get_path(SYS_COURSE_PATH).$course_dir;

        $filePath = handle_uploaded_document(
            $courseInfo,
            $file,
            $baseWorkDir,
            $path,
            $userId,
            $groupId,
            $toUserId,
            false,
            $whatIfFileExists,
            false,
            false,
            $comment,
            $sessionId
        );

        if ($filePath) {
            return self::get_document_id(
                $courseInfo,
                $filePath,
                $sessionId
            );
        }

        return false;
    }

    /**
     * Converts wav to mp3 file.
     * Requires the ffmpeg lib. In ubuntu: sudo apt-get install ffmpeg.
     *
     * @param string $wavFile
     * @param bool   $removeWavFileIfSuccess
     *
     * @return bool
     */
    public static function convertWavToMp3($wavFile, $removeWavFileIfSuccess = false)
    {
        if (file_exists($wavFile)) {
            try {
                $ffmpeg = \FFMpeg\FFMpeg::create();
                $video = $ffmpeg->open($wavFile);

                $mp3File = str_replace('wav', 'mp3', $wavFile);
                $result = $video->save(new FFMpeg\Format\Audio\Mp3(), $mp3File);
                if ($result && $removeWavFileIfSuccess) {
                    unlink($wavFile);
                }

                if (file_exists($mp3File)) {
                    return $mp3File;
                }
            } catch (Exception $e) {
                error_log($e->getMessage());
                error_log($e->getPrevious()->getMessage());
            }
        }

        return false;
    }

    /**
     * @param string $documentData     wav document information
     * @param array  $courseInfo
     * @param int    $sessionId
     * @param int    $userId           user that adds the document
     * @param string $whatIfFileExists
     * @param bool   $deleteWavFile
     *
     * @return bool
     */
    public static function addAndConvertWavToMp3(
        $documentData,
        $courseInfo,
        $sessionId,
        $userId,
        $whatIfFileExists = 'overwrite',
        $deleteWavFile = false
    ) {
        if (empty($documentData)) {
            return false;
        }

        if (isset($documentData['absolute_path']) &&
            file_exists($documentData['absolute_path'])
        ) {
            $mp3FilePath = self::convertWavToMp3($documentData['absolute_path']);

            if (!empty($mp3FilePath) && file_exists($mp3FilePath)) {
                $documentId = self::addFileToDocumentTool(
                    $mp3FilePath,
                    dirname($documentData['path']),
                    $courseInfo,
                    $sessionId,
                    $userId,
                    $whatIfFileExists,
                    null,
                    null,
                    $documentData['comment']
                );

                if (!empty($documentId)) {
                    if ($deleteWavFile) {
                        $coursePath = $courseInfo['directory'].'/document';
                        $documentPath = api_get_path(SYS_COURSE_PATH).$coursePath;
                        self::delete_document(
                            $courseInfo,
                            null,
                            $documentPath,
                            $sessionId,
                            $documentData['id']
                        );
                    }

                    return $documentId;
                }
            }
        }

        return false;
    }

    /**
     * Sets.
     *
     * @param string $file         ($document_data['path'])
     * @param string $file_url_sys
     *
     * @return string
     */
    public static function generateAudioTempFile($file, $file_url_sys)
    {
        //make temp audio
        $temp_folder = api_get_path(SYS_ARCHIVE_PATH).'temp/audio';
        if (!file_exists($temp_folder)) {
            @mkdir($temp_folder, api_get_permissions_for_new_directories(), true);
        }

        //make htaccess with allow from all, and file index.html into temp/audio
        $htaccess = api_get_path(SYS_ARCHIVE_PATH).'temp/audio/.htaccess';
        if (!file_exists($htaccess)) {
            $htaccess_content = "order deny,allow\r\nallow from all\r\nOptions -Indexes";
            $fp = @fopen(api_get_path(SYS_ARCHIVE_PATH).'temp/audio/.htaccess', 'w');
            if ($fp) {
                fwrite($fp, $htaccess_content);
                fclose($fp);
            }
        }

        //encript temp name file
        $name_crip = sha1(uniqid()); //encript
        $findext = explode(".", $file);
        $extension = $findext[count($findext) - 1];
        $file_crip = $name_crip.'.'.$extension;

        //copy file to temp/audio directory
        $from_sys = $file_url_sys;
        $to_sys = api_get_path(SYS_ARCHIVE_PATH).'temp/audio/'.$file_crip;

        if (file_exists($from_sys)) {
            copy($from_sys, $to_sys);
        }

        // get file from tmp directory
        Session::write('temp_audio_nanogong', $to_sys);

        return api_get_path(WEB_ARCHIVE_PATH).'temp/audio/'.$file_crip;
    }

    /**
     * Erase temp nanogong audio.
     */
    public static function removeGeneratedAudioTempFile()
    {
        $tempAudio = Session::read('temp_audio_nanogong');
        if (!empty(isset($tempAudio)) && is_file($tempAudio)) {
            unlink($tempAudio);
            Session::erase('temp_audio_nanogong');
        }
    }

    /**
     * Check if the path is used in this course.
     *
     * @param array  $courseInfo
     * @param string $path
     *
     * @return array
     */
    public static function getDocumentByPathInCourse($courseInfo, $path)
    {
        $table = Database::get_course_table(TABLE_DOCUMENT);
        $path = Database::escape_string($path);
        $courseId = $courseInfo['real_id'];
        if (empty($courseId)) {
            return false;
        }
        $sql = "SELECT * FROM $table WHERE c_id = $courseId AND path = '$path'";
        $result = Database::query($sql);

        return Database::store_result($result, 'ASSOC');
    }

    /**
     * @param array $_course
     *
     * @return int
     */
    public static function createDefaultAudioFolder($_course)
    {
        if (!isset($_course['path'])) {
            return false;
        }

        $audioId = null;
        $path = api_get_path(SYS_COURSE_PATH).$_course['path'].'/document/';
        if (!is_dir($path.'audio')) {
            mkdir($path.'audio', api_get_permissions_for_new_directories());
            self::addDocument($_course, '/audio', 'folder', 0, 'Audio');
        }

        return $audioId;
    }

    /**
     * Generate a default certificate for a courses.
     *
     * @todo move to certificate lib
     *
     * @global string $css CSS directory
     * @global string $img_dir image directory
     * @global string $default_course_dir Course directory
     * @global string $js JS directory
     *
     * @param array $courseData     The course info
     * @param bool  $fromBaseCourse
     * @param int   $sessionId
     */
    public static function generateDefaultCertificate(
        $courseData,
        $fromBaseCourse = false,
        $sessionId = 0
    ) {
        if (empty($courseData)) {
            return false;
        }

        global $css, $img_dir, $default_course_dir, $js;
        $codePath = api_get_path(REL_CODE_PATH);
        $dir = '/certificates';
        $comment = null;
        $title = get_lang('Default certificate');
        $fileName = api_replace_dangerous_char($title);
        $filePath = api_get_path(SYS_COURSE_PATH)."{$courseData['directory']}/document$dir";

        if (!is_dir($filePath)) {
            mkdir($filePath, api_get_permissions_for_new_directories());
        }

        $fileFullPath = "$filePath/$fileName.html";
        $fileType = 'file';
        $templateContent = file_get_contents(api_get_path(SYS_CODE_PATH).'gradebook/certificate_template/template.html');

        $search = ['{CSS}', '{IMG_DIR}', '{REL_CODE_PATH}', '{COURSE_DIR}'];
        $replace = [$css.$js, $img_dir, $codePath, $default_course_dir];

        $fileContent = str_replace($search, $replace, $templateContent);
        $saveFilePath = "$dir/$fileName.html";

        if ($fromBaseCourse) {
            $defaultCertificateId = self::get_default_certificate_id($courseData['real_id'], 0);
            if (!empty($defaultCertificateId)) {
                // We have a certificate from the course base
                $documentData = self::get_document_data_by_id(
                    $defaultCertificateId,
                    $courseData['code'],
                    false,
                    0
                );

                if ($documentData) {
                    $fileContent = file_get_contents($documentData['absolute_path']);
                }
            }
        }

        $document = self::addDocument(
            $courseData,
            $saveFilePath,
            $fileType,
            '',
            $title,
            $comment,
            0, //$readonly = 0,
            true, //$save_visibility = true,
            null, //$group_id = null,
            $sessionId,
            0,
            false,
            $fileContent
        );

        /*api_item_property_update(
            $courseData,
            TOOL_DOCUMENT,
            $documentId,
            'DocumentAdded',
            api_get_user_id(),
            null,
            null,
            null,
            null,
            $sessionId
        );*/

        $defaultCertificateId = self::get_default_certificate_id($courseData['real_id'], $sessionId);

        if (!isset($defaultCertificateId)) {
            self::attach_gradebook_certificate(
                $courseData['real_id'],
                $document->getId(),
                $sessionId
            );
        }
    }

    /**
     * Update the document name.
     *
     * @param int    $documentId The document id
     * @param string $newName    The new name
     */
    public static function renameDocument($documentId, $newName)
    {
        $documentId = intval($documentId);
        $newName = Database::escape_string($newName);
        $docuentTable = Database::get_course_table(TABLE_DOCUMENT);

        $values = [
            'title' => $newName,
        ];

        $whereConditions = [
            'id = ?' => $documentId,
        ];

        Database::update($docuentTable, $values, $whereConditions);
    }

    /**
     * Get folder/file suffix.
     *
     * @param array $courseInfo
     * @param int   $sessionId
     * @param int   $groupId
     *
     * @return string
     */
    public static function getDocumentSuffix($courseInfo, $sessionId, $groupId)
    {
        // If no session or group, then no suffix.
        if (empty($sessionId) && empty($groupId)) {
            return '';
        }

        return '__'.(int) $sessionId.'__'.(int) $groupId;
    }

    /**
     * Fix a document name adding session id and group id
     * Turns picture.jpg -> picture__1__2.jpg
     * Where 1 = session id and 2 group id
     * Of session id and group id are empty then the function returns:
     * picture.jpg ->  picture.jpg.
     *
     * @param string $name       folder or file name
     * @param string $type       'folder' or 'file'
     * @param array  $courseInfo
     * @param int    $sessionId
     * @param int    $groupId
     *
     * @return string
     */
    public static function fixDocumentName($name, $type, $courseInfo, $sessionId, $groupId)
    {
        $suffix = self::getDocumentSuffix($courseInfo, $sessionId, $groupId);

        switch ($type) {
            case 'folder':
                $name = $name.$suffix;
                break;
            case 'file':
                $name = self::addSuffixToFileName($name, $suffix);
                break;
        }

        return $name;
    }

    /**
     * Add a suffix to a file Example:
     * /folder/picture.jpg => to /folder/picture_this.jpg
     * where "_this" is the suffix.
     *
     * @param string $name
     * @param string $suffix
     *
     * @return string
     */
    public static function addSuffixToFileName($name, $suffix)
    {
        $extension = pathinfo($name, PATHINFO_EXTENSION);
        $fileName = pathinfo($name, PATHINFO_FILENAME);
        $dir = pathinfo($name, PATHINFO_DIRNAME);

        if ($dir == '.') {
            $dir = null;
        }

        if (!empty($dir) && $dir != '/') {
            $dir = $dir.'/';
        }

        $name = $dir.$fileName.$suffix.'.'.$extension;

        return $name;
    }

    /**
     * Check if folder exist in the course base or in the session course.
     *
     * @param string $folder     Example: /folder/folder2
     * @param array  $courseInfo
     * @param int    $sessionId
     * @param int    $groupId    group.id
     *
     * @return bool
     */
    public static function folderExists(
        $folder,
        $courseInfo,
        $sessionId,
        $groupId
    ) {
        $courseId = $courseInfo['real_id'];

        if (empty($courseId)) {
            return false;
        }

        $sessionId = (int) $sessionId;
        $folderWithSuffix = self::fixDocumentName(
            $folder,
            'folder',
            $courseInfo,
            $sessionId,
            $groupId
        );

        $folder = Database::escape_string($folder);
        $folderWithSuffix = Database::escape_string($folderWithSuffix);

        // Check if pathname already exists inside document table
        $tbl_document = Database::get_course_table(TABLE_DOCUMENT);
        $sql = "SELECT id, path FROM $tbl_document
                WHERE
                    filetype = 'folder' AND
                    c_id = $courseId AND
                    (path = '$folder' OR path = '$folderWithSuffix') AND
                    (session_id = 0 OR session_id IS NULL OR session_id = $sessionId)
        ";

        $rs = Database::query($sql);
        if (Database::num_rows($rs)) {
            return true;
        }

        return false;
    }

    /**
     * Check if file exist in the course base or in the session course.
     *
     * @param string $fileName   Example: /folder/picture.jpg
     * @param array  $courseInfo
     * @param int    $sessionId
     * @param int    $groupId
     *
     * @return bool
     */
    public static function documentExists(
        $fileName,
        $courseInfo,
        $sessionId,
        $groupId
    ) {
        $courseId = $courseInfo['real_id'];

        if (empty($courseId)) {
            return false;
        }

        $sessionId = (int) $sessionId;
        $fileNameEscape = Database::escape_string($fileName);

        $fileNameWithSuffix = self::fixDocumentName(
            $fileName,
            'file',
            $courseInfo,
            $sessionId,
            $groupId
        );

        $fileNameWithSuffix = Database::escape_string($fileNameWithSuffix);

        // Check if pathname already exists inside document table
        $table = Database::get_course_table(TABLE_DOCUMENT);
        $sql = "SELECT id, path FROM $table
                WHERE
                    filetype = 'file' AND
                    c_id = $courseId AND
                    (
                        path = '".$fileNameEscape."' OR
                        path = '$fileNameWithSuffix'
                    ) AND
                    (session_id = 0 OR session_id = $sessionId)
        ";
        $rs = Database::query($sql);
        if (Database::num_rows($rs)) {
            return true;
        }

        return false;
    }

    /**
     * Undo the suffix applied to a file example:
     * turns picture__1__1.jpg to picture.jpg.
     *
     * @param string $name
     * @param int    $courseId
     * @param int    $sessionId
     * @param int    $groupId
     *
     * @return string
     */
    public static function undoFixDocumentName(
        $name,
        $courseId,
        $sessionId,
        $groupId
    ) {
        if (empty($sessionId) && empty($groupId)) {
            return $name;
        }

        $suffix = self::getDocumentSuffix(
            ['real_id' => $courseId],
            $sessionId,
            $groupId
        );

        $name = str_replace($suffix, '', $name);

        return $name;
    }

    /**
     * @param string $path
     * @param string $name
     * @param array  $courseInfo
     * @param int    $sessionId
     * @param int    $groupId
     *
     * @return string
     */
    public static function getUniqueFileName($path, $name, $courseInfo, $sessionId, $groupId)
    {
        $counter = 1;
        $filePath = $path.$name;
        $uniqueName = $name;
        while ($documentExists = self::documentExists(
            $filePath,
            $courseInfo,
            $sessionId,
            $groupId
        )) {
            $uniqueName = self::addSuffixToFileName($name, '_'.$counter);
            $filePath = $path.$uniqueName;
            $counter++;
        }

        return $uniqueName;
    }

    /**
     * Builds the form that enables the user to
     * select a directory to browse/upload in.
     *
     * @param array    An array containing the folders we want to be able to select
     * @param string    The current folder (path inside of the "document" directory, including the prefix "/")
     * @param string    Group directory, if empty, prevents documents to be uploaded
     * (because group documents cannot be uploaded in root)
     * @param bool    Whether to change the renderer (this will add a template <span>
     * to the QuickForm object displaying the form)
     *
     * @return string html form
     */
    public static function build_directory_selector(
        $folders,
        $document_id,
        $group_dir = '',
        $change_renderer = false,
        &$form = null,
        $selectName = 'id'
    ) {
        $doc_table = Database::get_course_table(TABLE_DOCUMENT);
        $course_id = api_get_course_int_id();
        $folder_titles = [];

        if (is_array($folders)) {
            $escaped_folders = [];
            foreach ($folders as $key => &$val) {
                $escaped_folders[$key] = Database::escape_string($val);
            }
            $folder_sql = implode("','", $escaped_folders);

            $sql = "SELECT path, title 
                    FROM $doc_table
                    WHERE 
                        filetype = 'folder' AND 
                        c_id = $course_id AND 
                        path IN ('".$folder_sql."')";
            $res = Database::query($sql);
            $folder_titles = [];
            while ($obj = Database::fetch_object($res)) {
                $folder_titles[$obj->path] = $obj->title;
            }
        }

        $attributes = [];
        if (empty($form)) {
            $form = new FormValidator('selector', 'GET', api_get_self().'?'.api_get_cidreq());
            $attributes = ['onchange' => 'javascript: document.selector.submit();'];
        }
        $form->addElement('hidden', 'cidReq', api_get_course_id());
        $form->addElement('hidden', 'id_session', api_get_session_id());
        $form->addElement('hidden', 'gidReq', api_get_group_id());

        $parent_select = $form->addSelect(
            $selectName,
            get_lang('Current folder'),
            '',
            $attributes
        );

        // Group documents cannot be uploaded in the root
        if (empty($group_dir)) {
            $parent_select->addOption(get_lang('Documents'), '/');

            if (is_array($folders)) {
                foreach ($folders as $folder_id => &$folder) {
                    $selected = ($document_id == $folder_id) ? ' selected="selected"' : '';
                    $path_parts = explode('/', $folder);
                    $folder_titles[$folder] = cut($folder_titles[$folder], 80);
                    $counter = count($path_parts) - 2;
                    if ($counter > 0) {
                        $label = str_repeat('&nbsp;&nbsp;&nbsp;', $counter).' &mdash; '.$folder_titles[$folder];
                    } else {
                        $label = ' &mdash; '.$folder_titles[$folder];
                    }
                    $parent_select->addOption($label, $folder_id);
                    if ($selected != '') {
                        $parent_select->setSelected($folder_id);
                    }
                }
            }
        } else {
            if (!empty($folders)) {
                foreach ($folders as $folder_id => &$folder) {
                    $selected = ($document_id == $folder_id) ? ' selected="selected"' : '';
                    $label = $folder_titles[$folder];
                    if ($folder == $group_dir) {
                        $label = get_lang('Documents');
                    } else {
                        $path_parts = explode('/', str_replace($group_dir, '', $folder));
                        $label = cut($label, 80);
                        $label = str_repeat('&nbsp;&nbsp;&nbsp;', count($path_parts) - 2).' &mdash; '.$label;
                    }
                    $parent_select->addOption($label, $folder_id);
                    if ($selected != '') {
                        $parent_select->setSelected($folder_id);
                    }
                }
            }
        }

        $html = $form->toHtml();

        return $html;
    }

    /**
     * Create a html hyperlink depending on if it's a folder or a file.
     *
     * @param string $documentWebPath
     * @param array  $document_data
     * @param bool   $show_as_icon      - if it is true, only a clickable icon will be shown
     * @param int    $visibility        (1/0)
     * @param int    $size
     * @param bool   $isAllowedToEdit
     * @param bool   $isCertificateMode
     * @param bool   $addToEditor
     * @param string $editorUrl
     *
     * @return string url
     */
    public static function create_document_link(
        $documentWebPath,
        $document_data,
        $show_as_icon = false,
        $visibility,
        $size = 0,
        $isAllowedToEdit = false,
        $isCertificateMode = false,
        $addToEditor = false,
        $editorUrl = ''
    ) {
        global $dbl_click_id;

        $sessionId = api_get_session_id();
        $courseParams = api_get_cidreq();
        $courseCode = api_get_course_id();
        $webODFList = self::get_web_odf_extension_list();

        // Get the title or the basename depending on what we're using
        if ($document_data['title'] != '') {
            $title = $document_data['title'];
        } else {
            $title = basename($document_data['path']);
        }

        $isAdmin = api_is_platform_admin();

        $filetype = $document_data['filetype'];
        $path = $document_data['path'];
        $url_path = urlencode($document_data['path']);

        $basePageUrl = api_get_path(WEB_CODE_PATH).'document/';
        $pageUrl = $basePageUrl.'document.php';

        // Add class="invisible" on invisible files
        $classAddToEditor = '';
        if ($addToEditor) {
            $classAddToEditor = 'select_to_ckeditor';
        }
        $visibility_class = $visibility === false ? ' class="muted"' : ' class="'.$classAddToEditor.'" ';

        $forcedownload_link = '';
        $forcedownload_icon = '';
        $prevent_multiple_click = '';
        $force_download_html = '';

        if (!$show_as_icon) {
            // Build download link (icon)
            $forcedownload_link = $filetype === 'folder'
                ? $pageUrl.'?'.$courseParams.'&action=downloadfolder&id='.$document_data['id']
                : $pageUrl.'?'.$courseParams.'&action=download&id='.$document_data['id'];
            // Folder download or file download?
            $forcedownload_icon = $filetype === 'folder' ? 'save_pack.png' : 'save.png';
            // Prevent multiple clicks on zipped folder download
            $prevent_multiple_click = $filetype === 'folder' ? " onclick=\"javascript: if(typeof clic_$dbl_click_id == 'undefined' || !clic_$dbl_click_id) { clic_$dbl_click_id=true; window.setTimeout('clic_".($dbl_click_id++)."=false;',10000); } else { return false; }\"" : '';
        }

        $target = '_self';
        $is_browser_viewable_file = false;

        if ($filetype === 'file') {
            // Check the extension
            $ext = explode('.', $path);
            $ext = strtolower($ext[count($ext) - 1]);

            // HTML-files an some other types are shown in a frameset by default.
            $is_browser_viewable_file = self::isBrowserViewable($ext);
            if ($is_browser_viewable_file) {
                if ($ext == 'pdf' || in_array($ext, $webODFList)) {
                    $url = $pageUrl.'?'.$courseParams.'&action=download&amp;id='.$document_data['id'];
                } else {
                    $url = $basePageUrl.'showinframes.php?'.$courseParams.'&id='.$document_data['id'];
                }
            } else {
                $url = $documentWebPath.str_replace('%2F', '/', $url_path).'?'.$courseParams;
            }
        } else {
            $url = $pageUrl.'?'.$courseParams.'&id='.$document_data['id'];
        }

        if ($isCertificateMode) {
            $url .= '&certificate=true&selectcat='.(isset($_GET['selectcat']) ? $_GET['selectcat'] : '');
        }

        // The little download icon
        $tooltip_title = $title;
        $tooltip_title_alt = $tooltip_title;

        if ($filetype == 'link') {
            $tooltip_title_alt = $title;
            $url = $document_data['comment'].'" target="_blank';
        }

        if ($path === '/shared_folder') {
            $tooltip_title_alt = get_lang('Folders of users');
        } elseif (strstr($path, 'shared_folder_session_')) {
            $tooltip_title_alt = get_lang('Folders of users').' ('.api_get_session_name($sessionId).')';
        } elseif (strstr($tooltip_title, 'sf_user_')) {
            $userinfo = api_get_user_info(substr($tooltip_title, 8));
            $tooltip_title_alt = get_lang('User folder').' '.$userinfo['complete_name'];
        } elseif ($path == '/chat_files') {
            $tooltip_title_alt = get_lang('Chat conversations history');
        } elseif ($path == '/learning_path') {
            $tooltip_title_alt = get_lang('Learning paths');
        } elseif ($path == '/video') {
            $tooltip_title_alt = get_lang('Video');
        } elseif ($path == '/audio') {
            $tooltip_title_alt = get_lang('Audio');
        } elseif ($path == '/flash') {
            $tooltip_title_alt = get_lang('Flash');
        } elseif ($path == '/images') {
            $tooltip_title_alt = get_lang('Images');
        } elseif ($path == '/images/gallery') {
            $tooltip_title_alt = get_lang('Gallery');
        }

        $copyToMyFiles = $open_in_new_window_link = '';
        $curdirpath = isset($_GET['curdirpath']) ? Security::remove_XSS($_GET['curdirpath']) : null;
        $send_to = null;
        $checkExtension = $path;
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $document_data['file_extension'] = $extension;

        if (!$show_as_icon) {
            if ($filetype === 'folder') {
                if ($isAllowedToEdit ||
                    $isAdmin ||
                    api_get_setting('students_download_folders') == 'true'
                ) {
                    // filter: when I am into a shared folder, I can only show "my shared folder" for donwload
                    if (self::is_shared_folder($curdirpath, $sessionId)) {
                        if (preg_match('/shared_folder\/sf_user_'.api_get_user_id().'$/', urldecode($forcedownload_link)) ||
                            preg_match('/shared_folder_session_'.$sessionId.'\/sf_user_'.api_get_user_id().'$/', urldecode($forcedownload_link)) ||
                            $isAllowedToEdit || $isAdmin
                        ) {
                            $force_download_html = ($size == 0) ? '' : '<a href="'.$forcedownload_link.'" style="float:right"'.$prevent_multiple_click.'>'.
                                Display::return_icon($forcedownload_icon, get_lang('Download'), [], ICON_SIZE_SMALL).'</a>';
                        }
                    } elseif (!preg_match('/shared_folder/', urldecode($forcedownload_link)) ||
                        $isAllowedToEdit ||
                        $isAdmin
                    ) {
                        $force_download_html = ($size == 0) ? '' : '<a href="'.$forcedownload_link.'" style="float:right"'.$prevent_multiple_click.'>'.
                            Display::return_icon($forcedownload_icon, get_lang('Download'), [], ICON_SIZE_SMALL).'</a>';
                    }
                }
            } else {
                $force_download_html = $size == 0 ? '' : '<a href="'.$forcedownload_link.'" style="float:right"'.$prevent_multiple_click.' download="'.$document_data['basename'].'">'.
                    Display::return_icon($forcedownload_icon, get_lang('Download'), [], ICON_SIZE_SMALL).'</a>';
            }

            $pdf_icon = '';
            if (!$isAllowedToEdit &&
                api_get_setting('students_export2pdf') == 'true' &&
                $filetype === 'file' &&
                in_array($extension, ['html', 'htm'])
            ) {
                $pdf_icon = ' <a style="float:right".'.$prevent_multiple_click.' href="'.$pageUrl.'?'.$courseParams.'&action=export_to_pdf&id='.$document_data['id'].'&curdirpath='.$curdirpath.'">'.
                    Display::return_icon('pdf.png', get_lang('Export to PDF format'), [], ICON_SIZE_SMALL).'</a> ';
            }

            if ($is_browser_viewable_file) {
                $open_in_new_window_link = '<a href="'.$documentWebPath.str_replace('%2F', '/', $url_path).'?'.$courseParams.'" style="float:right"'.$prevent_multiple_click.' target="_blank">'.
                    Display::return_icon('open_in_new_window.png', get_lang('Open in a new window'), [], ICON_SIZE_SMALL).'&nbsp;&nbsp;</a>';
            }

            if ($addToEditor) {
                $force_download_html = '';
                $open_in_new_window_link = '';
                $send_to = '';
                $pdf_icon = '';
                if ($filetype === 'folder') {
                    $url = $editorUrl.'/'.$document_data['id'].'/?'.api_get_cidreq();
                } else {
                    $url = $documentWebPath.str_replace('%2F', '/', $url_path).'?'.$courseParams;
                }
            }

            if ($filetype === 'file') {
                // Sound preview
                if (preg_match('/mp3$/i', urldecode($checkExtension)) ||
                    (preg_match('/wav$/i', urldecode($checkExtension))) ||
                    preg_match('/ogg$/i', urldecode($checkExtension))
                ) {
                    return '<span style="float:left" '.$visibility_class.'>'.
                    $title.
                    '</span>'.$force_download_html.$send_to.$copyToMyFiles.$open_in_new_window_link.$pdf_icon;
                } elseif (
                    // Show preview
                    preg_match('/swf$/i', urldecode($checkExtension)) ||
                    preg_match('/png$/i', urldecode($checkExtension)) ||
                    preg_match('/gif$/i', urldecode($checkExtension)) ||
                    preg_match('/jpg$/i', urldecode($checkExtension)) ||
                    preg_match('/jpeg$/i', urldecode($checkExtension)) ||
                    preg_match('/bmp$/i', urldecode($checkExtension)) ||
                    preg_match('/svg$/i', urldecode($checkExtension))
                ) {
                    // Simpler version of showinframesmin.php with no headers
                    $url = 'show_content.php?'.$courseParams.'&id='.$document_data['id'];
                    $class = 'ajax ';
                    if ($addToEditor) {
                        $class = $classAddToEditor;
                        $url = $documentWebPath.str_replace('%2F', '/', $url_path).'?'.$courseParams;
                    }
                    $url = $documentWebPath.str_replace('%2F', '/', $url_path).'?'.$courseParams;
                    $url_path = str_replace('%2F', '/', $url_path);
                    $url = api_get_path(WEB_PUBLIC_PATH)."courses/$courseCode/document$url_path?type=show";

                    if ($visibility == false) {
                        $class = ' ajax text-muted ';
                        if ($addToEditor) {
                            $class = ' text-muted not_select_to_ckeditor';
                        }
                    }

                    return Display::url(
                        $title,
                        $url,
                        [
                            'class' => $class,
                            'title' => $tooltip_title_alt,
                            'data-title' => $title,
                            'style' => 'float:left;',
                        ]
                    )
                    .$force_download_html.$send_to.$copyToMyFiles
                    .$open_in_new_window_link.$pdf_icon;
                } else {
                    // For a "PDF Download" of the file.
                    $pdfPreview = null;
                    if ($ext != 'pdf' && !in_array($ext, $webODFList)) {
                        $url = $basePageUrl.'showinframes.php?'.$courseParams.'&id='.$document_data['id'];
                    } else {
                        $pdfPreview = Display::url(
                            Display::return_icon('preview.png', get_lang('Preview'), null, ICON_SIZE_SMALL),
                            api_get_path(WEB_CODE_PATH).'document/showinframes.php?'.$courseParams.'&id='.$document_data['id'],
                            ['style' => 'float:right']
                        );
                    }
                    // No plugin just the old and good showinframes.php page
                    return '<a href="'.$url.'" title="'.$tooltip_title_alt.'" style="float:left" '.$visibility_class.' >'.$title.'</a>'.
                    $pdfPreview.$force_download_html.$send_to.$copyToMyFiles.$open_in_new_window_link.$pdf_icon;
                }
            } else {
                return '<a href="'.$url.'" title="'.$tooltip_title_alt.'" '.$visibility_class.' style="float:left">'.$title.'</a>'.
                $force_download_html.$send_to.$copyToMyFiles.$open_in_new_window_link.$pdf_icon;
            }
        } else {
            $urlDecoded = urldecode($checkExtension);
            // Icon column
            if (preg_match('/shared_folder/', $urlDecoded) &&
                preg_match('/shared_folder$/', $urlDecoded) == false &&
                preg_match('/shared_folder_session_'.$sessionId.'$/', urldecode($url)) == false
            ) {
                if ($filetype === 'file') {
                    // Sound preview
                    if (preg_match('/mp3$/i', $urlDecoded) ||
                        preg_match('/wav$/i', $urlDecoded) ||
                        preg_match('/ogg$/i', $urlDecoded)
                    ) {
                        $soundPreview = self::generateAudioPreview($documentWebPath, $document_data);

                        return $soundPreview;
                    } elseif (
                        // Show preview
                        preg_match('/swf$/i', $urlDecoded) ||
                        preg_match('/png$/i', $urlDecoded) ||
                        preg_match('/gif$/i', $urlDecoded) ||
                        preg_match('/jpg$/i', $urlDecoded) ||
                        preg_match('/jpeg$/i', $urlDecoded) ||
                        preg_match('/bmp$/i', $urlDecoded) ||
                        preg_match('/svg$/i', $urlDecoded)
                    ) {
                        $url = $basePageUrl.'showinframes.php?'.$courseParams.'&id='.$document_data['id'];

                        return '<a href="'.$url.'" title="'.$tooltip_title_alt.'" '.$visibility_class.' style="float:left">'.
                            self::build_document_icon_tag($filetype, $path, $isAllowedToEdit).
                            Display::return_icon('shared.png', get_lang('Resource shared'), []).
                        '</a>';
                    } else {
                        return '<a href="'.$url.'" title="'.$tooltip_title_alt.'" '.$visibility_class.' style="float:left">'.
                            self::build_document_icon_tag($filetype, $path, $isAllowedToEdit).
                            Display::return_icon('shared.png', get_lang('Resource shared'), []).
                        '</a>';
                    }
                } else {
                    return '<a href="'.$url.'" title="'.$tooltip_title_alt.'" target="'.$target.'"'.$visibility_class.' style="float:left">'.
                        self::build_document_icon_tag($filetype, $path, $isAllowedToEdit).
                        Display::return_icon('shared.png', get_lang('Resource shared'), []).
                    '</a>';
                }
            } else {
                if ($filetype === 'file') {
                    // Sound preview with jplayer
                    if (preg_match('/mp3$/i', $urlDecoded) ||
                        (preg_match('/wav$/i', $urlDecoded)) ||
                        preg_match('/ogg$/i', $urlDecoded)) {
                        $soundPreview = self::generateAudioPreview($documentWebPath, $document_data);

                        return $soundPreview;
                    } elseif (
                        //Show preview
                        preg_match('/html$/i', $urlDecoded) ||
                        preg_match('/htm$/i', $urlDecoded) ||
                        preg_match('/swf$/i', $urlDecoded) ||
                        preg_match('/png$/i', $urlDecoded) ||
                        preg_match('/gif$/i', $urlDecoded) ||
                        preg_match('/jpg$/i', $urlDecoded) ||
                        preg_match('/jpeg$/i', $urlDecoded) ||
                        preg_match('/bmp$/i', $urlDecoded) ||
                        preg_match('/svg$/i', $urlDecoded)
                    ) {
                        $url = $basePageUrl.'showinframes.php?'.$courseParams.'&id='.$document_data['id']; //without preview
                        return '<a href="'.$url.'" title="'.$tooltip_title_alt.'" '.$visibility_class.' style="float:left">'.
                            self::build_document_icon_tag($filetype, $path, $isAllowedToEdit).
                        '</a>';
                    } else {
                        return '<a href="'.$url.'" title="'.$tooltip_title_alt.'" '.$visibility_class.' style="float:left">'.
                            self::build_document_icon_tag($filetype, $path, $isAllowedToEdit).
                        '</a>';
                    }
                } else {
                    return '<a href="'.$url.'" title="'.$tooltip_title_alt.'" target="'.$target.'"'.$visibility_class.' style="float:left">'.
                        self::build_document_icon_tag($filetype, $path, $isAllowedToEdit).
                    '</a>';
                }
            }
        }
    }

    /**
     * Builds an img html tag for the file type.
     *
     * @param string $type            (file/folder)
     * @param string $path
     * @param bool   $isAllowedToEdit
     *
     * @return string img html tag
     */
    public static function build_document_icon_tag($type, $path, $isAllowedToEdit = null)
    {
        $basename = basename($path);
        $sessionId = api_get_session_id();
        if (is_null($isAllowedToEdit)) {
            $isAllowedToEdit = api_is_allowed_to_edit(null, true);
        }
        $user_image = false;
        if ($type == 'file') {
            $icon = choose_image($basename);
            $basename = substr(strrchr($basename, '.'), 1);
        } elseif ($type == 'link') {
            $icon = 'clouddoc.png';
            $basename = get_lang('Cloud file link');
        } else {
            if ($path == '/shared_folder') {
                $icon = 'folder_users.png';
                if ($isAllowedToEdit) {
                    $basename = get_lang('INFORMATION VISIBLE TO THE TEACHER ONLY:
The users folder contains a folder for each user who has accessed it through the documents tool, or when any file has been sent in the course through the online editor. If neither circumstances has occurred, then no user folder will have been created. In the case of groups, files that are sent through the editor will be added in the folder of each group, which is only accessible by students from this group.');
                } else {
                    $basename = get_lang('Folders of users');
                }
            } elseif (strstr($basename, 'sf_user_')) {
                $userInfo = api_get_user_info(substr($basename, 8));
                $icon = $userInfo['avatar_small'];
                $basename = get_lang('User folder').' '.$userInfo['complete_name'];
                $user_image = true;
            } elseif (strstr($path, 'shared_folder_session_')) {
                $sessionName = api_get_session_name($sessionId);
                if ($isAllowedToEdit) {
                    $basename = '***('.$sessionName.')*** '.get_lang('INFORMATION VISIBLE TO THE TEACHER ONLY:
The users folder contains a folder for each user who has accessed it through the documents tool, or when any file has been sent in the course through the online editor. If neither circumstances has occurred, then no user folder will have been created. In the case of groups, files that are sent through the editor will be added in the folder of each group, which is only accessible by students from this group.');
                } else {
                    $basename = get_lang('Folders of users').' ('.$sessionName.')';
                }
                $icon = 'folder_users.png';
            } else {
                $icon = 'folder_document.png';

                if ($path == '/audio') {
                    $icon = 'folder_audio.png';
                    if ($isAllowedToEdit) {
                        $basename = get_lang('INFORMATION VISIBLE TO THE TEACHER ONLY:
This folder contains the default archives. You can clear files or add new ones, but if a file is hidden when it is inserted in a web document, the students will not be able to see it in this document. When inserting a file in a web document, first make sure it is visible. The folders can remain hidden.');
                    } else {
                        $basename = get_lang('Audio');
                    }
                } elseif ($path == '/flash') {
                    $icon = 'folder_flash.png';
                    if ($isAllowedToEdit) {
                        $basename = get_lang('INFORMATION VISIBLE TO THE TEACHER ONLY:
This folder contains the default archives. You can clear files or add new ones, but if a file is hidden when it is inserted in a web document, the students will not be able to see it in this document. When inserting a file in a web document, first make sure it is visible. The folders can remain hidden.');
                    } else {
                        $basename = get_lang('Flash');
                    }
                } elseif ($path == '/images') {
                    $icon = 'folder_images.png';
                    if ($isAllowedToEdit) {
                        $basename = get_lang('INFORMATION VISIBLE TO THE TEACHER ONLY:
This folder contains the default archives. You can clear files or add new ones, but if a file is hidden when it is inserted in a web document, the students will not be able to see it in this document. When inserting a file in a web document, first make sure it is visible. The folders can remain hidden.');
                    } else {
                        $basename = get_lang('Images');
                    }
                } elseif ($path == '/video') {
                    $icon = 'folder_video.png';
                    if ($isAllowedToEdit) {
                        $basename = get_lang('INFORMATION VISIBLE TO THE TEACHER ONLY:
This folder contains the default archives. You can clear files or add new ones, but if a file is hidden when it is inserted in a web document, the students will not be able to see it in this document. When inserting a file in a web document, first make sure it is visible. The folders can remain hidden.');
                    } else {
                        $basename = get_lang('Video');
                    }
                } elseif ($path == '/images/gallery') {
                    $icon = 'folder_gallery.png';
                    if ($isAllowedToEdit) {
                        $basename = get_lang('INFORMATION VISIBLE TO THE TEACHER ONLY:
This folder contains the default archives. You can clear files or add new ones, but if a file is hidden when it is inserted in a web document, the students will not be able to see it in this document. When inserting a file in a web document, first make sure it is visible. The folders can remain hidden.');
                    } else {
                        $basename = get_lang('Gallery');
                    }
                } elseif ($path == '/chat_files') {
                    $icon = 'folder_chat.png';
                    if ($isAllowedToEdit) {
                        $basename = get_lang('INFORMATION VISIBLE TO THE TEACHER ONLY:
This folder contains all sessions that have been opened in the chat. Although the chat sessions can often be trivial, others can be really interesting and worthy of being incorporated as an additional work document. To do this without changing the visibility of this folder, make the file visible and link it from where you deem appropriate. It is not recommended to make this folder visible to all.');
                    } else {
                        $basename = get_lang('Chat conversations history');
                    }
                } elseif ($path == '/learning_path') {
                    $icon = 'folder_learningpath.png';
                    if ($isAllowedToEdit) {
                        $basename = get_lang('HelpFolderLearning paths');
                    } else {
                        $basename = get_lang('Learning paths');
                    }
                }
            }
        }

        if ($user_image) {
            return Display::img($icon, $basename, [], false);
        }

        return Display::return_icon($icon, $basename, [], ICON_SIZE_SMALL);
    }

    /**
     * Creates the row of edit icons for a file/folder.
     *
     * @param array $document_data
     * @param int   $id
     * @param bool  $is_template
     * @param int   $visibility    (1/0)
     *
     * @return string html img tags with hyperlinks
     */
    public static function build_edit_icons($document_data, $id, $is_template, $visibility)
    {
        $sessionId = api_get_session_id();
        $courseParams = api_get_cidreq();
        $document_id = $document_data['id'];
        $type = $document_data['filetype'];
        $is_read_only = $document_data['readonly'];
        $path = $document_data['path'];

        if ($type == 'link') {
            $parent_id = self::get_document_id(
                api_get_course_info(),
                rtrim($path, '/'),
                0
            );
        } else {
            $parent_id = self::get_document_id(
                api_get_course_info(),
                dirname($path),
                0
            );
        }

        if (empty($parent_id) && !empty($sessionId)) {
            $parent_id = self::get_document_id(
                api_get_course_info(),
                dirname($path),
                $sessionId
            );
        }

        $curdirpath = dirname($document_data['path']);
        $is_certificate_mode = self::is_certificate_mode($path);
        $curdirpath = urlencode($curdirpath);
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        //@todo Implement remote support for converter
        $usePpt2lp = api_get_setting('service_ppt2lp', 'active') == 'true' && api_get_setting('service_ppt2lp', 'host') == 'localhost';
        $formatTypeList = self::getFormatTypeListConvertor('from', $extension);
        $formatType = current($formatTypeList);

        // If document is read only *or* we're in a session and the document
        // is from a non-session context, hide the edition capabilities
        $modify_icons = [];
        $modify_icons[] = self::getButtonEdit($is_read_only, $document_data, $extension, $is_certificate_mode);
        $modify_icons[] = self::getButtonMove($is_read_only, $document_data, $is_certificate_mode, $parent_id);
        $modify_icons[] = self::getButtonVisibility(
            $is_read_only,
            $visibility,
            $document_data,
            $is_certificate_mode,
            $parent_id
        );
        $modify_icons[] = self::getButtonDelete(
            $is_read_only,
            $document_data,
            $is_certificate_mode,
            $curdirpath,
            $parent_id
        );

        if (!$is_read_only /* or ($session_id!=api_get_session_id()) */) {
            // Add action to covert to PDF, will create a new document whit same filename but .pdf extension
            // @TODO: add prompt to select a format target
            if (!in_array($path, self::get_system_folders())) {
                if ($usePpt2lp && $formatType) {
                    $modify_icons[] = Display::url(
                        Display::return_icon('convert.png', get_lang('Convert')),
                        '#',
                        ['class' => 'convertAction', 'data-documentId' => $document_id, 'data-formatType' => $formatType]
                    );
                }
            }
        }

        if ($type == 'file' && ($extension == 'html' || $extension == 'htm')) {
            if ($is_template == 0) {
                if ((isset($_GET['curdirpath']) && $_GET['curdirpath'] != '/certificates') || !isset($_GET['curdirpath'])) {
                    $modify_icons[] = Display::url(
                        Display::return_icon('wizard.png', get_lang('Add as a template')),
                        api_get_self()."?$courseParams&curdirpath=$curdirpath&add_as_template=$id"
                    );
                }
                if ((isset($_GET['curdirpath']) && $_GET['curdirpath'] == '/certificates') || $is_certificate_mode) {//allow attach certificate to course
                    $visibility_icon_certificate = 'nocertificate';
                    if (self::get_default_certificate_id(api_get_course_int_id()) == $id) {
                        $visibility_icon_certificate = 'certificate';
                        $certificate = get_lang('Default certificate');
                        $preview = get_lang('Preview certificate');
                        $is_preview = true;
                    } else {
                        $is_preview = false;
                        $certificate = get_lang('NoDefault certificate');
                    }
                    if (isset($_GET['selectcat'])) {
                        $modify_icons[] = Display::url(
                            Display::return_icon($visibility_icon_certificate.'.png', $certificate),
                            api_get_self()."?$courseParams&curdirpath=$curdirpath&selectcat=".intval($_GET['selectcat'])."&set_certificate=$id"
                        );
                        if ($is_preview) {
                            $modify_icons[] = Display::url(
                                Display::return_icon('preview_view.png', $preview),
                                api_get_self()."?$courseParams&curdirpath=$curdirpath&set_preview=$id"
                            );
                        }
                    }
                }
            } else {
                $modify_icons[] = Display::url(
                    Display::return_icon('wizard_na.png', get_lang('Remove template')),
                    api_get_self()."?$courseParams&curdirpath=$curdirpath&remove_as_template=$id"
                );
            }

            $modify_icons[] = Display::url(
                Display::return_icon('pdf.png', get_lang('Export to PDF format')),
                api_get_self()."?$courseParams&action=export_to_pdf&id=$id&curdirpath=$curdirpath"
            );
        }

        return implode(PHP_EOL, $modify_icons);
    }

    /**
     * @param $folders
     * @param $curdirpath
     * @param $move_file
     * @param string $group_dir
     *
     * @return string
     */
    public static function build_move_to_selector($folders, $curdirpath, $move_file, $group_dir = '')
    {
        $form = new FormValidator('move_to', 'post', api_get_self().'?'.api_get_cidreq());

        // Form title
        $form->addHidden('move_file', $move_file);

        $options = [];

        // Group documents cannot be uploaded in the root
        if ($group_dir == '') {
            if ($curdirpath != '/') {
                $options['/'] = get_lang('Documents');
            }

            if (is_array($folders)) {
                foreach ($folders as &$folder) {
                    // Hide some folders
                    if ($folder == '/HotPotatoes_files' ||
                        $folder == '/certificates' ||
                        basename($folder) == 'css'
                    ) {
                        continue;
                    }
                    // Admin setting for Hide/Show the folders of all users
                    if (api_get_setting('show_users_folders') == 'false' &&
                        (strstr($folder, '/shared_folder') || strstr($folder, 'shared_folder_session_'))
                    ) {
                        continue;
                    }

                    // Admin setting for Hide/Show Default folders to all users
                    if (api_get_setting('show_default_folders') == 'false' &&
                        (
                            $folder == '/images' ||
                            $folder == '/flash' ||
                            $folder == '/audio' ||
                            $folder == '/video' ||
                            strstr($folder, '/images/gallery') ||
                            $folder == '/video/flv'
                        )
                    ) {
                        continue;
                    }

                    // Admin setting for Hide/Show chat history folder
                    if (api_get_setting('show_chat_folder') == 'false' &&
                        $folder == '/chat_files') {
                        continue;
                    }

                    // You cannot move a file to:
                    // 1. current directory
                    // 2. inside the folder you want to move
                    // 3. inside a subfolder of the folder you want to move
                    if (($curdirpath != $folder) &&
                        ($folder != $move_file) &&
                        (substr($folder, 0, strlen($move_file) + 1) != $move_file.'/')
                    ) {
                        // If document title is used, we have to display titles instead of real paths...
                        $path_displayed = self::get_titles_of_path($folder);
                        if (empty($path_displayed)) {
                            $path_displayed = get_lang('Untitled');
                        }
                        $options[$folder] = $path_displayed;
                    }
                }
            }
        } else {
            foreach ($folders as $folder) {
                if (($curdirpath != $folder) &&
                    ($folder != $move_file) &&
                    (substr($folder, 0, strlen($move_file) + 1) != $move_file.'/')
                ) {
                    // Cannot copy dir into his own subdir
                    $path_displayed = self::get_titles_of_path($folder);
                    $display_folder = substr($path_displayed, strlen($group_dir));
                    $display_folder = $display_folder == '' ? get_lang('Documents') : $display_folder;
                    $options[$folder] = $display_folder;
                }
            }
        }
        $form->addElement('select', 'move_to', get_lang('Move to'), $options);
        $form->addButtonNext(get_lang('Move element'), 'move_file_submit');

        return $form->returnForm();
    }

    /**
     * Gets the path translated with title of docs and folders.
     *
     * @param string $path the real path
     *
     * @return the path which should be displayed
     */
    public static function get_titles_of_path($path)
    {
        global $tmp_folders_titles;
        $course_id = api_get_course_int_id();
        $nb_slashes = substr_count($path, '/');
        $current_slash_pos = 0;
        $path_displayed = '';
        for ($i = 0; $i < $nb_slashes; $i++) {
            // For each folder of the path, retrieve title.
            $current_slash_pos = strpos($path, '/', $current_slash_pos + 1);
            $tmp_path = substr($path, strpos($path, '/', 0), $current_slash_pos);

            if (empty($tmp_path)) {
                // If empty, then we are in the final part of the path
                $tmp_path = $path;
            }

            if (!empty($tmp_folders_titles[$tmp_path])) {
                // If this path has soon been stored here we don't need a new query
                $path_displayed .= $tmp_folders_titles[$tmp_path];
            } else {
                $sql = 'SELECT title FROM '.Database::get_course_table(TABLE_DOCUMENT).'
                        WHERE c_id = '.$course_id.' AND path LIKE BINARY "'.$tmp_path.'"';
                $rs = Database::query($sql);
                $tmp_title = '/'.Database::result($rs, 0, 0);
                $path_displayed .= $tmp_title;
                $tmp_folders_titles[$tmp_path] = $tmp_title;
            }
        }

        return $path_displayed;
    }

    /**
     * Creates form that asks for the directory name.
     *
     * @return string html-output text for the form
     */
    public static function create_dir_form($dirId)
    {
        global $document_id;
        $form = new FormValidator('create_dir_form', 'post', api_get_self().'?'.api_get_cidreq());
        $form->addElement('hidden', 'create_dir', 1);
        $form->addElement('hidden', 'dir_id', intval($document_id));
        $form->addElement('hidden', 'id', intval($dirId));
        $form->addElement('header', get_lang('Create folder'));
        $form->addText('dirname', get_lang('Name of the new folder'), ['autofocus' => 'autofocus']);
        $form->addButtonCreate(get_lang('Create the folder'));

        return $form->returnForm();
    }

    /**
     * Checks whether the user is in shared folder.
     *
     * @param string $curdirpath
     * @param int    $sessionId
     *
     * @return bool Return true when user is into shared folder
     */
    public static function is_shared_folder($curdirpath, $sessionId)
    {
        $clean_curdirpath = Security::remove_XSS($curdirpath);
        if ($clean_curdirpath == '/shared_folder') {
            return true;
        } elseif ($clean_curdirpath == '/shared_folder_session_'.$sessionId) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Checks whether the user is into any user shared folder.
     *
     * @param string $path
     * @param int    $sessionId
     *
     * @return bool Return true when user is in any user shared folder
     */
    public static function is_any_user_shared_folder($path, $sessionId)
    {
        $clean_path = Security::remove_XSS($path);
        if (strpos($clean_path, 'shared_folder/sf_user_')) {
            return true;
        } elseif (strpos($clean_path, 'shared_folder_session_'.$sessionId.'/sf_user_')) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Create users shared folder for course.
     *
     * @param int   $userId
     * @param array $courseInfo
     * @param int   $sessionId
     */
    public static function createUserSharedFolder($userId, array $courseInfo, $sessionId = 0)
    {
        $documentDirectory = api_get_path(SYS_COURSE_PATH).$courseInfo['directory'].'/document';
        $userInfo = api_get_user_info($userId);

        if (!$sessionId) {
            //Create shared folder. Necessary for recycled courses.
            if (!file_exists($documentDirectory.'/shared_folder')) {
                create_unexisting_directory(
                    $courseInfo,
                    $userId,
                    0,
                    0,
                    0,
                    $documentDirectory,
                    '/shared_folder',
                    get_lang('Folders of users'),
                    0,
                    false,
                    false
                );
            }
            // Create dynamic user shared folder
            if (!file_exists($documentDirectory.'/shared_folder/sf_user_'.$userId)) {
                create_unexisting_directory(
                    $courseInfo,
                    $userId,
                    0,
                    0,
                    0,
                    $documentDirectory,
                    '/shared_folder/sf_user_'.$userId,
                    $userInfo['complete_name'],
                    1,
                    false,
                    false
                );
            }

            return;
        }

        // Create shared folder session.
        if (!file_exists($documentDirectory.'/shared_folder_session_'.$sessionId)) {
            create_unexisting_directory(
                $courseInfo,
                api_get_user_id(),
                $sessionId,
                0,
                0,
                $documentDirectory,
                '/shared_folder_session_'.$sessionId,
                get_lang('Folders of users').' ('.api_get_session_name($sessionId).')',
                0,
                false,
                false
            );
        }
        //Create dynamic user shared folder into a shared folder session
        if (!file_exists($documentDirectory.'/shared_folder_session_'.$sessionId.'/sf_user_'.$userId)) {
            create_unexisting_directory(
                $courseInfo,
                $userId,
                $sessionId,
                0,
                0,
                $documentDirectory,
                '/shared_folder_session_'.$sessionId.'/sf_user_'.$userId,
                $userInfo['complete_name'].'('.api_get_session_name($sessionId).')',
                1,
                false,
                false
            );
        }
    }

    /**
     * Checks whether the user is into his shared folder or into a subfolder.
     *
     * @param int    $user_id
     * @param string $path
     * @param int    $sessionId
     *
     * @return bool Return true when user is in his user shared folder or into a subfolder
     */
    public static function is_my_shared_folder($user_id, $path, $sessionId)
    {
        $clean_path = Security::remove_XSS($path).'/';
        //for security does not remove the last slash
        $main_user_shared_folder = '/shared_folder\/sf_user_'.$user_id.'\//';
        //for security does not remove the last slash
        $main_user_shared_folder_session = '/shared_folder_session_'.$sessionId.'\/sf_user_'.$user_id.'\//';

        if (preg_match($main_user_shared_folder, $clean_path)) {
            return true;
        } elseif (preg_match($main_user_shared_folder_session, $clean_path)) {
            return true;
        } else {
            return false;
        }
    }

    public static function isBasicCourseFolder($path, $sessionId)
    {
        $cleanPath = Security::remove_XSS($path);
        $basicCourseFolder = '/basic-course-documents__'.$sessionId.'__0';

        return $cleanPath == $basicCourseFolder;
    }

    /**
     * Check if the file name or folder searched exist.
     *
     * @return bool Return true when exist
     */
    public static function search_keyword($document_name, $keyword)
    {
        if (api_strripos($document_name, $keyword) !== false) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Checks whether a document can be previewed by using the browser.
     *
     * @param string $file_extension the filename extension of the document (it must be in lower case)
     *
     * @return bool returns TRUE or FALSE
     */
    public static function isBrowserViewable($file_extension)
    {
        static $allowed_extensions = [
            'htm', 'html', 'xhtml',
            'gif', 'jpg', 'jpeg', 'png', 'tif', 'tiff',
            'pdf', 'svg', 'swf',
            'txt', 'log',
            'mp4', 'ogg', 'ogv', 'ogx', 'mpg', 'mpeg', 'mov', 'avi', 'webm', 'wmv',
            'mp3', 'oga', 'wav', 'au', 'wma', 'mid', 'kar',
        ];

        /*
          //TODO: make a admin switch to strict mode
          1. global default $allowed_extensions
          if (in_array($file_extension, $allowed_extensions)) { // Assignment + a logical check.
          return true;
          }
          2. check native support
          3. check plugins: quicktime, mediaplayer, vlc, acrobat, flash, java
         */

        if (!($result = in_array($file_extension, $allowed_extensions))) {
            // Assignment + a logical check.
            return false;
        }

        //check native support (Explorer, Opera, Firefox, Chrome, Safari)
        if ($file_extension == "pdf") {
            return api_browser_support('pdf');
        } elseif ($file_extension == "mp3") {
            return api_browser_support('mp3');
        } elseif ($file_extension == "mp4") {
            return api_browser_support('mp4');
        } elseif ($file_extension == "ogg" || $file_extension == "ogx" || $file_extension == "ogv" || $file_extension == "oga") {
            return api_browser_support('ogg');
        } elseif ($file_extension == "svg") {
            return api_browser_support('svg');
        } elseif ($file_extension == "mpg" || $file_extension == "mpeg") {
            return api_browser_support('mpg');
        } elseif ($file_extension == "mov") {
            return api_browser_support('mov');
        } elseif ($file_extension == "wav") {
            return api_browser_support('wav');
        } elseif ($file_extension == "mid" || $file_extension == "kar") {
            return api_browser_support('mid');
        } elseif ($file_extension == "avi") {
            return api_browser_support('avi');
        } elseif ($file_extension == "wma") {
            return api_browser_support('wma');
        } elseif ($file_extension == "wmv") {
            return api_browser_support('wmv');
        } elseif ($file_extension == "tif" || $file_extension == "tiff") {
            return api_browser_support('tif');
        } elseif ($file_extension == "mov") {
            return api_browser_support('mov');
        } elseif ($file_extension == "au") {
            return api_browser_support('au');
        } elseif ($file_extension == "webm") {
            return api_browser_support('webm');
        }

        return $result;
    }

    /**
     * @param array $courseInfo
     * @param int   $sessionId
     *
     * @return array
     */
    public static function getDeletedDocuments($courseInfo, $sessionId = 0)
    {
        $table = Database::get_course_table(TABLE_DOCUMENT);
        $courseId = $courseInfo['real_id'];
        $sessionCondition = api_get_session_condition($sessionId);
        $sql = "SELECT * FROM $table
                WHERE
                  path LIKE '%DELETED%' AND
                  c_id = $courseId
                  $sessionCondition
                ORDER BY path
        ";

        $result = Database::query($sql);
        $files = [];
        while ($document = Database::fetch_array($result, 'ASSOC')) {
            $files[] = $document;
        }

        return $files;
    }

    /**
     * @param int   $id
     * @param array $courseInfo
     * @param int   $sessionId
     *
     * @return array
     */
    public static function getDeletedDocument($id, $courseInfo, $sessionId = 0)
    {
        if (empty($courseInfo)) {
            return false;
        }

        $table = Database::get_course_table(TABLE_DOCUMENT);
        $courseId = $courseInfo['real_id'];
        $sessionCondition = api_get_session_condition($sessionId);
        $sql = "SELECT * FROM $table
                WHERE
                  path LIKE '%DELETED%' AND
                  id = $id AND
                  c_id = $courseId
                  $sessionCondition
                LIMIT 1
        ";
        $result = Database::query($sql);
        if (Database::num_rows($result)) {
            $result = Database::fetch_array($result, 'ASSOC');

            return $result;
        }

        return [];
    }

    /**
     * @param int   $id
     * @param array $courseInfo
     * @param int   $sessionId
     *
     * @return bool
     */
    public static function purgeDocument($id, $courseInfo, $sessionId = 0)
    {
        $document = self::getDeletedDocument($id, $courseInfo, $sessionId);
        if (!empty($document)) {
            $path = $document['path'];
            $coursePath = api_get_path(SYS_COURSE_PATH).$courseInfo['path'].'/document/';
            my_delete($coursePath.$path);
            // Hard delete.
            self::deleteDocumentFromDb($id, $courseInfo, $sessionId, true);

            return true;
        }

        return false;
    }

    /**
     * @param array $courseInfo
     * @param int   $sessionId
     */
    public static function purgeDocuments($courseInfo, $sessionId)
    {
        $files = self::getDeletedDocuments($courseInfo, $sessionId);
        foreach ($files as $file) {
            self::purgeDocument($file['id'], $courseInfo, $sessionId);
        }
    }

    /**
     * @param int   $id
     * @param array $courseInfo
     * @param int   $sessionId
     */
    public static function downloadDeletedDocument($id, $courseInfo, $sessionId)
    {
        $document = self::getDeletedDocument($id, $courseInfo, $sessionId);
        if (!empty($document)) {
            $coursePath = api_get_path(SYS_COURSE_PATH).$courseInfo['path'].'/document/';

            if (Security::check_abs_path($coursePath.$document['path'], $coursePath)) {
                self::file_send_for_download($coursePath.$document['path']);
                exit;
            }
        }
    }

    /**
     * @param array $courseInfo
     * @param int   $sessionId
     *
     * @return bool
     */
    public static function downloadAllDeletedDocument($courseInfo, $sessionId)
    {
        $files = self::getDeletedDocuments($courseInfo, $sessionId);

        if (empty($files)) {
            return false;
        }

        $coursePath = api_get_path(SYS_COURSE_PATH).$courseInfo['path'].'/document';

        // Creating a ZIP file.
        $tempZipFile = api_get_path(SYS_ARCHIVE_PATH).api_get_unique_id().".zip";
        $zip = new PclZip($tempZipFile);
        foreach ($files as $file) {
            $zip->add(
                $coursePath.$file['path'],
                PCLZIP_OPT_REMOVE_PATH,
                $coursePath
            );
        }

        if (Security::check_abs_path($tempZipFile, api_get_path(SYS_ARCHIVE_PATH))) {
            self::file_send_for_download($tempZipFile, true);
            @unlink($tempZipFile);
            exit;
        }
    }

    /**
     * Delete documents from a session in a course.
     *
     * @param array $courseInfo
     * @param int   $sessionId
     *
     * @return bool
     */
    public static function deleteDocumentsFromSession($courseInfo, $sessionId)
    {
        if (empty($courseInfo)) {
            return false;
        }

        if (empty($sessionId)) {
            return false;
        }

        $documentTable = Database::get_course_table(TABLE_DOCUMENT);
        $conditionSession = api_get_session_condition($sessionId, true, false, 'd.session_id');
        $courseId = $courseInfo['real_id'];

        // get invisible folders
        $sql = "SELECT DISTINCT d.id, path
                FROM $documentTable d 
                WHERE                   
                    $conditionSession AND
                    d.c_id = $courseId ";

        $result = Database::query($sql);
        $documents = Database::store_result($result, 'ASSOC');
        if ($documents) {
            foreach ($documents as $document) {
                $documentId = $document['id'];
                self::delete_document(
                    $courseInfo,
                    null,
                    null,
                    $sessionId,
                    $documentId
                );
            }
        }

        /*
        $sql = "DELETE FROM $documentTable
                WHERE c_id = $courseId AND session_id = $sessionId";
        Database::query($sql);

        $sql = "DELETE FROM $itemPropertyTable
                WHERE c_id = $courseId AND session_id = $sessionId AND tool = '".TOOL_DOCUMENT."'";
        Database::query($sql);*/
    }

    /**
     * Update the file or directory path in the document db document table.
     *
     * @author - Hugues Peeters <peeters@ipm.ucl.ac.be>
     *
     * @param string $action   - action type require : 'delete' or 'update'
     * @param string $old_path - old path info stored to change
     * @param string $new_path - new path info to substitute
     *
     * @desc Update the file or directory path in the document db document table
     */
    public static function updateDbInfo($action, $old_path, $new_path = '')
    {
        $dbTable = Database::get_course_table(TABLE_DOCUMENT);
        $course_id = api_get_course_int_id();
        $old_path = Database::escape_string($old_path);
        switch ($action) {
            case 'delete':
                $query = "DELETE FROM $dbTable
                          WHERE
                            c_id = $course_id AND
                            (
                                path LIKE BINARY '".$old_path."' OR
                                path LIKE BINARY '".$old_path."/%'
                            )";
                Database::query($query);
                break;
            case 'update':
                if ($new_path[0] == '.') {
                    $new_path = substr($new_path, 1);
                }
                $new_path = str_replace('//', '/', $new_path);

                // Attempt to update	- tested & working for root	dir
                $new_path = Database::escape_string($new_path);
                $query = "UPDATE $dbTable SET
                            path = CONCAT('".$new_path."', SUBSTRING(path, LENGTH('".$old_path."')+1) )
                          WHERE 
                                c_id = $course_id AND 
                                (path LIKE BINARY '".$old_path."' OR path LIKE BINARY '".$old_path."/%')";
                Database::query($query);
                break;
        }
    }

    /**
     * This function calculates the resized width and resized heigt
     * according to the source and target widths
     * and heights, height so that no distortions occur
     * parameters.
     *
     * @param $image = the absolute path to the image
     * @param $target_width = how large do you want your resized image
     * @param $target_height = how large do you want your resized image
     * @param $slideshow (default=0) =
     *      indicates weither we are generating images for a slideshow or not,
     *		this overrides the $_SESSION["image_resizing"] a bit so that a thumbnail
     *	    view is also possible when you choose not to resize the source images
     *
     * @return array
     */
    public static function resizeImageSlideShow(
        $image,
        $target_width,
        $target_height,
        $slideshow = 0
    ) {
        // Modifications by Ivan Tcholakov, 04-MAY-2009.
        $result = [];
        $imageResize = Session::read('image_resizing');
        if ($imageResize == 'resizing' || $slideshow == 1) {
            $new_sizes = api_resize_image($image, $target_width, $target_height);
            $result[] = $new_sizes['height'];
            $result[] = $new_sizes['width'];
        } else {
            $size = api_getimagesize($image);
            $result[] = $size['height'];
            $result[] = $size['width'];
        }

        return $result;
    }

    /**
     * Adds a cloud link to the database.
     *
     * @author - Aquilino Blanco Cores <aqblanco@gmail.com>
     *
     * @param array  $_course
     * @param string $path
     * @param string $url
     * @param string $name
     *
     * @return int id of document or 0 if already exists or there was a problem creating it
     */
    public static function addCloudLink($_course, $path, $url, $name)
    {
        $file_path = $path;
        if (!self::cloudLinkExists($_course, $path, $url)) {
            $doc = self::addDocument($_course, $file_path, 'link', 0, $name, $url);

            return $doc->getId();
        } else {
            return 0;
        }
    }

    /**
     * Deletes a cloud link from the database.
     *
     * @author - Aquilino Blanco Cores <aqblanco@gmail.com>
     *
     * @param array  $courseInfo
     * @param string $documentId
     *
     * @return bool true if success / false if an error occurred
     */
    public static function deleteCloudLink($courseInfo, $documentId)
    {
        if (empty($documentId) || empty($courseInfo)) {
            return false;
        }

        $documentId = (int) $documentId;
        $fileDeletedFromDb = false;
        if (!empty($documentId)) {
            self::deleteDocumentFromDb($documentId, $courseInfo, 0, true);
            // checking
            $table = Database::get_course_table(TABLE_DOCUMENT);
            $courseId = $courseInfo['real_id'];
            echo $sql = "SELECT * FROM $table WHERE id = $documentId AND c_id = $courseId";
            $result = Database::query($sql);
            $exists = Database::num_rows($result) > 0;
            $fileDeletedFromDb = !$exists;
        }

        return $fileDeletedFromDb;
    }

    /**
     * Gets the id of a cloud link with a given path.
     *
     * @author - Aquilino Blanco Cores <aqblanco@gmail.com>
     *
     * @param array  $courseInfo
     * @param string $path
     * @param string $url
     *
     * @return int link's id / false if no link found
     */
    public static function getCloudLinkId($courseInfo, $path, $url)
    {
        $table = Database::get_course_table(TABLE_DOCUMENT);

        if (empty($courseInfo)) {
            return false;
        }

        $courseId = (int) $courseInfo['real_id'];
        $path = Database::escape_string($path);

        if (substr($path, -1) != '/') {
            // Add final slash to path if not present
            $path .= '/';
        }

        if (!empty($courseId) && !empty($path)) {
            $sql = "SELECT id FROM $table 
                    WHERE 
                        c_id = $courseId AND 
                        path LIKE BINARY '$path' AND 
                        comment = '$url' AND 
                        filetype = 'link' 
                    LIMIT 1";
            $result = Database::query($sql);
            if ($result && Database::num_rows($result)) {
                $row = Database::fetch_array($result);

                return (int) $row[0];
            }
        }

        return false;
    }

    /**
     * Checks if a cloud link exists.
     *
     * @author - Aquilino Blanco Cores <aqblanco@gmail.com>
     *
     * @param array  $courseInfo
     * @param string $path
     * @param string $url
     *
     * @return bool true if it exists false in other case
     */
    public static function cloudLinkExists($courseInfo, $path, $url)
    {
        $exists = self::getCloudLinkId($courseInfo, $path, $url);

        return $exists;
    }

    /**
     * Gets the wellformed URLs regular expression in order to use it on forms' verifications.
     *
     * @author Aquilino Blanco Cores <aqblanco@gmail.com>
     *
     * @return string the well formed URLs regular expressions string
     */
    public static function getWellFormedUrlRegex()
    {
        return '/\(?((http|https|ftp):\/\/)(?:((?:[^\W\s]|\.|-|[:]{1})+)@{1})?((?:www.)?(?:[^\W\s]|\.|-)+[\.][^\W\s]{2,4}|localhost(?=\/)|\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})(?::(\d*))?([\/]?[^\s\?]*[\/]{1})*(?:\/?([^\s\n\?\[\]\{\}\#]*(?:(?=\.)){1}|[^\s\n\?\[\]\{\}\.\#]*)?([\.]{1}[^\s\?\#]*)?)?(?:\?{1}([^\s\n\#\[\]]*))?([\#][^\s\n]*)?\)?/i';
    }

    /**
     * Gets the files hosting sites' whitelist.
     *
     * @author Aquilino Blanco Cores <aqblanco@gmail.com>
     *
     * @return array the sites list
     */
    public static function getFileHostingWhiteList()
    {
        return [
            'asuswebstorage.com',
            'dropbox.com',
            'dropboxusercontent.com',
            'fileserve.com',
            'drive.google.com',
            'docs.google.com',
            'icloud.com',
            'mediafire.com',
            'mega.nz',
            'onedrive.live.com',
            'slideshare.net',
            'scribd.com',
            'wetransfer.com',
            'box.com',
            'livefilestore.com', // OneDrive
        ];
    }

    /**
     * @param int $userId
     *
     * @return array Example [ 0 => ['code' => 'ABC', 'directory' => 'ABC0', 'path' => '/images/gallery/test.png', 'code_path' => 'ABC:/images/gallery/test.png'], 1 => ...]
     */
    public static function getAllDocumentsCreatedByUser($userId)
    {
        $tblItemProperty = Database::get_course_table(TABLE_ITEM_PROPERTY);
        $tblDocument = Database::get_course_table(TABLE_DOCUMENT);
        $tblCourse = Database::get_main_table(TABLE_MAIN_COURSE);
        $userId = (int) $userId;

        $sql = "SELECT DISTINCT c.code, c.directory, docs.path
                FROM $tblItemProperty AS last
                INNER JOIN $tblDocument AS docs
                ON (
                    docs.id = last.ref AND
                    docs.c_id = last.c_id AND
                    docs.filetype <> 'folder'
                )
                INNER JOIN $tblCourse as c
                ON (
                    docs.c_id = c.id
                )
                WHERE                                
                    last.tool = '".TOOL_DOCUMENT."' AND   
                    last.insert_user_id = $userId AND
                    docs.path NOT LIKE '%_DELETED_%'                     
                ORDER BY c.directory, docs.path
                ";
        $result = Database::query($sql);

        $list = [];
        if (Database::num_rows($result) != 0) {
            while ($row = Database::fetch_array($result, 'ASSOC')) {
                $row['code_path'] = $row['code'].':'.$row['path'];
                $list[] = $row;
            }
        }

        return $list;
    }

    /**
     * @param CDocument           $document
     * @param string              $realPath
     * @param string|UploadedFile $content
     * @param int                 $visibility
     * @param CGroupInfo          $group
     *
     * @return CDocument
     */
    public static function addFileToDocument(CDocument $document, $realPath, $content, $visibility, $group)
    {
        $repo = Container::getDocumentRepository();
        $fileType = $document->getFiletype();
        $resourceNode = $document->getResourceNode();

        if (!$resourceNode) {
            return false;
        }

        $em = Database::getManager();
        $title = $document->getTitle();

        // Only create a ResourceFile if there's a file involved
        if ($fileType === 'file') {
            $resourceFile = $resourceNode->getResourceFile();
            if (empty($resourceFile)) {
                $resourceFile = new ResourceFile();
            }

            if ($content instanceof UploadedFile) {
                $resourceFile->setFile($content);
                error_log('UploadedFile');
            } else {
                // $path points to a file in the directory
                if (file_exists($realPath) && !is_dir($realPath)) {
                    error_log('file_exists');
                    $file = new UploadedFile($realPath, $title, null, null, true);
                    $resourceFile->setFile($file);
                } else {
                    // We get the content and create a file
                    error_log('From content');
                    $handle = tmpfile();
                    fwrite($handle, $content);
                    $meta = stream_get_meta_data($handle);
                    //error_log($meta['uri']);
                    $file = new UploadedFile($meta['uri'], $title, null, null, true);
                    $resourceFile->setFile($file);
                }
            }

            $resourceFile->setName($title);
            $em->persist($resourceFile);
            $resourceNode->setResourceFile($resourceFile);
            $em->persist($resourceNode);
        }

        // By default visibility is published
        // @todo change visibility
        //$newVisibility = ResourceLink::VISIBILITY_PUBLISHED;
        $visibility = (int) $visibility;
        if (empty($visibility)) {
            $visibility = ResourceLink::VISIBILITY_PUBLISHED;
        }

        $repo->addResourceToCourse($resourceNode, $visibility, $document->getCourse(), $document->getSession(), $group);
        $em->flush();

        $documentId = $document->getIid();

        if ($documentId) {
            $table = Database::get_course_table(TABLE_DOCUMENT);
            $sql = "UPDATE $table SET id = iid WHERE iid = $documentId";
            Database::query($sql);

            return $document;
        }

        return false;
    }

    /**
     * Adds a new document to the database.
     *
     * @param array  $courseInfo
     * @param string $path
     * @param string $fileType
     * @param int    $fileSize
     * @param string $title
     * @param string $comment
     * @param int    $readonly
     * @param int    $visibility       see ResourceLink constants
     * @param int    $groupId          group.id
     * @param int    $sessionId        Session ID, if any
     * @param int    $userId           creator user id
     * @param bool   $sendNotification
     * @param string $content
     * @param int    $parentId
     * @param string $realPath
     *
     * @return CDocument|false
     */
    public static function addDocument(
        $courseInfo,
        $path,
        $fileType,
        $fileSize,
        $title,
        $comment = null,
        $readonly = 0,
        $visibility = null,
        $groupId = 0,
        $sessionId = 0,
        $userId = 0,
        $sendNotification = true,
        $content = '',
        $parentId = 0,
        $realPath = ''
    ) {
        $userId = empty($userId) ? api_get_user_id() : $userId;
        if (empty($userId)) {
            return false;
        }

        $userEntity = api_get_user_entity($userId);
        if (empty($userEntity)) {
            return false;
        }

        $courseEntity = api_get_course_entity($courseInfo['real_id']);
        if (empty($courseEntity)) {
            return false;
        }

        $sessionId = empty($sessionId) ? api_get_session_id() : $sessionId;
        $session = api_get_session_entity($sessionId);
        $group = api_get_group_entity($groupId);
        $readonly = (int) $readonly;
        $documentRepo = Container::getDocumentRepository();

        $parentNode = $courseEntity;
        if (!empty($parentId)) {
            $parent = $documentRepo->find($parentId);
            if ($parent) {
                $parentNode = $parent;
            }
        }

        $criteria = ['path' => $path, 'course' => $courseEntity];
        $document = $documentRepo->findOneBy($criteria);

        // Document already exists
        if ($document) {
            return false;
        }

        $document = new CDocument();
        $document
            ->setCourse($courseEntity)
            ->setPath($path)
            ->setFiletype($fileType)
            ->setSize($fileSize)
            ->setTitle($title)
            ->setComment($comment)
            ->setReadonly($readonly)
            ->setSession($session)
        ;

        $em = $documentRepo->getEntityManager();
        $em->persist($document);

        $documentRepo->addResourceNode($document, $userEntity, $parentNode);
        $document = self::addFileToDocument($document, $realPath, $content, $visibility, $group);

        if ($document) {
            $allowNotification = api_get_configuration_value('send_notification_when_document_added');
            if ($sendNotification && $allowNotification) {
                $courseTitle = $courseInfo['title'];
                if (!empty($sessionId)) {
                    $sessionInfo = api_get_session_info($sessionId);
                    $courseTitle .= ' ( '.$sessionInfo['name'].') ';
                }

                $url = api_get_path(WEB_CODE_PATH).
                    'document/showinframes.php?cidReq='.$courseInfo['code'].'&id_session='.$sessionId.'&id='.$document->getId();
                $link = Display::url(basename($title), $url, ['target' => '_blank']);
                $userInfo = api_get_user_info($userId);

                $message = sprintf(
                    get_lang('A new document %s has been added to the document tool in your course %s by %s.'),
                    $link,
                    $courseTitle,
                    $userInfo['complete_name']
                );
                $subject = sprintf(get_lang('New document added to course %s'), $courseTitle);
                MessageManager::sendMessageToAllUsersInCourse($subject, $message, $courseInfo, $sessionId);
            }

            return $document;
        }

        return false;
    }

    /**
     * @param array  $documentAndFolders
     * @param array  $courseInfo
     * @param bool   $is_certificate_mode
     * @param array  $groupMemberWithUploadRights
     * @param string $path
     * @param bool   $addToEditor
     * @param string $editorUrl
     *
     * @return array
     */
    public static function processDocumentAndFolders(
        $documentAndFolders,
        $courseInfo,
        $is_certificate_mode,
        $groupMemberWithUploadRights,
        $path,
        $addToEditor = false,
        $editorUrl = ''
    ) {
        if (empty($documentAndFolders) || empty($courseInfo)) {
            return [];
        }
        $isAllowedToEdit = api_is_allowed_to_edit(null, true);
        $userId = api_get_user_id();
        $currentUserInfo = api_get_user_info();
        $sessionId = api_get_session_id();
        $groupId = api_get_group_id();
        $userIsSubscribed = CourseManager::is_user_subscribed_in_course($userId, $courseInfo['code']);
        $url = api_get_path(WEB_COURSE_PATH).$courseInfo['directory'].'/document';

        $courseId = $courseInfo['real_id'];
        $group_properties = GroupManager::get_group_properties($groupId);

        $sortable_data = [];
        foreach ($documentAndFolders as $key => $document_data) {
            $row = [];
            $row['id'] = $document_data['id'];
            $row['type'] = $document_data['filetype'];

            // If the item is invisible, wrap it in a span with class invisible.
            $is_visible = self::is_visible_by_id(
                $document_data['id'],
                $courseInfo,
                $sessionId,
                $userId,
                false,
                $userIsSubscribed
            );
            $invisibility_span_open = $is_visible == 0 ? '<span class="muted">' : '';
            $invisibility_span_close = $is_visible == 0 ? '</span>' : '';
            $size = 1;
            // Get the title or the basename depending on what we're using
            if ($document_data['title'] != '') {
                $document_name = $document_data['title'];
            } else {
                $document_name = basename($document_data['path']);
            }
            $row['name'] = $document_name;
            // Data for checkbox
            if (($isAllowedToEdit || $groupMemberWithUploadRights) && count($documentAndFolders) > 1) {
                $row[] = $document_data['id'];
            }

            if (self::is_folder_to_avoid($document_data['path'], $is_certificate_mode)) {
                continue;
            }

            // Show the owner of the file only in groups
            $user_link = '';
            if (!empty($groupId)) {
                if (!empty($document_data['insert_user_id'])) {
                    $userInfo = api_get_user_info(
                        $document_data['insert_user_id'],
                        false,
                        false,
                        false,
                        false,
                        false
                    );
                    $user_link = '<div class="document_owner">'
                        .get_lang('Owner').': '.UserManager::getUserProfileLink($userInfo)
                        .'</div>';
                }
            }

            // Hack in order to avoid the download icon appearing on cloud links
            if ($document_data['filetype'] == 'link') {
                $size = 0;
            }

            // Icons (clickable)
            $row[] = self::create_document_link(
                $url,
                $document_data,
                true,
                $is_visible,
                $size,
                $isAllowedToEdit,
                $is_certificate_mode,
                $addToEditor,
                $editorUrl
            );

            // Validation when belongs to a session
            $session_img = api_get_session_image($document_data['session_id'], $currentUserInfo['status']);

            $link = self::create_document_link(
                $url,
                $document_data,
                false,
                $is_visible,
                $size,
                $isAllowedToEdit,
                $is_certificate_mode,
                $addToEditor,
                $editorUrl
            );

            // Document title with link
            $row[] = $link.$session_img.'<br />'.$invisibility_span_open.'<i>'
                .nl2br(htmlspecialchars($document_data['comment'], ENT_QUOTES, 'utf-8'))
                .'</i>'.$invisibility_span_close.$user_link;

            if ($document_data['filetype'] == 'folder') {
                $displaySize = '<span id="document_size_'.$document_data['id']
                    .'" data-path= "'.$document_data['path']
                    .'" class="document_size"></span>';
            } else {
                $displaySize = format_file_size($document_data['size']);
            }

            $row[] = '<span style="display:none;">'.$size.'</span>'.
                $invisibility_span_open.
                $displaySize.
                $invisibility_span_close;

            // Last edit date
            $last_edit_date = api_get_local_time($document_data['updated_at']);
            $display_date = date_to_str_ago($document_data['updated_at']).
                ' <div class="muted"><small>'.$last_edit_date."</small></div>";

            $row[] = $invisibility_span_open.$display_date.$invisibility_span_close;

            $groupMemberWithEditRightsCheckDocument = GroupManager::allowUploadEditDocument(
                $userId,
                $courseId,
                $group_properties,
                $document_data
            );

            // Admins get an edit column
            if ($isAllowedToEdit ||
                $groupMemberWithEditRightsCheckDocument ||
                self::is_my_shared_folder(api_get_user_id(), $path, $sessionId)
            ) {
                $is_template = isset($document_data['is_template']) ? $document_data['is_template'] : false;

                // If readonly, check if it the owner of the file or if the user is an admin
                if ($document_data['creator_id'] == api_get_user_id() || api_is_platform_admin()) {
                    $edit_icons = self::build_edit_icons(
                        $document_data,
                        $key,
                        $is_template,
                        $is_visible
                    );
                } else {
                    $edit_icons = self::build_edit_icons(
                        $document_data,
                        $key,
                        $is_template,
                        $is_visible
                    );
                }
                $row[] = $edit_icons;
            } else {
                $row[] = '';
            }
            $row[] = $last_edit_date;
            $row[] = $size;
            $row[] = $document_name;

            if ((isset($_GET['keyword']) && self::search_keyword($document_name, $_GET['keyword'])) ||
                !isset($_GET['keyword']) ||
                empty($_GET['keyword'])
            ) {
                $sortable_data[] = $row;
            }
        }

        return $sortable_data;
    }

    /**
     * Parse file information into a link.
     *
     * @param array  $userInfo        Current user info
     * @param array  $course_info
     * @param int    $session_id
     * @param array  $resource
     * @param int    $lp_id
     * @param bool   $add_move_button
     * @param string $target
     * @param string $overwrite_url
     *
     * @return string|null
     */
    private static function parseFile(
        $userInfo,
        $course_info,
        $session_id,
        $resource,
        $lp_id,
        $add_move_button,
        $target,
        $overwrite_url
    ) {
        $img_sys_path = api_get_path(SYS_CODE_PATH).'img/';
        $web_code_path = api_get_path(WEB_CODE_PATH);

        $documentId = $resource['id'];
        $path = $resource['path'];

        if (empty($path)) {
            $num = 0;
        } else {
            $num = substr_count($path, '/') - 1;
        }

        // It's a file.
        $icon = choose_image($path);
        $position = strrpos($icon, '.');
        $icon = substr($icon, 0, $position).'_small.gif';
        $my_file_title = $resource['title'];
        $visibility = $resource['visibility'];

        // If title is empty we try to use the path
        if (empty($my_file_title)) {
            $my_file_title = basename($path);
        }

        // Show the "image name" not the filename of the image.
        if ($lp_id) {
            // LP URL
            $url = api_get_path(WEB_CODE_PATH).'lp/lp_controller.php?'.api_get_cidreq().'&action=add_item&type='.TOOL_DOCUMENT.'&file='.$documentId.'&lp_id='.$lp_id;
        } else {
            // Direct document URL
            $url = $web_code_path.'document/document.php?cidReq='.$course_info['code'].'&id_session='.$session_id.'&id='.$documentId;
        }

        if (!empty($overwrite_url)) {
            $overwrite_url = Security::remove_XSS($overwrite_url);
            $url = $overwrite_url.'&cidReq='.$course_info['code'].'&id_session='.$session_id.'&document_id='.$documentId;
        }

        $img = Display::returnIconPath($icon);
        if (!file_exists($img_sys_path.$icon)) {
            $img = Display::returnIconPath('default_small.png');
        }

        $link = Display::url(
            '<img alt="" src="'.$img.'" title="" />&nbsp;'.$my_file_title,
            $url,
            ['target' => $target, 'class' => 'moved']
        );

        $directUrl = $web_code_path.'document/document.php?cidReq='.$course_info['code'].'&id_session='.$session_id.'&id='.$documentId;
        $link .= '&nbsp;'.Display::url(
            Display::return_icon('preview_view.png', get_lang('Preview')),
            $directUrl,
            ['target' => '_blank']
        );

        $visibilityClass = null;
        if ($visibility == 0) {
            $visibilityClass = ' text-muted ';
        }
        $return = null;

        if ($lp_id == false) {
            $return .= '<li class="doc_resource '.$visibilityClass.' " data_id="'.$documentId.'" data_type="document" title="'.$my_file_title.'" >';
        } else {
            $return .= '<li class="doc_resource lp_resource_element '.$visibilityClass.' " data_id="'.$documentId.'" data_type="document" title="'.$my_file_title.'" >';
        }

        $return .= '<div class="item_data" style="margin-left:'.($num * 5).'px;margin-right:5px;">';
        if ($add_move_button) {
            $return .= '<a class="moved" href="#">';
            $return .= Display::return_icon('move_everywhere.png', get_lang('Move'), [], ICON_SIZE_TINY);
            $return .= '</a> ';
        }
        $return .= $link;
        $sessionStar = api_get_session_image($resource['session_id'], $userInfo['status']);
        $return .= $sessionStar;

        $return .= '</div></li>';

        return $return;
    }

    /**
     * @param int   $folderId
     * @param array $resource
     * @param int   $lp_id
     *
     * @return string|null
     */
    private static function parseFolder($folderId, $resource, $lp_id)
    {
        $title = isset($resource['title']) ? $resource['title'] : null;
        $path = isset($resource['path']) ? $resource['path'] : null;

        if (empty($path)) {
            $num = 0;
        } else {
            $num = substr_count($path, '/');
        }

        // It's a folder.
        //hide some folders
        if (in_array(
            $path,
            ['shared_folder', 'chat_files', 'HotPotatoes_files', 'css', 'certificates']
        )) {
            return null;
        } elseif (preg_match('/_groupdocs/', $path)) {
            return null;
        } elseif (preg_match('/sf_user_/', $path)) {
            return null;
        } elseif (preg_match('/shared_folder_session_/', $path)) {
            return null;
        }

        //$onclick = '';
        // if in LP, hidden folder are displayed in grey
        $folder_class_hidden = '';
        if ($lp_id) {
            if (isset($resource['visible']) && $resource['visible'] == 0) {
                $folder_class_hidden = ' doc_folder_hidden'; // in base.css
            }
        }
        $onclick = 'onclick="javascript: testResources(\'res_'.$resource['id'].'\',\'img_'.$resource['id'].'\')"';
        $return = null;

        if (empty($path)) {
            $return = '<ul class="lp_resource">';
        }

        $return .= '<li class="doc_folder '.$folder_class_hidden.'" id="doc_id_'.$resource['id'].'"  style="margin-left:'.($num * 18).'px; ">';

        $image = Display::returnIconPath('nolines_plus.gif');
        if (empty($path)) {
            $image = Display::returnIconPath('nolines_minus.gif');
        }
        $return .= '<img style="cursor: pointer;" src="'.$image.'" align="absmiddle" id="img_'.$resource['id'].'" '.$onclick.'>';
        $return .= Display::return_icon('lp_folder.png').'&nbsp;';
        $return .= '<span '.$onclick.' style="cursor: pointer;" >'.$title.'</span>';
        $return .= '</li>';

        if (empty($path)) {
            if ($folderId == false) {
                $return .= '<div id="res_'.$resource['id'].'" >';
            } else {
                $return .= '<div id="res_'.$resource['id'].'" style="display: none;" >';
            }
        }

        return $return;
    }

    /**
     * Get the button to edit document.
     *
     * @param bool   $isReadOnly
     * @param array  $documentData
     * @param string $extension
     * @param bool   $isCertificateMode
     *
     * @return string
     */
    private static function getButtonEdit($isReadOnly, array $documentData, $extension, $isCertificateMode)
    {
        $extension = strtolower($extension);
        $iconEn = Display::return_icon('edit.png', get_lang('Edit'));
        $iconDis = Display::return_icon('edit_na.png', get_lang('Edit'));
        $courseParams = api_get_cidreq();
        $webOdfExtensionList = self::get_web_odf_extension_list();
        $path = $documentData['path'];
        $documentId = $documentData['id'];

        if ($isReadOnly) {
            if (!api_is_course_admin() && !api_is_platform_admin()) {
                return $iconDis;
            }

            if (
                $extension == 'svg' && api_browser_support('svg') &&
                api_get_setting('enabled_support_svg') == 'true'
            ) {
                return Display::url($iconEn, "edit_draw.php?$courseParams&id=$documentId");
            }

            if (
                in_array($extension, $webOdfExtensionList) &&
                api_get_configuration_value('enabled_support_odf') === true
            ) {
                return Display::url($iconEn, "edit_odf.php?$courseParams&id=$documentId");
            }

            if (
                in_array($extension, ['png', 'jpg', 'jpeg', 'bmp', 'gif', 'pxd']) &&
                    api_get_setting('enabled_support_pixlr') == 'true'
            ) {
                return Display::url($iconEn, "edit_paint.php?$courseParams&id=$documentId");
            }

            return Display::url($iconEn, "edit_document.php?$courseParams&id=$documentId");
        }

        if (in_array($path, self::get_system_folders())) {
            return $iconDis;
        }

        if ($isCertificateMode) {
            return Display::url($iconEn, "edit_document.php?$courseParams&id=$documentId&curdirpath=/certificates");
        }

        $sessionId = api_get_session_id();

        if ($sessionId && $documentData['session_id'] != $sessionId) {
            return $iconDis;
        }

        if (
            $extension == 'svg' && api_browser_support('svg') &&
            api_get_setting('enabled_support_svg') == 'true'
        ) {
            return Display::url($iconEn, "edit_draw.php?$courseParams&id=$documentId");
        }

        if (
            in_array($extension, $webOdfExtensionList) &&
            api_get_configuration_value('enabled_support_odf') === true
        ) {
            return Display::url($iconEn, "edit_odf.php?$courseParams&id=$documentId");
        }

        if (
            in_array($extension, ['png', 'jpg', 'jpeg', 'bmp', 'gif', 'pxd']) &&
                api_get_setting('enabled_support_pixlr') == 'true'
        ) {
            return Display::url($iconEn, "edit_paint.php?$courseParams&id=$documentId");
        }

        return Display::url($iconEn, "edit_document.php?$courseParams&id=$documentId");
    }

    /**
     * Get the button to move document.
     *
     * @param bool  $isReadOnly
     * @param array $documentData
     * @param bool  $isCertificateMode
     * @param int   $parentId
     *
     * @return string
     */
    private static function getButtonMove($isReadOnly, array $documentData, $isCertificateMode, $parentId)
    {
        $iconEn = Display::return_icon('move.png', get_lang('Move'));
        $iconDis = Display::return_icon('move_na.png', get_lang('Move'));

        if ($isReadOnly) {
            return $iconDis;
        }

        $path = $documentData['path'];
        $document_id = $documentData['id'];
        $sessionId = api_get_session_id();
        $courseParams = api_get_cidreq();

        if ($isCertificateMode || in_array($path, self::get_system_folders())) {
            return $iconDis;
        }

        if ($sessionId) {
            if ($documentData['session_id'] != $sessionId) {
                return $iconDis;
            }
        }

        $urlMoveParams = http_build_query(['id' => $parentId, 'move' => $document_id]);

        return Display::url(
            $iconEn,
            api_get_self()."?$courseParams&$urlMoveParams"
        );
    }

    /**
     * Get the button to set visibility to document.
     *
     * @param bool  $isReadOnly
     * @param int   $visibility
     * @param array $documentData
     * @param bool  $isCertificateMode
     * @param int   $parentId
     *
     * @return string|null
     */
    private static function getButtonVisibility(
        $isReadOnly,
        $visibility,
        array $documentData,
        $isCertificateMode,
        $parentId
    ) {
        $visibility_icon = $visibility == 0 ? 'invisible' : 'visible';
        $visibility_command = $visibility == 0 ? 'set_visible' : 'set_invisible';
        $courseParams = api_get_cidreq();

        if ($isReadOnly) {
            if (api_is_allowed_to_edit() || api_is_platform_admin()) {
                return Display::return_icon($visibility_icon.'.png', get_lang('The visibility cannot be changed'));
            }

            return null;
        }

        if ($isCertificateMode) {
            return Display::return_icon($visibility_icon.'.png', get_lang('The visibility cannot be changed'));
        }

        if (api_is_allowed_to_edit() || api_is_platform_admin()) {
            $tip_visibility = $visibility_icon == 'invisible' ? get_lang('Show') : get_lang('Hide');

            return Display::url(
                Display::return_icon($visibility_icon.'.png', $tip_visibility),
                api_get_self()."?$courseParams&id=$parentId&$visibility_command={$documentData['id']}"
            );
        }

        return null;
    }

    /**
     * GEt the button to delete a document.
     *
     * @param bool   $isReadOnly
     * @param array  $documentData
     * @param bool   $isCertificateMode
     * @param string $curDirPath
     * @param int    $parentId
     *
     * @return string
     */
    private static function getButtonDelete(
        $isReadOnly,
        array $documentData,
        $isCertificateMode,
        $curDirPath,
        $parentId
    ) {
        $iconEn = Display::return_icon('delete.png', get_lang('Delete'));
        $iconDis = Display::return_icon('delete_na.png', get_lang('This folder cannot be deleted'));
        $path = $documentData['path'];
        $id = $documentData['id'];
        $courseParams = api_get_cidreq();

        if ($isReadOnly) {
            return $iconDis;
        }

        if (in_array($path, self::get_system_folders())) {
            return $iconDis;
        }

        $titleToShow = addslashes(basename($documentData['title']));
        $urlDeleteParams = http_build_query([
            'curdirpath' => $curDirPath,
            'action' => 'delete_item',
            'id' => $parentId,
            'deleteid' => $documentData['id'],
        ]);

        $btn = Display::url(
            $iconEn,
            api_get_self()."?$courseParams&$urlDeleteParams",
            [
                'title' => get_lang('Do you want to delete the file?').': '.$titleToShow,
                'class' => 'delete-swal',
            ]
        );

        if (
            isset($_GET['curdirpath']) &&
            $_GET['curdirpath'] == '/certificates' &&
            self::get_default_certificate_id(api_get_course_int_id()) == $id
        ) {
            return $btn;
        }

        if ($isCertificateMode) {
            return $btn;
        }

        $sessionId = api_get_session_id();

        if ($sessionId) {
            if ($documentData['session_id'] != $sessionId) {
                return $iconDis;
            }
        }

        return $btn;
    }
}
