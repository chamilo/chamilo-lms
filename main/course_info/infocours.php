<?php
/* For licensing terms, see /license.txt */

/**
 *	Code to display the course settings form (for the course admin)
 *	and activate the changes.
 *
 *	See ./inc/conf/course_info.conf.php for settings
 * @todo Move $canBeEmpty from course_info.conf.php to config-settings
 * @todo Take those config settings into account in this script
 * @author Patrick Cool <patrick.cool@UGent.be>
 * @author Roan Embrechts, refactoring and improved course visibility|subscribe|unsubscribe options
 * @author Julio Montoya <gugli100@gmail.com> Jquery support + lots of fixes
 * @package chamilo.course_info
 */

require_once __DIR__.'/../inc/global.inc.php';
$current_course_tool = TOOL_COURSE_SETTING;
$this_section = SECTION_COURSES;

$nameTools = get_lang('ModifInfo');

api_protect_course_script(true);
api_block_anonymous_users();
$_course = api_get_course_info();

/*	Constants and variables */
define('MODULE_HELP_NAME', 'Settings');
define('COURSE_CHANGE_PROPERTIES', 'COURSE_CHANGE_PROPERTIES');

$currentCourseRepository = $_course['path'];
$is_allowedToEdit = $is_courseAdmin || $is_platformAdmin;

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
if (api_get_setting('pdf_export_watermark_by_course') == 'true') {
    if (isset($_GET['delete_watermark'])) {
        PDF::delete_watermark($course_code);
        $show_delete_watermark_text_message = true;
    }
}
$tbl_user = Database::get_main_table(TABLE_MAIN_USER);
$tbl_admin = Database::get_main_table(TABLE_MAIN_ADMIN);
$tbl_course_user = Database::get_main_table(TABLE_MAIN_COURSE_USER);
$tbl_course = Database::get_main_table(TABLE_MAIN_COURSE);

$sql = "SELECT tutor_name FROM $tbl_course WHERE id = $courseId";
$q_tutor = Database::query($sql);
$s_tutor = Database::result($q_tutor, 0, 'tutor_name');

$target_name = api_sort_by_first_name() ? 'firstname' : 'lastname';
$sql = "SELECT DISTINCT username, lastname, firstname
        FROM $tbl_user as user, $tbl_course_user as course_rel_user
        WHERE (course_rel_user.status='1') AND user.user_id=course_rel_user.user_id AND c_id ='".$courseId."'
        ORDER BY ".$target_name." ASC";
$q_result_titulars = Database::query($sql);

if (Database::num_rows($q_result_titulars) == 0) {
    $sql = "SELECT username, lastname, firstname FROM $tbl_user as user, $tbl_admin as admin
            WHERE admin.user_id=user.user_id ORDER BY ".$target_name." ASC";
    $q_result_titulars = Database::query($sql);
}

$a_profs[0] = '-- '.get_lang('NoManager').' --';
while ($a_titulars = Database::fetch_array($q_result_titulars)) {
    $s_username = $a_titulars['username'];
    $s_lastname = $a_titulars['lastname'];
    $s_firstname = $a_titulars['firstname'];

    if (api_get_person_name($s_firstname, $s_lastname) == $s_tutor) {
        $s_selected_tutor = api_get_person_name($s_firstname, $s_lastname);
    }
    $s_disabled_select_titular = '';
    if (!api_is_course_admin()) {
        $s_disabled_select_titular = 'disabled=disabled';
    }
    $a_profs[api_get_person_name($s_firstname, $s_lastname)] = api_get_person_name($s_lastname, $s_firstname).' ('.$s_username.')';
}

$categories = CourseCategory::getCategoriesCanBeAddedInCourse($_course['categoryCode']);

// Build the form
$form = new FormValidator('update_course', 'post', api_get_self().'?'.api_get_cidreq());

$form->addHtml('<div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">');

