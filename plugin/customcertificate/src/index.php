<?php
/* For licensing terms, see /license.txt */

$useDefault = false;
$isDefault = isset($_GET['default']) ? (int) $_GET['default'] : null;

if ($isDefault === 1) {
    $cidReset = true;
}

$course_plugin = 'customcertificate';
require_once __DIR__.'/../config.php';

$_setting['student_view_enabled'] = 'false';

$userId = api_get_user_id();
$plugin = CustomCertificatePlugin::create();
$nameTools = $plugin->get_lang('CertificateSetting');
$enable = $plugin->get('enable_plugin_customcertificate') == 'true';
$accessUrlId = api_get_current_access_url_id();
$course_info = api_get_course_info();

if ($isDefault === 1) {
    $courseId = 0;
    $courseCode = '';
    $sessionId = 0;
    $enableCourse = false;
    $useDefault = true;
    $defaultCertificate = 1;
    $nameTools = $plugin->get_lang('CertificateSettingDefault');
    $urlParams = '?default=1';
} else {
    $courseId = api_get_course_int_id();
    $courseCode = api_get_course_id();
    $sessionId = api_get_session_id();
    $enableCourse = api_get_course_setting('customcertificate_course_enable', $course_info) == 1 ? true : false;
    $useDefault = api_get_course_setting('use_certificate_default', $course_info) == 1 ? true : false;
    $defaultCertificate = 0;
    $urlParams = '?'.api_get_cidreq();
}

if (!$enable) {
    api_not_allowed(true, $plugin->get_lang('ToolDisabled'));
}

if (!$enableCourse && !$useDefault) {
    api_not_allowed(true, $plugin->get_lang('ToolDisabledCourse'));
}

if ($enableCourse && $useDefault) {
    api_not_allowed(true, $plugin->get_lang('ToolUseDefaultSettingCourse'));
}

$allow = api_is_platform_admin() || api_is_teacher();
if (!$allow) {
    api_not_allowed(true);
}

$table = Database::get_main_table(CustomCertificatePlugin::TABLE_CUSTOMCERTIFICATE);
$htmlHeadXtra[] = api_get_js_simple(
    api_get_path(WEB_PLUGIN_PATH).'customcertificate/resources/js/certificate.js'
);
$htmlHeadXtra[] = api_get_css_asset('cropper/dist/cropper.min.css');
$htmlHeadXtra[] = api_get_asset('cropper/dist/cropper.min.js');
$htmlHeadXtra[] = api_get_css(
    api_get_path(WEB_PLUGIN_PATH).'customcertificate/resources/css/form.css'
);
$htmlHeadXtra[] = '<script>
    $(function () {
        $("#delete_certificate").click(function (e) {
            e.preventDefault();
            e.stopPropagation();

            if (confirm("'.$plugin->get_lang("QuestionDelete").'")) {
                var courseId = '.$courseId.';
                var sessionId = '.$sessionId.';
                var accessUrlId = '.$accessUrlId.';
                var plugin_path = "'.api_get_path(WEB_PLUGIN_PATH).'";
                var ajax_path = plugin_path + "customcertificate/src/customcertificate.ajax.php?a=delete_certificate";
                $.ajax({
                    data: {courseId: courseId, sessionId: sessionId, accessUrlId: accessUrlId},
                    url: ajax_path,
                    type: "POST",
                    success: function (response) {
                        window.location.reload();
                    }
                }); 
            }
        });

    });
</script>';

// Get info certificate
$infoCertificate = CustomCertificatePlugin::getInfoCertificate($courseId, $sessionId, $accessUrlId);

$form = new FormValidator(
    'formEdit',
    'post',
    api_get_self().$urlParams,
    null,
    ['class' => 'form-vertical']
);

