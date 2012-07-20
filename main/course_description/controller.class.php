<?php

namespace CourseDescription;

use \Display;
use \Template;
use \FormValidator;
use \Security;
use Uri;
use Redirect;
use Chamilo;
use Javascript;

/**
 * Controller for course description. Dispatch request and peform required action.
 * 
 *      - list course description for course
 *      - add a new course description to a course/session
 *      - edit a course session
 *      - delete a course session
 * 
 * Usage:
 * 
 *      $controller = CourseDescriptionController::instance();
 *      $controller->run();
 * 
 * @package chamilo.course_description 
 * @author Christian Fasanando <christian1827@gmail.com>
 * @author Laurent Opprecht <laurent@opprecht.info> for the Univesity of Genevas
 * @license see /license.txt
 */
class Controller extends \Controller
{

    const ACTION_ADD = 'add';
    const ACTION_EDIT = 'edit';
    const ACTION_DELETE = 'delete';
    const ACTION_LISTING = 'listing';
    const ACTION_DEFAULT = 'listing';
    const ACTION_EXPORT_CSV = 'export_csv';
    const ACTION_IMPORT_CSV = 'import_csv';

    /**
     * Return the instance of the controller.
     * 
     * @return CourseDescriptionController 
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
     * Action to perform. 
     * Returns the request parameter.
     * 
     * @return string
     */
    public function get_action()
    {
        if (Request::is_student_view()) {
            return self::ACTION_LISTING;
        }

        $result = parent::get_action();
        $result = $result ? $result : self::ACTION_DEFAULT;
        return $result;
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

    /**
     * Whether the call is authorized or not.
     * 
     * @return boolean 
     */
    public function authorize()
    {
        $authorize = api_protect_course_script(true);
        if (!$authorize) {
            return false;
        }

        $c_id = Request::get_c_id();
        if (empty($c_id)) {
            return false;
        }

        return true;
    }

    /**
     * Prepare the environment. Set up breadcrumps and raise tracking event. 
     */
    protected function prolog()
    {
        global $interbreadcrumb;
        $interbreadcrumb = array();
        $interbreadcrumb[] = array('url' => 'index.php', 'name' => get_lang('CourseProgram'));

        $type_id = Request::get_description_type();
        $type = CourseDescriptionType::repository()->find_one_by_id($type_id);
        if ($type) {
            $interbreadcrumb[] = array('url' => '#', 'name' => $type->get_title());
        }

        global $this_section;
        $this_section = SECTION_COURSES;

        global $current_course_tool;
        $current_course_tool = TOOL_COURSE_DESCRIPTION;

        // Tracking
        event_access_tool(TOOL_COURSE_DESCRIPTION);
    }

    /**
     * Javascript used by the controller
     * 
     * @return string
     */
    public function javascript()
    {
        $src = Chamilo::url('/main/course_description/resources/js/main.js');
        $result = Javascript::tag($src);

        $www = Chamilo::url();
        $code = "var www = '$www';\n";
        //$code .= Javascript::get_lang('');
        $result .= Javascript::tag_code($code);
        return $result;
    }

    /**
     * Returns a url for an action that the controller can process
     * 
     * @param string $action
     * @param array $params
     * @return string 
     */
    public function url($action = '', $params = array())
    {
        $url_params = Uri::course_params();
        if ($c_id = Request::get_c_id()) {
            $url_params[Request::PARAM_C_ID] = $c_id;
        }
        if ($id = Request::get_id()) {
            $url_params[Request::PARAM_ID] = $id;
        }
        if ($session_id = Request::get_session_id()) {
            $url_params[Request::PARAM_SESSION_ID] = $session_id;
        }
        $url_params[Request::PARAM_ACTION] = $action;

        foreach ($params as $key => $value) {
            $url_params[$key] = $value;
        }

        $result = Uri::url('/main/course_description/index.php', $url_params, false);
        return $result;
    }

    /**
     * List course descriptions.
     * 
     * @param array messages 
     */
    public function listing()
    {
        $course = (object) array();
        $course->c_id = Request::get_c_id();
        $course->session_id = Request::get_session_id();

        $repo = CourseDescription::repository();
        $descriptions = $repo->find_by_course($course);

        $data = (object) array();
        $data->descriptions = $descriptions;
        $this->render('index', $data);
    }

    /**
     * Performs the edit action. 
     */
    public function edit()
    {
        if (!$this->is_allowed_to_edit()) {
            $this->forbidden();
            return;
        }
        
        $id = Request::get_id();
        $c_id = Request::get_c_id();

        $repo = CourseDescription::repository();
        $description = $repo->find_one_by_id($c_id, $id);

        $action = $this->url(self::ACTION_EDIT);
        $form = CourseDescriptionForm::create($action, $description);

        if ($form->validate()) {
            $success = $repo->save($description);
            
            $message = $success ? get_lang('DescriptionUpdated') : get_lang('Error');

            $home = $this->url(self::ACTION_DEFAULT);
            Redirect::go($home);
        }
        
        $data = (object) array();
        $data->form = $form;
        $this->render('edit', $data);
    }

    /**
     * Perform the add action
     */
    public function add()
    {
        if (!$this->is_allowed_to_edit()) {
            $this->forbidden();
            return;
        }

        $type_id = Request::get_description_type();
        $type = CourseDescriptionType::repository()->find_one_by_id($type_id);

        $c_id = Request::get_c_id();
        $session_id = Request::get_session_id();

        if (empty($type)) {
            $home = $this->url(self::ACTION_DEFAULT);
            Redirect::go($home);
        }

        $description = $type->create();
        $description->c_id = $c_id;
        $description->session_id = $session_id;

        $params = array();
        $params[Request::PARAM_DESCRIPTION_TYPE] = $type_id;
        $action = $this->url(self::ACTION_ADD, $params);
        $form = CourseDescriptionForm::create($action, $description);

        if ($form->validate()) {
            $repo = CourseDescription::repository();
            $success = $repo->save($description);

            $message = $success ? get_lang('CourseDescriptionAdded') : get_lang('Error');

            $home = $this->url();
            Redirect::go($home);
        }

        //$is_valid = !empty($type) && !empty($c_id) && !empty($title) && !empty($content) && Security::check_token();

        $data = (object) array();
        $data->type = $type;
        $data->form = $form;
        $this->render('edit', $data);
    }

    /**
     * Performs the delete action.
     * 
     * @todo: could be worth to require a security token in the url and check it. Currently confirmation is done through javascript confirmation only.
     */
    public function delete()
    {
        if (!$this->is_allowed_to_edit()) {
            $this->forbidden();
            return;
        }
//        $is_valid = Security::check_token();
//        if (!$is_valid) {
//            $this->listing();
//            return false;
//        }

        $description->c_id = Request::get_c_id();
        $description->id = Request::get_id();

        $repo = CourseDescription::repository();
        $success = $repo->remove($description);

        $message = $success ? get_lang('CourseDescriptionDeleted') : get_lang('Error');

        $home = $this->url();
        Redirect::go($home);
    }

    public function export_csv()
    {
        $c_id = Request::get_c_id();
        $session_id = Request::get_session_id();

        $course = (object) array();
        $course->c_id = $c_id;
        $course->session_id = $session_id;
        $descriptions = CourseDescription::repository()->find_by_course($course);

        $writer = new CsvWriter();
        $writer->add($descriptions);
        $path = $writer->get_path();

        \DocumentManager :: file_send_for_download($path, true, get_lang('CourseDescriptions') . '.csv');
    }

    public function import_csv()
    {
        if (!$this->is_allowed_to_edit()) {
            $this->forbidden();
            return;
        }

        $action = $this->url(self::ACTION_IMPORT_CSV);
        $form = new UploadFileForm('import_csv', 'post', $action);
        $form->init();
        if ($form->validate()) {
            $file = $form->get_file();
            $path = $file->tmp_name;
            $reader = new CsvReader($path);
            $descriptions = $reader->get_items();
            
            $c_id = Request::get_c_id();
            $session_id = Request::get_session_id();
            $course = (object) array();
            $course->c_id = $c_id;
            $course->session_id = $session_id;

            $import = new CourseImport($course);
            $import->add($descriptions);
            $home = $this->url(self::ACTION_DEFAULT);
            Redirect::go($home);
        }

        $data = (object) array();
        $data->form = $form;
        $this->render('upload', $data);
    }

    /**
     * Render a template using data. Adds a few common parameters to the data array.
     * 
     * @see /main/template/default/course_description/
     * @param string $template
     * @param array $data 
     */
    protected function render($template, $data)
    {
        $data = $data ? $data : (object) array();

        $_user = api_get_user_info();
        $session_id = Request::get_session_id();
        $data->session_image = api_get_session_image($session_id, $_user);

        $sec_token = Security::get_token();
        $data->sec_token = $sec_token;

        $data->root = $this->url('');

        $data->types = CourseDescriptionType::repository()->all();

        $data->session_id = $session_id;
        $data->c_id = Request::get_c_id();
        $data->is_allowed_to_edit = $this->is_allowed_to_edit();
        parent::render("course_description/$template.tpl", $data);
    }

}