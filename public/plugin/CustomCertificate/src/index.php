<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Enums\ActionIcon;
use Chamilo\CoreBundle\Enums\ObjectIcon;

$useDefault = false;
$isDefault = isset($_GET['default']) ? (int) $_GET['default'] : null;

if (1 === $isDefault) {
    $cidReset = true;
}

$course_plugin = 'CustomCertificate';
require_once __DIR__.'/../config.php';

$_setting['student_view_enabled'] = 'false';

$userId = api_get_user_id();
$plugin = CustomCertificatePlugin::create();
$nameTools = $plugin->get_lang('CertificateSetting');
$enable = $plugin->isEnabled(true);
$accessUrlId = api_get_current_access_url_id();
$course_info = api_get_course_info();

if (1 === $isDefault) {
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
    $enableCourse = 1 == api_get_course_setting('customcertificate_course_enable', $course_info) ? true : false;
    $useDefault = 1 == api_get_course_setting('use_certificate_default', $course_info) ? true : false;
    $defaultCertificate = 0;
    $urlParams = '?'.api_get_cidreq();
}

if (!$enable) {
    api_not_allowed(true, $plugin->get_lang('ToolDisabled'));
}

if (!$enableCourse && !$useDefault) {
    $backUrl = api_get_path(WEB_CODE_PATH).'course_info/infocours.php';
    if (!empty(api_get_cidreq())) {
        $backUrl .= '?'.api_get_cidreq();
    }

    Display::display_header($nameTools);
    echo Display::return_message($plugin->get_lang('ToolDisabledCourse'), 'warning');
    echo '<p>';
    echo Display::url(get_lang('Back'), $backUrl, ['class' => 'btn btn--plain']);
    echo '</p>';
    Display::display_footer();
    exit;
}

if ($enableCourse && $useDefault) {
    $backUrl = api_get_path(WEB_CODE_PATH).'course_info/infocours.php';
    if (!empty(api_get_cidreq())) {
        $backUrl .= '?'.api_get_cidreq();
    }

    Display::display_header($nameTools);
    echo Display::return_message($plugin->get_lang('ToolUseDefaultSettingCourse'), 'warning');
    echo '<p>';
    echo Display::url(get_lang('Back'), $backUrl, ['class' => 'btn btn--plain']);
    echo '</p>';
    Display::display_footer();
    exit;
}

$allow = api_is_platform_admin() || api_is_teacher();
if (!$allow) {
    api_not_allowed(true);
}

$table = Database::get_main_table(CustomCertificatePlugin::TABLE_CUSTOMCERTIFICATE);
$infoCertificate = CustomCertificatePlugin::getInfoCertificate($courseId, $sessionId, $accessUrlId);

