<?php
/* For licensing terms, see /license.txt */

/**
 * Certificate Class
 * Generate certificates based in the gradebook tool.
 * @package chamilo.library.certificates
 */
class Certificate extends Model
{
    public $table;
    public $columns = [
        'id',
        'cat_id',
        'score_certificate',
        'created_at',
        'path_certificate'
    ];
    /**
     * Certification data
     */
    public $certificate_data = [];

    /**
     * Student's certification path
     */
    public $certification_user_path = null;
    public $certification_web_user_path = null;
    public $html_file = null;
    public $qr_file = null;
    public $user_id;

    /** If true every time we enter to the certificate URL
     * we would generate a new certificate (good thing because we can edit the
     * certificate and all users will have the latest certificate bad because we
     * load the certificate every time */
    public $force_certificate_generation = true;

    /**
     * Constructor
     * @param int $certificate_id ID of the certificate.
     * @param int $userId
     * @param bool $sendNotification send message to student
     *
     * If no ID given, take user_id and try to generate one
     */
    public function __construct(
        $certificate_id = 0,
        $userId = 0,
        $sendNotification = false
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
                    $path_certificate
                );
                $this->certificate_data['path_certificate'] = $path_certificate;

                if ($this->isHtmlFileGenerated()) {
                    if (!empty($file_info)) {
                        //$text = $this->parse_certificate_variables($new_content_html['variables']);
                        //$this->generate_qr($text, $qr_code_filename);
                    }
                }
            }

            return $result;
        }
    }

    /**
     * Checks if the certificate user path directory is created
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

            if (!is_dir($path_info)) {
                mkdir($path_info, 0777, true);
            }
            if (!is_dir($this->certification_user_path)) {
                mkdir($this->certification_user_path, 0777);
            }
        }
    }

    /**
     * Deletes the current certificate object. This is generally triggered by
     * the teacher from the gradebook tool to re-generate the certificate because
     * the original version wa flawed.
     * @param bool $force_delete
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
     *  Generates an HTML Certificate and fills the path_certificate field in the DB
     *
     * @param array $params
     * @param bool $sendNotification
     * @return bool|int
     */
    public function generate($params = [], $sendNotification = false)
    {
        // The user directory should be set
        if (empty($this->certification_user_path) &&
            $this->force_certificate_generation == false
        ) {
            return false;
        }

        $params['hide_print_button'] = isset($params['hide_print_button']) ? true : false;

        if (isset($this->certificate_data) && isset($this->certificate_data['cat_id'])) {
            $my_category = Category::load($this->certificate_data['cat_id']);
        }

        if (isset($my_category[0]) &&
            $my_category[0]->is_certificate_available($this->user_id)
        ) {
            $courseInfo = api_get_course_info($my_category[0]->get_course_code());
            $courseId = $courseInfo['real_id'];
            $sessionId = $my_category[0]->get_session_id();

            $skill = new Skill();
            $skill->addSkillToUser(
                $this->user_id,
                $this->certificate_data['cat_id'],
                $courseId,
                $sessionId
            );

            if (is_dir($this->certification_user_path)) {
                if (!empty($this->certificate_data)) {
                    $new_content_html = GradebookUtils::get_user_certificate_content(
                        $this->user_id,
                        $my_category[0]->get_course_code(),
                        $my_category[0]->get_session_id(),
                        false,
                        $params['hide_print_button']
                    );

                    if ($my_category[0]->get_id() == strval(intval($this->certificate_data['cat_id']))) {
                        $name = $this->certificate_data['path_certificate'];
                        $myPathCertificate = $this->certification_user_path.basename($name);

                        if (file_exists($myPathCertificate) &&
                            !empty($name) &&
                            !is_dir($myPathCertificate) &&
                            $this->force_certificate_generation == false
                        ) {
                            //Seems that the file was already generated
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
                                self::updateUserCertificateInfo(
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
                                            Certificate::sendNotification(
                                                $subject,
                                                $message,
                                                api_get_user_info($this->user_id),
                                                $courseInfo,
                                                [
                                                    'score_certificate' => $score
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

                if ($this->isHtmlFileGenerated()) {
                    if (!empty($file_info)) {
                        //$text = $this->parse_certificate_variables($new_content_html['variables']);
                        //$this->generate_qr($text, $qr_code_filename);
                    }
                }
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
            '((portal_name))'
        ];

        return $tags;
    }

    /**
     * @param string $subject
     * @param string $message
     * @param array $userInfo
     * @param array $courseInfo
     * @param array $certificateInfo
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

        $replace = [
            $courseInfo['title'],
            $userInfo['firstname'],
            $userInfo['lastname'],
            $currentUserInfo['firstname'],
            $currentUserInfo['lastname'],
            $certificateInfo['score_certificate'],
            api_get_setting('Institution')
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
                'direct_message' => $message
            ];
            $smsPlugin->send($additionalParameters);
        }
    }

    /**
     * Update user info about certificate
     * @param int $cat_id category id
     * @param int $user_id user id
     * @param string $path_certificate the path name of the certificate
     *
     */
    public function updateUserCertificateInfo(
        $cat_id,
        $user_id,
        $path_certificate
    ) {
        $table = Database::get_main_table(TABLE_MAIN_GRADEBOOK_CERTIFICATE);
        $now = api_get_utc_datetime();
        if (!UserManager::is_user_certified($cat_id, $user_id)) {
            $sql = 'UPDATE '.$table.' SET 
                        path_certificate="'.Database::escape_string($path_certificate).'",
                        created_at = "'.$now.'"
                    WHERE cat_id="'.intval($cat_id).'" AND user_id="'.intval($user_id).'" ';
            Database::query($sql);
        }
    }

    /**
     * Check if the file was generated
     *
     * @return boolean
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
     * Generates a QR code for the certificate. The QR code embeds the text given
     * @param string $text Text to be added in the QR code
     * @param string $path file path of the image
     * @return bool
     **/
    public function generateQRImage($text, $path)
    {
        //Make sure HTML certificate is generated
        if (!empty($text) && !empty($path)) {
            //L low, M - Medium, L large error correction
            return PHPQRCode\QRcode::png($text, $path, 'M', 2, 2);
        }
        return false;
    }

    /**
     * Transforms certificate tags into text values. This function is very static
     * (it doesn't allow for much flexibility in terms of what tags are printed).
     * @param array $array Contains two array entries: first are the headers,
     * second is an array of contents
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

        if (api_get_course_setting('allow_public_certificates', $gradeBookInfo['course_code']) == 0) {
            // Printing not allowed
            return false;
        }

        return true;
    }

    /**
     * Check if the certificate is available
     * @return bool
     */
    public function isAvailable()
    {
        if (empty($this->certificate_data['path_certificate'])) {
            return false;
        }

        $user_certificate = $this->certification_user_path.basename($this->certificate_data['path_certificate']);

        if (!file_exists($user_certificate)) {
            return false;
        }

        return true;
    }

    /**
    * Shows the student's certificate (HTML file)
    */
    public function show()
    {
        header('Content-Type: text/html; charset='.api_get_system_encoding());

        $user_certificate = $this->certification_user_path.basename($this->certificate_data['path_certificate']);
        if (file_exists($user_certificate)) {
            $certificateContent = (string) file_get_contents($user_certificate);

            if ($this->user_id == api_get_user_id() && !empty($this->certificate_data)) {
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

        $sessions = SessionManager::get_sessions_by_user($this->user_id);
        $sessionsApproved = [];
        if ($sessions) {
            foreach ($sessions as $session) {
                $allCoursesApproved = [];
                foreach ($session['courses'] as $course) {
                    $courseInfo = api_get_course_info_by_id($course['real_id']);
                    $gradebookCategories = Category::load(
                        null,
                        null,
                        $courseInfo['code'],
                        null,
                        false,
                        $session['session_id']
                    );

                    if (isset($gradebookCategories[0])) {
                        /** @var Category $category */
                        $category = $gradebookCategories[0];
                        //  $categoryId = $category->get_id();
                        // @todo how we check if user pass a gradebook?
                        //$certificateInfo = GradebookUtils::get_certificate_by_user_id($categoryId, $this->user_id);

                        $result = Category::userFinishedCourse(
                            $this->user_id,
                            $category,
                            null,
                            $courseInfo['code'],
                            $session['session_id'],
                            true
                        );

                        if ($result) {
                            $allCoursesApproved[] = true;
                        }
                    }
                }

                if (count($allCoursesApproved) == count($session['courses'])) {
                    $sessionsApproved[] = $session;
                }
            }
        }

        $skill = new Skill();
        $skills = $skill->getStudentSkills($this->user_id);
        $timeInSeconds = Tracking::get_time_spent_on_the_platform($this->user_id);
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
        $tplContent->assign('time_in_platform_in_hours', round($timeInSeconds/3600, 1));
        $tplContent->assign(
            'certificate_generated_date_no_time',
            api_get_local_time(
                $myCertificate['created_at'],
                null,
                null,
                false,
                false
            )
        );
        $tplContent->assign(
            'terms_validation_date_no_time',
            api_get_local_time(
                $termsValidationDate,
                null,
                null,
                false,
                false
            )
        );
        $tplContent->assign('skills', $skills);
        $tplContent->assign('sessions', $sessionsApproved);

        $layoutContent = $tplContent->get_template('gradebook/custom_certificate.tpl');
        $content = $tplContent->fetch($layoutContent);

        return $content;
    }

    /**
     * Ofaj
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
}
