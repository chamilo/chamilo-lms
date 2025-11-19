<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Enums\ActionIcon;
use Chamilo\CoreBundle\Enums\ObjectIcon;
use Chamilo\CoreBundle\Enums\ToolIcon;
use Chamilo\CoreBundle\Framework\Container;
use Symfony\Component\HttpFoundation\File\UploadedFile;

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

// Build the form
$form = new FormValidator(
    'update_course',
    'post',
    api_get_self().'?'.api_get_cidreq()
);

// Course picture: current illustration + HTML fragment for preview
$imageSrc = '';

// Try to use the new illustration pipeline first.
$illustrationUrl = $illustrationRepo->getIllustrationUrl($courseEntity, 'course_picture_medium');
if (!empty($illustrationUrl)) {
    $imageSrc = $illustrationUrl;
}

$image = '<div class="rowimage">
    <label class="col-md-2 control-label">'.get_lang('Image').'</label>
    <div class="col-md-8 thumbnail-item">
        <img id="course-picture-preview" width="338" height="225" class="img-thumbnail"'.
    (!empty($imageSrc) ? ' src="'.htmlspecialchars($imageSrc, ENT_QUOTES).'"' : ' style="display:none;"').
    ' />
    </div>
</div>';

// Course settings panel (top section of the page)
$courseSettingsPanel = [];

// Title
$titleElement = $form->addText('title', get_lang('Title'), true);
$form->applyFilter('title', 'html_filter');
$form->applyFilter('title', 'trim');
$courseSettingsPanel[] = $titleElement;

// Language
$languageElement = $form->addSelectLanguage(
    'course_language',
    [get_lang('Language'), get_lang('This language will be valid for every visitor of your courses portal')]
);
$courseSettingsPanel[] = $languageElement;

// Show course in user's language
$group = [
    $form->createElement('radio', 'show_course_in_user_language', null, get_lang('Yes'), 1),
    $form->createElement('radio', 'show_course_in_user_language', null, get_lang('No'), 2),
];
$showCourseLangGroup = $form->addGroup($group, '', [get_lang('Show course in user\'s language')]);
$courseSettingsPanel[] = $showCourseLangGroup;

// Department and URL
$departmentNameElement = $form->addText('department_name', get_lang('Department'), false);
$form->applyFilter('department_name', 'html_filter');
$form->applyFilter('department_name', 'trim');
$courseSettingsPanel[] = $departmentNameElement;

$departmentUrlElement = $form->addText('department_url', get_lang('Department URL'), false);
$form->applyFilter('department_url', 'html_filter');
$courseSettingsPanel[] = $departmentUrlElement;

// Extra fields (tags, video_url, course_hours_duration, max_subscribed_students)
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

if (!empty($extra['jquery_ready_content'])) {
    $htmlHeadXtra[] = $extra['jquery_ready_content'];
}

$htmlHeadXtra[] = '
<style>
.display-panel-collapse__header {
    width: 100%;
}

.display-panel-collapse__header a {
    display: flex;
    align-items: center;
    width: 100%;
    gap: .5rem;
}

