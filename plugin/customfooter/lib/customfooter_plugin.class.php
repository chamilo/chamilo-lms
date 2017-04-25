<?php

/**
 * Description of Custom Footer
 *
 * @copyright (c) 2014 VF Consulting
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author Valery Fremaux <valery.fremaux@gmail.com>
 */
class CustomFooterPlugin extends Plugin {

    /**
     *
     * @return CustomFooterPlugin 
     */
    static function create() {
        static $result = null;
        return $result ? $result : $result = new self();
    }
    
    function get_name() {
        return 'customfooter';
    }

    protected function __construct() {
        parent::__construct('1.1', 'Valery Fremaux');
    }

	function pix_url($pixname, $size = 16) {
        global $_configuration;

        if (file_exists($_configuration['root_sys'].'/plugin/customplugin/pix/'.$pixname.'.png')) {
            return $_configuration['root_web'].'/plugin/customplugin/pix/'.$pixname.'.png';
        }
        if (file_exists($_configuration['root_sys'].'/plugin/customplugin/pix/'.$pixname.'.jpg')) {
            return $_configuration['root_web'].'/plugin/customplugin/pix/'.$pixname.'.jpg';
        }
        if (file_exists($_configuration['root_sys'].'/plugin/customplugin/pix/'.$pixname.'.gif')) {
            return $_configuration['root_web'].'/plugin/customplugin/pix/'.$pixname.'.gif';
        }
        return $_configuration['root_web'].'/main/img/icons/'.$size.'/'.$pixname.'.png';
    }
}