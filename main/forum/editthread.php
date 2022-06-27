<?php
/* For licensing terms, see /license.txt */

/**
 * Edit a Forum Thread.
 *
 * @Author José Loguercio <jose.loguercio@beeznest.com>
 *
 * @package chamilo.forum
 */
require_once __DIR__.'/../inc/global.inc.php';

// The section (tabs).
$this_section = SECTION_COURSES;
// Notification for unauthorized people.
api_protect_course_script(true);

$cidreq = api_get_cidreq();
$nameTools = get_lang('ToolForum');
$_user = api_get_user_info();

require_once 'forumfunction.inc.php';

// Are we in a lp ?
$origin = api_get_origin();

/* MAIN DISPLAY SECTION */
$forumId = (int) $_GET['forum'];
$currentForum = get_forum_information($forumId);
$currentForumCategory = get_forumcategory_information($currentForum['forum_category']);

if (api_is_in_gradebook()) {
    $interbreadcrumb[] = [
        'url' => Category::getUrl(),
        'name' => get_lang('ToolGradebook'),
    ];
}

$threadId = isset($_GET['thread']) ? intval($_GET['thread']) : 0;
$courseInfo = isset($_GET['cidReq']) ? api_get_course_info($_GET['cidReq']) : 0;
$cId = isset($courseInfo['real_id']) ? intval($courseInfo['real_id']) : 0;
$gradebookId = intval(api_is_in_gradebook());

/* Is the user allowed here? */

// The user is not allowed here if:

// 1. the forumcategory or forum is invisible (visibility==0) and the user is not a course manager
if (!api_is_allowed_to_edit(false, true) &&
    (($currentForumCategory['visibility'] && $currentForumCategory['visibility'] == 0) || $currentForum['visibility'] == 0)
) {
    api_not_allowed();
}

// 2. the forumcategory or forum is locked (locked <>0) and the user is not a course manager
if (!api_is_allowed_to_edit(false, true) &&
    (($currentForumCategory['visibility'] && $currentForumCategory['locked'] != 0) || $currentForum['locked'] != 0)
) {
    api_not_allowed();
}

// 3. new threads are not allowed and the user is not a course manager
if (!api_is_allowed_to_edit(false, true) &&
    $currentForum['allow_new_threads'] != 1
) {
    api_not_allowed();
}
// 4. anonymous posts are not allowed and the user is not logged in
if (!$_user['user_id'] && $currentForum['allow_anonymous'] != 1) {
    api_not_allowed();
}

// 5. Check user access
if ($currentForum['forum_of_group'] != 0) {
    $show_forum = GroupManager::user_has_access(
        api_get_user_id(),
        $currentForum['forum_of_group'],
        GroupManager::GROUP_TOOL_FORUM
    );
    if (!$show_forum) {
        api_not_allowed();
    }
}

// 6. Invited users can't create new threads
if (api_is_invitee()) {
    api_not_allowed(true);
}

$groupId = api_get_group_id();
if (!empty($groupId)) {
    $groupProperties = GroupManager::get_group_properties($groupId);
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_CODE_PATH).'group/group.php?'.$cidreq,
        'name' => get_lang('Groups'),
    ];
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_CODE_PATH).'group/group_space.php?'.$cidreq,
        'name' => get_lang('GroupSpace').' '.$groupProperties['name'],
    ];
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_CODE_PATH).'forum/viewforum.php?'.$cidreq.'&forum='.$forumId,
        'name' => $currentForum['forum_title'],
    ];
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_CODE_PATH).'forum/newthread.php?'.$cidreq.'&forum='.$forumId,
        'name' => get_lang('EditThread'),
    ];
} else {
    $interbreadcrumb[] = ['url' => api_get_path(WEB_CODE_PATH).'forum/index.php?'.$cidreq, 'name' => $nameTools];
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_CODE_PATH).'forum/viewforumcategory.php?'.$cidreq.'&forumcategory='.$currentForumCategory['cat_id'],
        'name' => $currentForumCategory['cat_title'],
    ];
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_CODE_PATH).'forum/viewforum.php?'.$cidreq.'&forum='.$forumId,
        'name' => $currentForum['forum_title'],
    ];
    $interbreadcrumb[] = ['url' => '#', 'name' => get_lang('EditThread')];
}

$tableLink = Database::get_main_table(TABLE_MAIN_GRADEBOOK_LINK);

/* Header */
$htmlHeadXtra[] = <<<JS
    <script>
    $(function() {
        $('[name="thread_qualify_gradebook"]:checkbox').change(function () {
            if (this.checked) {
                $('#options_field').show();
            } else {
                $('#options_field').hide();
                $("[name='numeric_calification']").val(0);
                $("[name='calification_notebook_title']").val('');
                $("[name='weight_calification']").val(0);
                $("[name='thread_peer_qualify'][value='0']").prop('checked', true);
            }
        });
    });
    </script>
JS;

