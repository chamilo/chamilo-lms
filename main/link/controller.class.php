<?php

namespace Link;

use Security;
use Uri;
use Redirect;
use Chamilo;
use Javascript;
use Header;

/**
 * Html controller. Dispatch request and perform required action:
 * 
 *      - list
 *      - add/edit/delete link
 *      - add/edit/delete category
 *      - make visible/invisible link
 *      - go to link target
 * 
 * Note:
 * Currently some actions are only implemented in the Ajax controller.
 * 
 * @author Laurent Opprecht <laurent@opprecht.info> for the Univesity of Genevas
 * @license /license.txt
 */
class Controller extends \Controller
{

    const ACTION_LISTING = 'listing';
    const ACTION_VIEW = 'view';
    const ACTION_ADD_LINK = 'add_link';
    const ACTION_EDIT_LINK = 'edit_link';
    const ACTION_DELETE_LINK = 'delete_link';
    const ACTION_MAKE_VISIBLE = 'make_visisble';
    const ACTION_MAKE_INVISIBLE = 'make_invisible';
    const ACTION_ADD_CATEGORY = 'add_category';
    const ACTION_EDIT_CATEGORY = 'edit_category';
    const ACTION_DELETE_CATEGORY = 'delete_category';
    const ACTION_GO = 'go';
    const ACTION_IMPORT_CSV = 'import_csv';
    const ACTION_EXPORT_CSV = 'export_csv';
    const ACTION_DEFAULT = 'listing';

    /**
     * 
     * @return \Link\Controller
     */
    public static function instance()
    {
        static $result = null;
        if (empty($result)) {
            $result = new self();
        }
        return $result;
    }

    public function is_allowed_to_edit()
    {
        if (Request::is_student_view()) {
            return false;
        }
        //$c_id = self::params()->get_c_id();
        //$id = self::params()->get_id();
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
     * Action to perform. 
     * Returns the request parameter.
     * 
     * @return string
     */
    public function get_action()
    {
        $result = parent::get_action();
//        if (self::params()->is_student_view()) {
//            if ($result != self::ACTION_LISTING && $result != self::ACTION_VIEW) {
//                return self::ACTION_LISTING;
//            }
//        }
        $result = $result ? $result : self::ACTION_DEFAULT;
        return $result;
    }

    public function prolog()
    {
        event_access_tool(TOOL_LINK);

        //legacy
        global $interbreadcrumb;
        $interbreadcrumb = array();
        $interbreadcrumb[] = array('url' => 'index.php', 'name' => get_lang('Links'));

        global $current_course_tool;
        global $this_section;
        global $nameTools;
        $current_course_tool = TOOL_LINK;
        $this_section = SECTION_COURSES;
        $nameTools = get_lang('Links');
    }

    /**
     * Whether the call is authorized or not.
     * 
     * @return boolean 
     */
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

        return true;
    }

