<?
require_once ('../../../../../../global.inc.php');
?>
<!--
 * 
 * File Authors:
 * 		shiv kumar (shiv@ballisticlearning.com)
-->
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<link href="browser.css" type="text/css" rel="stylesheet">
		<script type="text/javascript" src="js/common.js"></script>
		<script language="javascript">

		function SetResourceType(url)
		{
			window.parent.location.href=url;
		}

		</script>
	</head>
	<body bottomMargin="0" topMargin="0">
		<table height="100%" cellSpacing="0" cellPadding="0" width="100%" border="0">
			<tr>
				<td nowrap>
					Current Directory<BR>
					<?
						$course_path = api_get_path(SYS_COURSE_PATH);
						$Course_Dir = GetCourseFolders($course_path);
					?>
					<select id="cmbType" style="WIDTH: 100%" onchange="SetResourceType( this.options[selectedIndex].value )">
					<?
					$string = $_SERVER['HTTP_REFERER'];
					$pattern = '#([^<>]+/courses/)([^<>]+)(/document/)#i';
					preg_match($pattern, $string, $matches);

					for($i=0;$i<count($Course_Dir);$i++){
					$replacement = '$1'.$Course_Dir[$i].'$3';
					$new_url = preg_replace($pattern, $replacement, $string);
					$selected=($Course_Dir[$i]==$matches[2])?"selected":"";
					echo "<option value=\"$new_url\" $selected>".$Course_Dir[$i]."</option>";
					}
					?>
					</select>
				</td>
			</tr>
		</table>
	</body>
</html>
<?
function GetCourseFolders($currentFolder )
{

	// Array that will hold the folders names.
	$aFolders	= array() ;

	$oCurrentFolder = opendir( $currentFolder ) ;

	while ( false !== ($sFile = readdir( $oCurrentFolder )) )
	{
		if ( $sFile != '.' && $sFile != '..' && $sFile != 'CVS' && is_dir( $currentFolder . $sFile ) )
			$aFolders[] = $sFile;

	}

	closedir( $oCurrentFolder ) ;
	
	return $aFolders;
}
?>