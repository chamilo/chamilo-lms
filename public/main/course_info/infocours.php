<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;

/**
 * Code to display the course settings form (for the course admin)
 * and activate the changes.
 *
 * See ./inc/conf/course_info.conf.php for settings
 *
 * @todo    Take those config settings into account in this script
 *
 * @author  Patrick Cool <patrick.cool@UGent.be>
 * @author  Roan Embrechts, refactoring and improved course visibility|subscribe|unsubscribe options
 * @author  Julio Montoya <gugli100@gmail.com> Jquery support + lots of fixes
 */
require_once __DIR__.'/../inc/global.inc.php';
$current_course_tool = TOOL_COURSE_SETTING;
$this_section = SECTION_COURSES;
$nameTools = get_lang('Settings');
api_protect_course_script(true);
api_block_anonymous_users();

$urlId = api_get_current_access_url_id();
$_course = api_get_course_info();
$courseEntity = api_get_course_entity();
$isAllowToEdit = api_is_course_admin() || api_is_platform_admin();
$course_code = api_get_course_id();
$courseId = api_get_course_int_id();
$repo = Container::getCourseRepository();
$courseCategoryRepo = Container::getCourseCategoryRepository();
$illustrationRepo = Container::getIllustrationRepository();
$em = Database::getManager();
$isEditable = true;

if (!$isAllowToEdit) {
    api_not_allowed(true);
}

$router = Container::getRouter();
$translator = Container::getTranslator();

$show_delete_watermark_text_message = false;
if ('true' === api_get_setting('pdf_export_watermark_by_course')) {
    if (isset($_GET['delete_watermark'])) {
        PDF::delete_watermark($course_code);
        $show_delete_watermark_text_message = true;
    }
}

$categories = $courseCategoryRepo->getCategoriesByCourseIdAndAccessUrlId($courseId, $urlId);

$formOptionsArray = [];

// Build the form
$form = new FormValidator(
    'update_course',
    'post',
    api_get_self().'?'.api_get_cidreq()
);

// COURSE SETTINGS
function card_settings_open($id, $title, $open = false, $icon, $parent)
{
    $html = '<div class="card">';
    $html .= '<div class="card-header" id="card_'.$id.'">';
    $html .= '<h5 class="card-title">';
    $html .= '<a
        role="button" class="'.(($open) ? 'collapse' : ' ').'"
        data-toggle="collapse" data-target="#collapse_'.$id.'"
        aria-expanded="true" aria-controls="collapse_'.$id.'">';
    if ($icon) {
        $html .= Display::return_icon($icon, null, null, ICON_SIZE_SMALL);
    }
    $html .= $title;
    $html .= '</a></h5></div>';
    $html .= '<div
        id="collapse_'.$id.'"
        class="collapse show" aria-labelledby="heading_'.$id.'" data-parent="#'.$parent.'">';
    $html .= '<div class="card-body">';

    return $html;
}

function card_settings_close()
{
    $html = '</div></div></div>';

    return $html;
}

$form->addHtml(
    card_settings_open('course_settings', get_lang('Course settings'), true, 'settings.png', 'accordionSettings')
);

$image = '';
$illustrationUrl = $illustrationRepo->getIllustrationUrl($courseEntity, 'course_picture_medium');

if (!empty($illustrationUrl)) {
    $image = '<div class="row">
                <label class="col-md-2 control-label">'.get_lang('Image').'</label>
                <div class="col-md-8"><img class="img-thumbnail" src="'.$illustrationUrl.'" /></div></div>';
}

$form->addText('title', get_lang('Title'), true);
$form->applyFilter('title', 'html_filter');
$form->applyFilter('title', 'trim');

/*$form->addElement(
    'select',
    'category_id',
    get_lang('Category'),
    $categories,
    ['style' => 'width:350px', 'id' => 'category_id']
);*/
$form->addSelectLanguage(
    'course_language',
    [get_lang('Language'), get_lang('This language will be valid for every visitor of your courses portal')]
);

$group = [
    $form->createElement('radio', 'show_course_in_user_language', null, get_lang('Yes'), 1),
    $form->createElement('radio', 'show_course_in_user_language', null, get_lang('No'), 2),
];

