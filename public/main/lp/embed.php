<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

require_once __DIR__.'/../inc/global.inc.php';

api_protect_course_script(true);

$type = $_REQUEST['type'] ?? '';
$src = $_REQUEST['source'] ?? '';
$origin = $_REQUEST['origin'] ?? '';
if (empty($type) || empty($src)) {
    api_not_allowed();
}

$iframe = '';
switch ($type) {
    case 'download':
        /** @var learnpath $learnPath */
        $learnPath = Session::read('oLP');
        $itemId = isset($_GET['lp_item_id']) ? $_GET['lp_item_id'] : '';
        if (!$learnPath || empty($itemId)) {
            api_not_allowed();
        }

        $file = learnpath::rl_get_resource_link_for_learnpath(
            api_get_course_int_id(),
            $learnPath->get_id(),
            $itemId,
            $learnPath->get_view_id()
        );

        $iframe = Display::return_message(
            Display::url(get_lang('Download'), $file, ['class' => 'btn btn--primary']),
            'info',
            false
        );
        break;
    case 'youtube':
        $videoId = preg_replace('/[^A-Za-z0-9_-]/', '', (string) $src);
        if ('' === $videoId) {
            api_not_allowed();
        }
        $videoUrl = 'https://www.youtube.com/embed/'.$videoId.'?enablejsapi=1&playsinline=1';
        $iframe .= '<div id="content" style="width: 700px ;margin-left:auto; margin-right:auto;"><br />';
        $iframe .= '<iframe class="youtube-player" type="text/html" width="640" height="385" src="'.Security::remove_XSS($videoUrl).'" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>';
        $iframe .= '</div>';
        break;
    case 'vimeo':
        $videoId = preg_replace('/[^0-9]/', '', (string) $src);
        if ('' === $videoId) {
            api_not_allowed();
        }
        $videoUrl = 'https://player.vimeo.com/video/'.$videoId.'?api=1';
        $iframe .= '<div id="content" style="width: 700px ;margin-left:auto; margin-right:auto;"><br />';
        $iframe .= '<iframe src="'.Security::remove_XSS($videoUrl).'" width="640" height="385" frameborder="0" webkitAllowFullScreen mozallowfullscreen allowFullScreen></iframe>';
        $iframe .= '</div>';
        break;
    case 'nonhttps':
        $icon = '&nbsp;<em class="icon-external-link icon-2x"></em>';
        $iframe = Security::remove_XSS(Display::return_message(
            Display::url($src.$icon, $src, ['class' => 'btn', 'target' => '_blank']),
            'normal',
            false
        ));
        break;
}

if ('learnpath' === $origin) {
    echo '<!doctype html>';
    echo '<html>';
    echo '<head>';
    echo '<meta charset="utf-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<style>';
    echo 'html,body{margin:0;padding:0;width:100%;height:100%;min-height:100%;overflow:hidden;background:#fff;}';
    echo 'body{font-family:Arial,Helvetica,sans-serif;}';
    echo '.lp-embed-content{box-sizing:border-box;display:flex;width:100%;height:100%;min-height:100%;align-items:stretch;justify-content:stretch;margin:0;padding:0;text-align:center;}';
    echo '#content{box-sizing:border-box;width:100%!important;height:100%!important;max-width:none!important;margin:0!important;padding:0!important;}';
    echo '#content>br{display:none;}';
    echo '#content iframe,.lp-embed-content iframe{display:block;width:100%!important;height:100%!important;max-width:none!important;max-height:none!important;border:0;}';
    echo '.btn,.btn--primary{display:inline-block;padding:8px 16px;border-radius:4px;text-decoration:none;}';
    echo '</style>';
    echo '</head>';
    echo '<body>';
    echo '<div class="lp-embed-content">';
    echo $iframe;
    echo '</div>';
    echo '</body>';
    echo '</html>';

    exit;
}

$htmlHeadXtra[] = "
<style>
body { background: none;}
</style>
";

Display::display_reduced_header();
echo $iframe;
Display::display_footer();
