<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Enums\ActionIcon;
use Chamilo\CoreBundle\Enums\ObjectIcon;
use Chamilo\CoreBundle\Enums\ToolIcon;
use Chamilo\CoreBundle\Framework\Container;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Chamilo\CoreBundle\Entity\Course;

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
$translator = Container::$container->get('translator');

$show_delete_watermark_text_message = false;
if ('true' === api_get_setting('pdf_export_watermark_by_course')) {
    if (isset($_GET['delete_watermark'])) {
        PDF::delete_watermark($course_code);
        $show_delete_watermark_text_message = true;
    }
}

$categories = $courseCategoryRepo->getCategoriesByCourseIdAndAccessUrlId($courseId, $urlId);

$formOptionsArray = [];

$enableAiHelpers = 'true' === api_get_setting('ai_helpers.enable_ai_helpers');

$courseVisibilityAdminsOnlySetting = api_get_setting('workflows.course_visibility_change_only_admin');
$courseVisibilityAdminsOnly = \in_array($courseVisibilityAdminsOnlySetting, ['true', '1'], true);

// Teachers/course admins won't be able to change the visibility when this is enabled.
// Platform admins can still change it (and also from admin courses list as mentioned in the issue).
$canChangeCourseVisibility = !$courseVisibilityAdminsOnly || api_is_platform_admin();

// Build the form
$form = new FormValidator(
    'update_course',
    'post',
    api_get_self().'?'.api_get_cidreq()
);

$image = '';
$illustrationUrl = $illustrationRepo->getIllustrationUrl($courseEntity, 'course_picture_medium');
if (!empty($illustrationUrl)) {
    $image = '<div class="row">
                <label class="col-md-2 control-label">'.get_lang('Image').'</label>
                <div class="col-md-8">
                    <img class="img-thumbnail" src="'.$illustrationUrl.'" />
                </div>
            </div>';
}

$form->addElement('html', '<div id="course-main-settings-legacy-start"></div>');

// --- Main course settings fields (legacy block) ---
$form->addText('title', get_lang('Title'), true);
$form->applyFilter('title', 'html_filter');
$form->applyFilter('title', 'trim');

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

// Tags ExtraField (JS)
$htmlHeadXtra[] = '
<script>
$(function() {
    '.$extra['jquery_ready_content'].'
});
</script>';

// Picture preview HTML
$image = '';
$illustrationUrl = $illustrationRepo->getIllustrationUrl($courseEntity, 'course_picture_medium');

if (!empty($illustrationUrl)) {
    $image = '
        <div class="row course-picture-preview-row">
            <div class="col-md-2"></div>
            <div class="col-md-8">
                <div class="course-picture-preview ml-4">
                    <span class="help-block small">'.get_lang('Current picture').'</span>
                    <img class="img-thumbnail" src="'.$illustrationUrl.'" alt="'.get_lang('Current picture').'" />
                </div>
            </div>
        </div>';
}

// Picture file input
$form->addFile(
    'picture',
    get_lang('Course picture'),
    [
        'id' => 'picture',
        'class' => 'picture-form',
        'crop_image' => true,
    ]
);

$allowed_picture_types = api_get_supported_image_extensions(false);

// Preview below the file input
$form->addHtml($image);
$form->addRule(
    'picture',
    get_lang('Only PNG, JPG or GIF images allowed').' ('.implode(',', $allowed_picture_types).')',
    'filetype',
    $allowed_picture_types
);

// Delete checkbox
$form->addElement(
    'checkbox',
    'delete_picture',
    null,
    get_lang('Delete picture')
);