$form->addGroup($group, '', [get_lang('Show course in user\'s language')]);

$form->addText('department_name', get_lang('Department'), false);
$form->applyFilter('department_name', 'html_filter');
$form->applyFilter('department_name', 'trim');

$form->addText('department_url', get_lang('Department URL'), false);
$form->applyFilter('department_url', 'html_filter');

// Extra fields
$extra_field = new ExtraField('course');
$extraFieldAdminPermissions = false;
$showOnlyTheseFields = ['tags', 'video_url', 'course_hours_duration', 'max_subscribed_students'];
$extra = $extra_field->addElements(
    $form,
    $courseId,
    [],
    false,
    false,
    $showOnlyTheseFields
);

//Tags ExtraField
$htmlHeadXtra[] = '
<script>
$(function() {
    '.$extra['jquery_ready_content'].'
});
</script>';

// Picture
$form->addFile(
    'picture',
    get_lang('Add a picture'),
    ['id' => 'picture', 'class' => 'picture-form', 'crop_image' => true]
);

$allowed_picture_types = api_get_supported_image_extensions(false);

$form->addHtml($image);

$form->addRule(
    'picture',
    get_lang('Only PNG, JPG or GIF images allowed').' ('.implode(',', $allowed_picture_types).')',
    'filetype',
    $allowed_picture_types
);
$form->addElement('checkbox', 'delete_picture', null, get_lang('Delete picture'));

if ('true' == api_get_setting('pdf_export_watermark_by_course')) {
    $url = PDF::get_watermark($course_code);
    $form->addText('pdf_export_watermark_text', get_lang('PDF watermark text'), false, ['size' => '60']);
    $form->addElement('file', 'pdf_export_watermark_path', get_lang('Upload a watermark image'));
    if (false != $url) {
        $delete_url = '<a href="?delete_watermark">'.Display::return_icon('delete.png', get_lang('Remove picture')).'</a>';
        $form->addElement(
            'html',
            '<div class="row"><div class="form"><a href="'.$url.'">'.$url.' '.$delete_url.'</a></div></div>'
        );
    }
    $form->addRule(
        'pdf_export_watermark_path',
        get_lang('Only PNG, JPG or GIF images allowed').' ('.implode(',', $allowed_picture_types).')',
        'filetype',
        $allowed_picture_types
    );
}

if ('true' == api_get_setting('allow_course_theme')) {
    $group = [];
    $group[] = $form->createElement(
        'SelectTheme',
        'course_theme',
        null,
        ['id' => 'course_theme_id']
    );
    $form->addGroup($group, '', [get_lang('Style sheets')]);
}

$form->addElement('label', get_lang('Space Available'), format_file_size(DocumentManager::get_course_quota()));

$scoreModels = ExerciseLib::getScoreModels();
if (!empty($scoreModels)) {
    $options = ['' => get_lang('none')];
    foreach ($scoreModels['models'] as $item) {
        $options[$item['id']] = get_lang($item['name']);
    }
    $form->addSelect('score_model_id', get_lang('Score model'), $options);
}

$form->addButtonSave(get_lang('Save settings'), 'submit_save');

$form->addHtml(card_settings_close());

$group = [];
$group[] = $form->createElement(
    'radio',
    'visibility',
    get_lang('Course access'),
    get_lang('Public - access allowed for the whole world'),
    COURSE_VISIBILITY_OPEN_WORLD
);
$group[] = $form->createElement(
    'radio',
    'visibility',
    null,
    get_lang(' Open - access allowed for users registered on the platform'),
    COURSE_VISIBILITY_OPEN_PLATFORM
);
$group[] = $form->createElement(
    'radio', 'visibility', null, get_lang('Private access (access authorized to group members only)'),
    COURSE_VISIBILITY_REGISTERED
);
$group[] = $form->createElement(
    'radio',
    'visibility',
    null,
    get_lang('Closed - the course is only accessible to the teachers'),
    COURSE_VISIBILITY_CLOSED
);

// The "hidden" visibility is only available to portal admins
if (api_is_platform_admin()) {
    $group[] = $form->createElement(
        'radio',
        'visibility',
        null,
        get_lang('Hidden - Completely hidden to all users except the administrators'),
        COURSE_VISIBILITY_HIDDEN
    );
}

