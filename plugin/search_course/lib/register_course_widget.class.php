<?php

/**
 * Register course widget.
 * Handles user's registration action.
 * Display a register to course form if required.
 *
 * @copyright (c) 2011 University of Geneva
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author Laurent Opprecht
 */
class RegisterCourseWidget
{
    public const ACTION_SUBSCRIBE = 'subscribe';
    public const PARAM_SUBSCRIBE = 'subscribe';
    public const PARAM_PASSCODE = 'course_registration_code';

    /**
     * Returns $_POST data for $key is it exists or $default otherwise.
     *
     * @param string $key
     * @param object $default
     *
     * @return string
     */
    public static function post($key, $default = '')
    {
        return isset($_POST[$key]) ? $_POST[$key] : $default;
    }

    /**
     * Returns $_GET data for $key is it exists or $default otherwise.
     *
     * @param string $key
     * @param object $default
     *
     * @return string
     */
    public static function get($key, $default = '')
    {
        return isset($_GET[$key]) ? $_GET[$key] : $default;
    }

    /**
     * @return RegisterCourseWidget
     */
    public static function factory()
    {
        return new self();
    }

    public function run()
    {
        return $this->action_subscribe_user();
    }

    /**
     * Handle the subscribe action.
     *
     * @return bool
     */
    public function action_subscribe_user()
    {
        $action = self::get('action');
        if ($action != self::ACTION_SUBSCRIBE) {
            return false;
        }

        $course_code = self::post(self::PARAM_SUBSCRIBE);
        if (empty($course_code)) {
            return false;
        }

        $registration_code = self::post(self::PARAM_PASSCODE);

        if ($this->subscribe_user($course_code, $registration_code)) {
            echo Display::return_message(get_lang('EnrollToCourseSuccessful'), 'confirmation');

            return;
        }
        if (!empty($registration_code)) {
            echo Display::return_message(get_lang('CourseRegistrationCodeIncorrect'), 'error');
        }
        $this->display_form($course_code);

        return true;
    }

    /**
     * Regiser a user to a course.
     * Returns true on success, false otherwise.
     *
     * @param string $course_code
     * @param string $registration_code
     * @param int    $user_id
     *
     * @return bool
     */
    public function subscribe_user($course_code, $registration_code = '', $user_id = null)
    {
        $course = api_get_course_info($course_code);
        $course_regisration_code = $course['registration_code'];
        if (!empty($course_regisration_code) && $registration_code != $course_regisration_code) {
            return false;
        }

        if (empty($user_id)) {
            global $_user;
            $user_id = $_user['user_id'];
        }

        return (bool) CourseManager::subscribeUser($user_id, $course_code);
    }

    /**
     * Display the course registration form.
     * Asks for registration code/password.
     *
     * @param string $course_code
     */
    public function display_form($course_code)
    {
        global $stok;

        $course = api_get_course_info($course_code);
        $self = $_SERVER['REQUEST_URI'];
        $course_code = $course['code'];
        $course_visual_code = $course['visual_code'];
        $course_title = $course['title'];
        $submit_registration_code_label = get_lang("SubmitRegistrationCode");
        $course_requires_password_label = get_lang('CourseRequiresPassword');

        $result = <<<EOT
            $course_requires_password_label<br/>
            $course_visual_code - $course_title
            <form action="$self" method="post">
            <input type="hidden" name="sec_token" value="$stok" />
            <input type="hidden" name="subscribe" value="$course_code" />
            <input type="text" name="course_registration_code" value="$registration_code" />
            <input type="Submit" name="submit_course_registration_code" value="OK" alt="$submit_registration_code_label" /></form>
EOT;
        echo $result;
    }
}