if ('POST' === strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? ''))) {
    $formValues = $_POST;

    if (!Security::check_token('post')) {
        Display::addFlash(Display::return_message(get_lang('Invalid token'), 'error'));
        Security::clear_token();
        header('Location: '.api_get_self().$urlParams);
        exit;
    }

    $contents = '';
    if (!empty($formValues['contents'])) {
        $contents = (string) $formValues['contents'];
    }

    $params = [
        'access_url_id' => $accessUrlId,
        'c_id' => $courseId,
        'session_id' => $sessionId,
        'content_course' => Security::remove_XSS((string) ($formValues['content_course'] ?? '')),
        'contents_type' => (int) ($formValues['contents_type'] ?? 0),
        'contents' => Security::remove_XSS($contents),
        'date_change' => (int) ($formValues['date_change'] ?? 0),
        'date_start' => customcertificate_normalize_datetime((string) ($formValues['date_start'] ?? '')),
        'date_end' => customcertificate_normalize_datetime((string) ($formValues['date_end'] ?? '')),
        'place' => Security::remove_XSS((string) ($formValues['place'] ?? '')),
        'type_date_expediction' => (int) ($formValues['type_date_expediction'] ?? 0),
        'day' => Security::remove_XSS((string) ($formValues['day'] ?? '')),
        'month' => Security::remove_XSS((string) ($formValues['month'] ?? '')),
        'year' => Security::remove_XSS((string) ($formValues['year'] ?? '')),
        'logo_left' => (string) ($infoCertificate['logo_left'] ?? ''),
        'logo_center' => (string) ($infoCertificate['logo_center'] ?? ''),
        'logo_right' => (string) ($infoCertificate['logo_right'] ?? ''),
        'seal' => (string) ($infoCertificate['seal'] ?? ''),
        'signature1' => (string) ($infoCertificate['signature1'] ?? ''),
        'signature2' => (string) ($infoCertificate['signature2'] ?? ''),
        'signature3' => (string) ($infoCertificate['signature3'] ?? ''),
        'signature4' => (string) ($infoCertificate['signature4'] ?? ''),
        'signature_text1' => Security::remove_XSS((string) ($formValues['signature_text1'] ?? '')),
        'signature_text2' => Security::remove_XSS((string) ($formValues['signature_text2'] ?? '')),
        'signature_text3' => Security::remove_XSS((string) ($formValues['signature_text3'] ?? '')),
        'signature_text4' => Security::remove_XSS((string) ($formValues['signature_text4'] ?? '')),
        'background' => (string) ($infoCertificate['background'] ?? ''),
        'margin_left' => (int) ($formValues['margin_left'] ?? 0),
        'margin_right' => (int) ($formValues['margin_right'] ?? 0),
        'certificate_default' => $defaultCertificate,
    ];

    if (!empty($infoCertificate['id']) && $infoCertificate['id'] > 0) {
        $certificateId = (int) $infoCertificate['id'];
        Database::update($table, $params, ['id = ?' => $certificateId]);
    } else {
        $certificateId = (int) Database::insert($table, $params);
    }

    if ($certificateId <= 0) {
        Display::addFlash(Display::return_message($plugin->get_lang('CouldNotSaveCertificate'), 'error'));
        Security::clear_token();
        header('Location: '.api_get_self().$urlParams);
        exit;
    }

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

        if (!empty($formValues['remove_'.$field]) || !empty($_FILES[$field]['size'])) {
            checkInstanceImage(
                $certificateId,
                $infoCertificate[$field] ?? '',
                $field
            );
        }

        if (!empty($_FILES[$field]['size'])) {
            $newPicture = customcertificate_upload_image(
                $_FILES[$field],
                $certificateId,
                $field
            );

            if (!empty($newPicture)) {
                Database::update(
                    $table,
                    [$field => $newPicture],
                    ['id = ?' => $certificateId]
                );
                $checkLogo[$field] = true;
            }
        }
    }

    if (1 === (int) ($formValues['use_default'] ?? 0)) {
        $infoCertificateDefault = CustomCertificatePlugin::getInfoCertificateDefault($accessUrlId);
        if (!empty($infoCertificateDefault)) {
            foreach ($fieldList as $field) {
                if (!empty($infoCertificateDefault[$field]) && !$checkLogo[$field]) {
                    Database::update(
                        $table,
                        [$field => $infoCertificateDefault[$field]],
                        ['id = ?' => $certificateId]
                    );
                }
            }
        }
    }

    Display::addFlash(Display::return_message(get_lang('Saved')));

    Security::clear_token();
    header('Location: '.api_get_self().$urlParams);
    exit;
}

$token = Security::get_token();