if ('true' === api_get_setting('pdf_export_watermark_by_course')) {
    $url = PDF::get_watermark($course_code);
    $form->addText('pdf_export_watermark_text', get_lang('PDF watermark text'), false, ['size' => '60']);
    $form->addElement('file', 'pdf_export_watermark_path', get_lang('Upload a watermark image'));
    if (false != $url) {
        $delete_url = '<a href="?delete_watermark">'.Display::getMdiIcon(
                ActionIcon::DELETE,
                'ch-tool-icon',
                null,
                ICON_SIZE_SMALL,
                get_lang('Remove picture')
            ).'</a>';

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

if ('true' === api_get_setting('allow_course_theme')) {
    $group = [];
    $group[] = $form->createElement(
        'SelectTheme',
        'course_theme',
        [],
        ['id' => 'course_theme_id'],
        []
    );
    $form->addGroup($group, '', [get_lang('Style sheets')]);
}

$form->addElement('label', get_lang('Space available'), format_file_size(DocumentManager::get_course_quota()));

$aiOptions = [
    'learning_path_generator' => 'Enable learning path generator',
    'exercise_generator' => 'Enable exercise generator',
    'open_answers_grader' => 'Enable open answers grader',
    'tutor_chatbot' => 'Enable tutor chatbot',
    'task_grader' => 'Enable task grader',
    'content_analyser' => 'Enable content analyser',
    'image_generator' => 'Enable image generator',
    'glossary_terms_generator' => 'Enable glossary terms generator',
    'video_generator' => 'Enable video generator',
    'course_analyser' => 'Enable course analyser',
];

// This global "Save settings" button belongs to the main course settings block
$form->addButtonSave(get_lang('Save settings'), 'submit_save');

// Marker: end of legacy main settings block
$form->addElement('html', '<div id="course-main-settings-legacy-end"></div>');
$mainPanelGroup = [
    '' => [
        $form->createElement(
            'html',
            '<!-- Main course settings will be moved into this panel via JavaScript -->'
        ),
    ],
];

$form->addPanelOption(
    'course_main',
    get_lang('Course settings'),
    $mainPanelGroup,
    ActionIcon::INFORMATION,
    true
);

// --- End of legacy block, from here panels start as usual ---

$directUrl = api_get_path(WEB_CODE_PATH)."auth/registration.php?c=$courseId&e=1";
$directUrlLink = Display::url($directUrl, $directUrl);

$directLinkHtml = sprintf(
    get_lang(
        'If your course is public or open, you can use the direct link below to send an invitation to new users, so after registration, they will be sent directly to the course. Also, you can add the e=1 parameter to the URL, replacing "1" by an exercise ID to send them directly to a specific exam. The exercise ID can be discovered in the URL when clicking on an exercise to open it.<br/>%s'
    ),
    $directUrlLink
);
$directLinkElement = $form->createElement(
    'static',
    'direct_link',
    get_lang('Direct link'),
    '<div class="course-settings-direct-link">'.$directLinkHtml.'</div>'
);

$groupAccess = [];
$groupAccess[] = $form->createElement(
    'radio',
    'visibility',
    get_lang('Course access'),
    get_lang('Public - access allowed for the whole world'),
    Course::OPEN_WORLD
);
$groupAccess[] = $form->createElement(
    'radio',
    'visibility',
    null,
    get_lang('Open - access allowed for users registered on the platform'),
    Course::OPEN_PLATFORM
);
$groupAccess[] = $form->createElement(
    'radio',
    'visibility',
    null,
    get_lang('Private - access granted by privileged users'),
    Course::REGISTERED
);
$groupAccess[] = $form->createElement(
    'radio',
    'visibility',
    null,
    get_lang('Closed - the course is only accessible to the teachers'),
    Course::CLOSED
);

// The "hidden" visibility is only available to portal admins
if (api_is_platform_admin()) {
    $groupAccess[] = $form->createElement(
        'radio',
        'visibility',
        null,
        get_lang('Hidden - Completely hidden to all users except the administrators'),
        Course::HIDDEN
    );
}

$courseVisibilityHelp = null;
if (!$canChangeCourseVisibility) {
    foreach ($groupAccess as $radio) {
        if (\is_object($radio) && method_exists($radio, 'updateAttributes')) {
            $radio->updateAttributes([
                'disabled' => 'disabled',
            ]);
        }
    }

    $courseVisibilityHelp = $form->createElement(
        'html',
        '<div class="alert alert-info" role="alert">'
        .get_lang('Only platform administrators can change the course visibility.')
        .'</div>'
    );
}

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

$group3 = [];
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
    get_lang('Users are not allowed to unsubscribe from this course'),
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

// Anchor inside "Course access" panel used by JS to potentially move the visibility block
$courseAccessAnchor = $form->createElement('html', '<div id="course-access-panel-anchor"></div>');

$elements = [
    '' => array_values(array_filter([$courseAccessAnchor, $courseVisibilityHelp])),
    get_lang('Course access') => $groupAccess,
    $directLinkElement,
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
    ToolIcon::COURSE,
    false
);

// Documents
$globalGroup = [];
if ('true' === api_get_setting('documents_default_visibility_defined_in_course')) {
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

$form->addPanelOption(
    'documents',
    get_lang('Documents'),
    $globalGroup,
    ToolIcon::DOCUMENT,
    false
);

// E-mail notifications
$globalGroup = [];
$group = [];
$group[] = $form->createElement(
    'radio',
    'email_alert_to_teacher_on_new_user_in_course',
    get_lang('E-mail teacher when a new user auto-subscribes'),
    get_lang('Enable'),
    1
);
$group[] = $form->createElement(
    'radio',
    'email_alert_to_teacher_on_new_user_in_course',
    null,
    get_lang('To teacher and tutor'),
    2
);
$group[] = $form->createElement(
    'radio',
    'email_alert_to_teacher_on_new_user_in_course',
    null,
    get_lang('Disable'),
    0
);
$globalGroup[get_lang('E-mail teacher when a new user auto-subscribes')] = $group;

$group = [];
$group[] = $form->createElement(
    'radio',
    'email_alert_student_on_manual_subscription',
    get_lang('E-mail student when he is subscribed to the course'),
    get_lang('Enable'),
    1
);
$group[] = $form->createElement(
    'radio',
    'email_alert_student_on_manual_subscription',
    null,
    get_lang('Disable'),
    0
);
$globalGroup[get_lang('E-mail student when he is subscribed to the course')] = $group;

$group = [];
$group[] = $form->createElement(
    'radio',
    'email_alert_students_on_new_homework',
    get_lang('E-mail students on assignment creation'),
    get_lang('Enable'),
    1
);
$group[] = $form->createElement(
    'radio',
    'email_alert_students_on_new_homework',
    null,
    get_lang('To HR only'),
    2
);
$group[] = $form->createElement(
    'radio',
    'email_alert_students_on_new_homework',
    null,
    get_lang('Disable'),
    0
);
$globalGroup[get_lang('E-mail students on assignment creation')] = $group;

$group = [];
$group[] = $form->createElement(
    'radio',
    'email_alert_manager_on_new_doc',
    get_lang('E-mail on assignments submission by students'),
    get_lang('Enable'),
    1
);
$group[] = $form->createElement(
    'radio',
    'email_alert_manager_on_new_doc',
    null,
    get_lang('Only for teachers'),
    3
);
$group[] = $form->createElement(
    'radio',
    'email_alert_manager_on_new_doc',
    null,
    get_lang('Only for students'),
    2
);
$group[] = $form->createElement(
    'radio',
    'email_alert_manager_on_new_doc',
    null,
    get_lang('Disable'),
    0
);
$globalGroup[get_lang('E-mail on assignments submission by students')] = $group;

$group = [];
$group[] = $form->createElement(
    'radio',
    'email_alert_on_new_doc_dropbox',
    get_lang('E-mail users on dropbox file reception'),
    get_lang('Enable'),
    1
);
$group[] = $form->createElement(
    'radio',
    'email_alert_on_new_doc_dropbox',
    null,
    get_lang('Disable'),
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
    ActionIcon::SEND_MESSAGE,
    false
);

// User rights
$group = [];
$group[] = $form->createElement(
    'radio',
    'allow_user_edit_agenda',
    get_lang('Allow learners to edit the agenda'),
    get_lang('Enable'),
    1
);
$group[] = $form->createElement(
    'radio',
    'allow_user_edit_agenda',
    null,
    get_lang('Disable'),
    0
);

$group2 = [];
$group2[] = $form->createElement(
    'radio',
    'allow_user_edit_announcement',
    get_lang('Allow learners to edit announcements'),
    get_lang('Enable'),
    1
);
$group2[] = $form->createElement(
    'radio',
    'allow_user_edit_announcement',
    null,
    get_lang('Disable'),
    0
);

$group3 = [];
$group3[] = $form->createElement(
    'radio',
    'allow_user_image_forum',
    get_lang('User picture in forum'),
    get_lang('Enable'),
    1
);
$group3[] = $form->createElement(
    'radio',
    'allow_user_image_forum',
    null,
    get_lang('Disable'),
    0
);

$group4 = [];
$group4[] = $form->createElement(
    'radio',
    'allow_user_view_user_list',
    get_lang('Allow user view user list'),
    get_lang('Enable'),
    1
);
$group4[] = $form->createElement(
    'radio',
    'allow_user_view_user_list',
    null,
    get_lang('Disable'),
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
    ToolIcon::MEMBER,
    false
);

// Chat settings
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
    ToolIcon::CHAT,
    false
);

// Learning path settings
$globalGroup = [];

if ('true' === api_get_setting('allow_course_theme')) {
    // Allow theme into Learning path
    $group = [];
    $group[] = $form->createElement(
        'radio',
        'allow_learning_path_theme',
        get_lang('Enable course themes'),
        get_lang('Enable'),
        1
    );
    $group[] = $form->createElement(
        'radio',
        'allow_learning_path_theme',
        null,
        get_lang('Disable'),
        0
    );

    $globalGroup[get_lang('Enable course themes')] = $group;
}

$allowLPReturnLink = api_get_setting('lp.allow_lp_return_link');
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
            get_lang('My sessions'),
            4
        ),
        $form->createElement(
            'radio',
            'lp_return_link',
            null,
            get_lang('Redirect to portal home'),
            3
        ),
    ];
    $globalGroup[get_lang('Learning path return link')] = $group;
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
            get_lang('Test invisible in session'),
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

    $globalGroup[get_lang('Test invisible in session')] = $group;
}

