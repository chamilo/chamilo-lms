<?php
/* For licensing terms, see /LICENSE */

use Vimeo\Vimeo;

require __DIR__.'/../../../../../../global.inc.php';

$config = api_get_configuration_sub_value('ckeditor_vimeo_embed/config');

if (false === $config ||
    empty($config['client_id']) || empty($config['client_secret']) || empty($config['access_token'])
) {
    echo json_encode(['error' => true, 'message' => get_lang('NotAllowed')]);
    exit;
}

header('Content-Type: application/json');

try {
    if (empty($_FILES) ||
        empty($_POST) ||
        empty($_POST['title']) ||
        !isset($_POST['description']) ||
        empty($_FILES['ve_file']) ||
        !isset($_POST['privacy_download']) ||
        empty($_POST['privacy_embed']) ||
        !isset($_POST['privacy_embed_whitelist']) ||
        empty($_POST['privacy_view'])
    ) {
        throw new Exception('Missing params.');
    }

    if ($_FILES['ve_file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("File error ({$_FILES['ve_file']['error']}).");
    }

    if (empty($config['access_token'])) {
        throw new Exception(
            'You can not upload a file without an access token. You can find this token on your app page.'
        );
    }

    $vimeo = new Vimeo($config['client_id'], $config['client_secret'], $config['access_token']);

    $uri = $vimeo->upload(
        $_FILES['ve_file']['tmp_name'],
        array(
            'name' => $_POST['title'],
            'description' => $_POST['description'],
            'privacy' => [
                'download' => $_POST['privacy_download'] === 'true',
                'embed' => $_POST['privacy_embed'],
                'view' => $_POST['privacy_view'],
            ],
        )
    );

    if ('whitelist' === $_POST['privacy_embed'] && !empty($_POST['privacy_embed_whitelist'])) {
        $vimeo->request(
            "$uri/privacy/domains/{$_POST['privacy_embed_whitelist']}",
            [],
            'PUT'
        );
    }

    $videoData = $vimeo->request("$uri?fields=link");

    $singleUri = str_replace('videos', 'video', $uri);

    $embed = '<div class="embeddedContent">
            <div style="padding:56.25% 0 0 0;position:relative;">
                <iframe allow="autoplay; fullscreen" allowfullscreen frameborder="0"
                        src="https://player.vimeo.com'.$singleUri.'"
                        style="position:absolute;top:0;left:0;width:100%;height:100%;"></iframe>
            </div>
        </div>';

    echo json_encode(
        [
            'uploaded' => true,
            'url' => $videoData['body']['link'],
            'embed' => $embed,
        ]
    );
} catch (VimeoUploadException $exception) {
    echo json_encode(
        [
            'error' => true,
            'message' => $e->getMessage(),
        ]
    );
} catch (VimeoRequestException $exception) {
    echo json_encode(
        [
            'error' => true,
            'message' => $exception->getMessage(),
        ]
    );
} catch (Exception $exception) {
    echo json_encode(
        [
            'error' => true,
            'message' => $exception->getMessage(),
        ]
    );
}
