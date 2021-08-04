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
 * - quoting a message.
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @copyright Ghent University
 */
require_once __DIR__.'/../inc/global.inc.php';

// A notice for unauthorized people.
api_protect_course_script(true);

$nameTools = get_lang('ToolForum');
Display::display_reduced_header();

require_once 'forumfunction.inc.php';

/* Retrieving forum and forum categorie information */

// We are getting all the information about the current forum and forum category.
// Note pcool: I tried to use only one sql statement (and function) for this,
// but the problem is that the visibility of the forum AND forum cateogory are stored in the item_property table.
$current_thread = get_thread_information(
    $_GET['forum'],
    $_GET['thread']
); // Note: this has to be validated that it is an existing thread.
$current_forum = get_forum_information($current_thread['forum_id']);
// Note: this has to be validated that it is an existing forum.
$current_forum_category = get_forumcategory_information(
    $current_forum['forum_category']
);

/* Is the user allowed here? */

// if the user is not a course administrator and the forum is hidden
// then the user is not allowed here.
if (!api_is_allowed_to_edit(false, true) &&
    ($current_forum['visibility'] == 0 || $current_thread['visibility'] == 0)
) {
    api_not_allowed(false);
}

$course_id = api_get_course_int_id();

$table_posts = Database::get_course_table(TABLE_FORUM_POST);
$table_users = Database::get_main_table(TABLE_MAIN_USER);

/* Display Forum Category and the Forum information */

// We are getting all the information about the current forum and forum category.
// Note pcool: I tried to use only one sql statement (and function) for this,
// but the problem is that the visibility of the forum AND forum cateogory are stored in the item_property table.
$sql = "SELECT * FROM $table_posts posts 
        INNER JOIN $table_users users
        ON (posts.poster_id = users.user_id)
        WHERE
            posts.c_id = $course_id AND
            posts.thread_id='".$current_thread['thread_id']."'            
        ORDER BY posts.post_id ASC";
$result = Database::query($sql);

echo "<table width=\"100%\" height=\"100%\" cellspacing=\"5\" border=\"0\">";
while ($row = Database::fetch_array($result)) {
    echo "<tr>";
    echo "<td rowspan=\"2\" class=\"forum_message_left\">";
    $username = api_htmlentities(sprintf(get_lang('LoginX'), $row['username']), ENT_QUOTES);
    if ($row['user_id'] == '0') {
        $name = $row['poster_name'];
    } else {
        $name = api_get_person_name($row['firstname'], $row['lastname']);
    }
    echo Display::tag('span', $name, ['title' => $username]).'<br />';
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