if ($form->validate()) {
    $formValues = $form->getSubmitValues();
    if (empty($formValues['contents'])) {
        $contents = '';
    } else {
        $contents = $formValues['contents'];
    }
    $check = Security::check_token('post');
    if ($check) {
        $date_start = str_replace('/', '-', $formValues['date_start']);
        $date_end = str_replace('/', '-', $formValues['date_end']);
        $params = [
            'access_url_id' => api_get_current_access_url_id(),
            'c_id' => $formValues['c_id'],
            'session_id' => $formValues['session_id'],
            'content_course' => $formValues['content_course'],
            'contents_type' => (int) $formValues['contents_type'],
            'contents' => $contents,
            'date_change' => intval($formValues['date_change']),
            'date_start' => date("Y-m-d", strtotime($date_start)),
            'date_end' => date("Y-m-d", strtotime($date_end)),
            'place' => $formValues['place'],
            'type_date_expediction' => (int) $formValues['type_date_expediction'],
            'day' => $formValues['day'],
            'month' => $formValues['month'],
            'year' => $formValues['year'],
            'signature_text1' => $formValues['signature_text1'],
            'signature_text2' => $formValues['signature_text2'],
            'signature_text3' => $formValues['signature_text3'],
            'signature_text4' => $formValues['signature_text4'],
            'margin_left' => (int) $formValues['margin_left'],
            'margin_right' => (int) $formValues['margin_right'],
            'certificate_default' => 0,
        ];

        if (intval($formValues['default_certificate'] == 1)) {
            $params['certificate_default'] = 1;
        }

        // Insert or Update
        if ($infoCertificate['id'] > 0) {
            $certificateId = $infoCertificate['id'];
            Database::update($table, $params, ['id = ?' => $certificateId]);
        } else {
            $certificateId = Database::insert($table, $params);
        }

        // Image manager
        $fieldList = [
            'logo_left',
            'logo_center',
            'logo_right',
            'seal',
            'signature1',
            'signature2',
            'signature3',
            'signature4',
            'background',
        ];

        foreach ($fieldList as $field) {
            $checkLogo[$field] = false;
            if (!empty($formValues['remove_'.$field]) || $_FILES[$field]['size']) {
                checkInstanceImage(
                    $certificateId,
                    $infoCertificate[$field],
                    $field
                );
            }

            if ($_FILES[$field]['size']) {
                $newPicture = api_upload_file(
                    'certificates',
                    $_FILES[$field],
                    $certificateId,
                    $formValues[$field.'_crop_result']
                );
                if ($newPicture) {
                    $sql = "UPDATE $table
                            SET $field = '".$newPicture['path_to_save']."'
                            WHERE id = $certificateId";
                    Database::query($sql);
                    $checkLogo[$field] = true;
                }
            }
        }

        // Certificate Default
        if (intval($formValues['use_default'] == 1)) {
            $infoCertificateDefault = CustomCertificatePlugin::getInfoCertificateDefault($accessUrlId);
            if (!empty($infoCertificateDefault)) {
                foreach ($fieldList as $field) {
                    if (!empty($infoCertificateDefault[$field]) && !$checkLogo[$field]) {
                        $sql = "UPDATE $table
                                SET $field = '".$infoCertificateDefault[$field]."'
                                WHERE id = $certificateId";
                        Database::query($sql);
                    }
                }
            }
        }

        Display::addFlash(Display::return_message(get_lang('Saved')));

        Security::clear_token();
        header('Location: '.api_get_self().$urlParams);
        exit;
    }
}

if (empty($infoCertificate)) {
    $infoCertificate = CustomCertificatePlugin::getInfoCertificateDefault($accessUrlId);

    if (empty($infoCertificate)) {
        $infoCertificate = [
            'type_date_expediction' => '',
            'year' => '',
            'month' => '',
            'day' => '',
            'date_change' => '',
        ];
    }
    $useDefault = true;
}