    /**
     * Javascript used by the controller
     * 
     * @return string
     */
    public function javascript()
    {
        $src = Chamilo::url('/main/link/resources/js/main.js');
        $result = Javascript::tag($src);

        $www = Chamilo::url();
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
    public function url($action, $params = array())
    {
        $url_params = Uri::course_params();
        if ($c_id = Request::get_c_id()) {
            $url_params[Request::PARAM_C_ID] = $c_id;
        }
        if ($id = Request::get_id()) {
            $url_params[Request::PARAM_ID] = $id;
        }
        if($session_id = Request::get_session_id()){
            $url_params[Request::PARAM_SESSION_ID] = $session_id;
        }
        $url_params[Request::PARAM_ACTION] = $action;

        foreach ($params as $key => $value) {
            $url_params[$key] = $value;
        }

        $result = Uri::url('/main/link/index.php', $url_params, false);
        return $result;
    }

    public function listing()
    {
        $c_id = Request::get_c_id();
        $session_id = Request::get_session_id();

        $root = (object) array();
        $root->c_id = $c_id;
        $root->id = 0;
        $root->session_id = $session_id;
        $links = LinkRepository::instance()->find_by_category($root);

        $repo = LinkCategory::repository();
        $categories = $repo->find_by_course($c_id, $session_id);

        //$data = compact('links', 'categories');
        $data = (object) array();
        $data->categories = $categories;
        $data->links = $links;
        $this->render('index', $data);
    }

    public function add_link()
    {
        if (!$this->is_allowed_to_edit()) {
            $this->forbidden();
            return;
        }

        $link = new Link();
        $link->id = 0;
        $link->c_id = Request::get_c_id();
        $link->session_id = Request::get_session_id();
        /**
         * @todo: ensure session_id is correctly defaulted 
         */
        $action = $this->url(self::ACTION_ADD_LINK);
        $form = new LinkForm('link', 'post', $action);
        $form->init($link);

        if ($form->validate()) {
            $form->update_model();
            $repo = LinkRepository::instance();
            $success = $repo->save($link);

            $message = $success ? get_lang('LinkAdded') : get_lang('Error');

            $home = $this->url(self::ACTION_DEFAULT);
            Redirect::go($home);
        }

        global $interbreadcrumb;
        $interbreadcrumb[] = array('url' => '#', 'name' => get_lang('AddLink'));
        $data = (object) array();
        $data->form = $form;
        $this->render('edit_link', $data);
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
            $path = $file['tmp_name'];
            $c_id = Request::get_c_id();
            $session_id = Request::get_session_id();
            $import = new ImportCsv($c_id, $session_id, $path);
            $import->run();
            //import_csvfile();
            $home = $this->url(self::ACTION_DEFAULT);
            Redirect::go($home);
        }

        $data = (object) array();
        $data->form = $form;
        $this->render('edit_link', $data);
    }

    public function export_csv()
    {
        $c_id = Request::get_c_id();
        $session_id = Request::get_session_id();

        $root = (object) array();
        $root->c_id = $c_id;
        $root->id = 0;
        $root->session_id = $session_id;
        $links = LinkRepository::instance()->find_by_category($root);

        $repo = LinkCategory::repository();
        $categories = $repo->find_by_course($c_id, $session_id);

        $temp = Chamilo::temp_file();
        $writer = \CsvWriter::create(new \FileWriter($temp));
        $headers = array();
        $headers[] = 'url';
        $headers[] = 'title';
        $headers[] = 'description';
        $headers[] = 'target';
        $headers[] = 'category_title';
        $headers[] = 'category_description';
        $writer->put($headers);
        foreach ($links as $link) {
            $data = array();
            $data[] = $link->url;
            $data[] = $link->title;
            $data[] = $link->description;
            $data[] = $link->target;
            $data[] = '';
            $data[] = '';
            $writer->put($data);
        }
        foreach ($categories as $category) {
            foreach ($category->links as $link) {
                $data = array();
                $data[] = $link->url;
                $data[] = $link->title;
                $data[] = $link->description;
                $data[] = $link->target;
                $data[] = $category->category_title;
                $data[] = $category->description;
                $writer->put($data);
            }
        }
        
		\DocumentManager :: file_send_for_download($temp, true, get_lang('Links').'.csv');
    }

    public function delete_link()
    {
        if (!$this->is_allowed_to_edit()) {
            $this->forbidden();
            return;
        }

        /**
         * See AjaxController 
         */
        $this->missing();
    }

    public function edit_link()
    {
        if (!$this->is_allowed_to_edit()) {
            $this->forbidden();
            return;
        }

        $id = Request::get_id();
        $c_id = Request::get_c_id();

        $repo = LinkRepository::instance();
        $link = $repo->find_one_by_id($c_id, $id);

        $action = $this->url(self::ACTION_EDIT_LINK);
        $form = new LinkForm('link', 'post', $action);
        $form->init($link);

        if ($form->validate()) {
            $form->update_model();
            $success = $repo->save($link);

            $message = $success ? get_lang('LinkUpdated') : get_lang('Error');

            $home = $this->url(self::ACTION_DEFAULT);
            Redirect::go($home);
        }

        global $interbreadcrumb;
        $interbreadcrumb[] = array('url' => '#', 'name' => get_lang('EditLink'));
        $data = (object) array();
        $data->form = $form;
        $this->render('edit_link', $data);
    }

