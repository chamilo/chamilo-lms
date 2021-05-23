<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CForum;

/**
 * Edit a Forum Thread.
 *
 * @Author José Loguercio <jose.loguercio@beeznest.com>
 */
require_once __DIR__.'/../inc/global.inc.php';

// The section (tabs).
$this_section = SECTION_COURSES;
// Notification for unauthorized people.
api_protect_course_script(true);

$cidreq = api_get_cidreq();
$nameTools = get_lang('Forums');
$_user = api_get_user_info();

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

// Are we in a lp ?
$origin = api_get_origin();

/* MAIN DISPLAY SECTION */
$forumId = isset($_GET['forum']) ? (int) $_GET['forum'] : 0;

$repo = Container::getForumRepository();
/** @var CForum $forum */
$forum = $repo->find($forumId);
if (empty($forum)) {
    api_not_allowed();
}

$courseEntity = api_get_course_entity();
$sessionEntity = api_get_session_entity();
//$forumIsVisible = $forum->isVisible($courseEntity, $sessionEntity);

$category = $forum->getForumCategory();
$categoryIsVisible = $category->isVisible($courseEntity, $sessionEntity);

if (api_is_in_gradebook()) {
    $interbreadcrumb[] = [
        'url' => Category::getUrl(),
        'name' => get_lang('Assessments'),
    ];
}

$threadId = isset($_GET['thread']) ? (int) ($_GET['thread']) : 0;
$courseInfo = api_get_course_info();
$courseId = $courseInfo['real_id'];

$gradebookId = (int) (api_is_in_gradebook());

/* Is the user allowed here? */

// The user is not allowed here if:

// 1. the forumcategory or forum is invisible (visibility==0) and the user is not a course manager
if (!api_is_allowed_to_edit(false, true) && false === $categoryIsVisible) {
    api_not_allowed();
}

// 2. the forumcategory or forum is locked (locked <>0) and the user is not a course manager
if (!api_is_allowed_to_edit(false, true) &&
    (($categoryIsVisible && 0 != $category->getLocked()) || 0 != $forum->getLocked())
) {
    api_not_allowed();
}

// 3. new threads are not allowed and the user is not a course manager
if (!api_is_allowed_to_edit(false, true) &&
    1 != $forum->getAllowNewThreads()
) {
    api_not_allowed();
}
// 4. anonymous posts are not allowed and the user is not logged in
if (!$_user['user_id'] && 1 != $forum->getAllowAnonymous()) {
    api_not_allowed();
}

// 5. Check user access
if (0 != $forum->getForumOfGroup()) {
    $show_forum = GroupManager::userHasAccess(
        api_get_user_id(),
        api_get_group_entity($forum->getForumOfGroup()),
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
        'name' => get_lang('Group area').' '.$groupProperties['name'],
    ];
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_CODE_PATH).'forum/viewforum.php?'.$cidreq.'&forum='.$forumId,
        'name' => $forum->getForumTitle(),
    ];
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_CODE_PATH).'forum/newthread.php?'.$cidreq.'&forum='.$forumId,
        'name' => get_lang('Edit thread'),
    ];
} else {
    $interbreadcrumb[] = ['url' => api_get_path(WEB_CODE_PATH).'forum/index.php?'.$cidreq, 'name' => $nameTools];
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_CODE_PATH).'forum/index.php?'.$cidreq.'&forumcategory='.$category->getIid(),
        'name' => $category->getCatTitle(),
    ];
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_CODE_PATH).'forum/viewforum.php?'.$cidreq.'&forum='.$forumId,
        'name' => $forum->getForumTitle(),
    ];
    $interbreadcrumb[] = ['url' => '#', 'name' => get_lang('Edit thread')];
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
        Display::return_icon('back.png', get_lang('Back to forum'), '', ICON_SIZE_MEDIUM),
        'viewforum.php?forum='.$forumId.'&'.$cidreq
    ),
    search_link(),
];

$threadData = getThreadInfo($threadId, $courseId);
$gradeThisThread = empty($_POST) && ($threadData && ($threadData['threadQualifyMax'] > 0 || $threadData['threadWeight'] > 0));

$form = new FormValidator(
    'thread',
    'post',
    api_get_self().'?'.http_build_query([
        'forum' => $forumId,
        'thread' => $threadId,
    ]).'&'.api_get_cidreq()
);

$form->addElement('header', get_lang('Edit thread'));
$form->setConstants(['forum' => '5']);
$form->addElement('hidden', 'forum_id', $forumId);
$form->addElement('hidden', 'thread_id', $threadId);
$form->addElement('hidden', 'gradebook', $gradebookId);
$form->addElement('text', 'thread_title', get_lang('Title'));
$form->addElement('advanced_settings', 'advanced_params', get_lang('Advanced settings'));
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
            get_lang('Grade this thread')
        );
    } else {
        $form->addElement('hidden', 'thread_qualify_gradebook', false);
    }

    $form->addElement('html', '<div id="options_field" style="'.($gradeThisThread ? '' : 'display:none;').'">');
    $form->addElement('text', 'numeric_calification', get_lang('Maximum score'));
    $form->applyFilter('numeric_calification', 'html_filter');
    $form->addElement('text', 'calification_notebook_title', get_lang('Column header in Competences Report'));
    $form->applyFilter('calification_notebook_title', 'html_filter');
    $form->addElement(
        'number',
        'weight_calification',
        get_lang('Weight in Report'),
        ['value' => '0.00', 'step' => '0.01']
    );
    $form->applyFilter('weight_calification', 'html_filter');
    $group = [];
    $group[] = $form->createElement('radio', 'thread_peer_qualify', null, get_lang('Yes'), 1);
    $group[] = $form->createElement('radio', 'thread_peer_qualify', null, get_lang('No'), 0);
    $form->addGroup(
        $group,
        '',
        [get_lang('Thread scored by peers'), get_lang('Thread scored by peersComment')]
    );
    $form->addElement('html', '</div>');
}

if (api_is_allowed_to_edit(null, true)) {
    $form->addElement(
        'checkbox',
        'thread_sticky',
        '',
        get_lang('This is a sticky message (appears always on top and has a special sticky icon)')
    );
}

$form->addElement('html', '</div>');

$skillList = Skill::addSkillsToForm($form, ITEM_TYPE_FORUM_THREAD, $threadId);

$defaults = [];
$defaults['thread_qualify_gradebook'] = 0;
$defaults['numeric_calification'] = 0;
$defaults['calification_notebook_title'] = '';
$defaults['weight_calification'] = 0;
$defaults['thread_peer_qualify'] = 0;

if (!empty($threadData)) {
    $defaults['thread_qualify_gradebook'] = $gradeThisThread;
    $defaults['thread_title'] = prepare4display($threadData['threadTitle']);
    $defaults['thread_sticky'] = (string) ((int) ($threadData['threadSticky']));
    $defaults['thread_peer_qualify'] = (int) ($threadData['threadPeerQualify']);
    $defaults['numeric_calification'] = $threadData['threadQualifyMax'];
    $defaults['calification_notebook_title'] = $threadData['threadTitleQualify'];
    $defaults['weight_calification'] = $threadData['threadWeight'];
}

$defaults['skills'] = array_keys($skillList);

$form->addButtonUpdate(get_lang('Edit thread'), 'SubmitPost');

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

$form->setDefaults($defaults);
$token = Security::get_token();
$form->addElement('hidden', 'sec_token');
$form->setConstants(['sec_token' => $token]);
$originIsLearnPath = 'learnpath' === $origin;

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