.display-panel-collapse__header a .mdi {
    line-height: 1;
}
</style>
<script>
window.addEventListener("load", function () {
    var form = document.getElementById("update_course");
    if (!form) {
        return;
    }

    var firstPanel = form.querySelector(".display-panel-collapse.field");
    if (!firstPanel) {
        return;
    }

    // Preview image element
    var picturePreview = document.getElementById("course-picture-preview");

    var node = firstPanel.previousElementSibling;
    var hiddenCount = 0;

    while (node) {
        if (node.tagName !== "SCRIPT") {
            node.classList.add("js-course-settings-legacy-header");
            node.style.display = "none";
            hiddenCount++;
        }
        node = node.previousElementSibling;
    }

    // Force open the first panel ("Course settings")
    var header = firstPanel.querySelector(".display-panel-collapse__header a");
    var body   = firstPanel.querySelector(".display-panel-collapse__collapsible");

    if (header && body) {
        header.setAttribute("aria-expanded", "true");
        header.classList.remove("mdi-chevron-down");
        header.classList.add("mdi-chevron-up");
        body.classList.add("active");
    } else {
        console.log("[COURSE SETTINGS] Header/body not found inside firstPanel");
    }

    // Ensure previously saved picture is visible
    if (picturePreview && picturePreview.getAttribute("src")) {
        picturePreview.style.display = "block";
    }

    // Live preview for course picture
    if (form && picturePreview) {
        form.addEventListener("change", function (event) {
            var input = event.target;
            if (!input || input.tagName !== "INPUT" || input.type !== "file") {
                return;
            }

            var inputName = input.getAttribute("name") || "";
            if (inputName.indexOf("picture") === -1) {
                return;
            }

            var file = input.files && input.files[0];

            if (!file) {
                picturePreview.removeAttribute("src");
                picturePreview.style.display = "none";
                return;
            }

            // Only handle image files
            if (!file.type || !file.type.match(/^image\\//)) {
                return;
            }

            var reader = new FileReader();
            reader.onload = function (e) {
                picturePreview.src = e.target.result;
                picturePreview.style.display = "block";
                picturePreview.removeAttribute("hidden");
            };
            reader.readAsDataURL(file);
        });
    } else {
        console.log("[COURSE SETTINGS] Form or preview not found, live preview disabled");
    }
});
</script>';

// Attach extra field elements to the course settings panel when available
foreach ($showOnlyTheseFields as $fieldName) {
    $elementName = 'extra_'.$fieldName;
    if (method_exists($form, 'elementExists') && $form->elementExists($elementName)) {
        $courseSettingsPanel[] = $form->getElement($elementName);
    }
}

// Course picture (upload + preview + delete)
$allowed_picture_types = api_get_supported_image_extensions(false);

$acceptPictureTypes = '';
if (!empty($allowed_picture_types) && is_array($allowed_picture_types)) {
    $acceptPictureTypes = '.'.implode(',.', $allowed_picture_types);
}

$pictureElement = $form->addFile(
    'picture',
    get_lang('Add a picture'),
    [
        'id' => 'picture',
        'class' => 'picture-form',
        'crop_image' => true,
        'accept' => !empty($acceptPictureTypes) ? $acceptPictureTypes : 'image/*',
    ]
);
$courseSettingsPanel[] = $pictureElement;

if (!empty($image)) {
    $picturePreviewElement = $form->createElement(
        'html',
        $image
    );
    $courseSettingsPanel[] = $picturePreviewElement;
}

// Validation rules for picture type
$form->addRule(
    'picture',
    get_lang('Only PNG, JPG or GIF images allowed').' ('.implode(',', $allowed_picture_types).')',
    'filetype',
    $allowed_picture_types
);

// Checkbox to delete picture
$deletePictureElement = $form->addElement(
    'checkbox',
    'delete_picture',
    null,
    get_lang('Delete picture')
);
$courseSettingsPanel[] = $deletePictureElement;

// Save button for this panel
$courseSettingsPanel[] = $form->addButtonSave(get_lang('Save settings'), 'submit_save', true);

// Register "Course settings" panel
$form->addPanelOption(
    'course_settings',
    get_lang('Course settings'),
    $courseSettingsPanel,
    ActionIcon::EDIT,
    false
);

// AI options map (used later in the AI helpers panel)
$aiOptions = [
    'learning_path_generator' => 'Enable Learning Path Generator',
    'exercise_generator' => 'Enable Exercise Generator',
    'open_answers_grader' => 'Enable Open Answers Grader',
    'tutor_chatbot' => 'Enable Tutor Chatbot',
    'task_grader' => 'Enable Task Grader',
    'content_analyser' => 'Enable Content Analyser',
    'image_generator' => 'Enable Image Generator',
];

// Course access (subscription / legal terms)
$visibilityOptions = [
    [
        'value' => COURSE_VISIBILITY_OPEN_WORLD,
        'label' => get_lang('Public - access authorized for whole world'),
    ],
    [
        'value' => COURSE_VISIBILITY_OPEN_PLATFORM,
        'label' => get_lang('Open - access allowed for registered users on the platform'),
    ],
    [
        'value' => COURSE_VISIBILITY_REGISTERED,
        'label' => get_lang('Private (available only to course members)'),
    ],
    [
        'value' => COURSE_VISIBILITY_CLOSED,
        'label' => get_lang('Closed - no access to this course'),
    ],
    [
        'value' => COURSE_VISIBILITY_HIDDEN,
        'label' => get_lang('Invisible - Completely invisible and inaccessible for all users except administrators'),
    ],
];

$visibilityGroup = [];
foreach ($visibilityOptions as $option) {
    $visibilityGroup[] = $form->createElement(
        'radio',
        'visibility',
        null,
        $option['label'],
        $option['value']
    );
}

$url = api_get_path(WEB_CODE_PATH)."auth/inscription.php?c=$course_code&e=1";
$url = Display::url($url, $url);
$label = $form->addLabel(
    get_lang('Direct link'),
    sprintf(
        get_lang(
            'If your course is public or open, you can use the direct link below to send an invitation to new users, so after registration, they will be sent directly to the course. Also, you can add the e=1 parameter to the URL, replacing "1" by an exercise ID to send them directly to a specific exam. The exercise ID can be discovered in the URL when clicking on an exercise to open it.<br/>%s'
        ),
        $url
    ),
    true
);

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
$textAreaLegal = $form->createElement(
    'textarea',
    'legal',
    get_lang('Legal agreement for this course'),
    ['rows' => 8]
);

$elements = [
    get_lang('Access to this course') => $visibilityGroup,
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
    $globalGroup[] = $myButton;
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
    // If the user is not allowed to edit, freeze the form
    $form->freeze();
}

$form->addPanelOption(
    'config_lp',
    get_lang('Learning path settings'),
    $globalGroup,
    ToolIcon::LP,
    false
);

// -----------------------------------------------------------------------------
// Thematic advance configuration
// -----------------------------------------------------------------------------
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
    if ('-1' != $result) {
        $values[$setting] = $result;
    }
}

// Make sure new settings have a clear default value
if (!isset($values['student_delete_own_publication'])) {
    $values['student_delete_own_publication'] = 0;
}

if (!isset($values['email_alert_student_on_manual_subscription'])) {
    $values['email_alert_student_on_manual_subscription'] = 0;
}

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

if ($enableAiHelpers) {
    foreach ($aiOptions as $key => $label) {
        $values[$key] = api_get_course_setting($key);
    }
}

$form->setDefaults($values);

// Handle form submission
if ($form->validate()) {
    $updateValues = $form->exportValues();

    $updateValues['visibility'] = isset($updateValues['visibility'])
        ? (int) $updateValues['visibility']
        : $courseEntity->getVisibility();

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

        $deletePicture = false;
    }

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
    $courseFieldValue->saveFieldValues($updateValues, true);

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
