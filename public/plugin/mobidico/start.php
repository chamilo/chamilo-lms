<?php

require_once __DIR__.'/../../main/inc/global.inc.php';

$course_plugin = 'mobidico'; //needed in order to load the plugin lang variables

$plugin = Mobidico::create();

if ('true' !== $plugin->get('tool_enable')) {
    api_not_allowed(true);
}

$url = $plugin->get('mobidico_url');
$key = $plugin->get('api_key');

$tool_name = get_lang('Videoconference');

$params = [
    'chamiloid' => api_get_user_id(),
    'API_KEY' => $key,
];

$redirect = '';
try {
    $client = new GuzzleHttp\Client();
    $response = $client->request(
        'POST',
        $url.'/app/desktop/php/authenticate.php',
        [
            'form_params' => $params,
            'verify' => false,
        ]
    );

    $status = (int) $response->getStatusCode();
    if (200 === $status) {
        $result = json_decode($response->getBody());
        if ($result && isset($result->status)) {
            if ('OK' === $result->status) {
                $redirect = $url.'/app/index.html?session='.$result->session;
            } else {
                api_not_allowed(true);
            }
        }
    }
} catch (Exception $e) {
    echo $e->getMessage();
}

$htmlHeadXtra[] = '<script>
$(document).ready(function() {
    var url = "'.$redirect.'";
    var win = window.open(url, "_blank");
    win.focus();
});
</script>';

$tpl = new Template('Mobidico');
$content = '';
$tpl->assign('content', $content);
$tpl->display_one_col_template();
