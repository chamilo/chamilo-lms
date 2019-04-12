<?php
/* For licensing terms, see /license.txt */

/**
 * Code library for HotPotatoes integration.
 *
 * @package chamilo.exercise
 *
 * @author Istvan Mandak
 */
require_once __DIR__.'/../inc/global.inc.php';

api_protect_course_script(true);

require_once api_get_path(SYS_CODE_PATH).'exercise/hotpotatoes.lib.php';
$_course = api_get_course_info();

$time = time();
$doc_url = str_replace(['../', '\\', '\\0', '..'], ['', '', '', ''], urldecode($_GET['file']));
$cid = api_get_course_id();
$document_path = api_get_path(SYS_COURSE_PATH).$_course['path'].'/document';
$document_web_path = api_get_path(WEB_COURSE_PATH).$_course['path'].'/document';
$origin = api_get_origin();
$learnpath_id = isset($_REQUEST['learnpath_id']) ? $_REQUEST['learnpath_id'] : null;
$learnpath_item_id = isset($_REQUEST['learnpath_item_id']) ? $_REQUEST['learnpath_item_id'] : null;
$time = isset($_REQUEST['time']) ? $_REQUEST['time'] : null;
$lpViewId = isset($_REQUEST['lp_view_id']) ? $_REQUEST['lp_view_id'] : null;

$user_id = api_get_user_id();
$full_file_path = $document_path.$doc_url;
my_delete($full_file_path.$user_id.'.t.html');
$content = ReadFileCont($full_file_path.$user_id.'.t.html');

if ($content == '') {
    $content = ReadFileCont($full_file_path);
    // Do not move this like:
    $mit = "function Finish(){";
    $js_content = "
    // Code added - start
    var SaveScoreVariable = 0;
    function mySaveScore() {
        if (SaveScoreVariable==0) {
            SaveScoreVariable = 1;
            if (C.ie) {
                document.location.href = '".api_get_path(WEB_CODE_PATH)."exercise/savescores.php?lp_view_id=$lpViewId&origin=$origin&learnpath_id=$learnpath_id&learnpath_item_id=$learnpath_item_id&time=".Security::remove_XSS($time)."&test=".$doc_url."&uid=".$user_id."&cid=".$cid."&score='+Score;
            } else {
                window.location.href = '".api_get_path(WEB_CODE_PATH)."exercise/savescores.php?lp_view_id=$lpViewId&origin=$origin&learnpath_id=$learnpath_id&learnpath_item_id=$learnpath_item_id&time=".Security::remove_XSS($time)."&test=".$doc_url."&uid=".$user_id."&cid=".$cid."&score='+Score;
            }
        }
    }
    function Finish() {
        mySaveScore();
        // Code added - end
    ";

    $newcontent = str_replace($mit, $js_content, $content);
    $prehref = "<!-- BeginTopNavButtons -->";
    $posthref = "<!-- BeginTopNavButtons -->";
    $newcontent = str_replace($prehref, $posthref, $newcontent);

    if (CheckSubFolder($full_file_path.$user_id.'.t.html') == 0) {
        $newcontent = ReplaceImgTag($newcontent);
    }
} else {
    $newcontent = $content;
}

WriteFileCont($full_file_path.$user_id.'.t.html', $newcontent);
$doc_url = GetFolderPath($doc_url).urlencode(basename($doc_url));

$documentPath = api_get_path(SYS_COURSE_PATH).$_course['path']."/document";
$my_file = Security::remove_XSS($_GET['file']);
$my_file = str_replace(['../', '\\..', '\\0', '..\\'], ['', '', '', ''], urldecode($my_file));

$title = GetQuizName($my_file, $documentPath);
if ($title == '') {
    $title = basename($my_file);
}
$nameTools = $title;
$htmlHeadXtra[] = <<<HTML
    <script>
        function setHeight()
        {
            var iframe = document.getElementById('hotpotatoe');
            iframe.height = 800;
            var maxheight = $(iframe.contentDocument.body).outerHeight(true);
            iframe.height = maxheight
            $(iframe.contentDocument.body).children().each(function() {
                // If this elements height is bigger than the biggestHeight
                var tempheight = $(this)["0"].offsetHeight + $(this)["0"].offsetTop;
                if (tempheight > maxheight) {
                    // Set the maxheight to this Height
                    maxheight = tempheight;
                }
            });
            iframe.height = maxheight;
        }
        
        $(function() {
            var iframe = document.getElementById('hotpotatoe');
            iframe.onload = function () {
                // this.height = $(this.contentDocument.body).outerHeight(true)
                setTimeout(function(){
                   setHeight();
                }, 1750);
            };
        });
    </script>
HTML;

$interbreadcrumb[] = ["url" => './exercise.php?'.api_get_cidreq(), 'name' => get_lang('Exercises')];
if ($origin == 'learnpath') {
    Display::display_reduced_header($nameTools, "Exercise");
} else {
    Display::display_header($nameTools, "Exercise");
}
$url = $document_web_path.$doc_url.$user_id.'.t.html?time='.intval($time);
echo '<iframe id="hotpotatoe" name="hotpotatoe" width="100%" height="100%" frameborder="0" src="'.$url.'"></iframe>';

if ($origin == 'learnpath') {
    Display::display_reduced_footer();
} else {
    Display::display_footer();
}
