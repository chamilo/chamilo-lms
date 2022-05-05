<?php

/* For licensing terms, see /license.txt */

use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;

/**
 * Certificate Class
 * Generate certificates based in the gradebook tool.
 */
class Certificate extends Model
{
    public $table;
    public $columns = [
        'id',
        'cat_id',
        'score_certificate',
        'created_at',
        'path_certificate',
    ];
    /**
     * Certification data.
     */
    public $certificate_data = [];

    /**
     * Student's certification path.
     */
    public $certification_user_path = null;
    public $certification_web_user_path = null;
    public $html_file = null;
    public $qr_file = null;
    public $user_id;

    /** If true every time we enter to the certificate URL
     * we would generate a new certificate (good thing because we can edit the
     * certificate and all users will have the latest certificate bad because we.
     * load the certificate every time */
    public $force_certificate_generation = true;

    /**
     * Constructor.
     *
     * @param int  $certificate_id        ID of the certificate
     * @param int  $userId
     * @param bool $sendNotification      send message to student
     * @param bool $updateCertificateData
     *
     * If no ID given, take user_id and try to generate one
     */
    public function __construct(
        $certificate_id = 0,
        $userId = 0,
        $sendNotification = false,
        $updateCertificateData = true
    ) {
        $this->table = Database::get_main_table(TABLE_MAIN_GRADEBOOK_CERTIFICATE);
        $this->user_id = !empty($userId) ? $userId : api_get_user_id();

        if (!empty($certificate_id)) {
            $certificate = $this->get($certificate_id);
            if (!empty($certificate) && is_array($certificate)) {
                $this->certificate_data = $certificate;
                $this->user_id = $this->certificate_data['user_id'];
            }
        }

        if ($this->user_id) {
            // Need to be called before any operation
            $this->check_certificate_path();
            // To force certification generation
            if ($this->force_certificate_generation) {
                $this->generate([], $sendNotification);
            }
            if (isset($this->certificate_data) && $this->certificate_data) {
                if (empty($this->certificate_data['path_certificate'])) {
                    $this->generate([], $sendNotification);
                }
            }
        }

        // Setting the qr and html variables
        if (isset($certificate_id) &&
            !empty($this->certification_user_path) &&
            isset($this->certificate_data['path_certificate'])
        ) {
            $pathinfo = pathinfo($this->certificate_data['path_certificate']);
            $this->html_file = $this->certification_user_path.basename($this->certificate_data['path_certificate']);
            $this->qr_file = $this->certification_user_path.$pathinfo['filename'].'_qr.png';
        } else {
            $this->check_certificate_path();
            if (api_get_configuration_value('allow_general_certificate')) {
                // General certificate
                $name = md5($this->user_id).'.html';
                $my_path_certificate = $this->certification_user_path.$name;
                $path_certificate = '/'.$name;

                // Getting QR filename
                $file_info = pathinfo($path_certificate);
                $content = $this->generateCustomCertificate();

                $my_new_content_html = str_replace(
                    '((certificate_barcode))',
                    Display::img(
                        $this->certification_web_user_path.$file_info['filename'].'_qr.png',
                        'QR'
                    ),
                    $content
                );

                $my_new_content_html = mb_convert_encoding(
                    $my_new_content_html,
                    'UTF-8',
                    api_get_system_encoding()
                );

                $this->html_file = $my_path_certificate;
                $result = @file_put_contents($my_path_certificate, $my_new_content_html);

                if ($result) {
                    // Updating the path
                    self::updateUserCertificateInfo(
                        0,
                        $this->user_id,
                        $path_certificate,
                        $updateCertificateData
                    );
                    $this->certificate_data['path_certificate'] = $path_certificate;
                }
            }
        }
    }

    /**
     * Checks if the certificate user path directory is created.
     */
    public function check_certificate_path()
    {
        $this->certification_user_path = null;

        // Setting certification path
        $path_info = UserManager::getUserPathById($this->user_id, 'system');
        $web_path_info = UserManager::getUserPathById($this->user_id, 'web');

        if (!empty($path_info) && isset($path_info)) {
            $this->certification_user_path = $path_info.'certificate/';
            $this->certification_web_user_path = $web_path_info.'certificate/';
            $mode = api_get_permissions_for_new_directories();
            if (!is_dir($path_info)) {
                mkdir($path_info, $mode, true);
            }
            if (!is_dir($this->certification_user_path)) {
                mkdir($this->certification_user_path, $mode);
            }
        }
    }