$groupElement = $form->addGroup(
    $group,
    '',
    [get_lang('Course access'), get_lang('Course accessConfigTip')],
    null,
    null,
    true
);

$url = api_get_path(WEB_CODE_PATH)."auth/inscription.php?c=$course_code&e=1";
$url = Display::url($url, $url);
$label = $form->addLabel(get_lang('Direct link'), sprintf(get_lang('Course settingsRegisterDirect link'), $url), true);

$group2 = [];
$group2[] = $form->createElement('radio', 'subscribe', get_lang('Subscription'), get_lang('Allowed'), 1);
$group2[] = $form->createElement(
    'radio',
    'subscribe',
    null,
    get_lang('This function is only available to trainers'),
    0
);

$myButton = $form->addButtonSave(get_lang('Save settings'), 'submit_save', true);

$group3[] = $form->createElement(
    'radio',
    'unsubscribe',
    get_lang('Unsubscribe'),
    get_lang('Users are allowed to unsubscribe from this course'),
    1
);
$group3[] = $form->createElement(
    'radio',
    'unsubscribe',
    null,
    get_lang('NotUsers are allowed to unsubscribe from this course'),
    0
);

$text = $form->createElement(
    'text',
    'course_registration_password',
    get_lang('Course registration password'),
    false,
    ['size' => '60']
);

$checkBoxActiveLegal = $form->createElement(
    'checkbox',
    'activate_legal',
    [null, get_lang('Show a legal notice when entering the course')],
    get_lang('Enable legal terms')
);
$textAreaLegal = $form->createElement('textarea', 'legal', get_lang('Legal agreement for this course'), ['rows' => 8]);

$elements = [
    $groupElement,
    $label,
    get_lang('Subscription') => $group2,
    get_lang('Unsubscribe') => $group3,
    $text,
    $checkBoxActiveLegal,
    $textAreaLegal,
    $myButton,
];

$form->addPanelOption(
    'course_access',
    get_lang('Course access'),
    $elements,
    'course.png',
    false,
    'accordionSettings'
);

// Documents
$globalGroup = [];
if ('true' == api_get_setting('documents_default_visibility_defined_in_course')) {
    $group = [
        $form->createElement('radio', 'documents_default_visibility', null, get_lang('Visible'), 'visible'),
        $form->createElement('radio', 'documents_default_visibility', null, get_lang('invisible'), 'invisible'),
    ];
    $globalGroup[get_lang('Default visibility of new documents')] = $group;
}

if ('true' == api_get_setting('show_default_folders')) {
    $group = [
        $form->createElement('radio', 'show_system_folders', null, get_lang('Yes'), 1),
        $form->createElement('radio', 'show_system_folders', null, get_lang('No'), 2),
    ];

    $globalGroup[get_lang('Show system folders.')] = $group;

    $myButton = $form->addButtonSave(get_lang('Save settings'), 'submit_save', true);
}

$group = [];
$group[] = $form->createElement(
    'radio',
    'enable_document_auto_launch',
    get_lang('Auto-launch for documents'),
    get_lang('Redirect to the document list'),
    1
);
$group[] = $form->createElement('radio', 'enable_document_auto_launch', null, get_lang('Deactivate'), 0);
$globalGroup[get_lang('Auto-launch for documents')] = $group;

$globalGroup[] = $myButton;

$form->addPanelOption(
    'documents',
    get_lang('Documents'),
    $globalGroup,
    'folder.png',
    false,
    'accordionSettings'
);

$globalGroup = [];
$group = [];
$group[] = $form->createElement(
    'radio',
    'email_alert_to_teacher_on_new_user_in_course',
    get_lang('E-mail teacher when a new user auto-subscribes'),
    get_lang('E-mail teacher when a new user auto-subscribesEnable'),
    1
);
$group[] = $form->createElement(
    'radio',
    'email_alert_to_teacher_on_new_user_in_course',
    null,
    get_lang('E-mail teacher when a new user auto-subscribesToTeacharAndTutor'),
    2
);
$group[] = $form->createElement(
    'radio',
    'email_alert_to_teacher_on_new_user_in_course',
    null,
    get_lang('E-mail teacher when a new user auto-subscribesDisable'),
    0
);
$globalGroup[get_lang('E-mail teacher when a new user auto-subscribes')] = $group;

