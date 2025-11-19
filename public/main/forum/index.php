<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Enums\ActionIcon;
use Chamilo\CourseBundle\Entity\CForum;
use Chamilo\CourseBundle\Entity\CForumPost;

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

// Consistent icon/button sizing (visual alignment in toolbars)
$htmlHeadXtra[] = '<style>
.ch-tool-icon, .ch-tool-icon-disabled {
  width: 36px; height: 36px;
  display: inline-flex; align-items: center; justify-content: center;
}
.toolbar-forum a { display: inline-flex; align-items: center; justify-content: center; }
</style>';

// Generic helpers for legacy upload form behavior
$htmlHeadXtra[] = '<script>
/* Keep legacy behavior, but be explicit in intent */
function check_unzip() {
  // Toggle options when "unzip" is checked
  if (document.upload && document.upload.unzip && document.upload.unzip.checked) {
    document.upload.if_exists[0].disabled = true;
    document.upload.if_exists[1].checked  = true;
    document.upload.if_exists[2].disabled = true;
  } else if (document.upload && document.upload.if_exists) {
    document.upload.if_exists[0].checked  = true;
    document.upload.if_exists[0].disabled = false;
    document.upload.if_exists[2].disabled = false;
  }
}

function setFocus() {
  // Focus the legacy title field if present
  if (window.jQuery) {
    $("#title_file").trigger("focus");
  }
}
</script>';

// Recover Thread/Forum/Category IDs to compose AJAX URLs
$threadId = isset($_REQUEST['thread']) ? (int) ($_REQUEST['thread']) : 0;
$forumId = isset($_REQUEST['forum']) ? (int) ($_REQUEST['forum']) : 0;
$forumCategoryId = isset($_REQUEST['forumcategory']) ? (int) ($_REQUEST['forumcategory']) : 0;

$ajaxUrl = api_get_path(WEB_AJAX_PATH).'forum.ajax.php?'.api_get_cidreq();

// AJAX: delete attachment row with proper confirm message
$htmlHeadXtra[] = '<script>
/**
 * Handle attachment delete by AJAX with a clear confirm message.
 * Uses progressive enhancement and defensive checks.
 */
jQuery(function ($) {
  $(document).on("click", ".deleteLink", function (e) {
    e.preventDefault();
    e.stopPropagation();

    var $link = $(this);
    var $row = $link.closest("tr");
    var attachId = $row.attr("id");
    var filename = $.trim($row.find(".attachFilename").text()) || "this file";

    var msg = "Are you sure you want to delete: \\"" + filename + "\\"?";
    if (!window.confirm(msg)) {
      return;
    }

    $.ajax({
      type: "POST",
      url: "'.$ajaxUrl.'&a=delete_file&attachId=" + encodeURIComponent(attachId) + "&thread='.$threadId.'&forum='.$forumId.'",
      dataType: "json"
    }).done(function (data) {
      if (data && data.error === false) {
        $row.remove();
        if ($(".files td").length < 1) {
          $(".files").closest(".control-group").hide();
        }
      } else {
        console.warn("[forum] Delete failed or returned error payload.", data);
        alert("Delete failed. Please try again.");
      }
    }).fail(function (xhr) {
      console.error("[forum] Delete request failed", xhr);
      alert("Network error while deleting. Please try again.");
    });
  });
});
</script>';

api_protect_course_script(true);

$current_course_tool = TOOL_FORUM;

// Minor UI nicety: collapsible areas helper
$htmlHeadXtra[] = '<script>
jQuery(function ($) {
  $(".hide-me").slideUp();
});
function hidecontent(selector) {
  jQuery(selector).slideToggle("normal");
}
</script>';

// Section (tabs)
$this_section = SECTION_COURSES;

$nameTools = get_lang('Forums');
$courseEntity  = api_get_course_entity();
$sessionId     = api_get_session_id();
$sessionEntity = api_get_session_entity($sessionId);
$user          = api_get_user_entity();
$courseId      = $courseEntity->getId();

$hideNotifications = 1 == api_get_course_setting('hide_forum_notifications');

if (api_is_in_gradebook()) {
    $interbreadcrumb[] = [
        'url'  => Category::getUrl(),
        'name' => get_lang('Assessments'),
    ];
}

$search_forum = isset($_GET['search']) ? Security::remove_XSS($_GET['search']) : '';
$action       = $_GET['action'] ?? '';
$lp_id        = isset($_REQUEST['lp_id']) ? (int) $_REQUEST['lp_id'] : null;

$forumIndexUrl = 'index.php?'.api_get_cidreq();
$content       = $_GET['content'] ?? '';

// Ensure $interbreadcrumb exists (defensive)
$interbreadcrumb = $interbreadcrumb ?? [];