$htmlHeadXtra[] = api_get_js_simple(
    api_get_path(WEB_PLUGIN_PATH).'CustomCertificate/resources/js/certificate.js'
);
$htmlHeadXtra[] = api_get_css_asset('cropper/dist/cropper.min.css');
$htmlHeadXtra[] = api_get_asset('cropper/dist/cropper.min.js');
$htmlHeadXtra[] = api_get_css(
    api_get_path(WEB_PLUGIN_PATH).'CustomCertificate/resources/css/form.css'
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
                var ajax_path = plugin_path + "CustomCertificate/src/customcertificate.ajax.php?a=delete_certificate";
                $.ajax({
                    data: {courseId: courseId, sessionId: sessionId, accessUrlId: accessUrlId, sec_token: "'.$token.'"},
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

// Build form
$form = new FormValidator(
    'formEdit',
    'post',
    api_get_self().$urlParams,
    null,
    ['class' => 'form-vertical']
);


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
    Display::getMdiIcon(ObjectIcon::CERTIFICATE, 'ch-tool-icon text-primary', null, ICON_SIZE_SMALL, get_lang('Certificate')).
    '<span>'.get_lang('Certificate').'</span>',
    'print_certificate.php'.$urlParams,
    [
        'class' => 'inline-flex items-center gap-2 rounded-lg border border-gray-25 bg-white px-3 py-2 text-body-2 font-semibold text-primary shadow-sm hover:bg-support-2',
    ]
);

if (!empty($courseId) && !$useDefault) {
    $actionsLeft .= Display::url(
        Display::getMdiIcon(ActionIcon::DELETE, 'ch-tool-icon text-danger', null, ICON_SIZE_SMALL, $plugin->get_lang('DeleteCertificate')).
        '<span>'.$plugin->get_lang('DeleteCertificate').'</span>',
        'delete_certificate.php'.$urlParams,
        [
            'id' => 'delete_certificate',
            'class' => 'inline-flex items-center gap-2 rounded-lg border border-gray-25 bg-white px-3 py-2 text-body-2 font-semibold text-danger shadow-sm hover:bg-support-2',
        ]
    );
}

echo '<div class="ch customcertificate-page space-y-6">';
echo '<div class="rounded-lg border border-gray-25 bg-white p-4 shadow-sm">';
echo '<div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">';
echo '<div class="flex items-center gap-3">';
echo Display::getMdiIcon(ObjectIcon::CERTIFICATE, 'ch-tool-icon-gradient', null, ICON_SIZE_MEDIUM, get_lang('Certificate'));
echo '<div>';
echo '<h2 class="m-0 text-xl font-semibold text-gray-90">'.$nameTools.'</h2>';
echo '<p class="m-0 mt-1 text-body-2 text-gray-50">'.$plugin->get_lang('CertificateSetting').'</p>';
echo '</div>';
echo '</div>';
echo '<div class="flex flex-wrap items-center gap-2">'.$actionsLeft.'</div>';
echo '</div>';
echo '</div>';

if ($useDefault && $courseId > 0) {
    echo '<div class="rounded-lg border border-info bg-support-1 p-4 text-body-2 text-gray-90">';
    echo Display::return_message($plugin->get_lang('InfoFromDefaultCertificate'), 'info', false);
    echo '</div>';
}

// Student and course section
$form->addHtml(customcertificate_open_section($plugin->get_lang('StudentCourseInfo'), 'mdi-account-school-outline'));
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
];
$form->addHtmlEditor(
    'content_course',
    '',
    false,
    true,
    $editorConfig,
    []
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
$strInfo .= '((course_code))<br />';
$strInfo .= '((course_title))<br />';
$strInfo .= '((gradebook_grade))<br />';
$strInfo .= '((external_style))<br />';
$strInfo .= '((start_date))<br />';
$strInfo .= '((end_date))<br />';
$strInfo .= '((date_expediction))';

$createCertificate = $plugin->get_lang('CreateCertificateWithTags');
$form->addElement(
    'html',
    Display::return_message($createCertificate.': <br />'.$strInfo, 'normal', false)
);
$form->addHtml('</div>');
$form->addHtml(customcertificate_close_section());
$form->addHtml('<div class="clearfix"></div>');

// Contents section
$form->addHtml(customcertificate_open_section($plugin->get_lang('Contents'), 'mdi-format-list-bulleted'));
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
    $plugin->get_lang('ContentsCourseDescription'),
    0,
    ['id' => 'contents_type_0', 'onclick' => 'javascript: contentsTypeSwitchRadioButton();']
);

$group[] = $element;

$element = &$form->createElement(
    'radio',
    'contents_type',
    '',
    $plugin->get_lang('ContentsIndexLearnpath'),
    1,
    ['id' => 'contents_type_1', 'onclick' => 'javascript: contentsTypeSwitchRadioButton();']
);
$group[] = $element;

$element = &$form->createElement(
    'radio',
    'contents_type',
    '',
    $plugin->get_lang('ContentsHide'),
    3,
    ['id' => 'contents_type_3', 'onclick' => 'javascript: contentsTypeSwitchRadioButton();']
);
$group[] = $element;

$element = &$form->createElement(
    'radio',
    'contents_type',
    '',
    $plugin->get_lang('ContentsCustom'),
    2,
    ['id' => 'contents_type_2', 'onclick' => 'javascript: contentsTypeSwitchRadioButton();']
);
$group[] = $element;

$form->addGroup(
    $group,
    'contents_type',
    $plugin->get_lang('ContentsToShow'),
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
    'id' => 'contents',
    $extra,
];
$form->addHtmlEditor(
    'contents',
    $plugin->get_lang('Contents'),
    false,
    true,
    $editorConfig,
    []
);
$form->addHtml('</div>');

// Dates section
$form->addHtml(customcertificate_open_section($plugin->get_lang('Dates'), 'mdi-calendar-range'));

$group = [];
$option1 = &$form->createElement(
    'radio',
    'date_change',
    '',
    $plugin->get_lang('UseDateSessionAccess'),
    0,
    ['id' => 'date_change_0', 'onclick' => 'javascript: dateCertificateSwitchRadioButton0();']
);
$group[] = $option1;

$option2 = &$form->createElement(
    'radio',
    'date_change',
    '',
    $plugin->get_lang('None'),
    2,
    ['id' => 'date_change_2', 'onclick' => 'javascript: dateCertificateSwitchRadioButton2();']
);
$group[] = $option2;

$option3 = &$form->createElement(
    'radio',
    'date_change',
    '',
    $plugin->get_lang('Custom'),
    1,
    ['id' => 'date_change_1', 'onclick' => 'javascript: dateCertificateSwitchRadioButton1();']
);
$group[] = $option3;

$form->addGroup(
    $group,
    'date_change',
    $plugin->get_lang('CourseDeliveryDates'),
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
                value="'.(('1' == $infoCertificate['date_change'])
                        ? date("d/m/Y", strtotime($infoCertificate['date_start']))
                        : '').'"
                '.(('' == $infoCertificate['date_change']) ? 'disabled' : '').'
            >
            <span style="margin: 0 10px; font-style: italic;">'.get_lang('Until').'</span>
            <input
                size="20"
                class="form-control-cert text-center datepicker"
                name="date_end"
                id="date_end"
                type="text"
                value="'.(('1' == $infoCertificate['date_change'])
                        ? date("d/m/Y", strtotime($infoCertificate['date_end']))
                        : '').'"
                '.(("0" == $infoCertificate['date_change']) ? 'disabled' : '').'
            >
        </div>
        </div>
    </div>');

