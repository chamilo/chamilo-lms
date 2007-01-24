<?php
$cidReq = urlencode($_GET['cidReq']);
$uInfo = intval($_GET['uInfo']);
?>
 (<a href="<?php echo $_SERVER['PHP_SELF']."?cidReq=".$cidReq."&uInfo=".$uInfo; ?>">Hide</a>)

<?php

foreach ($allpermissions as $tool=>$toolpermissions)
{
	echo "\t<tr>\n";
	echo "\t\t<td>\n";
	echo $tool;
	echo "\t\t</td>\n";

	foreach ($possiblepermissions as $key=>$value)
	{
		echo "\t\t<td align='center'>\n";
		// checking if this permission is relevant for this tool
		if (in_array($value,$allpermissions[$tool]))
		{
			$checked="";
			if (is_array($currentpermissions[$tool]) AND in_array($value,$currentpermissions[$tool]))
			{
				$checked="checked";
			}
			echo "<input type=\"checkbox\" name=\"permission*$tool*$value\" $checked>";
		}
		echo "\t\t</td>\n";
	}

	echo "\t</tr>\n";
}
echo "</table>\n";

echo "<input type=\"Submit\" name=\"StorePermissions\" value=\"Store Permissions\">";
echo "</form>";







/*
echo "<hr>allpermissions<br>";
echo "<pre>";
print_r($allpermissions);
echo "</pre>";

echo "currentpermissions<br>";
echo "<pre>";
print_r($currentpermissions);
echo "</pre>";
*/
?>