    public function make_visible()
    {
        if (!$this->is_allowed_to_edit()) {
            $this->forbidden();
            return;
        }

        /**
         * See AjaxController 
         */
        $this->missing();
    }

    public function make_invisible()
    {
        if (!$this->is_allowed_to_edit()) {
            $this->forbidden();
            return;
        }

        /**
         * See AjaxController 
         */
        $this->missing();
    }

    public function add_category()
    {
        if (!$this->is_allowed_to_edit()) {
            $this->forbidden();
            return;
        }

        $category = (object) array();
        $category->id = 0;
        $category->c_id = Request::get_c_id();
        $category->session_id = Request::get_session_id();
        $category->category_title = '';
        $category->description = '';
        $category->display_order = 0;

        $action = $this->url(self::ACTION_ADD_CATEGORY);
        $form = new CategoryForm('category', 'post', $action);
        $form->init($category);

        if ($form->validate()) {
            $form->update_model();
            $repo = LinkCategoryRepository::instance();
            $success = $repo->save($category);

            $message = $success ? get_lang('CategoryAdded') : get_lang('Error');

            $home = $this->url(self::ACTION_DEFAULT);
            Redirect::go($home);
        }

        global $interbreadcrumb;
        $interbreadcrumb[] = array('url' => '#', 'name' => get_lang('AddCategory'));

        $data = (object) array();
        $data->form = $form;
        $this->render('edit_category', $data);
    }

    public function edit_category()
    {
        if (!$this->is_allowed_to_edit()) {
            $this->forbidden();
            return;
        }
        $c_id = Request::get_c_id();
        $id = Request::get_id();

        $repo = LinkCategoryRepository::instance();
        $category = $repo->find_one_by_id($c_id, $id);

        $action = $this->url(self::ACTION_EDIT_CATEGORY);
        $form = new CategoryForm('category', 'post', $action);
        $form->init($category);

        if ($form->validate()) {
            $form->update_model();
            $repo = LinkCategoryRepository::instance();
            $success = $repo->save($category);

            $message = $success ? get_lang('CategorySaved') : get_lang('Error');

            $home = $this->url(self::ACTION_DEFAULT);
            Redirect::go($home);
        }

        global $interbreadcrumb;
        $interbreadcrumb[] = array('url' => '#', 'name' => get_lang('EditCategory'));
        $data = (object) array();
        $data->form = $form;
        $this->render('edit_category', $data);
    }

    public function delete_category()
    {
        if (!$this->is_allowed_to_edit()) {
            $this->forbidden();
            return;
        }

        $category = (object) array();
        $category->id = Request::get_id();
        $category->c_id = Request::get_c_id();

        $success = $repo = CategoryRepo::instance()->remove($category);
        $message = $success ? get_lang('CategoryRemoved') : get_lang('Error');

        $home = $this->url(self::ACTION_DEFAULT);
        Redirect::go($home);
    }

    public function go()
    {
        $id = Request::get_id();
        $c_id = Request::get_c_id();

        $repo = LinkRepository::instance();
        $link = $repo->find_one_by_id($c_id, $id);
        $url = $link->url;

        event_link($id);

        Header::cache_control('no-store, no-cache, must-revalidate');
        Header::pragma('no-cache');
        Redirect::go($url);
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

        $context = Uri::course_params();
        $data->root = Uri::url('/main/link/index.php', $context);

        $data->session_id = $session_id;
        $data->c_id = Request::get_c_id();
        $data->is_allowed_to_edit = $this->is_allowed_to_edit();
        parent::render("link/$template.tpl", $data);
    }

}
