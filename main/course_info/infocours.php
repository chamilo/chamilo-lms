<?php

/* For licensing terms, see /license.txt */

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
$nameTools = get_lang('ModifInfo');
api_protect_course_script(true);
api_block_anonymous_users();
$_course = api_get_course_info();

/* Constants and variables */
define('MODULE_HELP_NAME', 'Settings');

$currentCourseRepository = $_course['path'];
$is_allowedToEdit = api_is_course_admin() || api_is_platform_admin();
$course_code = api_get_course_id();
$courseId = api_get_course_int_id();

function is_settings_editable()
{
    return isset($GLOBALS['course_info_is_editable']) && $GLOBALS['course_info_is_editable'];
}

/* MAIN CODE */
if (!$is_allowedToEdit) {
    api_not_allowed(true);
}

$htmlHeadXtra[] = api_get_css_asset('cropper/dist/cropper.min.css');
$htmlHeadXtra[] = api_get_asset('cropper/dist/cropper.min.js');
$show_delete_watermark_text_message = false;
if (api_get_setting('pdf_export_watermark_by_course') === 'true') {
    if (isset($_GET['delete_watermark'])) {
        PDF::delete_watermark($course_code);
        $show_delete_watermark_text_message = true;
    }
}

$allowPortfolioTool = api_get_configuration_value('allow_portfolio_tool');

$categories = CourseCategory::getCategoriesCanBeAddedInCourse($_course['categoryCode']);

// Build the form
$form = new FormValidator(
    'update_course',
    'post',
    api_get_self().'?'.api_get_cidreq()
);

$form->addHtml('<div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">');

