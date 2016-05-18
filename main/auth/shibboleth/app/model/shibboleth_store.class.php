<?php

namespace Shibboleth;

/**
 * Returns Shibboleth user's values based on Shibboleth's configuration.
 * Shibboleth returns not only whether a user is authenticated but returns as
 * well several paralemeter fields.
 * 
 * If a user is not authenticated nothing is returned.
 *
 * @license see /license.txt
 * @author Laurent Opprecht <laurent@opprecht.info>, Nicolas Rod for the University of Geneva
 */
class ShibbolethStore
{

    /**
     *
     * @return ShibbolethStore 
     */
    public static function instance()
    {
        static $result = false;
        if (empty($result))
        {
            $result = new self();
        }
        return $result;
    }

    /**
     *
     * @return ShibbolethConfig
     */
    public static function config()
    {
        return Shibboleth::config();
    }

    public function get_unique_id()
    {
        return $this->get(__FUNCTION__);
    }

    /**
     * If the user has more than one surname, it is possible depending of the user 
     * home organization that they are all given to the resource.
     * In the case of the University of Geneva, with two surnames, three different values
     * for the surname are sent. They are:
     * 1) "givenname1"
     * 2) "givenname2"
     * 3) "givenname1 givenname2"
     *    meaning the string is as follow: "givenname1;givenname2;givenname1 givenname2"
     *    
     * In such a case, the correct surname is the one which is followed by a space.
     * This function tests if such a situation is encountered, and returns the first given name.
     *
     * @author Nicolas Rod
     */
    public function get_firstname()
    {
        $result = $this->get(__FUNCTION__);

        if (!is_array($result))
        {
            $result = ucfirst($result);
            return $result;
        }
        foreach ($result as $name)
        {
            $parts = explode(' ', $name);

            if (count($parts) > 1)
            {
                $result = reset($parts);
                $result = ucfirst($result);
                return $result;
            }
        }
        $result = reset($result);
        $result = ucfirst($result);
        return $result;
    }

    public function get_lastname()
    {
        $result = $this->get(__FUNCTION__);
        $result = ucfirst($result);
        return $result;
    }

    public function get_email()
    {
        return $this->get(__FUNCTION__);
    }

    public function get_language()
    {
        return $this->get(__FUNCTION__);
    }

    public function get_gender()
    {
        return $this->get(__FUNCTION__);
    }

    public function get_address()
    {
        return $this->get(__FUNCTION__);
    }

    public function get_staff_category()
    {
        return $this->get(__FUNCTION__);
    }

    public function get_home_organization_type()
    {
        return $this->get(__FUNCTION__);
    }

    public function get_home_organization()
    {
        return $this->get(__FUNCTION__);
    }

    public function get_affiliation()
    {
        return $this->get(__FUNCTION__);
    }

    /**
     * @return ShibbolethUser 
     */
    public function get_user()
    {
        $result = new ShibbolethUser();
        foreach ($result as $key => $val)
        {
            $f = array($this, "get_$key");
            if (is_callable($f))
            {
                $result->{$key} = call_user_func($f);
            }
        }
        return $result;
    }

    /**
     * Returns the shibboleth value stored in $_SERVER if it exists or $default if it is not the case.
     *
     * @param string $name the generic name. I.e. one of the class const.
     * @param string $default default value if it is not provided by Shibboleth
     * @return string
     */
    public function get($name = '', $default = '')
    {
        $config = (array) Shibboleth::config();
        if ($name)
        {
            $name = str_replace('get_', '', $name);
            $shib_name = isset($config[$name]) ? $config[$name] : '';
            if ($shib_name)
            {
                $result = isset($_SERVER[$shib_name]) ? $_SERVER[$shib_name] : $default;
                $result = explode(';', $result);
                if (empty($result))
                {
                    $result = $default;
                }
                else if (count($result) == 1)
                {
                    $result = reset($result);
                }
                else
                {
                    $result = $result;
                }
                return $result;
            }
        }

        $result = array();
        foreach ($config as $key => $val)
        {
            $f = array($this, "get_$key");
            if (is_callable($f))
            {
                $result[$key] = call_user_func($f);
            }
        }

        return $result;
    }

}