if ($isEditable) {
    $myButton = $form->addButtonSave(get_lang('Save settings'), 'submit_save', true);
    $globalGroup[] = $myButton;
} else {
    // Is it allowed to edit the course settings?
    if (!$isEditable) {
        $disabled_output = 'disabled';
    }
    $form->freeze();
}

$form->addPanelOption(
    'config_lp',
    get_lang('Learning path settings'),
    $globalGroup,
    ToolIcon::LP,
    false
);

// START THEMATIC
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
    get_lang('Display information about the next incomplete and the last completed topic'),
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
    ToolIcon::COURSE_PROGRESS,
    false
);

// Certificates
if ('true' === api_get_setting('certificate.allow_public_certificates')) {
    $group = [];
    $group[] = $form->createElement(
        'radio',
        'allow_public_certificates',
        get_lang('Learner certificates are public'),
        get_lang('Yes'),
        1
    );
    $group[] = $form->createElement(
        'radio',
        'allow_public_certificates',
        null,
        get_lang('No'),
        0
    );
    $myButton = $form->addButtonSave(get_lang('Save settings'), 'submit_save', true);

    $globalGroup = [
        get_lang('Learner certificates are public') => $group,
        '' => $myButton,
    ];

    $form->addPanelOption(
        'certificate',
        get_lang('Certificates'),
        $globalGroup,
        ObjectIcon::CERTIFICATE,
        false
    );
}

