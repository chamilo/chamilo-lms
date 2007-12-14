<?php
/*
    DOKEOS - elearning and course management software

    For a full list of contributors, see documentation/credits.html

    This program is free software; you can redistribute it and/or
    modify it under the terms of the GNU General Public License
    as published by the Free Software Foundation; either version 2
    of the License, or (at your option) any later version.
    See "documentation/licence.html" more details.

    Contact:
		Dokeos
		Rue des Palais 44 Paleizenstraat
		B-1030 Brussels - Belgium
		Tel. +32 (2) 211 34 56
*/


/**
*	Code library for HotPotatoes integration.
*	@package dokeos.exercise
* 	@author Istvan Mandak
* 	@version $Id: showinframes.php 13988 2007-12-14 05:05:51Z yannoo $
*/

/*
-----------------------------------------------------------
	Included libraries
-----------------------------------------------------------
*/
include('../inc/global.inc.php');
include_once(api_get_path(LIBRARY_PATH).'fileManage.lib.php');
$time=time();
require_once(api_get_path(SYS_PATH).'main/exercice/hotpotatoes.lib.php');

// init
$doc_url=urldecode($_GET['file']);
$cid = api_get_course_id();
$documentPath= api_get_path(SYS_COURSE_PATH).$_course['path']."/document";
$documentWebPath= api_get_path(WEB_COURSE_PATH).$_course['path']."/document";
$origin = $_REQUEST['origin'];
$learnpath_id = $_REQUEST['learnpath_id'];
$learnpath_item_id = $_REQUEST['learnpath_item_id'];
$time = $_REQUEST['time'];

// read content
$full_file_path = $documentPath.$doc_url;
my_delete($full_file_path.$_user['user_id'].".t.html");
$content = ReadFileCont($full_file_path.$_user['user_id'].".t.html");

if ($content=="")
{
	$content = ReadFileCont($full_file_path);
	$mit = "function Finish(){";

	$js_content = "var SaveScoreVariable = 0; // This variable included by Dokeos System\n".
				"function mySaveScore() // This function included by Dokeos System\n".
				"{\n".
				"   if (SaveScoreVariable==0)\n".
				"		{\n".
				"			SaveScoreVariable = 1;\n".
				"			if (C.ie)\n".
				"			{\n".
				"				document.location.href = \"".api_get_path(WEB_PATH)."main/exercice/"."savescores.php?origin=$origin&learnpath_id=$learnpath_id&learnpath_item_id=$learnpath_item_id&time=$time&test=".$doc_url."&uid=".$_user['user_id']."&cid=".$cid."&score=\"+Score;\n".
				"				//window.alert(Score);\n".
				"			}\n".
				"			else\n".
				"			{\n".
				"				window.location.href = \"".api_get_path(WEB_PATH)."main/exercice/"."savescores.php?origin=$origin&learnpath_id=$learnpath_id&learnpath_item_id=$learnpath_item_id&time=$time&test=".$doc_url."&uid=".$_user['user_id']."&cid=".$cid."&score=\"+Score;\n".
				"			}\n".
				"		}\n".
				"}\n".
				"// Must be included \n".
				"function Finish(){\n".
				" mySaveScore();";

	$newcontent = str_replace($mit,$js_content,$content);
	$prehref="<!-- BeginTopNavButtons -->";
	$posthref="<!-- BeginTopNavButtons --><!-- edited by Dokeos -->";
	$newcontent = str_replace($prehref,$posthref,$newcontent);


	if (CheckSubFolder($full_file_path.$_user['user_id'].".t.html")==0)
	{ $newcontent = ReplaceImgTag($newcontent); }

}
else
{
	//my_delete($full_file_path.$_user['user_id'].".t.html");
	$newcontent = $content;
}

WriteFileCont($full_file_path.$_user['user_id'].".t.html",$newcontent);

/*	$prehref="javascript:void(0);";
	$posthref=$_configuration['root_web']."main/exercice/Hpdownload.php?doc_url=".$doc_url."&cid=".$cid."&uid=".$uid;
	$newcontent = str_replace($prehref,$posthref,$newcontent);

	$prehref="class=\"GridNum\" onclick=";
	$posthref="class=\"GridNum\" onMouseover=";
	$newcontent = str_replace($prehref,$posthref,$newcontent);
*/

$doc_url = GetFolderPath($doc_url).urlencode(GetFileName($doc_url));
//	echo $documentWebPath.$doc_url.$_user['user_id'].".t.html";
//	exit;
?>
<html>
<head>
<title>Tests - Dokeos</title>
</head>
<?php

if ($origin!='learnpath') {
	?>
	<frameset rows="130,*" border="0" frameborder="no">
		<frame name="top" scrolling="no" noresize target="contents" src="testheaderpage.php?file=<?php echo urlencode($_GET['file']); ?>">
		<frame name="main" src="<?php echo $documentWebPath.$doc_url.$_user['user_id'].".t.html?time=$time"; ?>">
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
	<script language='Javascript' type='text/javascript'>
		s='<?php echo $documentWebPath.$doc_url.$_user['user_id']; ?>.t.html?time=<?php echo $time; ?>';
		//document.write(s);
		window.location=s;
	</script>
	<?php
}
?>

</html>