    /**
     * Deletes the current certificate object. This is generally triggered by
     * the teacher from the gradebook tool to re-generate the certificate because
     * the original version wa flawed.
     *
     * @param bool $force_delete
     *
     * @return bool
     */
    public function delete($force_delete = false)
    {
        $delete_db = false;
        if (!empty($this->certificate_data)) {
            if (!is_null($this->html_file) || $this->html_file != '' || strlen($this->html_file)) {
                // Deleting HTML file
                if (is_file($this->html_file)) {
                    @unlink($this->html_file);
                    if (is_file($this->html_file) === false) {
                        $delete_db = true;
                    } else {
                        $delete_db = false;
                    }
                }
                // Deleting QR code PNG image file
                if (is_file($this->qr_file)) {
                    @unlink($this->qr_file);
                }
                if ($delete_db || $force_delete) {
                    return parent::delete($this->certificate_data['id']);
                }
            } else {
                return parent::delete($this->certificate_data['id']);
            }
        }

        return false;
    }

    /**
     *  Generates an HTML Certificate and fills the path_certificate field in the DB.
     *
     * @param array $params
     * @param bool  $sendNotification
     *
     * @return bool|int
     */
    public function generate($params = [], $sendNotification = false)
    {
        // The user directory should be set
        if (empty($this->certification_user_path) &&
            $this->force_certificate_generation === false
        ) {
            return false;
        }

        $params['hide_print_button'] = isset($params['hide_print_button']) ? true : false;
        $categoryId = 0;
        $my_category = [];
        if (isset($this->certificate_data) && isset($this->certificate_data['cat_id'])) {
            $categoryId = $this->certificate_data['cat_id'];
            $my_category = Category::load($categoryId);
        }

        if (isset($my_category[0]) && !empty($categoryId) &&
            $my_category[0]->is_certificate_available($this->user_id)
        ) {
            /** @var Category $category */
            $category = $my_category[0];

            $courseInfo = api_get_course_info($category->get_course_code());
            $courseId = $courseInfo['real_id'];
            $sessionId = $category->get_session_id();

            $skill = new Skill();
            $skill->addSkillToUser(
                $this->user_id,
                $category,
                $courseId,
                $sessionId
            );

            if (is_dir($this->certification_user_path)) {
                if (!empty($this->certificate_data)) {
                    $new_content_html = GradebookUtils::get_user_certificate_content(
                        $this->user_id,
                        $category->get_course_code(),
                        $category->get_session_id(),
                        false,
                        $params['hide_print_button']
                    );

                    if ($category->get_id() == $categoryId) {
                        $name = $this->certificate_data['path_certificate'];
                        $myPathCertificate = $this->certification_user_path.basename($name);

                        if (file_exists($myPathCertificate) &&
                            !empty($name) &&
                            !is_dir($myPathCertificate) &&
                            $this->force_certificate_generation == false
                        ) {
                            // Seems that the file was already generated
                            return true;
                        } else {
                            // Creating new name
                            $name = md5($this->user_id.$this->certificate_data['cat_id']).'.html';
                            $myPathCertificate = $this->certification_user_path.$name;
                            $path_certificate = '/'.$name;

                            // Getting QR filename
                            $file_info = pathinfo($path_certificate);
                            $qr_code_filename = $this->certification_user_path.$file_info['filename'].'_qr.png';

                            $newContent = str_replace(
                                '((certificate_barcode))',
                                Display::img(
                                    $this->certification_web_user_path.$file_info['filename'].'_qr.png',
                                    'QR'
                                ),
                                $new_content_html['content']
                            );

                            $newContent = api_convert_encoding(
                                $newContent,
                                'UTF-8',
                                api_get_system_encoding()
                            );

                            $result = @file_put_contents($myPathCertificate, $newContent);
                            if ($result) {
                                // Updating the path
                                $this->updateUserCertificateInfo(
                                    $this->certificate_data['cat_id'],
                                    $this->user_id,
                                    $path_certificate
                                );
                                $this->certificate_data['path_certificate'] = $path_certificate;

                                if ($this->isHtmlFileGenerated()) {
                                    if (!empty($file_info)) {
                                        $text = $this->parseCertificateVariables(
                                            $new_content_html['variables']
                                        );
                                        $this->generateQRImage(
                                            $text,
                                            $qr_code_filename
                                        );

                                        if ($sendNotification) {
                                            $subject = get_lang('NotificationCertificateSubject');
                                            $message = nl2br(get_lang('NotificationCertificateTemplate'));
                                            $score = $this->certificate_data['score_certificate'];
                                            self::sendNotification(
                                                $subject,
                                                $message,
                                                api_get_user_info($this->user_id),
                                                $courseInfo,
                                                [
                                                    'score_certificate' => $score,
                                                ]
                                            );
                                        }
                                    }
                                }
                            }

                            return $result;
                        }
                    }
                }
            }
        } else {
            $this->check_certificate_path();

            // General certificate
            $name = md5($this->user_id).'.html';
            $my_path_certificate = $this->certification_user_path.$name;
            $path_certificate = '/'.$name;

            // Getting QR filename
            $file_info = pathinfo($path_certificate);
            $content = $this->generateCustomCertificate();

            $my_new_content_html = str_replace(
                '((certificate_barcode))',
                Display::img(
                    $this->certification_web_user_path.$file_info['filename'].'_qr.png',
                    'QR'
                ),
                $content
            );

            $my_new_content_html = mb_convert_encoding(
                $my_new_content_html,
                'UTF-8',
                api_get_system_encoding()
            );

            $result = @file_put_contents($my_path_certificate, $my_new_content_html);

            if ($result) {
                // Updating the path
                self::updateUserCertificateInfo(
                    0,
                    $this->user_id,
                    $path_certificate
                );
                $this->certificate_data['path_certificate'] = $path_certificate;
            }

            return $result;
        }

        return false;
    }

