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
 *
 * @package chamilo.course_info
 */
require_once __DIR__.'/../inc/global.inc.php';
$current_course_tool = TOOL_COURSE_SETTING;
$this_section = SECTION_COURSES;
$nameTools = get_lang('ModifInfo');
api_protect_course_script(true);
api_block_anonymous_users();
$_course = api_get_course_info();
$currentCourseRepository = $_course['path'];
$isAllowToEdit = api_is_course_admin() || api_is_platform_admin();
$course_code = api_get_course_id();
$courseId = api_get_course_int_id();
$isEditable = true;

if (!$isAllowToEdit) {
    api_not_allowed(true);
}

$router = Container::getRouter();
$translator = Container::getTranslator();

$show_delete_watermark_text_message = false;
if (api_get_setting('pdf_export_watermark_by_course') == 'true') {
    if (isset($_GET['delete_watermark'])) {
        PDF::delete_watermark($course_code);
        $show_delete_watermark_text_message = true;
    }
}

$categories = CourseCategory::getCategoriesCanBeAddedInCourse($_course['categoryCode']);

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
    $html .= '<a role="button" class="'.(($open) ? 'collapse' : ' ').'"  data-toggle="collapse" data-target="#collapse_'.$id.'" aria-expanded="true" aria-controls="collapse_'.$id.'">';
    if ($icon) {
        $html .= Display::return_icon($icon, null, null, ICON_SIZE_SMALL);
    }
    $html .= $title;
    $html .= '</a></h5></div>';
    $html .= '<div id="collapse_'.$id.'" class="collapse show" aria-labelledby="heading_'.$id.'" data-parent="#'.$parent.'">';
    $html .= '<div class="card-body">';

    return $html;
}

function card_settings_close()
{
    $html = '</div></div></div>';

    return $html;
}

$form->addHtml(card_settings_open('course_settings', get_lang('CourseSettings'), true, 'settings.png', 'accordionSettings'));

$image = '';
// Display course picture
$course_path = api_get_path(SYS_COURSE_PATH).$currentCourseRepository; // course path
if (file_exists($course_path.'/course-pic85x85.png')) {
    $course_web_path = api_get_path(WEB_COURSE_PATH).$currentCourseRepository; // course web path
    $course_medium_image = $course_web_path.'/course-pic85x85.png?'.rand(1, 1000); // redimensioned image 85x85
    $image = '<div class="row"><label class="col-md-2 control-label">'.get_lang('Image').'</label> 
                    <div class="col-md-8"><img class="img-thumbnail" src="'.$course_medium_image.'" /></div></div>';
}

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
$extra = $extra_field->addElements(
    $form,
    $courseId,
    [],
    false,
    false,
    $showOnlyTheseFields,
    [],
    false
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
    get_lang('AddPicture'),
    ['id' => 'picture', 'class' => 'picture-form', 'crop_image' => true]
);

$allowed_picture_types = api_get_supported_image_extensions(false);

$form->addHtml($image);

$form->addRule(
    'picture',
    get_lang('OnlyImagesAllowed').' ('.implode(',', $allowed_picture_types).')',
    'filetype',
    $allowed_picture_types
);
$form->addElement('checkbox', 'delete_picture', null, get_lang('DeletePicture'));

if (api_get_setting('pdf_export_watermark_by_course') == 'true') {
    $url = PDF::get_watermark($course_code);
    $form->addText('pdf_export_watermark_text', get_lang('PDFExportWatermarkTextTitle'), false, ['size' => '60']);
    $form->addElement('file', 'pdf_export_watermark_path', get_lang('AddWaterMark'));
    if ($url != false) {
        $delete_url = '<a href="?delete_watermark">'.Display::return_icon('delete.png', get_lang('DelImage')).'</a>';
        $form->addElement(
            'html',
            '<div class="row"><div class="form"><a href="'.$url.'">'.$url.' '.$delete_url.'</a></div></div>'
        );
    }
    $form->addRule(
        'pdf_export_watermark_path',
        get_lang('OnlyImagesAllowed').' ('.implode(',', $allowed_picture_types).')',
        'filetype',
        $allowed_picture_types
    );
}

if (api_get_setting('allow_course_theme') == 'true') {
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

$form->addHtml(card_settings_close());

//************* COURSE ACCESS ******************//

$group = [];
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
$textAreaLegal = $form->createElement('textarea', 'legal', get_lang('CourseLegalAgreement'), ['rows' => 8]);

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
    'course_access',
    get_lang('CourseAccess'),
    $elements,
    'course.png',
    false,
    'accordionSettings'
);

//************** END COURSE ACCESS *************//

//************** START DOCUMENTS ***************//
$globalGroup = [];
if (api_get_setting('documents_default_visibility_defined_in_course') == 'true') {
    $group = [
        $form->createElement('radio', 'documents_default_visibility', null, get_lang('Visible'), 'visible'),
        $form->createElement('radio', 'documents_default_visibility', null, get_lang('Invisible'), 'invisible'),
    ];
    $globalGroup[get_lang('DocumentsDefaultVisibility')] = $group;
}

$group = [
    $form->createElement('radio', 'show_system_folders', null, get_lang('Yes'), 1),
    $form->createElement('radio', 'show_system_folders', null, get_lang('No'), 2),
];

$globalGroup[get_lang('ShowSystemFolders')] = $group;

$myButton = $form->addButtonSave(get_lang('SaveSettings'), 'submit_save', true);

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

$globalGroup[] = $myButton;

$form->addPanelOption(
    'documents',
    get_lang('Documents'),
    $globalGroup,
    'folder.png',
    false,
    'accordionSettings'
);

