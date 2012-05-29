<?php

use \ChamiloSession as Session;


/**
 * Wrapper for the current user - i.e. the logged in user. Provide access
 * to the current user's data.
 *
 * @license see /license.txt
 * @author Laurent Opprecht <laurent@opprecht.info> for the Univesity of Geneva
 */
class CurrentUser
{

    /**
     *
     * @return CurrentUser 
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
        ;
    }

    public function data()
    {
        global $_user;
        return $_user;
        //return Session::read('_user');
    }

    public function is_anonymous()
    {
        return api_is_anonymous();
    }

    public function first_name()
    {
        return $this->get('firstName');
    }

    public function last_name()
    {
        return $this->get('lastName');
    }

    public function email()
    {
        return $this->get('mail');
    }

    public function last_login()
    {
        return $this->get('lastLogin');
    }

    public function official_code()
    {
        return $this->get('official_code');
    }

    public function picture_uri()
    {
        return $this->get('picture_uri');
    }

    public function user_id()
    {
        return (int) $this->get('user_id');
    }

    public function language()
    {
        return $this->get('language');
    }

    public function auth_source()
    {
        return $this->get('auth_source');
    }

    public function theme()
    {
        return $this->get('theme');
    }

    /**
     * Returns true if user is a platform administrator, false otherwise.
     * 
     * @return boolean 
     * @see UserManager::is_admin(user_id) for user-id specific function.
     */
    public function is_platform_admin()
    {
        return (bool) Session::read('is_platformAdmin', false);
    }

    /**
     * Returns true if user is a session administrator, false otherwise.
     * 
     * @return boolean 
     */
    public function is_session_admin($allow_sessions_admins = false)
    {
        global $_user;
        return (bool) $_user['status'] == SESSIONADMIN;
    }

    /**
     * Returns true if the current user is allowed to create courses, false otherwise.
     * 
     * @return boolean 
     * false otherwise.
     */
    public function is_allowed_to_create_course()
    {
        return (bool) Session::read('is_allowedCreateCourse', false);
    }

    /**
     * Returns true if the current user is a course administrator for the current course, false otherwise.
     * 
     * @return boolean
     */
    public function is_course_admin()
    {
        return (bool) Session::read('is_courseAdmin', false);
    }

    /**
     * Returns true if the current user is a course member of the current course, false otherwise.
     * 
     * @return bool
     */
    public function is_course_member()
    {
        return (bool) Session::read('is_courseMember', false);
    }

    /**
     * Returns true if the current user is allowed in the current course, false otherwise.
     * 
     * @return bool
     */
    public function is_allowed_in_course()
    {
        return (bool) Session::read('is_allowed_in_course', false);
    }

    /**
     * Returns true if the current user is a course coach for the current course, false otherwise.
     * 
     * @return bool
     */
    public function is_course_coach()
    {
        return (bool) Session::read('is_courseCoach', false);
    }

    /**
     * Returns true if the  current user is a course tutor for the current course, false otherwise.
     * 
     * @return bool
     */
    public function is_course_tutor()
    {
        return (bool) Session::read('is_courseTutor', false);
    }

    public function get($name, $default = false)
    {
        $data = $this->data();
        return isset($data[$name]) ? $data[$name] : $default;
    }

}