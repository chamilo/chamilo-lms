<?php

/**
 * An access token. Can be passed between applications to grant access.
 * 
 * The token aggregate several values together (key id, api key, user id). This
 * is useful to pass a single value between application and avoid passing
 * each value as a separate parameter.
 * 
 * Note that values are aggregated but not crypted. An external application could
 * have access to individual components.
 * 
 * @see /main/auth/key_auth.class.php
 * @see table user_api_key
 * 
 * Usage:
 * 
 * Validate token:
 * 
 *      $data = Request::get('access_token');
 *      $token = AccessToken::parse($data); 
 *      $token->is_valid();
 * 
 * Pass token
 * 
 *      $token = new AccessToken(1, 1, '+*ç*%ç*ç');
 *      $url = '.....?access_token=' . $token;
 *
 * @license see /license.txt
 * @author Laurent Opprecht <laurent@opprecht.info> for the Univesity of Geneva
 */
class AccessToken
{

    static function empty_token()
    {
        static $result = null;
        if (empty($result)) {
            $result = new self(0, 0, '');
        }
        return $result;
    }

    /**
     *
     * @param type $string
     * @return AccessToken 
     */
    static function parse($string)
    {
        if (empty($string)) {
            return self::empty_token();
        }

        $data = base64_decode($string);
        $data = explode('/', $data);

        if (count($data) != 3) {
            return self::empty_token();
        }

        $id = $data[0];
        $user_id = $data[1];
        $key = $data[2];
        return new self($id, $user_id, $key);
    }
    
    /**
     *
     * @param int       $id
     * @param int       $user_id
     * @param string    $key 
     * @return AccessToken
     */
    static function create($id, $user_id, $key)
    {
        $is_valid = !empty($id) && !empty($user_id) && !empty($key);
        return $is_valid ? new self($id, $user_id, $key) : self::empty_token();
    }

    protected $id = 0;
    protected $user_id = 0;
    protected $key = '';

    /**
     *
     * @param int       $id
     * @param int       $user_id
     * @param string    $key 
     */
    function __construct($id, $user_id, $key)
    {
        $this->id = $id;
        $this->user_id = $user_id;
        $this->key = $key;
    }

    /**
     * The user_api_key id.
     * 
     * @return int
     */
    function get_id()
    {
        return $this->id;
    }

    /**
     * User id.
     * 
     * @return string
     */
    function get_user_id()
    {
        return $this->user_id;
    }

    /**
     * User api key.
     * 
     * @return string
     */
    function get_key()
    {
        return $this->key;
    }

    /**
     * True if the token is an empty token. I.e. a no access token.
     * 
     * @return bool
     */
    function is_empty()
    {
        return empty($this->id) || empty($this->user_id) || empty($this->key);
    }

    /**
     * Validate token against the database. Returns true if token is valid, 
     * false otherwise.
     * 
     * @return boolean 
     */
    function is_valid()
    {
        if ($this->is_empty()) {
            return false;
        }
        $key = UserApiKeyManager::get_by_id($this->id);
        if (empty($key)) {
            return false;
        }

        if ($key['api_key'] != $this->key) {
            return false;
        }

        if ($key['user_id'] != $this->user_id) {
            return false;
        }

        $time = time();
        $validity_start_date = $key['validity_start_date'] ? strtotime($key['validity_start_date']) : $time;
        $validity_end_date = $key['validity_end_date'] ? strtotime($key['validity_end_date']) : $time + 100000;
        return $validity_start_date <= $time && $time <= $validity_end_date;
    }

    /**
     * Returns a string representation of the token that can be passed in a url or a form.
     * The string representation can be parsed by calling AccessToken::parse();
     * 
     * @return string
     */
    function __toString()
    {
        $data[] = $this->id;
        $data[] = $this->user_id;
        $data[] = $this->key;

        $result = implode('/', $data);
        $result = base64_encode($result);
        return $result;
    }

}