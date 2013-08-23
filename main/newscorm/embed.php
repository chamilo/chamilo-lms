<?php
/* For licensing terms, see /license.txt */

require_once '../inc/global.inc.php';

$type = $_REQUEST['type'];
$src  = Security::remove_XSS($_REQUEST['src']);
if (empty($type) || empty($src)) {
    api_not_allowed();
}

switch ($type) {
    case 'youtube':
        $src = 'http://www.youtube.com/embed/'.$src;
        $iframe = '<iframe class="youtube-player" type="text/html" width="640" height="385" src="'.$src.'" frameborder="0"></iframe>';
        break;
    case 'vimeo':
        $src = 'http://player.vimeo.com/video/'.$src;
        $iframe = '<iframe src="'.$src.'" width="640" height="385" frameborder="0" webkitAllowFullScreen mozallowfullscreen allowFullScreen></iframe>';
        break;
}
?>
<!doctype html>
<html>
    <head>
        <meta charset="utf-8">
        <title></title>
    </head>
    <body>
        <div id="content" style="width: 700px ;margin-left:auto; margin-right:auto;">
        <br />
        <?php echo $iframe; ?>
        </div>
    </body>
</html>