// Display the header
Display::display_header($nameTools);
$actionsLeft = Display::url(
    Display::return_icon('certificate.png', get_lang('Certificate'), '', ICON_SIZE_MEDIUM),
    'print_certificate.php'.$urlParams
);
if (!empty($courseId) && !$useDefault) {
    $actionsLeft .= Display::url(
        Display::return_icon('delete.png', $plugin->get_lang('DeleteCertificate'), '', ICON_SIZE_MEDIUM),
        'delete_certificate.php'.$urlParams,
        ['id' => 'delete_certificate']
    );
}

echo Display::toolbarAction(
    'toolbar-document',
    [$actionsLeft]
);

if ($useDefault && $courseId > 0) {
    echo Display::return_message(get_lang('InfoFromDefaultCertificate'), 'info');
}

// Student and course section
$form->addHtml('<fieldset><legend>'.strtoupper(get_lang('StudentCourseInfo')).'</legend>');
$form->addHtml('<div class="col-sm-8">');
$dir = '/';
$courseInfo = api_get_course_info();
$isAllowedToEdit = api_is_allowed_to_edit(null, true);
$editorConfig = [
    'ToolbarSet' => $isAllowedToEdit ? 'Documents' : 'DocumentsStudent',
    'Width' => '100%',
    'Height' => '300',
    'cols-size' => [0, 12, 0],
    'FullPage' => true,
    'InDocument' => true,
    'CreateDocumentDir' => api_get_path(WEB_COURSE_PATH).$courseInfo['path'].'/document/',
    'CreateDocumentWebDir' => api_get_path(WEB_COURSE_PATH).$courseInfo['path'].'/document/',
    'BaseHref' => api_get_path(WEB_COURSE_PATH).$courseInfo['path'].'/document'.$dir,
];
$form->addHtmlEditor(
    'content_course',
    '',
    false,
    true,
    $editorConfig,
    true
);
$form->addHtml('</div>');
$form->addHtml('<div class="col-sm-4">');
$strInfo = '((user_firstname))<br />';
$strInfo .= '((user_lastname))<br />';
$strInfo .= '((gradebook_institution))<br />';
$strInfo .= '((gradebook_sitename))<br />';
$strInfo .= '((teacher_firstname))<br />';
$strInfo .= '((teacher_lastname))<br />';
$strInfo .= '((official_code))<br />';
$strInfo .= '((date_certificate))<br />';
$strInfo .= '((date_certificate_no_time))<br />';
$strInfo .= '((date_simple_certificate))<br />';
$strInfo .= '((course_code))<br />';
$strInfo .= '((course_title))<br />';
$strInfo .= '((gradebook_grade))<br />';
$strInfo .= '((external_style))<br />';
$strInfo .= '((start_date))<br />';
$strInfo .= '((end_date))<br />';
$strInfo .= '((date_expediction))';

$createCertificate = get_lang('CreateCertificateWithTags');
$form->addElement(
    'html',
    Display::return_message($createCertificate.': <br />'.$strInfo, 'normal', false)
);
$form->addHtml('</div>');
$form->addHtml('</fieldset>');
$form->addHtml('<div class="clearfix"></div>');

// Contents section
$form->addHtml('<fieldset><legend>'.strtoupper(get_lang('Contents')).'</legend>');
$extra = '';
if (empty($infoCertificate['contents_type'])) {
    $infoCertificate['contents_type'] = 0;
    $extra = 'disabled';
}

$group = [];
$element = &$form->createElement(
    'radio',
    'contents_type',
    '',
    get_lang('ContentsCourseDescription'),
    0,
    ['id' => 'contents_type_0', 'onclick' => 'javascript: contentsTypeSwitchRadioButton();']
);

$group[] = $element;

$element = &$form->createElement(
    'radio',
    'contents_type',
    '',
    get_lang('ContentsIndexLearnpath'),
    1,
    ['id' => 'contents_type_1', 'onclick' => 'javascript: contentsTypeSwitchRadioButton();']
);
$group[] = $element;

$element = &$form->createElement(
    'radio',
    'contents_type',
    '',
    get_lang('ContentsHide'),
    3,
    ['id' => 'contents_type_3', 'onclick' => 'javascript: contentsTypeSwitchRadioButton();']
);
$group[] = $element;

