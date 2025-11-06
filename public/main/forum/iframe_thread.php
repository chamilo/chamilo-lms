<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CForum;
use Chamilo\CourseBundle\Entity\CForumThread;

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

$cid = isset($_REQUEST['cid']) ? (int) $_REQUEST['cid'] : null;
$sid = isset($_REQUEST['sid']) ? (int) $_REQUEST['sid'] : null;

// A notice for unauthorized people.
api_protect_course_script(true, false, '', $cid);

$nameTools = get_lang('Forums');

$forumId = isset($_GET['forum']) ? (int) $_GET['forum'] : 0;
$threadId = isset($_GET['thread']) ? (int) $_GET['thread'] : 0;

$repo = Container::getForumRepository();
$forumEntity = null;
if (!empty($forumId)) {
    /** @var CForum $forumEntity */
    $forumEntity = $repo->find($forumId);
}

$repoThread = Container::getForumThreadRepository();
$threadEntity = null;
if (!empty($threadId)) {
    /** @var CForumThread $threadEntity */
    $threadEntity = $repoThread->find($threadId);
}

$courseEntity = api_get_course_entity($cid);
$sessionEntity = api_get_session_entity($sid);

/* Is the user allowed here? */
// if the user is not a course administrator and the forum is hidden
// then the user is not allowed here.
if (!api_is_allowed_to_create_course() &&
    (false == $forumEntity->isVisible($courseEntity) ||
        false == $threadEntity->isVisible($courseEntity)
    )
) {
    api_not_allowed(false);
}

$table_posts = Database::get_course_table(TABLE_FORUM_POST);
$table_users = Database::get_main_table(TABLE_MAIN_USER);

$sql = "SELECT username, firstname, lastname, u.id, post_date, title as post_title, post_text
        FROM $table_posts posts
        INNER JOIN $table_users u
        ON (posts.poster_id = u.id)
        WHERE
            posts.thread_id='".$threadEntity->getIid()."'
        ORDER BY posts.iid ASC";
$result = Database::query($sql);

$template = new Template('', false, false);

$content = '<table width="100%" height="100%" cellspacing="5" border="0">';
while ($row = Database::fetch_array($result)) {
    $content .= '<tr>';
    $content .= '<td rowspan="2" class="forum_message_left">';
    $username = api_htmlentities(sprintf(get_lang('Login: %s'), $row['username']), ENT_QUOTES);
    if ('0' == $row['id']) {
        $name = $row['poster_name'];
    } else {
        $name = api_get_person_name($row['firstname'], $row['lastname']);
    }
    $content .= Display::tag('span', $name, ['title' => $username]).'<br />';
    $content .= api_convert_and_format_date($row['post_date']).'<br /><br />';

    $content .= '</td>';
    $content .= '<td class="forum_message_post_title">'.Security::remove_XSS($row['post_title']).'</td>';
    $content .= '</tr>';

    $content .= '<tr>';
    $content .= '<td class="forum_message_post_text">'.Security::remove_XSS($row['post_text'], STUDENT).'</td>';
    $content .= '</tr>';
}
$content .= '</table>' ;


$css = '<style>
/* global reset to start at the very top */html, body {
  margin: 0 !important;
  padding: 0 !important;
  height: 100% !important;
}

/* hide the topbar and sidebar only inside the iframe */
.app-topbar,
.app-sidebar,
#app .app-topbar,
#app aside.app-sidebar {
  display: none !important;
}

/* hide the breadcrumb */
.app-breadcrumb,
.p-breadcrumb,
.p-breadcrumb-list,
.p-breadcrumb-item,
.p-breadcrumb-item-link {
  display: none !important;
  height: 0 !important;
  margin: 0 !important;
  padding: 0 !important;
  overflow: hidden !important;
}

/* hide possible Chamilo logo and avatars */
img[alt*="Chamilo"],
.branding,
.forum-branding,
.header-branding,
.ch-logo,
.user-avatar,
.avatar {
  display: none !important;
}

/* remove margins/padding on containers to stick to the top */
#app,
#app .app-main,
#app .app-main > .container,
.forum-thread,
.discussion,
#content {
  margin: 0 !important;
  padding: 0 !important;
  width: auto !important;
  max-width: 100% !important;
  text-align: left !important;
}

/* titles: remove top margin */
h1, h2, h3,
.page-title,
.forum-title,
.forum-thread-title {
  margin-top: 4px !important;
  margin-bottom: 4px !important;
  padding-top: 0 !important;
}

/* force no table spacing (override cellspacing) */
table, table[width="100%"], .forum-thread table {
  border-collapse: collapse !important;
  border-spacing: 0 !important;
  margin-top: 0 !important;
  padding-top: 0 !important;
}

/* clean remaining space on specific elements */
body,
.forum-thread,
.discussion,
.forum_message_post_title,
.forum_message_left {
  padding-top: 0 !important;
  margin-top: 0 !important;
}
</style>';


$content = $css . $content;


$template->assign('content', $content);
$template->display_no_layout_template();