// Forum settings
$myButton = $form->addButtonSave(get_lang('Save settings'), 'submit_save', true);

$groupNotification = [
    $form->createElement('radio', 'hide_forum_notifications', null, get_lang('Yes'), 1),
    $form->createElement('radio', 'hide_forum_notifications', null, get_lang('No'), 2),
];

$addUsers = [
    $form->createElement('radio', 'subscribe_users_to_forum_notifications', null, get_lang('Yes'), 1),
    $form->createElement('radio', 'subscribe_users_to_forum_notifications', null, get_lang('No'), 2),
];

$globalGroup = [
    get_lang('Hide forum notifications') => $groupNotification,
    get_lang('Subscribe automatically all users to all forum notifications') => $addUsers,
    '' => $myButton,
];

$form->addPanelOption(
    'forum',
    get_lang('Forum'),
    $globalGroup,
    ToolIcon::FORUM,
    false
);

// Assignments / Student publications
$group = [
    $form->createElement('radio', 'show_score', null, get_lang('New documents are visible for all users'), 0),
    $form->createElement(
        'radio',
        'show_score',
        null,
        get_lang('New documents are only visible for the teacher(s)'),
        1
    ),
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
    ToolIcon::ASSIGNMENT,
    false
);

// Auto-launch settings
$globalGroup = [];
$group = [];

