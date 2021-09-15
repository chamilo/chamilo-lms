<?php

/* For licensing terms, see /license.txt */

use Chamilo\CourseBundle\Entity\CForumPost;
use ChamiloSession as Session;

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
 * @copyright Patrick Cool
 */
require_once __DIR__.'/../inc/global.inc.php';

$htmlHeadXtra[] = api_get_jquery_libraries_js(['jquery-ui', 'jquery-upload']);
$htmlHeadXtra[] = '<script>

function check_unzip() {
    if (document.upload.unzip.checked){
        document.upload.if_exists[0].disabled=true;
        document.upload.if_exists[1].checked=true;
        document.upload.if_exists[2].disabled=true;
    } else {
        document.upload.if_exists[0].checked=true;
        document.upload.if_exists[0].disabled=false;
        document.upload.if_exists[2].disabled=false;
    }
}
function setFocus() {
    $("#title_file").focus();
}
</script>';
// The next javascript script is to manage ajax upload file
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
$htmlHeadXtra[] = '<script>
$(function() {
    $(\'.hide-me\').slideUp();
});

function hidecontent(content){
    $(content).slideToggle(\'normal\');
}
</script>';

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

$search_forum = isset($_GET['search']) ? Security::remove_XSS($_GET['search']) : '';
$action = isset($_GET['action']) ? $_GET['action'] : '';
$lp_id = isset($_REQUEST['lp_id']) ? (int) $_REQUEST['lp_id'] : null;

if ('add' === $action) {
    switch ($_GET['content']) {
        case 'forum':
            $interbreadcrumb[] = [
                'url' => 'index.php?search='.$search_forum.'&'.api_get_cidreq(),
                'name' => get_lang('Forum'),
            ];
            $interbreadcrumb[] = [
                'url' => '#',
                'name' => get_lang('Add a forum'),
            ];

            break;
        case 'forumcategory':
            $interbreadcrumb[] = [
                'url' => 'index.php?search='.$search_forum.'&'.api_get_cidreq(),
                'name' => get_lang('Forum'),
            ];
            $interbreadcrumb[] = [
                'url' => '#',
                'name' => get_lang('Add a forumCategory'),
            ];

            break;
        default:
            break;
    }
} else {
    $interbreadcrumb[] = [
        'url' => '#',
        'name' => get_lang('Forum Categories'),
    ];
}

// Tool introduction
$introduction = Display::return_introduction_section(TOOL_FORUM);
$form_count = 0;
$url = api_get_path(WEB_CODE_PATH).'forum/index.php?'.api_get_cidreq();
$formContent = handleForum($url);

//get_whats_new();
$whatsnew_post_info = Session::read('whatsnew_post_info');
Event::event_access_tool(TOOL_FORUM);

$logInfo = [
    'tool' => TOOL_FORUM,
    'action' => !empty($action) ? $action : 'list-category',
    'action_details' => isset($_GET['content']) ? $_GET['content'] : '',
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
$allCourseForums = get_forums('', '', 'true' === $setting);
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
        Display::return_icon(
            'back.png',
            get_lang('Back to').' '.get_lang('Learning paths'),
            null,
            ICON_SIZE_MEDIUM
        ),
        $url
    );
}

if (api_is_allowed_to_edit(false, true)) {
    if (is_array($forumCategories) && !empty($forumCategories)) {
        $actionLeft .= Display::url(
            Display::return_icon(
                'new_forum.png',
                get_lang('Add a forum'),
                null,
                ICON_SIZE_MEDIUM
            ),
            api_get_self().'?'.api_get_cidreq().'&action=add_forum&lp_id='.$lp_id
        );
    }

    $actionLeft .= Display::url(
        Display::return_icon(
            'new_folder.png',
            get_lang('Add a forumCategory'),
            null,
            ICON_SIZE_MEDIUM
        ),
        api_get_self().'?'.api_get_cidreq().'&action=add_category&lp_id='.$lp_id
    );
}

if (!empty($allCourseForums)) {
    $actionLeft .= search_link();
}

$actions = Display::toolbarAction('toolbar-forum', [$actionLeft]);

$languages = api_get_language_list_for_flag();
$defaultUserLanguage = 'en';
if (null !== $user) {
    $defaultUserLanguage = $user->getLocale();
}

$extraFieldValues = new ExtraFieldValue('user');
$value = $extraFieldValues->get_values_by_handler_and_field_variable(api_get_user_id(), 'langue_cible');

if ($value && isset($value['value']) && !empty($value['value'])) {
    $defaultUserLanguage = ucfirst($value['value']);
}

// Create a search-box
$searchFilter = '';
$translate = api_get_configuration_value('translate_html');
if ($translate) {
    $form = new FormValidator('search_simple', 'get', api_get_self().'?'.api_get_cidreq(), null, null, 'inline');
    $form->addHidden('cid', api_get_course_int_id());
    $form->addHidden('sid', api_get_session_id());

    $extraField = new ExtraField('forum_category');
    $returnParams = $extraField->addElements(
        $form,
        null,
        [], //exclude
        false, // filter
        false, // tag as select
        ['language'], //show only fields
        [], // order fields
        [], // extra data
        false,
        false,
        [],
        [],
        true //$addEmptyOptionSelects = false,
    );
    $form->setDefaults(['extra_language' => $defaultUserLanguage]);

    $searchFilter = $form->returnForm();
}

// Fixes error if there forums with no category.
$forumsInNoCategory = get_forums_in_category(0);
if (!empty($forumsInNoCategory)) {
    $forumCategories = array_merge(
        $forumCategories,
        [
            [
                'cat_id' => 0,
                'session_id' => 0,
                'visibility' => 1,
                'cat_comment' => null,
            ],
        ]
    );
}

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
        //$categorySessionId = $forumCategory->getSessionId();
        $categorySessionId = 0;
        $forumCategoryInfo['id'] = $categoryId;
        $forumCategoryInfo['title'] = $forumCategory->getCatTitle();
        /*
        if (empty($forumCategory['cat_title'])) {
            $forumCategoryInfo['title'] = get_lang('Without category');
        } else {
        }*/
        //$forumCategoryInfo['extra_fields'] = $forumCategory['extra_fields'];
        //$forumCategoryInfo['icon_session'] = api_get_session_image($categorySessionId, $_user['status']);
        $forumCategoryInfo['icon_session'] = '';

        // Validation when belongs to a session
        $forumCategoryInfo['description'] = $forumCategory->getCatComment();
        $forumCategoryInfo['session_display'] = null;
        if (!empty($sessionId)) {
            $forumCategoryInfo['session_display'] = ' ('.Security::remove_XSS($categorySessionId).')';
        }

        $tools = null;
        $forumCategoryInfo['url'] = 'index.php?'.api_get_cidreq().'&forumcategory='.$categoryId;
        $visibility = $forumCategory->isVisible($courseEntity, $sessionEntity);

        if (!empty($categoryId)) {
            if (api_is_allowed_to_edit(false, true) &&
                !(0 == $categorySessionId && 0 != $sessionId)
            ) {
                $tools .= '<a href="'.api_get_self().'?'.api_get_cidreq()
                    .'&action=edit_category&content=forumcategory&id='.$categoryId
                    .'">'.Display::return_icon(
                        'edit.png',
                        get_lang('Edit'),
                        [],
                        ICON_SIZE_SMALL
                    )
                    .'</a>';

                $tools .= '<a href="'.api_get_self().'?'.api_get_cidreq()
                    .'&action=delete_category&content=forumcategory&id='.$categoryId
                    ."\" onclick=\"javascript:if(!confirm('"
                    .addslashes(api_htmlentities(
                        get_lang('Delete forum category ?'),
                        ENT_QUOTES
                    ))
                    ."')) return false;\">"
                    .Display::return_icon(
                        'delete.png',
                        get_lang('Delete'),
                        [],
                        ICON_SIZE_SMALL
                    )
                    .'</a>';
                $tools .= return_visible_invisible_icon(
                    'forumcategory',
                    $categoryId,
                    $visibility
                );
                $tools .= return_lock_unlock_icon(
                    'forumcategory',
                    $categoryId,
                    $forumCategory->getLocked()
                );
                $tools .= return_up_down_icon(
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

                // Here we clean the whatnew_post_info array a little bit because to display the icon we
                // test if $whatsnew_post_info[$forum['forum_id']] is empty or not.
                /*if ($forum) {
                    if (!empty($whatsnew_post_info)) {
                        if (isset($whatsnew_post_info[$forum['forum_id']]) &&
                            is_array($whatsnew_post_info[$forum['forum_id']])
                        ) {
                            foreach ($whatsnew_post_info[$forum['forum_id']] as $key_thread_id => $new_post_array) {
                                if (empty($whatsnew_post_info[$forum['forum_id']][$key_thread_id])) {
                                    unset($whatsnew_post_info[$forum['forum_id']][$key_thread_id]);
                                    unset($_SESSION['whatsnew_post_info'][$forum['forum_id']][$key_thread_id]);
                                }
                            }
                        }
                    }
                }*/

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
                        /*$mywhatsnew_post_info = isset($whatsnew_post_info[$forum['forum_id']])
                            ? $whatsnew_post_info[$forum['forum_id']]
                            : null;*/
                        $forumInfo['id'] = $forumId;
                        $forumInfo['forum_of_group'] = $forum->getForumOfGroup();
                        $forumInfo['title'] = $forum->getForumTitle();
                        $forumInfo['forum_image'] = null;
                        // Showing the image
                        /*if (!empty($forum['forum_image'])) {
                            $image_path = api_get_path(WEB_COURSE_PATH).api_get_course_path().'/upload/forum/images/'
                                .$forum['forum_image'];
                            $image_size = api_getimagesize($image_path);
                            $img_attributes = '';
                            if (!empty($image_size)) {
                                $forumInfo['forum_image'] = $image_path;
                            }
                        }*/
                        // Validation when belongs to a session
                        /*$forumInfo['icon_session'] = api_get_session_image(
                            $forum->getSessionId(),
                            $user
                        );*/
                        $forumInfo['icon_session'] = '';
                        if ('0' != $forum->getForumOfGroup()) {
                            $forumOfGroup = $forum->getForumOfGroup();
                            $my_all_groups_forum_name = $all_groups[$forumOfGroup]['name'] ?? null;
                            $my_all_groups_forum_id = $all_groups[$forumOfGroup]['id'] ?? null;
                            $group_title = api_substr($my_all_groups_forum_name, 0, 30);
                            $forumInfo['forum_group_title'] = $group_title;
                        }

                        $groupId = $forum->getForumOfGroup();
                        $forumInfo['visibility'] = $forumVisibility = $forum->isVisible($courseEntity, $sessionEntity);
                        /*$forumInfo['number_threads'] = isset($forum['number_of_threads'])
                            ? (int) $forum['number_of_threads']
                            : 0;*/

                        $linkForum = api_get_path(WEB_CODE_PATH).'forum/viewforum.php?'.api_get_cidreq()
                            .'&gid='.$groupId.'&forum='.$forumId;
                        $forumInfo['url'] = $linkForum;

                        if (!empty($forum->getStartTime()) && !empty($forum->getEndTime())) {
                            $res = api_is_date_in_date_range($forum->getStartTime(), $forum->getEndTime());
                            if (!$res) {
                                $linkForum = $forum->getForumTitle();
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
                        // The number of topics and posts.
                        if (false == $hideNotifications) {
                            // The number of topics and posts.
                            /*
                            if ($forum->getForumOfGroup() !== '0') {
                                if (is_array($mywhatsnew_post_info) && !empty($mywhatsnew_post_info)) {
                                    $forumInfo['alert'] = ' '.
                                Display::return_icon(
                                    'alert.png',
                                    get_lang('Forum'),
                                    null,
                                    ICON_SIZE_SMALL
                                );
                                }
                            } else {
                                if (is_array($mywhatsnew_post_info) && !empty($mywhatsnew_post_info)) {
                                    $forumInfo['alert'] = ' '.Display::return_icon(
                                    'alert.png',
                                    get_lang('Forum'),
                                    null,
                                    ICON_SIZE_SMALL
                                );
                                }
                            }*/
                        }
                        $poster_id = null;
                        // The last post in the forum.
                        /*if (isset($forum['last_poster_name']) && $forum['last_poster_name'] != '') {
                            $name = $forum['last_poster_name'];
                            $poster_id = 0;
                            $username = "";
                        } else {
                            if (isset($forum['last_poster_firstname'])) {
                                $name = api_get_person_name(
                                    $forum['last_poster_firstname'],
                                    $forum['last_poster_lastname']
                                );
                                $poster_id = $forum['last_poster_id'];
                                $userinfo = api_get_user_info($poster_id);
                                $username = sprintf(
                                    get_lang('Login: %s'),
                                    $userinfo['username']
                                );
                            }
                        }*/
                        $forumInfo['last_poster_id'] = $poster_id;
                        /*if (!empty($forum['last_poster_id'])) {
                            $forumInfo['last_poster_date'] = api_convert_and_format_date($forum['last_post_date']);
                            $forumInfo['last_poster_user'] = display_user_link($poster_id, $name, null, $username);
                            $forumInfo['last_post_title'] = Security::remove_XSS(cut($forum['last_post_title'], 140));
                            $forumInfo['last_post_text'] = Security::remove_XSS(cut($forum['last_post_text'], 140));
                        }*/

                        /*if (api_is_allowed_to_edit(false, true)
                            && !(0 == $forum->getSessionId() && 0 != $sessionId)
                        ) {*/
                        if (api_is_allowed_to_edit(false, true)) {
                            $toolActions .= '<a href="'.api_get_self().'?'.api_get_cidreq()
                                .'&action=edit_forum&content=forum&id='.$forumId.'">'
                                .Display::return_icon('edit.png', get_lang('Edit'), [], ICON_SIZE_SMALL)
                                .'</a>';
                            $toolActions .= '<a href="'.api_get_self().'?'.api_get_cidreq()
                                .'&action=delete_forum&content=forum&id='.$forumId
                                ."\" onclick=\"javascript:if(!confirm('".addslashes(
                                    api_htmlentities(get_lang('Delete forum ?'), ENT_QUOTES)
                                )
                                ."')) return false;\">"
                                .Display::return_icon('delete.png', get_lang('Delete'), [], ICON_SIZE_SMALL)
                                .'</a>';

                            $toolActions .= return_visible_invisible_icon(
                                'forum',
                                $forumId,
                                $forumVisibility
                            );

                            $toolActions .= return_lock_unlock_icon(
                                'forum',
                                $forumId,
                                $forum->getLocked()
                            );

                            $toolActions .= return_up_down_icon(
                                'forum',
                                $forumId,
                                $forumsInCategory
                            );
                        }

                        /*$iconnotify = 'notification_mail_na.png';
                        $session_forum_notification = isset($_SESSION['forum_notification']['forum'])
                            ? $_SESSION['forum_notification']['forum']
                            : false;

                        if (is_array($session_forum_notification)) {
                            if (in_array($forum['forum_id'], $session_forum_notification)) {
                                $iconnotify = 'notification_mail.png';
                            }
                        }

                        if ($hideNotifications == false && !api_is_anonymous() && api_is_allowed_to_session_edit(false, true)) {
                            $toolActions .= '<a href="'.api_get_self().'?'.api_get_cidreq()
                                .'&action=notify&content=forum&id='.$forum['forum_id'].'">'
                                .Display::return_icon($iconnotify, get_lang('Notify me'), null, ICON_SIZE_SMALL)
                                .'</a>';
                        };*/
                        $forumInfo['tools'] = $toolActions;
                        $forumsDetailsList[] = $forumInfo;
                    }
                }
            }
            $forumCategoryInfo['forums'] = $forumsDetailsList;
        }

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
$tpl->assign('introduction', $introduction);
$tpl->assign('actions', $actions);
$tpl->assign('categories', $listForumCategory);
$tpl->assign('form_content', $formContent);
$tpl->assign('search_filter', $searchFilter);
$tpl->assign('default_user_language', $defaultUserLanguage);
$tpl->assign('languages', $languages);
$tpl->assign('is_allowed_to_edit', $isTeacher);
$extraFieldValue = new ExtraFieldValue('course');
$value = $extraFieldValue->get_values_by_handler_and_field_variable(api_get_course_int_id(), 'global_forum');
if ($value && isset($value['value']) && 1 == $value['value']) {
    $layout = $tpl->get_template('forum/global_list.tpl');
} else {
    $layout = $tpl->get_template('forum/list.tpl');
}
$tpl->display($layout);
