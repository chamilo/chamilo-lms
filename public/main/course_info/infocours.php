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
$imsLtiPluginEntity = Container::getPluginRepository()->findOneByTitle('ImsLti');
$currentAccessUrl = Container::getAccessUrlUtil()->getCurrent();
$imsLtiPluginConfiguration = $imsLtiPluginEntity?->getConfigurationsByAccessUrl($currentAccessUrl);

$isImsLtiEnabled = $imsLtiPluginEntity
    && $imsLtiPluginEntity->isInstalled()
    && $imsLtiPluginConfiguration
    && $imsLtiPluginConfiguration->isActive();

$courseHomeNotifyPluginEntity = Container::getPluginRepository()->findOneByTitle('CourseHomeNotify');
$courseHomeNotifyPluginConfiguration = $courseHomeNotifyPluginEntity?->getConfigurationsByAccessUrl($currentAccessUrl);

$isCourseHomeNotifyEnabled = $courseHomeNotifyPluginEntity
    && $courseHomeNotifyPluginEntity->isInstalled()
    && $courseHomeNotifyPluginConfiguration
    && $courseHomeNotifyPluginConfiguration->isActive();

$courseHomeNotifyPlugin = null;
$courseHomeNotifyPluginPath = api_get_path(SYS_PLUGIN_PATH).'CourseHomeNotify/CourseHomeNotifyPlugin.php';

if (is_file($courseHomeNotifyPluginPath)) {
    require_once $courseHomeNotifyPluginPath;

    if (class_exists('CourseHomeNotifyPlugin')) {
        $courseHomeNotifyPlugin = CourseHomeNotifyPlugin::create();
    }
}

$courseLegalPluginEntity = Container::getPluginRepository()->findOneByTitle('CourseLegal');
$courseLegalPluginConfiguration = $courseLegalPluginEntity?->getConfigurationsByAccessUrl($currentAccessUrl);

$isCourseLegalEnabled = $courseLegalPluginEntity
    && $courseLegalPluginEntity->isInstalled()
    && $courseLegalPluginConfiguration
    && $courseLegalPluginConfiguration->isActive();

$courseLegalPlugin = null;
$courseLegalPluginPath = api_get_path(SYS_PLUGIN_PATH).'CourseLegal/CourseLegalPlugin.php';

if (is_file($courseLegalPluginPath)) {
    require_once $courseLegalPluginPath;

    if (class_exists('CourseLegalPlugin')) {
        $courseLegalPlugin = CourseLegalPlugin::create();
    }
}

$courseBlockSettings = [
    'course_block_pre_footer',
    'course_block_footer_left',
    'course_block_footer_center',
    'course_block_footer_right',
];

$courseBlockPluginEntity = Container::getPluginRepository()->findOneByTitle('CourseBlock');
$courseBlockPluginConfiguration = $courseBlockPluginEntity?->getConfigurationsByAccessUrl($currentAccessUrl);

$isCourseBlockEnabled = $courseBlockPluginEntity
    && $courseBlockPluginEntity->isInstalled()
    && $courseBlockPluginConfiguration
    && $courseBlockPluginConfiguration->isActive();

$courseBlockPlugin = null;
$courseBlockPluginPath = api_get_path(SYS_PLUGIN_PATH).'CourseBlock/CourseBlockPlugin.php';

if (is_file($courseBlockPluginPath)) {
    require_once $courseBlockPluginPath;

    if (class_exists('CourseBlockPlugin')) {
        $courseBlockPlugin = CourseBlockPlugin::create();
    }
}

$courseBlockAppPlugin = null;

if ($isCourseBlockEnabled) {
    $courseBlockAppPlugin = AppPlugin::getInstance();
}

$customCertificateSettings = [
    'customcertificate_course_enable',
    'use_certificate_default',
];

$customCertificatePluginEntity = Container::getPluginRepository()->findOneByTitle('CustomCertificate');
$customCertificatePluginConfiguration = $customCertificatePluginEntity?->getConfigurationsByAccessUrl($currentAccessUrl);

$isCustomCertificateEnabled = $customCertificatePluginEntity
    && $customCertificatePluginEntity->isInstalled()
    && $customCertificatePluginConfiguration
    && $customCertificatePluginConfiguration->isActive();

$customCertificatePlugin = null;
$customCertificatePluginPath = api_get_path(SYS_PLUGIN_PATH).'CustomCertificate/src/CustomCertificatePlugin.php';

if ($isCustomCertificateEnabled && is_file($customCertificatePluginPath)) {
    require_once $customCertificatePluginPath;

    if (class_exists('CustomCertificatePlugin')) {
        $customCertificatePlugin = CustomCertificatePlugin::create();
    }
}