// Auto-launch for documents
$group[] = $form->createElement(
    'radio',
    'auto_launch_option',
    get_lang('Auto-launch for documents'),
    get_lang('Redirect to the document list'),
    'enable_document_auto_launch'
);

// Auto-launch for learning paths
$group[] = $form->createElement(
    'radio',
    'auto_launch_option',
    get_lang('Enable learning path auto-launch'),
    get_lang('Redirect to a selected learning path'),
    'enable_lp_auto_launch'
);
$group[] = $form->createElement(
    'radio',
    'auto_launch_option',
    get_lang('Enable learning path auto-launch'),
    get_lang('Redirect to the learning paths list'),
    'enable_lp_auto_launch_list'
);

// Auto-launch for exercises
$group[] = $form->createElement(
    'radio',
    'auto_launch_option',
    get_lang('Auto-launch for exercises'),
    get_lang('Redirect to the selected exercise'),
    'enable_exercise_auto_launch'
);
$group[] = $form->createElement(
    'radio',
    'auto_launch_option',
    get_lang('Auto-launch for exercises'),
    get_lang('Redirect to the exercises list'),
    'enable_exercise_auto_launch_list'
);

// Auto-launch for forums
$group[] = $form->createElement(
    'radio',
    'auto_launch_option',
    get_lang('Auto-launch for forums'),
    get_lang('Redirect to forums list'),
    'enable_forum_auto_launch'
);

// Disable all auto-launch options
$group[] = $form->createElement(
    'radio',
    'auto_launch_option',
    get_lang('Disable'),
    get_lang('Disable'),
    'disable_auto_launch'
);

$myButton = $form->addButtonSave(get_lang('Save settings'), 'submit_save', true);

$globalGroup = [
    get_lang('Auto-launch') => $group,
    '' => $myButton,
];

$form->addPanelOption(
    'autolaunch',
    get_lang('Autolaunch settings'),
    $globalGroup,
    ToolIcon::COURSE,
    false
);

// AI helpers
if ($enableAiHelpers) {
    $globalAiGroup = [];

    foreach ($aiOptions as $key => $label) {
        if (api_get_setting("ai_helpers.$key") === 'true') {
            $aiGroup = [];
            $aiGroup[] = $form->createElement('radio', $key, null, get_lang('Yes'), 'true');
            $aiGroup[] = $form->createElement('radio', $key, null, get_lang('No'), 'false');

            $globalAiGroup[get_lang($label)] = $aiGroup;
        }
    }

    if (!empty($globalAiGroup)) {
        $globalAiGroup[] = $form->addButtonSave(get_lang('Save settings'), 'submit_save', true);

        $form->addPanelOption(
            'ai_helpers',
            get_lang('AI helpers'),
            $globalAiGroup,
            ToolIcon::ROBOT,
            false
        );
    }
}

// External tools (LTI) info
$button = Display::toolbarButton(
    get_lang('External tools (LTI)'),
    $router->generate('chamilo_lti_configure', ['cid' => $courseId]).'?'.api_get_cidreq(),
    'cog',
    'primary'
);
$html = [
    $form->createElement(
        'html',
        '<p>'.get_lang('LTI tools allow your students to access external tools directly from your course. They can be enabled in your course if configured at the platform level.').'</p>'.$button
    ),
];