// Action links
$actions = [
    Display::url(
        Display::return_icon('back.png', get_lang('BackToForum'), '', ICON_SIZE_MEDIUM),
        'viewforum.php?forum='.$forumId.'&'.$cidreq
    ),
    search_link(),
];

$threadData = getThreadInfo($threadId, $cId);

$gradeThisThread = empty($_POST) && ($threadData['threadQualifyMax'] > 0 || $threadData['threadWeight'] > 0);

$form = new FormValidator(
    'thread',
    'post',
    api_get_self().'?'.http_build_query([
        'forum' => $forumId,
        'thread' => $threadId,
    ]).'&'.api_get_cidreq()
);

$form->addElement('header', get_lang('EditThread'));
$form->setConstants(['forum' => '5']);
$form->addElement('hidden', 'forum_id', $forumId);
$form->addElement('hidden', 'thread_id', $threadId);
$form->addElement('hidden', 'gradebook', $gradebookId);
$form->addElement('text', 'thread_title', get_lang('Title'));
$form->addElement('advanced_settings', 'advanced_params', get_lang('AdvancedParameters'));
$form->addElement('html', '<div id="advanced_params_options" style="display:none">');

if ((api_is_course_admin() || api_is_session_general_coach() || api_is_course_tutor()) && $threadId) {
    // Thread qualify
    if (Gradebook::is_active()) {
        //Loading gradebook select
        GradebookUtils::load_gradebook_select_in_tool($form);
        $form->addElement(
            'checkbox',
            'thread_qualify_gradebook',
            '',
            get_lang('QualifyThreadGradebook')
        );
    } else {
        $form->addElement('hidden', 'thread_qualify_gradebook', false);
    }

    $form->addElement('html', '<div id="options_field" style="'.($gradeThisThread ? '' : 'display:none;').'">');
    $form->addElement('text', 'numeric_calification', get_lang('QualificationNumeric'));
    $form->applyFilter('numeric_calification', 'html_filter');
    $form->addElement('text', 'calification_notebook_title', get_lang('TitleColumnGradebook'));
    $form->applyFilter('calification_notebook_title', 'html_filter');
    $form->addElement(
        'number',
        'weight_calification',
        get_lang('QualifyWeight'),
        ['value' => '0.00', 'step' => '0.01']
    );
    $form->applyFilter('weight_calification', 'html_filter');
    $group = [];
    $group[] = $form->createElement('radio', 'thread_peer_qualify', null, get_lang('Yes'), 1);
    $group[] = $form->createElement('radio', 'thread_peer_qualify', null, get_lang('No'), 0);
    $form->addGroup(
        $group,
        '',
        [get_lang('ForumThreadPeerScoring'), get_lang('ForumThreadPeerScoringComment')]
    );
    $form->addElement('html', '</div>');
}

if (api_is_allowed_to_edit(null, true)) {
    $form->addElement('checkbox', 'thread_sticky', '', get_lang('StickyPost'));
}

$form->addElement('html', '</div>');

Skill::addSkillsToForm($form, api_get_course_int_id(), api_get_session_id(), ITEM_TYPE_FORUM_THREAD, $threadId);

if (!empty($threadData)) {
    $defaults['thread_qualify_gradebook'] = $gradeThisThread;
    $defaults['thread_title'] = prepare4display($threadData['threadTitle']);
    $defaults['thread_sticky'] = strval(intval($threadData['threadSticky']));
    $defaults['thread_peer_qualify'] = intval($threadData['threadPeerQualify']);
    $defaults['numeric_calification'] = $threadData['threadQualifyMax'];
    $defaults['calification_notebook_title'] = $threadData['threadTitleQualify'];
    $defaults['weight_calification'] = $threadData['threadWeight'];
} else {
    $defaults['thread_qualify_gradebook'] = 0;
    $defaults['numeric_calification'] = 0;
    $defaults['calification_notebook_title'] = '';
    $defaults['weight_calification'] = 0;
    $defaults['thread_peer_qualify'] = 0;
}

$form->addButtonUpdate(get_lang('ModifyThread'), 'SubmitPost');

if ($form->validate()) {
    $redirectUrl = api_get_path(WEB_CODE_PATH).'forum/viewforum.php?forum='.$forumId.'&'.api_get_cidreq();
    $check = Security::check_token('post');
    if ($check) {
        $values = $form->exportValues();
        Security::clear_token();
        updateThread($values);
        Skill::saveSkills($form, ITEM_TYPE_FORUM_THREAD, $threadId);
        header('Location: '.$redirectUrl);
        exit;
    }
}

$form->setDefaults(isset($defaults) ? $defaults : null);
$token = Security::get_token();
$form->addElement('hidden', 'sec_token');
$form->setConstants(['sec_token' => $token]);
$originIsLearnPath = $origin == 'learnpath';

$view = new Template(
    '',
    !$originIsLearnPath,
    !$originIsLearnPath,
    $originIsLearnPath,
    $originIsLearnPath
);
$view->assign(
    'actions',
    Display::toolbarAction('toolbar', $actions)
);
$view->assign('content', $form->returnForm());
$view->display_one_col_template();
