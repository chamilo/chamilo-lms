<?php
include 'libwiris.php';
$config = wrs_loadConfig(WRS_CONFIG_FILE);
?>
<html>
	<head>
		<meta http-equiv="content-type" content="text/html; charset=UTF-8"/>
		<script type="text/javascript" src="../core/editor.js"></script>
		<title>WIRIS Formula Editor</title>
		
		<style type="text/css">
			/*<!--*/
			
			#manualLink {
				float: right;
				margin-right: 20px;
			}
			
			/*-->*/
		</style>
	</head>
	<body topmargin="0" leftmargin="0" marginwidth="0" marginheight="0">
		<table width="100%" height="100%">
			<tr height="100%">
				<td>
					<applet id="applet" codebase="<?php echo $config['wirisformulaeditorcodebase']; ?>" archive="<?php echo $config['wirisformulaeditorarchive']; ?>" code="<?php echo $config['wirisformulaeditorcode']; ?>" height="100%" width="100%">
						<param name="menuBar" value="false"/>
						
						<?php
						$params = array(
							'lang' => 'wirisformulaeditorlang',
							'identMathvariant' => 'wirisimageidentmathvariant',
							'numberMathvariant' => 'wirisimagenumbermathvariant',
							'fontIdent' => 'wirisimagefontident',
							'fontNumber' => 'wirisimagefontnumber',
							'version' => 'wirisimageserviceversion'
						);
						
						foreach ($params as $key => $value) {
							if (isset($config[$value])) {
								echo '<param name="' . $key . '" value="' . $config[$value] . '" />';
							}
						}
						
						if (isset($config['wirisimagefontranges'])) {
							$fontRanges = explode(',', $config['wirisimagefontranges']);
							$fontRangesCount = count($fontRanges);
							
							for ($i = 0; $i < $fontRangesCount; ++$i) {
								$fontRangeName = trim($fontRanges[$i]);
								
								if (isset($config[$fontRangeName])) {
									echo '<param name="font' . $i . '" value="' . $config[$fontRangeName] . '" />';
								}
							}
						}
						?>
						
						<p>You need JAVA&reg; to use WIRIS tools.<br />FREE download from <a target="_blank" href="http://www.java.com">www.java.com</a></p>
					</applet>
				</td>
			</tr>
			<tr>
				<td>
					<a id="manualLink" href="http://www.wiris.com/portal/docs/editor-manual" target="_blank">Manual &gt;&gt;</a>
					<input type="button" id="submit" value="Accept" />
					<input type="button" id="cancel" value="Cancel" />
				</td>
			</tr>
		</table>
	</body>
</html>