// ---------------------------------------------------------------------
// Default values
// When a course setting is not stored yet, api_get_course_setting() returns -1.
// We still want radios/selects to have a clear default selected in the UI.
// This does NOT write anything to the database; it only affects form defaults.
// ---------------------------------------------------------------------
$defaultCourseSettings = [
    // Documents (only used if the corresponding platform setting enables these fields)
    'documents_default_visibility' => 'visible',
    'show_system_folders' => 1,

    // E-mail notifications (default to disabled)
    'email_alert_to_teacher_on_new_user_in_course' => 0,
    'email_alert_student_on_manual_subscription' => 0,
    'email_alert_students_on_new_homework' => 0,
    'email_alert_manager_on_new_doc' => 0,
    'email_alert_on_new_doc_dropbox' => 0,
    'email_to_teachers_on_new_work_feedback' => 2, // Yes=1, No=2

    // User rights (default to disabled)
    'allow_user_edit_agenda' => 0,
    'allow_user_edit_announcement' => 0,
    'allow_user_image_forum' => 0,
    'allow_user_view_user_list' => 0,

    // Chat settings
    'allow_open_chat_window' => 0,

    // Learning path settings
    'allow_learning_path_theme' => 0,
    'lp_return_link' => 0, // Redirect to Course home
    'exercise_invisible_in_session' => 0,

    // Thematic
    'display_info_advance_inside_homecourse' => 0,

    // Certificates
    'allow_public_certificates' => 0,

    // Forum settings (Yes=1, No=2)
    'hide_forum_notifications' => 2,
    'subscribe_users_to_forum_notifications' => 2,

    // Assignments / Student publications
    'show_score' => 0,
    'student_delete_own_publication' => 0,

    // Auto-launch (UI helper radio)
    'auto_launch_option' => 'disable_auto_launch',
    'show_course_in_user_language' => 2,
    'email_alert_manager_on_new_quiz' => [],
];

// Set default values
$values = [];
$values['title'] = $courseEntity->getTitle();
$values['course_language'] = $courseEntity->getCourseLanguage();
$values['department_name'] = $courseEntity->getDepartmentName();
$values['department_url'] = $courseEntity->getDepartmentUrl();
$values['visibility'] = $courseEntity->getVisibility();
$values['subscribe'] = $courseEntity->getSubscribe();
$values['unsubscribe'] = $courseEntity->getUnsubscribe();
$values['course_registration_password'] = $courseEntity->getRegistrationCode();
$values['legal'] = $courseEntity->getLegal();
$values['activate_legal'] = $courseEntity->getActivateLegal();
$values['show_score'] = $_course['show_score'] ?? 0;

$courseSettings = CourseManager::getCourseSettingVariables();
foreach ($courseSettings as $setting) {
    $result = api_get_course_setting($setting);

    // Some settings can legitimately be arrays (e.g. multi-checkbox values).
    // Casting arrays to string triggers "Array to string conversion".
    if (\is_array($result)) {
        $values[$setting] = $result;
        continue;
    }

    // Stored setting: use it (api_get_course_setting returns -1 when not stored yet).
    if ($result !== null && '-1' !== (string) $result) {
        $values[$setting] = $result;
        continue;
    }

    // Not stored yet: fallback to UI defaults when available.
    if (!array_key_exists($setting, $values) && array_key_exists($setting, $defaultCourseSettings)) {
        $values[$setting] = $defaultCourseSettings[$setting];
    }
}

// Make sure new settings have a clear default value
if (!isset($values['student_delete_own_publication'])) {
    $values['student_delete_own_publication'] = 0;
}

if (!isset($values['email_alert_student_on_manual_subscription'])) {
    $values['email_alert_student_on_manual_subscription'] = 0;
}

// Auto-launch: compute the selected UI option from stored flags
$documentAutoLaunch = api_get_course_setting('enable_document_auto_launch');
$lpAutoLaunch = api_get_course_setting('enable_lp_auto_launch');
$exerciseAutoLaunch = api_get_course_setting('enable_exercise_auto_launch');
$forumAutoLaunch = api_get_course_setting('enable_forum_auto_launch');

$defaultAutoLaunchOption = 'disable_auto_launch';
if ($documentAutoLaunch == 1) {
    $defaultAutoLaunchOption = 'enable_document_auto_launch';
} elseif ($lpAutoLaunch == 1) {
    $defaultAutoLaunchOption = 'enable_lp_auto_launch';
} elseif ($lpAutoLaunch == 2) {
    $defaultAutoLaunchOption = 'enable_lp_auto_launch_list';
} elseif ($exerciseAutoLaunch == 1) {
    $defaultAutoLaunchOption = 'enable_exercise_auto_launch';
} elseif ($exerciseAutoLaunch == 2) {
    $defaultAutoLaunchOption = 'enable_exercise_auto_launch_list';
} elseif ($forumAutoLaunch == 1) {
    $defaultAutoLaunchOption = 'enable_forum_auto_launch';
}

