<?php
/* For licensing terms, see /license.txt */
/**
*	@package chamilo.admin
*	@author Carlos Vargas
*	This file is the calendar/print.php
*/
// name of the language file that needs to be included
$language_file = 'agenda';
$id=$_GET['id'];

if(strstr($id,','))
{
	$id=explode(',',$id);
	$id=array_map('intval',$id);
	$id=implode(',',$id);
}
else
{
	$id=intval($id);
}

// setting the global file that gets the general configuration, the databases, the languages, ...
require('../inc/global.inc.php');



$TABLEAGENDA 		= Database::get_main_table(TABLE_MAIN_SYSTEM_CALENDAR);

$sql 			= "SELECT * FROM $TABLEAGENDA WHERE id IN($id) ORDER BY start_date DESC";
$result			= Database::query($sql);
?>

<html>
<head>
<title><?php echo get_lang('Print'); ?></title>
<style type="text/css" media="screen, projection">
/*<![CDATA[*/
@import "../css/<?php echo api_get_setting('stylesheets'); ?>/default.css";
/*]]>*/
</style>
</head>
<body style="margin: 15px; padding: 0px;">

<center>
<input type="button" value="<?php echo api_htmlentities(get_lang('Print'),ENT_QUOTES,$charset); ?>" onClick="javascript:window.print();" />
</center>
<br /><br />

<?php
while($row=Database::fetch_array($result))
{
	$row['content'] = $row['content'];
	$row['content'] = make_clickable($row['content']);
	$row['content'] = text_filter($row['content']);
	$row['content'] = str_replace('<a ','<a target="_blank" ',$row['content']);

	if(!empty($row['title']))
	{
		echo '<b>'.$row['title'].'</b><br /><br />';
	}

	echo get_lang('StartTime').' : ';

	echo api_convert_and_format_date($row["start_date"], null, date_default_timezone_get());

	echo '<br />';

	echo get_lang('EndTime').' : ';

	echo api_convert_and_format_date($row["end_date"], null, date_default_timezone_get());

	echo '<br /><br />';

	echo $row['content'].'<hr size="1" noshade="noshade" />';
}
?>

<br /><br />
<center>
<input type="button" value="<?php echo api_htmlentities(get_lang('Print'),ENT_QUOTES,$charset); ?>" onClick="javascript:window.print();" />
</center>

</body>
</html>
