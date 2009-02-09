<?php
// name of the language file that needs to be included 
$language_file = array('resourcelinker','document');
include('../../../../../../inc/global.inc.php');
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
	<head>
		<title>Import MP3</title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<meta content="noindex, nofollow" name="robots">
		<script type="text/javascript">
		/*<![CDATA[*/		
		var rel_path = "<?php  echo api_get_path(REL_CODE_PATH); ?>";
		//var tab_to_select = '<?php echo !empty($_SERVER['QUERY_STRING']) ? 'Info' : 'Upload'; ?>';
		var tab_to_select = 'Info' ;
		/*]]>*/
		</script>
		<script src="../../dialog/common/fck_dialog_common.js" type="text/javascript"></script>
		<script src="fck_mp3.js" type="text/javascript"></script>
		<link href="../../dialog/common/fck_dialog_common.css" type="text/css" rel="stylesheet">
	</head>
	<body> <!--scroll="no" style="overflow: hidden"-->
		<div id="divInfo">
		  <div id="divExtra1"style="display: none">
			<table cellspacing="1" cellpadding="1" border="0" width="100%">
				<tr>
					<td>
						<table cellspacing="0" cellpadding="0" width="100%" border="0">
							<tr>
							<td valign="top" width="100%">								
								<span fckLang="DlgMP3URL">URL</span><br>
								<input id="mpUrl" onBlur="javascript:updatePreview();" style="width: 100%" type="text">
							</td>
							<td id="tdBrowse" valign="bottom" nowrap>
								<input type="button" fckLang="DlgMP3BtnBrowse" value="Browse Server" onClick="javascript:BrowseServer();" id="btnBrowse">
							</td>
						</tr>
						</table>
					</td>
				</tr>
			</table>
		  </div>
		  <div style="text-align: center;">
		    <table style="width: 95%; border: none; margin-left: auto; margin-right: auto;">
		      <tr>
		        <td>
		  <?php
		  $sType = "MP3";
		  //if (api_is_in_course() || api_is_platform_admin())
		  //{
		 	 include(api_get_path(INCLUDE_PATH).'course_document.inc.php');
		  //}		  
		  ?>
		        </td>
		      </tr>
		    </table>
		  </div>
		</div>
		<div id="divUpload" style="display: none">		
		<?php		
			include_once(api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');
			$form = new FormValidator('frmUpload','POST','','UploadWindow','id="frmUpload" enctype="multipart/form-data" onSubmit="javascript:return CheckUpload();"');			
			$form->addElement('html','<table cellspacing="1" cellpadding="1" border="0" width="90%" align="center">');			
			$form->addElement('html','<tr><td>&nbsp;</td><tr><td>');
			$form->addElement('file','NewFile','','id="txtUploadFile" size="40"');
			$form->addElement('html','</td></tr>');			
			$form->addElement('html','<tr><td>');
			//$renderer = & $form->defaultRenderer();
			//$renderer->setElementTemplate('<div style="margin-left:-4px;">{element} {label}</div>', 'autostart');
			$form->addElement('checkbox', 'autostart', '', '&nbsp;'.get_lang('FckMp3Autostart'), array('id' => 'autostart'));
			$form->addElement('html','</td></tr>');			
			$form->addElement('html','<tr><td>');
			$form->addElement('submit','','Send it to the Server','id="btnUpload" fckLang="DlgLnkBtnUpload"');
			$form->addElement('html','</td></tr></table>');			
			$form->addElement('html','<iframe name="UploadWindow" style="display: none" src="../../fckblank.html"></iframe>');
			
			$form->add_real_progress_bar('fckMP3','NewFile');			
			$form->display();			
		?>		
		</div>
		<script type="text/javascript">window_onload(tab_to_select);</script>
	</body>
</html>