$customCertificateAppPlugin = null;

if ($isCustomCertificateEnabled) {
    $customCertificateAppPlugin = AppPlugin::getInstance();
}

if (!function_exists('customcertificate_save_course_setting')) {
    function customcertificate_save_course_setting(string $variable, int $value, int $courseId): void
    {
        $courseId = (int) $courseId;

        if ($courseId <= 0 || !\in_array($variable, ['customcertificate_course_enable', 'use_certificate_default'], true)) {
            return;
        }

        $courseSettingTable = Database::get_course_table(TABLE_COURSE_SETTING);
        $escapedVariable = Database::escape_string($variable);
        $escapedValue = Database::escape_string((string) $value);
        $escapedTitle = Database::escape_string($variable);

        $sql = "SELECT variable
                FROM $courseSettingTable
                WHERE c_id = $courseId AND variable = '$escapedVariable'
                LIMIT 1";
        $result = Database::query($sql);

        if (Database::num_rows($result) > 0) {
            $sql = "UPDATE $courseSettingTable
                    SET value = '$escapedValue'
                    WHERE c_id = $courseId AND variable = '$escapedVariable'";
            Database::query($sql);

            return;
        }

        $sql = "INSERT INTO $courseSettingTable (c_id, variable, value, title)
                VALUES ($courseId, '$escapedVariable', '$escapedValue', '$escapedTitle')";
        Database::query($sql);
    }
}

if (!function_exists('customcertificate_save_course_settings_mode')) {
    function customcertificate_save_course_settings_mode(string $mode, int $courseId): void
    {
        $courseValue = 0;
        $defaultValue = 0;

        if ('course' === $mode) {
            $courseValue = 1;
        }

        if ('default' === $mode) {
            $defaultValue = 1;
        }

        customcertificate_save_course_setting('customcertificate_course_enable', $courseValue, $courseId);
        customcertificate_save_course_setting('use_certificate_default', $defaultValue, $courseId);
    }
}

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

// Teachers/course admins won't be able to change the visibility or subscription when this is enabled.
// Platform admins can still change both options.
$canChangeCourseVisibility = !$courseVisibilityAdminsOnly || api_is_platform_admin();
$canChangeCourseSubscription = $canChangeCourseVisibility;

// Build the form
$form = new FormValidator(
    'update_course',
    'post',
    api_get_self().'?'.api_get_cidreq()
);

// --- Main course settings fields (legacy block) ---
$form->addStartPanel(
    'course_main',
    get_lang('Course settings'),
    true,
    ActionIcon::INFORMATION
);
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
$form->addGroup($group, null, [get_lang('Show course in user\'s language')]);

$form->addText('department_name', get_lang('Department'), false);
$form->applyFilter('department_name', 'html_filter');
$form->applyFilter('department_name', 'trim');

$form->addText('department_url', get_lang('Department URL'), false);
$form->applyFilter('department_url', 'html_filter');

// Room.
$em = Database::getManager();
$roomCount = $em->getRepository(\Chamilo\CoreBundle\Entity\Room::class)->count([]);
if ($roomCount > 0) {
    $roomOptions = [];
    $courseEntity = api_get_course_entity();
    if ($courseEntity && $courseEntity->getRoom()) {
        $currentRoom = $courseEntity->getRoom();
        $branch = $currentRoom->getBranch();
        $roomLabel = $branch ? $branch->getTitle().' - '.$currentRoom->getTitle() : $currentRoom->getTitle();
        $roomOptions[$currentRoom->getId()] = $roomLabel;
    }
    $form->addSelectAjax(
        'room_id',
        get_lang('Default room'),
        $roomOptions,
        [
            'url' => api_get_path(WEB_AJAX_PATH).'course.ajax.php?a=search_room',
            'placeholder' => get_lang('Select'),
        ]
    );
}

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

// Course picture preview and upload
$hasCustomCoursePicture = $illustrationRepo->hasIllustration($courseEntity);
$illustrationUrl = $illustrationRepo->getIllustrationUrl($courseEntity, 'course_picture_medium');
$allowed_picture_types = api_get_supported_image_extensions(false);
$acceptedPictureTypes = implode(
    ',',
    array_map(
        static fn (string $extension): string => '.'.$extension,
        $allowed_picture_types
    )
);

$pictureStatusLabel = $hasCustomCoursePicture ? get_lang('Current picture') : get_lang('Default');
$pictureStatusClasses = $hasCustomCoursePicture
    ? 'bg-success text-white'
    : 'bg-gray-20 text-gray-90';