$group = [];
$group[] = $form->createElement(
    'radio',
    'email_alert_students_on_new_homework',
    get_lang('E-mail students on assignment creation'),
    get_lang('E-mail students on assignment creationEnable'),
    1
);
$group[] = $form->createElement(
    'radio',
    'email_alert_students_on_new_homework',
    null,
    get_lang('E-mail students on assignment creationToHrmEnable'),
    2
);
$group[] = $form->createElement(
    'radio',
    'email_alert_students_on_new_homework',
    null,
    get_lang('E-mail students on assignment creationDisable'),
    0
);
$globalGroup[get_lang('E-mail students on assignment creation')] = $group;

$group = [];
$group[] = $form->createElement(
    'radio',
    'email_alert_manager_on_new_doc',
    get_lang('E-mail on assignments submission by students'),
    get_lang('E-mail on assignments submission by studentsActivate'),
    1
);
$group[] = $form->createElement(
    'radio',
    'email_alert_manager_on_new_doc',
    null,
    get_lang('E-mail on assignments submission by studentsActivateOnlyForTeachers'),
    3
);
$group[] = $form->createElement(
    'radio',
    'email_alert_manager_on_new_doc',
    null,
    get_lang('E-mail on assignments submission by studentsActivateOnlyForStudents'),
    2
);
$group[] = $form->createElement(
    'radio',
    'email_alert_manager_on_new_doc',
    null,
    get_lang('E-mail on assignments submission by studentsDeactivate'),
    0
);

$globalGroup[get_lang('E-mail on assignments submission by students')] = $group;

$group = [];
$group[] = $form->createElement(
    'radio',
    'email_alert_on_new_doc_dropbox',
    get_lang('E-mail users on dropbox file reception'),
    get_lang('E-mail users on dropbox file receptionActivate'),
    1
);
$group[] = $form->createElement(
    'radio',
    'email_alert_on_new_doc_dropbox',
    null,
    get_lang('E-mail users on dropbox file receptionDeactivate'),
    0
);

$globalGroup[get_lang('E-mail users on dropbox file reception')] = $group;

// Exercises notifications
$emailAlerts = ExerciseLib::getNotificationSettings();
$group = [];
foreach ($emailAlerts as $itemId => $label) {
    $group[] = $form->createElement(
        'checkbox',
        'email_alert_manager_on_new_quiz[]',
        null,
        $label,
        ['value' => $itemId]
    );
}

$globalGroup[get_lang('Tests')] = $group;

$group = [];
$group[] = $form->createElement(
    'radio',
    'email_to_teachers_on_new_work_feedback',
    get_lang('E-mail to teachers on new user\'s student publication feedback.'),
    get_lang('Yes'),
    1
);
$group[] = $form->createElement(
    'radio',
    'email_to_teachers_on_new_work_feedback',
    null,
    get_lang('No'),
    2
);

$globalGroup[get_lang('E-mail to teachers on new user\'s student publication feedback.')] = $group;

$myButton = $form->addButtonSave(get_lang('Save settings'), 'submit_save', true);
$globalGroup[] = $myButton;

$form->addPanelOption(
    'email-notifications',
    get_lang('E-mail notifications'),
    $globalGroup,
    'mail.png',
    false,
    'accordionSettings'
);

$group = [];
$group[] = $form->createElement(
    'radio',
    'allow_user_edit_agenda',
    get_lang('Allow learners to edit the agenda'),
    get_lang('Allow learners to edit the agendaActivate'),
    1
);
$group[] = $form->createElement(
    'radio',
    'allow_user_edit_agenda',
    null,
    get_lang('Allow learners to edit the agendaDeactivate'),
    0
);

$group2 = [];
$group2[] = $form->createElement(
    'radio',
    'allow_user_edit_announcement',
    get_lang('Allow learners to edit announcements'),
    get_lang('Allow learners to edit announcementsActivate'),
    1
);
$group2[] = $form->createElement(
    'radio',
    'allow_user_edit_announcement',
    null,
    get_lang('Allow learners to edit announcementsDeactivate'),
    0
);

