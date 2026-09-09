<?php

declare(strict_types=1);

use Chamilo\CoreBundle\Framework\Container;

/**
 * This file contains the functions used by the OeL plugin.
 *
 * @version 18/05/2024
 *
 * @param mixed $src
 * @param mixed $dst
 */
function recurseCopyFolderSco($src, $dst): void
{
    $pluginFileSystem = Container::getPluginsFileSystem();

    if (!$pluginFileSystem->directoryExists($dst)) {
        $pluginFileSystem->createDirectory($dst);
    }

    foreach (new DirectoryIterator($src) as $item) {
        if ($item->isDot() || 'Thumbs.db' === $item->getFilename()) {
            continue;
        }

        $dstPath = $dst.'/'.$item->getFilename();

        if ($item->isDir()) {
            recurseCopyFolderSco($item->getPathname(), $dstPath);
        } else {
            $stream = fopen($item->getPathname(), 'rb');
            $pluginFileSystem->writeStream($dstPath, $stream);
            fclose($stream);
        }
    }
}

function recurseCopyFolderScoOufs($src, $dst): void
{
    $assetFileSystem = Container::getAssetRepository()->getFileSystem();
    $pluginFileSystem = Container::getPluginsFileSystem();

    if (!$pluginFileSystem->directoryExists($dst)) {
        $pluginFileSystem->createDirectory($dst);
    }

    $normalizedSrc = trim($src, '/');

    foreach ($assetFileSystem->listContents($src, true) as $item) {
        if ('Thumbs.db' === basename($item->path())) {
            continue;
        }

        $relativePath = substr($item->path(), strlen($normalizedSrc) + 1);
        $dstPath = $dst.'/'.$relativePath;

        if ($item->isDir()) {
            if (!$pluginFileSystem->directoryExists($dstPath)) {
                $pluginFileSystem->createDirectory($dstPath);
            }
        } else {
            $stream = $assetFileSystem->readStream($item->path());
            $pluginFileSystem->writeStream($dstPath, $stream);
        }
    }
}

function recurseCopyFolderScoNoVideos($src, $dst): void
{
    $pluginFileSystem = Container::getPluginsFileSystem();

    if (!$pluginFileSystem->directoryExists($dst)) {
        $pluginFileSystem->createDirectory($dst);
    }

    foreach (new DirectoryIterator($src) as $item) {
        if ($item->isDot() || 'Thumbs.db' === $item->getFilename()) {
            continue;
        }

        $dstPath = $dst.'/'.$item->getFilename();

        if ($item->isDir()) {
            recurseCopyFolderScoNoVideos($item->getPathname(), $dstPath);
        } elseif (!str_contains($item->getFilename(), '.mp4')) {
            $stream = fopen($item->getPathname(), 'rb');
            $pluginFileSystem->writeStream($dstPath, $stream);
            fclose($stream);
        }
    }
}

/**
 * Resolve $candidatePath to a real, existing, regular file strictly contained
 * within $allowedRoot. Defeats path traversal (../, absolute paths, symlink
 * escapes) by canonicalising both paths with realpath() and checking
 * containment on the canonical result, rather than blacklisting patterns in
 * the untrusted input.
 *
 * Returns null when the path does not exist, is not a regular file, or
 * resolves outside of $allowedRoot.
 */
function oel_resolve_safe_local_path(string $candidatePath, string $allowedRoot): ?string
{
    $realRoot = realpath($allowedRoot);
    if (false === $realRoot) {
        return null;
    }

    $realPath = realpath($candidatePath);
    if (false === $realPath || !is_file($realPath)) {
        return null;
    }

    $realRoot = rtrim($realRoot, \DIRECTORY_SEPARATOR).\DIRECTORY_SEPARATOR;

    if (0 !== strncmp($realPath, $realRoot, \strlen($realRoot))) {
        return null;
    }

    return $realPath;
}

/**
 * This method control rights user for editing a OeL page.
 *
 * Fails closed: a page id must have been explicitly whitelisted into the
 * current session (via oel_add_ctr_rights(), called once by editor/index.php
 * after it verifies a real edit permission + CSRF token) before any request
 * for that id is allowed. Previously this returned true whenever the session
 * whitelist was empty/unset -- the plugin's normal state for any request that
 * never went through editor/index.php first -- which let anonymous or
 * unrelated requests reach state-changing actions gated only by this check.
 *
 * @param mixed $idPage
 */
function oel_ctr_rights($idPage): bool
{
    $lst_ids = '';

    if (isset($_SESSION['idsessionedition'])) {
        $lst_ids = (string) $_SESSION['idsessionedition'];
    }

    if ('' == $lst_ids) {
        return false;
    }

    return false !== strrpos($lst_ids, ";$idPage;");
}

/**
 * This method control options studio
 *  ACL : Creator Activity Logs
 *  DTA : displayTemplateArea
 *  OUT : onlyUserTemplates.
 *
 * @param mixed $rightname
 */
function oel_ctr_options($rightname)
{
    $r = false;
    $options_studio = ';';
    if (isset($_SESSION['options-studio'])) {
        $options_studio = (string) $_SESSION['options-studio'];
    }
    $options_studio = ';'.$options_studio;

    $ctrOpts = strpos($options_studio, $rightname);

    if (false === $ctrOpts) {
        $r = false;
    } else {
        $r = true;
    }

    return $r;
}

function oel_escape_string($value)
{
    $search = ['\\', "\x00", "\n", "\r", "'", '"', "\x1a"];
    $replace = ['\\\\', '\0', '\n', '\r', "\\'", '\"', '\Z'];

    return str_replace($search, $replace, $value);
}

