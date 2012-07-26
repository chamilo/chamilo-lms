<?php

namespace Notebook;

use \Display;
use \Template;
use \FormValidator;
use \Security;
use \Uri;
use Header;

/**
 * Ajax controller. Dispatch request and perform required action.
 * 
 *      - delete one note
 *      - delete all notes in a course/session
 *      - returns a note from its id
 * 
 * Usage:
 * 
 *      $controller = AjaxController::instance();
 *      $controller->run();
 * 
 * @author Laurent Opprecht <laurent@opprecht.info> for the Univesity of Genevas
 * @license /license.txt
 */
class AjaxController extends \AjaxController
{

    const ACTION_REMOVE = 'remove';
    const ACTION_REMOVE_BY_COURSE = 'remove_by_course';
    const ACTION_FIND_BY_ID = 'find_by_id';

    /**
     * Return the instance of the controller.
     * 
     * @return  \Notebook\AjaxController
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
     * Prepare the environment. Set up breadcrumps and raise tracking event. 
     */
    protected function prolog()
    {
        event_access_tool(TOOL_NOTEBOOK);
    }        

    public function is_allowed_to_edit()
    {
        return $this->access()->can_edit();
    }

    /**
     * Remove/delete a Notebook entry
     */
    public function remove()
    {
        if (!$this->is_allowed_to_edit()) {
            $this->forbidden();
            return;
        }

        $item = Request::get_item_key();
        $success = Notebook::repository()->remove($item);
        $message = $success ? '' : get_lang('Error');

        $this->response($success, $message);
    }

    /**
     * Remove/delete all notebook entries belonging to a course.
     */
    public function remove_by_course()
    {
        if (!$this->is_allowed_to_edit()) {
            $this->forbidden();
            return;
        }

        $course = Request::get_course_key();
        $success = Notebook::repository()->remove_by_course($course);
        $message = $success ? '' : get_lang('Error');

        $this->response($success, $message);
    }

    public function find_by_id()
    {
        $c_id = Request::get_c_id();
        $id = Request::get_id();
        $item = Notebook::repository()->find_one_by_id($c_id, $id);
        $data = (object) array();
        if ($item) {
            $data->title = $item->title;
            $data->description = $item->description;
        }
        $this->response($success, '', $data);
    }

}