$form->addText(
    'place',
    $plugin->get_lang('ExpectionPlace'),
    false,
    ['id' => 'place', 'cols-size' => [2, 5, 5], 'autofocus']
);

$group = [];
$option = &$form->createElement(
    'radio',
    'type_date_expediction',
    '',
    $plugin->get_lang('UseDateEndAccessSession'),
    0,
    [
        'id' => 'type_date_expediction_0',
        'onclick' => 'javascript: dateCertificateSwitchRadioButton0();',
        ((0 == $sessionId) ? 'disabled' : ''),
    ]
);
$group[] = $option;

$option = &$form->createElement(
    'radio',
    'type_date_expediction',
    '',
    $plugin->get_lang('UseDateDownloadCertificate'),
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
    $plugin->get_lang('UseDateGenerationCertificate'),
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
    $plugin->get_lang('None'),
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
    $plugin->get_lang('UseCustomDate'),
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
    $plugin->get_lang('DateExpediction'),
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
                '.(('2' != $infoCertificate['type_date_expediction']) ? 'disabled' : '').'
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
                '.(('2' != $infoCertificate['type_date_expediction']) ? 'disabled' : '').'
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
                '.(('2' != $infoCertificate['type_date_expediction']) ? 'disabled' : '').'
            >
        </div>
        </div>
    </div>'
);
$form->addHtml(customcertificate_close_section());

