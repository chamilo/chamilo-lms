<?php

/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/cc_lib/gral_lib/ccdependencyparser.php under GNU/GPL license */

declare(strict_types=1);

namespace Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Lib;

use Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Base\CssParser;
use Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Base\XMLGenericDocument;
use DOMXPath;

use const DIRECTORY_SEPARATOR;
use const FILTER_FLAG_PATH_REQUIRED;
use const FILTER_VALIDATE_URL;
use const PATHINFO_EXTENSION;

/**
 * Converts backslashes to forward slashes (URL-friendly).
 */
function toUrlPath(string &$path): void
{
    $len = \strlen($path);
    for ($i = 0; $i < $len; $i++) {
        if ('\\' === $path[$i]) {
            $path[$i] = '/';
        }
    }
}

/**
 * Returns relative path of $path1 with respect to $path2.
 */
function pathDiff(string $path1, string $path2): string
{
    toUrlPath($path1);
    toUrlPath($path2);

    $pos = strpos($path1, $path2);
    if (false !== $pos) {
        return trim(substr($path1, $pos + \strlen($path2)), '/');
    }

    return '';
}

/**
 * Normalizes directory separators to '/'.
 * Value is passed by reference hence variable itself is changed.
 */
function toNativePath(string &$path): void
{
    $len = \strlen($path);
    for ($i = 0; $i < $len; $i++) {
        if ('\\' === $path[$i] || '/' === $path[$i]) {
            $path[$i] = '/';
        }
    }
}

/**
 * Strips 'url(...)' wrapper from CSS link and resolves relative to $rootDir.
 */
function stripUrl(string $path, string $rootDir = ''): string
{
    $result = $path;
    if ('' !== $path) {
        $start = strpos($path, '(');
        $end = strrpos($path, ')');
        if (false !== $start && false !== $end && $end > $start) {
            $start++;
            $inner = substr($path, $start, $end - $start);
            // Trim quotes if any
            $inner = trim($inner, " \t\n\r\0\x0B'\"");
            $result = fullPath($rootDir.$inner, '/');
        }
    }

    return $result;
}

/**
 * Resolves a path removing 1EdTech/IMS CC placeholders and '..' segments.
 */
function fullPath(string $path, string $dirsep = DIRECTORY_SEPARATOR): string
{
    // Remove placeholders like $IMS-CC-FILEBASE$ or $1EdTech_CC_FILEBASE$
    $token = '/\$(?:IMS|1EdTech)[-_]CC[-_]FILEBASE\$/';
    $path = (string) preg_replace($token, '', $path);

    if ('' === $path) {
        return '';
    }

    $sep = $dirsep;
    $segments = array_values(array_filter(explode($sep, str_replace(['\\', '/'], $sep, $path)), static fn ($s) => '' !== $s));
    $out = [];

    foreach ($segments as $seg) {
        if ('.' === $seg) {
            continue;
        }
        if ('..' === $seg) {
            array_pop($out);
        } else {
            $out[] = $seg;
        }
    }

    return implode($sep, $out);
}

/**
 * Validates if the string is a URL (with path).
 */
function isUrl(string $url): bool
{
    return false !== filter_var($url, FILTER_VALIDATE_URL, FILTER_FLAG_PATH_REQUIRED);
}

/**
 * Gets the dependency files of the $fname file (HTML/XHTML/HTM).
 *
 * @param string   $manifestroot Absolute path to manifest root (no trailing slash required)
 * @param string   $fname        HTML file name (e.g. 'index.html')
 * @param string   $folder       Relative folder inside the manifest (may be empty or end with '/')
 * @param string[] $filenames    Output map of dependency files [relPath => relPath]
 */