// *************** END DOCUMENTS ***************** //

// ************** START EMAIL NOTIFICATIONS *******************//

$globalGroup = [];

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
$globalGroup[get_lang('NewUserEmailAlert')] = $group;

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
$globalGroup[get_lang('NewHomeworkEmailAlert')] = $group;

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

$globalGroup[get_lang('WorkEmailAlert')] = $group;

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

$globalGroup[get_lang('DropboxEmailAlert')] = $group;

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

$globalGroup[get_lang('Exercises')] = $group;

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

$globalGroup[get_lang('EmailToTeachersWhenNewWorkFeedback')] = $group;

$myButton = $form->addButtonSave(get_lang('SaveSettings'), 'submit_save', true);
$globalGroup[] = $myButton;

$form->addPanelOption(
    'email-notifications',
    get_lang('EmailNotifications'),
    $globalGroup,
    'mail.png',
    false,
    'accordionSettings'
);

//************** END EMAIL NOTIFICATIONS ******************//
//*******************  START USER *******************//
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
    get_lang('UserRights'),
    $globalGroup,
    'user.png',
    false,
    'accordionSettings'
);
//****************** END USER ****************//

//***************** CHAT SETTINGS ***************//

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
    get_lang('ConfigChat'),
    $globalGroup,
    'chat.png',
    false,
    'accordionSettings'
);

//*************** START LEARNING PATH  *************** //

$globalGroup = [];
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

$globalGroup[get_lang('LPAutoLaunch')] = $group;

if (api_get_setting('allow_course_theme') == 'true') {
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

    $globalGroup[get_lang("AllowLearningPathTheme")] = $group;
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
    ];
    $globalGroup[get_lang("LpReturnLink")] = $group;
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

    $globalGroup[get_lang("ExerciseInvisibleInSession")] = $group;
}

if ($isEditable) {
    $myButton = $form->addButtonSave(get_lang('SaveSettings'), 'submit_save', true);
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
    get_lang('ConfigLearnpath'),
    $globalGroup,
    'scorms.png',
    false,
    'accordionSettings'
);
// ********** END CONFIGURE LEARN PATH ***************//

// ********** EXERCISE ********************* //
if (api_get_configuration_value('allow_exercise_auto_launch')) {
    $globalGroup = [];

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

    $globalGroup[get_lang("ExerciseAutoLaunch")] = $group;

    if ($isEditable) {
        $myButton = $form->addButtonSave(get_lang('SaveSettings'), 'submit_save', true);
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
        get_lang('Exercise'),
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
    get_lang('ThematicAdvanceConfiguration'),
    $globalGroup,
    'course_progress.png',
    false,
    'accordionSettings'
);

// ************* END THEMATIC  *********** //

// ************* CERTIFICATE SETTINGS ***************** //
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
        get_lang('Certificates'),
        $globalGroup,
        null,
        false,
        'accordionSettings'
    );
}

$group = [
    $form->createElement('radio', 'enable_forum_auto_launch', null, get_lang('RedirectToForumList'), 1),
    $form->createElement('radio', 'enable_forum_auto_launch', null, get_lang('Disabled'), 2),
];
$myButton = $form->addButtonSave(get_lang('SaveSettings'), 'submit_save', true);

$globalGroup = [
    get_lang('EnableForumAutoLaunch') => $group,
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

//********** STUDENT PUBLICATION ***************** //
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
    get_lang('StudentPublications'),
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
    $result = api_get_course_setting($setting);
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
if ($form->validate() && $isEditable) {
    $updateValues = $form->getSubmitValues();

    // update course picture
    $picture = $_FILES['picture'];
    if (!empty($picture['name'])) {
        $picture_uri = CourseManager::update_course_picture(
            $_course,
            $picture['name'],
            $picture['tmp_name'],
            $updateValues['picture_crop_result']
        );
    }

    $visibility = $updateValues['visibility'];
    $deletePicture = isset($updateValues['delete_picture']) ? $updateValues['delete_picture'] : '';

    if ($deletePicture) {
        CourseManager::deleteCoursePicture($course_code);
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
        $pdf_export_watermark_path_result = PDF::upload_watermark(
            $pdf_export_watermark_path['name'],
            $pdf_export_watermark_path['tmp_name'],
            $course_code
        );
        unset($updateValues['pdf_export_watermark_path']);
    }

    $activeLegal = isset($updateValues['activate_legal']) ? $updateValues['activate_legal'] : 0;
    $table_course = Database::get_main_table(TABLE_MAIN_COURSE);

    $params = [
        'title' => $updateValues['title'],
        'course_language' => $updateValues['course_language'],
        'category_code' => $updateValues['category_code'],
        'department_name' => $updateValues['department_name'],
        'department_url' => $updateValues['department_url'],
        'visibility' => $updateValues['visibility'],
        'subscribe' => $updateValues['subscribe'],
        'unsubscribe' => $updateValues['unsubscribe'],
        'legal' => $updateValues['legal'],
        'activate_legal' => $activeLegal,
        'registration_code' => $updateValues['course_registration_password'],
        'show_score' => $updateValues['show_score'],
    ];
    Database::update($table_course, $params, ['id = ?' => $courseId]);

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
    $courseFieldValue->saveFieldValues($updateValues);

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

//Template Course Info
$tpl = new Template($nameTools);

Display::display_header($nameTools, 'Settings');

//$form->display();

$tpl->assign('course_settings', $form->returnForm());
$courseInfoLayout = $tpl->get_template("course_info/index.html.twig");
$content = $tpl->fetch($courseInfoLayout);
echo $content;

Display::display_footer();
