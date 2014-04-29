<?php

/**
 * Controller for course notice. Displays activity of courses tools to which
 * the user is subscribed in RSS format.
 * 
 * Controller of CourseNotice.
 *
 * @license see /license.txt
 * @author Laurent Opprecht <laurent@opprecht.info> for the Univesity of Geneva
 */
class CourseNoticeController
{

    /**
     * @return CourseNoticeController 
     */
    public static function instance()
    {
        static $result = null;
        if(empty($result))
        {
            $result = new self();
        }
        return $result;
    }
    
    protected function __construct()
    {
        ;
    }

    /**
     * Name of the service for the API Key.
     * !! 10 chars max !!
     */
    public function get_service_name()
    {
        return 'chamilorss';
    }

    /**
     * Returns true if security accepts to run otherwise returns false.
     * 
     * @return boolean 
     */
    public function accept()
    {
        $user_id = $this->get_user_id();
        return ! empty($user_id);
    }

    /**
     * Returns the url used to access the rss for a specific user. 
     * Note that the url is protected by token
     * 
     * @return string
     */
    public function get_secret_url($action = 'display', $format = 'rss')
    {
        $user_id = $this->get_user_id();
        $token = $this->ensure_user_token();
        $params = array('user_id' => $user_id, 'token' => $token, 'format' => $format, 'action' => $action);
        return Uri::url('main/course_notice/index.php', $params);
    }

    /**
     * Returns the request user id
     * 
     * @return int 
     */
    public function get_user_id()
    {
        return api_get_user_id();
    }

    /**
     * Returns the format requested. Defaults to rss
     * 
     * @return string
     */
    public function get_format()
    {
        return Request::get('format', 'rss');
    }

    /**
     * Returns the action requested. Defaults to display
     * 
     * @return string
     */
    public function get_action()
    {
        return Request::get('action', 'display');
    }

    /**
     * Ensure a security token is available for the user and rss service- create one if needed.
     * 
     * @return string The security token
     */
    public function ensure_user_token()
    {
        $user_id = $this->get_user_id();
        $service = $this->service_name();
        $keys = UserManager::get_api_keys($user_id, $service);
        if (empty($keys))
        {
            UserManager::add_api_key($user_id, $service);
            $keys = UserManager::get_api_keys($user_id, $service);
        }
        return end($keys);
    }

    /**
     * Run the controller. Ensure security and execute requested action. 
     */
    public function run()
    {
        if (!$this->accept())
        {
            Display::display_header();
            Display::display_error_message(get_lang('NotAuthorized'));
            Display::display_footer();
            die;
        }

        $action = $this->get_action();
        $format = $this->get_format();
        $f = array($this, $action . '_' . $format);
        if (is_callable($f))
        {
            call_user_func($f);
        }
    }

    /**
     * Display rss action. Display the notfication Rss for the user.
     * Result is cached. It will refresh every hour. 
     */
    public function display_rss()
    {
        Header::content_type_xml();

        $rss = new CourseNoticeRss($this->get_user_id());
        $limit = (time() - 3600);
        if ($result = Cache::get($rss, $limit))
        {
            echo $result;
            exit;
        }

        $result = $rss->to_string();
        Cache::put($rss, $result);
        echo $result;
    }

}