<?php
// setting the language file
$langFile = "agenda";

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
include('../inc/global.inc.php');



$TABLEAGENDA 		= Database::get_course_table(AGENDA_TABLE);

$sql 			= "SELECT * FROM $TABLEAGENDA WHERE id IN($id) ORDER BY start_date DESC";
$result			= api_sql_query($sql,__FILE__,__LINE__);
?>

<html>
<head>
<title><?php echo get_lang('Print'); ?></title>
<style type="text/css" media="screen, projection">
/*<![CDATA[*/
@import "../css/default.css";
/*]]>*/
</style>
</head>
<body style="margin: 15px; padding: 0px;">

<center>
<input type="button" value="<?php echo htmlentities(get_lang('Print')); ?>" onclick="javascript:window.print();" />
</center>
<br /><br />

<?php
while($row=mysql_fetch_array($result))
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

	echo ucfirst(format_locale_date($dateFormatLong,strtotime($row["start_date"])))."&nbsp;&nbsp;&nbsp;";
	echo ucfirst(strftime($timeNoSecFormat,strtotime($row["start_date"])))."";

	echo '<br />';

	echo get_lang('EndTime').' : ';

	echo ucfirst(format_locale_date($dateFormatLong,strtotime($row["end_date"])))."&nbsp;&nbsp;&nbsp;";
	echo ucfirst(strftime($timeNoSecFormat,strtotime($row["end_date"])))."";

	echo '<br /><br />';

	echo $row['content'].'<hr size="1" noshade="noshade" />';
}
?>

<br /><br />
<center>
<input type="button" value="<?php echo htmlentities(get_lang('Print')); ?>" onclick="javascript:window.print();" />
</center>

</body>
</html>
