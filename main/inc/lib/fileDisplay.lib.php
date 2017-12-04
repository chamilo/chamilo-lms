<?php
/* See license terms in /license.txt */
/**
 * This is the file display library for Dokeos.
 * Include/require it in your code to use its functionality.
 * @todo move this file to DocumentManager
 * @package chamilo.library
 */

/* FILE DISPLAY FUNCTIONS */
/**
 * Define the image to display for each file extension.
 * This needs an existing image repository to work.
 *
 * @author - Hugues Peeters <peeters@ipm.ucl.ac.be>
 * @param  string $file_name (string) - Name of a file
 * @return string The gif image to chose
 */
function choose_image($file_name)
{
    static $type, $image;

    /* TABLES INITIALISATION */
    if (!$type || !$image) {
        $type['word'] = array(
            'doc',
            'dot',
            'rtf',
            'mcw',
            'wps',
            'psw',
            'docm',
            'docx',
            'dotm',
            'dotx',
        );
        $type['web'] = array(
            'htm',
            'html',
            'htx',
            'xml',
            'xsl',
            'php',
            'xhtml',
        );
        $type['image'] = array(
            'gif',
            'jpg',
            'png',
            'bmp',
            'jpeg',
            'tif',
            'tiff',
        );
        $type['image_vect'] = array('svg', 'svgz');
        $type['audio'] = array(
            'wav',
            'mid',
            'mp2',
            'mp3',
            'midi',
            'sib',
            'amr',
            'kar',
            'oga',
            'au',
            'wma',
        );
        $type['video'] = array(
            'mp4',
            'mov',
            'rm',
            'pls',
            'mpg',
            'mpeg',
            'm2v',
            'm4v',
            'flv',
            'f4v',
            'avi',
            'wmv',
            'asf',
            '3gp',
            'ogv',
            'ogg',
            'ogx',
            'webm',
        );
        $type['excel'] = array(
            'xls',
            'xlt',
            'xls',
            'xlt',
            'pxl',
            'xlsx',
            'xlsm',
            'xlam',
            'xlsb',
            'xltm',
            'xltx',
        );
        $type['compressed'] = array('zip', 'tar', 'rar', 'gz');
        $type['code'] = array(
            'js',
            'cpp',
            'c',
            'java',
            'phps',
            'jsp',
            'asp',
            'aspx',
            'cfm',
        );
        $type['acrobat'] = array('pdf');
        $type['powerpoint'] = array(
            'ppt',
            'pps',
            'pptm',
            'pptx',
            'potm',
            'potx',
            'ppam',
            'ppsm',
            'ppsx',
        );
        $type['flash'] = array('fla', 'swf');
        $type['text'] = array('txt', 'log');
        $type['oo_writer'] = array('odt', 'ott', 'sxw', 'stw');
        $type['oo_calc'] = array('ods', 'ots', 'sxc', 'stc');
        $type['oo_impress'] = array('odp', 'otp', 'sxi', 'sti');
        $type['oo_draw'] = array('odg', 'otg', 'sxd', 'std');
        $type['epub'] = array('epub');
        $type['java'] = array('class', 'jar');
        $type['freemind'] = array('mm');

        $image['word'] = 'word.png';
        $image['web'] = 'file_html.png';
        $image['image'] = 'file_image.png';
        $image['image_vect'] = 'file_svg.png';
        $image['audio'] = 'file_sound.png';
        $image['video'] = 'film.png';
        $image['excel'] = 'excel.png';
        $image['compressed'] = 'file_zip.png';
        $image['code'] = 'icons/22/mime_code.png';
        $image['acrobat'] = 'file_pdf.png';
        $image['powerpoint'] = 'powerpoint.png';
        $image['flash'] = 'file_flash.png';
        $image['text'] = 'icons/22/mime_text.png';
        $image['oo_writer'] = 'file_oo_writer.png';
        $image['oo_calc'] = 'file_oo_calc.png';
        $image['oo_impress'] = 'file_oo_impress.png';
        $image['oo_draw'] = 'file_oo_draw.png';
        $image['epub'] = 'file_epub.png';
        $image['java'] = 'file_java.png';
        $image['freemind'] = 'file_freemind.png';
    }

    $extension = array();
    if (!is_array($file_name)) {
        if (preg_match('/\.([[:alnum:]]+)(\?|$)/', $file_name, $extension)) {
            $extension[1] = strtolower($extension[1]);

            foreach ($type as $generic_type => $extension_list) {
                if (in_array($extension[1], $extension_list)) {
                    return $image[$generic_type];
                }
            }
        }
    }

    return 'defaut.gif';
}

/**
 * Get the icon to display for a folder by its path
 * @param string $folderPath
 * @return string
 */
function chooseFolderIcon($folderPath)
{
    if ($folderPath == '/shared_folder') {
        return 'folder_users.png';
    }

    if (strstr($folderPath, 'shared_folder_session_')) {
        return 'folder_users.png';
    }

    switch ($folderPath) {
        case '/audio':
            return 'folder_audio.png';
        case '/flash':
            return 'folder_flash.png';
        case '/images':
            return 'folder_images.png';
        case '/video':
            return 'folder_video.png';
        case '/images/gallery':
            return 'folder_gallery.png';
        case '/chat_files':
            return 'folder_chat.png';
        case '/learning_path':
            return 'folder_learningpath.png';
    }

    return 'folder_document.png';
}

/**
 * Transform a UNIX time stamp in human readable format date.
 *
 * @author - Hugues Peeters <peeters@ipm.ucl.ac.be>
 * @param int $date - UNIX time stamp
 * @return string A human readable representation of the UNIX date
 */
function format_date($date)
{
    return date('d.m.Y', $date);
}

/**
 * Transform the file path to a URL.
 *
 * @param string $file_path (string) - Relative local path of the file on the hard disk
 * @return string Relative url
 */
function format_url($file_path)
{
    $path_component = explode('/', $file_path);
    $path_component = array_map('rawurlencode', $path_component);

    return implode('/', $path_component);
}

/**
 * Get the total size of a directory.
 *
 * @param string $dir_name (string) - Path of the dir on the hard disk
 * @return int Total size in bytes
 */
function folder_size($dir_name)
{
    $size = 0;
    if ($dir_handle = opendir($dir_name)) {
        while (($entry = readdir($dir_handle)) !== false) {
            if ($entry == '.' || $entry == '..') {
                continue;
            }

            if (is_dir($dir_name.'/'.$entry)) {
                $size += folder_size($dir_name.'/'.$entry);
            } else {
                $size += filesize($dir_name.'/'.$entry);
            }
        }

        closedir($dir_handle);
    }

    return $size;
}

