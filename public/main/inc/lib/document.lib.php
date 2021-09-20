<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CDocument;
use ChamiloSession as Session;

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

        if (true === $filename) {
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

        while (!feof($fm) && $cur <= $end && (0 == connection_status())) {
            echo fread($fm, min(1024 * 16, ($end - $cur) + 1));
            $cur += 1024 * 16;
        }
    }

    /**
     * This function streams a file to the client.
     *
     * @param string $full_file_name
     * @param bool   $forced              Whether to force the browser to download the file
     * @param string $name
     * @param bool   $fixLinksHttpToHttps change file content from http to https
     * @param array  $extraHeaders        Additional headers to be sent
     *
     * @return false if file doesn't exist, true if stream succeeded
     */
    public static function file_send_for_download(
        $full_file_name,
        $forced = false,
        $name = '',
        $fixLinksHttpToHttps = false,
        $extraHeaders = []
    ) {
        session_write_close(); //we do not need write access to session anymore
        if (!is_file($full_file_name)) {
            return false;
        }
        $filename = '' == $name ? basename($full_file_name) : api_replace_dangerous_char($name);
        $len = filesize($full_file_name);
        // Fixing error when file name contains a ","
        $filename = str_replace(',', '', $filename);
        $sendFileHeaders = api_get_configuration_value('enable_x_sendfile_headers');

        // Allows chrome to make videos and audios seekable
        header('Accept-Ranges: bytes');
        if (!empty($extraHeaders)) {
            foreach ($extraHeaders as $name => $value) {
                //TODO: add restrictions to allowed headers?
                header($name.': '.$value);
            }
        }

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
            $lpFixedEncoding = 'true' === api_get_setting('lp.fixed_encoding');

            // Commented to let courses content to be cached in order to improve performance:
            //header('Expires: Wed, 01 Jan 1990 00:00:00 GMT');
            //header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');

            // Commented to avoid double caching declaration when playing with IE and HTTPS
            //header('Cache-Control: no-cache, must-revalidate');
            //header('Pragma: no-cache');

            $contentType = self::file_get_mime_type($filename);

            switch ($contentType) {
                case 'text/html':
                    if (isset($lpFixedEncoding) && 'true' === $lpFixedEncoding) {
                        $contentType .= '; charset=UTF-8';
                    } else {
                        $encoding = @api_detect_encoding_html(file_get_contents($full_file_name));
                        if (!empty($encoding)) {
                            $contentType .= '; charset='.$encoding;
                        }
                    }
                    break;
                case 'text/plain':
                    if (isset($lpFixedEncoding) && 'true' === $lpFixedEncoding) {
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
            if ('/chat_files' == $path) {
                $condition .= " AND (docs.session_id = '$sessionId') ";
            }
            // share_folder filter
            $condition .= " AND docs.path != '/shared_folder' ";
        }

        return $condition;
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
        if ('false' === api_get_setting('show_users_folders')) {
            $show_users_condition = " AND docs.path NOT LIKE '%shared_folder%'";
        }

        if ($can_see_invisible) {
            $sessionId = $sessionId ?: api_get_session_id();
            $condition_session = " AND (l.session_id = '$sessionId' OR (l.session_id = '0' OR l.session_id IS NULL) )";
            $condition_session .= self::getSessionFolderFilters($path, $sessionId);

            $sql = "SELECT DISTINCT docs.iid, n.path
                    FROM resource_node AS n
                    INNER JOIN $TABLE_DOCUMENT AS docs
                    ON (docs.resource_node_id = n.id)
                    INNER JOIN resource_link l
                    ON (l.resource_node_id = n.id)
                    WHERE
                        l.c_id = $courseId AND
                        docs.filetype = 'folder' AND
                        $groupCondition AND
                        n.path NOT LIKE '%shared_folder%' AND
                        l.visibility NOT IN ('".ResourceLink::VISIBILITY_DELETED."')
                        $condition_session ";

            if (0 != $groupIid) {
                $sql .= " AND n.path NOT LIKE '%shared_folder%' ";
            } else {
                $sql .= $show_users_condition;
            }

            $result = Database::query($sql);
            if ($result && 0 != Database::num_rows($result)) {
                while ($row = Database::fetch_array($result, 'ASSOC')) {
                    if (self::is_folder_to_avoid($row['path'])) {
                        continue;
                    }

                    if (false !== strpos($row['path'], '/shared_folder/')) {
                        if (!in_array($row['path'], $conditionList)) {
                            continue;
                        }
                    }

                    $folders[$row['iid']] = $row['path'];
                }

                if (!empty($folders)) {
                    natsort($folders);
                }

                return $folders;
            }

            return false;
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
                        l.c_id = $courseId ";
            $result = Database::query($sql);
            $visibleFolders = [];
            while ($row = Database::fetch_array($result, 'ASSOC')) {
                $visibleFolders[$row['id']] = $row['path'];
            }

            if ($getInvisibleList) {
                return $visibleFolders;
            }

            // get invisible folders
            $sql = "SELECT DISTINCT docs.iid, n.path
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
                        l.c_id = $courseId ";
            $result = Database::query($sql);
            $invisibleFolders = [];
            while ($row = Database::fetch_array($result, 'ASSOC')) {
                //get visible folders in the invisible ones -> they are invisible too
                $sql = "SELECT DISTINCT docs.iid, n.path
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
                            l.c_id = $courseId ";
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
        $document = $repo->find($docInfo['iid']);
        if ($document) {
            $repo->hardDelete($document);

            return true;
        }

        return false;
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
        if ('true' === api_get_setting('search_enabled')) {
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
            $sql = "SELECT iid FROM $table
                    WHERE
                        c_id = $courseId AND
                        path LIKE BINARY '$path'
                        $sessionCondition
                    LIMIT 1";

            $result = Database::query($sql);
            if (Database::num_rows($result)) {
                $row = Database::fetch_array($result);

                return (int) $row['iid'];
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
     * @deprecated  use $repo->find()
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

        $TABLE_DOCUMENT = Database::get_course_table(TABLE_DOCUMENT);
        $id = (int) $id;
        $sessionCondition = api_get_session_condition($session_id, true, true);

        $sql = "SELECT * FROM $TABLE_DOCUMENT
                WHERE iid = $id";

        if ($ignoreDeleted) {
            $sql .= " AND path NOT LIKE '%_DELETED_%' ";
        }

        $result = Database::query($sql);
        $courseParam = '&cid='.$course_id.'&id='.$id.'&sid='.$session_id.'&gid='.$groupId;
        if ($result && 1 == Database::num_rows($result)) {
            $row = Database::fetch_array($result, 'ASSOC');
            //@todo need to clarify the name of the URLs not nice right now
            $url_path = urlencode($row['path']);
            $path = str_replace('%2F', '/', $url_path);
            $pathinfo = pathinfo($row['path']);

            $row['url'] = api_get_path(WEB_CODE_PATH).'document/showinframes.php?id='.$id.$courseParam;
            $row['document_url'] = api_get_path(WEB_CODE_PATH).'document/document.php?id='.$id.$courseParam;
            //$row['absolute_path'] = api_get_path(SYS_COURSE_PATH).$course_info['path'].'/document'.$row['path'];
            $row['absolute_path_from_document'] = '/document'.$row['path'];
            //$row['absolute_parent_path'] = api_get_path(SYS_COURSE_PATH).$course_info['path'].'/document'.$pathinfo['dirname'].'/';
            //$row['direct_url'] = $www.$path;
            $row['basename'] = basename($row['path']);

            if ('.' == dirname($row['path'])) {
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
                    if (0 != $session_id && empty($parent_id)) {
                        $parent_id = self::get_document_id($course_info, $real_dir, 0);
                    }
                    if (!empty($parent_id)) {
                        $sub_document_data = self::get_document_data_by_id(
                            $parent_id,
                            $course_code,
                            false,
                            $session_id
                        );
                        if (0 != $session_id and !$sub_document_data) {
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
     * Check document visibility.
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
        $session_id = (int) $session_id;
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
            if (ResourceLink::VISIBILITY_PUBLISHED === $document->getVisibility()) {
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
     * Allow attach a certificate to a course.
     *
     * @todo move to certificate.lib.php
     *
     * @param int $courseId
     * @param int $document_id
     * @param int $sessionId
     */
    public static function attach_gradebook_certificate($courseId, $document_id, $sessionId = 0)
    {
        $tbl_category = Database::get_main_table(TABLE_MAIN_GRADEBOOK_CATEGORY);
        $sessionId = intval($sessionId);
        $courseId = (int) $courseId;
        if (empty($sessionId)) {
            $sessionId = api_get_session_id();
        }

        if (empty($sessionId)) {
            $sql_session = 'AND (session_id = 0 OR isnull(session_id)) ';
        } elseif ($sessionId > 0) {
            $sql_session = 'AND session_id='.$sessionId;
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
        if (0 == $num) {
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
        $user_id = (int) $user_id;
        $tbl_document = Database::get_course_table(TABLE_DOCUMENT);
        $course_id = $courseInfo['real_id'];
        $document_id = self::get_default_certificate_id($course_id, $sessionId);

        $my_content_html = null;
        if ($document_id) {
            $repo = Container::getDocumentRepository();
            $doc = Container::getDocumentRepository()->find($document_id);
            $new_content = '';
            $all_user_info = [];
            if ($doc) {
                $my_content_html = $repo->getResourceFileContent($doc);
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
     * @param bool $is_preview
     *
     * @return array
     */
    public static function get_all_info_to_certificate($user_id, $course_info, $sessionId, $is_preview = false)
    {
        $info_list = [];
        $user_id = (int) $user_id;
        $sessionId = (int) $sessionId;
        $courseCode = $course_info['code'];

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
        $info_grade_certificate = UserManager::get_info_gradebook_certificate($course_info, $sessionId, $user_id);
        $date_long_certificate = '';
        $date_certificate = '';
        $url = '';
        if ($info_grade_certificate) {
            $date_certificate = $info_grade_certificate['created_at'];
            $url = api_get_path(WEB_PATH).'certificates/index.php?id='.$info_grade_certificate['id'];
        }
        $date_no_time = api_convert_and_format_date(api_get_utc_datetime(), DATE_FORMAT_LONG_NO_DAY);
        if (!empty($date_certificate)) {
            $date_long_certificate = api_convert_and_format_date($date_certificate);
            $date_no_time = api_convert_and_format_date($date_certificate, DATE_FORMAT_LONG_NO_DAY);
        }

        if ($is_preview) {
            $date_long_certificate = api_convert_and_format_date(api_get_utc_datetime());
            $date_no_time = api_convert_and_format_date(api_get_utc_datetime(), DATE_FORMAT_LONG_NO_DAY);
        }

        $externalStyleFile = api_get_path(SYS_CSS_PATH).'themes/'.api_get_visual_theme().'/certificate.css';
        $externalStyle = '';
        if (is_file($externalStyleFile)) {
            $externalStyle = file_get_contents($externalStyleFile);
        }
        $timeInCourse = Tracking::get_time_spent_on_the_course($user_id, $course_info['real_id'], $sessionId);
        $timeInCourse = api_time_to_hms($timeInCourse, ':', false, true);

        $timeInCourseInAllSessions = 0;
        $sessions = SessionManager::get_session_by_course($course_info['real_id']);

        if (!empty($sessions)) {
            foreach ($sessions as $session) {
                $timeInCourseInAllSessions += Tracking::get_time_spent_on_the_course($user_id, $course_info['real_id'], $session['id']);
            }
        }
        $timeInCourseInAllSessions = api_time_to_hms($timeInCourseInAllSessions, ':', false, true);

        $first = Tracking::get_first_connection_date_on_the_course($user_id, $course_info['real_id'], $sessionId, false);
        $first = substr($first, 0, 10);
        $last = Tracking::get_last_connection_date_on_the_course($user_id, $course_info, $sessionId, false);
        $last = substr($last, 0, 10);

        if ($first === $last) {
            $startDateAndEndDate = get_lang('From').' '.$first;
        } else {
            $startDateAndEndDate = sprintf(
                get_lang('FromDateXToDateY'),
                $first,
                $last
            );
        }
        $courseDescription = new CourseDescription();
        $description = $courseDescription->get_data_by_description_type(2, $course_info['real_id'], $sessionId);
        $courseObjectives = '';
        if ($description) {
            $courseObjectives = $description['description_content'];
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
            $course_info['code'],
            $course_info['name'],
            isset($info_grade_certificate['grade']) ? $info_grade_certificate['grade'] : '',
            $url,
            '<a href="'.$url.'" target="_blank">'.get_lang('Online link to certificate').'</a>',
            '((certificate_barcode))',
            $externalStyle,
            $timeInCourse,
            $timeInCourseInAllSessions,
            $startDateAndEndDate,
            $courseObjectives,
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
            '((time_in_course))',
            '((time_in_course_in_all_sessions))',
            '((start_date_and_end_date))',
            '((course_objectives))',
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
            if (0 == $session_id || is_null($session_id)) {
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
            $dir_name = '/certificates';
            $post_dir_name = get_lang('Certificates');
            $id = self::get_document_id_of_directory_certificate();
            if (empty($id)) {
                create_unexisting_directory(
                    $courseInfo,
                    api_get_user_id(),
                    api_get_session_id(),
                    0,
                    0,
                    '',
                    $dir_name,
                    $post_dir_name,
                    null,
                    false,
                    false
                );
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
        if (isset($is_certificate_array[0]) && 'certificates' == $is_certificate_array[0]) {
            $is_certificate_mode = true;
        }

        return $is_certificate_mode || (isset($_GET['certificate']) && 'true' === $_GET['certificate']);
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
                            if (false === strpos($source, '.')) {
                                continue; //no dot, should not be an external file anyway
                            }
                            if (strpos($source, 'mailto:')) {
                                continue; //mailto link
                            }
                            if (strpos($source, ';') && !strpos($source, '&amp;')) {
                                continue; //avoid code - that should help
                            }

                            if ('value' == $attr) {
                                if (strpos($source, 'mp3file')) {
                                    $files_list[] = [
                                        substr($source, 0, strpos($source, '.swf') + 4),
                                        'local',
                                        'abs',
                                    ];
                                    $mp3file = substr($source, strpos($source, 'mp3file=') + 8);
                                    if ('/' == substr($mp3file, 0, 1)) {
                                        $files_list[] = [$mp3file, 'local', 'abs'];
                                    } else {
                                        $files_list[] = [$mp3file, 'local', 'rel'];
                                    }
                                } elseif (0 === strpos($source, 'flv=')) {
                                    $source = substr($source, 4);
                                    if (strpos($source, '&') > 0) {
                                        $source = substr($source, 0, strpos($source, '&'));
                                    }
                                    if (strpos($source, '://') > 0) {
                                        if (false !== strpos($source, api_get_path(WEB_PATH))) {
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
                                        if (false !== strpos($second_part, api_get_path(WEB_PATH))) {
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
                                        if ('/' === substr($second_part, 0, 1)) {
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
                                        } elseif (0 === strstr($second_part, '..')) {
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
                                            if ('./' == substr($second_part, 0, 2)) {
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
                                        if (false !== strpos($source, api_get_path(WEB_PATH))) {
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
                                        if ('/' === substr($source, 0, 1)) {
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
                                        } elseif (0 === strstr($source, '..')) {
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
                                            if ('./' == substr($source, 0, 2)) {
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
                                if (false !== strpos($source, api_get_path(WEB_PATH))) {
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
                                if ('/' === substr($source, 0, 1)) {
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
                                } elseif (0 === strpos($source, '..')) {
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
                                    if ('./' == substr($source, 0, 2)) {
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
                            if (!empty($value) && ('\'' == $value[0] || '"' == $value[0])) {
                                $value = substr($value, 1, -1);
                            }

                            if ('API.LMSGetValue(name' == $value) {
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
                    if (false !== strpos($dest_url_query, $origin_course_code)) {
                        $dest_url_query = str_replace($origin_course_code, $destination_course_code, $dest_url_query);
                    }
                }

                if ('local' == $scope_url) {
                    if ('abs' == $type_url || 'rel' == $type_url) {
                        $document_file = strstr($real_orig_path, 'document');

                        if (false !== strpos($real_orig_path, $document_file)) {
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
                            if (false !== strpos($content_html, $real_orig_url)) {
                                $url_course_path = str_replace(
                                    $orig_course_info_path.'/'.$document_file,
                                    '',
                                    $real_orig_path
                                );
                                // See BT#7780
                                $destination_url = $dest_course_path_rel.$document_file.$dest_url_query;
                                // If the course code doesn't exist in the path? what we do? Nothing! see BT#1985
                                if (false === strpos($real_orig_path, $origin_course_code)) {
                                    $url_course_path = $real_orig_path;
                                    $destination_url = $real_orig_path;
                                }
                                $content_html = str_replace($real_orig_url, $destination_url, $content_html);
                            }
                        }

                        // replace origin course code by destination course code  from origin url
                        if (0 === strpos($real_orig_url, '?')) {
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
                if (0 !== $ret_val) { // shell fail, probably 127 (command not found)
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
                if (127 == $ret_val) { // command not found
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
            if (0 !== $ret_val) { // shell fail, probably 127 (command not found)
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
            $courseEntity = api_get_course_entity();
            $repo = Container::getDocumentRepository();
            $total = $repo->getFolderSize($courseEntity->getResourceNode(), $courseEntity);

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

        return '<span class="preview">
                    <audio class="audio_preview skip" src="'.$filePath.'" type="audio/'.$extension.'"></audio>
                </span>';
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
            case 'wav':
            case 'ogg':
            case 'mp3':
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
     * @param bool   $addAudioPreview
     * @param array  $filterByExtension
     *
     * @return string
     */
    public static function get_document_preview(
        Course $course,
        $lp_id = false,
        $target = '',
        $session_id = 0,
        $add_move_button = false,
        $filter_by_folder = null,
        $overwrite_url = '',
        $showInvisibleFiles = false,
        $showOnlyFolders = false,
        $folderId = false,
        $addCloseButton = true,
        $addAudioPreview = false,
        $filterByExtension = []
    ) {
        $repo = Container::getDocumentRepository();
        $nodeRepository = $repo->getResourceNodeRepository();
        $move = get_lang('Move');
        $icon = Display::return_icon('move_everywhere.png', $move, null, ICON_SIZE_TINY);
        $folderIcon = Display::return_icon('lp_folder.png');

        $options = [
            'decorate' => true,
            'rootOpen' => '<ul id="doc_list" class="list-group lp_resource">',
            'rootClose' => '</ul>',
            //'childOpen' => '<li class="doc_resource lp_resource_element ">',
            'childOpen' => function ($child) {
                $id = $child['id'];
                $disableDrag = '';
                if (!$child['resourceFile']) {
                    $disableDrag = ' disable_drag ';
                }

                return '<li
                    id="'.$id.'"
                    data-id="'.$id.'"
                    class=" '.$disableDrag.' list-group-item nested-'.$child['level'].'"
                >';
            },
            'childClose' => '</li>',
            'nodeDecorator' => function ($node) use ($icon, $folderIcon) {
                $disableDrag = '';
                if (!$node['resourceFile']) {
                    $disableDrag = ' disable_drag ';
                }

                $link = '<div class="flex flex-row gap-1 h-4 item_data '.$disableDrag.' ">';
                $file = $node['resourceFile'];
                $extension = '';
                if ($file) {
                    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                }

                $folder = $folderIcon;

                if ($node['resourceFile']) {
                    $link .= '<a class="moved ui-sortable-handle" href="#">';
                    $link .= $icon;
                    $link .= '</a>';
                    $folder = '';
                }

                $link .= '<a
                    data_id="'.$node['id'].'"
                    data_type="document"
                    class="moved ui-sortable-handle link_with_id"
                >';
                $link .= $folder.'&nbsp;';
                $link .= '</a>';
                $link .= cut(addslashes($node['title']), 30);
                $link .= '</div>';

                return $link;
            },
        ];

        $type = $repo->getResourceType();
        $em = Database::getManager();
        $qb = $em
            ->createQueryBuilder()
            ->select('node')
            ->from(ResourceNode::class, 'node')
            ->innerJoin('node.resourceType', 'type')
            ->innerJoin('node.resourceLinks', 'links')
            ->leftJoin('node.resourceFile', 'file')
            ->where('type = :type')
            ->andWhere('links.course = :course')
            ->setParameters(['type' => $type, 'course' => $course])
            ->orderBy('node.parent', 'ASC')
            ->addSelect('file')
        ;

        $sessionId = api_get_session_id();
        if (empty($sessionId)) {
            $qb->andWhere('links.session IS NULL');
        } else {
            $qb
                ->andWhere('links.session = :session')
                ->setParameter('session', $sessionId)
            ;
        }

        if (!empty($filterByExtension)) {
            $orX = $qb->expr()->orX();
            foreach ($filterByExtension as $extension) {
                $orX->add($qb->expr()->like('file.originalName', ':'.$extension));
                $qb->setParameter($extension, '%'.$extension);
            }
            $qb->andWhere($orX);
        }
        $query = $qb->getQuery();

        return $nodeRepository->buildTree($query->getArrayResult(), $options);
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
        if ('true' !== api_get_setting('search_enabled')) {
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
        if (1 == Database::num_rows($result)) {
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
                if (!empty($if_exists) && 'overwrite' == $if_exists) {
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

        if (1 == $systemFolder) {
            $foldersToAvoid = [];
        }

        if ('css' == basename($path)) {
            return true;
        }

        if (false == $is_certificate_mode) {
            //Certificate results
            if (strstr($path, 'certificates')) {
                return true;
            }
        }

        // Admin setting for Hide/Show the folders of all users
        if ('false' == api_get_setting('show_users_folders')) {
            $foldersToAvoid[] = '/shared_folder';

            if (strstr($path, 'shared_folder_session_')) {
                return true;
            }
        }

        // Admin setting for Hide/Show Default folders to all users
        if ('false' == api_get_setting('show_default_folders')) {
            $foldersToAvoid[] = '/images';
            $foldersToAvoid[] = '/flash';
            $foldersToAvoid[] = '/audio';
            $foldersToAvoid[] = '/video';
        }

        // Admin setting for Hide/Show chat history folder
        if ('false' == api_get_setting('show_chat_folder')) {
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
            if ('true' == $settings['documents']) {
                $portalDefaultVisibility = 'visible';
            }

            $defaultVisibility = $portalDefaultVisibility;
        }

        if ('true' === api_get_setting('documents_default_visibility_defined_in_course')) {
            $courseVisibility = api_get_course_setting('documents_default_visibility', $courseCode);
            if (!empty($courseVisibility) && in_array($courseVisibility, ['visible', 'invisible'])) {
                $defaultVisibility = $courseVisibility;
            }
        }

        return $defaultVisibility;
    }

    /**
     * @param array $_course
     *
     * @return CDocument
     */
    public static function createDefaultAudioFolder($_course)
    {
        if (!isset($_course['path'])) {
            return false;
        }

        return self::addDocument($_course, '/audio', 'folder', 0, 'Audio');
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
            0,
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

        $defaultCertificateId = self::get_default_certificate_id($courseData['real_id'], $sessionId);

        if (!isset($defaultCertificateId)) {
            self::attach_gradebook_certificate(
                $courseData['real_id'],
                $document->getIid(),
                $sessionId
            );
        }
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

        if ('.' == $dir) {
            $dir = null;
        }

        if (!empty($dir) && '/' != $dir) {
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
        $sql = "SELECT iid, path FROM $tbl_document
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
        $sql = "SELECT iid, title FROM $table
                WHERE
                    filetype = 'file' AND
                    c_id = $courseId AND
                    (
                        title = '".$fileNameEscape."' OR
                        title = '$fileNameWithSuffix'
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
        $baseName = pathinfo($name, PATHINFO_FILENAME);
        $extension = pathinfo($name, PATHINFO_EXTENSION);

        return uniqid($baseName.'-', true).'.'.$extension;

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

            $sql = "SELECT DISTINCT docs.title, n.path
                    FROM resource_node AS n
                    INNER JOIN $doc_table AS docs
                    ON (docs.resource_node_id = n.id)
                    INNER JOIN resource_link l
                    ON (l.resource_node_id = n.id)
                    WHERE
                        l.c_id = $course_id AND
                        docs.filetype = 'folder' AND
                        n.path IN ('".$folder_sql."') AND
                        l.visibility NOT IN ('".ResourceLink::VISIBILITY_DELETED."')
                         ";

            /*$sql = "SELECT path, title
                    FROM $doc_table
                    WHERE
                        filetype = 'folder' AND
                        c_id = $course_id AND
                        path IN ('".$folder_sql."') ";*/
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
        $form->addElement('hidden', 'cid', api_get_course_int_id());
        $form->addElement('hidden', 'sid', api_get_session_id());
        $form->addElement('hidden', 'gid', api_get_group_id());

        $parent_select = $form->addSelect(
            $selectName,
            get_lang('Current folder'),
            [],
            $attributes
        );

        // Group documents cannot be uploaded in the root
        if (empty($group_dir)) {
            $parent_select->addOption(get_lang('Documents'), '/');

            if (is_array($folders)) {
                foreach ($folders as $folder_id => &$folder) {
                    if (!isset($folder_titles[$folder])) {
                        continue;
                    }
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
                    if ('' != $selected) {
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
                    if ('' != $selected) {
                        $parent_select->setSelected($folder_id);
                    }
                }
            }
        }

        return $form->toHtml();
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
        if ('file' == $type) {
            $icon = choose_image($basename);
            $basename = substr(strrchr($basename, '.'), 1);
        } elseif ('link' == $type) {
            $icon = 'clouddoc.png';
            $basename = get_lang('Cloud file link');
        } else {
            if ('/shared_folder' == $path) {
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

                if ('/audio' == $path) {
                    $icon = 'folder_audio.png';
                    if ($isAllowedToEdit) {
                        $basename = get_lang('INFORMATION VISIBLE TO THE TEACHER ONLY:
This folder contains the default archives. You can clear files or add new ones, but if a file is hidden when it is inserted in a web document, the students will not be able to see it in this document. When inserting a file in a web document, first make sure it is visible. The folders can remain hidden.');
                    } else {
                        $basename = get_lang('Audio');
                    }
                } elseif ('/flash' == $path) {
                    $icon = 'folder_flash.png';
                    if ($isAllowedToEdit) {
                        $basename = get_lang('INFORMATION VISIBLE TO THE TEACHER ONLY:
This folder contains the default archives. You can clear files or add new ones, but if a file is hidden when it is inserted in a web document, the students will not be able to see it in this document. When inserting a file in a web document, first make sure it is visible. The folders can remain hidden.');
                    } else {
                        $basename = get_lang('Flash');
                    }
                } elseif ('/images' == $path) {
                    $icon = 'folder_images.png';
                    if ($isAllowedToEdit) {
                        $basename = get_lang('INFORMATION VISIBLE TO THE TEACHER ONLY:
This folder contains the default archives. You can clear files or add new ones, but if a file is hidden when it is inserted in a web document, the students will not be able to see it in this document. When inserting a file in a web document, first make sure it is visible. The folders can remain hidden.');
                    } else {
                        $basename = get_lang('Images');
                    }
                } elseif ('/video' == $path) {
                    $icon = 'folder_video.png';
                    if ($isAllowedToEdit) {
                        $basename = get_lang('INFORMATION VISIBLE TO THE TEACHER ONLY:
This folder contains the default archives. You can clear files or add new ones, but if a file is hidden when it is inserted in a web document, the students will not be able to see it in this document. When inserting a file in a web document, first make sure it is visible. The folders can remain hidden.');
                    } else {
                        $basename = get_lang('Video');
                    }
                } elseif ('/images/gallery' == $path) {
                    $icon = 'folder_gallery.png';
                    if ($isAllowedToEdit) {
                        $basename = get_lang('INFORMATION VISIBLE TO THE TEACHER ONLY:
This folder contains the default archives. You can clear files or add new ones, but if a file is hidden when it is inserted in a web document, the students will not be able to see it in this document. When inserting a file in a web document, first make sure it is visible. The folders can remain hidden.');
                    } else {
                        $basename = get_lang('Gallery');
                    }
                } elseif ('/chat_files' == $path) {
                    $icon = 'folder_chat.png';
                    if ($isAllowedToEdit) {
                        $basename = get_lang('INFORMATION VISIBLE TO THE TEACHER ONLY:
This folder contains all sessions that have been opened in the chat. Although the chat sessions can often be trivial, others can be really interesting and worthy of being incorporated as an additional work document. To do this without changing the visibility of this folder, make the file visible and link it from where you deem appropriate. It is not recommended to make this folder visible to all.');
                    } else {
                        $basename = get_lang('Chat conversations history');
                    }
                } elseif ('/learning_path' == $path) {
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

    public static function isBasicCourseFolder($path, $sessionId)
    {
        $cleanPath = Security::remove_XSS($path);
        $basicCourseFolder = '/basic-course-documents__'.$sessionId.'__0';

        return $cleanPath == $basicCourseFolder;
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
        if (null === $userEntity) {
            return false;
        }

        $courseEntity = api_get_course_entity($courseInfo['real_id']);
        if (null === $courseEntity) {
            return false;
        }

        $sessionId = empty($sessionId) ? api_get_session_id() : $sessionId;
        $session = api_get_session_entity($sessionId);
        $group = api_get_group_entity($groupId);
        $readonly = (int) $readonly;
        $documentRepo = Container::getDocumentRepository();

        /** @var \Chamilo\CoreBundle\Entity\AbstractResource $parentResource */
        $parentResource = $courseEntity;
        if (!empty($parentId)) {
            $parent = $documentRepo->find($parentId);
            if ($parent) {
                $parentResource = $parent;
            }
        }

        $document = $documentRepo->findCourseResourceByTitle(
            $title,
            $parentResource->getResourceNode(),
            $courseEntity,
            $session,
            $group
        );

        // Document already exists
        if (null !== $document) {
            return $document;
        }

        // is updated using the title
        $document = (new CDocument())
            ->setFiletype($fileType)
            ->setTitle($title)
            ->setComment($comment)
            ->setReadonly(1 === $readonly)
            ->setCreator(api_get_user_entity())
            ->setParent($parentResource)
            ->addCourseLink($courseEntity, $session, $group)
        ;

        $em = Database::getManager();
        $em->persist($document);
        $em->flush();

        $repo = Container::getDocumentRepository();
        if (!empty($content)) {
            $repo->addFileFromString($document, $title, 'text/html', $content, true);
        } else {
            if (!empty($realPath) && !is_dir($realPath) && file_exists($realPath)) {
                $repo->addFileFromPath($document, $title, $realPath);
            }
        }

        if ($document) {
            $allowNotification = api_get_configuration_value('send_notification_when_document_added');
            if ($sendNotification && $allowNotification) {
                $courseTitle = $courseEntity->getTitle();
                if (!empty($sessionId)) {
                    $sessionInfo = api_get_session_info($sessionId);
                    $courseTitle .= ' ( '.$sessionInfo['name'].') ';
                }

                $url = api_get_path(WEB_CODE_PATH).
                    'document/showinframes.php?cid='.$courseEntity->getId().'&sid='.$sessionId.'&id='.$document->getIid();
                $link = Display::url(basename($title), $url, ['target' => '_blank']);
                $userInfo = api_get_user_info($userId);
                $message = sprintf(
                    get_lang('A new document %s has been added to the document tool in your course %s by %s.'),
                    $link,
                    $courseTitle,
                    $userInfo['complete_name']
                );
                $subject = sprintf(get_lang('New document added to course %s'), $courseTitle);
                MessageManager::sendMessageToAllUsersInCourse($subject, $message, $courseEntity, $sessionId);
            }

            return $document;
        }

        return false;
    }
}