$element = &$form->createElement(
    'radio',
    'contents_type',
    '',
    get_lang('ContentsCustom'),
    2,
    ['id' => 'contents_type_2', 'onclick' => 'javascript: contentsTypeSwitchRadioButton();']
);
$group[] = $element;

$form->addGroup(
    $group,
    'contents_type',
    get_lang('ContentsToShow'),
    null,
    false
);

$form->addHtml('<div id="contents-section">');
$editorConfig = [
    'ToolbarSet' => ($isAllowedToEdit ? 'Documents' : 'DocumentsStudent'),
    'Width' => '100%',
    'Height' => '200',
    'cols-size' => [2, 10, 0],
    'FullPage' => true,
    'InDocument' => true,
    'CreateDocumentDir' => api_get_path(WEB_COURSE_PATH).$courseInfo['path'].'/document/',
    'CreateDocumentWebDir' => api_get_path(WEB_COURSE_PATH).$courseInfo['path'].'/document/',
    'BaseHref' => api_get_path(WEB_COURSE_PATH).$courseInfo['path'].'/document'.$dir,
    'id' => 'contents',
    $extra,
];
$form->addHtmlEditor(
    'contents',
    get_lang('Contents'),
    false,
    true,
    $editorConfig,
    true
);
$form->addHtml('</div>');

// Dates section
$form->addHtml('<fieldset><legend>'.strtoupper(get_lang("Dates")).'</legend>');

$group = [];
$option1 = &$form->createElement(
    'radio',
    'date_change',
    '',
    get_lang('UseDateSessionAccess'),
    0,
    ['id' => 'date_change_0', 'onclick' => 'javascript: dateCertificateSwitchRadioButton0();']
);
$group[] = $option1;

$option2 = &$form->createElement(
    'radio',
    'date_change',
    '',
    get_lang('None'),
    2,
    ['id' => 'date_change_2', 'onclick' => 'javascript: dateCertificateSwitchRadioButton2();']
);
$group[] = $option2;

$option3 = &$form->createElement(
    'radio',
    'date_change',
    '',
    get_lang('Custom'),
    1,
    ['id' => 'date_change_1', 'onclick' => 'javascript: dateCertificateSwitchRadioButton1();']
);
$group[] = $option3;

$form->addGroup(
    $group,
    'date_change',
    get_lang('CourseDeliveryDates'),
    null,
    false
);
$form->addHtml('<div class="form-group" style="padding-top: 10px;">
        <label for="date_certificate" class="col-sm-2 control-label">&nbsp;</label>
        <div class="col-sm-10">
        <div class="radio" style="margin-top: -25px;">
            <span style="margin: 0 10px; font-style: italic;">'.get_lang('From').'</span>
            <input 
                size="20"
                autofocus="autofocus"
                class="form-control-cert text-center datepicker"
                name="date_start"
                id="date_start"
                type="text"
                value="'.(($infoCertificate['date_change'] == '1')
                        ? date("d/m/Y", strtotime($infoCertificate['date_start']))
                        : '').'"
                '.(($infoCertificate['date_change'] == '') ? 'disabled' : '').'
            >
            <span style="margin: 0 10px; font-style: italic;">'.get_lang('Until').'</span>
            <input
                size="20"
                class="form-control-cert text-center datepicker"
                name="date_end"
                id="date_end"
                type="text"
                value="'.(($infoCertificate['date_change'] == '1')
                        ? date("d/m/Y", strtotime($infoCertificate['date_end']))
                        : '').'"
                '.(($infoCertificate['date_change'] == "0") ? 'disabled' : '').'
            >
        </div>
        </div>
    </div>');

$form->addText(
    'place',
    get_lang('ExpectionPlace'),
    false,
    ['id' => 'place', 'cols-size' => [2, 5, 5], 'autofocus']
);