// Sub-actions: show "Forums" as root crumb and set a specific page title
if (in_array($action, ['add', 'add_forum', 'add_category', 'edit_forum', 'edit_category'], true)) {
    // Always point back to forums index
    $interbreadcrumb[] = [
        'url'  => $forumIndexUrl,
        'name' => get_lang('Forums'),
    ];

    // Set a clear, action-specific page title
    switch (true) {
        case $action === 'add' && $content === 'forum':
        case $action === 'add_forum':
            $nameTools = get_lang('Add a forum');
            break;

        case $action === 'add' && $content === 'forumcategory':
        case $action === 'add_category':
            $nameTools = get_lang('Add forum category');
            break;

        case $action === 'edit_forum':
            $nameTools = get_lang('Edit forum');
            break;

        case $action === 'edit_category':
            $nameTools = get_lang('Edit forum category');
            break;

        default:
            // no-op, keep $nameTools as is if nothing matches
            break;
    }
}

// Tool introduction
$introduction = Display::return_introduction_section(TOOL_FORUM);

// Handle add/edit actions and capture any form content to render above list
$url         = api_get_path(WEB_CODE_PATH).'forum/index.php?'.api_get_cidreq();
$formContent = handleForum($url);

Event::event_access_tool(TOOL_FORUM);

$logInfo = [
    'tool' => TOOL_FORUM,
    'action' => !empty($action) ? $action : 'list-category',
    'action_details' => $_GET['content'] ?? '',
];
Event::registerLog($logInfo);

// -----------------------------------------------------------------------------
// Retrieve categories & forums
// -----------------------------------------------------------------------------

// All forum categories
$forumCategories = get_forum_categories();

// Visible forums for current course/session (students see only visible ones)
$setting          = api_get_setting('display_groups_forum_in_general_tool'); // kept for parity
$allCourseForums  = getVisibleForums($courseId, $sessionId);
$user_id          = api_get_user_id();

// User groups (kept, may be used by deeper logic)
$groups_of_user = GroupManager::get_group_ids($courseId, $user_id);

// All groups mapped by iid for quick lookup (if not anonymous)
if (!api_is_anonymous()) {
    $all_groups = GroupManager::get_group_list();
    if (is_array($all_groups)) {
        foreach ($all_groups as $group) {
            $all_groups[$group['iid']] = $group;
        }
    }
}

// -----------------------------------------------------------------------------
// Toolbar actions (left side)
// -----------------------------------------------------------------------------
$actionLeft = null;

// If invoked from LP, show back to LP link (fixed concatenation)
if (!empty($lp_id)) {
    $backUrl = '../lp/lp_controller.php?'.api_get_cidreq()
        .'&gradebook=&action=add_item&type=step&lp_id='.$lp_id.'#resource_tab-5';

    $actionLeft .= Display::url(
        Display::getMdiIcon(
            ActionIcon::BACK,
            'ch-tool-icon',
            null,
            ICON_SIZE_MEDIUM,
            get_lang('Back to').' '.get_lang('Learning paths')
        ),
        $backUrl
    );
}

