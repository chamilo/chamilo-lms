<?php
include 'libwiris.php';
$config = wrs_loadConfig(WRS_CONFIG_FILE);
$availableLanguages = wrs_getAvailableCASLanguages($config['wiriscaslanguages']);

if (isset($_GET['mode']) && $_GET['mode'] == 'applet') {
	if (isset($_GET['lang']) && in_array($_GET['lang'], $availableLanguages)) {
		$language = $_GET['lang'];
	}
	else {
		$language = $availableLanguages[0];
	}
	
	$codebase = wrs_replaceVariable($config['wiriscascodebase'], 'LANG', $language);
	$archive = wrs_replaceVariable($config['wiriscasarchive'], 'LANG', $language);
	$className = wrs_replaceVariable($config['wiriscasclass'], 'LANG', $language);
	
	?>
	<html>
		<head>
			<style type="text/css">
				/*<!--*/
				body {
					overflow: hidden;		// Hide scrollbars
				}
				/*-->*/
			</style>
		</head>
		<body topmargin="0" leftmargin="0" marginwidth="0" marginheight="0">
			<applet id="applet" alt="WIRIS CAS" codebase="<?php echo htmlentities($codebase, ENT_QUOTES, 'UTF-8'); ?>" archive="<?php echo htmlentities($archive, ENT_QUOTES, 'UTF-8'); ?>" code="<?php echo htmlentities($className, ENT_QUOTES, 'UTF-8'); ?>" width="100%" height="100%">
				<p>You need JAVA&reg; to use WIRIS tools.<br />FREE download from <a target="_blank" href="http://www.java.com">www.java.com</a></p>
			</applet>
		</body>
	</html>
	<?php
}
else {
	?>
	<html>
		<head>
			<meta http-equiv="content-type" content="text/html; charset=UTF-8"/>
			<script type="text/javascript" src="../core/cas.js"></script>
			<title>WIRIS CAS</title>
			
			<style type="text/css">
				/*<!--*/
				body {
					overflow: hidden;		// Hide scrollbars
				}
				/*-->*/
			</style>
		</head>
		<body topmargin="0" leftmargin="0" marginwidth="0" marginheight="0">
			<form id="optionForm">
				<table height="100%" width="100%">
					<tr>
						<td id="appletContainer" colspan="5"></td>
					</tr>
					<tr height="1px">
						<td>Width</td>
						<td><input name="width" type="text" value="<?php echo $config['CAS_width']; ?>"/></td>					
						<td><input name="executeonload" type="checkbox"/> Calculate on load</td>
						<td><input name="toolbar" type="checkbox" checked /> Show toolbar</td>
						
						<td>
							Language
							
							<select id="languageList">
								<?php
								foreach ($availableLanguages as $language) {
									$language = htmlentities($language, ENT_QUOTES, 'UTF-8');
									echo '<option value="', $language, '">', $language, '</option>';
								}
								?>
							</select>
						</td>
					</tr>
					<tr height="1px">
						<td>Height</td>
						<td><input name="height" type="text" value="<?php echo $config['CAS_height']; ?>"/></td>
						<td><input name="focusonload" type="checkbox"/> Focus on load</td>
						<td><input name="level" type="checkbox"/> Elementary mode</td>
						<td></td>
					</tr>
					<tr height="1px">
						<td colspan="5"><input id="submit" value="Accept" type="button"/> <input id="cancel" value="Cancel" type="button"/></td>
					</tr>
				</table>
			</form>
		</body>
	</html>
	<?php
}
?>