<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Enums\ActionIcon;
use Chamilo\CoreBundle\Enums\ToolIcon;
use Chamilo\CourseBundle\Entity\CForumPost;

require_once __DIR__.'/../inc/global.inc.php';

$htmlHeadXtra[] = api_get_jquery_libraries_js(['jquery-ui', 'jquery-upload']);

// Recover Thread/Forum/Category IDs (used for AJAX and filtering)
$threadId        = isset($_REQUEST['thread']) ? (int) ($_REQUEST['thread']) : 0;
$forumId         = isset($_REQUEST['forum']) ? (int) ($_REQUEST['forum']) : 0;
$forumCategoryId = isset($_REQUEST['forumcategory']) ? (int) ($_REQUEST['forumcategory']) : 0;

$ajaxUrl = api_get_path(WEB_AJAX_PATH).'forum.ajax.php?'.api_get_cidreq();

// Delete attachment by AJAX (legacy UI behavior kept)
$htmlHeadXtra[] = '<script>
$(function () {
    $(document).on("click", ".deleteLink", function(e) {
        e.preventDefault();
        e.stopPropagation();
        var l = $(this);
        var id = l.closest("tr").attr("id");
        var filename = l.closest("tr").find(".attachFilename").html();
        if (confirm("'.get_lang('Are you sure to delete').'", filename)) {
            $.ajax({
                type: "POST",
                url: "'.$ajaxUrl.'&a=delete_file&attachId=" + id +"&thread='.$threadId.'&forum='.$forumId.'",
                dataType: "json",
                success: function(data) {
                    if (data.error == false) {
                        l.closest("tr").remove();
                        if ($(".files td").length < 1) {
                            $(".files").closest(".control-group").hide();
                        }
                    }
                }
            })
        }
    });
});
</script>';

api_protect_course_script(true);

$current_course_tool = TOOL_FORUM;

// Tabs section
$this_section = SECTION_COURSES;

// Default page title (will be overridden below with category name if available)
$nameTools = get_lang('Forums');
$courseEntity = api_get_course_entity();
$sessionId = api_get_session_id();
$sessionEntity = api_get_session_entity($sessionId);
$user = api_get_user_entity();
$courseId = $courseEntity->getId();

$hideNotifications = api_get_course_setting('hide_forum_notifications');
$hideNotifications = 1 == $hideNotifications;

if (api_is_in_gradebook()) {
    $interbreadcrumb[] = [
        'url' => Category::getUrl(),
        'name' => get_lang('Assessments'),
    ];
}

$action = isset($_GET['action']) ? $_GET['action'] : '';
$lp_id = isset($_REQUEST['lp_id']) ? (int) $_REQUEST['lp_id'] : null;

// Tool introduction
$form_count = 0;
$url = api_get_path(WEB_CODE_PATH).'forum/index.php?'.api_get_cidreq();
$formContent = handleForum($url);

Event::event_access_tool(TOOL_FORUM);

$logInfo = [
    'tool' => TOOL_FORUM,
    'action' => !empty($action) ? $action : 'list-category',
    'action_details' => $_GET['content'] ?? '',
];
Event::registerLog($logInfo);

/*
    RETRIEVING ALL THE FORUM CATEGORIES AND FORUMS
    note: we do this here just after het handling of the actions to be
    sure that we already incorporate the latest changes
*/

// Store all forum categories (entities)
$forumCategories = get_forum_categories();

// BREADCRUMBS BLOCK
$forumUrl = api_get_path(WEB_CODE_PATH).'forum/';

// Root crumb → Forums index
$interbreadcrumb[] = [
    'url'  => $forumUrl.'index.php?'.api_get_cidreq(),
    'name' => get_lang('Forums'),
];

// Resolve current category title (used as last crumb / $nameTools)
$currentCategoryTitle = null;
if (!empty($forumCategoryId) && is_array($forumCategories)) {
    foreach ($forumCategories as $fc) {
        // Entities expose iid as internal identifier
        if (method_exists($fc, 'getIid') && $fc->getIid() === $forumCategoryId) {
            $currentCategoryTitle = Security::remove_XSS($fc->getTitle());
            break;
        }
    }
}

// Override $nameTools to show the category as the final breadcrumb text.
// If not found, fallback to "Forums".
$nameTools = $currentCategoryTitle ?: get_lang('Forums');

// Get forums (only visible for students, legacy helper kept)
$setting          = api_get_setting('display_groups_forum_in_general_tool'); // not used here but parity kept
$allCourseForums  = get_forums();
$user_id          = api_get_user_id();

/* RETRIEVING ALL GROUPS AND THOSE OF THE USER */

// The groups of the user.
$groups_of_user = GroupManager::get_group_ids($courseId, $user_id);

// All groups in the course (index by iid)
if (!api_is_anonymous()) {
    $all_groups = GroupManager::get_group_list();
    if (is_array($all_groups)) {
        foreach ($all_groups as $group) {
            $all_groups[$group['iid']] = $group;
        }
    }
}