$group3 = [];
$group3[] = $form->createElement(
    'radio',
    'allow_user_image_forum',
    get_lang('User picture in forum'),
    get_lang('User picture in forumActivate'),
    1
);
$group3[] = $form->createElement(
    'radio',
    'allow_user_image_forum',
    null,
    get_lang('User picture in forumDeactivate'),
    0
);

$group4 = [];
$group4[] = $form->createElement(
    'radio',
    'allow_user_view_user_list',
    get_lang('Allow user view user list'),
    get_lang('Allow user view user listActivate'),
    1
);
$group4[] = $form->createElement(
    'radio',
    'allow_user_view_user_list',
    null,
    get_lang('Allow user view user listDeactivate'),
    0
);
$myButton = $form->addButtonSave(get_lang('Save settings'), 'submit_save', true);

$globalGroup = [
    get_lang('Allow learners to edit the agenda') => $group,
    get_lang('Allow learners to edit announcements') => $group2,
    get_lang('User picture in forum') => $group3,
    get_lang('Allow user view user list') => $group4,
    '' => $myButton,
];

$form->addPanelOption(
    'users',
    get_lang('User rights'),
    $globalGroup,
    'user.png',
    false,
    'accordionSettings'
);

// CHAT SETTINGS
$group = [];
$group[] = $form->createElement(
    'radio',
    'allow_open_chat_window',
    get_lang('Open chat in a new Window'),
    get_lang('Activate open the chat in a new window'),
    1
);
$group[] = $form->createElement(
    'radio',
    'allow_open_chat_window',
    null,
    get_lang('Deactivate open the chat in a new window'),
    0
);
$myButton = $form->addButtonSave(get_lang('Save settings'), 'submit_save', true);

$globalGroup = [
    get_lang('Open chat in a new Window') => $group,
    '' => $myButton,
];

$form->addPanelOption(
    'chat',
    get_lang('Chat settings'),
    $globalGroup,
    'chat.png',
    false,
    'accordionSettings'
);

$globalGroup = [];
$group = [];
$group[] = $form->createElement(
    'radio',
    'enable_lp_auto_launch',
    get_lang('Enable learning path auto-launch'),
    get_lang('Redirect to a selected learning path'),
    1
);
$group[] = $form->createElement(
    'radio',
    'enable_lp_auto_launch',
    get_lang('Enable learning path auto-launch'),
    get_lang('Redirect to the learning paths list'),
    2
);
$group[] = $form->createElement('radio', 'enable_lp_auto_launch', null, get_lang('Deactivate'), 0);

$globalGroup[get_lang('Enable learning path auto-launch')] = $group;

if ('true' == api_get_setting('allow_course_theme')) {
    // Allow theme into Learning path
    $group = [];
    $group[] = $form->createElement(
        'radio',
        'allow_learning_path_theme',
        get_lang('Enable course themes'),
        get_lang('Enable course themesAllow'),
        1
    );
    $group[] = $form->createElement(
        'radio',
        'allow_learning_path_theme',
        null,
        get_lang('Enable course themesDisallow'),
        0
    );

    $globalGroup[get_lang("Enable course themes")] = $group;
}

$allowLPReturnLink = api_get_setting('allow_lp_return_link');
if ('true' === $allowLPReturnLink) {
    $group = [
        $form->createElement(
            'radio',
            'lp_return_link',
            get_lang('Learning path return link'),
            get_lang('Redirect to the learning paths list'),
            1
        ),
        $form->createElement(
            'radio',
            'lp_return_link',
            null,
            get_lang('Redirect to Course home'),
            0
        ),
        $form->createElement(
            'radio',
            'lp_return_link',
            null,
            get_lang('My courses'),
            2
        ),
        $form->createElement(
            'radio',
            'lp_return_link',
            null,
            get_lang('RedirectToPortalHome'),
            3
        ),
    ];
    $globalGroup[get_lang("Learning path return link")] = $group;
}

$exerciseInvisible = api_get_setting('exercise_invisible_in_session');
$configureExerciseVisibility = api_get_setting('configure_exercise_visibility_in_course');