$group = [];
$option = &$form->createElement(
    'radio',
    'type_date_expediction',
    '',
    get_lang('UseDateEndAccessSession'),
    0,
    [
        'id' => 'type_date_expediction_0',
        'onclick' => 'javascript: dateCertificateSwitchRadioButton0();',
        (($sessionId == 0) ? 'disabled' : ''),
    ]
);
$group[] = $option;

$option = &$form->createElement(
    'radio',
    'type_date_expediction',
    '',
    get_lang('UseDateDownloadCertificate'),
    1,
    [
        'id' => 'type_date_expediction_1',
        'onclick' => 'javascript: typeDateExpedictionSwitchRadioButton();',
    ]
);
$group[] = $option;

$option = &$form->createElement(
    'radio',
    'type_date_expediction',
    '',
    get_lang('UseDateGenerationCertificate'),
    4,
    [
        'id' => 'type_date_expediction_4',
        'onclick' => 'javascript: typeDateExpedictionSwitchRadioButton();',
    ]
);
$group[] = $option;

$option = &$form->createElement(
    'radio',
    'type_date_expediction',
    '',
    get_lang('None'),
    3,
    [
        'id' => 'type_date_expediction_3',
        'onclick' => 'javascript: typeDateExpedictionSwitchRadioButton();',
    ]
);
$group[] = $option;

$option = &$form->createElement(
    'radio',
    'type_date_expediction',
    '',
    get_lang('UseCustomDate'),
    2,
    [
        'id' => 'type_date_expediction_2',
        'onclick' => 'javascript: typeDateExpedictionSwitchRadioButton();',
    ]
);
$group[] = $option;

$form->addGroup(
    $group,
    'type_date_expediction',
    get_lang('DateExpediction'),
    null,
    false
);

$form->addHtml(
    '<div class="form-group" style="padding-top: 10px;">
        <label for="date_certificate" class="col-sm-2 control-label">&nbsp;</label>
        <div class="col-sm-10">
        <div class="radio" style="margin-top: -25px;">
            <span class="certificado-text-label">a</span>
            <input
                size="4"
                autofocus="autofocus"
                class="form-control-cert text-center"
                name="day"
                id="day"
                type="text"
                value="'.$infoCertificate['day'].'"
                '.(($infoCertificate['type_date_expediction'] != '2') ? 'disabled' : '').'
            >
            <span class="certificado-text-label">de</span>
            <input
                size="10"
                autofocus="autofocus"
                class="form-control-cert text-center"
                name="month"
                id="month"
                type="text"
                value="'.$infoCertificate['month'].'"
                '.(($infoCertificate['type_date_expediction'] != '2') ? 'disabled' : '').'
            >
            <span class="certificado-text-label">de</span>
            <input
                size="4"
                autofocus="autofocus"
                class="form-control-cert text-center"
                name="year"
                id="year"
                type="text"
                value="'.$infoCertificate['year'].'"
                '.(($infoCertificate['type_date_expediction'] != '2') ? 'disabled' : '').'
            >
        </div>
        </div>
    </div>'
);
$form->addHtml('</fieldset>');

