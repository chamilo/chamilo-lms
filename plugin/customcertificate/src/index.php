<?php
/* For licensing terms, see /license.txt */

if (intval($_GET['default']) == 1) {
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
if (intval($_GET['default']) == 1) {
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
    $enableCourse = api_get_course_setting('customcertificate_course_enable', $courseCode) == 1 ? true : false;
    $useDefault = api_get_course_setting('use_certificate_default', $courseCode) == 1 ? true : false;
    $defaultCertificate = 0;
    $urlParams = '?'.api_get_cidreq();
}

if ($enable) {
    if (!$enableCourse && !$useDefault) {
        api_not_allowed(true, $plugin->get_lang('ToolDisabledCourse'));
    }

    if ($enableCourse && $useDefault) {
        api_not_allowed(true, $plugin->get_lang('ToolUseDefaultSettingCourse'));
    }

    if (api_is_platform_admin() || api_is_teacher()) {
        $table = Database::get_main_table(CustomCertificatePlugin::TABLE_CUSTOMCERTIFICATE);
        $htmlHeadXtra[] = api_get_js_simple(
            api_get_path(WEB_PLUGIN_PATH).'customcertificate/resources/js/certificate.js'
        );
        $htmlHeadXtra[] = api_get_css_asset('cropper/dist/cropper.min.css');
        $htmlHeadXtra[] = api_get_asset('cropper/dist/cropper.min.js');
        $htmlHeadXtra[] = api_get_css(
            api_get_path(WEB_PLUGIN_PATH).'customcertificate/resources/css/form.css'
        );

        // Get info certificate
        $infoCertificate = Database::select(
            '*',
            $table,
            ['where' => ['access_url_id = ? AND c_id = ? AND session_id = ?' => [$accessUrlId, $courseId, $sessionId]]],
            'first'
        );
        if (!is_array($infoCertificate)) {
            $infoCertificate = [];
        }

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
                    'contents_type' => intval($formValues['contents_type']),
                    'contents' => $contents,
                    'date_change' => intval($formValues['date_change']),
                    'date_start' => date("Y-m-d", strtotime($date_start)),
                    'date_end' => date("Y-m-d", strtotime($date_end)),
                    'place' => $formValues['place'],
                    'type_date_expediction' => intval($formValues['type_date_expediction']),
                    'day' => $formValues['day'],
                    'month' => $formValues['month'],
                    'year' => $formValues['year'],
                    'signature_text1' => $formValues['signature_text1'],
                    'signature_text2' => $formValues['signature_text2'],
                    'signature_text3' => $formValues['signature_text3'],
                    'signature_text4' => $formValues['signature_text4'],
                    'margin_left' => intval($formValues['margin_left']),
                    'margin_right' => intval($formValues['margin_right']),
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
                    Database::insert($table, $params);
                    $certificateId = Database::insert_id();
                }

                // Image manager
                $base = api_get_path(SYS_UPLOAD_PATH);
                $pathCertificates = $base.'certificates/'.$certificateId.'/';

                if (!empty($formValues['remove_logo_left']) || $_FILES['logo_left']['size']) {
                    @unlink($pathCertificates.$infoCertificate['logo_left']);
                    $sql = "UPDATE $table SET logo_left = '' WHERE id = $certificateId";
                    $rs = Database::query($sql);
                }

                if (!empty($formValues['remove_logo_center']) || $_FILES['logo_center']['size']) {
                    @unlink($pathCertificates.$infoCertificate['logo_center']);
                    $sql = "UPDATE $table SET logo_center = '' WHERE id = $certificateId";
                    $rs = Database::query($sql);
                }

                if (!empty($formValues['remove_logo_right']) || $_FILES['logo_right']['size']) {
                    @unlink($pathCertificates.$infoCertificate['logo_right']);
                    $sql = "UPDATE $table SET logo_right = '' WHERE id = $certificateId";
                    $rs = Database::query($sql);
                }

                if (!empty($formValues['remove_seal']) || $_FILES['seal']['size']) {
                    @unlink($pathCertificates.$infoCertificate['seal']);
                    $sql = "UPDATE $table SET seal = '' WHERE id = $certificateId";
                    $rs = Database::query($sql);
                }

                if (!empty($formValues['remove_signature1']) || $_FILES['signature1']['size']) {
                    @unlink($pathCertificates.$infoCertificate['signature1']);
                    $sql = "UPDATE $table SET signature1 = '' WHERE id = $certificateId";
                    $rs = Database::query($sql);
                }

                if (!empty($formValues['remove_signature2']) || $_FILES['signature2']['size']) {
                    @unlink($pathCertificates.$infoCertificate['signature2']);
                    $sql = "UPDATE $table SET signature2 = '' WHERE id = $certificateId";
                    $rs = Database::query($sql);
                }

                if (!empty($formValues['remove_signature3']) || $_FILES['signature3']['size']) {
                    @unlink($pathCertificates.$infoCertificate['signature3']);
                    $sql = "UPDATE $table SET signature3 = '' WHERE id = $certificateId";
                    $rs = Database::query($sql);
                }

                if (!empty($formValues['remove_signature4']) || $_FILES['signature4']['size']) {
                    @unlink($pathCertificates.$infoCertificate['signature4']);
                    $sql = "UPDATE $table SET signature4 = '' WHERE id = $certificateId";
                    $rs = Database::query($sql);
                }

                if (!empty($formValues['remove_background']) || $_FILES['background']['size']) {
                    @unlink($pathCertificates.$infoCertificate['background']);
                    $sql = "UPDATE $table SET background = '' WHERE id = $certificateId";
                    $rs = Database::query($sql);
                }

                $logoLeft = false;
                $logoCenter = false;
                $logoRight = false;
                $seal = false;
                $signature1 = false;
                $signature2 = false;
                $signature3 = false;
                $signature4 = false;
                $background = false;

                if ($_FILES['logo_left']['size']) {
                    $newPicture = uploadImageCertificate(
                        $certificateId,
                        $_FILES['logo_left']['name'],
                        $_FILES['logo_left']['tmp_name'],
                        $formValues['logo_left_crop_result']
                    );
                    if ($newPicture) {
                        $sql = "UPDATE $table SET logo_left = '".$newPicture."' WHERE id = $certificateId";
                        Database::query($sql);
                        $logoLeft = true;
                    }
                }

                if ($_FILES['logo_center']['size']) {
                    $newPicture = uploadImageCertificate(
                        $certificateId,
                        $_FILES['logo_center']['name'],
                        $_FILES['logo_center']['tmp_name'],
                        $formValues['logo_center_crop_result']
                    );
                    if ($newPicture) {
                        $sql = "UPDATE $table SET logo_center = '".$newPicture."' WHERE id = $certificateId";
                        Database::query($sql);
                        $logoCenter = true;
                    }
                }

                if ($_FILES['logo_right']['size']) {
                    $newPicture = uploadImageCertificate(
                        $certificateId,
                        $_FILES['logo_right']['name'],
                        $_FILES['logo_right']['tmp_name'],
                        $formValues['logo_right_crop_result']
                    );
                    if ($newPicture) {
                        $sql = "UPDATE $table SET logo_right = '".$newPicture."' WHERE id = $certificateId";
                        Database::query($sql);
                        $logoRight = true;
                    }
                }

                if ($_FILES['seal']['size']) {
                    $newPicture = uploadImageCertificate(
                        $certificateId,
                        $_FILES['seal']['name'],
                        $_FILES['seal']['tmp_name'],
                        $formValues['seal_crop_result']
                    );
                    if ($newPicture) {
                        $sql = "UPDATE $table SET seal = '".$newPicture."' WHERE id = $certificateId";
                        Database::query($sql);
                        $seal = true;
                    }
                }

                if ($_FILES['signature1']['size']) {
                    $newPicture = uploadImageCertificate(
                        $certificateId,
                        $_FILES['signature1']['name'],
                        $_FILES['signature1']['tmp_name'],
                        $formValues['signature1_crop_result']
                    );
                    if ($newPicture) {
                        $sql = "UPDATE $table SET signature1 = '".$newPicture."' WHERE id = $certificateId";
                        Database::query($sql);
                        $signature1 = true;
                    }
                }

                if ($_FILES['signature2']['size']) {
                    $newPicture = uploadImageCertificate(
                        $certificateId,
                        $_FILES['signature2']['name'],
                        $_FILES['signature2']['tmp_name'],
                        $formValues['signature2_crop_result']
                    );
                    if ($newPicture) {
                        $sql = "UPDATE $table SET signature2 = '".$newPicture."' WHERE id = $certificateId";
                        Database::query($sql);
                        $signature2 = true;
                    }
                }

                if ($_FILES['signature3']['size']) {
                    $newPicture = uploadImageCertificate(
                        $certificateId,
                        $_FILES['signature3']['name'],
                        $_FILES['signature3']['tmp_name'],
                        $formValues['signature3_crop_result']
                    );
                    if ($newPicture) {
                        $sql = "UPDATE $table SET signature3 = '".$newPicture."' WHERE id = $certificateId";
                        Database::query($sql);
                        $signature3 = true;
                    }
                }

                if ($_FILES['signature4']['size']) {
                    $newPicture = uploadImageCertificate(
                        $certificateId,
                        $_FILES['signature4']['name'],
                        $_FILES['signature4']['tmp_name'],
                        $formValues['signature4_crop_result']
                    );
                    if ($newPicture) {
                        $sql = "UPDATE $table SET signature4 = '".$newPicture."' WHERE id = $certificateId";
                        Database::query($sql);
                        $signature4 = true;
                    }
                }

                if ($_FILES['background']['size']) {
                    $newPicture = uploadImageCertificate(
                        $certificateId,
                        $_FILES['background']['name'],
                        $_FILES['background']['tmp_name'],
                        $formValues['background_crop_result']
                    );
                    if ($newPicture) {
                        $sql = "UPDATE $table SET background = '".$newPicture."' WHERE id = $certificateId";
                        Database::query($sql);
                        $background = true;
                    }
                }

                // Certificate Default
                if (intval($formValues['use_default'] == 1)) {
                    $base = api_get_path(SYS_UPLOAD_PATH);

                    $infoCertificateDefault = Database::select(
                        '*',
                        $table,
                        ['where' => ['certificate_default = ? ' => 1]],
                        'first'
                    );

                    if (!empty($infoCertificateDefault)) {
                        $pathCertificatesDefault = $base.'certificates/'.$infoCertificateDefault['id'].'/';

                        if (!file_exists($pathCertificates)) {
                            mkdir($pathCertificates, api_get_permissions_for_new_directories(), true);
                        }

                        if (!empty($infoCertificateDefault['logo_left']) && !$logoLeft) {
                            copy(
                                $pathCertificatesDefault.$infoCertificateDefault['logo_left'],
                                $pathCertificates.$infoCertificateDefault['logo_left']
                            );
                            $sql = "UPDATE $table
                                    SET logo_left = '".$infoCertificateDefault['logo_left']."'
                                    WHERE id = $certificateId";
                            Database::query($sql);
                        }

                        if (!empty($infoCertificateDefault['logo_center']) && !$logoCenter) {
                            copy(
                                $pathCertificatesDefault.$infoCertificateDefault['logo_center'],
                                $pathCertificates.$infoCertificateDefault['logo_center']
                            );
                            $sql = "UPDATE $table
                                    SET logo_center = '".$infoCertificateDefault['logo_center']."'
                                    WHERE id = $certificateId";
                            Database::query($sql);
                        }

                        if (!empty($infoCertificateDefault['logo_right']) && !$logoRight) {
                            copy(
                                $pathCertificatesDefault.$infoCertificateDefault['logo_right'],
                                $pathCertificates.$infoCertificateDefault['logo_right']
                            );
                            $sql = "UPDATE $table
                                    SET logo_right = '".$infoCertificateDefault['logo_right']."'
                                    WHERE id = $certificateId";
                            Database::query($sql);
                        }

                        if (!empty($infoCertificateDefault['seal']) && !$seal) {
                            copy(
                                $pathCertificatesDefault.$infoCertificateDefault['seal'],
                                $pathCertificates.$infoCertificateDefault['seal']
                            );
                            $sql = "UPDATE $table
                                    SET seal = '".$infoCertificateDefault['seal']."'
                                    WHERE id = $certificateId";
                            Database::query($sql);
                        }

                        if (!empty($infoCertificateDefault['signature1']) && !$signature1) {
                            copy(
                                $pathCertificatesDefault.$infoCertificateDefault['signature1'],
                                $pathCertificates.$infoCertificateDefault['signature1']
                            );
                            $sql = "UPDATE $table
                                    SET signature1 = '".$infoCertificateDefault['signature1']."'
                                    WHERE id = $certificateId";
                            Database::query($sql);
                        }

                        if (!empty($infoCertificateDefault['signature2']) && !$signature2) {
                            copy(
                                $pathCertificatesDefault.$infoCertificateDefault['signature2'],
                                $pathCertificates.$infoCertificateDefault['signature2']
                            );
                            $sql = "UPDATE $table
                                    SET signature2 = '".$infoCertificateDefault['signature2']."'
                                    WHERE id = $certificateId";
                            Database::query($sql);
                        }

                        if (!empty($infoCertificateDefault['signature3']) && !$signature3) {
                            copy(
                                $pathCertificatesDefault.$infoCertificateDefault['signature3'],
                                $pathCertificates.$infoCertificateDefault['signature3']
                            );
                            $sql = "UPDATE $table
                                    SET signature3 = '".$infoCertificateDefault['signature3']."'
                                    WHERE id = $certificateId";
                            Database::query($sql);
                        }

                        if (!empty($infoCertificateDefault['signature4']) && !$signature4) {
                            copy(
                                $pathCertificatesDefault.$infoCertificateDefault['signature4'],
                                $pathCertificates.$infoCertificateDefault['signature4']
                            );
                            $sql = "UPDATE $table
                                    SET signature4 = '".$infoCertificateDefault['signature4']."'
                                    WHERE id = $certificateId";
                            Database::query($sql);
                        }

                        if (!empty($infoCertificateDefault['background']) && !$background) {
                            copy(
                                $pathCertificatesDefault.$infoCertificateDefault['background'],
                                $pathCertificates.$infoCertificateDefault['background']
                            );
                            $sql = "UPDATE $table
                                    SET background = '".$infoCertificateDefault['background']."'
                                    WHERE id = $certificateId";
                            Database::query($sql);
                        }
                    }
                }

                Security::clear_token();
                header('Location: '.api_get_self().$urlParams);
                exit;
            }
        }
        if (empty($infoCertificate)) {
            $infoCertificate = Database::select(
                '*',
                $table,
                ['where' => ['access_url_id = ? AND certificate_default = ? ' => [$accessUrlId, 1]]],
                'first'
            );

            if (!is_array($infoCertificate)) {
                $infoCertificate = [];
            }
            if (!empty($infoCertificate)) {
                $useDefault = true;
            }
        }
        /*	Display user interface */
        // Display the header
        Display::display_header($nameTools);
        $actionsLeft .= Display::url(
            Display::return_icon('certificate.png', get_lang('Certificate'), '', ICON_SIZE_MEDIUM),
            'print_certificate.php'.$urlParams
        );
        echo Display::toolbarAction(
            'toolbar-document',
            [$actionsLeft]
        );

        if ($useDefault && $courseId > 0) {
            echo Display::return_message(get_lang('InfoFromDefaultCertificate'), 'info');
        }

        // Student and course section
        $form->addElement('html', '<fieldset><legend>'.strtoupper(get_lang('StudentCourseInfo')).'</legend>');
        $form->addElement('html', '<div class="col-sm-8">');
        $dir = '/';
        $courseInfo = api_get_course_info();
        $isAllowedToEdit = api_is_allowed_to_edit(null, true);
        $editorConfig = [
            'ToolbarSet' => ($isAllowedToEdit ? 'Documents' : 'DocumentsStudent'),
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
        $form->addElement('html', '</div>');
        $form->addElement('html', '<div class="col-sm-4">');

        $strInfo = '((user_lastname))<br />';
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

        $createCertificate = get_lang('CreateCertificateWithTags');
        $form->addElement(
            'html',
            Display::return_message($createCertificate.': <br /><br/>'.$strInfo, 'normal', false)
        );
        $form->addElement('html', '</div>');
        $form->addElement('html', '</fieldset>');
        $form->addElement('html', '<div class="clearfix"></div>');

        // Contents section
        $form->addElement('html', '<fieldset><legend>'.strtoupper(get_lang('Contents')).'</legend>');
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

        $form->addElement('html', '<div id="contents-section">');
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
        $form->addElement('html', '</div>');

        // Dates section
        $form->addElement('html', '<fieldset><legend>'.strtoupper(get_lang("Dates")).'</legend>');

        $group = [];
        $option1 = &$form->createElement(
            'radio',
            'date_change',
            '',
            get_lang('UseDateSessionAccess'),
            0,
            ['id' => 'contents_type_0', 'onclick' => 'javascript: dateCertificateSwitchRadioButton0();']
        );
        $group[] = $option1;

        $option2 = &$form->createElement(
            'radio',
            'date_change',
            '',
            get_lang('None'),
            2,
            ['id' => 'contents_type_2', 'onclick' => 'javascript: dateCertificateSwitchRadioButton2();']
        );
        $group[] = $option2;

        $option3 = &$form->createElement(
            'radio',
            'date_change',
            '',
            get_lang('Custom'),
            1,
            ['id' => 'contents_type_1', 'onclick' => 'javascript: dateCertificateSwitchRadioButton1();']
        );
        $group[] = $option3;

        $form->addGroup(
            $group,
            'date_change',
            get_lang('CourseDeliveryDates'),
            null,
            false
        );
        $form->addElement('html', '<div class="form-group" style="padding-top: 10px;">
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
                        value="'.(($infoCertificate['date_change'] == "1")
                                ? date("d/m/Y", strtotime($infoCertificate['date_start']))
                                : '').'"
                        '.(($infoCertificate['date_change'] == "0") ? 'disabled' : '').'
                    >
                    <span style="margin: 0 10px; font-style: italic;">'.get_lang('Until').'</span>
                    <input
                        size="20"
                        class="form-control-cert text-center datepicker"
                        name="date_end"
                        id="date_end"
                        type="text"
                        value="'.(($infoCertificate['date_change'] == "1")
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
        $option1 = &$form->createElement(
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
        $group[] = $option1;

        $option2 = &$form->createElement(
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
        $group[] = $option2;

        $option4 = &$form->createElement(
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
        $group[] = $option4;

        $option3 = &$form->createElement(
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
        $group[] = $option3;

        $form->addGroup(
            $group,
            'type_date_expediction',
            get_lang('DateExpediction'),
            null,
            false
        );

        $form->addElement('html', '<div class="form-group" style="padding-top: 10px;">
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
                        '.(($infoCertificate['type_date_expediction'] != "2") ? 'disabled' : '').'
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
                        '.(($infoCertificate['type_date_expediction'] != "2") ? 'disabled' : '').'
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
                        '.(($infoCertificate['type_date_expediction'] != "2") ? 'disabled' : '').'
                    >
                </div>
                </div>
            </div>');
        $form->addElement('html', '</fieldset>');

        // Signature section
        $base = api_get_path(WEB_UPLOAD_PATH);
        $path = $base.'certificates/'.$infoCertificate['id'].'/';

        $form->addElement('html', '<fieldset><legend>'.strtoupper(get_lang('LogosSeal')).'</legend>');
        // Logo 1
        $form->addElement('html', '<div class="col-sm-6">');
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
        $form->addElement('html', '</div>');
        // Logo 2
        $form->addElement('html', '<div class="col-sm-6">');
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
        $form->addElement('html', '</div><div class="clearfix"></div>');
        // Logo 3
        $form->addElement('html', '<div class="col-sm-6">');
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
        $form->addElement('html', '</div>');
        $form->addElement('html', '<div class="col-sm-6">');
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
        $form->addElement('html', '</div><div class="clearfix"></div>');
        $form->addElement('html', '</fieldset>');
        $form->addElement('html', '<fieldset><legend>'.strtoupper(get_lang('Signatures')).'</legend>');
        // signature 1
        $form->addElement('html', '<div class="col-sm-6">');
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
        $form->addElement('html', '</div>');
        // signature 2
        $form->addElement('html', '<div class="col-sm-6">');
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
        $form->addElement('html', '</div><div class="clearfix"></div>');
        // signature 3
        $form->addElement('html', '<div class="col-sm-6">');
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
        $form->addElement('html', '</div>');
        // signature 4
        $form->addElement('html', '<div class="col-sm-6">');
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
        $form->addElement('html', '</div><div class="clearfix"></div>');
        $form->addElement('html', '</fieldset><br>');
        $form->addElement('html', '<div class="col-sm-6">');
        $form->addElement('html', '<fieldset><legend>'.strtoupper(get_lang('BackgroundCertificate')).'</legend>');
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
        $form->addElement('html', '</fieldset>');
        $form->addElement('html', '</div>');
        $form->addElement('html', '<div class="col-sm-6">');
        $form->addElement('html', '<fieldset><legend>'.strtoupper(get_lang('OtherOptions')).'</legend>');
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
        $form->addElement('html', '</fieldset>');
        $form->addElement('html', '</div>');
        $form->addElement('html', '<div class="clearfix"></div>');

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
    } else {
        $session = api_get_session_entity(api_get_session_id());
        $courseInfo = api_get_course_info();
        $webCoursePath = api_get_path(WEB_COURSE_PATH);
        $url = $webCoursePath.$courseInfo['path'].'/index.php'.($session ? '?id_session='.$session->getId() : '');

        Display::addFlash(
            Display::return_message($plugin->get_lang('OnlyAdminPlatformOrTeacher'))
        );

        header('Location: '.$url);
        exit;
    }
} else {
    api_not_allowed(true, $plugin->get_lang('ToolDisabled'));
}

function uploadImageCertificate(
    $certId,
    $file = null,
    $sourceFile = null,
    $cropParameters = '',
    $default = false
) {
    if (empty($certId)) {
        return false;
    }

    $delete = empty($file);

    if (empty($sourceFile)) {
        $sourceFile = $file;
    }

    $base = api_get_path(SYS_UPLOAD_PATH);
    $path = $base.'certificates/'.$certId.'/';

    if ($default) {
        $path = $base.'certificates/default/';
    }

    // If this directory does not exist - we create it.
    if (!file_exists($path)) {
        mkdir($path, api_get_permissions_for_new_directories(), true);
    }

    // Exit if only deletion has been requested. Return an empty picture name.
    if ($delete) {
        return '';
    }

    $allowedTypes = api_get_supported_image_extensions();
    $file = str_replace('\\', '/', $file);
    $filename = (($pos = strrpos($file, '/')) !== false) ? substr($file, $pos + 1) : $file;
    $extension = strtolower(substr(strrchr($filename, '.'), 1));

    if (!in_array($extension, $allowedTypes)) {
        return false;
    }

    $filename = api_replace_dangerous_char($filename);
    $filename = uniqid('').'_'.$filename;
    $filename = $certId.'_'.$filename;

    //Crop the image to adjust 1:1 ratio
    $image = new Image($sourceFile);
    $image->crop($cropParameters);

    $origin = new Image($sourceFile); // This is the original picture.
    $origin->send_image($path.$filename);

    $result = $origin;

    return $result ? $filename : false;
}
