<?php
/* For licensing terms, see /license.txt */

/**
 *  This script contains the actual html code to display the "header"
 *  or "banner" on top of every Chamilo page.
 *  @todo this should be remove we should only use header.inc.php
 *
 *  @package chamilo.include
 */

require_once api_get_path(LIBRARY_PATH).'banner.lib.php';

$session_id     = api_get_session_id();
$session_name   = api_get_session_name($my_session_id);
echo '<div id="wrapper">';

echo '<ul id="navigation">';
if (!empty($help)) { 
    $help = Security::remove_XSS($help);
?>
    <li class="help">                   
        <a href="<?php echo api_get_path(WEB_CODE_PATH); ?>help/help.php?open=<?php echo $help; ?>&height=400&width=600" class="thickbox" title="<?php echo get_lang('Help'); ?>">
        <img src="<?php echo api_get_path(WEB_IMG_PATH);?>help.large.png" alt="<?php echo get_lang('Help');?>" title="<?php echo get_lang('Help');?>" />
        </a>
    </li>
<?php 
}
if (api_get_setting('show_link_bug_notification') == 'true') { 
?>
    <li class="report">
        <a href="http://support.chamilo.org/projects/chamilo-18/wiki/How_to_report_bugs" target="_blank">
        <img src="<?php echo api_get_path(WEB_IMG_PATH) ?>bug.large.png" style="vertical-align: middle;" alt="<?php echo get_lang('ReportABug') ?>" title="<?php echo get_lang('ReportABug');?>"/></a>
    </li>
<?php
}
echo'</ul>';
echo '<div id="header">';

show_header_1($language_file, $nameTools);
show_header_2();
$menu_navigation = show_header_3();
show_header_4($interbreadcrumb, $language_file, $nameTools);
    
if (isset($database_connection)) {
    // connect to the main database.
    // if single database, don't pefix table names with the main database name in SQL queries
    // (ex. SELECT * FROM table)
    // if multiple database, prefix table names with the course database name in SQL queries (or no prefix if the table is in
    // the main database)
    // (ex. SELECT * FROM table_from_main_db  -  SELECT * FROM courseDB.table_from_course_db)
    Database::select_db($_configuration['main_database'], $database_connection);
}
echo '</div>'; // <!-- end of the whole #header section -->
  
if (api_get_setting('show_toolshortcuts') == 'true') {        
    require_once api_get_path(INCLUDE_PATH).'tool_navigation_menu.inc.php';
    show_navigation_tool_shortcuts();        
}

echo '<div id="main">';
echo '<div id="submain">';


/*  "call for chat" module section */

$chat = strpos(api_get_self(), 'chat_banner.php');
if (!$chat) {
    include_once api_get_path(LIBRARY_PATH).'online.inc.php';
    //echo $accept;
    $chatcall = chatcall();
    if ($chatcall) {
        Display :: display_normal_message($chatcall);
    }
}

/*  Navigation menu section */

if (api_get_setting('show_navigation_menu') != 'false' && api_get_setting('show_navigation_menu') != 'icons') {
    Display::show_course_navigation_menu($_GET['isHidden']);
    $course_id = api_get_course_id();
    if (!empty($course_id) && ($course_id != -1)) {
        echo '<div id="menuButton">';
        echo $output_string_menu;
        echo '</div>';
        if (isset($_SESSION['hideMenu'])) {
            if ($_SESSION['hideMenu'] == 'shown') {
                if (isset($_cid)) {
                    echo '<div id="centerwrap">'; // <!-- start of #centerwrap -->
                    echo '<div id="center">'; // <!-- start of #center -->
                }
            }
        } else {
            if (isset($_cid)) {
                echo '<div id="centerwrap">'; // <!-- start of #centerwrap -->
                echo '<div id="center">'; //<!-- start of #center -->
            }
        }
    }
}