// Teacher actions
if (api_is_allowed_to_edit(false, true)) {
    if (is_array($forumCategories) && !empty($forumCategories)) {
        $actionLeft .= Display::url(
            Display::getMdiIcon('comment-quote', 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Add a forum')),
            api_get_self().'?'.api_get_cidreq().'&action=add_forum&lp_id='.$lp_id
        );
    }

    $actionLeft .= Display::url(
        Display::getMdiIcon('folder-multiple-plus', 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Add a forumCategory')),
        api_get_self().'?'.api_get_cidreq().'&action=add_category&lp_id='.$lp_id
    );
}

// Search icon/link only if there are forums
if (!empty($allCourseForums)) {
    $actionLeft .= search_link();
}

$actions = Display::toolbarAction('toolbar-forum', [$actionLeft]);

// -----------------------------------------------------------------------------
// Language / translation filter (select2) - kept behavior
// -----------------------------------------------------------------------------
$languages = api_get_language_list_for_flag();
$defaultUserLanguage = 'english';
if (null !== $user) {
    $langInfo = api_get_language_from_iso($user->getLocale());
    $defaultUserLanguage = $langInfo->getEnglishName();
}

$extraFieldValues = new ExtraFieldValue('user');
$value = $extraFieldValues->get_values_by_handler_and_field_variable(api_get_user_id(), 'langue_cible');

if ($value && isset($value['value']) && !empty($value['value'])) {
    $defaultUserLanguage = ucfirst($value['value']);
}

// Create a search-box
$searchFilter = '';
$translate = 'true' === api_get_setting('editor.translate_html');
if ($translate) {
    $htmlHeadXtra[] = api_get_css_asset('select2/css/select2.min.css');
    $htmlHeadXtra[] = api_get_asset('select2/js/select2.min.js');
    $htmlHeadXtra[] = '<script>
jQuery(function ($) {
  $("#extra_language").select2({
    placeholder: "'.get_lang('Please select a language').'",
    allowClear: true
  });

  var urlParams = new URLSearchParams(window.location.search);
  var reloaded = urlParams.get("reloaded");

  $("#extra_language").on("change", function () {
    var selected = $(this).val() || [];
    // If cleared and not yet reloaded, refresh once to reset state
    if (selected.length === 0 && !reloaded) {
      urlParams.set("reloaded", "true");
      window.location.href = window.location.pathname + "?" + urlParams.toString();
    }
  });

  if (reloaded) {
    urlParams.delete("reloaded");
    window.history.replaceState(null, null, window.location.pathname + "?" + urlParams.toString());
  }
});
</script>';

    $form = new FormValidator('search_simple', 'get', api_get_self().'?'.api_get_cidreq(), null, null);
    $form->addHidden('cid', api_get_course_int_id());
    $form->addHidden('sid', api_get_session_id());

    $extraField = new ExtraField('forum_category');
    $extraField->addElements(
        $form,
        null,
        [],            // exclude
        false,         // filter
        false,         // tag as select
        ['language'],  // show only fields
        [],            // order fields
        [],            // extra data
        false,
        false,
        [],
        [],
        true           // $addEmptyOptionSelects = false
    );
    $form->setDefault('extra_language', $defaultUserLanguage);

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

// -----------------------------------------------------------------------------
// Build view-model for template
// -----------------------------------------------------------------------------
$listForumCategory = [];
$forumCategoryInfo = [];

if (is_array($forumCategories)) {
    foreach ($forumCategories as $forumCategory) {
        // In Doctrine entity form, use getters; in array fallback, keep BC
        $categoryId = is_object($forumCategory) ? $forumCategory->getIid() : (int) ($forumCategory['cat_id'] ?? 0);

        if (!empty($forumCategoryId) && $categoryId !== $forumCategoryId) {
            continue;
        }

        $forumCategoryInfo = [
            'id'            => $categoryId,
            'title'         => is_object($forumCategory) ? $forumCategory->getTitle() : ($forumCategory['cat_title'] ?? get_lang('Without category')),
            'icon_session'  => is_object($forumCategory)
                ? api_get_session_image($forumCategory->getFirstResourceLink()->getSession()?->getId(), $user)
                : '',
            'description'   => is_object($forumCategory) ? $forumCategory->getCatComment() : ($forumCategory['cat_comment'] ?? ''),
            'session_display' => !empty($sessionId) ? ' ('.Security::remove_XSS('0').')' : null,
            'url'           => 'viewforumcategory.php?'.api_get_cidreq().'&forumcategory='.$categoryId,
            'tools'         => null,
            'forums'        => [],
        ];

        $courseVisibility = is_object($forumCategory)
            ? $forumCategory->isVisible($courseEntity)
            : (bool) ($forumCategory['visibility'] ?? true);

        // Tools for teachers
        if (!empty($categoryId)) {
            $categorySessionId = 0;

            if (api_is_allowed_to_edit(false, true) && !(0 == $categorySessionId && 0 != $sessionId)) {
                $forumCategoryInfo['tools']  = '<a href="'.api_get_self().'?'.api_get_cidreq()
                    .'&action=edit_category&content=forumcategory&id='.$categoryId.'">'
                    .Display::getMdiIcon(ActionIcon::EDIT, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Edit'))
                    .'</a>';

                $forumCategoryInfo['tools'] .= '<a href="'.api_get_self().'?'.api_get_cidreq()
                    .'&action=delete_category&content=forumcategory&id='.$categoryId
                    ."\" onclick=\"javascript:if(!confirm('"
                    .addslashes(api_htmlentities(get_lang('Delete forum category ?'), \ENT_QUOTES))
                    ."')) return false;\">"
                    .Display::getMdiIcon(ActionIcon::DELETE, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Delete'))
                    .'</a>';

                $forumCategoryInfo['tools'] .= returnVisibleInvisibleIcon('forumcategory', $categoryId, $courseVisibility);
                $forumCategoryInfo['tools'] .= returnLockUnlockIcon('forumcategory', $categoryId, is_object($forumCategory) ? $forumCategory->getLocked() : 0);
                $forumCategoryInfo['tools'] .= returnUpDownIcon('forumcategory', $categoryId, $forumCategories);
            }
        }

        // Forums inside this category
        $forumsInCategory = getVisibleForumsInCategory($categoryId, $courseId, $sessionId);
        $forumsDetailsList = [];

        if (!empty($forumsInCategory)) {
            /** @var CForum $forum */
            foreach ($forumsInCategory as $forum) {
                $forumId = $forum->getIid();

                // Determine visibility to current user
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

                $forumInfo = [
                    'id'              => $forumId,
                    'forum_of_group'  => $forum->getForumOfGroup(),
                    'title'           => $forum->getTitle(),
                    'forum_image'     => null,
                    'icon_session'    => api_get_session_image($forum->getFirstResourceLink()->getSession()?->getId(), $user),
                    'forum_group_title' => null,
                    'visibility'      => $forum->isVisible($courseEntity),
                    'number_threads'  => (int) get_threads($forumId, api_get_course_int_id(), api_get_session_id(), true),
                    'url'             => api_get_path(WEB_CODE_PATH).'forum/viewforum.php?'.api_get_cidreq().'&gid='.$forum->getForumOfGroup().'&forum='.$forumId,
                    'description'     => Security::remove_XSS($forum->getForumComment()),
                    'moderation'      => null,
                    'alert'           => null,
                    'tools'           => null,
                    'last_poster_id'  => null,
                ];

                // Group title label if any
                if ('0' != $forum->getForumOfGroup()) {
                    $forumOfGroup = $forum->getForumOfGroup();
                    $groupName = $all_groups[$forumOfGroup]['name'] ?? null;
                    $forumInfo['forum_group_title'] = api_substr($groupName, 0, 30);
                }

                // Time window visibility (keep behavior of replacing link with plain title when out of date-range)
                if (!empty($forum->getStartTime()) && !empty($forum->getEndTime())) {
                    $inWindow = api_is_date_in_date_range($forum->getStartTime(), $forum->getEndTime());
                    if (!$inWindow) {
                        $forumInfo['url'] = $forum->getTitle();
                    }
                }

                // Moderation badge for teachers
                if ($forum->isModerated() && api_is_allowed_to_edit(false, true)) {
                    $waitingCount = getCountPostsWithStatus(CForumPost::STATUS_WAITING_MODERATION, $forum);
                    if (!empty($waitingCount)) {
                        $forumInfo['moderation'] = $waitingCount;
                    }
                }

                // Teacher tools for forum
                if (api_is_allowed_to_edit(false, true) && !(null === $forum->getFirstResourceLink()->getSession() && 0 != $sessionId)) {
                    $forumInfo['tools']  = '<a href="'.api_get_self().'?'.api_get_cidreq().'&action=edit_forum&content=forum&id='.$forumId.'">'
                        .Display::getMdiIcon(ActionIcon::EDIT, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Edit'))
                        .'</a>';

                    $forumInfo['tools'] .= '<a href="'.api_get_self().'?'.api_get_cidreq().'&action=delete_forum&content=forum&id='.$forumId
                        ."\" onclick=\"javascript:if(!confirm('".addslashes(api_htmlentities(get_lang('Delete forum ?'), \ENT_QUOTES))."')) return false;\">"
                        .Display::getMdiIcon(ActionIcon::DELETE, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Delete'))
                        .'</a>';

                    $forumInfo['tools'] .= returnVisibleInvisibleIcon('forum', $forumId, $forumInfo['visibility']);
                    $forumInfo['tools'] .= returnLockUnlockIcon('forum', $forumId, $forum->getLocked());
                    $forumInfo['tools'] .= returnUpDownIcon('forum', $forumId, $forumsInCategory);
                }

                // Notify toggle (session-based)
                if (!api_is_anonymous() && api_is_allowed_to_session_edit(false, true)) {
                    $notifyDisabled = true;
                    $sessionForumNotification = $_SESSION['forum_notification']['forum'] ?? [];
                    if (in_array($forumId, $sessionForumNotification)) {
                        $notifyDisabled = false;
                    }
                    $forumInfo['tools'] .= '<a href="'.api_get_self().'?'.api_get_cidreq().'&action=notify&content=forum&id='.$forumId.'">'
                        .Display::getMdiIcon('email-alert', $notifyDisabled ? 'ch-tool-icon-disabled' : 'ch-tool-icon', '', ICON_SIZE_SMALL, get_lang('Notify me'))
                        .'</a>';
                }

                $forumsDetailsList[] = $forumInfo;
            }
        }

        // Category-level extra fields (kept)
        $extraFieldValue = new ExtraFieldValue('forum_category');
        $forumCategoryInfo['extra_fields'] = $extraFieldValue->getAllValuesByItem($categoryId);

        // Hide empty categories for students
        if (!api_is_allowed_to_edit() && empty($forumsDetailsList)) {
            continue;
        }

        $forumCategoryInfo['forums'] = $forumsDetailsList;
        $listForumCategory[] = $forumCategoryInfo;
    }
}

// -----------------------------------------------------------------------------
// Render
// -----------------------------------------------------------------------------
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