$deleteCoursePictureButton = '';
if ($hasCustomCoursePicture) {
    $deleteCoursePictureButton = '
        <button
            type="submit"
            name="delete_picture"
            value="1"
            class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-danger bg-white text-danger transition hover:bg-support-6"
            title="'.get_lang('Delete picture').'"
            aria-label="'.get_lang('Delete picture').'"
        >
            '.Display::getMdiIcon(
            ActionIcon::DELETE,
            'ch-tool-icon text-danger',
            null,
            ICON_SIZE_SMALL,
            get_lang('Delete picture')
        ).'
        </button>';
}

$form->addHtml('
    <div id="course-picture-card" class="my-6 rounded-2xl border border-gray-25 bg-white p-4 shadow-sm">
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-4 lg:items-start">
            <div class="rounded-2xl border border-gray-25 bg-support-2 p-4 lg:col-span-3">
                <div class="mb-3 flex items-center gap-2">
                    <span class="mdi mdi-image-outline ch-tool-icon"></span>
                    <h4 class="m-0 text-body-1 font-semibold text-gray-90">'.get_lang('Course picture').'</h4>
                </div>

                <div
                    id="course-picture-input-target"
                    class="min-h-24 rounded-2xl border border-dashed border-support-3 bg-white p-4"
                >
                    <p class="m-0 mb-2 text-body-2 font-semibold text-gray-90">'.get_lang('Add image').'</p>
                    <p class="m-0 mb-3 text-caption text-gray-50">
                        '.get_lang('Only PNG, JPG or GIF images allowed').' ('.implode(', ', $allowed_picture_types).')
                    </p>
');

// Keep the file element registered directly in QuickForm.
// The surrounding HTML keeps the input in the visual card without moving it with JavaScript.
$form->addFile(
    'picture',
    '',
    [
        'id' => 'picture',
        'class' => 'picture-form block w-full cursor-pointer rounded-xl border border-gray-25 bg-white text-body-2 text-gray-90 file:mr-4 file:border-0 file:bg-primary file:px-4 file:py-2 file:text-body-2 file:font-semibold file:text-white hover:file:bg-primary-gradient',
        'crop_image' => true,
        'accept' => $acceptedPictureTypes,
    ]
);

$form->addHtml('
                </div>

                <p
                    id="course-picture-selected-file"
                    class="mt-3 hidden text-body-2 font-semibold text-primary"
                ></p>
            </div>

            <div class="rounded-2xl border border-gray-25 bg-support-2 p-3 lg:col-span-1">
                <div class="mb-3 flex items-center justify-between gap-2">
                    <span class="text-body-2 font-semibold text-gray-90">'.get_lang('Preview').'</span>
                    <span class="flex items-center gap-2">
                        <span
                            id="course-picture-status"
                            class="inline-flex items-center rounded-full px-3 py-1 text-caption font-semibold '.$pictureStatusClasses.'"
                        >
                            '.$pictureStatusLabel.'
                        </span>
                        '.$deleteCoursePictureButton.'
                    </span>
                </div>

                <div class="aspect-video overflow-hidden rounded-xl border border-gray-25 bg-white">
                    <img
                        id="course-picture-preview-image"
                        class="block h-full w-full object-cover"
                        src="'.htmlspecialchars($illustrationUrl, ENT_QUOTES | ENT_SUBSTITUTE).'"
                        alt="'.get_lang('Course picture').'"
                    />
                </div>
            </div>
        </div>
    </div>
');

$form->addRule(
    'picture',
    get_lang('Only PNG, JPG or GIF images allowed').' ('.implode(',', $allowed_picture_types).')',
    'filetype',
    $allowed_picture_types
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
    $form->addGroup($group, null, [get_lang('Style sheets')]);
}

$form->addElement('label', get_lang('Space Available'), format_file_size(DocumentManager::get_course_quota()));

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

$form->addEndPanel();

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
        $radio->updateAttributes([
            'disabled' => 'disabled',
        ]);
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

if (!$canChangeCourseSubscription) {
    foreach ($group2 as $radio) {
        $radio->updateAttributes([
            'disabled' => 'disabled',
        ]);
    }
}

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
$validQuizNotificationValues = array_map('strval', array_keys($emailAlerts));

$buildQuizNotificationFieldName = static function (string $itemId): string {
    return 'email_alert_manager_on_new_quiz_'.$itemId;
};

$group = [];

foreach ($emailAlerts as $itemId => $label) {
    $itemId = (string) $itemId;

    $group[] = $form->createElement(
        'checkbox',
        $buildQuizNotificationFieldName($itemId),
        null,
        $label,
        ['value' => 1]
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

if ($isCustomCertificateEnabled && null !== $customCertificatePlugin) {
    $customCertificateUrl = api_get_path(WEB_PLUGIN_PATH).'CustomCertificate/start.php?'.api_get_cidreq();
    $customCertificateCourseIsEnabled = 1 == api_get_course_setting('customcertificate_course_enable');
    $customCertificateDefaultIsEnabled = 1 == api_get_course_setting('use_certificate_default');
    $customCertificateCanOpenEditor = $customCertificateCourseIsEnabled || $customCertificateDefaultIsEnabled;

    $customCertificateMode = 'disabled';
    if ($customCertificateCourseIsEnabled) {
        $customCertificateMode = 'course';
    }
    if ($customCertificateDefaultIsEnabled) {
        $customCertificateMode = 'default';
    }

    $values['customcertificate_mode'] = $customCertificateMode;

    $customCertificateModeGroup = [
        $form->createElement(
            'radio',
            'customcertificate_mode',
            null,
            $customCertificatePlugin->get_lang('Disabled'),
            'disabled'
        ),
        $form->createElement(
            'radio',
            'customcertificate_mode',
            null,
            $customCertificatePlugin->get_lang('UseCourseCustomCertificate'),
            'course'
        ),
        $form->createElement(
            'radio',
            'customcertificate_mode',
            null,
            $customCertificatePlugin->get_lang('UseDefaultCustomCertificate'),
            'default'
        ),
    ];

    if ($customCertificateCanOpenEditor) {
        $customCertificateAction = '<a class="btn btn--primary text-white" style="color: #fff;" href="'.$customCertificateUrl.'">'
            .'<span class="mdi mdi-certificate-outline text-white" style="color: #fff;" aria-hidden="true"></span> '
            .'<span class="text-white" style="color: #fff;">'.$customCertificatePlugin->get_lang('CertificateSetting').'</span>'
            .'</a>';
    } else {
        $customCertificateAction = '<span class="btn btn--plain disabled" aria-disabled="true">'
            .'<span class="mdi mdi-certificate-outline" aria-hidden="true"></span> '
            .$customCertificatePlugin->get_lang('CertificateSetting')
            .'</span>'
            .'<p class="text-muted mt-2">'
            .$customCertificatePlugin->get_lang('SelectOneOptionBeforeOpeningEditor')
            .'</p>';
    }

    $customCertificateInfo = $form->createElement(
        'html',
        '<div class="mb-4">'
        .'<p class="mb-3">'
        .$customCertificatePlugin->get_lang('ChooseCustomCertificateModeHelp')
        .'</p>'
        .$customCertificateAction
        .'</div>'
    );

    $customCertificateSaveButton = $form->addButtonSave(get_lang('Save settings'), 'submit_save', true);

    $form->addPanelOption(
        'custom_certificate',
        $customCertificatePlugin->get_title(),
        [
            $customCertificateInfo,
            $customCertificatePlugin->get_lang('CertificateMode') => $customCertificateModeGroup,
            '' => $customCertificateSaveButton,
        ],
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

// Attendance
$group = [
    $form->createElement('radio', 'student_validate_own_attendance', null, get_lang('Yes'), 1),
    $form->createElement('radio', 'student_validate_own_attendance', null, get_lang('No'), 0),
];

$myButton = $form->addButtonSave(get_lang('Save settings'), 'submit_save', true);

$globalGroup = [
    get_lang('Enable student to validate own attendance') => $group,
    '' => $myButton,
];

$form->addPanelOption(
    'attendance',
    get_lang('Attendance'),
    $globalGroup,
    ToolIcon::ATTENDANCE,
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

if ($isImsLtiEnabled) {
    $button = Display::toolbarButton(
        get_lang('External tools (LTI)'),
        $router->generate('chamilo_lti_configure', ['cid' => $courseId]).'?'.api_get_cidreq(),
        'cog',
        'primary'
    );

    $ltiInfo = $form->createElement(
        'html',
        '<div class="mb-4">'
        .'<p class="mb-3">'
        .get_lang('LTI tools allow your students to access external tools directly from your course. They can be enabled in your course if configured at the platform level.')
        .'</p>'
        .$button
        .'</div>'
    );

    $form->addPanelOption(
        'external_tools_lti',
        get_lang('External tools (LTI)'),
        [$ltiInfo],
        ToolIcon::COURSE,
        false
    );
}

if ($isCourseBlockEnabled) {
    $courseBlockTitle = $courseBlockPlugin
        ? $courseBlockPlugin->get_title()
        : get_lang('Course block');

    $courseBlockEditorConfig = [
        'ToolbarSet' => 'Documents',
        'Width' => '100%',
        'Height' => '220',
    ];

    $form->addStartPanel(
        'course_block',
        $courseBlockTitle,
        true,
        ToolIcon::PLUGIN
    );

    $form->addHtml(
        '<div class="mb-4">'
        .'<p class="mb-3">'
        .get_lang('Add custom content blocks to course footer regions.')
        .'</p>'
        .'</div>'
    );

    $form->addHtmlEditor(
        'course_block_pre_footer',
        $courseBlockPlugin ? $courseBlockPlugin->get_lang('course_block_pre_footer') : get_lang('Before footer'),
        false,
        false,
        $courseBlockEditorConfig
    );
    $form->addHtmlEditor(
        'course_block_footer_left',
        $courseBlockPlugin ? $courseBlockPlugin->get_lang('course_block_footer_left') : get_lang('Footer left'),
        false,
        false,
        $courseBlockEditorConfig
    );
    $form->addHtmlEditor(
        'course_block_footer_center',
        $courseBlockPlugin ? $courseBlockPlugin->get_lang('course_block_footer_center') : get_lang('Footer center'),
        false,
        false,
        $courseBlockEditorConfig
    );
    $form->addHtmlEditor(
        'course_block_footer_right',
        $courseBlockPlugin ? $courseBlockPlugin->get_lang('course_block_footer_right') : get_lang('Footer right'),
        false,
        false,
        $courseBlockEditorConfig
    );

    $form->addButtonSave(get_lang('Save settings'), 'submit_save');

    $form->addEndPanel();
}

if ($isCourseHomeNotifyEnabled) {
    $courseHomeNotifyTitle = $courseHomeNotifyPlugin
        ? $courseHomeNotifyPlugin->get_title()
        : 'Notify in course home';
    $courseHomeNotifyDescription = $courseHomeNotifyPlugin
        ? $courseHomeNotifyPlugin->get_comment()
        : 'Show notifications when a user enters the course homepage.';
    $courseHomeNotifyButtonLabel = $courseHomeNotifyPlugin
        ? $courseHomeNotifyPlugin->get_lang('SetNotification')
        : 'Set one notification on home page';

    $button = Display::toolbarButton(
        $courseHomeNotifyButtonLabel,
        api_get_path(WEB_PLUGIN_PATH).'CourseHomeNotify/configure.php?'.api_get_cidreq(),
        'cog',
        'primary'
    );

    $courseHomeNotifyInfo = $form->createElement(
        'html',
        '<div class="mb-4">'
        .'<p class="mb-3">'
        .$courseHomeNotifyDescription
        .'</p>'
        .$button
        .'</div>'
    );

    $form->addPanelOption(
        'course_home_notify',
        $courseHomeNotifyTitle,
        [$courseHomeNotifyInfo],
        ToolIcon::COURSE,
        false
    );
}

if ($isCourseLegalEnabled) {
    $courseLegalTitle = $courseLegalPlugin
        ? $courseLegalPlugin->get_title()
        : 'Course legal agreement';
    $courseLegalDescription = $courseLegalPlugin
        ? $courseLegalPlugin->get_comment()
        : 'Configure a legal agreement that learners must accept before accessing the course.';

    $configureButton = Display::toolbarButton(
        $courseLegalPlugin ? $courseLegalPlugin->get_lang('CourseLegal') : 'Configure agreement',
        api_get_path(WEB_PLUGIN_PATH).'CourseLegal/start.php?'.api_get_cidreq(),
        'file-document-edit-outline',
        'primary'
    );

    $userListButton = Display::toolbarButton(
        get_lang('User list'),
        api_get_path(WEB_PLUGIN_PATH).'CourseLegal/user_list.php?'.api_get_cidreq(),
        'account-check-outline',
        'secondary'
    );

    $courseLegalInfo = $form->createElement(
        'html',
        '<div class="mb-4">'
        .'<p class="mb-3">'
        .$courseLegalDescription
        .'</p>'
        .'<div class="flex flex-wrap gap-2">'
        .$configureButton
        .$userListButton
        .'</div>'
        .'</div>'
    );

    $form->addPanelOption(
        'course_legal',
        $courseLegalTitle,
        [$courseLegalInfo],
        ToolIcon::COURSE,
        false
    );
}

$normalizeQuizNotificationSetting = static function ($value) use ($validQuizNotificationValues): array {
    if (\is_array($value)) {
        $items = [];

        foreach ($value as $item) {
            $normalizedValue = trim((string) $item);

            if ('' !== $normalizedValue && \in_array($normalizedValue, $validQuizNotificationValues, true)) {
                $items[] = $normalizedValue;
            }
        }

        return array_values(array_unique($items));
    }

    if (null === $value || '-1' === (string) $value) {
        return [];
    }

    $rawValue = trim((string) $value);

    if ('' === $rawValue) {
        return [];
    }

    $items = array_map('trim', explode(',', $rawValue));

    $items = array_values(
        array_filter(
            array_map('strval', $items),
            static function (string $item) use ($validQuizNotificationValues): bool {
                return '' !== $item && \in_array($item, $validQuizNotificationValues, true);
            }
        )
    );

    return array_values(array_unique($items));
};

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

    // CustomCertificate plugin
    'customcertificate_course_enable' => 0,
    'use_certificate_default' => 0,

    // Forum settings (Yes=1, No=2)
    'hide_forum_notifications' => 2,
    'subscribe_users_to_forum_notifications' => 2,

    // Assignments / Student publications
    'show_score' => 0,
    'student_delete_own_publication' => 0,

    // Attendance
    'student_validate_own_attendance' => 0,

    // Auto-launch (UI helper radio)
    'auto_launch_option' => 'disable_auto_launch',
    'show_course_in_user_language' => 2,
    'email_alert_manager_on_new_quiz' => [],
];

// Set default values
$values = [];
$values['title'] = $courseEntity->getTitle();
$values['course_language'] = $courseEntity->getCourseLanguage();
$values['room_id'] = $courseEntity->getRoom() ? $courseEntity->getRoom()->getId() : null;
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

if ($isCourseBlockEnabled) {
    $courseSettings = array_values(array_unique(array_merge($courseSettings, $courseBlockSettings)));
}

if ($isCustomCertificateEnabled) {
    $courseSettings = array_values(array_unique(array_merge($courseSettings, $customCertificateSettings)));
}

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

$selectedQuizNotifications = $normalizeQuizNotificationSetting(
    api_get_course_setting('email_alert_manager_on_new_quiz')
);

foreach ($validQuizNotificationValues as $itemId) {
    $values[$buildQuizNotificationFieldName($itemId)] = \in_array(
        $itemId,
        $selectedQuizNotifications,
        true
    ) ? 1 : 0;
}

// Make sure new settings have a clear default value
if (!isset($values['student_delete_own_publication'])) {
    $values['student_delete_own_publication'] = 0;
}

if (!isset($values['student_validate_own_attendance'])) {
    $values['student_validate_own_attendance'] = 0;
}

if (!isset($values['email_alert_student_on_manual_subscription'])) {
    $values['email_alert_student_on_manual_subscription'] = 0;
}

if ($isCustomCertificateEnabled) {
    foreach ($customCertificateSettings as $customCertificateSetting) {
        if (!isset($values[$customCertificateSetting])) {
            $values[$customCertificateSetting] = 0;
        }
    }

    $values['customcertificate_mode'] = 'disabled';

    if (1 == $values['customcertificate_course_enable']) {
        $values['customcertificate_mode'] = 'course';
    }

    if (1 == $values['use_certificate_default']) {
        $values['customcertificate_mode'] = 'default';
    }
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

$htmlHeadXtra[] = '
<script>
document.addEventListener("DOMContentLoaded", function () {
    var card = document.getElementById("course-picture-card");
    var input = document.getElementById("picture");
    var previewImage = document.getElementById("course-picture-preview-image");
    var selectedFile = document.getElementById("course-picture-selected-file");
    var status = document.getElementById("course-picture-status");
    var currentPictureLabel = '.json_encode(get_lang('Current picture')).';
    var objectUrl = null;
    var originalImage = null;
    var lastCropValue = "";
    var syncTimer = null;

    function markAsCurrentPicture() {
        if (!status) {
            return;
        }

        status.textContent = currentPictureLabel;
        status.classList.remove(
            "bg-gray-20",
            "text-gray-90",
            "bg-success"
        );
        status.classList.add(
            "bg-primary",
            "text-white"
        );
    }

    function updateSelectedFileName(file) {
        if (!selectedFile) {
            return;
        }

        selectedFile.textContent = file.name;
        selectedFile.classList.remove("hidden");
    }

    function getCropInput() {
        return document.querySelector(
            "input[name=\"picture_crop_result\"], textarea[name=\"picture_crop_result\"], #picture_crop_result"
        );
    }

    function parseCropValue(value) {
        var crop = null;

        if (!value) {
            return null;
        }

        try {
            crop = JSON.parse(value);
        } catch (e) {
            try {
                crop = Object.fromEntries(new URLSearchParams(value));
            } catch (ignored) {
                crop = null;
            }
        }

        if (!crop) {
            return null;
        }

        var x = parseFloat(crop.x || crop.left || 0);
        var y = parseFloat(crop.y || crop.top || 0);
        var width = parseFloat(crop.width || crop.w || 0);
        var height = parseFloat(crop.height || crop.h || 0);

        if (!width || !height) {
            return null;
        }

        return {
            x: x,
            y: y,
            width: width,
            height: height
        };
    }

    function normaliseCrop(crop, image) {
        var normalised = {
            x: crop.x,
            y: crop.y,
            width: crop.width,
            height: crop.height
        };

        if (normalised.width <= 1 && normalised.height <= 1) {
            normalised.x *= image.naturalWidth;
            normalised.y *= image.naturalHeight;
            normalised.width *= image.naturalWidth;
            normalised.height *= image.naturalHeight;
        }

        normalised.x = Math.max(0, Math.min(normalised.x, image.naturalWidth - 1));
        normalised.y = Math.max(0, Math.min(normalised.y, image.naturalHeight - 1));
        normalised.width = Math.max(1, Math.min(normalised.width, image.naturalWidth - normalised.x));
        normalised.height = Math.max(1, Math.min(normalised.height, image.naturalHeight - normalised.y));

        return normalised;
    }

    function applyCropPreviewFromHiddenInput() {
        var cropInput = getCropInput();

        if (!cropInput || !cropInput.value || !originalImage || !previewImage) {
            return false;
        }

        if (cropInput.value === lastCropValue) {
            return true;
        }

        var crop = parseCropValue(cropInput.value);

        if (!crop) {
            return false;
        }

        lastCropValue = cropInput.value;
        crop = normaliseCrop(crop, originalImage);

        var canvas = document.createElement("canvas");
        var maxWidth = 640;
        var outputWidth = Math.min(maxWidth, Math.round(crop.width));
        var outputHeight = Math.max(1, Math.round(outputWidth * crop.height / crop.width));
        var context = canvas.getContext("2d");

        if (!context) {
            return false;
        }

        canvas.width = outputWidth;
        canvas.height = outputHeight;

        context.drawImage(
            originalImage,
            crop.x,
            crop.y,
            crop.width,
            crop.height,
            0,
            0,
            outputWidth,
            outputHeight
        );

        previewImage.src = canvas.toDataURL("image/jpeg", 0.92);
        markAsCurrentPicture();

        return true;
    }

    function isCropEditorElement(element) {
        return Boolean(
            element.closest(
                ".modal, .modal-dialog, .ui-dialog, .cropper-container, .cropper-wrap-box, .cropper-canvas, .jcrop-holder, [role=\"dialog\"]"
            )
        );
    }

    function getExternalGeneratedPreviewImages() {
        var form = input ? input.closest("form") : null;
        var cropInput = getCropInput();

        if (!form || !card || !cropInput || !cropInput.value) {
            return [];
        }

        return Array.prototype.filter.call(form.querySelectorAll("img"), function (image) {
            var src = image.getAttribute("src") || "";

            if (card.contains(image) || isCropEditorElement(image)) {
                return false;
            }

            if (image.classList.contains("hidden")) {
                return false;
            }

            return src.indexOf("blob:") === 0 || src.indexOf("data:image/") === 0;
        });
    }

    function hideGeneratedPreviewElement(image) {
        var current = image;
        var stop = input ? input.closest("form") : null;
        var steps = 0;

        if (isCropEditorElement(image)) {
            return;
        }

        while (
            current.parentElement &&
            current.parentElement !== stop &&
            steps < 4
        ) {
            var parent = current.parentElement;

            if (
                isCropEditorElement(parent) ||
                parent.querySelector("input[type=\"file\"], select, textarea")
            ) {
                break;
            }

            if (parent.children.length <= 2) {
                current = parent;
                steps++;
                continue;
            }

            break;
        }

        current.classList.add("hidden");
        current.setAttribute("aria-hidden", "true");
    }

    function hideExternalGeneratedPreviews() {
        getExternalGeneratedPreviewImages().forEach(hideGeneratedPreviewElement);
    }

    function syncPreviewAfterCrop() {
        var updated = applyCropPreviewFromHiddenInput();

        if (updated) {
            hideExternalGeneratedPreviews();
        }
    }

    function readImageFile(file) {
        if (objectUrl) {
            window.URL.revokeObjectURL(objectUrl);
        }

        objectUrl = window.URL.createObjectURL(file);

        originalImage = new Image();
        originalImage.onload = function () {
            if (previewImage) {
                previewImage.src = objectUrl;
            }

            syncPreviewAfterCrop();
        };
        originalImage.src = objectUrl;
    }

    if (!input || !card) {
        return;
    }

    input.addEventListener("change", function () {
        if (!input.files || !input.files[0]) {
            return;
        }

        lastCropValue = "";
        updateSelectedFileName(input.files[0]);
        readImageFile(input.files[0]);
        markAsCurrentPicture();

        if (syncTimer) {
            window.clearInterval(syncTimer);
        }

        syncTimer = window.setInterval(syncPreviewAfterCrop, 500);

        window.setTimeout(function () {
            if (syncTimer) {
                window.clearInterval(syncTimer);
                syncTimer = null;
            }

            syncPreviewAfterCrop();
        }, 30000);
    });
});
</script>
';

// Handle form submission
if ($form->validate()) {
    $updateValues = $form->exportValues();

    $request = Container::getRequest();
    $submittedValues = $request->request->all();

    $selectedQuizNotifications = [];

    foreach ($validQuizNotificationValues as $itemId) {
        $fieldName = $buildQuizNotificationFieldName($itemId);

        if (!empty($submittedValues[$fieldName])) {
            $selectedQuizNotifications[] = $itemId;
        }

        unset($updateValues[$fieldName]);
    }

    $updateValues['email_alert_manager_on_new_quiz'] = $selectedQuizNotifications;

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

    if ($courseVisibilityAdminsOnly && !api_is_platform_admin()) {
        $updateValues['subscribe'] = (int) $courseEntity->getSubscribe();
    }

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

    $request = Container::getRequest();
    /** @var UploadedFile|null $uploadFile */
    $uploadFile = $request->files->get('picture');
    $deletePicture = !empty($submittedValues['delete_picture'] ?? null);

    // Handle course picture delete / upload.
    // Delete has priority to avoid uploading and deleting a picture in the same submit.
    if ($deletePicture) {
        if ($illustrationRepo->hasIllustration($courseEntity)) {
            $illustrationRepo->deleteIllustration($courseEntity);
        }
    } elseif (null !== $uploadFile) {
        if ($illustrationRepo->hasIllustration($courseEntity)) {
            $illustrationRepo->deleteIllustration($courseEntity);
        }

        $file = $illustrationRepo->addIllustration(
            $courseEntity,
            api_get_user_entity(api_get_user_id()),
            $uploadFile
        );

        if ($file) {
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

    if ($isCustomCertificateEnabled) {
        foreach ($customCertificateSettings as $customCertificateSetting) {
            $updateValues[$customCertificateSetting] = !empty($submittedValues[$customCertificateSetting]) ? 1 : 0;
        }

        if (!empty($updateValues['use_certificate_default'])) {
            $updateValues['customcertificate_course_enable'] = 0;
        }
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

    if (!empty($updateValues['room_id'])) {
        $room = $em->find(\Chamilo\CoreBundle\Entity\Room::class, (int) $updateValues['room_id']);
        $courseEntity->setRoom($room ?: null);
    } else {
        $courseEntity->setRoom(null);
    }

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

    if ($isCustomCertificateEnabled) {
        $customCertificateMode = $submittedValues['customcertificate_mode'] ?? 'disabled';

        if (!\in_array($customCertificateMode, ['disabled', 'course', 'default'], true)) {
            $customCertificateMode = 'disabled';
        }

        customcertificate_save_course_settings_mode($customCertificateMode, api_get_course_int_id());
    }

    // Insert/Update course_settings table
    $quizNotificationSettingSaved = false;

    foreach ($courseSettings as $setting) {
        $value = $updateValues[$setting] ?? null;

        if ($isCourseBlockEnabled && \in_array($setting, $courseBlockSettings, true)) {
            // CourseBlock fields are added as panel elements and can be missing from exportValues()
            // on some branches. Read the raw POST value to avoid saving null.
            $value = $submittedValues[$setting] ?? '';
        }

        if ($isCustomCertificateEnabled && \in_array($setting, $customCertificateSettings, true)) {
            continue;
        }

        if ('email_alert_manager_on_new_quiz' === $setting) {
            // Store checkbox values as a stable CSV string.
            $value = implode(',', $updateValues['email_alert_manager_on_new_quiz']);
            $quizNotificationSettingSaved = true;
        }

        $pluginContext = null;

        if (\in_array($setting, $courseBlockSettings, true)) {
            $pluginContext = $courseBlockAppPlugin;
        }

        if ($isCustomCertificateEnabled && \in_array($setting, $customCertificateSettings, true)) {
            $pluginContext = $customCertificateAppPlugin;
        }

        CourseManager::saveCourseConfigurationSetting(
            $setting,
            $value,
            api_get_course_int_id(),
            $pluginContext
        );
    }

    if ($isCourseBlockEnabled && null !== $courseBlockAppPlugin) {
        foreach ($courseBlockSettings as $setting) {
            $value = $submittedValues[$setting] ?? '';

            CourseManager::saveCourseConfigurationSetting(
                $setting,
                $value,
                api_get_course_int_id(),
                $courseBlockAppPlugin
            );
        }
    }

    if (!$quizNotificationSettingSaved) {
        CourseManager::saveCourseConfigurationSetting(
            'email_alert_manager_on_new_quiz',
            implode(',', $updateValues['email_alert_manager_on_new_quiz']),
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