    /**
     * @return array
     */
    public static function notificationTags()
    {
        $tags = [
            '((course_title))',
            '((user_first_name))',
            '((user_last_name))',
            '((author_first_name))',
            '((author_last_name))',
            '((score))',
            '((portal_name))',
            '((certificate_link))',
        ];

        return $tags;
    }

    /**
     * @param string $subject
     * @param string $message
     * @param array  $userInfo
     * @param array  $courseInfo
     * @param array  $certificateInfo
     *
     * @return bool
     */
    public static function sendNotification(
        $subject,
        $message,
        $userInfo,
        $courseInfo,
        $certificateInfo
    ) {
        if (empty($userInfo) || empty($courseInfo)) {
            return false;
        }

        $currentUserInfo = api_get_user_info();
        $url = api_get_path(WEB_PATH).
            'certificates/index.php?id='.$certificateInfo['id'].'&user_id='.$certificateInfo['user_id'];
        $link = Display::url($url, $url);

        $replace = [
            $courseInfo['title'],
            $userInfo['firstname'],
            $userInfo['lastname'],
            $currentUserInfo['firstname'],
            $currentUserInfo['lastname'],
            $certificateInfo['score_certificate'],
            api_get_setting('Institution'),
            $link,
        ];

        $message = str_replace(self::notificationTags(), $replace, $message);
        MessageManager::send_message(
            $userInfo['id'],
            $subject,
            $message,
            [],
            [],
            0,
            0,
            0,
            0,
            $currentUserInfo['id']
        );

        $plugin = new AppPlugin();
        $smsPlugin = $plugin->getSMSPluginLibrary();
        if ($smsPlugin) {
            $additionalParameters = [
                'smsType' => SmsPlugin::CERTIFICATE_NOTIFICATION,
                'userId' => $userInfo['id'],
                'direct_message' => $message,
            ];
            $smsPlugin->send($additionalParameters);
        }
    }

    /**
     * Update user info about certificate.
     *
     * @param int    $categoryId            category id
     * @param int    $user_id               user id
     * @param string $path_certificate      the path name of the certificate
     * @param bool   $updateCertificateData
     */
    public function updateUserCertificateInfo(
        $categoryId,
        $user_id,
        $path_certificate,
        $updateCertificateData = true
    ) {
        $categoryId = (int) $categoryId;
        $user_id = (int) $user_id;

        if ($updateCertificateData &&
            !UserManager::is_user_certified($categoryId, $user_id)
        ) {
            $table = Database::get_main_table(TABLE_MAIN_GRADEBOOK_CERTIFICATE);
            $now = api_get_utc_datetime();
            $sql = 'UPDATE '.$table.' SET
                        path_certificate="'.Database::escape_string($path_certificate).'",
                        created_at = "'.$now.'"
                    WHERE cat_id = "'.$categoryId.'" AND user_id="'.$user_id.'" ';
            Database::query($sql);
        }
    }