// COURSE SETTINGS
$form->addHtml('<div class="panel panel-default">');
$form->addHtml('
    <div class="panel-heading" role="tab" id="heading-course-settings">
        <h4 class="panel-title">
            <a role="button" data-toggle="collapse" data-parent="#accordion" href="#collapse-course-settings" aria-expanded="true" aria-controls="collapse-course-settings">
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
    <div id="collapse-course-settings" class="panel-collapse collapse in" role="tabpanel" aria-labelledby="heading-course-settings">
        <div class="panel-body">
');

$image = '';
// Display course picture
$course_path = api_get_path(SYS_COURSE_PATH).$currentCourseRepository; // course path
if (file_exists($course_path.'/course-pic85x85.png')) {
    $course_web_path = api_get_path(WEB_COURSE_PATH).$currentCourseRepository; // course web path
    $course_medium_image = $course_web_path.'/course-pic85x85.png?'.rand(1, 1000); // redimensioned image 85x85
    $image = '<div class="row"><label class="col-md-2 control-label">'.get_lang('Image').'</label> 
                    <div class="col-md-8"><img src="'.$course_medium_image.'" /></div></div>';
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
    ['style'=>'width:350px', 'id'=>'category_code']
);
$form->addSelectLanguage(
    'course_language',
    array(get_lang('Ln'), get_lang('TipLang'))
);

$group = array(
    $form->createElement('radio', 'show_course_in_user_language', null, get_lang('Yes'), 1),
    $form->createElement('radio', 'show_course_in_user_language', null, get_lang('No'), 2),
);

$form->addGroup($group, '', array(get_lang("ShowCourseInUserLanguage")));

$form->addText('department_name', get_lang('Department'), false);
$form->applyFilter('department_name', 'html_filter');
$form->applyFilter('department_name', 'trim');

$form->addText('department_url', get_lang('DepartmentUrl'), false);
$form->applyFilter('department_url', 'html_filter');

// Picture
$form->addFile(
    'picture',
    get_lang('AddPicture'),
    array('id' => 'picture', 'class' => 'picture-form', 'crop_image' => true)
);

$allowed_picture_types = api_get_supported_image_extensions(false);
$form->addRule(
    'picture',
    get_lang('OnlyImagesAllowed').' ('.implode(',', $allowed_picture_types).')',
    'filetype',
    $allowed_picture_types
);
$form->addElement('checkbox', 'delete_picture', null, get_lang('DeletePicture'));

if (api_get_setting('pdf_export_watermark_by_course') == 'true') {
    $url = PDF::get_watermark($course_code);
    $form->addText('pdf_export_watermark_text', get_lang('PDFExportWatermarkTextTitle'), false, array('size' => '60'));
    $form->addElement('file', 'pdf_export_watermark_path', get_lang('AddWaterMark'));
    if ($url != false) {
        $delete_url = '<a href="?delete_watermark">'.Display::return_icon('delete.png', get_lang('DelImage')).'</a>';
        $form->addElement('html', '<div class="row"><div class="formw"><a href="'.$url.'">'.$url.' '.$delete_url.'</a></div></div>');
    }
    $form->addRule('pdf_export_watermark_path', get_lang('OnlyImagesAllowed').' ('.implode(',', $allowed_picture_types).')', 'filetype', $allowed_picture_types);
}

if (api_get_setting('allow_course_theme') == 'true') {
    $group = array();
    $group[] = $form->createElement(
        'SelectTheme',
        'course_theme',
        null,
        array('id' => 'course_theme_id')
    );
    $form->addGroup($group, '', array(get_lang("Stylesheets")));
}

$form->addElement('label', get_lang('DocumentQuota'), format_file_size(DocumentManager::get_course_quota()));
$form->addButtonSave(get_lang('SaveSettings'), 'submit_save');
$form->addHtml('
        </div>
    </div>
');
$form->addHtml('</div>');

// COURSE ACCESS
$form->addHtml('<div class="panel panel-default">');
$form->addHtml('
    <div class="panel-heading" role="tab" id="heading-course-access">
        <h4 class="panel-title">
            <a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion" href="#collapse-course-access" aria-expanded="false" aria-controls="collapse-course-access">
');
$form->addElement(
    'html',
    Display::return_icon('course.png', get_lang('CourseAccess')).' '.get_lang('CourseAccess')
);
$form->addHtml('
            </a>
        </h4>
    </div>
');

$form->addHtml('
    <div id="collapse-course-access" class="panel-collapse collapse" role="tabpanel" aria-labelledby="heading-course-access">
        <div class="panel-body">
');

$group = array();
$group[] = $form->createElement('radio', 'visibility', get_lang("CourseAccess"), get_lang('OpenToTheWorld'), COURSE_VISIBILITY_OPEN_WORLD);
$group[] = $form->createElement('radio', 'visibility', null, get_lang('OpenToThePlatform'), COURSE_VISIBILITY_OPEN_PLATFORM);
$group[] = $form->createElement('radio', 'visibility', null, get_lang('Private'), COURSE_VISIBILITY_REGISTERED);
$group[] = $form->createElement('radio', 'visibility', null, get_lang('CourseVisibilityClosed'), COURSE_VISIBILITY_CLOSED);
// The "hidden" visibility is only available to portal admins
if (api_is_platform_admin()) {
    $group[] = $form->createElement('radio', 'visibility', null, get_lang('CourseVisibilityHidden'), COURSE_VISIBILITY_HIDDEN);
}
$form->addGroup($group, '', array(get_lang("CourseAccess"), get_lang("CourseAccessConfigTip")));

$url = api_get_path(WEB_CODE_PATH)."auth/inscription.php?c=$course_code&e=1";
$url = Display::url($url, $url);
$form->addElement('label', get_lang('DirectLink'), sprintf(get_lang('CourseSettingsRegisterDirectLink'), $url));

$group = array();
$group[] = $form->createElement('radio', 'subscribe', get_lang('Subscription'), get_lang('Allowed'), 1);
$group[] = $form->createElement('radio', 'subscribe', null, get_lang('Denied'), 0);
$form->addGroup($group, '', array(get_lang("Subscription")));

$group = array();
$group[] = $form->createElement('radio', 'unsubscribe', get_lang('Unsubscription'), get_lang('AllowedToUnsubscribe'), 1);
$group[] = $form->createElement('radio', 'unsubscribe', null, get_lang('NotAllowedToUnsubscribe'), 0);
$form->addGroup($group, '', array(get_lang("Unsubscription")));

$form->addText('course_registration_password', get_lang('CourseRegistrationPassword'), false, array('size' => '60'));

$form->addElement('checkbox', 'activate_legal', array(null, get_lang('ShowALegalNoticeWhenEnteringTheCourse')), get_lang('ActivateLegal'));
$form->addElement('textarea', 'legal', get_lang('CourseLegalAgreement'), array('rows' => 8));
$form->addButtonSave(get_lang('SaveSettings'), 'submit_save');

$form->addHtml('
        </div>
    </div>
');
$form->addHtml('</div>');

// Documents
$form->addHtml('<div class="panel panel-default">');
$form->addHtml('
    <div class="panel-heading" role="tab" id="heading-documents">
        <h4 class="panel-title">
            <a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion" href="#collapse-documents" aria-expanded="false" aria-controls="collapse-documents">
');
$form->addHtml(
    Display::return_icon('folder.png', get_lang('Documents')).' '.get_lang('Documents')
);
$form->addHtml('
                </a>
            </h4>
        </div>
    ');
$form->addHtml('
    <div id="collapse-documents" class="panel-collapse collapse" role="tabpanel" aria-labelledby="heading-documents">
        <div class="panel-body">
');

if (api_get_setting('documents_default_visibility_defined_in_course') == 'true') {
    $group = array(
        $form->createElement('radio', 'documents_default_visibility', null, get_lang('Visible'), 'visible'),
        $form->createElement('radio', 'documents_default_visibility', null, get_lang('Invisible'), 'invisible')
    );
    $form->addGroup($group, '', array(get_lang('DocumentsDefaultVisibility')));
}

$group = array(
    $form->createElement('radio', 'show_system_folders', null, get_lang('Yes'), 1),
    $form->createElement('radio', 'show_system_folders', null, get_lang('No'), 2)
);
$form->addGroup($group, '', array(get_lang('ShowSystemFolders')));

$form->addButtonSave(get_lang('SaveSettings'), 'submit_save');
$form->addHtml('
        </div>
    </div>
');
$form->addHtml('</div>');

// EMAIL NOTIFICATIONS
$form->addHtml('<div class="panel panel-default">');
$form->addHtml('
    <div class="panel-heading" role="tab" id="heading-email-notifications">
        <h4 class="panel-title">
            <a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion" href="#collapse-email-notifications" aria-expanded="false" aria-controls="collapse-email-notifications">
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
    <div id="collapse-email-notifications" class="panel-collapse collapse" role="tabpanel" aria-labelledby="heading-email-notifications">
        <div class="panel-body">
');
$group = array();
$group[] = $form->createElement('radio', 'email_alert_to_teacher_on_new_user_in_course', get_lang('NewUserEmailAlert'), get_lang('NewUserEmailAlertEnable'), 1);
$group[] = $form->createElement('radio', 'email_alert_to_teacher_on_new_user_in_course', null, get_lang('NewUserEmailAlertToTeacharAndTutor'), 2);
$group[] = $form->createElement('radio', 'email_alert_to_teacher_on_new_user_in_course', null, get_lang('NewUserEmailAlertDisable'), 0);
$form->addGroup($group, '', array(get_lang("NewUserEmailAlert")));

$group = array();
$group[] = $form->createElement('radio', 'email_alert_students_on_new_homework', get_lang('NewHomeworkEmailAlert'), get_lang('NewHomeworkEmailAlertEnable'), 1);
$group[] = $form->createElement('radio', 'email_alert_students_on_new_homework', null, get_lang('NewHomeworkEmailAlertDisable'), 0);
$form->addGroup($group, '', array(get_lang("NewHomeworkEmailAlert")));

$group = array();
$group[] = $form->createElement('radio', 'email_alert_manager_on_new_doc', null, get_lang('WorkEmailAlertActivateOnlyForTeachers'), 3);
$group[] = $form->createElement('radio', 'email_alert_manager_on_new_doc', null, get_lang('WorkEmailAlertActivateOnlyForStudents'), 2);
$group[] = $form->createElement('radio', 'email_alert_manager_on_new_doc', get_lang('WorkEmailAlert'), get_lang('WorkEmailAlertActivate'), 1);
$group[] = $form->createElement('radio', 'email_alert_manager_on_new_doc', null, get_lang('WorkEmailAlertDeactivate'), 0);
$form->addGroup($group, '', array(get_lang("WorkEmailAlert")));

$group = array();
$group[] = $form->createElement('radio', 'email_alert_on_new_doc_dropbox', get_lang('DropboxEmailAlert'), get_lang('DropboxEmailAlertActivate'), 1);
$group[] = $form->createElement('radio', 'email_alert_on_new_doc_dropbox', null, get_lang('DropboxEmailAlertDeactivate'), 0);
$form->addGroup($group, '', array(get_lang("DropboxEmailAlert")));

$group = array();

$group[] = $form->createElement('checkbox', 'email_alert_manager_on_new_quiz[]', null, get_lang('SendEmailToTeacherWhenStudentStartQuiz'), ['value' => 2]);
// Default
$group[] = $form->createElement('checkbox', 'email_alert_manager_on_new_quiz[]', null, get_lang('SendEmailToTeacherWhenStudentEndQuiz'), ['value' => 1]);

$group[] = $form->createElement('checkbox', 'email_alert_manager_on_new_quiz[]', null, get_lang('SendEmailToTeacherWhenStudentEndQuizOnlyIfOpenQuestion'), ['value' => 3]);
$group[] = $form->createElement('checkbox', 'email_alert_manager_on_new_quiz[]', null, get_lang('SendEmailToTeacherWhenStudentEndQuizOnlyIfOralQuestion'), ['value' => 4]);
//$group[] = $form->createElement('checkbox', 'email_alert_manager_on_new_quiz[]', null, get_lang('QuizEmailAlertDeactivate'), ['value' => 0]);

//$group[] = $form->createElement('checkbox', 'email_alert_manager_on_new_quiz[]', null, get_lang('QuizEmailSendToTeacherWhenStudentEndQuiz'), ['value' => 3]);
$form->addGroup($group, '', array(get_lang("Exercises")));
$form->addButtonSave(get_lang('SaveSettings'), 'submit_save');

$form->addHtml('
        </div>
    </div>
');
$form->addHtml('</div>');

// USER RIGHTS
$form->addHtml('<div class="panel panel-default">');
$form->addHtml('
    <div class="panel-heading" role="tab" id="heading-user-right">
        <h4 class="panel-title">
            <a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion" href="#collapse-user-right" aria-expanded="false" aria-controls="collapse-user-right">
');
$form->addHtml(
    Display::return_icon('user.png', get_lang('UserRights')).' '.get_lang('UserRights')
);
$form->addHtml('
            </a>
        </h4>
    </div>
');
$form->addHtml('
    <div id="collapse-user-right" class="panel-collapse collapse" role="tabpanel" aria-labelledby="heading-user-right">
        <div class="panel-body">
');

$group = array();
$group[] = $form->createElement('radio', 'allow_user_edit_agenda', get_lang('AllowUserEditAgenda'), get_lang('AllowUserEditAgendaActivate'), 1);
$group[] = $form->createElement('radio', 'allow_user_edit_agenda', null, get_lang('AllowUserEditAgendaDeactivate'), 0);
$form->addGroup($group, '', array(get_lang("AllowUserEditAgenda")));

$group = array();
$group[] = $form->createElement('radio', 'allow_user_edit_announcement', get_lang('AllowUserEditAnnouncement'), get_lang('AllowUserEditAnnouncementActivate'), 1);
$group[] = $form->createElement('radio', 'allow_user_edit_announcement', null, get_lang('AllowUserEditAnnouncementDeactivate'), 0);
$form->addGroup($group, '', array(get_lang("AllowUserEditAnnouncement")));

$group = array();
$group[] = $form->createElement('radio', 'allow_user_image_forum', get_lang('AllowUserImageForum'), get_lang('AllowUserImageForumActivate'), 1);
$group[] = $form->createElement('radio', 'allow_user_image_forum', null, get_lang('AllowUserImageForumDeactivate'), 0);
$form->addGroup($group, '', array(get_lang("AllowUserImageForum")));

$group = array();
$group[] = $form->createElement('radio', 'allow_user_view_user_list', get_lang('AllowUserViewUserList'), get_lang('AllowUserViewUserListActivate'), 1);
$group[] = $form->createElement('radio', 'allow_user_view_user_list', null, get_lang('AllowUserViewUserListDeactivate'), 0);
$form->addGroup($group, '', array(get_lang("AllowUserViewUserList")));
$form->addButtonSave(get_lang('SaveSettings'), 'submit_save');
$form->addHtml('
        </div>
    </div>
');
$form->addHtml('</div>');

// CHAT SETTINGS
$form->addHtml('<div class="panel panel-default">');
$form->addHtml('
    <div class="panel-heading" role="tab" id="heading-chat-settings">
        <h4 class="panel-title">
            <a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion" href="#collapse-chat-settings" aria-expanded="false" aria-controls="collapse-chat-settings">
');
$form->addHtml(
    Display::return_icon('chat.png', get_lang('ConfigChat'), '', ICON_SIZE_SMALL).' '.get_lang('ConfigChat')
);
$form->addHtml('
            </a>
        </h4>
    </div>
');
$form->addHtml('
    <div id="collapse-chat-settings" class="panel-collapse collapse" role="tabpanel" aria-labelledby="heading-chat-settings">
        <div class="panel-body">
');

$group = array();
$group[] = $form->createElement('radio', 'allow_open_chat_window', get_lang('AllowOpenchatWindow'), get_lang('AllowOpenChatWindowActivate'), 1);
$group[] = $form->createElement('radio', 'allow_open_chat_window', null, get_lang('AllowOpenChatWindowDeactivate'), 0);
$form->addGroup($group, '', array(get_lang("AllowOpenchatWindow")));
$form->addButtonSave(get_lang('SaveSettings'), 'submit_save');

$form->addHtml('
        </div>
    </div>
');
$form->addHtml('</div>');

// LEARNING PATH
$form->addHtml('<div class="panel panel-default">');
$form->addHtml('
    <div class="panel-heading" role="tab" id="heading-learning-path">
        <h4 class="panel-title">
            <a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion" href="#collapse-learning-path" aria-expanded="false" aria-controls="collapse-learning-path">
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
    <div id="collapse-learning-path" class="panel-collapse collapse" role="tabpanel" aria-labelledby="heading-learning-path">
        <div class="panel-body">
');

// Auto launch LP
$group = array();
$group[] = $form->createElement('radio', 'enable_lp_auto_launch', get_lang('LPAutoLaunch'), get_lang('RedirectToALearningPath'), 1);
$group[] = $form->createElement('radio', 'enable_lp_auto_launch', get_lang('LPAutoLaunch'), get_lang('RedirectToTheLearningPathList'), 2);
$group[] = $form->createElement('radio', 'enable_lp_auto_launch', null, get_lang('Deactivate'), 0);
$form->addGroup($group, '', array(get_lang("LPAutoLaunch")));

if (api_get_setting('allow_course_theme') == 'true') {
    // Allow theme into Learning path
    $group = array();
    $group[] = $form->createElement('radio', 'allow_learning_path_theme', get_lang('AllowLearningPathTheme'), get_lang('AllowLearningPathThemeAllow'), 1);
    $group[] = $form->createElement('radio', 'allow_learning_path_theme', null, get_lang('AllowLearningPathThemeDisallow'), 0);
    $form->addGroup($group, '', array(get_lang("AllowLearningPathTheme")));
}

$allowLPReturnLink = api_get_setting('allow_lp_return_link');
if ($allowLPReturnLink === 'true') {
    $group = array(
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
        )
    );
    $form->addGroup($group, '', array(get_lang("LpReturnLink")));
}

$exerciseInvisible = api_get_setting('exercise_invisible_in_session');
$configureExerciseVisibility = api_get_setting('configure_exercise_visibility_in_course');

if ($exerciseInvisible === 'true' &&
    $configureExerciseVisibility === 'true'
) {
    $group = array(
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
        )
    );
    $form->addGroup($group, '', array(get_lang("ExerciseInvisibleInSession")));
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

// THEMATIC ADVANCE SETTINGS
$form->addHtml('<div class="panel panel-default">');
$form->addHtml('
    <div class="panel-heading" role="tab" id="heading-advance-settings">
        <h4 class="panel-title">
            <a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion" href="#collapse-advance-settings" aria-expanded="false" aria-controls="collapse-advance-settings">
');
$form->addHtml(
    Display::return_icon(
        'course_progress.png',
        get_lang('ThematicAdvanceConfiguration')
    ).' '.get_lang('ThematicAdvanceConfiguration')
);
$form->addHtml('
            </a>
        </h4>
    </div>
');
$form->addHtml('
    <div id="collapse-advance-settings" class="panel-collapse collapse" role="tabpanel" aria-labelledby="heading-advance-settings">
        <div class="panel-body">
');

$group = array();
$group[] = $form->createElement('radio', 'display_info_advance_inside_homecourse', get_lang('InfoAboutAdvanceInsideHomeCourse'), get_lang('DisplayAboutLastDoneAdvance'), 1);
$group[] = $form->createElement('radio', 'display_info_advance_inside_homecourse', null, get_lang('DisplayAboutNextAdvanceNotDone'), 2);
$group[] = $form->createElement('radio', 'display_info_advance_inside_homecourse', null, get_lang('DisplayAboutNextAdvanceNotDoneAndLastDoneAdvance'), 3);
$group[] = $form->createElement('radio', 'display_info_advance_inside_homecourse', null, get_lang('DoNotDisplayAnyAdvance'), 0);
$form->addGroup($group, '', array(get_lang("InfoAboutAdvanceInsideHomeCourse")));
$form->addButtonSave(get_lang('SaveSettings'), 'submit_save');
$form->addHtml('
        </div>
    </div>
');
$form->addHtml('</div>');

// Certificate settings
if (api_get_setting('allow_public_certificates') == 'true') {
    $form->addHtml('<div class="panel panel-default">');
    $form->addHtml('
        <div class="panel-heading" role="tab" id="heading-certificate-settings">
            <h4 class="panel-title">
                <a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion" href="#collapse-certificate-settings" aria-expanded="false" aria-controls="collapse-certificate-settings">
    ');
    $form->addHtml(
        Display::return_icon('certificate.png', get_lang('Certificates')).' '.get_lang('Certificates')
    );
    $form->addHtml('
                </a>
            </h4>
        </div>
    ');
    $form->addHtml('
        <div id="collapse-certificate-settings" class="panel-collapse collapse" role="tabpanel" aria-labelledby="heading-certificate-settings">
            <div class="panel-body">
    ');

    $group = array();
    $group[] = $form->createElement('radio', 'allow_public_certificates', get_lang('AllowPublicCertificates'), get_lang('Yes'), 1);
    $group[] = $form->createElement('radio', 'allow_public_certificates', null, get_lang('No'), 0);
    $form->addGroup($group, '', array(get_lang('AllowPublicCertificates')));
    $form->addButtonSave(get_lang('SaveSettings'), 'submit_save');
    $form->addHtml('
            </div>
        </div>
    ');
    $form->addHtml('</div>');
}

// Forum settings
$form->addHtml('<div class="panel panel-default">');
$form->addHtml('
    <div class="panel-heading" role="tab" id="heading-forum-settings">
        <h4 class="panel-title">
            <a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion" href="#collapse-forum-settings" aria-expanded="false" aria-controls="collapse-forum-settings">
');
$form->addHtml(
    Display::return_icon('forum.png', get_lang('Forum')).' '.get_lang('Forum')
);
$form->addHtml('
            </a>
        </h4>
    </div>
');
$form->addHtml('
    <div id="collapse-forum-settings" class="panel-collapse collapse" role="tabpanel" aria-labelledby="heading-forum-settings">
        <div class="panel-body">
');

$group = array(
    $form->createElement('radio', 'enable_forum_auto_launch', null, get_lang('RedirectToForumList'), 1),
    $form->createElement('radio', 'enable_forum_auto_launch', null, get_lang('Disabled'), 2),
);
$form->addGroup($group, '', array(get_lang('EnableForumAutoLaunch')));
$form->addButtonSave(get_lang('SaveSettings'), 'submit_save');
$form->addHtml('
        </div>
    </div>
');
$form->addHtml('</div>');


// Plugin course settings
$appPlugin = new AppPlugin();
$appPlugin->add_course_settings_form($form);

$form->addHtml('</div>');

// Get all the course information
$all_course_information = CourseManager::get_course_information($_course['sysCode']);

// Set the default values of the form
$values = array();
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

$courseSettings = CourseManager::getCourseSettingVariables($appPlugin);
foreach ($courseSettings as $setting) {
    $result = api_get_course_setting($setting);
    if ($result != '-1') {
        $values[$setting] = $result;
    }
}
$form->setDefaults($values);

// Validate form
if ($form->validate() && is_settings_editable()) {
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

    $pdf_export_watermark_path = isset($_FILES['pdf_export_watermark_path']) ? $_FILES['pdf_export_watermark_path'] : null;

    if (!empty($pdf_export_watermark_path['name'])) {
        $pdf_export_watermark_path_result = PDF::upload_watermark(
            $pdf_export_watermark_path['name'],
            $pdf_export_watermark_path['tmp_name'],
            $course_code
        );
        unset($updateValues['pdf_export_watermark_path']);
    }

    // Variables that will be saved in the TABLE_MAIN_COURSE table
    $update_in_course_table = array(
        'title',
        'course_language',
        'category_code',
        'department_name',
        'department_url',
        'visibility',
        'subscribe',
        'unsubscribe',
        'tutor_name',
        'course_registration_password',
        'legal',
        'activate_legal'
    );

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

    $appPlugin->saveCourseSettingsHook($updateValues);
    $cidReset = true;
    $cidReq = $course_code;
    Display::addFlash(Display::return_message(get_lang('Updated')));
    require '../inc/local.inc.php';
    $url = api_get_path(WEB_CODE_PATH).'course_info/infocours.php?'.api_get_cidreq();
    header("Location: $url");
    exit;
}

if ($show_delete_watermark_text_message) {
    Display::addFlash(Display::return_message(get_lang('FileDeleted'), 'normal'));
}

/*	Header */
Display::display_header($nameTools, MODULE_HELP_NAME);

// Display the form
echo '<div id="course_settings">';
$form->display();
echo '</div>';

Display::display_footer();
