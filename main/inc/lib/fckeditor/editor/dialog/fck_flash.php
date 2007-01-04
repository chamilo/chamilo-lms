<?php
// name of the language file that needs to be included 
$language_file = array('resourcelinker');
include('../../../../../inc/global.inc.php');
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
	<head>
		<title>Flash Properties</title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<meta content="noindex, nofollow" name="robots">
		<script src="common/fck_dialog_common.js" type="text/javascript"></script>
		<script src="fck_flash/fck_flash.js" type="text/javascript"></script>
		<link href="common/fck_dialog_common.css" type="text/css" rel="stylesheet">
	</head>
	<body><!--scroll="no" style="OVERFLOW: hidden"-->
		<div id="divInfo">
		  <div id="divExtra1"  style="DISPLAY: none">
			<table cellspacing="1" cellpadding="1" border="0" width="100%">
				<tr>
					<td>
						<table cellspacing="0" cellpadding="0" width="100%" border="0">
							<tr>
								<td width="100%"><span fckLang="DlgImgURL">URL</span>
								</td>
								<td id="tdBrowse" style="DISPLAY: block" nowrap rowspan="2"><br><input id="btnBrowse" onClick="BrowseServer();" type="button" value="Browse Server" fckLang="DlgBtnBrowseServer">
								</td>
							</tr>
							<tr>
								<td valign="top">
									<input id="txtUrl" style="WIDTH: 100%" type="text" onBlur="UpdatePreview();">
								</td>
							</tr>

							<tr><td colspan="2">&nbsp;</td></tr>
						</table>
					</td>
				</tr>
			</table>
		  </div>
		  <?php 
		  
		  $sType = "Flash";
		  include(api_get_path(INCLUDE_PATH).'course_document.inc.php');
		  
		  ?>
		</div>
		<div id="divExtra"> <!--added by shiv -->
			<table cellSpacing="1" cellPadding="1" width="100%" border="0">
				<TR>
					<TD>
						<table cellSpacing="0" cellPadding="0" border="0">
							<TR>
								<TD nowrap>
									<span fckLang="DlgImgWidth">Width</span><br>
									<input id="txtWidth" class="FCK__FieldNumeric" type="text" size="3">
								</TD>
								<TD>&nbsp;</TD>
								<TD>
									<span fckLang="DlgImgHeight">Height</span><br>
									<input id="txtHeight" class="FCK__FieldNumeric" type="text" size="3">
								</TD>
							</TR>
						</table>
					</TD>
				</TR>
				<tr>
					<td vAlign="top">
						<table cellSpacing="0" cellPadding="0" width="100%" border="0">
							<tr>
								<td valign="top" width="100%">
									<table cellSpacing="0" cellPadding="0" width="100%">
										<tr>
											<td><span fckLang="DlgImgPreview">Preview</span></td>
										</tr>
										<tr>
											<td id="ePreviewCell" valign="top" class="FlashPreviewArea"><iframe src="fck_flash/fck_flash_preview.html" frameborder="no" marginheight="0" marginwidth="0"></iframe></td>
										</tr>
									</table>
								</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
		</div>
		<div id="divUpload" style="DISPLAY: none">
		<?php
		require("../plugins/loader.class.php");
		$loader = new Loader('frmUpload');
		$loader->init();
		?>
			<form id="frmUpload" name="frmUpload" method="post" target="UploadWindow" enctype="multipart/form-data" action="" onSubmit="return CheckUpload();">
				<!--<span fckLang="DlgLnkUpload">Upload</span>--><br />
				<table cellspacing="1" cellpadding="1" border="0" width="90%" align="center">
				<tr><td><input id="txtUploadFile" type="file" size="40" name="NewFile" />&nbsp;<input id="btnUpload" type="submit" value="Send it to the Server" fckLang="DlgLnkBtnUpload" /></td></tr>
				</table>
				<iframe name="UploadWindow" style="DISPLAY: none" src="../fckblank.html"></iframe>
			</form>
		<?php
		$loader->close();
		?>
		</div>
		<div id="divAdvanced" style="DISPLAY: none">
			<TABLE cellSpacing="0" cellPadding="0" border="0">
				<TR>
					<TD nowrap>
						<span fckLang="DlgFlashScale">Scale</span><BR>
						<select id="cmbScale">
							<option value="" selected></option>
							<option value="showall" fckLang="DlgFlashScaleAll">Show all</option>
							<option value="noborder" fckLang="DlgFlashScaleNoBorder">No Border</option>
							<option value="exactfit" fckLang="DlgFlashScaleFit">Exact Fit</option>
						</select></TD>
					<TD>&nbsp;&nbsp;&nbsp; &nbsp;
					</TD>
					<td valign="bottom">
						<table>
							<tr>
								<td><input id="chkAutoPlay" type="checkbox" checked></td>
								<td><label for="chkAutoPlay" nowrap fckLang="DlgFlashChkPlay">Auto Play</label>&nbsp;&nbsp;</td>
								<td><input id="chkLoop" type="checkbox" checked></td>
								<td><label for="chkLoop" nowrap fckLang="DlgFlashChkLoop">Loop</label>&nbsp;&nbsp;</td>
								<td><input id="chkMenu" type="checkbox" checked></td>
								<td><label for="chkMenu" nowrap fckLang="DlgFlashChkMenu">Enable Flash Menu</label></td>
							</tr>
						</table>
					</td>
				</TR>
			</TABLE>
			<br>
			&nbsp;
			<table cellSpacing="0" cellPadding="0" width="100%" align="center" border="0">
				<tr>
					<td valign="top" width="50%"><span fckLang="DlgGenId">Id</span><br>
						<input id="txtAttId" style="WIDTH: 100%" type="text">
					</td>
					<td>&nbsp;&nbsp;</td>
					<td valign="top" nowrap><span fckLang="DlgGenClass">Stylesheet Classes</span><br>
						<input id="txtAttClasses" style="WIDTH: 100%" type="text">
					</td>
					<td>&nbsp;&nbsp;</td>
					<td valign="top" nowrap width="50%">&nbsp;<span fckLang="DlgGenTitle">Advisory Title</span><br>
						<input id="txtAttTitle" style="WIDTH: 100%" type="text">
					</td>
				</tr>
			</table>
			<span fckLang="DlgGenStyle">Style</span><br>
			<input id="txtAttStyle" style="WIDTH: 100%" type="text">
		</div>
		<div style="text-align: center;">
		<input type="button" value="<?php echo get_lang("Validate");?>" onclick="Ok();">
		</div>
	</body>
</html>
