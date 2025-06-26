<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Enums\ActionIcon;
use Chamilo\CoreBundle\Enums\ToolIcon;
use Chamilo\CourseBundle\Entity\CForumPost;

require_once __DIR__.'/../inc/global.inc.php';

$htmlHeadXtra[] = api_get_jquery_libraries_js(['jquery-ui', 'jquery-upload']);

// Recover Thread ID, will be used to generate delete attachment URL to do ajax
$threadId = isset($_REQUEST['thread']) ? (int) ($_REQUEST['thread']) : 0;
$forumId = isset($_REQUEST['forum']) ? (int) ($_REQUEST['forum']) : 0;
$forumCategoryId = isset($_REQUEST['forumcategory']) ? (int) ($_REQUEST['forumcategory']) : 0;

$ajaxUrl = api_get_path(WEB_AJAX_PATH).'forum.ajax.php?'.api_get_cidreq();
// The next javascript script is to delete file by ajax
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

// The section (tabs).
$this_section = SECTION_COURSES;

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

// Step 1: We store all the forum categories in an array $forum_categories.
$forumCategories = get_forum_categories();

// Step 2: We find all the forums (only the visible ones if it is a student).
// display group forum in general forum tool depending to configuration option
$setting = api_get_setting('display_groups_forum_in_general_tool');
$allCourseForums = get_forums();
$user_id = api_get_user_id();

/* RETRIEVING ALL GROUPS AND THOSE OF THE USER */

// The groups of the user.
$groups_of_user = GroupManager::get_group_ids($courseId, $user_id);

// All groups in the course (and sorting them as the
// id of the group = the key of the array).
if (!api_is_anonymous()) {
    $all_groups = GroupManager::get_group_list();
    if (is_array($all_groups)) {
        foreach ($all_groups as $group) {
            $all_groups[$group['iid']] = $group;
        }
    }
}

/* ACTION LINKS */
$actionLeft = null;
//if is called from learning path
if (!empty($_GET['lp_id']) || !empty($_POST['lp_id'])) {
    $url = '../lp/lp_controller.php?'.api_get_cidreq()
        ."&gradebook=&action=add_item&type=step&lp_id='.$lp_id.'#resource_tab-5";
    $actionLeft .= Display::url(
        Display::getMdiIcon(ActionIcon::BACK, 'ch-tool-icon', '', ICON_SIZE_MEDIUM, get_lang('Back to').' '.get_lang('Learning paths')),
        $url
    );
}