$values['auto_launch_option'] = $defaultAutoLaunchOption;

// AI helpers: api_get_course_setting() can also return -1 for new courses.
// We want radios to default to "No" ("false") to avoid an empty state.
if ($enableAiHelpers) {
    foreach ($aiOptions as $key => $label) {
        $v = api_get_course_setting($key);
        $values[$key] = ('-1' === (string) $v || $v === null || $v === '') ? 'false' : (string) $v;
    }
}

$form->setDefaults($values);

// JS helpers: move legacy main block into the "Course settings" panel
$htmlHeadXtra[] = '
<script>
document.addEventListener("DOMContentLoaded", function () {
    var form = document.getElementById("update_course");
    if (!form) {
        return;
    }

    // Move legacy main settings into the "Course settings" panel body
    var start = document.getElementById("course-main-settings-legacy-start");
    var end = document.getElementById("course-main-settings-legacy-end");
    var panelBody = document.getElementById("collapse_course_main");

    if (start && end && panelBody) {
        var nodesToMove = [];
        var node = start.nextSibling;

        // Collect nodes between start and end (exclusive)
        while (node && node !== end) {
            var next = node.nextSibling;
            nodesToMove.push(node);
            node = next;
        }

        // Move nodes into the panel body
        nodesToMove.forEach(function (n) {
            panelBody.appendChild(n);
        });

        // Remove markers to keep DOM clean
        if (start.parentNode) {
            start.parentNode.removeChild(start);
        }
        if (end.parentNode) {
            end.parentNode.removeChild(end);
        }
    }
});
</script>
';
$htmlHeadXtra[] = '
<style>
    .course-picture-preview-row {
        margin-top: 0.5rem;
    }

    .course-picture-preview img {
        max-width: 320px;
        height: auto;
        display: block;
    }

    .course-picture-preview .help-block {
        margin-bottom: 0.25rem;
    }
    .field-checkbox, .field-radiobutton {
      margin-top: 10px;
    }
</style>
';

