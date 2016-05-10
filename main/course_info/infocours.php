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

require_once '../inc/global.inc.php';
$current_course_tool  = TOOL_COURSE_SETTING;
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
$course_access_settings = CourseManager:: get_access_settings($course_code);

//LOGIC FUNCTIONS
function is_settings_editable()
{
    return isset($GLOBALS['course_info_is_editable']) && $GLOBALS['course_info_is_editable'];
}

/* MAIN CODE */
if (!$is_allowedToEdit) {
    api_not_allowed(true);
}

$htmlHeadXtra[] = '<link  href="'. api_get_path(WEB_PATH) .'web/assets/cropper/dist/cropper.min.css" rel="stylesheet">';
$htmlHeadXtra[] = '<script src="'. api_get_path(WEB_PATH) .'web/assets/cropper/dist/cropper.min.js"></script>';
$htmlHeadXtra[] = '<script>
$(document).ready(function() {
    var $image = $("#previewImage");
    var $input = $("[name=\'cropResult\']");
    var $cropButton = $("#cropButton");
    var canvas = "";
    var imageWidth = "";
    var imageHeight = "";
    
    $("input:file").change(function() {
        var oFReader = new FileReader();
        oFReader.readAsDataURL(document.getElementById("picture").files[0]);

        oFReader.onload = function (oFREvent) {
            $image.attr("src", this.result);
            $("#labelCropImage").html("'.get_lang('Preview').'");
            $("#cropImage").addClass("thumbnail");
            $cropButton.removeClass("hidden");
            // Destroy cropper
            $image.cropper("destroy");

            $image.cropper({
                aspectRatio: 16 / 9,
                responsive : true,
                center : false,
                guides : false,
                movable: false,
                zoomable: false,
                rotatable: false,
                scalable: false,
                crop: function(e) {
                    // Output the result data for cropping image.
                    $input.val(e.x+","+e.y+","+e.width+","+e.height);
                }
            });
        };
    });
    
    $("#cropButton").on("click", function() {
        var canvas = $image.cropper("getCroppedCanvas");
        var dataUrl = canvas.toDataURL();
        $image.attr("src", dataUrl);
        $image.cropper("destroy");
        $cropButton.addClass("hidden");
        return false;
    });
});
</script>';

$show_delete_watermark_text_message = false;
if (api_get_setting('pdf_export_watermark_by_course') == 'true') {
    if (isset($_GET['delete_watermark'])) {
        PDF::delete_watermark($course_code);
        $show_delete_watermark_text_message = true;
    }
}
$tbl_user = Database:: get_main_table(TABLE_MAIN_USER);
$tbl_admin = Database:: get_main_table(TABLE_MAIN_ADMIN);
$tbl_course_user = Database:: get_main_table(TABLE_MAIN_COURSE_USER);
$tbl_course = Database:: get_main_table(TABLE_MAIN_COURSE);

$s_select_course_tutor_name = "SELECT tutor_name FROM $tbl_course WHERE id = $courseId";
$q_tutor = Database::query($s_select_course_tutor_name);
$s_tutor = Database::result($q_tutor, 0, 'tutor_name');

$target_name = api_sort_by_first_name() ? 'firstname' : 'lastname';
$s_sql_course_titular = "SELECT DISTINCT username, lastname, firstname
    FROM $tbl_user as user, $tbl_course_user as course_rel_user
    WHERE (course_rel_user.status='1') AND user.user_id=course_rel_user.user_id AND c_id ='".$courseId."'
    ORDER BY ".$target_name." ASC";
$q_result_titulars = Database::query($s_sql_course_titular);

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

$categories = getCategoriesCanBeAddedInCourse($_course['categoryCode']);

$linebreak = '<div class="row"><div class="label"></div><div class="formw" style="border-bottom:1px dashed grey"></div></div>';

// Build the form
$form = new FormValidator('update_course', 'post', api_get_self().'?'.api_get_cidreq());

// COURSE SETTINGS
$form->addElement('html', '<div><h3>'.Display::return_icon('settings.png', Security::remove_XSS(get_lang('CourseSettings')),'',ICON_SIZE_SMALL).' '.Security::remove_XSS(get_lang('CourseSettings')).'</h3><div>');

$image_html = '';


// Display course picture
$course_path = api_get_path(SYS_COURSE_PATH).$currentCourseRepository;   // course path

