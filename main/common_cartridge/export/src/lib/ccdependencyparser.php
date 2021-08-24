<?php
/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/cc_lib/gral_lib/ccdependencyparser.php under GNU/GPL license */

/**
 * Converts \ Directory separator to the / more suitable for URL.
 *
 * @param string $path
 */
function toUrlPath(&$path)
{
    for ($count = 0; $count < strlen($path); $count++) {
        $chr = $path[$count];
        if (($chr == '\\')) {
            $path[$count] = '/';
        }
    }
}

/**
 * Returns relative path from two directories with full path.
 *
 * @param string $path1
 * @param string $path2
 *
 * @return string
 */
function pathDiff($path1, $path2)
{
    toUrlPath($path1);
    toUrlPath($path2);
    $result = "";
    $bl2 = strlen($path2);
    $a = strpos($path1, $path2);
    if ($a !== false) {
        $result = trim(substr($path1, $bl2 + $a), '/');
    }

    return $result;
}

/**
 * Converts direcotry separator in given path to / to validate in CC
 * Value is passed byref hence variable itself is changed.
 *
 * @param string $path
 */
function toNativePath(&$path)
{
    for ($count = 0; $count < strlen($path); $count++) {
        $chr = $path[$count];
        if (($chr == '\\') || ($chr == '/')) {
            $path[$count] = '/';
        }
    }
}

/**
 * Function strips url part from css link.
 *
 * @param string $path
 * @param string $rootDir
 *
 * @return string
 */
function stripUrl($path, $rootDir = '')
{
    $result = $path;
    if (is_string($path) && ($path != '')) {
        $start = strpos($path, '(') + 1;
        $length = strpos($path, ')') - $start;
        $rut = $rootDir.substr($path, $start, $length);
        $result = fullPath($rut, '/');
    }

    return $result;
}

/**
 * Get full path.
 *
 * @param string $path
 * @param string $dirsep
 *
 * @return false|string
 */
function fullPath($path, $dirsep = DIRECTORY_SEPARATOR)
{
    $token = '$IMS-CC-FILEBASE$';
    $path = str_replace($token, '', $path);
    if (is_string($path) && ($path != '')) {
        $sep = $dirsep;
        $dotDir = '.';
        $upDir = '..';
        $length = strlen($path);
        $rtemp = trim($path);
        $start = strrpos($path, $sep);
        $canContinue = ($start !== false);
        $result = $canContinue ? '' : $path;
        $rcount = 0;
        while ($canContinue) {
            $dirPart = ($start !== false) ? substr($rtemp, $start + 1, $length - $start) : $rtemp;
            $canContinue = ($dirPart !== false);
            if ($canContinue) {
                if ($dirPart != $dotDir) {
                    if ($dirPart == $upDir) {
                        $rcount++;
                    } else {
                        if ($rcount > 0) {
                            $rcount--;
                        } else {
                            $result = ($result == '') ? $dirPart : $dirPart.$sep.$result;
                        }
                    }
                }
                $rtemp = substr($path, 0, $start);
                $start = strrpos($rtemp, $sep);
                $canContinue = (($start !== false) || (strlen($rtemp) > 0));
            }
        }
    }

    return $result;
}

/**
 * validates URL.
 *
 * @param string $url
 *
 * @return bool
 */
function isUrl($url)
{
    $result = filter_var($url, FILTER_VALIDATE_URL, FILTER_FLAG_PATH_REQUIRED) !== false;

    return $result;
}

/**
 * Gets the dependency files of the $fname file.
 *
 * @param string $manifestroot
 * @param string $fname
 * @param string $folder
 * @param array  $filenames
 */
function getDepFiles($manifestroot, $fname, $folder, &$filenames)
{
    static $types = ['xhtml' => true, 'html' => true, 'htm' => true];
    $extension = strtolower(trim(pathinfo($fname, PATHINFO_EXTENSION)));
    $filenames = [];
    if (isset($types[$extension])) {
        $dcx = new XMLGenericDocument();
        $filename = $manifestroot.$folder.$fname;
        if (!file_exists($filename)) {
            $filename = $manifestroot.DIRECTORY_SEPARATOR.$folder.DIRECTORY_SEPARATOR.$fname;
        }
        if (file_exists($filename)) {
            $res = $dcx->loadHTMLFile($filename);
            if ($res) {
                getDepFilesHTML($manifestroot, $fname, $filenames, $dcx, $folder);
            }
        }
    }
}

/**
 * Gets the dependency of .html of the $fname file.
 *
 * @param string $manifestroot
 * @param string $fname
 * @param string $filenames
 * @param string $dcx
 * @param string $folder
 */
function getDepFilesHTML($manifestroot, $fname, &$filenames, &$dcx, $folder)
{
    $dcx->resetXpath();
    $nlist = $dcx->nodeList("//img/@src | //link/@href | //script/@src | //a[not(starts-with(@href,'#'))]/@href");
    $cssObjArray = [];
    foreach ($nlist as $nl) {
        $item = $folder.$nl->nodeValue;
        $path_parts = pathinfo($item);
        $fname = $path_parts['basename'];
        $ext = array_key_exists('extension', $path_parts) ? $path_parts['extension'] : '';
        if (!isUrl($folder.$nl->nodeValue) && !isUrl($nl->nodeValue)) {
            $path = $folder.$nl->nodeValue;
            $file = fullPath($path, "/");
            toNativePath($file);
            if (file_exists($manifestroot.DIRECTORY_SEPARATOR.$file)) {
                $filenames[$file] = $file;
            }
        }
        if ($ext == 'css') {
            $css = new CssParser();
            $css->parse($dcx->filePath().$nl->nodeValue);
            $cssObjArray[$item] = $css;
        }
    }
    $nlist = $dcx->nodeList("//*/@class");
    foreach ($nlist as $nl) {
        $item = $folder.$nl->nodeValue;
        foreach ($cssObjArray as $csskey => $cssobj) {
            $bimg = $cssobj->get($item, "background-image");
            $limg = $cssobj->get($item, "list-style-image");
            $npath = pathinfo($csskey);
            if ((!empty($bimg)) && ($bimg != 'none')) {
                $value = stripUrl($bimg, $npath['dirname'].'/');
                $filenames[$value] = $value;
            } elseif ((!empty($limg)) && ($limg != 'none')) {
                $value = stripUrl($limg, $npath['dirname'].'/');
                $filenames[$value] = $value;
            }
        }
    }
    $elemsToCheck = ["body", "p", "ul", "h4", "a", "th"];
    $doWeHaveIt = [];
    foreach ($elemsToCheck as $elem) {
        $doWeHaveIt[$elem] = ($dcx->nodeList("//".$elem)->length > 0);
    }
    foreach ($elemsToCheck as $elem) {
        if ($doWeHaveIt[$elem]) {
            foreach ($cssObjArray as $csskey => $cssobj) {
                $sb = $cssobj->get($elem, "background-image");
                $sbl = $cssobj->get($elem, "list-style-image");
                $npath = pathinfo($csskey);
                if ((!empty($sb)) && ($sb != 'none')) {
                    $value = stripUrl($sb, $npath['dirname'].'/');
                    $filenames[$value] = $value;
                } elseif ((!empty($sbl)) && ($sbl != 'none')) {
                    $value = stripUrl($sbl, $npath['dirname'].'/');
                    $filenames[$value] = $value;
                }
            }
        }
    }
}