if ('true' === $exerciseInvisible &&
    'true' === $configureExerciseVisibility
) {
    $group = [
        $form->createElement(
            'radio',
            'exercise_invisible_in_session',
            get_lang('TestinvisibleInSession'),
            get_lang('Yes'),
            1
        ),
        $form->createElement(
            'radio',
            'exercise_invisible_in_session',
            null,
            get_lang('No'),
            0
        ),
    ];

    $globalGroup[get_lang("TestinvisibleInSession")] = $group;
}

if ($isEditable) {
    $myButton = $form->addButtonSave(get_lang('Save settings'), 'submit_save', true);
    $globalGroup[] = $myButton;
} else {
    // Is it allowed to edit the course settings?
    if (!$isEditable) {
        $disabled_output = "disabled";
    }
    $form->freeze();
}

$form->addPanelOption(
    'config_lp',
    get_lang('Learning path settings'),
    $globalGroup,
    'scorms.png',
    false,
    'accordionSettings'
);

if (api_get_configuration_value('allow_exercise_auto_launch')) {
    $globalGroup = [];

    // Auto launch exercise
    $group = [];
    $group[] = $form->createElement(
        'radio',
        'enable_exercise_auto_launch',
        get_lang('Auto-launch for exercises'),
        get_lang('Redirect to the selected exercise'),
        1
    );
    $group[] = $form->createElement(
        'radio',
        'enable_exercise_auto_launch',
        get_lang('Auto-launch for exercises'),
        get_lang('Redirect to the exercises list'),
        2
    );
    $group[] = $form->createElement('radio', 'enable_exercise_auto_launch', null, get_lang('Deactivate'), 0);

    $globalGroup[get_lang("Auto-launch for exercises")] = $group;

    if ($isEditable) {
        $myButton = $form->addButtonSave(get_lang('Save settings'), 'submit_save', true);
        $globalGroup[] = $myButton;
    } else {
        // Is it allowed to edit the course settings?
        if (!$isEditable) {
            $disabled_output = "disabled";
        }
        $form->freeze();
    }

    $form->addPanelOption(
        'config_exercise',
        get_lang('Test'),
        $globalGroup,
        'quiz.png',
        false,
        'accordionSettings'
    );
}

// *************** START THEMATIC  *************/
$group = [];
$group[] = $form->createElement(
    'radio',
    'display_info_advance_inside_homecourse',
    get_lang('Information on thematic advance on course homepage'),
    get_lang('Display information about the last completed topic'),
    1
);
$group[] = $form->createElement(
    'radio',
    'display_info_advance_inside_homecourse',
    null,
    get_lang('Display information about the next uncompleted topic'),
    2
);
$group[] = $form->createElement(
    'radio',
    'display_info_advance_inside_homecourse',
    null,
    get_lang('Display information about the next uncompleted topicAndLastDoneAdvance'),
    3
);
$group[] = $form->createElement(
    'radio',
    'display_info_advance_inside_homecourse',
    null,
    get_lang('Do not display progress'),
    0
);
$myButton = $form->addButtonSave(get_lang('Save settings'), 'submit_save', true);

$globalGroup = [
    get_lang('Information on thematic advance on course homepage') => $group,
    '' => $myButton,
];

$form->addPanelOption(
    'thematic',
    get_lang('Thematic advance configuration'),
    $globalGroup,
    'course_progress.png',
    false,
    'accordionSettings'
);

if ('true' === api_get_setting('allow_public_certificates')) {
    $group = [];
    $group[] = $form->createElement(
        'radio',
        'allow_public_certificates',
        get_lang('Learner certificates are public'),
        get_lang('Yes'),
        1
    );
    $group[] = $form->createElement('radio', 'allow_public_certificates', null, get_lang('No'), 0);
    $myButton = $form->addButtonSave(get_lang('Save settings'), 'submit_save', true);

    $globalGroup = [
        get_lang('Learner certificates are public') => $group,
        '' => $myButton,
    ];

    $form->addPanelOption(
        'certificate',
        get_lang('Certificates'),
        $globalGroup,
        null,
        false,
        'accordionSettings'
    );
}

// Forum settings
$group = [
    $form->createElement('radio', 'enable_forum_auto_launch', null, get_lang('Redirect to forums list'), 1),
    $form->createElement('radio', 'enable_forum_auto_launch', null, get_lang('Disabled'), 2),
];
$myButton = $form->addButtonSave(get_lang('Save settings'), 'submit_save', true);

