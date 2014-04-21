<?php

namespace Notebook;

/**
 * Authorize current users to perform various actions.
 * 
 * @author Laurent Opprecht <laurent@opprecht.info> for the Univesity of Genevas
 * @license /license.txt
 */
class Access extends \Access
{

    /**
     * Return the instance .
     * 
     * @return  \Access
     */
    public static function instance()
    {
        static $result = null;
        if (empty($result)) {
            $result = new self();
        }
        return $result;
    }

    /**
     * Returns true if the user has the right to edit.
     * 
     * @return boolean 
     */
    public function can_edit()
    {
        if (Request::is_student_view()) {
            return false;
        }
        $session_id = Request::get_session_id();

        if ($session_id != 0 && api_is_allowed_to_session_edit(false, true) == false) {
            return false;
        }

        if (!api_is_allowed_to_edit()) {
            return false;
        }
        return true;
    }

    /**
     * Returns true if the current user has the right to view
     * 
     * @return boolean 
     */
    public function can_view()
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
    
    public function authorize()
    {
        if (!$this->can_view()) {
            return false;
        }

        $c_id = Request::get_c_id();
        if (empty($c_id)) {
            return false;
        }

        return true;
    }

}