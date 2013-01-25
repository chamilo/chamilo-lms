<?php
/* For licensing terms, see /license.txt */
/**
 *    Code library for HotPotatoes integration.
 * @package chamilo.exercise
 * @author Istvan Mandak
 */
/**
 * Included libraries
 */
require '../inc/global.inc.php';
require_once api_get_path(SYS_CODE_PATH).'exercice/hotpotatoes.lib.php';

$time = time();
$doc_url = str_replace(array('../', '\\', '\\0', '..'), array('', '', '', ''), urldecode($_GET['file']));
$cid = api_get_course_id();
$document_path = api_get_path(SYS_COURSE_PATH).$_course['path'].'/document';
$document_web_path = api_get_path(WEB_COURSE_PATH).$_course['path'].'/document';
$origin = $_REQUEST['origin'];
$learnpath_id = $_REQUEST['learnpath_id'];
$learnpath_item_id = $_REQUEST['learnpath_item_id'];
$time = $_REQUEST['time'];

$user_id = api_get_user_id();
$full_file_path = $document_path.$doc_url;
FileManager::my_delete($full_file_path.$user_id.'.t.html');
$content = ReadFileCont($full_file_path.$user_id.'.t.html');

if ($content == '') {
    $content = ReadFileCont($full_file_path);
    $mit = "function Finish(){";
    $js_content = "
        //Code added - start
        var SaveScoreVariable = 0;
        function mySaveScore() {
            if (SaveScoreVariable==0) {
                SaveScoreVariable = 1;
                    if (C.ie) {
                        document.location.href = '".api_get_path(
        WEB_PATH
    )."main/exercice/savescores.php?origin=$origin&learnpath_id=$learnpath_id&learnpath_item_id=$learnpath_item_id&time=".Security::remove_XSS(
        $time
    )."&test=".$doc_url."&uid=".$user_id."&cid=".$cid."&score='+Score;
						//window.alert(Score);
                    } else {
                        window.location.href = '".api_get_path(
        WEB_PATH
    )."main/exercice/savescores.php?origin=$origin&learnpath_id=$learnpath_id&learnpath_item_id=$learnpath_item_id&time=".Security::remove_XSS(
        $time
    )."&test=".$doc_url."&uid=".$user_id."&cid=".$cid."&score='+Score;
                    }
						}
				}
				function Finish(){
                    mySaveScore();
        //Code added - end
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
$my_file = str_replace(array('../', '\\..', '\\0', '..\\'), array('', '', '', ''), urldecode($my_file));

$title = GetQuizName($my_file, $documentPath);
if ($title == '') {
    $title = basename($my_file);
}
$nameTools = $title;
$noPHP_SELF = true;
if (isset($_SESSION['gradebook'])) {
    $gradebook = $_SESSION['gradebook'];
}

if (!empty($gradebook) && $gradebook == 'view') {
    $interbreadcrumb[] = array(
        'url' => '../gradebook/'.$_SESSION['gradebook_dest'],
        'name' => get_lang('ToolGradebook')
    );
}
$htmlHeadXtra[] = '
<script>
    $(document).ready( function(){
        var height = $(this).innerHeight() - 20;
        $("#hotpotatoe").css("height", height)
    });
</script>';

$interbreadcrumb[] = array("url" => "./exercice.php", "name" => get_lang('Exercices'));
if ($origin == 'learnpath') {
    Display::display_reduced_header($nameTools, "Exercise");
} else {
    Display::display_header($nameTools, "Exercise");
}
$url = $document_web_path.$doc_url.$user_id.'.t.html?time='.Security::remove_XSS($time);
echo '<iframe style="overflow:hidden" id="hotpotatoe" width="100%" frameborder="0" src="'.$url.'"></iframe>';
echo '</body></html>';
exit;