    /**
     * Check if the file was generated.
     *
     * @return bool
     */
    public function isHtmlFileGenerated()
    {
        if (empty($this->certification_user_path)) {
            return false;
        }
        if (!empty($this->certificate_data) &&
            isset($this->certificate_data['path_certificate']) &&
            !empty($this->certificate_data['path_certificate'])
        ) {
            return true;
        }

        return false;
    }

    /**
     * Generates a QR code for the certificate. The QR code embeds the text given.
     *
     * @param string $text Text to be added in the QR code
     * @param string $path file path of the image
     *
     * @return bool
     */
    public function generateQRImage($text, $path)
    {
        if (!empty($text) && !empty($path)) {
            $qrCode = new QrCode($text);
            //$qrCode->setEncoding('UTF-8');
            $qrCode->setSize(120);
            $qrCode->setMargin(5);
            $qrCode->setWriterByName('png');
            $qrCode->setErrorCorrectionLevel(ErrorCorrectionLevel::MEDIUM());
            $qrCode->setForegroundColor(['r' => 0, 'g' => 0, 'b' => 0, 'a' => 0]);
            $qrCode->setBackgroundColor(['r' => 255, 'g' => 255, 'b' => 255, 'a' => 0]);
            $qrCode->setValidateResult(false);
            $qrCode->writeFile($path);

            return true;
        }

        return false;
    }

    /**
     * Transforms certificate tags into text values. This function is very static
     * (it doesn't allow for much flexibility in terms of what tags are printed).
     *
     * @param array $array Contains two array entries: first are the headers,
     *                     second is an array of contents
     *
     * @return string The translated string
     */
    public function parseCertificateVariables($array)
    {
        $headers = $array[0];
        $content = $array[1];
        $final_content = [];

        if (!empty($content)) {
            foreach ($content as $key => $value) {
                $my_header = str_replace(['((', '))'], '', $headers[$key]);
                $final_content[$my_header] = $value;
            }
        }

        /* Certificate tags
         *
          0 => string '((user_firstname))' (length=18)
          1 => string '((user_lastname))' (length=17)
          2 => string '((gradebook_institution))' (length=25)
          3 => string '((gradebook_sitename))' (length=22)
          4 => string '((teacher_firstname))' (length=21)
          5 => string '((teacher_lastname))' (length=20)
          6 => string '((official_code))' (length=17)
          7 => string '((date_certificate))' (length=20)
          8 => string '((course_code))' (length=15)
          9 => string '((course_title))' (length=16)
          10 => string '((gradebook_grade))' (length=19)
          11 => string '((certificate_link))' (length=20)
          12 => string '((certificate_link_html))' (length=25)
          13 => string '((certificate_barcode))' (length=23)
         */

        $break_space = " \n\r ";
        $text =
            $final_content['gradebook_institution'].' - '.
            $final_content['gradebook_sitename'].' - '.
            get_lang('Certification').$break_space.
            get_lang('Student').': '.$final_content['user_firstname'].' '.$final_content['user_lastname'].$break_space.
            get_lang('Teacher').': '.$final_content['teacher_firstname'].' '.$final_content['teacher_lastname'].$break_space.
            get_lang('Date').': '.$final_content['date_certificate'].$break_space.
            get_lang('Score').': '.$final_content['gradebook_grade'].$break_space.
            'URL'.': '.$final_content['certificate_link'];

        return $text;
    }

    /**
     * Check if the certificate is visible for the current user
     * If the global setting allow_public_certificates is set to 'false', no certificate can be printed.
     * If the global allow_public_certificates is set to 'true' and the course setting allow_public_certificates
     * is set to 0, no certificate *in this course* can be printed (for anonymous users).
     * Connected users can always print them.
     *
     * @return bool
     */
    public function isVisible()
    {
        if (!api_is_anonymous()) {
            return true;
        }

        if (api_get_setting('allow_public_certificates') != 'true') {
            // The "non-public" setting is set, so do not print
            return false;
        }

        if (!isset($this->certificate_data, $this->certificate_data['cat_id'])) {
            return false;
        }

        $gradeBook = new Gradebook();
        $gradeBookInfo = $gradeBook->get($this->certificate_data['cat_id']);

        if (empty($gradeBookInfo['course_code'])) {
            return false;
        }

        $setting = api_get_course_setting(
            'allow_public_certificates',
            api_get_course_info($gradeBookInfo['course_code'])
        );

        if ($setting == 0) {
            // Printing not allowed
            return false;
        }

        return true;
    }

