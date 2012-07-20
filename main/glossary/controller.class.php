<?php

namespace Glossary;

use \ChamiloSession as Session;
use \Display;
use \Template;
use \FormValidator;
use \Security;
use Uri;
use Redirect;
use Chamilo;
use Javascript;

/**
 * Controller for glossary. Dispatch request and peform required action.
 * 
 *      - list glossary entries for course
 *      - add/edit glossary entry
 *      - change view from table to details
 * 
 * Usage:
 * 
 *      $controller = Controller::instance();
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
    const ACTION_INDEX = 'index';
    const ACTION_DEFAULT = 'index';
    const ACTION_EXPORT_CSV = 'export_csv';
    const ACTION_IMPORT_CSV = 'import_csv';

    /**
     * Return the instance of the controller.
     * 
     * @return  \Glossary\Controller
     */
    public static function instance()
    {
        static $result = null;
        if (empty($result)) {
            $result = new self(Access::instance());
        }
        return $result;
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
            return self::ACTION_INDEX;
        }

        $result = parent::get_action();
        $result = $result ? $result : self::ACTION_DEFAULT;
        return $result;
    }

    public function is_allowed_to_edit()
    {
        return $this->access()->can_edit();
    }

    /**
     * Prepare the environment. Set up breadcrumps and raise tracking event. 
     */
    protected function prolog()
    {
        global $interbreadcrumb;
        $interbreadcrumb = array();
        $interbreadcrumb[] = array('url' => 'index.php', 'name' => get_lang('Glossary'));


        global $this_section;
        $this_section = SECTION_COURSES;

        global $current_course_tool;
        $current_course_tool = TOOL_GLOSSARY;

        // Tracking
        event_access_tool(TOOL_GLOSSARY);
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
        if ($action) {
            $url_params[Request::PARAM_ACTION] = $action;
        }

        foreach ($params as $key => $value) {
            $url_params[$key] = $value;
        }

        $result = Uri::url('/main/glossary/index.php', $url_params, false);
        return $result;
    }

    /**
     * List course descriptions.
     * 
     * @param array messages 
     */
    public function index()
    {
        $course = Request::get_course_key();
        $repo = Glossary::repository();
        $items = $repo->find_by_course($course);

        $view = Request::get_view();
        Session::write(Request::PARAM_VIEW, $view);

        $data = (object) array();
        $data->items = $items;
        $data->sort = $sort;
        $data->view = $view;
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

        $repo = Glossary::repository();
        $item = $repo->find_one_by_id($c_id, $id);

        $action = $this->url(self::ACTION_EDIT);
        $form = GlossaryForm::create($action, $item);

        if ($form->validate()) {
            $success = $repo->save($item);

            $message = $success ? get_lang('GlossaryTermUpdated') : get_lang('Error');

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

        $c_id = Request::get_c_id();
        $session_id = Request::get_session_id();

        $item = Glossary::create();
        $item->c_id = $c_id;
        $item->session_id = $session_id;

        $action = $this->url(self::ACTION_ADD);
        $form = GlossaryForm::create($action, $item);

        if ($form->validate()) {
            $repo = Glossary::repository();
            $success = $repo->save($item);

            $message = $success ? get_lang('GlossaryAdded') : get_lang('Error');

            $home = $this->url();
            Redirect::go($home);
        }

        $data = (object) array();
        $data->type = $type;
        $data->form = $form;
        $this->render('edit', $data);
    }

    /**
     * Performs the delete action.
     * 
     * @see AjaxController
     */
    public function delete()
    {
        if (!$this->is_allowed_to_edit()) {
            $this->forbidden();
            return;
        }

        $this->missing();
    }

    public function export_csv()
    {
        $course = Request::get_course_key();
        $items = Glossary::repository()->find_by_course($course);

        $writer = CsvWriter::create();
        $writer->add($items);
        $path = $writer->get_path();

        \DocumentManager :: file_send_for_download($path, true, get_lang('Glossary') . '.csv');
    }

    public function import_csv()
    {
        if (!$this->is_allowed_to_edit()) {
            $this->forbidden();
            return;
        }

        $action = $this->url(self::ACTION_IMPORT_CSV);
        $form = UploadFileForm::create($action);
        $form->init();
        if ($form->validate()) {
            $delete_all = $form->get_delete_all();
            if ($delete_all) {
                $course = Request::get_course_key();
                $repo = Glossary::repository();
                $repo->remove_by_course($course);
            }

            $file = $form->get_file();
            $path = $file->tmp_name;
            $reader = new CsvReader($path);
            $items = $reader->get_items();

            $course = Request::get_course_key();
            $import = new CourseImport($course);
            $import->add($items);
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

        $data->sec_token = $this->access()->get_token();
        ;

        $data->root = $this->url('');

        $data->session_id = $session_id;
        $data->c_id = Request::get_c_id();
        $data->is_allowed_to_edit = $this->is_allowed_to_edit();
        parent::render("glossary/$template.tpl", $data);
    }

}