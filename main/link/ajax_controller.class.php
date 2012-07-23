<?php

namespace Link;

use \Model\Course;
use \CourseDescription;
use \CourseDescriptionRoutes;
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
 *      - hide/show link
 *      - sort links/categories
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

    const ACTION_DELETE_CATEGORY = 'delete_category';
    const ACTION_HIDE_LINK = 'hide_link';
    const ACTION_SHOW_LINK = 'show_link';
    const ACTION_DELETE_LINK = 'delete_link';
    const ACTION_DELETE_BY_COURSE = 'delete_by_course';
    const ACTION_SORT_CATEGORIES = 'sort_categories';
    const ACTION_SORT_LINKS = 'sort_links';
    const ACTION_VALIDATE_LINK = 'validate_link';

    /**
     * Return the instance of the controller.
     * 
     * @return  \Link\AjaxController
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
        event_access_tool(TOOL_LINK);
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

    public function is_allowed_to_edit()
    {
        $session_id = Request::get_session_id();

        if ($session_id != 0 && api_is_allowed_to_session_edit(false, true) == false) {
            return false;
        }

//        if (!Security::check_token('get')) {
//            return false;
//        }

        if (!api_is_allowed_to_edit(false, true, true)) {
            return false;
        }
        return true;
    }

    /**
     * 
     */
    public function hide_link()
    {
        if (!$this->is_allowed_to_edit()) {
            $this->forbidden();
            return;
        }

        $c_id = Request::get_c_id();
        $id = Request::get_id();

        $success = LinkRepository::instance()->make_invisible($c_id, $id);

        $this->response($success);
    }

    /**
     * 
     */
    public function show_link()
    {
        if (!$this->is_allowed_to_edit()) {
            $this->forbidden();
            return;
        }

        $c_id = Request::get_c_id();
        $id = Request::get_id();

        $success = LinkRepository::instance()->make_visible($c_id, $id);

        $this->response($success);
    }

    /**
     * 
     */
    public function delete_link()
    {
        if (!$this->is_allowed_to_edit()) {
            $this->forbidden();
            return;
        }

        $link = (object) array();
        $link->c_id = Request::get_c_id();
        $link->id = Request::get_id();

        $success = LinkRepository::instance()->remove($link);

        $this->response($success);
    }

    /**
     * 
     */
    public function delete_category()
    {
        if (!$this->is_allowed_to_edit()) {
            $this->forbidden();
            return;
        }

        $category = (object) array();
        $category->c_id = Request::get_c_id();
        $category->id = Request::get_id();

        $success = LinkCategoryRepository::instance()->remove($category);

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
        $c_id = Request::get_c_id();
        $session_id = Request::get_session_id();

        $success_link = LinkRepository::instance()->remove_by_course($c_id, $session_id);
        $success_cat = LinkCategoryRepository::instance()->remove_by_course($c_id, $session_id);

        $this->response($success_link && $success_cat);
    }

    public function sort_categories()
    {
        if (!$this->is_allowed_to_edit()) {
            $this->forbidden();
            return;
        }

        $c_id = Request::get_c_id();
        $ids = Request::get_ids();
        if (empty($ids)) {
            return;
        }

        $repo = LinkCategoryRepository::instance();
        $success = $repo->order($c_id, $ids);

        $this->response($success);
    }

    public function sort_links()
    {
        if (!$this->is_allowed_to_edit()) {
            $this->forbidden();
            return;
        }

        $c_id = Request::get_c_id();
        $ids = Request::get_ids();
        if (empty($ids)) {
            return;
        }

        $repo = LinkRepository::instance();
        $success = $repo->order($c_id, $ids);

        $this->response($success);
    }


    public function validate_link()
    {
        $c_id = Request::get_c_id();
        $id = Request::get_id();

        $repo = LinkRepository::instance();
        $link = $repo->find_one_by_id($c_id, $id);
        $success = $link ? $link->validate() : false;
        
        $this->response($success);
    }

    function forbidden()
    {
        $this->response(false, get_lang('YouAreNotAuthorized'));
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