    /**
     * Check if the certificate is available.
     *
     * @return bool
     */
    public function isAvailable()
    {
        if (empty($this->certificate_data['path_certificate'])) {
            return false;
        }

        $userCertificate = $this->certification_user_path.basename($this->certificate_data['path_certificate']);

        if (!file_exists($userCertificate)) {
            return false;
        }

        return true;
    }

    /**
     * Shows the student's certificate (HTML file).
     */
    public function show()
    {
        $user_certificate = $this->certification_user_path.basename($this->certificate_data['path_certificate']);
        if (file_exists($user_certificate)) {
            // Needed in order to browsers don't add custom CSS
            $certificateContent = '<!DOCTYPE html>';
            $certificateContent .= (string) file_get_contents($user_certificate);

            // Remove media=screen to be available when printing a document
            $certificateContent = str_replace(
                ' media="screen"',
                '',
                $certificateContent
            );

            if ($this->user_id == api_get_user_id() &&
                !empty($this->certificate_data) &&
                isset($this->certificate_data['id'])
            ) {
                $certificateId = $this->certificate_data['id'];
                $extraFieldValue = new ExtraFieldValue('user_certificate');
                $value = $extraFieldValue->get_values_by_handler_and_field_variable(
                    $certificateId,
                    'downloaded_at'
                );
                if (empty($value)) {
                    $params = [
                        'item_id' => $this->certificate_data['id'],
                        'extra_downloaded_at' => api_get_utc_datetime(),
                    ];
                    $extraFieldValue->saveFieldValues($params);
                }
            }

            header('Content-Type: text/html; charset='.api_get_system_encoding());
            echo $certificateContent;

            return;
        }
        api_not_allowed(true);
    }

    /**
     * @return string
     */
    public function generateCustomCertificate()
    {
        $myCertificate = GradebookUtils::get_certificate_by_user_id(
            0,
            $this->user_id
        );
        if (empty($myCertificate)) {
            GradebookUtils::registerUserInfoAboutCertificate(
                0,
                $this->user_id,
                100,
                api_get_utc_datetime()
            );
        }

        $userInfo = api_get_user_info($this->user_id);
        $extraFieldValue = new ExtraFieldValue('user');
        $value = $extraFieldValue->get_values_by_handler_and_field_variable($this->user_id, 'legal_accept');
        $termsValidationDate = '';
        if (isset($value) && !empty($value['value'])) {
            list($id, $id2, $termsValidationDate) = explode(':', $value['value']);
        }

        $sessions = SessionManager::get_sessions_by_user($this->user_id, false, true);
        $totalTimeInLearningPaths = 0;
        $sessionsApproved = [];
        $coursesApproved = [];
        $courseList = [];

        if ($sessions) {
            foreach ($sessions as $session) {
                $allCoursesApproved = [];
                foreach ($session['courses'] as $course) {
                    $courseInfo = api_get_course_info_by_id($course['real_id']);
                    $courseCode = $courseInfo['code'];
                    $gradebookCategories = Category::load(
                        null,
                        null,
                        $courseCode,
                        null,
                        false,
                        $session['session_id']
                    );

                    if (isset($gradebookCategories[0])) {
                        /** @var Category $category */
                        $category = $gradebookCategories[0];
                        $result = Category::userFinishedCourse(
                            $this->user_id,
                            $category,
                            true
                        );

                        // Find time spent in LP
                        $timeSpent = Tracking::get_time_spent_in_lp(
                            $this->user_id,
                            $courseCode,
                            [],
                            $session['session_id']
                        );

                        if (!isset($courseList[$course['real_id']])) {
                            $courseList[$course['real_id']]['approved'] = false;
                            $courseList[$course['real_id']]['time_spent'] = 0;
                        }

                        if ($result) {
                            $courseList[$course['real_id']]['approved'] = true;
                            $coursesApproved[$course['real_id']] = $courseInfo['title'];

                            // Find time spent in LP
                            //$totalTimeInLearningPaths += $timeSpent;
                            $allCoursesApproved[] = true;
                        }
                        $courseList[$course['real_id']]['time_spent'] += $timeSpent;
                    }
                }

                if (count($allCoursesApproved) == count($session['courses'])) {
                    $sessionsApproved[] = $session;
                }
            }
        }

        $totalTimeInLearningPaths = 0;
        foreach ($courseList as $courseId => $courseData) {
            if ($courseData['approved'] === true) {
                $totalTimeInLearningPaths += $courseData['time_spent'];
            }
        }

        $skill = new Skill();
        // Ofaj
        $skills = $skill->getStudentSkills($this->user_id, 2);
        $timeInSeconds = Tracking::get_time_spent_on_the_platform(
            $this->user_id,
            'ever'
        );
        $time = api_time_to_hms($timeInSeconds);

        $tplContent = new Template(null, false, false, false, false, false);

        // variables for the default template
        $tplContent->assign('complete_name', $userInfo['complete_name']);
        $tplContent->assign('time_in_platform', $time);
        $tplContent->assign('certificate_generated_date', api_get_local_time($myCertificate['created_at']));
        if (!empty($termsValidationDate)) {
            $termsValidationDate = api_get_local_time($termsValidationDate);
        }
        $tplContent->assign('terms_validation_date', $termsValidationDate);

        // Ofaj
        $tplContent->assign('time_in_platform_in_hours', round($timeInSeconds / 3600, 1));
        $tplContent->assign(
            'certificate_generated_date_no_time',
            api_get_local_time(
                $myCertificate['created_at'],
                null,
                null,
                false,
                false,
                false,
                'd-m-Y'
            )
        );
        $tplContent->assign(
            'terms_validation_date_no_time',
            api_get_local_time(
                $termsValidationDate,
                null,
                null,
                false,
                false,
                false,
                'd-m-Y'
            )
        );
        $tplContent->assign('skills', $skills);
        $tplContent->assign('sessions', $sessionsApproved);
        $tplContent->assign('courses', $coursesApproved);
        $tplContent->assign('time_spent_in_lps', api_time_to_hms($totalTimeInLearningPaths));
        $tplContent->assign('time_spent_in_lps_in_hours', round($totalTimeInLearningPaths / 3600, 1));

        $layoutContent = $tplContent->get_template('gradebook/custom_certificate.tpl');
        $content = $tplContent->fetch($layoutContent);

        return $content;
    }