// Forum settings
$groupNotification = [
    $form->createElement('radio', 'hide_forum_notifications', null, get_lang('Yes'), 1),
    $form->createElement('radio', 'hide_forum_notifications', null, get_lang('No'), 2),
];

$addUsers = [
    $form->createElement('radio', 'subscribe_users_to_forum_notifications', null, get_lang('Yes'), 1),
    $form->createElement('radio', 'subscribe_users_to_forum_notifications', null, get_lang('No'), 2),
];

$globalGroup = [
    get_lang('Enable forum auto-launch') => $group,
    get_lang('Hide forum notifications') => $groupNotification,
    get_lang('Subscribe automatically all users to all forum notifications') => $addUsers,
    '' => $myButton,
];

$form->addPanelOption(
    'forum',
    get_lang('Forum'),
    $globalGroup,
    'forum.png',
    false,
    'accordionSettings'
);

// Student publication
$group = [
    $form->createElement('radio', 'show_score', null, get_lang('New documents are visible for all users'), 0),
    $form->createElement('radio', 'show_score', null, get_lang('New documents are only visible for the teacher(s)'), 1),
];
$group2 = [
    $form->createElement('radio', 'student_delete_own_publication', null, get_lang('Yes'), 1),
    $form->createElement('radio', 'student_delete_own_publication', null, get_lang('No'), 0),
];
$myButton = $form->addButtonSave(get_lang('Save settings'), 'submit_save', true);

$globalGroup = [
    get_lang('Default setting for the visibility of newly posted files') => $group,
    get_lang('Allow learners to delete their own publications') => $group2,
    '' => $myButton,
];

$form->addPanelOption(
    'student-publication',
    get_lang('Assignments'),
    $globalGroup,
    'work.png',
    false,
    'accordionSettings'
);

$button = Display::toolbarButton(
    get_lang('Configure external tools'),
    api_get_path(WEB_PUBLIC_PATH)."courses/$course_code/lti/",
    'cog',
    'primary'
);
$html = [
    $form->createElement('html', '<p>'.get_lang('LTI intro tool').'</p>'.$button),
];

$form->addPanelOption(
    'lti_tool',
    $translator->trans('External tools'),
    $html,
    'plugin.png',
    false,
    'accordionSettings'
);

// Plugin course settings
$appPlugin = new AppPlugin();
$appPlugin->add_course_settings_form($form);

$form->addHtml('</div>');

// Set the default values of the form
$values = [];
$values['title'] = $_course['name'];
//$values['category_id'] = $_course['category_id'];
$values['course_language'] = $_course['language'];
$values['department_name'] = $_course['extLink']['name'];
$values['department_url'] = $_course['extLink']['url'];
$values['visibility'] = $_course['visibility'];
$values['subscribe'] = $_course['subscribe'];
$values['unsubscribe'] = $_course['unsubscribe'];
$values['course_registration_password'] = $_course['registration_code'];
$values['legal'] = $_course['legal'];
$values['activate_legal'] = $_course['activate_legal'];
$values['show_score'] = $_course['show_score'];

$courseSettings = CourseManager::getCourseSettingVariables($appPlugin);

foreach ($courseSettings as $setting) {
    $result = api_get_course_setting($setting);
    if ('-1' != $result) {
        $values[$setting] = $result;
    }
}
// make sure new settings have a clear default value
if (!isset($values['student_delete_own_publication'])) {
    $values['student_delete_own_publication'] = 0;
}
$form->setDefaults($values);

