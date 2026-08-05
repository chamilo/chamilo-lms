<?php

/* For license terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
use Symfony\Component\HttpFoundation\Response;

require_once __DIR__.'/../../main/inc/global.inc.php';

$plugin = TopLinksPlugin::create();
$filename = $plugin->sanitizeIconName((string) ($_GET['f'] ?? ''));

if (null === $filename) {
    (new Response('', Response::HTTP_NOT_FOUND))->send();
    exit;
}

$storagePath = $plugin->getIconStoragePath($filename);
$pluginsFilesystem = Container::getPluginsFileSystem();

if (!$pluginsFilesystem->fileExists($storagePath)) {
    (new Response('', Response::HTTP_NOT_FOUND))->send();
    exit;
}

$content = $pluginsFilesystem->read($storagePath);
$mimeType = $pluginsFilesystem->mimeType($storagePath) ?: 'image/png';

$response = new Response(
    $content,
    Response::HTTP_OK,
    [
        'Content-Type' => $mimeType,
        'Cache-Control' => 'public, max-age=86400',
        'X-Content-Type-Options' => 'nosniff',
    ]
);
$response->send();
exit;