// Handle form submission
if ($form->validate()) {
    $updateValues = $form->exportValues();

    $updateValues['visibility'] = isset($updateValues['visibility'])
        ? (int) $updateValues['visibility']
        : $courseEntity->getVisibility();

    if ($courseVisibilityAdminsOnly && !api_is_platform_admin()) {
        // Do not allow non-platform admins to change course visibility even if they tamper with the POST payload.
        $updateValues['visibility'] = (int) $courseEntity->getVisibility();
    }

    $updateValues['subscribe'] = isset($updateValues['subscribe'])
        ? (int) $updateValues['subscribe']
        : $courseEntity->getSubscribe();

    $updateValues['unsubscribe'] = isset($updateValues['unsubscribe'])
        ? (int) $updateValues['unsubscribe']
        : $courseEntity->getUnsubscribe();

    $updateValues['legal'] = array_key_exists('legal', $updateValues)
        ? $updateValues['legal']
        : $courseEntity->getLegal();

    $updateValues['course_registration_password'] =
        array_key_exists('course_registration_password', $updateValues)
            ? $updateValues['course_registration_password']
            : $courseEntity->getRegistrationCode();

    $visibility = $updateValues['visibility'] ?? $courseEntity->getVisibility();
    $deletePicture = !empty($updateValues['delete_picture'] ?? null);

    $request = Container::getRequest();
    /** @var UploadedFile|null $uploadFile */
    $uploadFile = $request->files->get('picture');

    // Handle course picture upload / update
    if (null !== $uploadFile) {
        // Replace existing illustration with the new one
        if ($illustrationRepo->hasIllustration($courseEntity)) {
            $illustrationRepo->deleteIllustration($courseEntity);
        }

        $file = $illustrationRepo->addIllustration(
            $courseEntity,
            api_get_user_entity(api_get_user_id()),
            $uploadFile
        );

        if ($file) {
            // Crop info is provided by the crop widget (hidden field)
            if (!empty($updateValues['picture_crop_result'])) {
                $file->setCrop($updateValues['picture_crop_result']);
            }

            $em->persist($file);
            $em->flush();

            Event::addEvent(
                LOG_COURSE_SETTINGS_CHANGED,
                'course_picture',
                $uploadFile->getFilename()
            );
        }
    }

    $visibility = $updateValues['visibility'] ?? '';
    $deletePicture = $updateValues['delete_picture'] ?? '';

    if ($deletePicture) {
        if ($illustrationRepo->hasIllustration($courseEntity)) {
            $illustrationRepo->deleteIllustration($courseEntity);
        }
    }

    $access_url_id = api_get_current_access_url_id();

    $limitCourses = get_hosting_limit($access_url_id, 'active_courses');
    if ($limitCourses !== null && $limitCourses > 0) {
        $courseInfo = api_get_course_info_by_id($courseId);

        if (COURSE_VISIBILITY_HIDDEN == $courseInfo['visibility'] && $visibility != $courseInfo['visibility']) {
            $num = CourseManager::countActiveCourses($access_url_id);
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

    $activeLegal = $updateValues['activate_legal'] ?? 0;

    // Normalize auto-launch flags from the single radio value
    $autoLaunchOption = $updateValues['auto_launch_option'] ?? 'disable_auto_launch';
    $updateValues['enable_document_auto_launch'] = 0;
    $updateValues['enable_lp_auto_launch'] = 0;
    $updateValues['enable_lp_auto_launch_list'] = 0;
    $updateValues['enable_exercise_auto_launch'] = 0;
    $updateValues['enable_exercise_auto_launch_list'] = 0;
    $updateValues['enable_forum_auto_launch'] = 0;

    switch ($autoLaunchOption) {
        case 'enable_document_auto_launch':
            $updateValues['enable_document_auto_launch'] = 1;
            break;
        case 'enable_lp_auto_launch':
            $updateValues['enable_lp_auto_launch'] = 1;
            break;
        case 'enable_lp_auto_launch_list':
            $updateValues['enable_lp_auto_launch'] = 2;
            break;
        case 'enable_exercise_auto_launch':
            $updateValues['enable_exercise_auto_launch'] = 1;
            break;
        case 'enable_exercise_auto_launch_list':
            $updateValues['enable_exercise_auto_launch'] = 2;
            break;
        case 'enable_forum_auto_launch':
            $updateValues['enable_forum_auto_launch'] = 1;
            break;
        case 'disable_auto_launch':
        default:
            break;
    }

    // Persist main course entity
    $courseEntity
        ->setTitle($updateValues['title'])
        ->setCourseLanguage($updateValues['course_language'])
        ->setDepartmentName($updateValues['department_name'])
        ->setDepartmentUrl($updateValues['department_url'])
        ->setVisibility($updateValues['visibility'])
        ->setSubscribe($updateValues['subscribe'])
        ->setUnsubscribe($updateValues['unsubscribe'])
        ->setLegal($updateValues['legal'])
        ->setActivateLegal($activeLegal)
        ->setRegistrationCode($updateValues['course_registration_password']);

    $em->persist($courseEntity);
    $em->flush();

    // Persist AI helper per-course settings
    if ($enableAiHelpers) {
        foreach ($aiOptions as $key => $label) {
            if (isset($updateValues[$key])) {
                CourseManager::saveCourseConfigurationSetting($key, $updateValues[$key], api_get_course_int_id());
            }
        }
    }

    // Insert/Update course_settings table
    foreach ($courseSettings as $setting) {
        $value = $updateValues[$setting] ?? null;
        CourseManager::saveCourseConfigurationSetting(
            $setting,
            $value,
            api_get_course_int_id()
        );
    }

    // Update course extra fields
    $courseFieldValue = new ExtraFieldValue('course');
    $extraValues = $form->getSubmitValues();
    $extraValues['item_id'] = $courseId;
    $courseFieldValue->saveFieldValues($extraValues, true);

    //$appPlugin->saveCourseSettingsHook($updateValues);
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

// Template rendering
$tpl = new Template($nameTools);

Display::display_header($nameTools, 'Settings');
$tpl->assign('course_settings', $form->returnForm());
$courseInfoLayout = $tpl->get_template('course_info/index.html.twig');
$content = $tpl->fetch($courseInfoLayout);
echo $content;

Display::display_footer();