// Signature section
$form->addHtml(customcertificate_open_section($plugin->get_lang('LogosSeal'), 'mdi-image-multiple-outline'));
// Logo 1
$form->addHtml('<div class="col-sm-6">');
$form->addFile(
    'logo_left',
    $plugin->get_lang('LogoLeft'),
    [
        'id' => 'logo_left',
        'class' => 'picture-form',
        'crop_image' => true,
        'crop_scalable' => 'true',
    ]
);
$form->addProgress();
if (!empty($infoCertificate['logo_left'])) {
    $form->addElement('checkbox', 'remove_logo_left', null, get_lang('Remove picture'));
    $form->addElement(
        'html',
        '<label class="col-sm-2">&nbsp;</label>
        <img src="'.CustomCertificatePlugin::getCertificateImageUrl($infoCertificate['logo_left']).'" width="100"  />
        <br><br>'
    );
}
$allowedPictureTypes = api_get_supported_image_extensions(false);
$form->addRule(
    'logo_left',
    get_lang('Only PNG, JPG or GIF images allowed').' ('.implode(', ', $allowedPictureTypes).')',
    'filetype',
    $allowedPictureTypes
);
$form->addHtml('</div>');
// Logo 2
$form->addHtml('<div class="col-sm-6">');
$form->addFile(
    'logo_center',
    $plugin->get_lang('LogoCenter'),
    [
        'id' => 'logo_center',
        'class' => 'picture-form',
        'crop_image' => true,
        'crop_scalable' => 'true',
    ]
);
$form->addProgress();
if (!empty($infoCertificate['logo_center'])) {
    $form->addElement('checkbox', 'remove_logo_center', null, get_lang('Remove picture'));
    $form->addElement(
        'html',
        '<label class="col-sm-2">&nbsp;</label>
        <img src="'.CustomCertificatePlugin::getCertificateImageUrl($infoCertificate['logo_center']).'" width="100"  />
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
    $plugin->get_lang('LogoRight'),
    [
        'id' => 'logo_right',
        'class' => 'picture-form',
        'crop_image' => true,
        'crop_scalable' => 'true',
    ]
);
$form->addProgress();
if (!empty($infoCertificate['logo_right'])) {
    $form->addElement('checkbox', 'remove_logo_right', null, get_lang('Remove picture'));
    $form->addElement(
        'html',
        '<label class="col-sm-2">&nbsp;</label>
        <img src="'.CustomCertificatePlugin::getCertificateImageUrl($infoCertificate['logo_right']).'" width="100"  />
        <br><br>'
    );
}
$tblProperty = api_get_supported_image_extensions(false);
$form->addRule(
    'logo_right',
    get_lang('Only PNG, JPG or GIF images allowed').' ('.implode(', ', $allowedPictureTypes).')',
    'filetype',
    $allowedPictureTypes
);
$form->addHtml('</div>');
$form->addHtml('<div class="col-sm-6">');
$form->addFile(
    'seal',
    $plugin->get_lang('Seal'),
    [
        'id' => 'seal',
        'class' => 'picture-form',
        'crop_image' => true,
        'crop_scalable' => 'true',
    ]
);
$form->addProgress();
if (!empty($infoCertificate['seal'])) {
    $form->addElement('checkbox', 'remove_seal', null, get_lang('Remove picture'));
    $form->addElement(
        'html',
        '<label class="col-sm-2">&nbsp;</label>
        <img src="'.CustomCertificatePlugin::getCertificateImageUrl($infoCertificate['seal']).'" width="100"  />
        <br><br>'
    );
}
$allowedPictureTypes = api_get_supported_image_extensions(false);
$form->addRule(
    'seal',
    get_lang('Only PNG, JPG or GIF images allowed').' ('.implode(', ', $allowedPictureTypes).')',
    'filetype',
    $allowedPictureTypes
);
$form->addHtml('</div><div class="clearfix"></div>');
$form->addHtml(customcertificate_close_section());
$form->addHtml(customcertificate_open_section($plugin->get_lang('Signatures'), 'mdi-draw-pen'));
// signature 1
$form->addHtml('<div class="col-sm-6">');
$form->addText(
    'signature_text1',
    $plugin->get_lang('SignatureText1'),
    false,
    ['cols-size' => [2, 10, 0], 'autofocus']
);
$form->addFile(
    'signature1',
    $plugin->get_lang('Signature1'),
    [
        'id' => 'signature1',
        'class' => 'picture-form',
        'crop_image' => true,
        'crop_scalable' => 'true',
    ]
);
$form->addProgress();
if (!empty($infoCertificate['signature1'])) {
    $form->addElement('checkbox', 'remove_signature1', null, get_lang('Remove picture'));
    $form->addElement(
        'html',
        '<label class="col-sm-2">&nbsp;</label>
        <img src="'.CustomCertificatePlugin::getCertificateImageUrl($infoCertificate['signature1']).'" width="100"  />
        <br><br>'
    );
}
$allowedPictureTypes = api_get_supported_image_extensions(false);
$form->addRule(
    'signature1',
    get_lang('Only PNG, JPG or GIF images allowed').' ('.implode(', ', $allowedPictureTypes).')',
    'filetype',
    $allowedPictureTypes
);
$form->addHtml('</div>');
// signature 2
$form->addHtml('<div class="col-sm-6">');
$form->addText(
    'signature_text2',
    $plugin->get_lang('SignatureText2'),
    false,
    ['cols-size' => [2, 10, 0], 'autofocus']
);
$form->addFile(
    'signature2',
    $plugin->get_lang('Signature2'),
    [
        'id' => 'signature2',
        'class' => 'picture-form',
        'crop_image' => true,
        'crop_scalable' => 'true',
    ]
);
$form->addProgress();
if (!empty($infoCertificate['signature2'])) {
    $form->addElement('checkbox', 'remove_signature2', null, get_lang('Remove picture'));
    $form->addElement(
        'html',
        '<label class="col-sm-2">&nbsp;</label>
        <img src="'.CustomCertificatePlugin::getCertificateImageUrl($infoCertificate['signature2']).'" width="100"  />
        <br><br>'
    );
}
$allowedPictureTypes = api_get_supported_image_extensions(false);
$form->addRule(
    'signature2',
    get_lang('Only PNG, JPG or GIF images allowed').' ('.implode(', ', $allowedPictureTypes).')',
    'filetype',
    $allowedPictureTypes
);
$form->addHtml('</div><div class="clearfix"></div>');
// signature 3
$form->addHtml('<div class="col-sm-6">');
$form->addText(
    'signature_text3',
    $plugin->get_lang('SignatureText3'),
    false,
    ['cols-size' => [2, 10, 0], 'autofocus']
);
$form->addFile(
    'signature3',
    $plugin->get_lang('Signature3'),
    [
        'id' => 'signature3',
        'class' => 'picture-form',
        'crop_image' => true,
        'crop_scalable' => 'true',
    ]
);
$form->addProgress();
if (!empty($infoCertificate['signature3'])) {
    $form->addElement('checkbox', 'remove_signature3', null, get_lang('Remove picture'));
    $form->addElement(
        'html',
        '<label class="col-sm-2">&nbsp;</label>
        <img src="'.CustomCertificatePlugin::getCertificateImageUrl($infoCertificate['signature3']).'" width="100" />
        <br><br>'
    );
}
$allowedPictureTypes = api_get_supported_image_extensions(false);
$form->addRule(
    'signature3',
    get_lang('Only PNG, JPG or GIF images allowed').' ('.implode(', ', $allowedPictureTypes).')',
    'filetype',
    $allowedPictureTypes
);
$form->addHtml('</div>');
// signature 4
$form->addHtml('<div class="col-sm-6">');
$form->addText(
    'signature_text4',
    $plugin->get_lang('SignatureText4'),
    false,
    ['cols-size' => [2, 10, 0], 'autofocus']
);
$form->addFile(
    'signature4',
    $plugin->get_lang('Signature4'),
    [
        'id' => 'signature4',
        'class' => 'picture-form',
        'crop_image' => true,
        'crop_scalable' => 'true',
    ]
);
$form->addProgress();
if (!empty($infoCertificate['signature4'])) {
    $form->addElement('checkbox', 'remove_signature4', null, get_lang('Remove picture'));
    $form->addElement(
        'html',
        '<label class="col-sm-2">&nbsp;</label>
        <img src="'.CustomCertificatePlugin::getCertificateImageUrl($infoCertificate['signature4']).'" width="100"  />
        <br><br>'
    );
}
$allowedPictureTypes = api_get_supported_image_extensions(false);
$form->addRule(
    'signature4',
    get_lang('Only PNG, JPG or GIF images allowed').' ('.implode(', ', $allowedPictureTypes).')',
    'filetype',
    $allowedPictureTypes
);
$form->addHtml('</div><div class="clearfix"></div>');
$form->addHtml(customcertificate_close_section().'<br>');
$form->addHtml('<div class="col-sm-6">');
$form->addHtml(customcertificate_open_section($plugin->get_lang('Background'), 'mdi-image-outline'));
// background
$form->addFile(
    'background',
    $plugin->get_lang('Background'),
    [
        'id' => 'background',
        'class' => 'picture-form',
        'crop_image' => true,
        'crop_ratio' => '297 / 210',
    ]
);
$form->addProgress();
if (!empty($infoCertificate['background'])) {
    $form->addElement('checkbox', 'remove_background', null, get_lang('Remove picture'));
    $form->addElement(
        'html',
        '<label class="col-sm-2">&nbsp;</label>
        <img src="'.CustomCertificatePlugin::getCertificateImageUrl($infoCertificate['background']).'" width="100"  />
        <br><br>'
    );
}
$allowedPictureTypes = api_get_supported_image_extensions(false);
$form->addRule(
    'background',
    get_lang('Only PNG, JPG or GIF images allowed').' ('.implode(', ', $allowedPictureTypes).')',
    'filetype',
    $allowedPictureTypes
);
$form->addHtml(customcertificate_close_section());
$form->addHtml('</div>');
$form->addHtml('<div class="col-sm-6">');
$form->addHtml(customcertificate_open_section($plugin->get_lang('OtherOptions'), 'mdi-tune'));
$marginOptions = [];
$i = 0;
while ($i < 298) {
    $marginOptions[$i] = $i.' mm';
    $i++;
}
$form->addSelect(
    'margin_left',
    $plugin->get_lang('MarginLeft'),
    $marginOptions,
    ['cols-size' => [4, 8, 0]]
);
$form->addSelect(
    'margin_right',
    $plugin->get_lang('MarginRight'),
    $marginOptions,
    ['cols-size' => [4, 8, 0]]
);
$form->addHtml(customcertificate_close_section());
$form->addHtml('</div>');
$form->addHtml('<div class="clearfix"></div>');

$form->addButton(
    'submit',
    $plugin->get_lang('SaveCertificate'),
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
echo '<div class="rounded-lg border border-gray-25 bg-white p-4 shadow-sm">';
echo '<div id="doc_form" class="w-full">';
echo $form->returnForm();
echo '</div>';
echo '</div>';
echo '</div>';
Display::display_footer();



/**
 * Open a styled section for the legacy form.
 */
function customcertificate_open_section(string $title, string $icon): string
{
    return '<section class="mb-6 overflow-hidden rounded-lg border border-gray-25 bg-white shadow-sm">'.
        '<div class="flex items-center gap-2 border-b border-gray-20 bg-support-2 px-4 py-3">'.
        '<span class="mdi '.$icon.' ch-tool-icon text-primary" aria-hidden="true"></span>'.
        '<h3 class="m-0 text-body-2 font-semibold text-gray-90">'.htmlspecialchars($title, ENT_QUOTES).'</h3>'.
        '</div>'.
        '<div class="p-4">';
}

/**
 * Close a styled section for the legacy form.
 */
function customcertificate_close_section(): string
{
    return '</div></section>';
}

/**
 * Uploads a certificate image and returns the relative path stored in DB.
 *
 * @param array  $file
 * @param int    $certificateId
 * @param string $field
 *
 * @return string|null
 */

function customcertificate_normalize_datetime(string $value): string
{
    $value = trim($value);

    if ('' === $value) {
        return '1970-01-01 00:00:00';
    }

    $timestamp = strtotime(str_replace('/', '-', $value));

    if (false === $timestamp) {
        return '1970-01-01 00:00:00';
    }

    return date('Y-m-d H:i:s', $timestamp);
}

function customcertificate_upload_image(array $file, int $certificateId, string $field): ?string
{
    return CustomCertificatePlugin::storeUploadedCertificateImage($file, $certificateId, $field);
}

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
    if (1 == Database::num_rows($res)) {
        CustomCertificatePlugin::deleteUploadedCertificateFile($imagePath);
    }

    $sql = "UPDATE $table SET $field = '' WHERE id = $certificateId";
    Database::query($sql);
}
