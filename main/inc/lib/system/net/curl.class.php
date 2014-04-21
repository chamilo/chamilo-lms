<?php

/**
 * 
 * @copyright (c) 2012 University of Geneva
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author Laurent Opprecht <laurent@opprecht.info>
 */
class Curl
{

    protected static $default_options = array();

    static function get_default_options($options = array())
    {
        if (empty(self::$default_options)) {
            self::$default_options[CURLOPT_HEADER] = false;
            self::$default_options[CURLOPT_RETURNTRANSFER] = true;
            self::$default_options[CURLOPT_SSL_VERIFYPEER] = false;
        }

        $result = self::$default_options;
        foreach ($options as $key => $value) {
            $result[$key] = $value;
        }
        return $result;
    }

    static function set_default_option($key, $value)
    {
        $options = $this->get_options(array($key => $value));
        self::$default_options = $options;
    }

    /**
     *
     * @param string $url
     * @param array $options
     * @return Curl
     */
    static function get($url, $options = array())
    {
        $options[CURLOPT_HTTPGET] = true;
        $result = new self($url, $options);
        return $result;
    }

    /**
     *
     * @param string $url
     * @param array $fields
     * @param array $options
     * @return Curl 
     */
    static function post($url, $fields, $options = array())
    {
        $options[CURLOPT_POST] = true;
        $options[CURLOPT_POSTFIELDS] = $fields;
        $result = new self($url, $options);
        return $result;
    }

    protected $url = '';
    protected $options = array();
    protected $content = '';
    protected $info = array();
    protected $error = '';
    protected $error_no = 0;

    function __construct($url, $options = array())
    {
        $this->url = $url;
        $this->options = self::get_default_options($options);
    }

    function url()
    {
        return $this->url;
    }

    function options()
    {
        return $this->options;
    }

    function execute()
    {
        $ch = curl_init();

        $options = $this->options;
        $options[CURLOPT_URL] = $this->url;
        curl_setopt_array($ch, $options);

        $this->content = curl_exec($ch);
        $this->error = curl_error($ch);
        $this->info = curl_getinfo($ch);
        $this->error_no = curl_errno($ch);

        curl_close($ch);

        return $this->content;
    }

    function content()
    {
        return $this->content;
    }

    /**
     * @return array|string
     */
    function info($key = false)
    {
        if ($key) {
            return isset($this->info[$key]) ? $this->info[$key] : false;
        } else {
            return $this->info;
        }
    }

    function error()
    {
        return $this->error;
    }

    function error_no()
    {
        return $this->error_no;
    }

}