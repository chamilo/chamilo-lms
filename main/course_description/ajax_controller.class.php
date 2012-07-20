<?php

namespace CourseDescription;

use \Display;
use \Template;
use \FormValidator;
use \Security;
use \Uri;
use Header;

/**
 * Ajax controller. Dispatch request and perform required action.
 * 
 *      - delete category/link 
 * 
 * Usage:
 * 
 *      $controller = AjaxController::instance();
 *      $controller->run();
 * 
 * @author Laurent Opprecht <laurent@opprecht.info> for the Univesity of Genevas
 * @license /license.txt
 */
class AjaxController extends \Controller
{

    const ACTION_DELETE = 'delete';
    const ACTION_DELETE_BY_COURSE = 'delete_by_course';

    /**
     * Return the instance of the controller.
     * 
     * @return  \CourseDescription\AjaxController
     */
    public static function instance()
    {
        static $result = null;
        if (empty($result)) {
            $result = new self();
        }
        return $result;
    }

    protected function __construct()
    {
        
    }

    /**
     * Prepare the environment. Set up breadcrumps and raise tracking event. 
     */
    protected function prolog()
    {
        event_access_tool(TOOL_COURSE_DESCRIPTION);
    }

    public function is_allowed_to_edit()
    {
        if (Request::is_student_view()) {
            return false;
        }
        $session_id = Request::get_session_id();

        if ($session_id != 0 && api_is_allowed_to_session_edit(false, true) == false) {
            return false;
        }

        if (!api_is_allowed_to_edit(false, true, true)) {
            return false;
        }
        return true;
    }
    
    public function authorize()
    {
        $authorize = api_protect_course_script();
        if (!$authorize) {
            return false;
        }

        $c_id = Request::get_c_id();
        if (empty($c_id)) {
            return false;
        }
        if (Request::is_student_view()) {
            return false;
        }
        if (!$this->is_allowed_to_edit()) {
            return false;
        }

        return true;
    }

    /**
     * 
     */
    public function delete()
    {
        if (!$this->is_allowed_to_edit()) {
            $this->forbidden();
            return;
        }

        $description = (object) array();
        $description->c_id = Request::get_c_id();
        $description->id = Request::get_id();

        $success = CourseDescription::repository()->remove($description);

        $this->response($success);
    }
    /**
     * 
     */
    public function delete_by_course()
    {
        if (!$this->is_allowed_to_edit()) {
            $this->forbidden();
            return;
        }
        
        $course = (object) array();
        $course->c_id = Request::get_c_id();
        $course->session_id = Request::get_session_id();
        
        $success = CourseDescription::repository()->remove_by_course($course);

        $this->response($success);
    }

    function forbidden()
    {
        $this->response(false, get_lang('YourAreNotAuthorized'));
    }

    public function unknown()
    {
        $this->response(false, get_lang('UnknownAction'));
    }

    /**
     * Action exists but implementation is missing. 
     */
    public function missing()
    {
        $this->response(false, get_lang('NoImplementation'));
    }

    /**
     * Display a standard json responce.
     * 
     * @param bool $success
     * @param string $message 
     * @param object $data
     */
    public function response($success = false, $message = '', $data = null)
    {
        $message = trim($message);
        $response = (object) array();
        $response->success = $success;
        if ($message) {
            $response->message = Display::return_message($message, $success ? 'normal' : 'error');
        } else {
            $response->message = '';
        }
        $response->data = $data;
        $this->render_json($response);
    }

}