<?php
/* See license terms in /license.txt */
/**
 * This is the file display library for Dokeos.
 * Include/require it in your code to use its functionality.
 *
 * @todo move this file to DocumentManager
 *
 * Define the image to display for each file extension.
 * This needs an existing image repository to work.
 *
 * @author - Hugues Peeters <peeters@ipm.ucl.ac.be>
 *
 * @param string $file_name (string) - Name of a file
 *
 * @return string The gif image to chose
 */
function choose_image($file_name)
{
    static $type, $image;

    /* TABLES INITIALISATION */
    if (!$type || !$image) {
        $type['word'] = [
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
        ];
        $type['web'] = [
            'htm',
            'html',
            'htx',
            'xml',
            'xsl',
            'php',
            'xhtml',
        ];
        $type['image'] = [
            'gif',
            'jpg',
            'png',
            'bmp',
            'jpeg',
            'tif',
            'tiff',
        ];
        $type['image_vect'] = ['svg', 'svgz'];
        $type['audio'] = [
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
        ];
        $type['video'] = [
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
        ];
        $type['excel'] = [
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
        ];
        $type['compressed'] = ['zip', 'tar', 'rar', 'gz'];
        $type['code'] = [
            'js',
            'cpp',
            'c',
            'java',
            'phps',
            'jsp',
            'asp',
            'aspx',
            'cfm',
        ];
        $type['acrobat'] = ['pdf'];
        $type['powerpoint'] = [
            'ppt',
            'pps',
            'pptm',
            'pptx',
            'potm',
            'potx',
            'ppam',
            'ppsm',
            'ppsx',
        ];
        $type['flash'] = ['fla', 'swf'];
        $type['text'] = ['txt', 'log'];
        $type['oo_writer'] = ['odt', 'ott', 'sxw', 'stw'];
        $type['oo_calc'] = ['ods', 'ots', 'sxc', 'stc'];
        $type['oo_impress'] = ['odp', 'otp', 'sxi', 'sti'];
        $type['oo_draw'] = ['odg', 'otg', 'sxd', 'std'];
        $type['epub'] = ['epub'];
        $type['java'] = ['class', 'jar'];
        $type['freemind'] = ['mm'];

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

    $extension = [];
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
 * Get the icon to display for a folder by its path.
 *
 * @param string $folderPath
 *
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
