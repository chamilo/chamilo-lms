<?php

/* For licensing terms, see /license.txt */

/**
 * Description of Tour
 * 
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 * @package chamilo.plugin.tour
 */
class Tour extends Plugin
{

    /**
     * Class constructor
     */
    protected function __construct()
    {
        $parameters = array(
            'show_tour' => 'boolean'
        );

        parent::__construct('1.0', 'Angel Fernando Quiroz Campos', $parameters);
    }

    /**
     * Instance the plugin
     * @staticvar null $result
     * @return type
     */
    static function create()
    {
        static $result = null;

        return $result ? $result : $result = new self();
    }

    public function install()
    {
        
    }

    public function uninstall()
    {
        
    }

}