// Validate form
if ($form->validate() && $isEditable) {
    $updateValues = $form->getSubmitValues();

    // update course picture
    $picture = $_FILES['picture'];

    if (!empty($picture['name'])) {
        $uploadFile = $request->files->get('picture');
        $file = $illustrationRepo->addIllustration($courseEntity, api_get_user_entity(api_get_user_id()), $uploadFile);
        if ($file) {
            $file->setCrop($updateValues['picture_crop_result_for_resource']);
            $em->persist($file);
            $em->flush();
            Event::addEvent(
                LOG_COURSE_SETTINGS_CHANGED,
                'course_picture',
                $picture['name']
            );
        }
    }

    $visibility = $updateValues['visibility'];
    $deletePicture = isset($updateValues['delete_picture']) ? $updateValues['delete_picture'] : '';

    if ($deletePicture) {
        $illustrationRepo->deleteIllustration($courseEntity);
    }

    $limitCourses = api_get_configuration_value('hosting_limit_active_courses');
    if ($limitCourses > 0) {
        $courseInfo = api_get_course_info_by_id($courseId);

        // Check if
        if (COURSE_VISIBILITY_HIDDEN == $courseInfo['visibility'] &&
            $visibility != $courseInfo['visibility']
        ) {
            $num = CourseManager::countActiveCourses($urlId);
            if ($num >= $limitCourses) {
                api_warn_hosting_contact('hosting_limit_active_courses');

                Display::addFlash(
                    Display::return_message(
                        get_lang(
                            'Sorry, this installation has an active courses limit, which has now been reached. You can still create new courses, but only if you hide/disable at least one existing active course. To do this, edit a course from the administration courses list, and change the visibility to \'hidden\', then try creating this course again. To increase the maximum number of active courses allowed on this Chamilo installation, please contact your hosting provider or, if available, upgrade to a superior hosting plan.'
                        )
                    )
                );

                $url = api_get_path(WEB_CODE_PATH).'course_info/infocours.php?'.api_get_cidreq();
                header("Location: $url");
                exit;
            }
        }
    }

    $pdf_export_watermark_path = isset($_FILES['pdf_export_watermark_path'])
        ? $_FILES['pdf_export_watermark_path']
        : null;

    if (!empty($pdf_export_watermark_path['name'])) {
        PDF::upload_watermark(
            $pdf_export_watermark_path['name'],
            $pdf_export_watermark_path['tmp_name'],
            $course_code
        );
        unset($updateValues['pdf_export_watermark_path']);
    }

    $activeLegal = isset($updateValues['activate_legal']) ? $updateValues['activate_legal'] : 0;

    /*$category = null;
    if (!empty($updateValues['category_id'])) {
        $category = $courseCategoryRepo->find($updateValues['category_id']);
    }*/

    $courseEntity
        ->setTitle($updateValues['title'])
        ->setCourseLanguage($updateValues['course_language'])
        //->setCategory($category)
        ->setDepartmentName($updateValues['department_name'])
        ->setDepartmentUrl($updateValues['department_url'])
        ->setVisibility($updateValues['visibility'])
        ->setSubscribe($updateValues['subscribe'])
        ->setUnsubscribe($updateValues['unsubscribe'])
        ->setLegal($updateValues['legal'])
        ->setActivateLegal($activeLegal)
        ->setRegistrationCode($updateValues['course_registration_password'])
        ->setShowScore($updateValues['show_score']);

    $em->persist($courseEntity);
    $em->flush();

    // Insert/Updates course_settings table
    foreach ($courseSettings as $setting) {
        $value = isset($updateValues[$setting]) ? $updateValues[$setting] : null;
        CourseManager::saveCourseConfigurationSetting(
            $appPlugin,
            $setting,
            $value,
            api_get_course_int_id()
        );
    }
    // update the extra fields
    $courseFieldValue = new ExtraFieldValue('course');
    $courseFieldValue->saveFieldValues($updateValues, true);

    $appPlugin->saveCourseSettingsHook($updateValues);
    $courseParams = api_get_cidreq();
    $cidReset = true;
    $cidReq = $course_code;
    Display::addFlash(Display::return_message(get_lang('Update successful')));
    $url = api_get_path(WEB_CODE_PATH).'course_info/infocours.php?'.$courseParams;
    header("Location: $url");
    exit;
}

if ($show_delete_watermark_text_message) {
    Display::addFlash(
        Display::return_message(get_lang('File deleted'), 'normal')
    );
}

//Template Course Info
$tpl = new Template($nameTools);

Display::display_header($nameTools, 'Settings');
$tpl->assign('course_settings', $form->returnForm());
$courseInfoLayout = $tpl->get_template("course_info/index.html.twig");
$content = $tpl->fetch($courseInfoLayout);
echo $content;

Display::display_footer();
