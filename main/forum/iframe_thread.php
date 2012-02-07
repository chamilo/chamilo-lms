<?php
/* For licensing terms, see /license.txt */

/**
 * These files are a complete rework of the forum. The database structure is
 * based on phpBB but all the code is rewritten. A lot of new functionalities
 * are added:
 * - forum categories and forums can be sorted up or down, locked or made invisible
 * - consistent and integrated forum administration
 * - forum options:     are students allowed to edit their post?
 *                      moderation of posts (approval)
 *                      reply only forums (students cannot create new threads)
 *                      multiple forums per group
 * - sticky messages
 * - new view option: nested view
 * - quoting a message
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @copyright Ghent University
 *
 * @package chamilo.forum
 */

/* INIT SECTION */

/* Language Initialisation */

// Name of the language file that needs to be included
$language_file = 'forum';

//$this_section = SECTION_COURSES;

require_once '../inc/global.inc.php';

/* ACCESS RIGHTS */

// A notice for unauthorized people.
api_protect_course_script(true);

require_once api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php';
include_once api_get_path(LIBRARY_PATH).'groupmanager.lib.php';

$nameTools = get_lang('ToolForum');
?>
<!DOCTYPE html
     PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
     "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
    <title></title>
<style type="text/css" media="screen, projection">
/*<![CDATA[*/
@import "<?php echo api_get_path(WEB_CODE_PATH); ?>css/<?php echo api_get_setting('stylesheets');?>/default.css";
/*]]>*/
</style>
</head>
<body>
<?php

/* Including necessary files */

require 'forumconfig.inc.php';
require_once 'forumfunction.inc.php';


/* MAIN DISPLAY SECTION */

/* Retrieving forum and forum categorie information */

// We are getting all the information about the current forum and forum category.
// Note pcool: I tried to use only one sql statement (and function) for this,
// but the problem is that the visibility of the forum AND forum cateogory are stored in the item_property table.
$current_thread=get_thread_information($_GET['thread']); // Note: this has to be validated that it is an existing thread.
$current_forum=get_forum_information($current_thread['forum_id']); // Note: this has to be validated that it is an existing forum.
$current_forum_category=get_forumcategory_information($current_forum['forum_category']);

/* Is the user allowed here? */

// if the user is not a course administrator and the forum is hidden
// then the user is not allowed here.
if (!api_is_allowed_to_edit(false, true) AND ($current_forum['visibility'] == 0 OR $current_thread['visibility'] == 0)) {
    $forum_allow = forum_not_allowed_here();
    if ($forum_allow === false) {
        exit;
    }
}

$course_id = api_get_course_int_id();

/* Display Forum Category and the Forum information */

// We are getting all the information about the current forum and forum category.
// Note pcool: I tried to use only one sql statement (and function) for this,
// but the problem is that the visibility of the forum AND forum cateogory are stored in the item_property table.

$sql = "SELECT * FROM $table_posts posts, $table_users users
        WHERE 
        posts.c_id = $course_id AND 
        posts.thread_id='".$current_thread['thread_id']."'
        AND posts.poster_id=users.user_id
        ORDER BY posts.post_id ASC";
$result = Database::query($sql);

echo "<table width=\"100%\" cellspacing=\"5\" border=\"0\">";
while ($row = Database::fetch_array($result)) {
    echo "<tr>";
    echo "<td rowspan=\"2\" class=\"forum_message_left\">";
    $username = api_htmlentities(sprintf(get_lang('LoginX'), $row['username']), ENT_QUOTES);
    if ($row['user_id']=='0') {
        $name = $row['poster_name'];
    } else {
        $name = api_get_person_name($row['firstname'], $row['lastname']);
    }
    echo Display::tag('span', $name, array('title'=>$username)).'<br />';
    echo api_convert_and_format_date($row['post_date']).'<br /><br />';

    echo "</td>";
    echo "<td class=\"forum_message_post_title\">".Security::remove_XSS($row['post_title'])."</td>";
    echo "</tr>";

    echo "<tr>";
    echo "<td class=\"forum_message_post_text\">".Security::remove_XSS($row['post_text'], STUDENT)."</td>";
    echo "</tr>";
}
echo "</table>";

?>
</body>
</html>