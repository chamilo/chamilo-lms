<?php

/**
 * Authorize or deny calls. 
 * 
 * 
 * Note:
 * 
 * This class stores locally the security token so that the current call
 * can still be validated after generating the new token.
 * 
 * The new security token is generated only on first call. Successive calls
 * return the same token. This ensure that different parts of the application
 * (form, javascript for javascript, etc) can get access to the same token.
 * 
 * @author Laurent Opprecht <laurent@opprecht.info> for the Univesity of Geneva
 * @license /license.txt
 */
abstract class Access
{

    const SEC_TOKEN = 'sec_token';
    
    /**
     * Return view and edit access.
     * 
     * @return  \Access
     */
    public static function all()
    {
        return AccessAll::instance();
    }

    /**
     * Return no access.
     * 
     * @return  \Access
     */
    public static function forbidden()
    {
        AccessForbidden::instance();
    }

    protected $session_token;
    protected $token;

    /**
     * Returns true if security token is valid, false otherwise.
     * 
     * @return bool
     */
    public function is_token_valid()
    {
        $call_token = Request::get_security_token();
        if (empty($call_token)) {
            return false;
        }
        $session_token = $this->get_session_token();
        return $session_token == $call_token;
    }

    /**
     * Returns the token contained in the session. 
     * Stores the token for further reuse so that it can be changed in session.
     * 
     * @return string 
     */
    public function get_session_token()
    {
        if (empty($this->session_token)) {
            $key = self::SEC_TOKEN;
            $this->session_token = isset($_SESSION[$key]) ? $_SESSION[$key] : '';
        }
        return $this->session_token;
    }

    /*
     * On first call generate a new security token and save it in session.
     * On successful calls returns the same (new) token (function is repeatable).
     * If user do not have the right to edit, returns a blank (invalid) token.
     * 
     * Stores the existing session token before saving the new one so that 
     * the current call can still be validated after calling this function.
     */

    public function get_token()
    {
        if (!$this->can_edit()) {
            return '';
        }
        if ($this->token) {
            return $this->token;
        }
        $this->session_token = $this->get_session_token();


        $this->token = \Security::get_token();
    }

    /**
     * Returns true if the user has the right to edit.
     * 
     * @return boolean 
     */
    public abstract function can_edit();

    /**
     * Returns true if the current user has the right to view
     * 
     * @return boolean 
     */
    public abstract function can_view();

    public function authorize()
    {
        return $this->can_view();
    }

}

/**
 * Authorize access and view access. 
 */
class AccessAll extends Access
{

    /**
     * Return the instance.
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

    private function __construct()
    {
        
    }

    public function can_edit()
    {
        return true;
    }

    public function can_view()
    {
        return true;
    }

    public function authorize()
    {
        return true;
    }

}

/**
 * Authorizev view access only 
 */
class AccessForbidden extends Access
{

    /**
     * Return the instance.
     * 
     * @return  \AccessView
     */
    public static function instance()
    {
        static $result = null;
        if (empty($result)) {
            $result = new self();
        }
        return $result;
    }

    private function __construct()
    {
        
    }

    public function can_edit()
    {
        return false;
    }

    public function can_view()
    {
        return false;
    }

    public function authorize()
    {
        return false;
    }

}