function getDepFiles(string $manifestroot, string $fname, string $folder, array &$filenames): void
{
    static $types = ['xhtml' => true, 'html' => true, 'htm' => true];

    $extension = strtolower((string) pathinfo($fname, PATHINFO_EXTENSION));
    // Ensure folder has a trailing slash if not empty
    if ('' !== $folder && !str_ends_with($folder, '/') && !str_ends_with($folder, DIRECTORY_SEPARATOR)) {
        $folder .= '/';
    }

    if (!isset($types[$extension])) {
        return;
    }

    $filenames = [];

    // Build absolute path to the HTML file, try both separators
    $filename = rtrim($manifestroot, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$folder.$fname;
    if (!file_exists($filename)) {
        $alt = rtrim($manifestroot, '/').'/'.$folder.$fname;
        if (file_exists($alt)) {
            $filename = $alt;
        }
    }

    if (!file_exists($filename)) {
        return;
    }

    $doc = new XMLGenericDocument();
    $loaded = @$doc->loadHTMLFile($filename);
    if (false === $loaded) {
        return;
    }

    getDepFilesHTML($manifestroot, $fname, $filenames, $doc, $folder, $filename);
}

/**
 * Parses HTML to collect dependency files (img/src, link/href, script/src, a/href not starting '#')
 * and CSS background/list-style images referenced via classes.
 *
 * @param string               $manifestroot Absolute path to manifest root
 * @param string               $fname        HTML file name
 * @param array<string,string> $filenames    Output map of dependency files
 * @param XMLGenericDocument   $dcx          Loaded HTML document
 * @param string               $folder       Relative folder of the HTML file inside the package
 * @param string               $htmlFullPath Absolute full path to the HTML file
 */
function getDepFilesHTML(
    string $manifestroot,
    string $fname,
    array &$filenames,
    XMLGenericDocument $dcx,
    string $folder,
    string $htmlFullPath
): void {
    // Compute base directory for relative includes (CSS in particular)
    $baseDir = rtrim(str_replace('\\', '/', \dirname($htmlFullPath)), '/').'/';

    $xpath = new DOMXPath($dcx);

    // Collect direct dependencies from HTML attributes
    $nodeList = $xpath->query("//img/@src | //link/@href | //script/@src | //a[not(starts-with(@href,'#'))]/@href");
    $cssParsers = []; // [key => CssParser]

    if (false !== $nodeList) {
        foreach ($nodeList as $attr) {
            $val = trim($attr->nodeValue ?? '');
            if ('' === $val) {
                continue;
            }

            // Relative path within the course package
            $candidate = $folder.$val;

            // Skip external URLs
            if (isUrl($candidate) || isUrl($val)) {
                continue;
            }

            // Normalize and test existence relative to manifest root
            $rel = fullPath($candidate, '/');
            toNativePath($rel);

            $abs = rtrim($manifestroot, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$rel;
            if (file_exists($abs)) {
                $filenames[$rel] = $rel;
            }

            // If it's a CSS, parse it to collect referenced assets
            $ext = strtolower((string) pathinfo($val, PATHINFO_EXTENSION));
            if ('css' === $ext) {
                $cssFull = $baseDir.ltrim(str_replace('\\', '/', $val), '/');
                if (!file_exists($cssFull)) {
                    // Fallback: build from manifest root + folder
                    $cssFull = rtrim($manifestroot, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$rel;
                }

                if (file_exists($cssFull)) {
                    $css = new CssParser();
                    // NOTE: CssParser::parse() is expected to read file paths
                    $css->parse($cssFull);
                    $cssParsers[$folder.$val] = $css;
                }
            }
        }
    }

    // From each CSS, collect images referenced by class names present in the HTML
    $classNodes = $xpath->query('//*/@class');
    if (false !== $classNodes) {
        foreach ($classNodes as $attr) {
            $classAttr = trim($attr->nodeValue ?? '');
            if ('' === $classAttr) {
                continue;
            }

            // Split multiple classes
            $classes = preg_split('/\s+/', $classAttr) ?: [];
            foreach ($classes as $cls) {
                if ('' === $cls) {
                    continue;
                }
                $selector = $cls; // CssParser::get() expects selector name (without dot) in this port

                foreach ($cssParsers as $cssKey => $cssObj) {
                    $bg = $cssObj->get($selector, 'background-image');
                    $lst = $cssObj->get($selector, 'list-style-image');

                    $cssPathInfo = pathinfo($cssKey);
                    $cssDir = ($cssPathInfo['dirname'] ?? '');
                    if ('' !== $cssDir && !str_ends_with($cssDir, '/')) {
                        $cssDir .= '/';
                    }

                    if (!empty($bg) && 'none' !== $bg) {
                        $value = stripUrl($bg, $cssDir);
                        if ('' !== $value) {
                            $filenames[$value] = $value;
                        }
                    } elseif (!empty($lst) && 'none' !== $lst) {
                        $value = stripUrl($lst, $cssDir);
                        if ('' !== $value) {
                            $filenames[$value] = $value;
                        }
                    }
                }
            }
        }
    }

    // Optional: check a few common tags for background/list-style rules even without classes
    $elemsToCheck = ['body', 'p', 'ul', 'h4', 'a', 'th'];
    $present = [];
    foreach ($elemsToCheck as $elem) {
        $nodes = $xpath->query('//'.$elem);
        $present[$elem] = (false !== $nodes && $nodes->length > 0);
    }

    foreach ($elemsToCheck as $elem) {
        if (!$present[$elem]) {
            continue;
        }
        foreach ($cssParsers as $cssKey => $cssObj) {
            $bg = $cssObj->get($elem, 'background-image');
            $lst = $cssObj->get($elem, 'list-style-image');

            $cssPathInfo = pathinfo($cssKey);
            $cssDir = ($cssPathInfo['dirname'] ?? '');
            if ('' !== $cssDir && !str_ends_with($cssDir, '/')) {
                $cssDir .= '/';
            }

            if (!empty($bg) && 'none' !== $bg) {
                $value = stripUrl($bg, $cssDir);
                if ('' !== $value) {
                    $filenames[$value] = $value;
                }
            } elseif (!empty($lst) && 'none' !== $lst) {
                $value = stripUrl($lst, $cssDir);
                if ('' !== $value) {
                    $filenames[$value] = $value;
                }
            }
        }
    }
}
