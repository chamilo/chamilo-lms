<?php
/* For licensing terms, see /license.txt */
/**
*	@package chamilo.admin
* 	@author Julio Montoya <gugli100@gmail.com>
*/

// Language files that should be included
$language_file = array('admin', 'registration');
$cidReset = true;

require_once '../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'diagnoser.lib.php';
require_once api_get_path(LIBRARY_PATH).'sortabletable.class.php';

$this_section = SECTION_PLATFORM_ADMIN;
// User permissions
api_protect_admin_script();
$interbreadcrumb[] = array ('url' => 'index.php', 'name' => get_lang('PlatformAdmin'));
Display :: display_header(get_lang('SystemStatus'));

/* @todo this will be moved to default.css */
?>
<style>
.tabbed-pane {
	clear: both;
}

.tabbed-pane-tabs {
	list-style: none;
	margin: 0;
	padding: 0 0 0 1em;
	height: 0; /* for IE */
}

.tabbed-pane-tabs li {
	display: inline;
	float: left;
	margin: 0 0.5ex 0 0;
	padding: 0;
}

.tabbed-pane-tabs li a {
	display: block;
	margin: 0.25em 0 -1px 0;
	padding: 0.25em 1em;
	background: #E5EDF9;
	color: #4171b5;
	text-decoration: none;
	border: 1px solid #4271b5;
	-moz-border-radius-topleft: 5px;
	-moz-border-radius-topright: 5px;
	-webkit-border-top-left-radius: 5px;
	-webkit-border-top-right-radius: 5px;
}

.tabbed-pane-tabs li a:hover,.tabbed-pane-tabs li a.current {
	background: white;
	margin-top: 0;
	padding-bottom: 0.5em;
	border-bottom-color: white;
	color: #4171b5;
}

.tabbed-pane-tabs li a:active,.tabbed-pane-tabs li a.current,.tabbed-pane-tabs li a.current:hover {
	color: black;
}

.tabbed-pane-content {
	clear: both;
	border: 1px solid #4271b5;
	margin: 0;
	padding: 1em;
	min-height: 15em;
	-moz-border-radius-topright: 10px;
	-moz-border-radius-bottomleft: 10px;
	-webkit-border-top-right-radius: 10px;
	-webkit-border-bottom-left-radius: 10px;
}
</style>
<?php
$diag = new Diagnoser();
$diag->show_html();

Display :: display_footer();