// Signature section
$base = api_get_path(WEB_UPLOAD_PATH);
$path = $base.'certificates/';
$form->addHtml('<fieldset><legend>'.strtoupper(get_lang('LogosSeal')).'</legend>');
// Logo 1
$form->addHtml('<div class="col-sm-6">');
$form->addFile(
    'logo_left',
    get_lang('LogoLeft'),
    [
        'id' => 'logo_left',
        'class' => 'picture-form',
        'crop_image' => true,
        'crop_scalable' => 'true',
    ]
);
$form->addProgress();
if (!empty($infoCertificate['logo_left'])) {
    $form->addElement('checkbox', 'remove_logo_left', null, get_lang('DelImage'));
    $form->addElement(
        'html',
        '<label class="col-sm-2">&nbsp;</label>
        <img src="'.$path.$infoCertificate['logo_left'].'" width="100"  />
        <br><br>'
    );
}
$allowedPictureTypes = api_get_supported_image_extensions(false);
$form->addRule(
    'logo_left',
    get_lang('OnlyImagesAllowed').' ('.implode(', ', $allowedPictureTypes).')',
    'filetype',
    $allowedPictureTypes
);
$form->addHtml('</div>');
// Logo 2
$form->addHtml('<div class="col-sm-6">');
$form->addFile(
    'logo_center',
    get_lang('LogoCenter'),
    [
        'id' => 'logo_center',
        'class' => 'picture-form',
        'crop_image' => true,
        'crop_scalable' => 'true',
    ]
);
$form->addProgress();
if (!empty($infoCertificate['logo_center'])) {
    $form->addElement('checkbox', 'remove_logo_center', null, get_lang('DelImage'));
    $form->addElement(
        'html',
        '<label class="col-sm-2">&nbsp;</label>
        <img src="'.$path.$infoCertificate['logo_center'].'" width="100"  />
        <br><br>'
    );
}
$allowedPictureTypes = api_get_supported_image_extensions(false);
$form->addRule(
    'logo_center',
    get_lang('OnlyImagesAllowed').' ('.implode(', ', $allowedPictureTypes).')',
    'filetype',
    $allowedPictureTypes
);
$form->addHtml('</div><div class="clearfix"></div>');
// Logo 3
$form->addHtml('<div class="col-sm-6">');
$form->addFile(
    'logo_right',
    get_lang('LogoRight'),
    [
        'id' => 'logo_right',
        'class' => 'picture-form',
        'crop_image' => true,
        'crop_scalable' => 'true',
    ]
);
$form->addProgress();
if (!empty($infoCertificate['logo_right'])) {
    $form->addElement('checkbox', 'remove_logo_right', null, get_lang('DelImage'));
    $form->addElement(
        'html',
        '<label class="col-sm-2">&nbsp;</label>
        <img src="'.$path.$infoCertificate['logo_right'].'" width="100"  />
        <br><br>'
    );
}
$tblProperty = api_get_supported_image_extensions(false);
$form->addRule(
    'logo_right',
    get_lang('OnlyImagesAllowed').' ('.implode(', ', $allowedPictureTypes).')',
    'filetype',
    $allowedPictureTypes
);
$form->addHtml('</div>');
$form->addHtml('<div class="col-sm-6">');
$form->addFile(
    'seal',
    get_lang('Seal'),
    [
        'id' => 'seal',
        'class' => 'picture-form',
        'crop_image' => true,
        'crop_scalable' => 'true',
    ]
);
$form->addProgress();
if (!empty($infoCertificate['seal'])) {
    $form->addElement('checkbox', 'remove_seal', null, get_lang('DelImage'));
    $form->addElement(
        'html',
        '<label class="col-sm-2">&nbsp;</label>
        <img src="'.$path.$infoCertificate['seal'].'" width="100"  />
        <br><br>'
    );
}
$allowedPictureTypes = api_get_supported_image_extensions(false);
$form->addRule(
    'seal',
    get_lang('OnlyImagesAllowed').' ('.implode(', ', $allowedPictureTypes).')',
    'filetype',
    $allowedPictureTypes
);
$form->addHtml('</div><div class="clearfix"></div>');
$form->addHtml('</fieldset>');
$form->addHtml('<fieldset><legend>'.strtoupper(get_lang('Signatures')).'</legend>');
// signature 1
$form->addHtml('<div class="col-sm-6">');
$form->addText(
    'signature_text1',
    get_lang('SignatureText1'),
    false,
    ['cols-size' => [2, 10, 0], 'autofocus']
);
$form->addFile(
    'signature1',
    get_lang('Signature1'),
    [
        'id' => 'signature1',
        'class' => 'picture-form',
        'crop_image' => true,
        'crop_scalable' => 'true',
    ]
);
$form->addProgress();
if (!empty($infoCertificate['signature1'])) {
    $form->addElement('checkbox', 'remove_signature1', null, get_lang('DelImage'));
    $form->addElement(
        'html',
        '<label class="col-sm-2">&nbsp;</label>
        <img src="'.$path.$infoCertificate['signature1'].'" width="100"  />
        <br><br>'
    );
}
$allowedPictureTypes = api_get_supported_image_extensions(false);
$form->addRule(
    'signature1',
    get_lang('OnlyImagesAllowed').' ('.implode(', ', $allowedPictureTypes).')',
    'filetype',
    $allowedPictureTypes
);
$form->addHtml('</div>');
// signature 2
$form->addHtml('<div class="col-sm-6">');
$form->addText(
    'signature_text2',
    get_lang('SignatureText2'),
    false,
    ['cols-size' => [2, 10, 0], 'autofocus']
);
$form->addFile(
    'signature2',
    get_lang('Signature2'),
    [
        'id' => 'signature2',
        'class' => 'picture-form',
        'crop_image' => true,
        'crop_scalable' => 'true',
    ]
);
$form->addProgress();
if (!empty($infoCertificate['signature2'])) {
    $form->addElement('checkbox', 'remove_signature2', null, get_lang('DelImage'));
    $form->addElement(
        'html',
        '<label class="col-sm-2">&nbsp;</label>
        <img src="'.$path.$infoCertificate['signature2'].'" width="100"  />
        <br><br>'
    );
}
$allowedPictureTypes = api_get_supported_image_extensions(false);
$form->addRule(
    'signature2',
    get_lang('OnlyImagesAllowed').' ('.implode(', ', $allowedPictureTypes).')',
    'filetype',
    $allowedPictureTypes
);
$form->addHtml('</div><div class="clearfix"></div>');
// signature 3
$form->addHtml('<div class="col-sm-6">');
$form->addText(
    'signature_text3',
    get_lang('SignatureText3'),
    false,
    ['cols-size' => [2, 10, 0], 'autofocus']
);
$form->addFile(
    'signature3',
    get_lang('Signature3'),
    [
        'id' => 'signature3',
        'class' => 'picture-form',
        'crop_image' => true,
        'crop_scalable' => 'true',
    ]
);
$form->addProgress();
if (!empty($infoCertificate['signature3'])) {
    $form->addElement('checkbox', 'remove_signature3', null, get_lang('DelImage'));
    $form->addElement(
        'html',
        '<label class="col-sm-2">&nbsp;</label>
        <img src="'.$path.$infoCertificate['signature3'].'" width="100" />
        <br><br>'
    );
}
$allowedPictureTypes = api_get_supported_image_extensions(false);
$form->addRule(
    'signature3',
    get_lang('OnlyImagesAllowed').' ('.implode(', ', $allowedPictureTypes).')',
    'filetype',
    $allowedPictureTypes
);
$form->addHtml('</div>');
// signature 4
$form->addHtml('<div class="col-sm-6">');
$form->addText(
    'signature_text4',
    get_lang('SignatureText4'),
    false,
    ['cols-size' => [2, 10, 0], 'autofocus']
);
$form->addFile(
    'signature4',
    get_lang('Signature4'),
    [
        'id' => 'signature4',
        'class' => 'picture-form',
        'crop_image' => true,
        'crop_scalable' => 'true',
    ]
);
$form->addProgress();
if (!empty($infoCertificate['signature4'])) {
    $form->addElement('checkbox', 'remove_signature4', null, get_lang('DelImage'));
    $form->addElement(
        'html',
        '<label class="col-sm-2">&nbsp;</label>
        <img src="'.$path.$infoCertificate['signature4'].'" width="100"  />
        <br><br>'
    );
}
$allowedPictureTypes = api_get_supported_image_extensions(false);
$form->addRule(
    'signature4',
    get_lang('OnlyImagesAllowed').' ('.implode(', ', $allowedPictureTypes).')',
    'filetype',
    $allowedPictureTypes
);
$form->addHtml('</div><div class="clearfix"></div>');
$form->addHtml('</fieldset><br>');
$form->addHtml('<div class="col-sm-6">');
$form->addHtml('<fieldset><legend>'.strtoupper(get_lang('BackgroundCertificate')).'</legend>');
// background
$form->addFile(
    'background',
    get_lang('Background'),
    [
        'id' => 'background',
        'class' => 'picture-form',
        'crop_image' => true,
        'crop_ratio' => '297 / 210',
    ]
);
$form->addProgress();
if (!empty($infoCertificate['background'])) {
    $form->addElement('checkbox', 'remove_background', null, get_lang('DelImage'));
    $form->addElement(
        'html',
        '<label class="col-sm-2">&nbsp;</label>
        <img src="'.$path.$infoCertificate['background'].'" width="100"  />
        <br><br>'
    );
}
$allowedPictureTypes = api_get_supported_image_extensions(false);
$form->addRule(
    'background',
    get_lang('OnlyImagesAllowed').' ('.implode(', ', $allowedPictureTypes).')',
    'filetype',
    $allowedPictureTypes
);
$form->addHtml('</fieldset>');
$form->addHtml('</div>');
$form->addHtml('<div class="col-sm-6">');
$form->addHtml('<fieldset><legend>'.strtoupper(get_lang('OtherOptions')).'</legend>');
$marginOptions = [];
$i = 0;
while ($i < 298) {
    $marginOptions[$i] = $i.' mm';
    $i++;
}
$form->addElement(
    'select',
    'margin_left',
    get_lang('MarginLeft'),
    $marginOptions,
    ['cols-size' => [4, 8, 0]]
);
$form->addElement(
    'select',
    'margin_right',
    get_lang('MarginRight'),
    $marginOptions,
    ['cols-size' => [4, 8, 0]]
);
$form->addHtml('</fieldset>');
$form->addHtml('</div>');
$form->addHtml('<div class="clearfix"></div>');