    /**
     * Ofaj.
     */
    public function generatePdfFromCustomCertificate()
    {
        $orientation = api_get_configuration_value('certificate_pdf_orientation');

        $params['orientation'] = 'landscape';
        if (!empty($orientation)) {
            $params['orientation'] = $orientation;
        }

        $params['left'] = 0;
        $params['right'] = 0;
        $params['top'] = 0;
        $params['bottom'] = 0;
        $page_format = $params['orientation'] == 'landscape' ? 'A4-L' : 'A4';
        $pdf = new PDF($page_format, $params['orientation'], $params);

        $pdf->html_to_pdf(
            $this->html_file,
            get_lang('Certificates'),
            null,
            false,
            false
        );
    }

    /**
     * @param int $userId
     *
     * @return array
     */
    public static function getCertificateByUser($userId)
    {
        $userId = (int) $userId;
        if (empty($userId)) {
            return [];
        }

        $table = Database::get_main_table(TABLE_MAIN_GRADEBOOK_CERTIFICATE);
        $sql = "SELECT * FROM $table
                WHERE user_id= $userId";
        $rs = Database::query($sql);

        return Database::store_result($rs, 'ASSOC');
    }

    /**
     * @param int $userId
     */
    public static function generateUserSkills($userId)
    {
        $controller = new IndexManager(get_lang('MyCourses'));
        $courseAndSessions = $controller->returnCoursesAndSessions($userId, true, null, true, false);

        if (isset($courseAndSessions['courses']) && !empty($courseAndSessions['courses'])) {
            foreach ($courseAndSessions['courses'] as $course) {
                $cats = Category::load(
                    null,
                    null,
                    $course['code'],
                    null,
                    null,
                    null,
                    false
                );

                if (isset($cats[0]) && !empty($cats[0])) {
                    Category::generateUserCertificate(
                        $cats[0]->get_id(),
                        $userId
                    );
                }
            }
        }

        if (isset($courseAndSessions['sessions']) && !empty($courseAndSessions['sessions'])) {
            foreach ($courseAndSessions['sessions'] as $sessionCategory) {
                if (isset($sessionCategory['sessions'])) {
                    foreach ($sessionCategory['sessions'] as $sessionData) {
                        if (!empty($sessionData['courses'])) {
                            $sessionId = $sessionData['session_id'];
                            foreach ($sessionData['courses'] as $courseData) {
                                $cats = Category::load(
                                    null,
                                    null,
                                    $courseData['course_code'],
                                    null,
                                    null,
                                    $sessionId,
                                    false
                                );

                                if (isset($cats[0]) && !empty($cats[0])) {
                                    Category::generateUserCertificate(
                                        $cats[0]->get_id(),
                                        $userId
                                    );
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}