// COURSE SETTINGS
$form->addHtml('<div class="panel panel-default">');
$form->addHtml('
    <div class="panel-heading" role="tab" id="heading-course-settings">
        <h4 class="panel-title">
            <a role="button" data-toggle="collapse" data-parent="#accordion" href="#collapse-course-settings"
               aria-expanded="true" aria-controls="collapse-course-settings">
');
$form->addHtml(
    Display::return_icon('settings.png', get_lang('CourseSettings')).' '.get_lang('CourseSettings')
);
$form->addHtml('
            </a>
        </h4>
    </div>
');
$form->addHtml('
    <div id="collapse-course-settings" class="panel-collapse collapse in" role="tabpanel"
         aria-labelledby="heading-course-settings">
        <div class="panel-body">
');

// Display regular image
$image = '';
// Display course picture
$course_path = api_get_path(SYS_COURSE_PATH).$currentCourseRepository; // course path
if (file_exists($course_path.'/course-pic85x85.png') || file_exists($course_path.'/course-email-pic-cropped.png')) {
    $image = '<div class="row">';
    $course_web_path = api_get_path(WEB_COURSE_PATH).$currentCourseRepository; // course web path
    if (file_exists($course_path.'/course-pic85x85.png')) {
        $course_medium_image = $course_web_path.'/course-pic85x85.png?'.rand(1, 1000); // resized image
        $image .= '<label class="col-md-2 control-label">'.get_lang('Image').'</label><div class="col-md-4"><img src="'.$course_medium_image.'" /></div>';
    }
    if (file_exists($course_path.'/course-email-pic-cropped.png')) {
        $course_medium_image = $course_web_path.'/course-email-pic-cropped.png?'.rand(1, 1000); // redimensioned image
        $image .= '<label class="col-md-2 control-label">'.get_lang('EmailPicture').'</label><div class="col-md-4"><img src="'.$course_medium_image.'" /></div>';
    }
    $image .= '</div>';
}
$form->addHtml($image);
$form->addText('title', get_lang('Title'), true);
$form->applyFilter('title', 'html_filter');
$form->applyFilter('title', 'trim');

$form->addElement(
    'select',
    'category_code',
    get_lang('Fac'),
    $categories,
    ['style' => 'width:350px', 'id' => 'category_code']
);
$form->addSelectLanguage(
    'course_language',
    [get_lang('Ln'), get_lang('TipLang')]
);

$group = [
    $form->createElement('radio', 'show_course_in_user_language', null, get_lang('Yes'), 1),
    $form->createElement('radio', 'show_course_in_user_language', null, get_lang('No'), 2),
];

$form->addGroup($group, '', [get_lang('ShowCourseInUserLanguage')]);

$form->addText('department_name', get_lang('Department'), false);
$form->applyFilter('department_name', 'html_filter');
$form->applyFilter('department_name', 'trim');

$form->addText('department_url', get_lang('DepartmentUrl'), false);
$form->applyFilter('department_url', 'html_filter');

// Extra fields
$extra_field = new ExtraField('course');
$extraFieldAdminPermissions = false;
$showOnlyTheseFields = ['tags', 'video_url', 'course_hours_duration', 'max_subscribed_students'];
$extraFieldsToShow = api_get_configuration_value('course_configuration_tool_extra_fields_to_show_and_edit');
if (false !== $extraFieldsToShow && !empty($extraFieldsToShow['fields'])) {
    $showOnlyTheseFields = array_merge($showOnlyTheseFields, $extraFieldsToShow['fields']);
}
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
    [get_lang('AddPicture'), get_lang('AddPictureComment')],
    ['id' => 'picture', 'class' => 'picture-form', 'crop_image' => true]
);

$allowed_picture_types = api_get_supported_image_extensions(false);
$form->addRule(
    'picture',
    get_lang('OnlyImagesAllowed').' ('.implode(',', $allowed_picture_types).')',
    'filetype',
    $allowed_picture_types
);
$form->addElement('checkbox', 'delete_picture', null, get_lang('DeletePicture'));

// Email Picture
$form->addFile(
    'email_picture',
    [get_lang('AddEmailPicture'), get_lang('AddEmailPictureComment')],
    ['id' => 'email_picture', 'class' => 'picture-form', 'crop_image' => true, 'crop_min_ratio' => '250 / 70', 'crop_max_ratio' => '10']
);

$allowed_picture_types = api_get_supported_image_extensions(false);
$form->addRule(
    'email_picture',
    get_lang('OnlyImagesAllowed').' ('.implode(',', $allowed_picture_types).')',
    'filetype',
    $allowed_picture_types
    );
$form->addElement('checkbox', 'delete_email_picture', null, get_lang('DeleteEmailPicture'));

if (api_get_setting('pdf_export_watermark_by_course') === 'true') {
    $url = PDF::get_watermark($course_code);
    $form->addText('pdf_export_watermark_text', get_lang('PDFExportWatermarkTextTitle'), false, ['size' => '60']);
    $form->addElement('file', 'pdf_export_watermark_path', get_lang('AddWaterMark'));
    if ($url != false) {
        $delete_url = '<a href="?delete_watermark">'.Display::return_icon('delete.png', get_lang('DelImage')).'</a>';
        $form->addElement(
            'html',
            '<div class="row"><div class="formw"><a href="'.$url.'">'.$url.' '.$delete_url.'</a></div></div>'
        );
    }
    $form->addRule(
        'pdf_export_watermark_path',
        get_lang('OnlyImagesAllowed').' ('.implode(',', $allowed_picture_types).')',
        'filetype',
        $allowed_picture_types
    );
}

if (api_get_setting('allow_course_theme') === 'true') {
    $group = [];
    $group[] = $form->createElement(
        'SelectTheme',
        'course_theme',
        null,
        ['id' => 'course_theme_id']
    );
    $form->addGroup($group, '', [get_lang('Stylesheets')]);
}

$form->addElement('label', get_lang('DocumentQuota'), format_file_size(DocumentManager::get_course_quota()));

$scoreModels = ExerciseLib::getScoreModels();
if (!empty($scoreModels)) {
    $options = ['' => get_lang('None')];
    foreach ($scoreModels['models'] as $item) {
        $options[$item['id']] = get_lang($item['name']);
    }
    $form->addSelect('score_model_id', get_lang('ScoreModel'), $options);
}

$form->addButtonSave(get_lang('SaveSettings'), 'submit_save');
$form->addHtml('
        </div>
    </div>
');
$form->addHtml('</div>');

// COURSE ACCESS
$group = [];
$groupElement = '';
$visibilityChangeable = !api_get_configuration_value('course_visibility_change_only_admin');
if ($visibilityChangeable) {
    $group[] = $form->createElement(
        'radio',
        'visibility',
        get_lang('CourseAccess'),
        get_lang('OpenToTheWorld'),
        COURSE_VISIBILITY_OPEN_WORLD
    );
    $group[] = $form->createElement(
        'radio',
        'visibility',
        null,
        get_lang('OpenToThePlatform'),
        COURSE_VISIBILITY_OPEN_PLATFORM
    );
    $group[] = $form->createElement('radio', 'visibility', null, get_lang('Private'), COURSE_VISIBILITY_REGISTERED);
    $group[] = $form->createElement(
        'radio',
        'visibility',
        null,
        get_lang('CourseVisibilityClosed'),
        COURSE_VISIBILITY_CLOSED
    );

    // The "hidden" visibility is only available to portal admins
    if (api_is_platform_admin()) {
        $group[] = $form->createElement(
            'radio',
            'visibility',
            null,
            get_lang('CourseVisibilityHidden'),
            COURSE_VISIBILITY_HIDDEN
        );
    }

    $groupElement = $form->addGroup(
        $group,
        '',
        [get_lang('CourseAccess'), get_lang('CourseAccessConfigTip')],
        null,
        null,
        true
    );
}

$url = api_get_path(WEB_CODE_PATH)."auth/inscription.php?c=$course_code&e=1";
$url = Display::url($url, $url);
$label = $form->addLabel(get_lang('DirectLink'), sprintf(get_lang('CourseSettingsRegisterDirectLink'), $url), true);

$group2 = [];
$group2[] = $form->createElement('radio', 'subscribe', get_lang('Subscription'), get_lang('Allowed'), 1);
$group2[] = $form->createElement('radio', 'subscribe', null, get_lang('Denied'), 0);

$myButton = $form->addButtonSave(get_lang('SaveSettings'), 'submit_save', true);

$group3[] = $form->createElement(
    'radio',
    'unsubscribe',
    get_lang('Unsubscription'),
    get_lang('AllowedToUnsubscribe'),
    1
);
$group3[] = $form->createElement('radio', 'unsubscribe', null, get_lang('NotAllowedToUnsubscribe'), 0);

$text = $form->createElement(
    'text',
    'course_registration_password',
    get_lang('CourseRegistrationPassword'),
    false,
    ['size' => '60']
);

$checkBoxActiveLegal = $form->createElement(
    'checkbox',
    'activate_legal',
    [null, get_lang('ShowALegalNoticeWhenEnteringTheCourse')],
    get_lang('ActivateLegal')
);

$textAreaLegal = $form->createElement('html_editor', 'legal', get_lang('CourseLegalAgreement'), ['rows' => 8]);

$elements = [
    $groupElement,
    $label,
    get_lang('Subscription') => $group2,
    get_lang('Unsubscription') => $group3,
    $text,
    $checkBoxActiveLegal,
    $textAreaLegal,
    $myButton,
];

$form->addPanelOption(
    'course-access',
    Display::return_icon('course.png', get_lang('CourseAccess')).' '.get_lang('CourseAccess'),
    $elements
);

// Documents
$globalGroup = [];
if (api_get_setting('documents_default_visibility_defined_in_course') === 'true') {
    $group = [
        $form->createElement('radio', 'documents_default_visibility', null, get_lang('Visible'), 'visible'),
        $form->createElement('radio', 'documents_default_visibility', null, get_lang('Invisible'), 'invisible'),
    ];
    $globalGroup[get_lang('DocumentsDefaultVisibility')] = $group;
}

if (api_get_setting('show_default_folders') == 'true') {
    $group = [
        $form->createElement('radio', 'show_system_folders', null, get_lang('Yes'), 1),
        $form->createElement('radio', 'show_system_folders', null, get_lang('No'), 2),
    ];

    $globalGroup[get_lang('ShowSystemFolders')] = $group;
}

$group = [];
$group[] = $form->createElement(
    'radio',
    'enable_document_auto_launch',
    get_lang('DocumentAutoLaunch'),
    get_lang('RedirectToTheDocumentList'),
    1
);
$group[] = $form->createElement('radio', 'enable_document_auto_launch', null, get_lang('Deactivate'), 0);
$globalGroup[get_lang('DocumentAutoLaunch')] = $group;

$myButton = $form->addButtonSave(get_lang('SaveSettings'), 'submit_save', true);
$globalGroup[] = $myButton;

$form->addPanelOption(
    'documents',
    Display::return_icon('folder.png', get_lang('Documents')).' '.get_lang('Documents'),
    $globalGroup
);

// EMAIL NOTIFICATIONS
$form->addHtml('<div class="panel panel-default">');
$form->addHtml('
    <div class="panel-heading" role="tab" id="heading-email-notifications">
        <h4 class="panel-title">
            <a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion"
               href="#collapse-email-notifications" aria-expanded="false" aria-controls="collapse-email-notifications">
');
$form->addHtml(
    Display::return_icon('mail.png', get_lang('EmailNotifications')).' '.get_lang('EmailNotifications')
);
$form->addHtml('
            </a>
        </h4>
    </div>
');
$form->addHtml('
    <div id="collapse-email-notifications" class="panel-collapse collapse" role="tabpanel"
         aria-labelledby="heading-email-notifications">
        <div class="panel-body">
');
$group = [];
$group[] = $form->createElement(
    'radio',
    'email_alert_to_teacher_on_new_user_in_course',
    get_lang('NewUserEmailAlert'),
    get_lang('NewUserEmailAlertEnable'),
    1
);
$group[] = $form->createElement(
    'radio',
    'email_alert_to_teacher_on_new_user_in_course',
    null,
    get_lang('NewUserEmailAlertToTeacharAndTutor'),
    2
);
$group[] = $form->createElement(
    'radio',
    'email_alert_to_teacher_on_new_user_in_course',
    null,
    get_lang('NewUserEmailAlertDisable'),
    0
);
$form->addGroup($group, '', [get_lang("NewUserEmailAlert")]);

$group = [];
$group[] = $form->createElement(
    'radio',
    'email_alert_students_on_new_homework',
    get_lang('NewHomeworkEmailAlert'),
    get_lang('NewHomeworkEmailAlertEnable'),
    1
);
$group[] = $form->createElement(
    'radio',
    'email_alert_students_on_new_homework',
    null,
    get_lang('NewHomeworkEmailAlertToHrmEnable'),
    2
);
$group[] = $form->createElement(
    'radio',
    'email_alert_students_on_new_homework',
    null,
    get_lang('NewHomeworkEmailAlertDisable'),
    0
);
$form->addGroup($group, '', [get_lang("NewHomeworkEmailAlert")]);

$group = [];
$group[] = $form->createElement(
    'radio',
    'email_alert_manager_on_new_doc',
    get_lang('WorkEmailAlert'),
    get_lang('WorkEmailAlertActivate'),
    1
);
$group[] = $form->createElement(
    'radio',
    'email_alert_manager_on_new_doc',
    null,
    get_lang('WorkEmailAlertActivateOnlyForTeachers'),
    3
);
$group[] = $form->createElement(
    'radio',
    'email_alert_manager_on_new_doc',
    null,
    get_lang('WorkEmailAlertActivateOnlyForStudents'),
    2
);
$group[] = $form->createElement(
    'radio',
    'email_alert_manager_on_new_doc',
    null,
    get_lang('WorkEmailAlertDeactivate'),
    0
);
$form->addGroup($group, '', [get_lang("WorkEmailAlert")]);

$group = [];
$group[] = $form->createElement(
    'radio',
    'email_alert_on_new_doc_dropbox',
    get_lang('DropboxEmailAlert'),
    get_lang('DropboxEmailAlertActivate'),
    1
);
$group[] = $form->createElement(
    'radio',
    'email_alert_on_new_doc_dropbox',
    null,
    get_lang('DropboxEmailAlertDeactivate'),
    0
);
$form->addGroup($group, '', [get_lang("DropboxEmailAlert")]);

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

$form->addGroup($group, '', [get_lang('Exercises')]);

$group = [];
$group[] = $form->createElement(
    'radio',
    'email_to_teachers_on_new_work_feedback',
    get_lang('EmailToTeachersWhenNewWorkFeedback'),
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
$form->addGroup($group, '', [get_lang("EmailToTeachersWhenNewWorkFeedback")]);

if ($allowPortfolioTool) {
    $group = [];
    $group[] = $form->createElement(
        'radio',
        'email_alert_teachers_new_post',
        get_lang('EmailToTeachersWhenNewPost'),
        get_lang('Yes'),
        1
    );
    $group[] = $form->createElement(
        'radio',
        'email_alert_teachers_new_post',
        null,
        get_lang('No'),
        2
    );
    $form->addGroup($group, '', [get_lang("EmailToTeachersWhenNewPost")]);

    $group = [];
    $group[] = $form->createElement(
        'radio',
        'email_alert_teachers_student_new_comment',
        get_lang('EmailToTeachersAndStudentWhenNewComment'),
        get_lang('Yes'),
        1
    );
    $group[] = $form->createElement(
        'radio',
        'email_alert_teachers_student_new_comment',
        null,
        get_lang('No'),
        2
    );
    $form->addGroup($group, '', [get_lang("EmailToTeachersAndStudentWhenNewComment")]);
}

$form->addButtonSave(get_lang('SaveSettings'), 'submit_save');

$form->addHtml('
        </div>
    </div>
');
$form->addHtml('</div>');

// USER RIGHTS
$group = [];
$group[] = $form->createElement(
    'radio',
    'allow_user_edit_agenda',
    get_lang('AllowUserEditAgenda'),
    get_lang('AllowUserEditAgendaActivate'),
    1
);
$group[] = $form->createElement('radio', 'allow_user_edit_agenda', null, get_lang('AllowUserEditAgendaDeactivate'), 0);

$group2 = [];
$group2[] = $form->createElement(
    'radio',
    'allow_user_edit_announcement',
    get_lang('AllowUserEditAnnouncement'),
    get_lang('AllowUserEditAnnouncementActivate'),
    1
);
$group2[] = $form->createElement(
    'radio',
    'allow_user_edit_announcement',
    null,
    get_lang('AllowUserEditAnnouncementDeactivate'),
    0
);

$group3 = [];
$group3[] = $form->createElement(
    'radio',
    'allow_user_image_forum',
    get_lang('AllowUserImageForum'),
    get_lang('AllowUserImageForumActivate'),
    1
);
$group3[] = $form->createElement('radio', 'allow_user_image_forum', null, get_lang('AllowUserImageForumDeactivate'), 0);

$group4 = [];
$group4[] = $form->createElement(
    'radio',
    'allow_user_view_user_list',
    get_lang('AllowUserViewUserList'),
    get_lang('AllowUserViewUserListActivate'),
    1
);
$group4[] = $form->createElement(
    'radio',
    'allow_user_view_user_list',
    null,
    get_lang('AllowUserViewUserListDeactivate'),
    0
);
$myButton = $form->addButtonSave(get_lang('SaveSettings'), 'submit_save', true);

$globalGroup = [
    get_lang('AllowUserEditAgenda') => $group,
    get_lang('AllowUserEditAnnouncement') => $group2,
    get_lang('AllowUserImageForum') => $group3,
    get_lang('AllowUserViewUserList') => $group4,
    '' => $myButton,
];

$form->addPanelOption(
    'users',
    Display::return_icon('user.png', get_lang('UserRights')).' '.get_lang('UserRights'),
    $globalGroup
);

// CHAT SETTINGS
$group = [];
$group[] = $form->createElement(
    'radio',
    'allow_open_chat_window',
    get_lang('AllowOpenchatWindow'),
    get_lang('AllowOpenChatWindowActivate'),
    1
);
$group[] = $form->createElement('radio', 'allow_open_chat_window', null, get_lang('AllowOpenChatWindowDeactivate'), 0);
$myButton = $form->addButtonSave(get_lang('SaveSettings'), 'submit_save', true);

$globalGroup = [
    get_lang('AllowOpenchatWindow') => $group,
    '' => $myButton,
];

$form->addPanelOption(
    'chat',
    Display::return_icon('chat.png', get_lang('ConfigChat'), '', ICON_SIZE_SMALL).' '.get_lang('ConfigChat'),
    $globalGroup
);

// LEARNING PATH
$form->addHtml('<div class="panel panel-default">');
$form->addHtml('
    <div class="panel-heading" role="tab" id="heading-learning-path">
        <h4 class="panel-title">
            <a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion"
               href="#collapse-learning-path" aria-expanded="false" aria-controls="collapse-learning-path">
');
$form->addHtml(
    Display::return_icon('scorms.png', get_lang('ConfigLearnpath')).' '.get_lang('ConfigLearnpath')
);
$form->addHtml('
            </a>
        </h4>
    </div>
');
$form->addHtml('
    <div id="collapse-learning-path" class="panel-collapse collapse" role="tabpanel"
         aria-labelledby="heading-learning-path">
        <div class="panel-body">
');

// Auto launch LP
$group = [];
$group[] = $form->createElement(
    'radio',
    'enable_lp_auto_launch',
    get_lang('LPAutoLaunch'),
    get_lang('RedirectToALearningPath'),
    1
);
$group[] = $form->createElement(
    'radio',
    'enable_lp_auto_launch',
    get_lang('LPAutoLaunch'),
    get_lang('RedirectToTheLearningPathList'),
    2
);
$group[] = $form->createElement('radio', 'enable_lp_auto_launch', null, get_lang('Deactivate'), 0);
$form->addGroup($group, '', [get_lang('LPAutoLaunch')]);

if (api_get_setting('allow_course_theme') === 'true') {
    // Allow theme into Learning path
    $group = [];
    $group[] = $form->createElement(
        'radio',
        'allow_learning_path_theme',
        get_lang('AllowLearningPathTheme'),
        get_lang('AllowLearningPathThemeAllow'),
        1
    );
    $group[] = $form->createElement(
        'radio',
        'allow_learning_path_theme',
        null,
        get_lang('AllowLearningPathThemeDisallow'),
        0
    );
    $form->addGroup($group, '', [get_lang("AllowLearningPathTheme")]);
}

$allowLPReturnLink = api_get_setting('allow_lp_return_link');
if ($allowLPReturnLink === 'true') {
    $group = [
        $form->createElement(
            'radio',
            'lp_return_link',
            get_lang('LpReturnLink'),
            get_lang('RedirectToTheLearningPathList'),
            1
        ),
        $form->createElement(
            'radio',
            'lp_return_link',
            null,
            get_lang('RedirectToCourseHome'),
            0
        ),
        $form->createElement(
            'radio',
            'lp_return_link',
            null,
            get_lang('MyCourses'),
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
    $form->addGroup($group, '', [get_lang('LpReturnLink')]);
}

if (api_get_configuration_value('lp_show_max_progress_or_average_enable_course_level_redefinition')) {
    $group = [
        $form->createElement(
            'radio',
            'lp_show_max_or_average_progress',
            null,
            get_lang('LpMaxProgress'),
            'max'
        ),
        $form->createElement(
            'radio',
            'lp_show_max_or_average_progress',
            null,
            get_lang('LpAverageProgress'),
            'average'
        ),
    ];
    $form->addGroup($group, '', [get_lang('lpShowMaxProgressOrAverage')]);
}

$exerciseInvisible = api_get_setting('exercise_invisible_in_session');
$configureExerciseVisibility = api_get_setting('configure_exercise_visibility_in_course');

if ($exerciseInvisible === 'true' &&
    $configureExerciseVisibility === 'true'
) {
    $group = [
        $form->createElement(
            'radio',
            'exercise_invisible_in_session',
            get_lang('ExerciseInvisibleInSession'),
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
    $form->addGroup($group, '', [get_lang("ExerciseInvisibleInSession")]);
}

if (is_settings_editable()) {
    $form->addButtonSave(get_lang('SaveSettings'), 'submit_save');
} else {
    // Is it allowed to edit the course settings?
    if (!is_settings_editable()) {
        $disabled_output = "disabled";
    }
    $form->freeze();
}
$form->addHtml('
        </div>
    </div>
');
$form->addHtml('</div>');

// Exercise
$form->addHtml('<div class="panel panel-default">');
$form->addHtml('
        <div class="panel-heading" role="tab" id="heading-exercise">
            <h4 class="panel-title">
                <a class="collapsed"
                   role="button" data-toggle="collapse"
                   data-parent="#accordion"
                   href="#collapse-exercise" aria-expanded="false" aria-controls="collapse-exercise">
    ');
$form->addHtml(
    Display::return_icon('quiz.png', get_lang('Exercises')).' '.get_lang('Exercises')
);
$form->addHtml('
                </a>
            </h4>
        </div>
    ');
$form->addHtml('
        <div id="collapse-exercise" class="panel-collapse collapse" role="tabpanel" aria-labelledby="heading-exercise">
            <div class="panel-body">
    ');

if (api_get_configuration_value('allow_exercise_auto_launch')) {
    // Auto launch exercise
    $group = [];
    $group[] = $form->createElement(
        'radio',
        'enable_exercise_auto_launch',
        get_lang('ExerciseAutoLaunch'),
        get_lang('RedirectToExercise'),
        1
    );
    $group[] = $form->createElement(
        'radio',
        'enable_exercise_auto_launch',
        get_lang('ExerciseAutoLaunch'),
        get_lang('RedirectToTheExerciseList'),
        2
    );
    $group[] = $form->createElement('radio', 'enable_exercise_auto_launch', null, get_lang('Deactivate'), 0);
    $form->addGroup($group, '', [get_lang('ExerciseAutoLaunch')]);
}

$form->addElement(
    'number',
    'quiz_question_limit_per_day',
    [get_lang('QuizQuestionsLimitPerDay'), get_lang('QuizQuestionsLimitPerDayComment')],
    ['step' => 1, 'min' => 0]
);

if (is_settings_editable()) {
    $form->addButtonSave(get_lang('SaveSettings'), 'submit_save');
} else {
    // Is it allowed to edit the course settings?
    if (!is_settings_editable()) {
        $disabled_output = "disabled";
    }
    $form->freeze();
}

$form->addHtml('
            </div>
        </div>
    ');
$form->addHtml('</div>');

// THEMATIC ADVANCE SETTINGS
$group = [];
$group[] = $form->createElement(
    'radio',
    'display_info_advance_inside_homecourse',
    get_lang('InfoAboutAdvanceInsideHomeCourse'),
    get_lang('DisplayAboutLastDoneAdvance'),
    1
);
$group[] = $form->createElement(
    'radio',
    'display_info_advance_inside_homecourse',
    null,
    get_lang('DisplayAboutNextAdvanceNotDone'),
    2
);
$group[] = $form->createElement(
    'radio',
    'display_info_advance_inside_homecourse',
    null,
    get_lang('DisplayAboutNextAdvanceNotDoneAndLastDoneAdvance'),
    3
);
$group[] = $form->createElement(
    'radio',
    'display_info_advance_inside_homecourse',
    null,
    get_lang('DoNotDisplayAnyAdvance'),
    0
);
$myButton = $form->addButtonSave(get_lang('SaveSettings'), 'submit_save', true);

$globalGroup = [
    get_lang('InfoAboutAdvanceInsideHomeCourse') => $group,
    '' => $myButton,
];

$form->addPanelOption(
    'thematic',
    Display::return_icon(
        'course_progress.png',
        get_lang('ThematicAdvanceConfiguration')
    )
    .' '
    .get_lang('ThematicAdvanceConfiguration'),
    $globalGroup
);

// Certificate settings
if (api_get_setting('allow_public_certificates') === 'true') {
    $group = [];
    $group[] = $form->createElement(
        'radio',
        'allow_public_certificates',
        get_lang('AllowPublicCertificates'),
        get_lang('Yes'),
        1
    );
    $group[] = $form->createElement('radio', 'allow_public_certificates', null, get_lang('No'), 0);
    $myButton = $form->addButtonSave(get_lang('SaveSettings'), 'submit_save', true);

    $globalGroup = [
        get_lang('AllowPublicCertificates') => $group,
        '' => $myButton,
    ];

    $form->addPanelOption(
        'certificate',
        Display::return_icon('certificate.png', get_lang('Certificates')).' '.get_lang('Certificates'),
        $globalGroup
    );
}

// Forum settings
$group = [
    $form->createElement('radio', 'enable_forum_auto_launch', null, get_lang('RedirectToForumList'), 1),
    $form->createElement('radio', 'enable_forum_auto_launch', null, get_lang('Disabled'), 2),
];
$myButton = $form->addButtonSave(get_lang('SaveSettings'), 'submit_save', true);

// Forum settings
$groupNotification = [
    $form->createElement('radio', 'hide_forum_notifications', null, get_lang('Yes'), 1),
    $form->createElement('radio', 'hide_forum_notifications', null, get_lang('No'), 2),
];

$addUsers = [
    $form->createElement('radio', 'subscribe_users_to_forum_notifications', null, get_lang('Yes'), 1),
    $form->createElement('radio', 'subscribe_users_to_forum_notifications', null, get_lang('No'), 2),
];

$forumsInSessions = [
    $form->createElement('radio', 'share_forums_in_sessions', null, get_lang('Yes'), 1),
    $form->createElement('radio', 'share_forums_in_sessions', null, get_lang('No'), 2),
];

$globalGroup = [
    get_lang('EnableForumAutoLaunch') => $group,
    get_lang('HideForumNotifications') => $groupNotification,
    get_lang('SubscribeUsersToAllForumNotifications') => $addUsers,
    get_lang('ShareForumsInSessions') => $forumsInSessions,
    '' => $myButton,
];

$form->addPanelOption(
    'forum',
    Display::return_icon('forum.png', get_lang('Forum')).' '.get_lang('Forum'),
    $globalGroup
);

// Student publication
$group = [
    $form->createElement('radio', 'show_score', null, get_lang('NewVisible'), 0),
    $form->createElement('radio', 'show_score', null, get_lang('NewUnvisible'), 1),
];
$group2 = [
    $form->createElement('radio', 'student_delete_own_publication', null, get_lang('Yes'), 1),
    $form->createElement('radio', 'student_delete_own_publication', null, get_lang('No'), 0),
];
$myButton = $form->addButtonSave(get_lang('SaveSettings'), 'submit_save', true);

$globalGroup = [
    get_lang('DefaultUpload') => $group,
    get_lang('StudentAllowedToDeleteOwnPublication') => $group2,
    '' => $myButton,
];

$form->addPanelOption(
    'student-publication',
    Display::return_icon('work.png', get_lang('StudentPublications')).' '.get_lang('StudentPublications'),
    $globalGroup
);

// Agenda settings -->
$group = [];
$group[] = $form->createElement(
    'radio',
    'agenda_share_events_in_sessions',
    null,
    get_lang('AgendaEventsInBaseCourseWillBeVisibleInCourseSessions'),
    1
);
$group[] = $form->createElement(
    'radio',
    'agenda_share_events_in_sessions',
    null,
    get_lang('AgendaEventsOnlyVisibleInCurrentCourse'), 0
);

$globalGroup = [
    get_lang('ShareEventsInSessions') => $group,
    '' => $form->addButtonSave(get_lang('SaveSettings'), 'submit_save', true),
];

$form->addPanelOption(
    'agenda',
    Display::return_icon('agenda.png', get_lang('Agenda')).' '.get_lang('Agenda'),
    $globalGroup
);
// <-- end of agenda settings

if ($allowPortfolioTool) {
    $globalGroup = [
        get_lang('QualifyPortfolioItems') => [
            $form->createElement('radio', 'qualify_portfolio_item', null, get_lang('Yes'), 1),
            $form->createElement('radio', 'qualify_portfolio_item', null, get_lang('No'), 2),
        ],
        get_lang('QualifyPortfolioComments') => [
            $form->createElement('radio', 'qualify_portfolio_comment', null, get_lang('Yes'), 1),
            $form->createElement('radio', 'qualify_portfolio_comment', null, get_lang('No'), 2),
        ],
        get_lang('MaxScore') => [
            $form->createElement('number', 'portfolio_max_score', get_lang('MaxScore'), ['step' => 'any', 'min' => 0]),
        ],
        get_lang('RequiredNumberOfItems') => [
            $form->createElement('number', 'portfolio_number_items', '', ['step' => '1', 'min' => 0]),
        ],
        get_lang('RequiredNumberOfComments') => [
            $form->createElement('number', 'portfolio_number_comments', '', ['step' => '1', 'min' => 0]),
        ],
        $form->addButtonSave(get_lang('SaveSettings'), 'submit_save', true),
    ];

    $form->addPanelOption(
        'portfolio',
        Display::return_icon('wiki_task.png', get_lang('Portfolio')).PHP_EOL.get_lang('Portfolio'),
        $globalGroup
    );
}

// Plugin course settings
$appPlugin = new AppPlugin();
$appPlugin->add_course_settings_form($form);

$form->addHtml('</div>');

// Get all the course information
$all_course_information = CourseManager::get_course_information($_course['sysCode']);

// Set the default values of the form
$values = [];
$values['title'] = $_course['name'];
$values['category_code'] = $_course['categoryCode'];
$values['course_language'] = $_course['language'];
$values['department_name'] = $_course['extLink']['name'];
$values['department_url'] = $_course['extLink']['url'];
$values['visibility'] = $_course['visibility'];
$values['subscribe'] = $_course['subscribe'];
$values['unsubscribe'] = $_course['unsubscribe'];
$values['course_registration_password'] = $all_course_information['registration_code'];
$values['legal'] = $all_course_information['legal'];
$values['activate_legal'] = $all_course_information['activate_legal'];
$values['show_score'] = $all_course_information['show_score'];

$courseSettings = CourseManager::getCourseSettingVariables($appPlugin);

foreach ($courseSettings as $setting) {
    $result = api_get_course_setting($setting, $_course, true);
    if ($result != '-1') {
        $values[$setting] = $result;
    }
}
// make sure new settings have a clear default value
if (!isset($values['student_delete_own_publication'])) {
    $values['student_delete_own_publication'] = 0;
}
$form->setDefaults($values);

// Validate form
if ($form->validate() && is_settings_editable()) {
    $updateValues = $form->getSubmitValues();

    // update course picture
    $picture = $_FILES['picture'];
    if (!empty($picture['name'])) {
        CourseManager::update_course_picture(
            $_course,
            $picture['name'],
            $picture['tmp_name'],
            $updateValues['picture_crop_result']
        );

        Event::addEvent(
            LOG_COURSE_SETTINGS_CHANGED,
            'course_picture',
            $picture['name']
        );
    }

    // update email picture
    $emailPicture = $_FILES['email_picture'];
    if (!empty($emailPicture['name'])) {
        CourseManager::updateCourseEmailPicture(
            $_course,
            $emailPicture['tmp_name'],
            $updateValues['email_picture_crop_result']
            );

        Event::addEvent(
            LOG_COURSE_SETTINGS_CHANGED,
            'course_email_picture',
            $emailPicture['name']
            );
    }

    if ($visibilityChangeable && isset($updateValues['visibility'])) {
        $visibility = $updateValues['visibility'];
    } else {
        $visibility = $_course['visibility'];
    }
    $deletePicture = isset($updateValues['delete_picture']) ? $updateValues['delete_picture'] : '';

    if ($deletePicture) {
        CourseManager::deleteCoursePicture($course_code);
    }

    $deleteEmailPicture = isset($updateValues['delete_email_picture']) ? $updateValues['delete_email_picture'] : '';

    if ($deleteEmailPicture) {
        CourseManager::deleteCourseEmailPicture($course_code);
    }
    global $_configuration;
    $urlId = api_get_current_access_url_id();
    if (isset($_configuration[$urlId]) &&
        isset($_configuration[$urlId]['hosting_limit_active_courses']) &&
        $_configuration[$urlId]['hosting_limit_active_courses'] > 0
    ) {
        $courseInfo = api_get_course_info_by_id($courseId);

        // Check if
        if ($courseInfo['visibility'] == COURSE_VISIBILITY_HIDDEN &&
            $visibility != $courseInfo['visibility']
        ) {
            $num = CourseManager::countActiveCourses($urlId);
            if ($num >= $_configuration[$urlId]['hosting_limit_active_courses']) {
                api_warn_hosting_contact('hosting_limit_active_courses');

                Display::addFlash(
                    Display::return_message(get_lang('PortalActiveCoursesLimitReached'))
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
    $params = [
        'title' => $updateValues['title'],
        'course_language' => $updateValues['course_language'],
        'category_code' => $updateValues['category_code'],
        'department_name' => $updateValues['department_name'],
        'department_url' => $updateValues['department_url'],
        'subscribe' => $updateValues['subscribe'],
        'unsubscribe' => $updateValues['unsubscribe'],
        'legal' => $updateValues['legal'],
        'activate_legal' => $activeLegal,
        'registration_code' => $updateValues['course_registration_password'],
        'show_score' => $updateValues['show_score'],
    ];
    if ($visibilityChangeable && isset($updateValues['visibility'])) {
        $params['visibility'] = $visibility;
    }
    $table = Database::get_main_table(TABLE_MAIN_COURSE);
    Database::update($table, $params, ['id = ?' => $courseId]);
    CourseManager::saveSettingChanges($_course, $params);

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
    Display::addFlash(Display::return_message(get_lang('Updated')));

    require '../inc/local.inc.php';
    $url = api_get_path(WEB_CODE_PATH).'course_info/infocours.php?'.$courseParams;
    header("Location: $url");
    exit;
}

if ($show_delete_watermark_text_message) {
    Display::addFlash(
        Display::return_message(get_lang('FileDeleted'), 'normal')
    );
}

Display::display_header($nameTools, MODULE_HELP_NAME);

echo '<div id="course_settings">';
$form->display();
echo '</div>';

Display::display_footer();
