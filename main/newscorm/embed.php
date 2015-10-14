<?php
/* For licensing terms, see /license.txt */

require_once '../inc/global.inc.php';

$type = $_REQUEST['type'];
$src  = Security::remove_XSS($_REQUEST['source']);
if (empty($type) || empty($src)) {
    api_not_allowed();
}

$iframe = '';
switch ($type) {
    case 'youtube':
        $src = 'http://www.youtube.com/embed/'.$src;
        $iframe .= '<div id="content" style="width: 700px ;margin-left:auto; margin-right:auto;"><br />';
        $iframe .= '<iframe class="youtube-player" type="text/html" width="640" height="385" src="'.$src.'" frameborder="0"></iframe>';
        $iframe .= '</div>';
        break;
    case 'vimeo':
        $src = 'http://player.vimeo.com/video/'.$src;
        $iframe .= '<div id="content" style="width: 700px ;margin-left:auto; margin-right:auto;"><br />';
        $iframe .= '<iframe src="'.$src.'" width="640" height="385" frameborder="0" webkitAllowFullScreen mozallowfullscreen allowFullScreen></iframe>';
        $iframe .= '</div>';
        break;
    case 'nonhttps':
        $icon = '&nbsp;<em class="icon-external-link icon-2x"></em>';
        $iframe = Display::return_message(
            Display::url($src.$icon, $src, ['class' => 'btn', 'target' => '_blank']),
            'normal',
            false
        );
        break;
}

$htmlHeadXtra[] = "
<style>
body { background: none;}
</style>
";

Display::display_reduced_header();
echo $iframe;
Display::display_footer();