/* ACTION LINKS (left toolbar) */
$actionLeft = null;

// If called from LP, show back link
if (!empty($_GET['lp_id']) || !empty($_POST['lp_id'])) {
    $url = '../lp/lp_controller.php?'.api_get_cidreq()
        ."&gradebook=&action=add_item&type=step&lp_id='.$lp_id.'#resource_tab-5";
    $actionLeft .= Display::url(
        Display::getMdiIcon(
            ActionIcon::BACK,
            'ch-tool-icon',
            '',
            ICON_SIZE_MEDIUM,
            get_lang('Back to').' '.get_lang('Learning paths')
        ),
        $url
    );
}

if (api_is_allowed_to_edit(false, true)) {
    // Back to forum index (kept)
    $url = 'index.php?'.api_get_cidreq();
    $actionLeft .= Display::url(
        Display::getMdiIcon(
            ActionIcon::BACK,
            'ch-tool-icon',
            '',
            ICON_SIZE_MEDIUM,
            get_lang('Back to').' '.get_lang('Learning paths')
        ),
        $url
    );

    // Add forum (only if there are categories)
    if (is_array($forumCategories) && !empty($forumCategories)) {
        $actionLeft .= Display::url(
            Display::getMdiIcon(ToolIcon::FORUM, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Add a forum')),
            api_get_self().'?'.api_get_cidreq().'&action=add_forum&lp_id='.$lp_id
        );
    }

    // Add forum category
    $actionLeft .= Display::url(
        Display::getMdiIcon(ActionIcon::CREATE_FOLDER, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Add a forumCategory')),
        api_get_self().'?'.api_get_cidreq().'&action=add_category&lp_id='.$lp_id
    );
}

// Search link only if there are forums
if (!empty($allCourseForums)) {
    $actionLeft .= search_link();
}

$actions = Display::toolbarAction('toolbar-forum', [$actionLeft]);

// Create a search-bo

/* Display Forum Categories and the Forums in it */
// Display the selected category (or all, if no filter)
$listForumCategory = [];
$forumCategoryInfo = [];
if (is_array($forumCategories)) {
    foreach ($forumCategories as $forumCategory) {
        $categoryId = $forumCategory->getIid();

        // If a category filter is present, only render that category
        if (!empty($forumCategoryId) && $categoryId !== $forumCategoryId) {
            continue;
        }
        $categorySessionId = 0;
        $forumCategoryInfo['id']           = $categoryId;
        $forumCategoryInfo['title']        = $forumCategory->getTitle();
        $forumCategoryInfo['icon_session'] = '';

        // Validation when belongs to a session
        $forumCategoryInfo['description'] = $forumCategory->getCatComment();
        $forumCategoryInfo['session_display'] = null;
        if (!empty($sessionId)) {
            $forumCategoryInfo['session_display'] = ' ('.Security::remove_XSS($categorySessionId).')';
        }

        $tools = null;

        $forumCategoryInfo['url'] = 'viewforumcategory.php?'.api_get_cidreq().'&forumcategory='.$categoryId;

        $visibility = $forumCategory->isVisible($courseEntity);

        if (!empty($categoryId)) {
            if (api_is_allowed_to_edit(false, true) && !(0 == $categorySessionId && 0 != $sessionId)) {
                // Edit
                $tools .= '<a href="'.api_get_self().'?'.api_get_cidreq()
                    .'&action=edit_category&content=forumcategory&id='.$categoryId
                    .'">'.Display::getMdiIcon(ActionIcon::EDIT, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Edit'))
                    .'</a>';

                // Delete (with confirm)
                $tools .= '<a href="'.api_get_self().'?'.api_get_cidreq()
                    .'&action=delete_category&content=forumcategory&id='.$categoryId
                    ."\" onclick=\"javascript:if(!confirm('"
                    .addslashes(api_htmlentities(get_lang('Delete forum category ?'), \ENT_QUOTES))
                    ."')) return false;\">"
                    .Display::getMdiIcon(ActionIcon::DELETE, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Delete'))
                    .'</a>';

                // Visibility toggle
                $tools .= returnVisibleInvisibleIcon('forumcategory', $categoryId, $visibility);

                // Lock/Unlock
                $tools .= returnLockUnlockIcon('forumcategory', $categoryId, $forumCategory->getLocked());

                // Up/Down ordering
                $tools .= returnUpDownIcon('forumcategory', $categoryId, $forumCategories);
            }
        }

        $forumCategoryInfo['tools']  = $tools;
        $forumCategoryInfo['forums'] = [];

        // Forums inside this category
        $forumsInCategory = get_forums_in_category($categoryId, $courseId);

        if (!empty($forumsInCategory)) {
            $forumsDetailsList = [];

            foreach ($forumsInCategory as $forum) {
                $forumId = $forum->getIid();

                // Visibility rules: teachers see all; students see based on group membership
                $show_forum = false;
                if (api_is_allowed_to_edit(false, true)) {
                    $show_forum = true;
                } else {
                    if ('0' == $forum->getForumOfGroup()) {
                        $show_forum = true;
                    } else {
                        $show_forum = GroupManager::userHasAccess(
                            $user_id,
                            api_get_group_entity($forum->getForumOfGroup()),
                            GroupManager::GROUP_TOOL_FORUM
                        );
                    }
                }

                if (!$show_forum) {
                    continue;
                }

                $form_count++;
                $forumInfo                       = [];
                $forumInfo['id']                 = $forumId;
                $forumInfo['forum_of_group']     = $forum->getForumOfGroup();
                $forumInfo['title']              = $forum->getTitle();
                $forumInfo['forum_image']        = null;
                $forumInfo['icon_session']       = '';

                // Group label
                if ('0' != $forum->getForumOfGroup()) {
                    $forumOfGroup  = $forum->getForumOfGroup();
                    $groupName     = $all_groups[$forumOfGroup]['name'] ?? null;
                    $group_title   = api_substr($groupName, 0, 30);
                    $forumInfo['forum_group_title'] = $group_title;
                }

                $groupId = $forum->getForumOfGroup();
                $forumInfo['visibility'] = $forumVisibility = $forum->isVisible($courseEntity);

                // Link to the forum
                $linkForum = api_get_path(WEB_CODE_PATH).'forum/viewforum.php?'.api_get_cidreq()
                    .'&gid='.$groupId.'&forum='.$forumId;
                $forumInfo['url'] = $linkForum;

                // Time window visibility: if outside window, show plain title (no link)
                if (!empty($forum->getStartTime()) && !empty($forum->getEndTime())) {
                    $res = api_is_date_in_date_range($forum->getStartTime(), $forum->getEndTime());
                    if (!$res) {
                        $linkForum = $forum->getTitle();
                    }
                }

                $forumInfo['description'] = Security::remove_XSS($forum->getForumComment());

                // Moderation badge for teachers
                if ($forum->isModerated() && api_is_allowed_to_edit(false, true)) {
                    $waitingCount = getCountPostsWithStatus(CForumPost::STATUS_WAITING_MODERATION, $forum);
                    if (!empty($waitingCount)) {
                        $forumInfo['moderation'] = $waitingCount;
                    }
                }

                // Teacher tool icons for this forum
                $toolActions                   = null;
                $forumInfo['alert']            = null;
                $poster_id                     = null;
                $forumInfo['last_poster_id']   = $poster_id;

                if (api_is_allowed_to_edit(false, true)) {
                    // Edit forum
                    $toolActions .= '<a href="'.api_get_self().'?'.api_get_cidreq()
                        .'&action=edit_forum&content=forum&id='.$forumId.'">'
                        .Display::getMdiIcon(ActionIcon::EDIT, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Edit'))
                        .'</a>';

                    // Delete forum
                    $toolActions .= '<a href="'.api_get_self().'?'.api_get_cidreq()
                        .'&action=delete_forum&content=forum&id='.$forumId
                        ."\" onclick=\"javascript:if(!confirm('".addslashes(
                            api_htmlentities(get_lang('Delete forum ?'), \ENT_QUOTES)
                        )."')) return false;\">"
                        .Display::getMdiIcon(ActionIcon::DELETE, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Delete'))
                        .'</a>';

                    // Visibility toggle
                    $toolActions .= returnVisibleInvisibleIcon('forum', $forumId, $forumVisibility);

                    // Lock/Unlock
                    $toolActions .= returnLockUnlockIcon('forum', $forumId, $forum->getLocked());

                    // Up/Down ordering inside the category
                    $toolActions .= returnUpDownIcon('forum', $forumId, $forumsInCategory);
                }

                $forumInfo['tools'] = $toolActions;
                $forumsDetailsList[] = $forumInfo;
            }
            $forumCategoryInfo['forums'] = $forumsDetailsList;
        }

        // Category extra fields (kept)
        $extraFieldValue = new ExtraFieldValue('forum_category');
        $forumCategoryInfo['extra_fields'] = $extraFieldValue->getAllValuesByItem($categoryId);

        // Hide empty categories for students
        if (!api_is_allowed_to_edit() && empty($forumCategoryInfo['forums'])) {
            continue;
        }
        $listForumCategory[] = $forumCategoryInfo;
    }
}

$isTeacher = api_is_allowed_to_edit(false, true);
$tpl = new Template($nameTools);
$tpl->assign('actions', $actions);
$tpl->assign('categories', $listForumCategory);
$tpl->assign('form_content', $formContent);
$tpl->assign('search_filter', '');
$tpl->assign('is_allowed_to_edit', $isTeacher);

$layout = $tpl->get_template('forum/viewcategory.html.twig');

$tpl->display($layout);