function uuid($length)
{
    $chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
    while (strlen($chars) < $length) {
        $chars .= $chars;
    }

    return substr(str_shuffle($chars), 0, $length);
}

function get_int_from($search)
{
    $valInt = 0;
    if (isset($_POST[$search])) {
        $valInt = $_POST[$search];
    }
    if (isset($_GET[$search])) {
        $valInt = $_GET[$search];
    }

    return (int) $valInt;
}

function get_string_from($search)
{
    $valStr = '';
    if (isset($_POST[$search])) {
        $valStr = $_POST[$search];
    }
    if (isset($_GET[$search])) {
        $valStr = $_GET[$search];
    }

    $valStr = str_replace(['<', '>', "'", '"', ')', '('], ['&lt;', '&gt;', '&apos;', '&#x22;', '&#x29;', '&#x28;'], $valStr);
    $valStr = str_replace(['?', '!'], ['', ''], $valStr);
    $valStr = str_ireplace('%3Cscript', '', $valStr);

    return $valStr;
}

function get_string_direct_from($search)
{
    $valStr = '';
    if (isset($_POST[$search])) {
        $valStr = $_POST[$search];
    }
    if (isset($_GET[$search])) {
        $valStr = $_GET[$search];
    }

    return $valStr;
}

function get_clean_idstring($value)
{
    $value = strtolower($value);
    $search = ['é', ' ', 'ç', 'è', '?', '"', "'"];
    $replace = ['e', '-', 'c', 'e', '', '-', '-'];

    return str_replace($search, $replace, $value);
}

function clean_term_string($value)
{
    $search = ['é', ' ', 'ç', 'è', '@', "\r", "\n"];
    $replace = ['e', '-', 'c', 'e', '-', '', ''];

    return str_replace($search, $replace, $value);
}

function clean_term_split($value)
{
    $search = ['|', '@', "\r", "\n"];
    $replace = ['-', '-', '', ''];

    return str_replace($search, $replace, $value);
}

// sanitize filename
function filter_filename($filename)
{
    return preg_replace('/[^a-zA-Z0-9\-\._]/', '', $filename);
}

function fileIndexOf($mystring, $search)
{
    $pos = strrpos($mystring, $search);
    if (false === $pos) {
        return false;
    }

    return true;
}

function removeQuotesPdf($string)
{
    $txt = str_replace('&nbsp;', ' ', $string);
    $txt = str_replace('&Aacute;', 'Á', $txt);
    $txt = str_replace('&aacute;', 'á', $txt);
    $txt = str_replace('&Eacute;', 'É', $txt);
    $txt = str_replace('&eacute;', 'é', $txt);
    $txt = str_replace('&Iacute;', 'Í', $txt);
    $txt = str_replace('&iacute;', 'í', $txt);
    $txt = str_replace('&Oacute;', 'Ó', $txt);
    $txt = str_replace('&oacute;', 'ó', $txt);
    $txt = str_replace('&Uacute;', 'Ú', $txt);
    $txt = str_replace('&uacute;', 'ú', $txt);
    $txt = str_replace('&Ntilde;', 'Ñ', $txt);
    $txt = str_replace('&ntilde;', 'ñ', $txt);
    $txt = str_replace('&quot;', '"', $txt);
    $txt = str_replace('&ordf;', 'ª', $txt);
    $txt = str_replace('&ordm;', 'º', $txt);
    $txt = str_replace('&amp;', '&', $txt);
    $txt = str_replace('&bull;', '•', $txt);
    $txt = str_replace('&iquest; &', '¿', $txt);
    $txt = str_replace('&agrave;', 'à', $txt);
    $txt = str_replace('&Agrave;', 'À', $txt);
    $txt = str_replace('&iexcl;', '¡', $txt);
    $txt = str_replace('&middot;', '·', $txt);
    $txt = str_replace('&Ccedil;', 'Ç', $txt);
    $txt = str_replace('&ccedil;', 'ç', $txt);
    $txt = str_replace('&euro;', 'EUR', $txt);
    $txt = str_replace('&uuml;', 'ü', $txt);
    $txt = str_replace('&Uuml;', 'Ü', $txt);

    return str_replace('uml;', '¨', $txt);
}

function isFileDirectUpload($fileSrc)
{
    $fileSrc = strtolower($fileSrc);
    $v = false;
    if (fileIndexOf($fileSrc, '.jpg')
      || fileIndexOf($fileSrc, '.jpeg')
      || fileIndexOf($fileSrc, '.gif')
      || fileIndexOf($fileSrc, '.svg')
      || fileIndexOf($fileSrc, '.png')
      || fileIndexOf($fileSrc, '.pdf')
      || fileIndexOf($fileSrc, '.mp4')
      || fileIndexOf($fileSrc, '.mp3')
      || fileIndexOf($fileSrc, '.odt')
      || fileIndexOf($fileSrc, '.ods')
      || fileIndexOf($fileSrc, '.odp')
      || fileIndexOf($fileSrc, '.otp')
      || fileIndexOf($fileSrc, '.xlsx')
      || fileIndexOf($fileSrc, '.docx')
      || fileIndexOf($fileSrc, '.pptx')
    ) {
        $v = true;
    }

    return $v;
}

function findNextId($CollPages, $ida)
{
    $varLpid = -1;
    $aFind = false;
    foreach ($CollPages as &$row) {
        if ($aFind) {
            $varLpid = $row['id'];
            $aFind = false;
        }
        if ($row['id'] == $ida) {
            $aFind = true;
        }
    }

    return $varLpid;
}
