<?php

/* For licensing terms, see /license.txt */

/**
 * Script needed in order to avoid mixed content in links inside a learning path
 * In order to use this file you have to:
 *
 * 1. Modify configuration.php and add this setting: $_configuration['lp_fix_embed_content'] = true;
 * 2. Copy this file in app/courses/proxy.php
 * 3. Change your .htaccess in order to let the proxy.php to be read inside app/courses
 *
 */

require_once '../config/configuration.php';

if (!isset($_configuration['lp_fix_embed_content'])) {
    exit;
}

if (true !== $_configuration['lp_fix_embed_content']) {
    exit;
}

/**
 * Returns "%" or "px"
 *
 * 800px => function returns "px"
 * 800% => function returns %
 *
 * @param string $value
 * @return string
 */
function addPixelOrPercentage($value)
{
    $addPixel = strpos($value, 'px');
    $addPixel = !($addPixel === false);
    $addCharacter = '';
    if ($addPixel == false) {
        $addPercentage = strpos($value, '%');
        $addPercentage = !($addPercentage === false);
        if ($addPercentage) {
            $addCharacter = '%';
        }
    } else {
        $addCharacter = 'px';
    }

    return $addCharacter;
}

function get_http_response_code($theURL)
{
    $headers = get_headers($theURL);

    return substr($headers[0], 9, 3);
}


$height = isset($_GET['height']) ? (int) $_GET['height'].addPixelOrPercentage($_GET['height']) : '';
$width = isset($_GET['width']) ? (int) $_GET['width'].addPixelOrPercentage($_GET['width'])  : '';
$vars = isset($_GET['flashvars']) ? htmlentities($_GET['flashvars']) : '';
$src = isset($_GET['src']) ? htmlentities($_GET['src']) : '';
$id = isset($_GET['id']) ? htmlentities($_GET['id']) : '';
$type = isset($_GET['type']) ? $_GET['type'] : 'flash';

// Fixes URL like: https://www.vopspsy.ugent.be/pdfs/download.php?own=mvsteenk&file=caleidoscoop.pdf
if (strpos($src, 'download.php') !== false) {
    $src = str_replace('download.php', 'download.php?', $src);
    $src .= isset($_GET['own']) ? '&own='.htmlentities($_GET['own']) : '';
    $src .= isset($_GET['file']) ? '&file='.htmlentities($_GET['file']) : '';
}

$result = get_http_response_code($src);
$urlToTest = parse_url($src, PHP_URL_HOST);
$g = stream_context_create (array('ssl' => array('capture_peer_cert' => true)));
$r = @stream_socket_client("ssl://$urlToTest:443", $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $g);
$cont = stream_context_get_params($r);
$convertToSecure = false;

$certinfo = openssl_x509_parse($cont['options']['ssl']['peer_certificate']);
if (isset($certinfo) && isset($certinfo['subject']) && isset($certinfo['subject']['CN'])) {
    $certUrl = $certinfo['subject']['CN'];
    $parsed = parse_url($certUrl);

    // Remove www from URL
    $parsedUrl = preg_replace('#^(http(s)?://)?w{3}\.#', '$1', $certUrl);

    if ($urlToTest == $certUrl || $parsedUrl == $urlToTest) {
        $convertToSecure = true;
    }

    if ($urlToTest != $certUrl) {
        // url and cert url are different this will show a warning in browsers
        // use normal "http" version
        $result = false;
    }
}

if ($result == false) {
    $src = str_replace('https', 'http', $src);
}

if ($convertToSecure) {
    $src = str_replace('http', 'https', $src);
}

$result = '';
switch ($type) {
    case 'link':
        // Check if links comes from a course
        $srcParts = explode('/', $src);
        $srcParts = array_filter($srcParts);
        $srcParts = array_values($srcParts);

        if (isset($srcParts[0], $srcParts[2]) && $srcParts[0] === 'courses' && $srcParts[2] === 'document') {
            $src = $_configuration['root_web'].$src;
        }

        if (strpos($src, 'http') === false) {
            $src = "http://$src";
        }
        header('Location: '.$src);
        exit;
        break;
    case 'iframe':
        $result = '<iframe src="'.$src.'" width="'.$width.'" height="'.$height.'" ></iframe>';
        break;
    case 'flash':
        $result =  '
        <object
            id="'.$id.'" width="'.$width.'" height="'.$height.'" align="center"
            codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,40,0">
            <param name="id" value="'.$id.'">
            <param name="width" value="'.$width.'">
            <param name="height" value="'.$height.'">
            <param name="bgcolor" value="#ffffff">
            <param name="align" value="center">
            <param name="allowfullscreen" value="true">
            <param name="allowscriptaccess" value="always">
            <param name="quality" value="high">
            <param name="wmode" value="transparent">
            <param name="flashvars" value="'.$vars.'">
            <param name="src" value="'.$src.'">
            <embed
                id="'.$id.'" width="'.$width.'" height="'.$height.'" bgcolor="#ffffff" align="center"
                allowfullscreen="true" allowscriptaccess="always" quality="high" wmode="transparent"
                flashvars="'.$vars.'" src="'.$src.'"
                type="application/x-shockwave-flash"
            >
        </object>';
}

echo $result;