if (file_exists($course_path.'/course-pic85x85.png')) {
    $course_web_path = api_get_path(WEB_COURSE_PATH).$currentCourseRepository;   // course web path
    $course_medium_image = $course_web_path.'/course-pic85x85.png?'.rand(1, 1000); // redimensioned image 85x85
    $image_html =  '<div class="row"><label class="col-md-2 control-label">'.get_lang('Image').'</label> <div class="col-md-8"><img src="'.$course_medium_image.'" /></div></div>';
}
$form->addElement('html', $image_html);

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

$form->addElement('select_language', 'course_language', array(get_lang('Ln'), get_lang('TipLang')));
$form->addText('department_name', get_lang('Department'), false);
$form->applyFilter('department_name', 'html_filter');
$form->applyFilter('department_name', 'trim');

$form->addText('department_url', get_lang('DepartmentUrl'), false);
$form->applyFilter('department_url', 'html_filter');

// Picture
$form->addElement('file', 'picture', get_lang('AddPicture'), array('id' => 'picture', 'class' => 'picture-form'));
$form->addHtml('<div class="form-group">
                <label for="cropImage" id="labelCropImage" class="col-sm-2 control-label">
                </label>
                <div class="col-sm-8">
                    <div id="cropImage" class="cropCanvas">
                        <img id="previewImage" >
                    </div>
                    <div>
                        <button class="btn btn-primary hidden" type="button" name="cropButton" id="cropButton">
                            <em class="fa fa-crop"></em> '.get_lang('CropYourPicture').'
                        </button>
                    </div>
                </div>
            </div>
');
$form->addHidden('cropResult', '');
$allowed_picture_types = array ('jpg', 'jpeg', 'png', 'gif');
$form->addRule(
    'picture',
    get_lang('OnlyImagesAllowed').' ('.implode(',', $allowed_picture_types).')',
    'filetype',
    $allowed_picture_types
);
$form->addElement('checkbox', 'delete_picture', null, get_lang('DeletePicture'));

if (api_get_setting('pdf_export_watermark_by_course') == 'true') {
    $url =  PDF::get_watermark($course_code);
    $form->addText('pdf_export_watermark_text', get_lang('PDFExportWatermarkTextTitle'), false, array('size' => '60'));
    $form->addElement('file', 'pdf_export_watermark_path', get_lang('AddWaterMark'));
    if ($url != false) {
        $delete_url = '<a href="?delete_watermark">'.Display::return_icon('delete.png',get_lang('DelImage')).'</a>';
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
    $form->addGroup($group, '', array(get_lang("Stylesheets")), '');
}

$form->addElement('label', get_lang('DocumentQuota'), format_file_size(DocumentManager::get_course_quota()));
$form->addButtonSave(get_lang('SaveSettings'),'submit_save');
$form->addElement('html', '</div></div>');

// COURSE ACCESS

$form->addElement('html', '<div> <h3>'.Display::return_icon('course.png', Security::remove_XSS(get_lang('CourseAccess')),'',ICON_SIZE_SMALL).' '.Security::remove_XSS(get_lang('CourseAccess')).'</h3><div>');

$group = array();
$group[]= $form->createElement('radio', 'visibility', get_lang("CourseAccess"), get_lang('OpenToTheWorld'), COURSE_VISIBILITY_OPEN_WORLD);
$group[]= $form->createElement('radio', 'visibility', null, get_lang('OpenToThePlatform'), COURSE_VISIBILITY_OPEN_PLATFORM);
$group[]= $form->createElement('radio', 'visibility', null, get_lang('Private'), COURSE_VISIBILITY_REGISTERED);
$group[]= $form->createElement('radio', 'visibility', null, get_lang('CourseVisibilityClosed'), COURSE_VISIBILITY_CLOSED);
// The "hidden" visibility is only available to portal admins
if (api_is_platform_admin()) {
    $group[]= $form->createElement('radio', 'visibility', null, get_lang('CourseVisibilityHidden'), COURSE_VISIBILITY_HIDDEN);
}
$form->addGroup($group, '', array(get_lang("CourseAccess"), get_lang("CourseAccessConfigTip")), '');

$url = api_get_path(WEB_CODE_PATH)."auth/inscription.php?c=$course_code&e=1";
$url = Display::url($url, $url);
$form->addElement('label', get_lang('DirectLink'), sprintf(get_lang('CourseSettingsRegisterDirectLink'), $url));

$group = array();
$group[]=$form->createElement('radio', 'subscribe', get_lang('Subscription'), get_lang('Allowed'), 1);
$group[]=$form->createElement('radio', 'subscribe', null, get_lang('Denied'), 0);
$form->addGroup($group, '', array(get_lang("Subscription")), '');

$group = array();
$group[]=$form->createElement('radio', 'unsubscribe', get_lang('Unsubscription'), get_lang('AllowedToUnsubscribe'), 1);
$group[]=$form->createElement('radio', 'unsubscribe', null, get_lang('NotAllowedToUnsubscribe'), 0);
$form->addGroup($group, '', array(get_lang("Unsubscription")), '');

$form->addText('course_registration_password', get_lang('CourseRegistrationPassword'), false, array('size' => '60'));

$form->addElement('checkbox', 'activate_legal', array(null, get_lang('ShowALegalNoticeWhenEnteringTheCourse')), get_lang('ActivateLegal'));
$form->addElement('textarea', 'legal', get_lang('CourseLegalAgreement'), array('rows' => 8));
$form->addButtonSave(get_lang('SaveSettings'), 'submit_save');

$form->addElement('html', '</div></div>');

// Documents
if (api_get_setting('documents_default_visibility_defined_in_course') == 'true') {
    $form->addElement('html', '<div> <h3>'.Display::return_icon('folder.png', Security::remove_XSS(get_lang('Documents')),'',ICON_SIZE_SMALL).' '.Security::remove_XSS(get_lang('Documents')).'</h3><div>');

    $group = array(
        $form->createElement('radio', 'documents_default_visibility', null, get_lang('Visible'), 'visible'),
        $form->createElement('radio', 'documents_default_visibility', null, get_lang('Invisible'), 'invisible')
    );
    $form->addGroup($group, '', array(get_lang("DocumentsDefaultVisibility")), '');
    $form->addButtonSave(get_lang('SaveSettings'),'submit_save');
    $form->addElement('html', '</div></div>');
}

// EMAIL NOTIFICATIONS
$form->addElement('html', '<div> <h3>'.Display::return_icon('mail.png', Security::remove_XSS(get_lang('EmailNotifications')),'',ICON_SIZE_SMALL).' '.Security::remove_XSS(get_lang('EmailNotifications')).'</h3><div>');

$group = array();
$group[]=$form->createElement('radio', 'email_alert_to_teacher_on_new_user_in_course', get_lang('NewUserEmailAlert'), get_lang('NewUserEmailAlertEnable'), 1);
$group[]=$form->createElement('radio', 'email_alert_to_teacher_on_new_user_in_course', null, get_lang('NewUserEmailAlertToTeacharAndTutor'), 2);
$group[]=$form->createElement('radio', 'email_alert_to_teacher_on_new_user_in_course', null, get_lang('NewUserEmailAlertDisable'), 0);
$form->addGroup($group, '', array(get_lang("NewUserEmailAlert")), '');

$group = array();
$group[]=$form->createElement('radio', 'email_alert_students_on_new_homework', get_lang('NewHomeworkEmailAlert'), get_lang('NewHomeworkEmailAlertEnable'), 1);
$group[]=$form->createElement('radio', 'email_alert_students_on_new_homework', null, get_lang('NewHomeworkEmailAlertDisable'), 0);
$form->addGroup($group, '', array(get_lang("NewHomeworkEmailAlert")), '');

$group = array();
$group[]=$form->createElement('radio', 'email_alert_manager_on_new_doc', null, get_lang('WorkEmailAlertActivateOnlyForTeachers'), 3);
$group[]=$form->createElement('radio', 'email_alert_manager_on_new_doc', null, get_lang('WorkEmailAlertActivateOnlyForStudents'), 2);
$group[]=$form->createElement('radio', 'email_alert_manager_on_new_doc', get_lang('WorkEmailAlert'), get_lang('WorkEmailAlertActivate'), 1);
$group[]=$form->createElement('radio', 'email_alert_manager_on_new_doc', null, get_lang('WorkEmailAlertDeactivate'), 0);
$form->addGroup($group, '', array(get_lang("WorkEmailAlert")), '');

$group = array();
$group[]=$form->createElement('radio', 'email_alert_on_new_doc_dropbox', get_lang('DropboxEmailAlert'), get_lang('DropboxEmailAlertActivate'), 1);
$group[]=$form->createElement('radio', 'email_alert_on_new_doc_dropbox', null, get_lang('DropboxEmailAlertDeactivate'), 0);
$form->addGroup($group, '', array(get_lang("DropboxEmailAlert")), '');

$group = array();
$group[]=$form->createElement('radio', 'email_alert_manager_on_new_quiz', get_lang('QuizEmailAlert'), get_lang('QuizEmailAlertActivate'), 1);
$group[]=$form->createElement('radio', 'email_alert_manager_on_new_quiz', null, get_lang('QuizEmailAlertDeactivate'), 0);
$form->addGroup($group, '', array(get_lang("QuizEmailAlert")), '');
$form->addButtonSave(get_lang('SaveSettings'), 'submit_save');

$form->addElement('html', '</div></div>');

// USER RIGHTS
$form->addElement('html', '<div> <h3>'.Display::return_icon('user.png', Security::remove_XSS(get_lang('UserRights')),'',ICON_SIZE_SMALL).' '.Security::remove_XSS(get_lang('UserRights')).'</h3><div>');

$group = array();
$group[]=$form->createElement('radio', 'allow_user_edit_agenda', get_lang('AllowUserEditAgenda'), get_lang('AllowUserEditAgendaActivate'), 1);
$group[]=$form->createElement('radio', 'allow_user_edit_agenda', null, get_lang('AllowUserEditAgendaDeactivate'), 0);
$form->addGroup($group, '', array(get_lang("AllowUserEditAgenda")), '');

$group = array();
$group[]=$form->createElement('radio', 'allow_user_edit_announcement', get_lang('AllowUserEditAnnouncement'), get_lang('AllowUserEditAnnouncementActivate'), 1);
$group[]=$form->createElement('radio', 'allow_user_edit_announcement', null, get_lang('AllowUserEditAnnouncementDeactivate'), 0);
$form->addGroup($group, '', array(get_lang("AllowUserEditAnnouncement")), '');

$group = array();
$group[]=$form->createElement('radio', 'allow_user_image_forum', get_lang('AllowUserImageForum'), get_lang('AllowUserImageForumActivate'), 1);
$group[]=$form->createElement('radio', 'allow_user_image_forum', null, get_lang('AllowUserImageForumDeactivate'), 0);
$form->addGroup($group, '', array(get_lang("AllowUserImageForum")), '');

$group = array();
$group[]=$form->createElement('radio', 'allow_user_view_user_list', get_lang('AllowUserViewUserList'), get_lang('AllowUserViewUserListActivate'), 1);
$group[]=$form->createElement('radio', 'allow_user_view_user_list', null, get_lang('AllowUserViewUserListDeactivate'), 0);
$form->addGroup($group, '', array(get_lang("AllowUserViewUserList")), '');
$form->addButtonSave(get_lang('SaveSettings'), 'submit_save');
$form->addElement('html', '</div></div>');

// CHAT SETTINGS
$form->addElement('html', '<div><h3>'.Display::return_icon('chat.png', Security::remove_XSS(get_lang('ConfigChat')),'',ICON_SIZE_SMALL).' '.Security::remove_XSS(get_lang('ConfigChat')).'</h3><div>');

$group = array();
$group[]=$form->createElement('radio', 'allow_open_chat_window', get_lang('AllowOpenchatWindow'), get_lang('AllowOpenChatWindowActivate'), 1);
$group[]=$form->createElement('radio', 'allow_open_chat_window', null, get_lang('AllowOpenChatWindowDeactivate'), 0);
$form->addGroup($group, '', array(get_lang("AllowOpenchatWindow")), '');
$form->addButtonSave(get_lang('SaveSettings'), 'submit_save');
$form->addElement('html', '</div></div>');

// LEARNING PATH
$form->addElement('html', '<div><h3>'.Display::return_icon('scorms.png', get_lang('ConfigLearnpath'),'',ICON_SIZE_SMALL).' '.Security::remove_XSS(get_lang('ConfigLearnpath')).'</h3><div>');

// Auto launch LP
$group = array();
$group[]=$form->createElement('radio', 'enable_lp_auto_launch', get_lang('LPAutoLaunch'), get_lang('RedirectToALearningPath'), 1);
$group[]=$form->createElement('radio', 'enable_lp_auto_launch', get_lang('LPAutoLaunch'), get_lang('RedirectToTheLearningPathList'), 2);
$group[]=$form->createElement('radio', 'enable_lp_auto_launch', null, get_lang('Deactivate'), 0);
$form->addGroup($group, '', array(get_lang("LPAutoLaunch")), '');

if (api_get_setting('allow_course_theme') == 'true') {
    // Allow theme into Learning path
    $group = array();
    $group[]=$form->createElement('radio', 'allow_learning_path_theme', get_lang('AllowLearningPathTheme'), get_lang('AllowLearningPathThemeAllow'), 1);
    $group[]=$form->createElement('radio', 'allow_learning_path_theme', null, get_lang('AllowLearningPathThemeDisallow'), 0);
    $form->addGroup($group, '', array(get_lang("AllowLearningPathTheme")), '');
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
    $form->addGroup($group, '', array(get_lang("LpReturnLink")), '');
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
$form->addElement('html', '</div></div>');

// THEMATIC ADVANCE SETTINGS
$form->addElement(
    'html',
    '<div><h3>'.Display::return_icon(
    'course_progress.png',
    Security::remove_XSS(get_lang('ThematicAdvanceConfiguration')),'',ICON_SIZE_SMALL
    ).' '.Security::remove_XSS(get_lang('ThematicAdvanceConfiguration')).'</h3><div>'
);

$group = array();
$group[]=$form->createElement('radio', 'display_info_advance_inside_homecourse', get_lang('InfoAboutAdvanceInsideHomeCourse'), get_lang('DisplayAboutLastDoneAdvance'), 1);
$group[]=$form->createElement('radio', 'display_info_advance_inside_homecourse', null, get_lang('DisplayAboutNextAdvanceNotDone'), 2);
$group[]=$form->createElement('radio', 'display_info_advance_inside_homecourse', null, get_lang('DisplayAboutNextAdvanceNotDoneAndLastDoneAdvance'), 3);
$group[]=$form->createElement('radio', 'display_info_advance_inside_homecourse', null, get_lang('DoNotDisplayAnyAdvance'), 0);
$form->addGroup($group, '', array(get_lang("InfoAboutAdvanceInsideHomeCourse")), '');
$form->addButtonSave(get_lang('SaveSettings'), 'submit_save');
$form->addElement('html', '</div></div>');

// Document settings
$form->addElement('html', '<div><h3>'.Display::return_icon('folder.png', Security::remove_XSS(get_lang('Documents')),'',ICON_SIZE_SMALL).' '.Security::remove_XSS(get_lang('Documents')).'</h3><div>');

$group = array(
    $form->createElement('radio', 'show_system_folders', null, get_lang('Yes'), 1),
    $form->createElement('radio', 'show_system_folders', null, get_lang('No'), 2),

);
$form->addGroup($group, '', array(get_lang("ShowSystemFolders")), '');
$form->addButtonSave(get_lang('SaveSettings'), 'submit_save');
$form->addElement('html', '</div></div>');

// Certificate settings
if (api_get_setting('allow_public_certificates')=='true') {
    $form->addElement('html', '<div><h3>'.Display::return_icon('certificate.png', Security::remove_XSS(get_lang('Certificates')),'',ICON_SIZE_SMALL).' '.Security::remove_XSS(get_lang('Certificates')).'</h3><div>');
    $group = array();
    $group[]=$form->createElement('radio', 'allow_public_certificates', get_lang('AllowPublicCertificates'), get_lang('Yes'), 1);
    $group[]=$form->createElement('radio', 'allow_public_certificates', null, get_lang('No'), 0);
    $form->addGroup($group, '', array(get_lang("AllowPublicCertificates")), '');
    $form->addButtonSave(get_lang('SaveSettings'), 'submit_save');
    $form->addElement('html', '</div></div>');
}

// Plugin course settings

$appPlugin = new AppPlugin();
$appPlugin->add_course_settings_form($form);

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
$values['subscribe'] = $course_access_settings['subscribe'];
$values['unsubscribe'] = $course_access_settings['unsubscribe'];
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
            $course_code,
            $picture['name'],
            $picture['tmp_name'],
            $updateValues['cropResult']
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
                api_set_failure(get_lang('PortalActiveCoursesLimitReached'));
                $url = api_get_path(WEB_CODE_PATH).'course_info/infocours.php?action=course_active_warning&cidReq='.$course_code;
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

    //Variables that will be saved in the TABLE_MAIN_COURSE table
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
    $table_course = Database :: get_main_table(TABLE_MAIN_COURSE);

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
    require '../inc/local.inc.php';
    $url = api_get_path(WEB_CODE_PATH).'course_info/infocours.php?action=show_message&cidReq='.$course_code;
    header("Location: $url");
    exit;
}

/*	Header */

Display :: display_header($nameTools, MODULE_HELP_NAME);
if ($show_delete_watermark_text_message) {
    Display :: display_normal_message(get_lang('FileDeleted'));
}

if (isset($_GET['action']) && $_GET['action'] == 'show_message') {
    Display :: display_normal_message(get_lang('ModifDone'));
}
if (isset($_GET['action']) && $_GET['action'] == 'course_active_warning') {
    Display :: display_warning_message(get_lang('PortalActiveCoursesLimitReached'));
}

echo '<script>
$(function() {
	$("#course_settings").accordion({
		autoHeight: false,
		heightStyle: "content",
		header: "div > h3"
	});
});
</script>';

// Display the form
echo '<div id="course_settings">';
$form->display();
echo '</div>';

Display::display_footer();
