<?php
// name of the language file that needs to be included 
$language_file = array('resourcelinker');
include('../../../../../../inc/global.inc.php');
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
	<head>
		<title>Movie Properties</title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<meta name="robots" content="noindex, nofollow">
		<script src="../../dialog/common/fck_dialog_common.js" type="text/javascript"></script>
		<script src="fck_video.js" type="text/javascript"></script>
		<link href="../../dialog/common/fck_dialog_common.css" type="text/css" rel="stylesheet">
	</head>
	<body> <!--scroll="no" style="OVERFLOW: hidden"-->
		<div id="divInfo">
		  <div id="divExtra1"  style="DISPLAY: none">
			<table cellspacing="1" cellpadding="1" border="0" width="100%">
				<tr>
					<td>
						<table cellspacing="0" cellpadding="0" width="100%" border="0">
							<tr>
							<td valign="top" width="100%">								
								<span fckLang="DlgVideoURL">URL</span><br>
								<input id="txtUrl" onBlur="UpdatePreview();" style="WIDTH: 100%" type="text">
							</td>
							<td id="tdBrowse" valign="bottom" nowrap>
								<input type="button" fckLang="DlgVideoBtnBrowse" value="Browse Server" onClick="BrowseServer();" id="btnBrowse">
							</td>
						</tr>
						</table>
					</td>
				</tr>
			</table>
		  </div>
		  <?php 
		  
		  $sType = "Video";
		  include(api_get_path(INCLUDE_PATH).'course_document.inc.php');
		  
		  ?>
		</div>
		<div id="divUpload" style="DISPLAY: none">
		<?php
		require("../loader.class.php");
		$loader = new Loader('frmUpload');
		$loader->init();
		?>
		<form id="frmUpload" name="frmUpload" method="post" target="UploadWindow" enctype="multipart/form-data" action="" onSubmit="return CheckUpload();">
			<!--<span fckLang="DlgLnkUpload">Upload</span>--><br />
			<table cellspacing="1" cellpadding="1" border="0" width="90%" align="center">
			<tr><td><input id="txtUploadFile" style="WIDTH: 100%" type="file" size="40" name="NewFile" /></td></tr>
			<tr><td><input id="btnUpload" type="submit" value="Send it to the Server" fckLang="DlgLnkBtnUpload" /></td></tr>
			</table>
			<iframe name="UploadWindow" style="DISPLAY: none" src="../fckblank.html"></iframe>
		</form>
		<?php
		$loader->close();
		?>
		</div>
	<!--<script language="javascript">window_onload();</script>-->
	</body>
</html>