if (api_is_allowed_to_edit(false, true)) {

    $url = 'index.php?'.api_get_cidreq();
    $actionLeft .= Display::url(
        Display::getMdiIcon(ActionIcon::BACK, 'ch-tool-icon', '', ICON_SIZE_MEDIUM, get_lang('Back to').' '.get_lang('Learning paths')),
        $url
    );

    if (is_array($forumCategories) && !empty($forumCategories)) {
        $actionLeft .= Display::url(
            Display::getMdiIcon(ToolIcon::FORUM, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Add a forum')),
            api_get_self().'?'.api_get_cidreq().'&action=add_forum&lp_id='.$lp_id
        );
    }

    $actionLeft .= Display::url(
        Display::getMdiIcon(ActionIcon::CREATE_FOLDER, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Add a forumCategory')),
        api_get_self().'?'.api_get_cidreq().'&action=add_category&lp_id='.$lp_id
    );
}

if (!empty($allCourseForums)) {
    $actionLeft .= search_link();
}

$actions = Display::toolbarAction('toolbar-forum', [$actionLeft]);

// Create a search-bo

/* Display Forum Categories and the Forums in it */
// Step 3: We display the forum_categories first.
$listForumCategory = [];
$forumCategoryInfo = [];
if (is_array($forumCategories)) {
    foreach ($forumCategories as $forumCategory) {
        $categoryId = $forumCategory->getIid();

        if (!empty($forumCategoryId)) {
            if ($categoryId !== $forumCategoryId) {
                continue;
            }
        }
        $categorySessionId = 0;
        $forumCategoryInfo['id'] = $categoryId;
        $forumCategoryInfo['title'] = $forumCategory->getTitle();
        $forumCategoryInfo['icon_session'] = '';

        // Validation when belongs to a session
        $forumCategoryInfo['description'] = $forumCategory->getCatComment();
        $forumCategoryInfo['session_display'] = null;
        if (!empty($sessionId)) {
            $forumCategoryInfo['session_display'] = ' ('.Security::remove_XSS($categorySessionId).')';
        }

        $tools = null;
        $forumCategoryInfo['url'] = 'index.php?'.api_get_cidreq().'&forumcategory='.$categoryId;
        $visibility = $forumCategory->isVisible($courseEntity);

        if (!empty($categoryId)) {
            if (api_is_allowed_to_edit(false, true) &&
                !(0 == $categorySessionId && 0 != $sessionId)
            ) {
                $tools .= '<a href="'.api_get_self().'?'.api_get_cidreq()
                    .'&action=edit_category&content=forumcategory&id='.$categoryId
                    .'">'.Display::getMdiIcon(ActionIcon::EDIT, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Edit'))
                    .'</a>';

                $tools .= '<a href="'.api_get_self().'?'.api_get_cidreq()
                    .'&action=delete_category&content=forumcategory&id='.$categoryId
                    ."\" onclick=\"javascript:if(!confirm('"
                    .addslashes(api_htmlentities(
                        get_lang('Delete forum category ?'),
                        ENT_QUOTES
                    ))
                    ."')) return false;\">"
                    .Display::getMdiIcon(ActionIcon::DELETE, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Delete'))
                    .'</a>';
                $tools .= returnVisibleInvisibleIcon(
                    'forumcategory',
                    $categoryId,
                    $visibility
                );
                $tools .= returnLockUnlockIcon(
                    'forumcategory',
                    $categoryId,
                    $forumCategory->getLocked()
                );
                $tools .= returnUpDownIcon(
                    'forumcategory',
                    $categoryId,
                    $forumCategories
                );
            }
        }

        $forumCategoryInfo['tools'] = $tools;
        $forumCategoryInfo['forums'] = [];
        // The forums in this category.
        $forumInfo = [];
        $forumsInCategory = get_forums_in_category($categoryId, $courseId);

        if (!empty($forumsInCategory)) {
            $forumsDetailsList = [];
            // We display all the forums in this category.
            foreach ($forumsInCategory as $forum) {
                $forumId = $forum->getIid();

                // Note: This can be speed up if we transform the $allCourseForums
                // to an array that uses the forum_category as the key.
                if (true) {
                    //if (isset($forum['forum_category']) && $forum['forum_category'] == $forumCategory['cat_id']) {
                    $show_forum = false;
                    // SHOULD WE SHOW THIS PARTICULAR FORUM
                    // you are teacher => show forum
                    if (api_is_allowed_to_edit(false, true)) {
                        $show_forum = true;
                    } else {
                        // you are not a teacher
                        // it is not a group forum => show forum
                        // (invisible forums are already left out see get_forums function)
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

                    if ($show_forum) {
                        $form_count++;
                        $forumInfo['id'] = $forumId;
                        $forumInfo['forum_of_group'] = $forum->getForumOfGroup();
                        $forumInfo['title'] = $forum->getTitle();
                        $forumInfo['forum_image'] = null;
                        $forumInfo['icon_session'] = '';
                        if ('0' != $forum->getForumOfGroup()) {
                            $forumOfGroup = $forum->getForumOfGroup();
                            $my_all_groups_forum_name = $all_groups[$forumOfGroup]['name'] ?? null;
                            $my_all_groups_forum_id = $all_groups[$forumOfGroup]['id'] ?? null;
                            $group_title = api_substr($my_all_groups_forum_name, 0, 30);
                            $forumInfo['forum_group_title'] = $group_title;
                        }

                        $groupId = $forum->getForumOfGroup();
                        $forumInfo['visibility'] = $forumVisibility = $forum->isVisible($courseEntity);

                        $linkForum = api_get_path(WEB_CODE_PATH).'forum/viewforum.php?'.api_get_cidreq()
                            .'&gid='.$groupId.'&forum='.$forumId;
                        $forumInfo['url'] = $linkForum;

                        if (!empty($forum->getStartTime()) && !empty($forum->getEndTime())) {
                            $res = api_is_date_in_date_range($forum->getStartTime(), $forum->getEndTime());
                            if (!$res) {
                                $linkForum = $forum->getTitle();
                            }
                        }

                        $forumInfo['description'] = Security::remove_XSS($forum->getForumComment());
                        if ($forum->isModerated() && api_is_allowed_to_edit(false, true)) {
                            $waitingCount = getCountPostsWithStatus(
                                CForumPost::STATUS_WAITING_MODERATION,
                                $forum
                            );
                            if (!empty($waitingCount)) {
                                $forumInfo['moderation'] = $waitingCount;
                            }
                        }

                        $toolActions = null;
                        $forumInfo['alert'] = null;
                        $poster_id = null;
                        $forumInfo['last_poster_id'] = $poster_id;
                        if (api_is_allowed_to_edit(false, true)) {
                            $toolActions .= '<a href="'.api_get_self().'?'.api_get_cidreq()
                                .'&action=edit_forum&content=forum&id='.$forumId.'">'
                                .Display::getMdiIcon(ActionIcon::EDIT, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Edit'))
                                .'</a>';
                            $toolActions .= '<a href="'.api_get_self().'?'.api_get_cidreq()
                                .'&action=delete_forum&content=forum&id='.$forumId
                                ."\" onclick=\"javascript:if(!confirm('".addslashes(
                                    api_htmlentities(get_lang('Delete forum ?'), ENT_QUOTES)
                                )
                                ."')) return false;\">"
                                .Display::getMdiIcon(ActionIcon::DELETE, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Delete'))
                                .'</a>';

                            $toolActions .= returnVisibleInvisibleIcon(
                                'forum',
                                $forumId,
                                $forumVisibility
                            );

                            $toolActions .= returnLockUnlockIcon(
                                'forum',
                                $forumId,
                                $forum->getLocked()
                            );

                            $toolActions .= returnUpDownIcon(
                                'forum',
                                $forumId,
                                $forumsInCategory
                            );
                        }
                        $forumInfo['tools'] = $toolActions;
                        $forumsDetailsList[] = $forumInfo;
                    }
                }
            }
            $forumCategoryInfo['forums'] = $forumsDetailsList;
        }

        // It set the languages by category
        $extraFieldValue = new ExtraFieldValue('forum_category');
        $forumCategoryInfo['extra_fields'] = $extraFieldValue->getAllValuesByItem($categoryId);
        // Don't show empty categories (for students)
        if (!api_is_allowed_to_edit()) {
            if (empty($forumCategoryInfo['forums'])) {
                continue;
            }
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
