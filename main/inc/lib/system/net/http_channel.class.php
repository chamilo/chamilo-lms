<?php

namespace net;

use Curl;

/**
 * Description of channel
 *
 * @copyright (c) 2012 University of Geneva
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author Laurent Opprecht <laurent@opprecht.info>
 */
class HttpChannel
{

    /**
     *
     * @param string $url
     * @param type $modules
     * @return HttpChannel 
     */
    static function create($url, $modules = array())
    {
        return new self($url, $modules);
    }

    protected $base_url = '';
    protected $modules = array();

    public function __construct($base_url = '', $modules = array())
    {
        $this->base_url = $base_url;
        $this->modules = $modules;
    }
    
    function modules()
    {
        return $this->modules;
    }

    function get($url, $parameters)
    {
        $options = $this->get_options();
        $url = $this->base_url . $url;
        return Curl::get($url, $options)->execute();
    }

    function post($url, $fields)
    {
        $options = $this->get_options();
        $url = $this->base_url . $url;
        return Curl::post($url, $fields, $options)->execute();
    }

    protected function get_options()
    {
        $result = array();
        $modules = $this->modules();
        foreach ($modules as $module) {
            if (is_array($module)) {
                $options = $module;
            } else {

                $options = $module->get_options();
            }
            $result = array_merge($result, $options);
        }
        return $result;
    }

}