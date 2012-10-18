<?php
/* For licensing terms, see /license.txt */
/**
 *	Code library for HotPotatoes integration.
 *	@package chamilo.exercise
 * 	@author Istvan Mandak
 */
/**
 * Included libraries
 */
require '../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'fileManage.lib.php';
require_once api_get_path(SYS_CODE_PATH).'exercice/hotpotatoes.lib.php';

$time = time();

$doc_url = str_replace(array('../', '\\', '\\0', '..'), array('', '', '', ''), urldecode($_GET['file']));
$cid                = api_get_course_id();
$document_path      = api_get_path(SYS_COURSE_PATH).$_course['path'].'/document';
$document_web_path  = api_get_path(WEB_COURSE_PATH).$_course['path'].'/document';
$origin             = $_REQUEST['origin'];
$learnpath_id       = $_REQUEST['learnpath_id'];
$learnpath_item_id  = $_REQUEST['learnpath_item_id'];
$time               = $_REQUEST['time'];

$user_id = api_get_user_id();
$full_file_path = $document_path.$doc_url;
my_delete($full_file_path.$user_id.'.t.html');
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
                        document.location.href = '".api_get_path(WEB_PATH)."main/exercice/savescores.php?origin=$origin&learnpath_id=$learnpath_id&learnpath_item_id=$learnpath_item_id&time=".Security::remove_XSS($time)."&test=".$doc_url."&uid=".$user_id."&cid=".$cid."&score='+Score;
						//window.alert(Score);
                    } else {
                        window.location.href = '".api_get_path(WEB_PATH)."main/exercice/savescores.php?origin=$origin&learnpath_id=$learnpath_id&learnpath_item_id=$learnpath_item_id&time=".Security::remove_XSS($time)."&test=".$doc_url."&uid=".$user_id."&cid=".$cid."&score='+Score;
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

$documentPath= api_get_path(SYS_COURSE_PATH).$_course['path']."/document";
$my_file = Security::remove_XSS($_GET['file']);
$my_file = str_replace(array('../','\\..','\\0','..\\'),array('','','',''),urldecode($my_file));

$title = GetQuizName($my_file,$documentPath);
if ($title =='') {
	$title = basename($my_file);
}
$nameTools = $title;
$noPHP_SELF=true;
if (isset($_SESSION['gradebook'])){
	$gradebook=	$_SESSION['gradebook'];
}

if (!empty($gradebook) && $gradebook=='view') {
	$interbreadcrumb[]= array (
			'url' => '../gradebook/'.$_SESSION['gradebook_dest'],
			'name' => get_lang('ToolGradebook')
		);
}
$htmlHeadXtra[]  = '
<script>
    var height = window.innerHeight;
    $(document).ready( function(){
        $("iframe").css("height", height)
    });
</script>';

$interbreadcrumb[]= array ("url"=>"./exercice.php", "name"=> get_lang('Exercices'));
if ($origin == 'learnpath') {
    Display::display_reduced_header($nameTools,"Exercise");
} else {
    Display::display_header($nameTools,"Exercise");
}
$url = $document_web_path.$doc_url.$user_id.'.t.html?time='.Security::remove_XSS($time);
echo '<iframe id="hotpotatoe" width="100%" frameborder="0" src="'.$url.'"><iframe>';
exit;


if ($origin!='learnpath') {
	?>
	<frameset rows="<?php echo $header_height; ?>,*" border="0" frameborder="no">
		<frame name="top" scrolling="no" noresize target="contents" src="testheaderpage.php?file=<?php echo Security::remove_XSS(str_replace(array('../','\\','\\0','..'),array('','','',''),urldecode($_GET['file']))); ?>">
		<frame name="main" src="<?php echo $document_web_path.$doc_url.$user_id.'.t.html?time='.Security::remove_XSS($time); ?>">
	<noframes>
	<body>
		<p>This page uses frames, but your browser doesn't support them.
			We suggest you try Mozilla, Firebird, Safari, Opera, or other browsers updated this millenium.
 		</p>
	</body>
	</noframes>
	</frameset>
	<?php
} else {
	?>
	<script>
		s='<?php echo $document_web_path.$doc_url.$user_id; ?>.t.html?time=<?php echo Security::remove_XSS($time); ?>';		
		window.location=s;
	</script>
	<?php
}
?>

</html>