$form->addButton(
    'submit',
    get_lang('SaveCertificate'),
    'check',
    'primary',
    null,
    null,
    ['cols-size' => [5, 2, 5]],
    false
);

$form->addElement('hidden', 'formSent');
$infoCertificate['formSent'] = 1;
$form->setDefaults($infoCertificate);
$token = Security::get_token();
$form->addElement('hidden', 'sec_token');
$form->addElement('hidden', 'use_default');
$form->addElement('hidden', 'default_certificate');
$form->addElement('hidden', 'c_id');
$form->addElement('hidden', 'session_id');
$form->setConstants(
    [
        'sec_token' => $token,
        'use_default' => $useDefault,
        'default_certificate' => $defaultCertificate,
        'c_id' => $courseId,
        'session_id' => $sessionId,
    ]
);
echo '<div class="page-create">';
echo '<div class="row" style="overflow:hidden">';
echo '<div id="doc_form" class="col-md-12">';
echo $form->returnForm();
echo '</div>';
echo '</div>';
echo '</div>';
Display::display_footer();

/**
 * Delete the file if there is only one instance.
 *
 * @param int    $certificateId
 * @param string $imagePath
 * @param string $field
 * @param string $type
 */
function checkInstanceImage($certificateId, $imagePath, $field, $type = 'certificates')
{
    $table = Database::get_main_table(CustomCertificatePlugin::TABLE_CUSTOMCERTIFICATE);
    $imagePath = Database::escape_string($imagePath);
    $field = Database::escape_string($field);
    $certificateId = (int) $certificateId;

    $sql = "SELECT * FROM $table WHERE $field = '$imagePath'";
    $res = Database::query($sql);
    if (Database::num_rows($res) == 1) {
        api_remove_uploaded_file($type, $imagePath);
    }

    $sql = "UPDATE $table SET $field = '' WHERE id = $certificateId";
    Database::query($sql);
}
