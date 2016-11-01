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
    public $columns = array(
        'id',
        'cat_id',
        'score_certificate',
        'created_at',
        'path_certificate'
    );
    /**
     * Certification data
     */
    public $certificate_data = array();

    /**
     * Student's certification path
     */
    public $certification_user_path = null;
    public $certification_web_user_path = null;
    public $html_file = null;
    public $qr_file = null;
    public $user_id;

    /* If true every time we enter to the certificate URL
    we would generate a new certificate (good thing because we can edit the
    certificate and all users will have the latest certificate bad because we
    load the certificate everytime*/

    public $force_certificate_generation = true;

    /**
     * Constructor
     * @param int $certificate_id ID of the certificate.
     * @param int $userId
     *
     * If no ID given, take user_id and try to generate one
     */
    public function __construct($certificate_id = 0, $userId = 0)
    {
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
                $this->generate();
            }

            if (isset($this->certificate_data) && $this->certificate_data) {
                if (empty($this->certificate_data['path_certificate'])) {
                    $this->generate();
                }
            }
        }

        //Setting the qr and html variables
        if (
            isset($certificate_id) &&
            !empty($this->certification_user_path) &&
            isset($this->certificate_data['path_certificate'])
        ) {
            $pathinfo = pathinfo($this->certificate_data['path_certificate']);
            $this->html_file = $this->certification_user_path.basename($this->certificate_data['path_certificate']);
            $this->qr_file = $this->certification_user_path.$pathinfo['filename'].'_qr.png';
        }
    }

    /**
     * Checks if the certificate user path directory is created
     */
    public function check_certificate_path()
    {
        $this->certification_user_path = null;

        //Setting certification path
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
                //Deleting HTML file
                if (is_file($this->html_file)) {
                    @unlink($this->html_file);
                    if (is_file($this->html_file) === false) {
                        $delete_db = true;
                    } else {
                        $delete_db = false;
                    }
                }
                //Deleting QR code PNG image file
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
     * @return bool|int
     */
    public function generate($params = array())
    {
        // The user directory should be set
        if (empty($this->certification_user_path) &&
            $this->force_certificate_generation == false
        ) {
            return false;
        }

        $params['hide_print_button'] = isset($params['hide_print_button']) ? true : false;

        if (isset($this->certificate_data) && isset($this->certificate_data['cat_id'])) {
            $my_category = Category :: load($this->certificate_data['cat_id']);
        }

        if (isset($my_category[0]) &&
            $my_category[0]->is_certificate_available($this->user_id)
        ) {
            $courseId = api_get_course_int_id();
            $sessionId = api_get_session_id();

            $skill = new Skill();
            $skill->add_skill_to_user(
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
                        $my_path_certificate = $this->certification_user_path.basename($name);
                        if (file_exists($my_path_certificate) &&
                            !empty($name) &&
                            !is_dir($my_path_certificate) &&
                            $this->force_certificate_generation == false
                        ) {
                            //Seems that the file was already generated
                            return true;
                        } else {
                            // Creating new name
                            $name = md5($this->user_id.$this->certificate_data['cat_id']).'.html';
                            $my_path_certificate = $this->certification_user_path.$name;
                            $path_certificate    ='/'.$name;

                            //Getting QR filename
                            $file_info = pathinfo($path_certificate);
                            $qr_code_filename = $this->certification_user_path.$file_info['filename'].'_qr.png';

                            $my_new_content_html = str_replace(
                                '((certificate_barcode))',
                                Display::img($this->certification_web_user_path.$file_info['filename'].'_qr.png', 'QR'),
                                $new_content_html['content']
                            );
                            $my_new_content_html = mb_convert_encoding(
                                $my_new_content_html,
                                'UTF-8',
                                api_get_system_encoding()
                            );

                            $result = @file_put_contents($my_path_certificate, $my_new_content_html);
                            if ($result) {
                                //Updating the path
                                self::update_user_info_about_certificate(
                                    $this->certificate_data['cat_id'],
                                    $this->user_id,
                                    $path_certificate
                                );
                                $this->certificate_data['path_certificate'] = $path_certificate;

                                if ($this->html_file_is_generated()) {
                                    if (!empty($file_info)) {
                                        $text = $this->parse_certificate_variables($new_content_html['variables']);
                                        $this->generate_qr($text, $qr_code_filename);
                                    }
                                }
                            }

                            return $result;
                        }
                    }
                }
            }
        }

        return false;
    }

    /**
    * update user info about certificate
    * @param int $cat_id category id
    * @param int $user_id user id
    * @param string $path_certificate the path name of the certificate
    * @return void
    */
    public function update_user_info_about_certificate(
        $cat_id,
        $user_id,
        $path_certificate
    ) {
        $table_certificate = Database::get_main_table(TABLE_MAIN_GRADEBOOK_CERTIFICATE);
        $now = api_get_utc_datetime();
        if (!UserManager::is_user_certified($cat_id, $user_id)) {
            $sql = 'UPDATE '.$table_certificate.' SET 
                        path_certificate="'.Database::escape_string($path_certificate).'",
                        created_at = "'.$now.'"
                    WHERE cat_id="'.intval($cat_id).'" AND user_id="'.intval($user_id).'" ';
            Database::query($sql);
        }
    }

    /**
     *
     * Check if the file was generated
     *
     * @return boolean
     */
    public function html_file_is_generated()
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
     * @param    string    $text Text to be added in the QR code
     * @param    string    $path file path of the image
     * */
    public function generate_qr($text, $path)
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
     * @param array $array Contains two array entris: first are the headers,
     * second is an array of contents
     * @return string The translated string
     */
    public function parse_certificate_variables($array)
    {
        $headers = $array[0];
        $content = $array[1];
        $final_content = array();

        if (!empty($content)) {
            foreach ($content as $key => $value) {
                $my_header = str_replace(array('((', '))'), '', $headers[$key]);
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
            get_lang('Student'). ': '.$final_content['user_firstname'].' '.$final_content['user_lastname'].$break_space.
            get_lang('Teacher'). ': '.$final_content['teacher_firstname'].' '.$final_content['teacher_lastname'].$break_space.
            get_lang('Date'). ': '.$final_content['date_certificate'].$break_space.
            get_lang('Score'). ': '.$final_content['gradebook_grade'].$break_space.
            'URL'. ': '.$final_content['certificate_link'];

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

        $gradebook = new Gradebook();
        $gradebook_info = $gradebook->get($this->certificate_data['cat_id']);

        if (empty($gradebook_info['course_code'])) {
            return false;
        }

        if (api_get_course_setting('allow_public_certificates', $gradebook_info['course_code']) == 0) {
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

        $user_certificate = $this->certification_user_path . basename($this->certificate_data['path_certificate']);

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
        header('Content-Type: text/html; charset='. api_get_system_encoding());

        $user_certificate = $this->certification_user_path . basename($this->certificate_data['path_certificate']);
        $certificateContent = file_get_contents($user_certificate);

        $certificate = new DOMDocument();
        $certificate->loadHTML($certificateContent);

        echo $certificate->saveHTML();
    }
}
