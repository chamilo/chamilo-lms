<?php
/* For licensing terms, see /license.txt */

/**
 *	This	 script displays a help window with an overview of the allowed HTML-
 *   tags  and their attributes.
 *
 *	@package chamilo.help
 */
require '../inc/global.inc.php';

header('Content-Type: text/html; charset='.api_get_system_encoding());
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo api_get_language_isocode(); ?>" lang="<?php echo api_get_language_isocode(); ?>">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo api_get_system_encoding(); ?>" />
<title>
<?php echo get_lang('Allowed HTML tags'); ?>
</title>
<style type="text/css" media="screen, projection">
/*<![CDATA[*/
@import "<?php echo api_get_path(WEB_CSS_PATH); ?>chamilo/default.css";
/*]]>*/
</style>
<?php
if (api_get_setting('stylesheets') != '') {
    ?>
	<style type="text/css" media="screen, projection">
	/*<![CDATA[*/
	@import "<?php echo api_get_path(WEB_CSS_PATH), api_get_setting('stylesheets'); ?>/default.css";
	/*]]>*/
	</style>
<?php
}
?>
</head>
<body dir="<?php echo api_get_text_direction(); ?>">
<div style="margin:10px;">
<div style="text-align:right;"><a href="javascript: window.close();"><?php echo get_lang('Close'); ?></a></div>
<h4>
<?php echo get_lang('Allowed HTML tags'); ?>
</h4>
<?php
$html_type = $_SESSION['status'] == COURSEMANAGER ? TEACHER_HTML : STUDENT_HTML;

$fullpage = intval($_GET['fullpage']) != 0;
$tags = HTML_QuickForm_Rule_HTML :: get_allowed_tags($html_type, $fullpage);
$table_header = [];
$table_header[] = ['tag', true];
$table_header[] = ['attributes', false];
foreach ($tags as $tag => &$attributes) {
    $row = [];
    $row[] = '<kbd>'.$tag.'</kbd>';
    $row[] = '<kbd>&nbsp;'.implode(', ', array_keys($attributes)).'</kbd>';
    $table_data[] = $row;
}
Display::display_sortable_table($table_header, $table_data, [], [], ['fullpage' => intval($_GET['fullpage'])]);
?>
<div style="text-align:right;"><a href="javascript: window.close();"><?php echo get_lang('Close'); ?></a></div>
</div>
</body>
</html>
