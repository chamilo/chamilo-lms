<?php
/* For licensing terms, see /license.txt */

/**
 *	This is the document library for Chamilo.
 *	It is / will be used to provide a service layer to all document-using tools.
 *	and eliminate code duplication fro group documents, scorm documents, main documents.
 *	Include/require it in your code to use its functionality.
 *
 *	@package chamilo.library
 */
/**
 * Code
 */
class DocumentManager {

    private function __construct() {
    }

    /**
     * @return the document folder quota for the current course, in bytes, or the default quota     
     */
    public static function get_course_quota($course_code = null) {
        if (empty($course_code)) {
            $course_info = api_get_course_info();
        } else {
            $course_info = api_get_course_info($course_code);
        }
        
        $course_quota   = null;
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
     *	Get the content type of a file by checking the extension
     *	We could use mime_content_type() with php-versions > 4.3,
     *	but this doesn't work as it should on Windows installations
     *
     *	@param string $filename or boolean TRUE to return complete array
     *	@author ? first version
     *	@author Bert Vanderkimpen
     *
     */
    public static function file_get_mime_type($filename) {
        // All MIME types in an array (from 1.6, this is the authorative source)
        // Please, keep this alphabetical if you add something to this list!
        $mime_types = array(
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
            'dxf' => 'application/vnd.dxf',
            'dxr' => 'application/x-director',
            'eps' => 'application/postscript',
            'epub' => 'application/epub+zip',
            'etx' => 'text/x-setext',
            'exe' => 'application/octet-stream',
            'ez' => 'application/andrew-inset',
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
            'mp4' => 'video/mpeg4-generic',
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
            'oga'=> 'audio/ogg',
            'ogg'=> 'application/ogg',
            'ogx'=> 'application/ogg',
            'ogv'=> 'video/ogg',
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
            'pps' => 'application/vnd.ms-powerpoint',
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
            'wml' => 'text/vnd.wap.wml',
            'wmlc' => 'application/vnd.wap.wmlc',
            'wmls' => 'text/vnd.wap.wmlscript',
            'wmlsc' => 'application/vnd.wap.wmlscriptc',
            'wma' => 'video/x-ms-wma',
            'wmv' => 'audio/x-ms-wmv',
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
            'zip' => 'application/zip'
            );

        if ($filename === true) {
            return $mime_types;
        }

        //get the extension of the file
        $extension = explode('.', $filename);

        //$filename will be an array if a . was found
        if (is_array($extension)) {
            $extension = strtolower($extension[sizeof($extension) - 1]);
        }
        //file without extension
        else {
            $extension = 'empty';
        }

        //if the extension is found, return the content type
        if (isset($mime_types[$extension])) {
            return $mime_types[$extension];
        }
        //else return octet-stream
        return 'application/octet-stream';
    }

    /**
     *	@return true if the user is allowed to see the document, false otherwise
     *	@author Sergio A Kessler, first version
     *	@author Roan Embrechts, bugfix
     *   @todo ??not only check if a file is visible, but also check if the user is allowed to see the file??
     */
    public static function file_visible_to_user ($this_course, $doc_url) {
        $current_session_id = api_get_session_id();

        $is_allowed_to_edit = api_is_allowed_to_edit(null, true);

        if ($is_allowed_to_edit) {
            return true;
        } else {
            $tbl_document = Database::get_course_table(TABLE_DOCUMENT);
            $tbl_item_property = $this_course.'item_property';
            $doc_url = Database::escape_string($doc_url);
            //$doc_url = addslashes($doc_url);
            $query = "SELECT 1 FROM $tbl_document AS docs,$tbl_item_property AS props
                      WHERE props.tool = 'document' AND docs.id=props.ref AND props.visibility <> '1' AND docs.path = '$doc_url'";
            //echo $query;
            $result = Database::query($query);

            return (Database::num_rows($result) == 0);
        }
    }

    /**
     * This function streams a file to the client
     *
     * @param string $full_file_name
     * @param boolean $forced
     * @param string $name
     * @return false if file doesn't exist, true if stream succeeded
     */
    public static function file_send_for_download($full_file_name, $forced = false, $name = '') {
        if (!is_file($full_file_name)) {
            return false;
        }
        $filename = ($name == '') ? basename($full_file_name) : replace_dangerous_char($name);
        $len = filesize($full_file_name);

        if ($forced) {
            //force the browser to save the file instead of opening it

            header('Content-type: application/octet-stream');
            //header('Content-Type: application/force-download');
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
            header('Content-transfer-encoding: binary');

            $fp = fopen($full_file_name, 'r');
            fpassthru($fp);
            return true;
        } else {
            //no forced download, just let the browser decide what to do according to the mimetype

            $content_type = self::file_get_mime_type($filename);
            header('Expires: Wed, 01 Jan 1990 00:00:00 GMT');
            header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
            // Commented to avoid double caching declaration when playing with IE and HTTPS
            //header('Cache-Control: no-cache, must-revalidate');
            //header('Pragma: no-cache');
            switch ($content_type) {
                case 'text/html':
                    $encoding = @api_detect_encoding_html(file_get_contents($full_file_name));
                    if (!empty($encoding)) {
                        $content_type .= '; charset='.$encoding;
                    }
                    break;
                case 'text/plain':
                    $encoding = @api_detect_encoding(strip_tags(file_get_contents($full_file_name)));
                    if (!empty($encoding)) {
                        $content_type .= '; charset='.$encoding;
                    }
                    break;
            }
            header('Content-type: '.$content_type);
            header('Content-Length: '.$len);
            $user_agent = strtolower($_SERVER['HTTP_USER_AGENT']);
            if (strpos($user_agent, 'msie')) {
                header('Content-Disposition: ; filename= '.$filename);
            } else {
                header('Content-Disposition: inline; filename= '.$filename);
            }
            readfile($full_file_name);
            return true;
        }
    }

    /**
     * This function streams a string to the client for download.
     * You have to ensure that the calling script then stops processing (exit();)
     * otherwise it may cause subsequent use of the page to want to download
     * other pages in php rather than interpreting them.
     *
     * @param string The string contents
     * @param boolean Whether "save" mode is forced (or opening directly authorized)
     * @param string The name of the file in the end (including extension)
     * @return false if file doesn't exist, true if stream succeeded
     */
    public static function string_send_for_download($full_string, $forced = false, $name = '') {
        $filename = $name;
        $len = strlen($full_string);

        if ($forced) {
            //force the browser to save the file instead of opening it

            header('Content-type: application/octet-stream');
            //header('Content-Type: application/force-download');
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
            header('Content-transfer-encoding: binary');

            //$fp = fopen($full_string, 'r');
            //fpassthru($fp);
            echo $full_string;
            return true;
            //You have to ensure that the calling script then stops processing (exit();)
            //otherwise it may cause subsequent use of the page to want to download
            //other pages in php rather than interpreting them.
        } else {
            //no forced download, just let the browser decide what to do according to the mimetype

            $content_type = self::file_get_mime_type($filename);
            header('Expires: Wed, 01 Jan 1990 00:00:00 GMT');
            header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
            header('Cache-Control: no-cache, must-revalidate');
            header('Pragma: no-cache');
            switch ($content_type) {
                case 'text/html':
                    $encoding = @api_detect_encoding_html($full_string);
                    if (!empty($encoding)) {
                        $content_type .= '; charset='.$encoding;
                    }
                    break;
                case 'text/plain':
                    $encoding = @api_detect_encoding(strip_tags($full_string));
                    if (!empty($encoding)) {
                        $content_type .= '; charset='.$encoding;
                    }
                    break;
            }
            header('Content-type: '.$content_type);
            header('Content-Length: '.$len);
            $user_agent = strtolower($_SERVER['HTTP_USER_AGENT']);
            if (strpos($user_agent, 'msie')) {
                header('Content-Disposition: ; filename= '.$filename);
            } else {
                header('Content-Disposition: inline; filename= '.$filename);
            }
            echo($full_string);
            //You have to ensure that the calling script then stops processing (exit();)
            //otherwise it may cause subsequent use of the page to want to download
            //other pages in php rather than interpreting them.
            return true;
        }
    }

    /**
     * Fetches all document data for the given user/group
     *
     * @param array $_course
     * @param string $path
     * @param int $to_group_id
     * @param int $to_user_id
     * @param boolean $can_see_invisible
     * @return array with all document data
     */
    public static function get_all_document_data($_course, $path = '/', $to_group_id = 0, $to_user_id = NULL, $can_see_invisible = false, $search = false) {
        $TABLE_ITEMPROPERTY = Database::get_course_table(TABLE_ITEM_PROPERTY);
        $TABLE_DOCUMENT     = Database::get_course_table(TABLE_DOCUMENT);
        $TABLE_COURSE       = Database::get_main_table(TABLE_MAIN_COURSE);

        //if to_user_id = NULL -> change query (IS NULL)
        //$to_user_id = (is_null($to_user_id)) ? 'IS NULL' : '= '.$to_user_id;
        if (!is_null($to_user_id)) {
            $to_field = 'last.to_user_id';
            $to_value = $to_user_id;
        } else {
            $to_field = 'last.to_group_id';
            $to_value = $to_group_id;
        }

        //escape underscores in the path so they don't act as a wildcard
        $path 		= Database::escape_string(str_replace('_', '\_', $path));
        $to_user_id = Database::escape_string($to_user_id);
        $to_value	= Database::escape_string($to_value);

        //if they can't see invisible files, they can only see files with visibility 1
        //$visibility_bit = ' = 1';
        //if they can see invisible files, only deleted files (visibility 2) are filtered out
        //if ($can_see_invisible) {
        $visibility_bit = ' <> 2';
        //}

        //the given path will not end with a slash, unless it's the root '/'
        //so no root -> add slash
        $added_slash = ($path == '/') ? '' : '/';

        //condition for the session
        $current_session_id = api_get_session_id();
        $condition_session = " AND (id_session = '$current_session_id' OR id_session = '0')";

        if (!$can_see_invisible) {
            //$condition_session = " AND (id_session = '$current_session_id' ) ";
        }

        //condition for search (get ALL folders and documents)
        if ($search) {
            $sql = "SELECT docs.id, docs.filetype, docs.path, docs.title, docs.comment, docs.size, docs.readonly, docs.session_id, last.id_session item_property_session_id, last.lastedit_date, last.visibility, last.insert_user_id
                        FROM  ".$TABLE_ITEMPROPERTY."  AS last, ".$TABLE_DOCUMENT."  AS docs
                        WHERE docs.id = last.ref
                        AND last.tool = '".TOOL_DOCUMENT."'
                        AND ".$to_field." = ".$to_value."
                        AND last.visibility".$visibility_bit . $condition_session." AND
            			docs.c_id = {$_course['real_id']} AND
            			last.c_id = {$_course['real_id']}  ";
        } else {
            $sql = "SELECT docs.id, docs.filetype, docs.path, docs.title, docs.comment, docs.size, docs.readonly, docs.session_id, last.id_session item_property_session_id, last.lastedit_date, last.visibility, last.insert_user_id
                        FROM  ".$TABLE_ITEMPROPERTY."  AS last, ".$TABLE_DOCUMENT."  AS docs
                        WHERE docs.id = last.ref
                        AND docs.path LIKE '".$path.$added_slash."%'
                        AND docs.path NOT LIKE '".$path.$added_slash."%/%'
                        AND last.tool = '".TOOL_DOCUMENT."'
                        AND ".$to_field." = ".$to_value."
                        AND last.visibility".$visibility_bit.$condition_session." AND
            			docs.c_id = {$_course['real_id']} AND
            			last.c_id = {$_course['real_id']}  ";
        }

        $result = Database::query($sql);

        $doc_list = array();
        $document_data = array();
        $is_allowed_to_edit = api_is_allowed_to_edit(null, true);

        if ($result !== false && Database::num_rows($result) != 0) {
            while ($row = Database::fetch_array($result, 'ASSOC')) {

                if (api_is_coach()) {
                    //Looking for course items that are invisible to hide it in the session
                    if (in_array($row['id'], array_keys($doc_list))) {
                        if ($doc_list[$row['id']]['item_property_session_id'] == 0 && $doc_list[$row['id']]['session_id'] == 0) {
                            if ($doc_list[$row['id']]['visibility'] == 0) {
                                unset($document_data[$row['id']]);
                                continue;
                            }
                        }
                    }

                    $doc_list[$row['id']] = $row;
                }

                if (!api_is_coach() && !$is_allowed_to_edit) {
                    $doc_list[] = $row;
                }


                if ($row['filetype'] == 'file' && pathinfo($row['path'], PATHINFO_EXTENSION) == 'html') {
                    //Templates management
                    $table_template = Database::get_main_table(TABLE_MAIN_TEMPLATES);
                    $sql_is_template = "SELECT id FROM $table_template
                                        WHERE course_code='".$_course['id']."'
                                        AND user_id='".api_get_user_id()."'
                                        AND ref_doc='".$row['id']."'";
                    $template_result = Database::query($sql_is_template);
                    $row['is_template'] = (Database::num_rows($template_result) > 0) ? 1 : 0;
                }
                //just filling $document_data
                $document_data[$row['id']] = $row;
            }



            //Only for the student we filter the results see BT#1652
            if (!api_is_coach() && !$is_allowed_to_edit) {
                $ids_to_remove = array();
                $my_repeat_ids = $temp= array();

                //Selecting repetead ids
                foreach($doc_list as $row ) {
                    if (in_array($row['id'], array_keys($temp))) {
                        $my_repeat_ids[] = $row['id'];
                    }
                    $temp[$row['id']] = $row;
                }
				//@todo use the DocumentManager::is_visible function

                //Checking disponibility in a session
                foreach($my_repeat_ids as $id) {
                    foreach($doc_list as $row ) {
                        if ($id == $row['id']) {
                            //var_dump($row['visibility'].' - '.$row['session_id'].' - '.$row['item_property_session_id']);
                            if ($row['visibility'] == 0 && $row['item_property_session_id'] == 0) {
                                $delete_repeated[$id] = true;
                            }
                            if ($row['visibility'] == 0 && $row['item_property_session_id'] != 0) {
                                $delete_repeated[$id] = true;
                            }
                        }
                    }
                }

                //var_dump($delete_repeated);

                foreach($doc_list as $key=>$row) {
                    //&& !in_array($row['id'],$my_repeat_ids)
                    //var_dump($row['id'].' - '.$row['visibility']);
                    if (in_array($row['visibility'], array('0','2')) && !in_array($row['id'],$my_repeat_ids) ) {
                        $ids_to_remove[] = $row['id'];
                        unset($doc_list[$key]);
                    }
                }
                //var_dump($ids_to_remove);

                foreach($document_data as $row) {
                    if (in_array($row['id'], $ids_to_remove)) {
                        unset($document_data[$row['id']]);
                    }
                    if (isset($delete_repeated[$row['id']]) && $delete_repeated[$row['id']]) {
                        unset($document_data[$row['id']]);
                    }
                }

                //Checking parents visibility
                $final_document_data = array();
                foreach($document_data as $row) {
                	$is_visible = DocumentManager::check_visibility_tree($row['id'], $_course['code'], $current_session_id, api_get_user_id());
                	if ($is_visible) {
                		$final_document_data[$row['id']]=$row;
                	}
                }
            } else {
            	$final_document_data = $document_data;
            }
            return $final_document_data;
        } else {
            //display_error("Error getting document info from database (".Database::error().")!");
            return false;
        }
    }

    /**
     * Gets the paths of all folders in a course
     * can show all folders (exept for the deleted ones) or only visible ones
     * @param array $_course
     * @param boolean $can_see_invisible
     * @param int $to_group_id
     * @return array with paths
     */
    public static function get_all_document_folders ($_course, $to_group_id = '0', $can_see_invisible = false) {
        $TABLE_ITEMPROPERTY = Database::get_course_table(TABLE_ITEM_PROPERTY);
        $TABLE_DOCUMENT     = Database::get_course_table(TABLE_DOCUMENT);

        $to_group_id = intval($to_group_id);

        if ($can_see_invisible) {
            //condition for the session
            $session_id = api_get_session_id();
            $condition_session = api_get_session_condition($session_id);
            $sql = "SELECT DISTINCT docs.id, path FROM  ".$TABLE_ITEMPROPERTY."  AS last, ".$TABLE_DOCUMENT."  AS docs
					WHERE 	docs.id 			= last.ref AND
							docs.filetype 		= 'folder' AND
							last.tool 			= '".TOOL_DOCUMENT."' AND
							last.to_group_id	= ".$to_group_id." AND
            				last.visibility 	<> 2 $condition_session AND
                            last.c_id           = {$_course['real_id']} AND
            				docs.c_id 			= {$_course['real_id']} ";

            $result = Database::query($sql);

            if ($result && Database::num_rows($result) != 0) {
                while ($row = Database::fetch_array($result, 'ASSOC')) {
                    $document_folders[$row['id']] = $row['path'];
                }
                //sort($document_folders);
                natsort($document_folders);

                //return results
                return $document_folders;
            } else {
                return false;
            }
        } else {
            //no invisible folders

            //condition for the session
            $session_id = api_get_session_id();
            $condition_session = api_get_session_condition($session_id);
            //get visible folders
            $visible_sql = "SELECT DISTINCT docs.id, path
                        FROM  ".$TABLE_ITEMPROPERTY."  AS last, ".$TABLE_DOCUMENT."  AS docs
                        WHERE docs.id = last.ref
                        AND docs.filetype = 'folder'
                        AND last.tool = '".TOOL_DOCUMENT."'
                        AND last.to_group_id = ".$to_group_id."
                        AND last.visibility = 1 $condition_session AND
                        last.c_id = {$_course['real_id']}  AND
                        docs.c_id = {$_course['real_id']} ";
            $visibleresult = Database::query($visible_sql);
            while ($all_visible_folders = Database::fetch_array($visibleresult, 'ASSOC')) {
                $visiblefolders[$all_visible_folders['id']] = $all_visible_folders['path'];
            }
            //condition for the session
            $session_id = api_get_session_id();
            $condition_session = api_get_session_condition($session_id);
            //get invisible folders
            $invisible_sql = "SELECT DISTINCT docs.id, path
                        FROM  ".$TABLE_ITEMPROPERTY."  AS last, ".$TABLE_DOCUMENT."  AS docs
                        WHERE docs.id = last.ref
                        AND docs.filetype = 'folder'
                        AND last.tool = '".TOOL_DOCUMENT."'
                        AND last.to_group_id = ".$to_group_id."
                        AND last.visibility = 0 $condition_session AND
                        last.c_id = {$_course['real_id']}  AND
                        docs.c_id = {$_course['real_id']} ";
            $invisibleresult = Database::query($invisible_sql);
            while ($invisible_folders = Database::fetch_array($invisibleresult, 'ASSOC')) {
                //condition for the session
                $session_id = api_get_session_id();
                $condition_session = api_get_session_condition($session_id);
                //get visible folders in the invisible ones -> they are invisible too
                $folder_in_invisible_sql = "SELECT DISTINCT docs.id, path
                                FROM  ".$TABLE_ITEMPROPERTY."  AS last, ".$TABLE_DOCUMENT."  AS docs
                                WHERE docs.id = last.ref
                                AND docs.path LIKE '".Database::escape_string($invisible_folders['path'])."/%'
                                AND docs.filetype = 'folder'
                                AND last.tool = '".TOOL_DOCUMENT."'
                                AND last.to_group_id = ".$to_group_id."
                                AND last.visibility = 1 $condition_session AND
                                last.c_id = {$_course['real_id']}  AND
                                docs.c_id = {$_course['real_id']}  ";
                $folder_in_invisible_result = Database::query($folder_in_invisible_sql);
                while ($folders_in_invisible_folder = Database::fetch_array($folder_in_invisible_result, 'ASSOC')) {
                    $invisiblefolders[$folders_in_invisible_folder['id']] = $folders_in_invisible_folder['path'];
                }
            }

            //if both results are arrays -> //calculate the difference between the 2 arrays -> only visible folders are left :)
            if (is_array($visiblefolders) && is_array($invisiblefolders)) {
                $document_folders = array_diff($visiblefolders, $invisiblefolders);
                natsort($document_folders);
                return $document_folders;
            } elseif (is_array($visiblefolders)) {
            	//only visible folders found
                //sort($visiblefolders);
                natsort($visiblefolders);
                return $visiblefolders;
            } else {
            	//no visible folders found
                return false;
            }
        }
    }

    /**
     * This check if a document has the readonly property checked, then see if the user
     * is the owner of this file, if all this is true then return true.
     *
     * @param array  $_course
     * @param int    $user_id id of the current user
     * @param string $file path stored in the database
     * @param int    $document_id in case you dont have the file path ,insert the id of the file here and leave $file in blank ''
     * @return boolean true/false
     **/
    public static function check_readonly($_course, $user_id, $file,$document_id = '', $to_delete = false) {
        if (!(!empty($document_id) && is_numeric($document_id))) {
            $document_id = self::get_document_id($_course, $file);
        }
        $TABLE_PROPERTY = Database::get_course_table(TABLE_ITEM_PROPERTY);
        $TABLE_DOCUMENT = Database::get_course_table(TABLE_DOCUMENT);
		$course_id 		= $_course['real_id'];

        if ($to_delete) {
            if (self::is_folder($_course, $document_id)) {
                if (!empty($file)) {
                    $path = Database::escape_string($file);
                    $what_to_check_sql = "SELECT td.id, readonly, tp.insert_user_id FROM ".$TABLE_DOCUMENT." td , $TABLE_PROPERTY tp
                                          WHERE td.c_id = $course_id AND
                                          		tp.c_id = $course_id AND
                    							tp.ref= td.id AND
                    							(path='".$path."' OR path LIKE BINARY '".$path."/%' ) ";
                    //get all id's of documents that are deleted
                    $what_to_check_result = Database::query($what_to_check_sql);

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
            $sql= "SELECT a.insert_user_id, b.readonly FROM $TABLE_PROPERTY a, $TABLE_DOCUMENT b
                   WHERE
            			a.c_id = $course_id AND
                        b.c_id = $course_id AND
            			a.ref = b.id and a.ref= $document_id LIMIT 1";
            $resultans   =  Database::query($sql);
            $doc_details =  Database ::fetch_array($resultans, 'ASSOC');

            if ($doc_details['readonly'] == 1) {
                return !($doc_details['insert_user_id'] == $user_id || api_is_platform_admin());
            }
        }
        return false;
    }

    /**
     * This check if a document is a folder or not
     * @param array  $_course
     * @param int    $document_id of the item
     * @return boolean true/false
     **/
    public static function is_folder($_course, $document_id) {
        $TABLE_DOCUMENT = Database::get_course_table(TABLE_DOCUMENT);
		$course_id = $_course['real_id'];
        $document_id = Database::escape_string($document_id);
        $result = Database::fetch_array(Database::query("SELECT filetype FROM $TABLE_DOCUMENT WHERE c_id = $course_id AND id= $document_id"), 'ASSOC');
        return $result['filetype'] == 'folder';
    }

    /**
     * This deletes a document by changing visibility to 2, renaming it to filename_DELETED_#id
     * Files/folders that are inside a deleted folder get visibility 2
     *
     * @param array $_course
     * @param string $path, path stored in the database
     * @param string ,$base_work_dir, path to the documents folder
     * @return boolean true/false
     * @todo now only files/folders in a folder get visibility 2, we should rename them too.
     */
    public static function delete_document($_course, $path, $base_work_dir) {

        $TABLE_DOCUMENT 	= Database :: get_course_table(TABLE_DOCUMENT);
        $TABLE_ITEMPROPERTY = Database :: get_course_table(TABLE_ITEM_PROPERTY);

        $course_id = $_course['real_id'];

        //first, delete the actual document...
        $document_id = self :: get_document_id($_course, $path);
        $new_path = $path.'_DELETED_'.$document_id;
        $current_session_id = api_get_session_id();
        if ($document_id) {
            if (api_get_setting('permanently_remove_deleted_files') == 'true') { //deleted files are *really* deleted
                $what_to_delete_sql = "SELECT id FROM ".$TABLE_DOCUMENT." WHERE c_id = $course_id AND path='".$path."' OR path LIKE BINARY '".$path."/%'";
                //get all id's of documents that are deleted
                $what_to_delete_result = Database::query($what_to_delete_sql);

                if ($what_to_delete_result && Database::num_rows($what_to_delete_result) != 0) {
                    //needed to deleted medadata
                    require_once api_get_path(SYS_CODE_PATH).'metadata/md_funcs.php';
                    require_once api_get_path(LIBRARY_PATH).'fileManage.lib.php';
                    $mdStore = new mdstore(true);

                    //delete all item_property entries
                    while ($row = Database::fetch_array($what_to_delete_result)) {
                        //query to delete from item_property table
                        //avoid wrong behavior

                        //$remove_from_item_property_sql = "DELETE FROM ".$TABLE_ITEMPROPERTY." WHERE ref = ".$row['id']." AND tool='".TOOL_DOCUMENT."'";
                        api_item_property_update($_course, TOOL_DOCUMENT, $row['id'], 'delete', api_get_user_id(), null, null, null, null, $current_session_id);

                        //query to delete from document table
                        $remove_from_document_sql = "DELETE FROM ".$TABLE_DOCUMENT." WHERE c_id = $course_id AND id = ".$row['id'];
                        self::unset_document_as_template($row['id'], $_course, api_get_user_id());
                        Database::query($remove_from_document_sql);

                        //delete metadata
                        $eid = 'Document'.'.'.$row['id'];
                        $mdStore->mds_delete($eid);
                        $mdStore->mds_delete_offspring($eid);

                    }
                    self::delete_document_from_search_engine(api_get_course_id(), $document_id);
                    //delete documents, do it like this so metadata get's deleted too
                    //update_db_info('delete', $path);
                    //throw it away
                    my_delete($base_work_dir.$path);

                    return true;
                } else {
                    return false;
                }

            } else { //set visibility to 2 and rename file/folder to qsdqsd_DELETED_#id

                if (api_item_property_update($_course, TOOL_DOCUMENT, $document_id, 'delete', api_get_user_id(), null, null, null, null, $current_session_id)) {
                    if (is_file($base_work_dir.$path) || is_dir($base_work_dir.$path)) {
                        if(rename($base_work_dir.$path, $base_work_dir.$new_path)) {
                            self::unset_document_as_template($document_id, api_get_course_id(), api_get_user_id());
                            $sql = "UPDATE $TABLE_DOCUMENT set path='".$new_path."' WHERE c_id = $course_id AND id='".$document_id."'";
                            if (Database::query($sql)) {
                                //if it is a folder it can contain files
                                $sql = "SELECT id,path FROM ".$TABLE_DOCUMENT." WHERE c_id = $course_id AND path LIKE BINARY '".$path."/%'";
                                $result = Database::query($sql);
                                if ($result && Database::num_rows($result) > 0) {
                                    while ($deleted_items = Database::fetch_array($result, 'ASSOC')) {
                                        api_item_property_update($_course, TOOL_DOCUMENT, $deleted_items['id'], 'delete', api_get_user_id(),null,null,null,null,$current_session_id);
                                        //Change path of subfolders and documents in database
                                        $old_item_path = $deleted_items['path'];
                                        $new_item_path = $new_path.substr($old_item_path, strlen($path));
                                        /*
                                         // Trying to fix this bug FS#2681
                                         echo $base_work_dir.$old_item_path;
                                         echo "<br />";
                                         echo $base_work_dir.$new_item_path;
                                         echo "<br /><br />";
                                         rename($base_work_dir.$old_item_path, $base_work_dir.$new_item_path);
                                         */
                                        self::unset_document_as_template($deleted_items['id'], api_get_course_id(), api_get_user_id());
                                        $sql = "UPDATE $TABLE_DOCUMENT set path = '".$new_item_path."' WHERE c_id = $course_id AND id = ".$deleted_items['id'];

                                        Database::query($sql);
                                    }
                                }

                                self::delete_document_from_search_engine(api_get_course_id(), $document_id);
                                return true;
                            }
                        } else {
                            //Couldn't rename - file permissions problem?
                            error_log(__FILE__.' '.__LINE__.': Error renaming '.$base_work_dir.$path.' to '.$base_work_dir.$new_path.'. This is probably due to file permissions',0);
                        }

                    } else {

                        //echo $base_work_dir.$path;
                        //The file or directory isn't there anymore (on the filesystem)
                        // This means it has been removed externally. To prevent a
                        // blocking error from happening, we drop the related items from the
                        // item_property and the document table.
                        error_log(__FILE__.' '.__LINE__.': System inconsistency detected. The file or directory '.$base_work_dir.$path.' seems to have been removed from the filesystem independently from the web platform. To restore consistency, the elements using the same path will be removed from the database',0);

                        $sql = "SELECT id FROM $TABLE_DOCUMENT WHERE c_id = $course_id AND path='".$path."' OR path LIKE BINARY '".$path."/%'";
                        $res = Database::query($sql);

                        self::delete_document_from_search_engine(api_get_course_id(), $document_id);

                        while ($row = Database::fetch_array($res)) {
                            $sqlipd = "DELETE FROM $TABLE_ITEMPROPERTY WHERE c_id = $course_id AND ref = ".$row['id']." AND tool='".TOOL_DOCUMENT."'";
                            Database::query($sqlipd);
                            self::unset_document_as_template($row['id'],api_get_course_id(), api_get_user_id());
                            $sqldd = "DELETE FROM $TABLE_DOCUMENT WHERE c_id = $course_id AND id = ".$row['id'];
                            Database::query($sqldd);
                        }
                    }
                }
            }

        }

        return false;
    }

    /**
     * Removes documents from search engine database
     *
     * @param string $course_id Course code
     * @param int $document_id Document id to delete
     */
    public static function delete_document_from_search_engine ($course_id, $document_id) {
        // remove from search engine if enabled
        if (api_get_setting('search_enabled') == 'true') {
            $tbl_se_ref = Database::get_main_table(TABLE_MAIN_SEARCH_ENGINE_REF);
            $sql = 'SELECT * FROM %s WHERE course_code=\'%s\' AND tool_id=\'%s\' AND ref_id_high_level=%s LIMIT 1';
            $sql = sprintf($sql, $tbl_se_ref, $course_id, TOOL_DOCUMENT, $document_id);
            $res = Database::query($sql);
            if (Database::num_rows($res) > 0) {
                $row2 = Database::fetch_array($res);
                require_once api_get_path(LIBRARY_PATH) .'search/DokeosIndexer.class.php';
                $di = new DokeosIndexer();
                $di->remove_document((int)$row2['search_did']);
            }
            $sql = 'DELETE FROM %s WHERE course_code=\'%s\' AND tool_id=\'%s\' AND ref_id_high_level=%s LIMIT 1';
            $sql = sprintf($sql, $tbl_se_ref, $course_id, TOOL_DOCUMENT, $document_id);
            Database::query($sql);

            // remove terms from db
            require_once api_get_path(LIBRARY_PATH) .'specific_fields_manager.lib.php';
            delete_all_values_for_item($course_id, TOOL_DOCUMENT, $document_id);
        }
    }

    /**
     * Gets the id of a document with a given path
     *
     * @param array $_course
     * @param string $path
     * @return int id of document / false if no doc found
     */
    public static function get_document_id($course_info, $path) {
        $TABLE_DOCUMENT = Database :: get_course_table(TABLE_DOCUMENT);
        $course_id = $course_info['real_id'];
        $path = Database::escape_string($path);
        if (!empty($course_id) && !empty($path)) {
            $sql = "SELECT id FROM $TABLE_DOCUMENT WHERE c_id = $course_id AND path LIKE BINARY '$path' LIMIT 1";
            $result = Database::query($sql);
            if ($result && Database::num_rows($result)) {
                $row = Database::fetch_array($result);
                return intval($row[0]);
            }
        }
        return false;
    }

    /**
     * Gets the document data with a given id
     *
     * @param array $_course
     * @param string $path
     * @todo load parent_id
     * @return int id of document / false if no doc found
     */
    public static function get_document_data_by_id($id, $course_code, $load_parents = false) {
        $course_info = api_get_course_info($course_code);
        $course_id 	 = $course_info['real_id'];

        if (empty($course_info)) {
            return false;
        }
        $www = api_get_path(WEB_COURSE_PATH).$course_info['path'].'/document';

        $TABLE_DOCUMENT = Database :: get_course_table(TABLE_DOCUMENT);
        $id = intval($id);
        $sql = "SELECT * FROM $TABLE_DOCUMENT WHERE c_id = $course_id AND id = $id ";
        $result = Database::query($sql);
        if ($result && Database::num_rows($result) == 1) {
            $row = Database::fetch_array($result,'ASSOC');

            //@todo need to clarify the name of the URLs not nice right now
            $url_path = urlencode($row['path']);
            $path 	  = str_replace('%2F', '/',$url_path);

            $row['url'] 			= api_get_path(WEB_CODE_PATH).'document/showinframes.php?cidReq='.$course_code.'&id='.$id;
            $row['document_url'] 	= api_get_path(WEB_CODE_PATH).'document/document.php?cidReq='.$course_code.'&id='.$id;
            $row['absolute_path']   = api_get_path(SYS_COURSE_PATH).$course_info['path'].'/document'.$row['path'];
            
            $row['absolute_path_from_document']   = '/document'.$row['path'];
           
            $pathinfo = pathinfo($row['path']);

            $row['absolute_parent_path']   = api_get_path(SYS_COURSE_PATH).$course_info['path'].'/document'.$pathinfo['dirname'].'/';


            $row['direct_url'] 		= $www.$path;

            if (dirname($row['path']) == '.') {
            	$row['parent_id'] = '0';
            } else {
            	$row['parent_id'] = self::get_document_id($course_info, dirname($row['path']));
            }
            $parents = array();

            //Use to generate parents (needed for the breadcrumb)
            //@todo sorry but this for is here because there's not a parent_id in the document table so we parsed the path!!

            $visibility = true;

            if ($load_parents) {
            	$dir_array = explode('/', $row['path']);
            	$dir_array = array_filter($dir_array);
            	$array_len = count($dir_array) +1 ;
            	$real_dir  = '';

            	for ($i = 1; $i < $array_len; $i++) {
            		$sub_visibility = true;
            		$real_dir .= '/'.$dir_array[$i];
            		$parent_id = self::get_document_id($course_info, $real_dir);
            		if (!empty($parent_id)) {
            			 $sub_document_data = self::get_document_data_by_id($parent_id, $course_code, false);
            			 //@todo add visibility here

            			 /*$sub_visibility    = self::is_visible_by_id($parent_id, $course_info, api_get_session_id(), api_get_user_id());
            			 if ($visibility && $sub_visibility == false) {
            			 	$visibility = false;
            			 }
            			 */
            			 $parents[] = $sub_document_data;
            		}
            	}
            }
            //$row['visibility_for_user'] = $visibility;
            $row['parents'] = $parents;
            return $row;
        }
        return false;
    }


    /**
     * Allow to set a specific document as a new template for FCKEditor for a particular user in a particular course
     *
     * @param string $title
     * @param string $description
     * @param int $document_id_for_template the document id
     * @param string $couse_code
     * @param int $user_id
     */
    public static function set_document_as_template($title, $description, $document_id_for_template, $couse_code, $user_id, $image) {
        // Database table definition
        $table_template = Database::get_main_table(TABLE_MAIN_TEMPLATES);

        // creating the sql statement
        $sql = "INSERT INTO ".$table_template."
                    (title, description, course_code, user_id, ref_doc, image)
                VALUES (
                    '".Database::escape_string($title)."',
                    '".Database::escape_string($description)."',
                    '".Database::escape_string($couse_code)."',
                    '".Database::escape_string($user_id)."',
                    '".Database::escape_string($document_id_for_template)."',
                    '".Database::escape_string($image)."')";
        Database::query($sql);

        return true;
    }


    /**
     * Unset a document as template
     *
     * @param int $document_id
     * @param string $couse_code
     * @param int $user_id
     */
    public static function unset_document_as_template($document_id, $course_code, $user_id) {

        $table_template = Database::get_main_table(TABLE_MAIN_TEMPLATES);
        $course_code = Database::escape_string($course_code);
        $user_id = Database::escape_string($user_id);
        $document_id = Database::escape_string($document_id);

        $sql = 'SELECT id FROM '.$table_template.' WHERE course_code="'.$course_code.'" AND user_id="'.$user_id.'" AND ref_doc="'.$document_id.'"';
        $result = Database::query($sql);
        $template_id = Database::result($result,0,0);

        include_once(api_get_path(LIBRARY_PATH) . 'fileManage.lib.php');
        my_delete(api_get_path(SYS_CODE_PATH).'upload/template_thumbnails/'.$template_id.'.jpg');

        $sql = 'DELETE FROM '.$table_template.' WHERE course_code="'.$course_code.'" AND user_id="'.$user_id.'" AND ref_doc="'.$document_id.'"';

        Database::query($sql);
    }

    /**
     * Return true if the documentpath have visibility=1 as item_property (you should use the is_visible_by_id)
     *
     * @param string $document_path the relative complete path of the document
     * @param array  $course the _course array info of the document's course
     */
    public static function is_visible($doc_path, $course, $session_id = 0, $file_type = 'file') {
        $docTable  = Database::get_course_table(TABLE_DOCUMENT);
        $propTable = Database::get_course_table(TABLE_ITEM_PROPERTY);

        $course_id = $course['real_id'];
        //note the extra / at the end of doc_path to match every path in the document table that is part of the document path
        $doc_path = Database::escape_string($doc_path);

        $session_id = intval($session_id);
        $condition = "AND id_session IN  ('$session_id', '0') ";
        // The " d.filetype='file' " let the user see a file even if the folder is hidden see #2198

        //When using hotpotatoes files, new files are generated in the hotpotatoe folder, if user_id=1 does the exam a new html file will be generated: hotpotatoe.html.(user_id).t.html
        //so we remove that string in order to find correctly the origin file
        if (strpos($doc_path, 'HotPotatoes_files')) {
//            $doc_path = substr($doc_path, 0, strlen($doc_path) - 8);
            $doc_path = substr($doc_path, 0, strlen($doc_path) - 7 - strlen(api_get_user_id()));
        }

        if (!in_array($file_type, array('file','folder'))) {
            $file_type = 'file';
        }

        $sql  = "SELECT visibility FROM $docTable d, $propTable ip
        		 WHERE 	d.c_id  = $course_id  AND
        		 		ip.c_id = $course_id AND
        				d.id = ip.ref AND
        				ip.tool = '".TOOL_DOCUMENT."' $condition AND
        				filetype = '$file_type' AND locate(concat(path,'/'),'".$doc_path."/')=1";

        $result = Database::query($sql);
        $is_visible = false;
        if (Database::num_rows($result) > 0) {
            $row = Database::fetch_array($result,'ASSOC');
            if ($row['visibility'] == 1) {
                $is_visible = $_SESSION['is_allowed_in_course'] || api_is_platform_admin();
            }
        }
        //improved protection of documents viewable directly through the url: incorporates the same protections of the course at the url of documents:	access allowed for the whole world Open, access allowed for users registered on the platform Private access, document accessible only to course members (see the Users list), Completely closed; the document is only accessible to the course admin and teaching assistants.
        //return $_SESSION ['is_allowed_in_course'] || api_is_platform_admin();
        return $is_visible;
    }

    /**
     * Return true if user can see a file
     *
     * @param   int     document id
     * @param   array   course info
     * @param   array  	$course the _course array info of the document's course
     * @return  bool
     */
    public static function is_visible_by_id($doc_id, $course_info, $session_id, $user_id, $admins_can_see_everything = true) {
    	$is_visible		= false;
    	$user_in_course = false;

    	//1. Checking the course array
    	if (empty($course_info)) {
    		$course_info = api_get_course_info();
    		if (empty($course_info)) {
    			return false;
    		}
    	}

    	$doc_id     = intval($doc_id);
    	$session_id = intval($session_id);


    	//2. Course and Session visibility are handle in local.inc.php/global.inc.php

    	//3. Checking if user exist in course/session

    	if ($session_id == 0 ) {
			if (CourseManager::is_user_subscribed_in_course($user_id, $course_info['code']) || api_is_platform_admin()) {
				$user_in_course = true;
    		}
    		//Check if course is open then we can consider that the student is regitered to the course
    		if (isset($course_info) && in_array($course_info['visibility'], array(2, 3))) {
    			$user_in_course = true;
    		}
    	} else {
    		$user_status = SessionManager::get_user_status_in_session($user_id, $course_info['code'], $session_id);
    		if (in_array($user_status, array('0', '2', '6'))) {
    			//is true if is an student, course session teacher or coach
    			$user_in_course = true;
    		}
    	}


    	//4. Checking document visibility (i'm repeating the code in order to be more clear when reading ) - jm

    	if ($user_in_course) {

    		//4.1 Checking document visibility for a Course

    		if ($session_id == 0) {
    			$item_info 	= api_get_item_property_info($course_info['real_id'], 'document', $doc_id, 0);

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
    			//4.2 Checking document visibility for a Course in a Session
    			$item_info 			  = api_get_item_property_info($course_info['real_id'], 'document', $doc_id, 0);
    			$item_info_in_session = api_get_item_property_info($course_info['real_id'], 'document', $doc_id, $session_id);

    			// True for admins if document exists
    			if (isset($item_info['visibility'])) {
    				if ($admins_can_see_everything && api_is_platform_admin())
    					return true;
    			}

    			if (isset($item_info_in_session['visibility'])) {
    				//if ($doc_id == 85) { var_dump($item_info_in_session);}
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
     * Allow attach a certificate to a course
     * @param string The course id
     * @param int The document id
     * @return void()
     */
    function attach_gradebook_certificate ($course_id, $document_id) {
        $tbl_category = Database :: get_main_table(TABLE_MAIN_GRADEBOOK_CATEGORY);
        $session_id = api_get_session_id();
        if ($session_id==0 || is_null($session_id)) {
            $sql_session='AND (session_id='.Database::escape_string($session_id).' OR isnull(session_id)) ';
        } elseif ($session_id>0) {
            $sql_session='AND session_id='.Database::escape_string($session_id);
        } else {
            $sql_session='';
        }
        $sql='UPDATE '.$tbl_category.' SET document_id="'.Database::escape_string($document_id).'"
               WHERE course_code="'.Database::escape_string($course_id).'" '.$sql_session;
        Database::query($sql);
    }

    /**
     * get the document id of default certificate
     * @param string The course id
     * @return int The default certificate id
     */
    static function get_default_certificate_id($course_id) {
        $tbl_category   = Database :: get_main_table(TABLE_MAIN_GRADEBOOK_CATEGORY);
        $session_id     = api_get_session_id();
        if ($session_id==0 || is_null($session_id)) {
            $sql_session='AND (session_id='.Database::escape_string($session_id).' OR isnull(session_id)) ';
        } elseif ($session_id>0) {
            $sql_session='AND session_id='.Database::escape_string($session_id);
        } else {
            $sql_session='';
        }
        $sql    = 'SELECT document_id FROM '.$tbl_category.' WHERE course_code="'.Database::escape_string($course_id).'" '.$sql_session;
        $rs     = Database::query($sql);
        $num    = Database::num_rows($rs);
        if ($num == 0) {
            return null;
        }
        $row    = Database::fetch_array($rs);
        return $row['document_id'];
    }

    /**
     * allow replace user info in file html
     * @param string The course code
     * @return string The html content of the certificate
     */
    function replace_user_info_into_html($user_id, $course_code, $is_preview = false) {
        $user_id 		= intval($user_id);
        $course_info 	= api_get_course_info($course_code);
        $tbl_document 	= Database::get_course_table(TABLE_DOCUMENT);
        $course_id 		= $course_info['real_id'];
        $document_id 	= self::get_default_certificate_id($course_code);

        if ($document_id) {
            $sql = "SELECT path FROM $tbl_document WHERE c_id = $course_id AND id = $document_id";
            $rs = Database::query($sql);
            $new_content = '';
            $all_user_info = array();
            if (Database::num_rows($rs)) {
                $row=Database::fetch_array($rs);
                $filepath = api_get_path(SYS_COURSE_PATH).$course_info['path'].'/document'.$row['path'];
                if (is_file($filepath)) {
                    $my_content_html = file_get_contents($filepath);
                }
                $all_user_info = self::get_all_info_to_certificate($user_id, $course_code, $is_preview);
                $info_to_be_replaced_in_content_html=$all_user_info[0];
                $info_to_replace_in_content_html=$all_user_info[1];
                $new_content=str_replace($info_to_be_replaced_in_content_html,$info_to_replace_in_content_html, $my_content_html);
            }
            return array('content' => $new_content, 'variables' => $all_user_info);
        }
        return array();
    }

    /**
     * return all content to replace and all content to be replace
     */
    static function get_all_info_to_certificate($user_id, $course_id, $is_preview = false) {
        $info_list	= array();
        $user_id	= intval($user_id);

        $course_info = api_get_course_info($course_id);

        //info portal
        $organization_name = api_get_setting('Institution');
        $portal_name       = api_get_setting('siteName');

        //Extra user data information
        $extra_user_info_data = UserManager::get_extra_user_data($user_id, false, false, false, true);

        //Student information
        $user_info 	    = api_get_user_info($user_id);
        $first_name     = $user_info['firstname'];
        $last_name 	    = $user_info['lastname'];
        $official_code  = $user_info['official_code'];

        //Teacher information
        $info_teacher_id = UserManager::get_user_id_of_course_admin_or_session_admin($course_id);
        $teacher_info = api_get_user_info($info_teacher_id);
        $teacher_first_name = $teacher_info['firstname'];
        $teacher_last_name = $teacher_info['lastname'];

        // info gradebook certificate
        $info_grade_certificate = UserManager::get_info_gradebook_certificate($course_id, $user_id);

        $date_certificate = $info_grade_certificate['created_at'];
        $date_long_certificate = '';
        if (!empty($date_certificate)) {
            $date_long_certificate = api_convert_and_format_date($date_certificate);
        }

        if ($is_preview) {
            $date_long_certificate = api_convert_and_format_date(api_get_utc_datetime());
        }

        $url = api_get_path(WEB_PATH).'certificates/?id='.$info_grade_certificate['id'];
        //replace content
        $info_to_replace_in_content_html     = array($first_name,
                                                     $last_name,
                                                     $organization_name,
                                                     $portal_name,
                                                     $teacher_first_name,
                                                     $teacher_last_name,
                                                     $official_code,
                                                     $date_long_certificate,
                                                     $course_id,
                                                     $course_info['name'],
                                                     $info_grade_certificate['grade'],
                                                     $url,
                                                     '<a href="'.$url.'" target="_blank">'.get_lang('CertificateOnlineLink').'</a>',
                                                     '((certificate_barcode))',
                                                    );
        $info_to_be_replaced_in_content_html = array('((user_firstname))',
        											 '((user_lastname))',
        											 '((gradebook_institution))',
                                                     '((gradebook_sitename))',
                                                     '((teacher_firstname))',
                                                     '((teacher_lastname))',
                                                     '((official_code))',
                                                     '((date_certificate))',
        											 '((course_code))',
                									 '((course_title))',
        											 '((gradebook_grade))',
                                                     '((certificate_link))',
                                                     '((certificate_link_html))',
                                                     '((certificate_barcode))',
                                            );

        if (!empty($extra_user_info_data)) {
            foreach ($extra_user_info_data as $key_extra=>$value_extra) {
                $info_to_be_replaced_in_content_html[]='(('.strtolower($key_extra).'))';
                $info_to_replace_in_content_html[]=$value_extra;
            }
        }
        $info_list[]=$info_to_be_replaced_in_content_html;
        $info_list[]=$info_to_replace_in_content_html;
        return $info_list;
    }
    /**
     * Remove default certificate
     * @param string The course id
     * @param int The document id of the default certificate
     * @return void()
     */
    function remove_attach_certificate ($course_id,$default_certificate_id) {
        $default_certificate=self::get_default_certificate_id($course_id);
        if ((int)$default_certificate==(int)$default_certificate_id) {
            $tbl_category=Database :: get_main_table(TABLE_MAIN_GRADEBOOK_CATEGORY);
            $session_id = api_get_session_id();
            if ($session_id==0 || is_null($session_id)) {
                $sql_session='AND (session_id='.Database::escape_string($session_id).' OR isnull(session_id)) ';
            } elseif ($session_id>0) {
                $sql_session='AND session_id='.Database::escape_string($session_id);
            } else {
                $sql_session='';
            }

            $sql='UPDATE '.$tbl_category.' SET document_id=null
                  WHERE course_code="'.Database::escape_string($course_id).'" AND document_id="'.$default_certificate_id.'" '.$sql_session;
            Database::query($sql);
        }
    }

    /**
     * Create directory certificate
     * @param string The course id
     * @return void()
     */
    static function create_directory_certificate_in_course ($course_id) {
        $course_info = api_get_course_info($course_id);
        if (!empty($course_info)) {
            $to_group_id=0;
            $to_user_id=null;
            $course_dir   = $course_info['path']."/document/";
            $sys_course_path = api_get_path(SYS_COURSE_PATH);
            $base_work_dir=$sys_course_path.$course_dir;
            $base_work_dir_test=$base_work_dir.'certificates';
            $dir_name='/certificates';
            $post_dir_name=get_lang('CertificatesFiles');
            $visibility_command = 'invisible';
            
            if (!is_dir($base_work_dir_test)) {
                $created_dir = create_unexisting_directory($course_info, api_get_user_id(), api_get_session_id(), $to_group_id,$to_user_id,$base_work_dir,$dir_name,$post_dir_name);                
                $update_id = DocumentManager::get_document_id_of_directory_certificate();                
                api_item_property_update($course_info, TOOL_DOCUMENT, $update_id, $visibility_command, api_get_user_id());
            }
        }
    }

    /**
     * Get the document id of the directory certificate
     * @param string The course id
     * @return int The document id of the directory certificate
     */
    function get_document_id_of_directory_certificate () {
        $tbl_document=Database::get_course_table(TABLE_DOCUMENT);
        $course_id = api_get_course_int_id();
        $sql = "SELECT id FROM $tbl_document WHERE c_id = $course_id AND path='/certificates' ";
        $rs = Database::query($sql);
        $row = Database::fetch_array($rs);
        return $row['id'];
    }

    /**
     * Check if a directory given is for certificate
     * @param string path of directory
     * @return bool  true if is a certificate or false otherwise
     */
    static function is_certificate_mode($dir) {
        //I'm in the certification module?
        $is_certificate_mode = false;
        $is_certificate_array = explode('/',$dir);
        array_shift($is_certificate_array);
        if ($is_certificate_array[0]=='certificates') {
            $is_certificate_mode = true;
        }
        return $is_certificate_mode;
    }

    /**
     * Gets the list of included resources as a list of absolute or relative paths from a html file or string html
     * This allows for a better SCORM export or replace urls inside content html from copy course
     * The list will generally include pictures, flash objects, java applets, or any other
     * stuff included in the source of the current item. The current item is expected
     * to be an HTML file or string html. If it is not, then the function will return and empty list.
     * @param	string  source html (content or path)
     * @param	bool  	is file or string html
     * @param	string	type (one of the Dokeos tools) - optional (otherwise takes the current item's type)
     * @param	int		level of recursivity we're in
     * @return	array	List of file paths. An additional field containing 'local' or 'remote' helps determine if the file should be copied into the zip or just linked
     */
    function get_resources_from_source_html($source_html, $is_file = false, $type = null, $recursivity = 1) {
        $max = 5;
        $attributes = array();
        $wanted_attributes = array('src', 'url', '@import', 'href', 'value', 'flashvars');
        $abs_path = '';

        if ($recursivity > $max) {
            return array();
        }

        if (!isset($type)) {
            $type = TOOL_DOCUMENT;
        }

        if (!$is_file) {
            $attributes = DocumentManager::parse_HTML_attributes($source_html, $wanted_attributes);

        } else {
            if (is_file($source_html)) {
                $abs_path = $source_html;
                //for now, read the whole file in one go (that's gonna be a problem when the file is too big)
                $info = pathinfo($abs_path);
                $ext = $info['extension'];
                switch (strtolower($ext)) {
                    case 'html'	:
                    case 'htm'	:
                    case 'shtml':
                    case 'css'	:
                        $file_content = file_get_contents($abs_path);
                        //get an array of attributes from the HTML source
                        $attributes = DocumentManager::parse_HTML_attributes($file_content, $wanted_attributes);
                        break;
                    default		:
                        break;
                }
            } else {
                return false;
            }
        }

        switch ($type) {
            case TOOL_DOCUMENT :
            case TOOL_QUIZ:
            case 'sco':
                foreach ($wanted_attributes as $attr) {
                    if (isset($attributes[$attr])) {
                        //find which kind of path these are (local or remote)
                        $sources = $attributes[$attr];
                        foreach ($sources as $source) {
                            //skip what is obviously not a resource
                            if (strpos($source, '+this.')) continue; //javascript code - will still work unaltered
                            if (strpos($source, '.') === false) continue; //no dot, should not be an external file anyway
                            if (strpos($source, 'mailto:')) continue; //mailto link
                            if (strpos($source, ';') && !strpos($source, '&amp;')) continue; //avoid code - that should help

                            if ($attr == 'value') {
                                if (strpos($source , 'mp3file')) {
                                    $files_list[] = array(substr($source, 0, strpos($source, '.swf') + 4), 'local', 'abs');
                                    $mp3file = substr($source , strpos($source, 'mp3file=') + 8);
                                    if (substr($mp3file, 0, 1) == '/') {
                                        $files_list[] = array($mp3file, 'local', 'abs');
                                    } else {
                                        $files_list[] = array($mp3file, 'local', 'rel');
                                    }
                                } elseif (strpos($source, 'flv=') === 0) {
                                    $source = substr($source, 4);
                                    if (strpos($source, '&') > 0) {
                                        $source = substr($source, 0, strpos($source, '&'));
                                    }
                                    if (strpos($source,'://') > 0) {
                                        if (strpos($source, api_get_path(WEB_PATH)) !== false) {
                                            //we found the current portal url
                                            $files_list[] = array($source, 'local', 'url');
                                        } else {
                                            //we didn't find any trace of current portal
                                            $files_list[] = array($source, 'remote', 'url');
                                        }
                                    } else {
                                        $files_list[] = array($source, 'local', 'abs');
                                    }
                                    continue; //skipping anything else to avoid two entries (while the others can have sub-files in their url, flv's can't)
                                }
                            }
                            if (strpos($source, '://') > 0) {
                                //cut at '?' in a URL with params
                                if (strpos($source, '?') > 0) {
                                    $second_part = substr($source,strpos($source, '?'));
                                    if(strpos($second_part, '://') > 0) {
                                        //if the second part of the url contains a url too, treat the second one before cutting
                                        $pos1 = strpos($second_part, '=');
                                        $pos2 = strpos($second_part, '&');
                                        $second_part = substr($second_part, $pos1 + 1, $pos2 - ($pos1 + 1));
                                        if (strpos($second_part, api_get_path(WEB_PATH)) !== false) {
                                            //we found the current portal url
                                            $files_list[] = array($second_part, 'local', 'url');
                                            $in_files_list[] = DocumentManager::get_resources_from_source_html($second_part, true, TOOL_DOCUMENT, $recursivity + 1);
                                            if (count($in_files_list) > 0) {
                                                $files_list = array_merge($files_list, $in_files_list);
                                            }
                                        } else {
                                            //we didn't find any trace of current portal
                                            $files_list[] = array($second_part, 'remote', 'url');
                                        }
                                    } elseif (strpos($second_part, '=') > 0) {
                                        if (substr($second_part, 0, 1) === '/') {
                                            //link starts with a /, making it absolute (relative to DocumentRoot)
                                            $files_list[] = array($second_part, 'local', 'abs');
                                            $in_files_list[] = DocumentManager::get_resources_from_source_html($second_part, true, TOOL_DOCUMENT, $recursivity + 1);
                                            if (count($in_files_list) > 0) {
                                                $files_list = array_merge($files_list, $in_files_list);
                                            }
                                        } elseif(strstr($second_part, '..') === 0) {
                                            //link is relative but going back in the hierarchy
                                            $files_list[] = array($second_part, 'local', 'rel');
                                            //$dir = api_get_path(SYS_CODE_PATH);//dirname($abs_path);
                                            //$new_abs_path = realpath($dir.'/'.$second_part);
                                            $dir = '';
                                            if (!empty($abs_path)) {
                                                $dir = dirname($abs_path).'/';
                                            }
                                            $new_abs_path = realpath($dir.$second_part);
                                            $in_files_list[] = DocumentManager::get_resources_from_source_html($new_abs_path, true, TOOL_DOCUMENT, $recursivity + 1);
                                            if (count($in_files_list) > 0) {
                                                $files_list = array_merge($files_list, $in_files_list);
                                            }
                                        } else {
                                            //no starting '/', making it relative to current document's path
                                            if (substr($second_part, 0, 2) == './') {
                                                $second_part = substr($second_part, 2);
                                            }
                                            $files_list[] = array($second_part, 'local', 'rel');
                                            $dir = '';
                                            if (!empty($abs_path)) {
                                                $dir = dirname($abs_path).'/';
                                            }
                                            $new_abs_path = realpath($dir.$second_part);
                                            $in_files_list[] = DocumentManager::get_resources_from_source_html($new_abs_path, true, TOOL_DOCUMENT, $recursivity + 1);
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
                                            $files_list[] = array($source, 'local', 'url');
                                            $in_files_list[] = DocumentManager::get_resources_from_source_html($source, true, TOOL_DOCUMENT, $recursivity+1);
                                            if (count($in_files_list) > 0) {
                                                $files_list = array_merge($files_list, $in_files_list);
                                            }
                                        } else {
                                            //we didn't find any trace of current portal
                                            $files_list[] = array($source, 'remote', 'url');
                                        }
                                    } else {
                                        //no protocol found, make link local
                                        if (substr($source, 0, 1) === '/') {
                                            //link starts with a /, making it absolute (relative to DocumentRoot)
                                            $files_list[] = array($source, 'local', 'abs');
                                            $in_files_list[] = DocumentManager::get_resources_from_source_html($source, true, TOOL_DOCUMENT, $recursivity + 1);
                                            if (count($in_files_list) > 0) {
                                                $files_list = array_merge($files_list, $in_files_list);
                                            }
                                        } elseif (strstr($source, '..') === 0) {	//link is relative but going back in the hierarchy
                                            $files_list[] = array($source, 'local', 'rel');
                                            $dir = '';
                                            if (!empty($abs_path)) {
                                                $dir = dirname($abs_path).'/';
                                            }
                                            $new_abs_path = realpath($dir.$source);
                                            $in_files_list[] = DocumentManager::get_resources_from_source_html($new_abs_path, true, TOOL_DOCUMENT, $recursivity + 1);
                                            if (count($in_files_list) > 0) {
                                                $files_list = array_merge($files_list, $in_files_list);
                                            }
                                        } else {
                                            //no starting '/', making it relative to current document's path
                                            if (substr($source, 0, 2) == './') {
                                                $source = substr($source, 2);
                                            }
                                            $files_list[] = array($source, 'local', 'rel');
                                            $dir = '';
                                            if (!empty($abs_path)) {
                                                $dir = dirname($abs_path).'/';
                                            }
                                            $new_abs_path = realpath($dir.$source);
                                            $in_files_list[] = DocumentManager::get_resources_from_source_html($new_abs_path, true, TOOL_DOCUMENT, $recursivity + 1);
                                            if (count($in_files_list) > 0) {
                                                $files_list = array_merge($files_list, $in_files_list);
                                            }
                                        }
                                    }
                                }
                                //found some protocol there
                                if (strpos($source, api_get_path(WEB_PATH)) !== false) {
                                    //we found the current portal url
                                    $files_list[] = array($source, 'local', 'url');
                                    $in_files_list[] = DocumentManager::get_resources_from_source_html($source, true, TOOL_DOCUMENT, $recursivity + 1);
                                    if (count($in_files_list) > 0) {
                                        $files_list = array_merge($files_list, $in_files_list);
                                    }
                                } else {
                                    //we didn't find any trace of current portal
                                    $files_list[] = array($source, 'remote', 'url');
                                }
                            } else {
                                //no protocol found, make link local
                                if (substr($source, 0, 1) === '/') {
                                    //link starts with a /, making it absolute (relative to DocumentRoot)
                                    $files_list[] = array($source, 'local', 'abs');
                                    $in_files_list[] = DocumentManager::get_resources_from_source_html($source, true, TOOL_DOCUMENT, $recursivity + 1);
                                    if (count($in_files_list) > 0) {
                                        $files_list = array_merge($files_list, $in_files_list);
                                    }
                                } elseif (strpos($source, '..') === 0) {
                                    //link is relative but going back in the hierarchy
                                    $files_list[] = array($source, 'local', 'rel');
                                    $dir = '';
                                    if (!empty($abs_path)) {
                                        $dir = dirname($abs_path).'/';
                                    }
                                    $new_abs_path = realpath($dir.$source);
                                    $in_files_list[] = DocumentManager::get_resources_from_source_html($new_abs_path, true, TOOL_DOCUMENT, $recursivity + 1);
                                    if (count($in_files_list) > 0) {
                                        $files_list = array_merge($files_list, $in_files_list);
                                    }
                                } else {
                                    //no starting '/', making it relative to current document's path
                                    if (substr($source, 0, 2) == './') {
                                        $source = substr($source, 2);
                                    }
                                    $files_list[] = array($source, 'local', 'rel');
                                    $dir = '';
                                    if (!empty($abs_path)) {
                                        $dir = dirname($abs_path).'/';
                                    }
                                    $new_abs_path = realpath($dir.$source);
                                    $in_files_list[] = DocumentManager::get_resources_from_source_html($new_abs_path, true, TOOL_DOCUMENT, $recursivity + 1);
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

        $checked_files_list = array();
        $checked_array_list = array();

        if (count($files_list ) > 0) {
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
     * @param    string  HTML attribute string
     * @param	 array	 List of attributes that we want to get back
     * @return   array   An associative array of attributes
     * @author 	 Based on a function from the HTML_Common2 PEAR module
     */
    static function parse_HTML_attributes($attrString, $wanted = array()) {
        $attributes = array();
        $regs = array();
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
                '/(((([A-Za-z_:])([A-Za-z0-9_:\.-]*))' .
            // '/(((([A-Za-z_:])([A-Za-z0-9_:\.-]|[^\x00-\x7F])*)' . -> seems to be taking too much
            // '/(((([A-Za-z_:])([^\x00-\x7F])*)' . -> takes only last letter of parameter name
                '([ \n\t\r]+)?(' .
            // '(=([ \n\t\r]+)?("[^"]+"|\'[^\']+\'|[^ \n\t\r]+))' . -> doesn't restrict close enough to the url itself
                  '(=([ \n\t\r]+)?("[^"\)]+"|\'[^\'\)]+\'|[^ \n\t\r\)]+))' .
                  '|' .
            // '(\(([ \n\t\r]+)?("[^"]+"|\'[^\']+\'|[^ \n\t\r]+)\))' . -> doesn't restrict close enough to the url itself
                  '(\(([ \n\t\r]+)?("[^"\)]+"|\'[^\'\)]+\'|[^ \n\t\r\)]+)\))' .
                '))' .
                '|' .
            // '(@import([ \n\t\r]+)?("[^"]+"|\'[^\']+\'|[^ \n\t\r]+)))?/', -> takes a lot (like 100's of thousands of empty possibilities)
                '(@import([ \n\t\r]+)?("[^"]+"|\'[^\']+\'|[^ \n\t\r]+)))/',
            $attrString,
            $regs
            );

        } catch (Exception $e) {
            error_log('Caught exception: '. $e->getMessage(), 0) ;
        }
        if ($res) {
            for ($i = 0; $i < count($regs[1]); $i++) {
                $name  = trim($regs[3][$i]);
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
                    if (!$reduced OR in_array(strtolower($name), $wanted)) {
                        if ($name == $check) {
                            $attributes[strtolower($name)][] = strtolower($name);
                        } else {
                            if (!empty($value) && ($value[0] == '\'' || $value[0] == '"')) {
                                $value = substr($value, 1, -1);
                            }
                            if ($value == 'API.LMSGetValue(name') {
                                $value = 'API.LMSGetValue(name)';
                            }
                            $attributes[strtolower($name)][] = $value;
                        }
                    }
                }
            }
        } else {
            error_log('preg_match did not find anything', 0);
        }
        return $attributes;
    }

    /**
     * Replace urls inside content html from a copy course
     * @param string		content html
     * @param string		origin course code
     * @param string		destination course directory
     * @return string	new content html with replaced urls or return false if content is not a string
     */
    function replace_urls_inside_content_html_from_copy_course($content_html, $origin_course_code, $destination_course_directory) {
        require_once api_get_path(LIBRARY_PATH).'fileUpload.lib.php';
        if (!is_string($content_html)) {
            return false;
        }

        $orig_source_html 	= DocumentManager::get_resources_from_source_html($content_html);
        $orig_course_info 	= api_get_course_info($origin_course_code);
        $orig_course_path 	= api_get_path(SYS_PATH).'courses/'.$orig_course_info['path'].'/';
        $destination_course_code = CourseManager::get_course_id_from_path ($destination_course_directory);
        $destination_course_info = api_get_course_info($destination_course_code);
        $dest_course_path 	= api_get_path(SYS_COURSE_PATH).$destination_course_directory.'/';
        
        $user_id = api_get_user_id();
        
        if (!empty($orig_source_html)) {
            foreach ($orig_source_html as $source) {                

                // get information about source url
                $real_orig_url	= $source[0];	// url
                $scope_url  	= $source[1];   // scope (local, remote)
                $type_url		= $source[2];	// tyle (rel, abs, url)


                // Get path and query from origin url
                $orig_parse_url  = parse_url($real_orig_url);
                $real_orig_path  = $orig_parse_url['path'];
                $real_orig_query = $orig_parse_url['query'];

                // Replace origin course code by destination course code from origin url query
                $dest_url_query = '';
                
                if (!empty($real_orig_query)) {
                    $dest_url_query = '?'.$real_orig_query;
                    if (strpos($dest_url_query,$origin_course_code) !== false) {
                        $dest_url_query = str_replace($origin_course_code, $destination_course_code, $dest_url_query);
                    }
                }

                if ($scope_url == 'local') {
                    if ($type_url == 'abs' || $type_url == 'rel') {                        
                        $document_file = strstr($real_orig_path, 'document');
                        
                        if (strpos($real_orig_path,$document_file) !== false) {
                            $origin_filepath        = $orig_course_path.$document_file;
                            $destination_filepath   = $dest_course_path.$document_file;

                            // copy origin file inside destination course
                            if (file_exists($origin_filepath)) {
                                $filepath_dir = dirname($destination_filepath);
                                
                                if (!is_dir($filepath_dir)) {
                                    $perm = api_get_permissions_for_new_directories();
                                    $result = @mkdir($filepath_dir, $perm, true);                                    
                                    if ($result) {
                                        $filepath_to_add = str_replace(array($dest_course_path, 'document'), '', $filepath_dir);                                
                                    
                                        //Add to item properties to the new folder
                                        $doc_id = add_document($destination_course_info, $filepath_to_add, 'folder', 0, basename($filepath_to_add));
                                        api_item_property_update($destination_course_info, TOOL_DOCUMENT, $doc_id, 'FolderCreated', $user_id, null, null, null, null);
                                    }
                                }
                                
                                if (!file_exists($destination_filepath)) {
                                    $result = @copy($origin_filepath, $destination_filepath);
                                    if ($result) {                 
                                    
                                        $filepath_to_add = str_replace(array($dest_course_path, 'document'), '', $destination_filepath);
                                        $size = filesize($destination_filepath);
                                                                        
                                        //Add to item properties to the file
                                        $doc_id = add_document($destination_course_info, $filepath_to_add, 'file', $size, basename($filepath_to_add));
                                        api_item_property_update($destination_course_info, TOOL_DOCUMENT, $doc_id, 'FolderCreated', $user_id, null, null, null, null);
                                    }
                                    
                                }
                            }

                            // Replace origin course path by destination course path
                            if (strpos($content_html,$real_orig_url) !== false) {
                                //$origin_course_code
                                $url_course_path = str_replace($orig_course_info['path'].'/'.$document_file, '', $real_orig_path);
                                $destination_url = $url_course_path.$destination_course_directory.'/'.$document_file.$dest_url_query;

                                //If the course code doesn't exist in the path? what we do? Nothing! see BT#1985
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
                    } else {
                        if ($type_url == 'url') {

                        }
                    }
                }
            }
        }
        return $content_html;
    }

    /**
     * Replace urls inside content html when moving a file
     * @todo this code is only called in document.php but is commented
     * @param string     content html
     * @param string     origin
     * @param string     destination
     * @return string    new content html with replaced urls or return false if content is not a string
     */
    function replace_urls_inside_content_html_when_moving_file($file_name, $original_path, $destiny_path) {
        if (substr($original_path, strlen($original_path) -1 , strlen($original_path)) == '/')  {
            $original    = $original_path.$file_name;
        } else {
            $original    = $original_path.'/'.$file_name;
        }
        if (substr($destiny_path, strlen($destiny_path) -1 , strlen($destiny_path)) == '/')  {
            $destination = $destiny_path.$file_name;
        } else {
            $destination = $destiny_path.'/'.$file_name;
        }
        //var_dump("From $original ", "to $destination");
        $original_count     = count(explode('/', $original));
        $destination_count  = count(explode('/', $destination));
        if ($original_count == $destination_count) {
            //Nothing to change
            return true;
        }

        $mode = '';
        if ($original_count > $destination_count) {
            $mode = 'outside';
        } else {
            $mode = 'inside';
        }
        //echo $original_count.' '.$destination_count; var_dump($mode);
        //We do not select the $original_path becayse the file was already moved
        $content_html = file_get_contents($destiny_path.'/'.$file_name);
        $destination_file = $destiny_path.'/'.$file_name;


        $pre_original = strstr($original_path, 'document');
        $pre_destin   = strstr($destiny_path, 'document');

        //var_dump ("pre_original $pre_original");
        //var_dump ("pre_destin $pre_destin");

        $pre_original = substr($pre_original, 8, strlen($pre_original));
        $pre_destin = substr($pre_destin, 8, strlen($pre_destin));

        //var_dump ("pre_original $pre_original");
        //var_dump ("pre_destin $pre_destin");

        $levels = count(explode('/', $pre_destin)) - 1 ;
        $link_to_add = '';
        for ($i=1; $i <=$levels ; $i++) {
            $link_to_add .= '../';
        }

        if ($pre_original == '/') {
            $pre_original = '';
        }

        if ($pre_destin == '/') {
            $pre_destin = '';
        }

        if ($pre_original != '') {
            $pre_original = '..'.$pre_original.'/';
        }

        if ($pre_destin != '') {
            $pre_destin = '..'.$pre_destin.'/';
        }

        //var_dump($pre_original);

        $levels = explode('/', $pre_original);
        //var_dump($levels);

        $count_pre_destination_levels = 0;
        foreach($levels as $item) {
            if (!empty($item) && $item != '..') {
                $count_pre_destination_levels++;
            }
        }
        $count_pre_destination_levels--;
        //$count_pre_destination_levels = count() - 3;
        if ($count_pre_destination_levels == 0 ) {
            $count_pre_destination_levels = 1;
        }
        //echo '$count_pre_destination_levels '. $count_pre_destination_levels;
        $pre_remove = '';
        for ($i=1; $i <= $count_pre_destination_levels; $i++) {
            $pre_remove .='..\/';
        }

        //var_dump(' link to add '.$link_to_add.'  -- remove '.$pre_remove);

        $orig_source_html   = DocumentManager::get_resources_from_source_html($content_html);

        //var_dump($orig_source_html);

        foreach ($orig_source_html as $source) {

            // get information about source url
            $real_orig_url  = $source[0];   // url
            $scope_url      = $source[1];   // scope (local, remote)
            $type_url       = $source[2];   // tyle (rel, abs, url)


            // Get path and query from origin url
            $orig_parse_url  = parse_url($real_orig_url);
            $real_orig_path  = $orig_parse_url['path'];
            $real_orig_query = $orig_parse_url['query'];

            // Replace origin course code by destination course code from origin url query
            /*
            $dest_url_query = '';
            if (!empty($real_orig_query)) {
            $dest_url_query = '?'.$real_orig_query;
            if (strpos($dest_url_query,$origin_course_code) !== false) {
            $dest_url_query = str_replace($origin_course_code, $destination_course_code, $dest_url_query);
            }
            }*/

            if ($scope_url == 'local') {
                if ( $type_url == 'abs' || $type_url == 'rel') {
                    $document_file = strstr($real_orig_path, 'document');

                    if (strpos($real_orig_path, $document_file) !== false) {
                        echo 'continue1';
                        continue;
                    } else {
                        $real_orig_url_temp = '';
                        if ($mode == 'inside') {
                            $real_orig_url_temp = str_replace('../', '', $real_orig_url);
                            $destination_url = $link_to_add.$real_orig_url_temp;
                        } else {
                            $real_orig_url_temp = $real_orig_url;

                            $destination_url = preg_replace("/".$pre_remove."/", '', $real_orig_url, 1);
                        }
                        if ($real_orig_url == $destination_url) {
                            echo 'continue2';
                            continue;
                        }
                        var_dump($real_orig_url_temp.' - '.$destination_url);
                        $content_html = str_replace($real_orig_url, $destination_url, $content_html);
                    }
                } else {
                    echo 'continue3';
                    continue;
                }
            }
        }
        $return = file_put_contents($destination, $content_html);
        return $return;
    }

    public function export_to_pdf($document_id, $course_code) {
        require_once api_get_path(LIBRARY_PATH).'pdf.lib.php';
        $course_data    = api_get_course_info($course_code);
        $document_data  = self::get_document_data_by_id($document_id, $course_code);
        $file_path      = api_get_path(SYS_COURSE_PATH).$course_data['path'].'/document'.$document_data['path'];
        $pdf = new PDF();
        $pdf->html_to_pdf($file_path, $document_data['title'], $course_code);
    }

    /**
     * Uploads a document
     *
     * @param array     the $_FILES variable
     * @param string    $path
     * @param string    title
     * @param string    comment
     * @param int       unzip or not the file
     * @param int       if_exists overwrite, rename or warn if exists (default)
     * @param bool      index document (search xapian module)
     * @param bool      print html messages
     * @return unknown_type
     */
    public static function upload_document($files, $path, $title ='', $comment = '', $unzip = 0, $if_exists = '', $index_document = false, $show_output = false) {
        require_once api_get_path(LIBRARY_PATH).'fileUpload.lib.php';

        $max_filled_space = self::get_course_quota();
        $course_info      = api_get_course_info();
        $course_dir       = $course_info['path'].'/document';
        $sys_course_path  = api_get_path(SYS_COURSE_PATH);
        $base_work_dir    = $sys_course_path.$course_dir;

        if (isset($files['file'])) {
            $upload_ok = process_uploaded_file($files['file'], $show_output);
            
            if ($upload_ok) {
                // File got on the server without problems, now process it
                $new_path = handle_uploaded_document($course_info, $files['file'], $base_work_dir, $path, api_get_user_id(), api_get_group_id(), null, $max_filled_space, $unzip, $if_exists, $show_output);
	
                if ($new_path) {
                    $docid = DocumentManager::get_document_id($course_info, $new_path);

                    if (!empty($docid)) {
                    	$table_document = Database::get_course_table(TABLE_DOCUMENT);
						$params = array();
                        
                        if (!empty($title)) {
                        	$params['title'] = get_document_title($title);
                        } else {
                            if (isset($if_exists) && $if_exists == 'rename') {
                                $new_path = basename($new_path);                   
                                $params['title']  = get_document_title($new_path);                                
                            } else {
                                $params['title']  = get_document_title($files['file']['name']);
                            }
                        }
                        
                        if (!empty($comment)) {
                        	$params['comment'] = trim($comment);
                        }
                        Database::update($table_document, $params, array('id = ? AND c_id = ? ' => array($docid, $course_info['real_id'])));
                    }

                    // Showing message when sending zip files
                    if ($new_path === true && $unzip == 1 && $show_output) {
                        Display::display_confirmation_message(get_lang('UplUploadSucceeded').'<br />', false);
                    }

                    /*// Check for missing images in html files
                     $missing_files = check_for_missing_files($base_work_dir.$new_path);
                     if ($missing_files && $show_output) {
                     // Show a form to upload the missing files
                     Display::display_normal_message(build_missing_files_form($missing_files, $path, $files['file']['name']), false);
                     }*/
                    if ($index_document) {
                        $idx_doc = self::index_document($docid,$course_info['code'],null,$_POST['language'],$_REQUEST,$if_exists);
                    }
                    if (!empty($docid) && is_numeric($docid)) {
                        $document_data = self::get_document_data_by_id($docid, $course_info['code']);
                        return $document_data;
                    }
                }
            }
        }
        return false;
    }

    /**
     * Obtains the text inside the file with the right parser

     */
    function get_text_content($doc_path, $doc_mime) {
        // TODO: review w$ compatibility

        // Use usual exec output lines array to store stdout instead of a temp file
        // because we need to store it at RAM anyway before index on DokeosIndexer object
        $ret_val = null;
        switch ($doc_mime) {
            case 'text/plain':
                $handle = fopen($doc_path, 'r');
                $output = array(fread($handle, filesize($doc_path)));
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
                    $parsed_output = array();
                    foreach ($output as & $line) {
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
            foreach ($output as & $line) {
                $content .= $line."\n";
            }
            return $content;
        }
        else {
            return false;
        }
    }


    /**
     * Calculates the total size of all documents in a course
     *
     * @author Bert vanderkimpen
     * @param  int $to_group_id (to calculate group document space)
     * @return int total size
     */
    static function documents_total_space($course_id = null, $group_id = null, $session_id = null) {
        $TABLE_ITEMPROPERTY     = Database::get_course_table(TABLE_ITEM_PROPERTY);
        $TABLE_DOCUMENT         = Database::get_course_table(TABLE_DOCUMENT);
        
        if (isset($course_id)) {            
            $course_id = intval($course_id);
        } else {
            $course_id = api_get_course_int_id();
        }
        
        $group_condition = null;
        
        if (isset($group_id)) {            
            $group_id = intval($group_id);
            $group_condition = " AND props.to_group_id='".$group_id."' ";            
        }
        
        $session_condition = null;
        
        if (isset($session_id)) {            
            $session_id = intval($session_id);
            $session_condition = " AND props.id_session='".$session_id."' ";            
        }        
        
        $sql = "SELECT SUM(size) FROM  ".$TABLE_ITEMPROPERTY."  AS props, ".$TABLE_DOCUMENT."  AS docs
		        WHERE 	props.c_id 	= $course_id AND
		        		docs.c_id 	= $course_id AND
		        		docs.id 	= props.ref AND
		        		props.tool 	= '".TOOL_DOCUMENT."' AND
                        props.visibility <> 2
                        $group_condition         
                        $session_condition
                ";        
        $result = Database::query($sql);

        if ($result && Database::num_rows($result) != 0) {
            $row = Database::fetch_row($result);
            return $row[0];
        } else {
            return 0;
        }
    }


    /**
     *  Here we count 1 kilobyte = 1000 byte, 12 megabyte = 1000 kilobyte.
     */
    static function display_quota($course_quota, $already_consumed_space) {
        $course_quota_m = round($course_quota / 1000000);
        $already_consumed_space_m = round($already_consumed_space / 1000000);

        $message = get_lang('MaximumAllowedQuota') . ' <strong>'.$course_quota_m.' megabyte</strong>.<br />';
        $message .= get_lang('CourseCurrentlyUses') . ' <strong>' . $already_consumed_space_m . ' megabyte</strong>.<br />';

        $percentage = round( ($already_consumed_space / $course_quota * 100), 1);

        $other_percentage = $percentage < 100 ? 100 - $percentage : 0;

        // Decide where to place percentage in graph
        if ($percentage >= 50) {
            $text_in_filled = '&nbsp;'.$other_percentage.'%';
            $text_in_unfilled = '';
        } else {
            $text_in_unfilled = '&nbsp;'.$other_percentage.'%';
            $text_in_filled = '';
        }

        // Decide the background colour of the graph
        if ($percentage < 65) {
            $colour = '#00BB00';        // Safe - green
        } elseif ($percentage < 90) {
            $colour = '#ffd400';        // Filling up - yelloworange
        } else {
            $colour = '#DD0000';        // Full - red
        }

        // This is used for the table width: a table of only 100 pixels looks too small
        $visual_percentage = 4 * $percentage;
        $visual_other_percentage = 4 * $other_percentage;

        $message .= get_lang('PercentageQuotaInUse') . ': <strong>'.$percentage.'%</strong>.<br />' .
        get_lang('PercentageQuotaFree') . ': <strong>'.$other_percentage.'%</strong>.<br />';

        $show_percentage = '&nbsp;'.$percentage.'%';
        $message .= '<div style="width: 80%; text-align: center; -moz-border-radius: 5px 5px 5px 5px; border: 1px solid #aaa; background-image: url(\''.api_get_path(WEB_CODE_PATH).'css/'.api_get_visual_theme().'/images/bg-header4.png\');" class="document-quota-bar">'.
                    '<div style="width:'.$percentage.'%; background-color: #bbb; border-right:3px groove #bbb; -moz-border-radius:5px;">&nbsp;</div>'.
                    '<span style="margin-top: -15px; margin-left:-15px; position: absolute;font-weight:bold;">'.$show_percentage.'</span></div>';
        echo $message;
    }


    /**
     * Display the document quota in a simple way
     *
     *  Here we count 1 kilobyte = 1000 byte, 12 megabyte = 1000 kilobyte.
     */
    static function display_simple_quota($course_quota, $already_consumed_space) {
        $course_quota_m = round($course_quota / 1000000);
        $already_consumed_space_m = round($already_consumed_space / 1000000, 2);
        $percentage = $already_consumed_space / $course_quota * 100;
        $percentage = round($percentage, 1);        
        $message = get_lang('YouAreCurrentlyUsingXOfYourX');
        $message = sprintf($message, $already_consumed_space_m, $percentage.'%',$course_quota_m.' ');
        echo Display::div($message, array('id'=>'document_quota'));
    }


    /**
     * Checks if there is enough place to add a file on a directory
     * on the base of a maximum directory size allowed
     *
     * @author Bert Vanderkimpen
     * @param  int file_size size of the file in byte
     * @param array $_course
     * @param  int max_dir_space maximum size
     * @return boolean true if there is enough space, false otherwise
     *
     * @see enough_space() uses  documents_total_space() function
     */
    static function enough_space($file_size, $max_dir_space) {
        if ($max_dir_space) {
            $already_filled_space = self::documents_total_space();
            if (($file_size + $already_filled_space) > $max_dir_space) {
                return false;
            }
        }
        return true;
    }
    
    /**
     * 
     * @param array paremeters: count, url, extension
     * @return string
     */
    
    static function generate_jplayer_jquery($params = array()) {
        $js_path = api_get_path(WEB_LIBRARY_PATH).'javascript/';        
        
        $jplayer_definition = ' $("#jquery_jplayer_' . $params['count'] . '").jPlayer({                                
                            ready: function() {                    
                                $(this).jPlayer("setMedia", {                                        
                                    ' . $params['extension'] . ' : "' . $params['url'] . '"                                                                                  
                                });
                            },
                            play: function() { // To avoid both jPlayers playing together.
                                $(this).jPlayer("pauseOthers");
                            },                                
                            //errorAlerts: true,
                            //warningAlerts: true,
                            swfPath: "' . $js_path . 'jquery-jplayer",
                            //supplied: "m4a, oga, mp3, ogg, wav",
                            supplied: "' . $params['extension'] . '",
                            wmode: "window",
                            solution: "flash, html",  // Do not change this setting 
                            cssSelectorAncestor: "#jp_container_' . $params['count'] . '", 
                        });  	 ' . "\n\n";
        return $jplayer_definition;
    }

    /**
     *
     * Shows a play icon next to the document title in the document list
     * @param int
     * @return string	html content
     */
    static function generate_media_preview($i, $type = 'simple') {
        $i = intval($i);
        
        $extra_controls = $progress = '';
        if ($type == 'advanced') {
            $extra_controls = ' <li><a href="javascript:;" class="jp-stop" tabindex="1">stop</a></li>
                                <li><a href="#" class="jp-mute" tabindex="1">mute</a></li>
								<li><a href="#" class="jp-unmute" tabindex="1">unmute</a></li>';
            $progress = '<div class="jp-progress">
                                <div class="jp-seek-bar">
                                    <div class="jp-play-bar"></div>
                                </div>
                            </div>';
        }
        
        //Shows only the play button
        $html = '<div id="jquery_jplayer_'.$i.'" class="jp-jplayer"></div>
                <div id="jp_container_'.$i.'" class="jp-audio">
                    <div class="jp-type-single">
                        <div class="jp-gui jp-interface">
                            <ul class="jp-controls">
                                <li><a href="javascript:;" class="jp-play" tabindex="1">play</a></li>
                                <li><a href="javascript:;" class="jp-pause" tabindex="1">pause</a></li>
                                '.$extra_controls.'
                            </ul>                            
                            '.$progress.'                            
                        </div>
                    </div>
                </div>';
        //<div id="jplayer_inspector_'.$i.'"></div>
        return $html;
    }

    static function generate_video_preview($document_data = array()) {
        $html = '
        <div id="jp_container_1" class="jp-video">
			<div class="jp-type-single">
				<div id="jquery_jplayer_1" class="jp-jplayer"></div>
				<div class="jp-gui">
					<div class="jp-video-play">
						<a href="javascript:;" class="jp-video-play-icon" tabindex="1">play</a>
					</div>
					<div class="jp-interface">
						<div class="jp-progress">
							<div class="jp-seek-bar">
								<div class="jp-play-bar"></div>
							</div>
						</div>
						<div class="jp-current-time"></div>
						<div class="jp-controls-holder">
							<ul class="jp-controls">
								<li><a href="javascript:;" class="jp-play" tabindex="1">play</a></li>
								<li><a href="javascript:;" class="jp-pause" tabindex="1">pause</a></li>
								<li><a href="javascript:;" class="jp-stop" tabindex="1">stop</a></li>
								<li><a href="javascript:;" class="jp-mute" tabindex="1" title="mute">mute</a></li>
								<li><a href="javascript:;" class="jp-unmute" tabindex="1" title="unmute">unmute</a></li>
								<li><a href="javascript:;" class="jp-volume-max" tabindex="1" title="max volume">max volume</a></li>
							</ul>
							<div class="jp-volume-bar">
								<div class="jp-volume-bar-value"></div>
							</div>
							<ul class="jp-toggles">
								<li><a href="javascript:;" class="jp-full-screen" tabindex="1" title="full screen">full screen</a></li>
								<li><a href="javascript:;" class="jp-restore-screen" tabindex="1" title="restore screen">restore screen</a></li>
								<li><a href="javascript:;" class="jp-repeat" tabindex="1" title="repeat">repeat</a></li>
								<li><a href="javascript:;" class="jp-repeat-off" tabindex="1" title="repeat off">repeat off</a></li>
							</ul>
						</div>
						<div class="jp-title">
							<ul>
								<li>'.$document_data['title'].'</li>
							</ul>
						</div>
					</div>
				</div>
				<div class="jp-no-solution">
                     <span>'.get_lang('UpdateRequire').'</span>
                    '.get_lang("ToPlayTheMediaYouWillNeedToUpdateYourBrowserToARecentVersionYouCanAlsoDownloadTheFile").'
				</div>
			</div>
		</div>';
        return $html;
    }

    function get_document_preview($course_info, $lp_id = false, $target = '', $session_id = 0, $add_move_button = false, $filter_by_folder = null, $overwrite_url = null) {
    	if (empty($course_info['real_id']) || empty($course_info['code']) || !is_array($course_info)) {
    		return '';
    	}
    	$user_id = api_get_user_id();
    	$user_in_course = false;

    	if (api_is_platform_admin()) {
    		$user_in_course = true;
    	}

    	if (!$user_in_course) {
    		if (CourseManager::is_course_teacher($user_id, $course_info['code'])) {
    			$user_in_course = true;
    		}
    	}

    	//condition for the session
    	$session_id  = intval($session_id);

    	if (!$user_in_course)  {
        	if (empty($session_id)) {
        		if (CourseManager::is_user_subscribed_in_course($user_id, $course_info['code'])) {
        			$user_in_course = true;
        		}
        		//Check if course is open then we can consider that the student is regitered to the course
        		if (isset($course_info) && in_array($course_info['visibility'], array(2, 3))) {
        			$user_in_course = true;
        		}
        	} else {
        		$user_status = SessionManager::get_user_status_in_session($user_id, $course_info['code'], $session_id);
                //is true if is an student, course session teacher or coach
        		if (in_array($user_status, array('0', '2', '6'))) {
        			$user_in_course = true;
        		}
        	}
        }

    	$tbl_doc 		= Database::get_course_table(TABLE_DOCUMENT);
    	$tbl_item_prop 	= Database::get_course_table(TABLE_ITEM_PROPERTY);

    	$path = '/';
    	$path = Database::escape_string(str_replace('_', '\_', $path));
    	$added_slash = ($path == '/') ? '' : '/';


    	//$condition_session = " AND (id_session = '$session_id' OR (id_session = '0' AND insert_date <= (SELECT creation_date FROM $tbl_course WHERE code = '".$course_info['code']."' )))";
    	$condition_session = " AND (id_session = '$session_id' OR  id_session = '0' )";
        
        $add_folder_filter = null;
        if (!empty($filter_by_folder)) {
            $add_folder_filter = " AND docs.path LIKE '".Database::escape_string($filter_by_folder)."%'";
        }
        
		$sql_doc = "SELECT last.visibility, docs.*
					FROM  $tbl_item_prop AS last, $tbl_doc AS docs
    	            WHERE   docs.id = last.ref AND 
                            docs.path LIKE '".$path.$added_slash."%' AND 
                            docs.path NOT LIKE '%_DELETED_%' AND 
                            last.tool = '".TOOL_DOCUMENT."' $condition_session AND
                            last.visibility = '1' AND 
                            docs.c_id = {$course_info['real_id']} AND 
                            last.c_id = {$course_info['real_id']} 
                            $add_folder_filter
                    ORDER BY docs.title ASC";
        
    	$res_doc 	= Database::query($sql_doc);
    	$resources  = Database::store_result($res_doc, 'ASSOC');


    	$resources_sorted = array();
        $return 	= '';
        
    	if ($lp_id) {
	    	$return .= '<div class="lp_resource_element">';
	    	$return .= Display::return_icon('new_doc.gif', '', array(), ICON_SIZE_SMALL);
	    	$return .= Display::url(get_lang('NewDocument'), api_get_self().'?'.api_get_cidreq().'&action=add_item&type='.TOOL_DOCUMENT.'&lp_id='.$_SESSION['oLP']->lp_id);
	    	$return .= '</div>';
    	} else {
    		$return .= Display::div(Display::url(Display::return_icon('close.png', get_lang('Close'), array(), ICON_SIZE_SMALL), ' javascript:void(0);', array('id'=>'close_div_'.$course_info['real_id'].'_'.$session_id,'class' =>'close_div')), array('style' => 'position:absolute;right:10px'));
    	}

    	// If you want to debug it, I advise you to do "echo" on the eval statements.
    	if (!empty($resources) && $user_in_course) {
             foreach ($resources as $resource) {
            	$is_visible = self::is_visible_by_id($resource['id'], $course_info, $session_id, api_get_user_id());
            	if (!$is_visible) {
            		continue;
            	}
		    	$resource_paths = explode('/', $resource['path']);
		    	array_shift($resource_paths);
		    	$path_to_eval = $last_path = '';
		    	$is_file = false;

		    	if ($resource['filetype'] == 'file') {
		    		foreach ($resource_paths as $key => $resource_path) {
		    			if ($key != count($resource_paths)-1) {
		    				// It's a folder.
		    				$path_to_eval .= "['$resource_path']['files']";
		    			}
		    			$is_file = true;
		    		}
		    	} else {
		    		foreach ($resource_paths as $key => $resource_path) {
		    			if ($key != count($resource_paths) - 1) {
		    				// It's a folder.
		    				$path_to_eval .= "['$resource_path']['files']";
		    			}
		    		}
		    	}
		    	$last_path = $resource_path;

		    	//$data = json_encode(array('title'=>$resource['title'], 'path'=>$last_path));
		    	//@todo not sure if it's a good thing using base64_encode. I tried with json_encode but i received the same error
		    	//Some testing is needed in order to prove the performance
		    	//Also change the explode to value from "/" to "|@j@|" it fixes  #3780

		    	$data = base64_encode($resource['title'].'|@j@|'.$last_path);

		    	if ($is_file) {
		    		//for backward compatibility
		    		if (empty($resource['title'])) {
		    			$resource['title'] = basename($resource['path']);
		    		}
		    		eval ('$resources_sorted'.$path_to_eval.'['.$resource['id'].'] = "'.$data.'" ; ');
		    	} else {
		    		eval ('$resources_sorted'.$path_to_eval.'["'.$last_path.'"]["id"]='.$resource['id'].';');
		    		eval ('$resources_sorted'.$path_to_eval.'["'.$last_path.'"]["title"]= "'.api_htmlentities($resource['title']).'";');
		    	}
	    	}
    	}

    	$label = get_lang('Documents');

    	$new_array[$label] = array('id' => 0, 'files' => $resources_sorted);

    	$write_result = self::write_resources_tree($course_info, $session_id, $new_array, 0, $lp_id, $target, $add_move_button, $overwrite_url);

    	$return .= $write_result ;

    	$img_path = api_get_path(WEB_IMG_PATH);

    	if ($lp_id == false) {
    		$return .= "<script>
    		    	$('.doc_folder').mouseover(function() {
    					var my_id = this.id.split('_')[2];
    					$('#res_'+my_id).show();
    				});

    				$('.close_div').click(function() {
    					var course_id = this.id.split('_')[2];
    					var session_id = this.id.split('_')[3];
    					$('#document_result_'+course_id+'_'+session_id).hide();
    					$('.lp_resource').remove();
    				});
    				</script>";
    	} else {
    		//For LPs
    		$return .=  "<script>

    		function testResources(id, img) {
	    		if (document.getElementById(id).style.display=='block'){
	    			document.getElementById(id).style.display='none';
                    var id = id.split('_')[1];
	    			document.getElementById('img_'+id).src='".$img_path."nolines_plus.gif';
	    		} else {
	    			document.getElementById(id).style.display='block';
                    var id = id.split('_')[1];
    				document.getElementById('img_'+id).src='".$img_path."nolines_minus.gif';
    			}
    		}
    		</script>";
    	}
    	if(!$user_in_course) {
    		$return = '';
    	}
    	return $return;
    }

    /**
    * Generate and return an HTML list of resources based on a given array.
    * This list is used to show the course creator a list of available resources to choose from
    * when creating a learning path.
    * @param	array	Array of elements to add to the list
    * @param	integer Enables the tree display by shifting the new elements a certain distance to the right
    * @return	string	The HTML list
    */
    public function write_resources_tree($course_info, $session_id, $resources_sorted, $num = 0, $lp_id = false, $target = '', $add_move_button = false, $overwrite_url = null) {
    	require_once api_get_path(LIBRARY_PATH).'fileDisplay.lib.php';

    	$img_path 		= api_get_path(WEB_IMG_PATH);
    	$img_sys_path 	= api_get_path(SYS_CODE_PATH).'img/';
    	$web_code_path 	= api_get_path(WEB_CODE_PATH);

    	$return = '';

    	if (count($resources_sorted) > 0) {
    		foreach ($resources_sorted as $key => $resource) {
    			$title = isset($resource['title']) ? $resource['title'] : null;
    			if (empty($title)) {
    				$title = $key;
    			}
    			//echo '<pre>'; print_r($resource);
    			if (isset($resource['id']) && is_int($resource['id'])) {
    				// It's a folder.
    				//hide some folders
    				if (in_array($key, array('shared_folder','chat_files', 'HotPotatoes_files', 'css', 'certificates'))){
    					continue;
    				} elseif(preg_match('/_groupdocs/', $key)){
    					continue;
    				} elseif(preg_match('/sf_user_/', $key)){
    					continue;
    				} elseif(preg_match('/shared_folder_session_/', $key)){
    					continue;
    				}

    				//trad some titles
    				if ($key=='images') {
    					$key=get_lang('Images');
    				} elseif($key=='gallery') {
    					$key=get_lang('Gallery');
    				} elseif($key=='flash') {
    					$key=get_lang('Flash');
    				} elseif($key=='audio'){
    					$key=get_lang('Audio');
    				} elseif($key=='video') {
    					$key=get_lang('Video');
    				}

    				$onclick = '';

    				if ($lp_id) {
    					$onclick = 'onclick="javascript: testResources(\'res_' . $resource['id'] . '\',\'img_' . $resource['id'] . '\')"';
    				}

    				$return .= '<ul class="lp_resource">';
                        $return .= '<li class="doc_folder"  id="doc_id_'.$resource['id'].'"  style="margin-left:'.($num * 18).'px; ">';

                        if ($lp_id) {
                            $return .= '<img style="cursor: pointer;" src="'.$img_path.'nolines_plus.gif" align="absmiddle" id="img_' . $resource['id'] . '"  '.$onclick.' >';
                        } else {
                            $return .= '<span style="margin-left:16px">&nbsp;</span>';
                        }
                        $return .= '<img alt="" src="'.$img_path.'lp_folder.gif" title="" align="absmiddle" />&nbsp;';
                        $return .= '<span '.$onclick.' style="cursor: pointer;" >'.$title.'</span>';
                        $return .= '</li>';

                        $return .= '<div id="res_'.$resource['id'].'" style="display: none;" >';
                        if (isset($resource['files'])) {
                            $return .= self::write_resources_tree($course_info, $session_id, $resource['files'], $num +1, $lp_id, $target, $add_move_button, $overwrite_url);
                        }
                        $return .= '</div>';
                    $return .= '</ul>';
    			} else {
    				if (!is_array($resource)) {
    					$resource = base64_decode($resource);
    					// It's a file.
    					$icon		= choose_image($resource);
    					$position 	= strrpos($icon, '.');
    					$icon 		= substr($icon, 0, $position) . '_small.gif';
    					$file_info	= explode('|@j@|', $resource);
    					$my_file_title = $file_info[0];

                        //If title is empty we try to use the path
                        if (empty($my_file_title)) {
                            $my_file_title  = $file_info[1];
                        }

    					// Show the "image name" not the filename of the image.
    					if ($lp_id) {
    						//LP URL
    						$lp_id = $this->lp_id;
    						$url  = api_get_self() . '?cidReq=' . Security::remove_XSS($_GET['cidReq']) . '&amp;action=add_item&amp;type=' . TOOL_DOCUMENT . '&amp;file=' . $key . '&amp;lp_id=' .$lp_id;
                            if (!empty($overwrite_url)) {
                                $url = $overwrite_url.'&document_id='.$key;
                            }
    					} else {
    						//Direct document URL
    						$url  = $web_code_path.'document/document.php?cidReq='.$course_info['code'].'&id_session='.$session_id.'&id='.$key;
                            if (!empty($overwrite_url)) {
                                $url = $overwrite_url.'&document_id='.$key;
                            }                            
    					}
    					$img = $img_path.$icon;
    					if (!file_exists($img_sys_path.$icon)) {
    						$img = $img_path.'icons/16/default_small.gif';
    					}

    					$link = Display::url('<img alt="" src="'.$img.'" title="" />&nbsp;' . $my_file_title, $url, array('target' => $target));

    					if ($lp_id == false) {
                            $return .= '<li class="doc_resource" data_id="'.$key.'" data_type="document" title="'.$my_file_title.'" >';
                        } else {
                            $return .= '<li class="doc_resource lp_resource_element" data_id="'.$key.'" data_type="document" title="'.$my_file_title.'" >';
                        }

                        $return .= '<div class="item_data" style="margin-left:' . (($num +1) * 18) . 'px;margin-right:5px;">';

                        if ($add_move_button) {
                            $return .= '<a class="moved" href="#">';
                            $return .= Display::return_icon('move_everywhere.png', get_lang('Move'), array(), ICON_SIZE_TINY);
                            $return .= '</a> ';
                        }
    					$return .= $link;
    					$return .= '</div></li>';
    				}
    			}
    		}
    	}
    	return $return;
    }

    public static function check_visibility_tree($doc_id, $course_code, $session_id, $user_id) {
    	$document_data = self::get_document_data_by_id($doc_id, $course_code);

        if (!empty($document_data)) {
            //if admin or course teacher, allow anyway
            if (api_is_platform_admin() || CourseManager::is_course_teacher($user_id,$course_code)) { return true; }
            $course_info = api_get_course_info($course_code);
    		if ($document_data['parent_id'] == false || empty($document_data['parent_id'])) {
    			$visible = self::is_visible_by_id($doc_id, $course_info, $session_id, $user_id);
    			return $visible;
    		} else {
    			$course_info = api_get_course_info($course_code);
    			$visible = self::is_visible_by_id($doc_id, $course_info, $session_id, $user_id);

    			if (!$visible) {
    				return false;
    			} else {
    				return self::check_visibility_tree($document_data['parent_id'], $course_code, $session_id, $user_id);
    			}
    		}
    	} else {
    		return false;
    	}
    }

    /**
     * Index a given document.
     * @param   int     Document ID inside its corresponding course
     * @param   string  Course code
     * @param   int     Session ID (not used yet)
     * @param   string  Language of document's content (defaults to course language)
     * @param   array   Array of specific fields (['code'=>'value',...])
     * @param   string  What to do if the file already exists (default or overwrite)
     * @param   bool    When set to true, this runs the indexer without actually saving anything to any database
     * @return  bool    Returns true on presumed success, false on failure
     */
    public function index_document($docid, $course_code, $session_id=0, $lang='english', $specific_fields_values=array(), $if_exists = '', $simulation = false) {
        if (api_get_setting('search_enabled') !== 'true') {
        	return false;
        }
        if (empty($docid) or $docid != intval($docid)) {
        	return false;
        }
        if (empty($session_id)) {
        	$session_id = api_get_session_id();
        }
        $course_info      = api_get_course_info($course_code);
        $course_dir       = $course_info['path'].'/document';
        $sys_course_path  = api_get_path(SYS_COURSE_PATH);
        $base_work_dir    = $sys_course_path.$course_dir;

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
            //echo $doc_mime;
            $allowed_mime_types = self::file_get_mime_type(true);

            // mime_content_type does not detect correctly some formats that are going to be supported for index, so an extensions array is used for the moment
            if (empty($doc_mime)) {
                $allowed_extensions = array('doc', 'docx', 'ppt', 'pptx', 'pps', 'ppsx', 'xls', 'xlsx', 'odt', 'odp', 'ods', 'pdf', 'txt', 'rtf', 'msg', 'csv', 'html', 'htm');
                $extensions = preg_split("/[\/\\.]/", $doc_path) ;
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

                require_once api_get_path(LIBRARY_PATH).'search/DokeosIndexer.class.php';
                require_once api_get_path(LIBRARY_PATH).'search/IndexableChunk.class.php';

                $ic_slide = new IndexableChunk();
                $ic_slide->addValue('title', $file_title);
                $ic_slide->addCourseId($course_code);
                $ic_slide->addToolId(TOOL_DOCUMENT);
                $xapian_data = array(
                SE_COURSE_ID => $course_code,
                SE_TOOL_ID   => TOOL_DOCUMENT,
                SE_DATA      => array('doc_id' => $docid),
                SE_USER      => api_get_user_id(),
                );

                $ic_slide->xapian_data = serialize($xapian_data);
                $di = new DokeosIndexer();
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
                            $all_specific_terms .= ' '. $sterms;
                            $sterms = explode(',', $sterms);
                            foreach ($sterms as $sterm) {
                                $sterm = trim($sterm);
                                if (!empty($sterm)) {
                                    $ic_slide->addTerm($sterm, $specific_field['code']);
                                    // updated the last param here from $value to $sterm without being sure - see commit15464
                                    if (!$simulation) {
                                      add_specific_field_value($specific_field['id'], $course_code, TOOL_DOCUMENT, $docid, $sterm);
                                    }
                                }
                            }
                        }
                        // Add terms also to content to make terms findable by probabilistic search
                        $file_content = $all_specific_terms .' '. $file_content;

                        if (!$simulation) {
                          $ic_slide->addValue('content', $file_content);
                          $di->addChunk($ic_slide);
                          // Index and return a new search engine document id
                          $did = $di->index();
                          //var_dump($did);
                          if ($did) {
                            // update the search_did on db
                            $tbl_se_ref = Database::get_main_table(TABLE_MAIN_SEARCH_ENGINE_REF);
                            $sql = 'UPDATE %s SET search_did=%d WHERE id=%d LIMIT 1';
                            $sql = sprintf($sql, $tbl_se_ref, (int)$did, (int)$se_ref['id']);
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
                        $all_specific_terms .= ' '. $sterms;
                        if (!empty($sterms)) {
                            $sterms = explode(',', $sterms);
                            foreach ($sterms as $sterm) {
                                if (!$simulation) {
                                  $ic_slide->addTerm(trim($sterm), $specific_field['code']);
                                  add_specific_field_value($specific_field['id'], $course_code, TOOL_DOCUMENT, $docid, $sterm);
                                }
                            }
                        }
                    }
                    // Add terms also to content to make terms findable by probabilistic search
                    $file_content = $all_specific_terms .' '. $file_content;
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

    public static function get_web_odf_extension_list(){
        return array('ods', 'odt');
    }
}
//end class DocumentManager
