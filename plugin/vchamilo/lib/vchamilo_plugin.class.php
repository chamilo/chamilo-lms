<?php


global $_configuration;
require_once 'bootlib.php';
require_once vchamilo_boot_api_get_path($_configuration) . 'plugin.class.php';

/**
 * Description of VChamilo
 *
 * @copyright (c) 2014 VF Consulting
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author Valery Fremaux <valery.fremaux@gmail.com>
 */
class VChamiloPlugin extends Plugin
{
    /**
     * VChamiloPlugin constructor.
     */
    public function __construct()
    {
        parent::__construct('1.2', 'Valery Fremaux, Julio Montoya');
    }

    /**
     *
     * @return VChamiloPlugin
     */
    public static function create()
    {
        static $result = null;
        return $result ? $result : $result = new self();
    }

    public function get_name()
    {
        return 'vchamilo';
    }

    /**
     * @param $pixname
     * @param int $size
     * @return string
     */
    public function pix_url($pixname, $size = 16)
    {
        global $_configuration;

        if (file_exists($_configuration['root_sys'].'/plugin/vchamilo/pix/'.$pixname.'.png')){
            return $_configuration['root_web'].'/plugin/vchamilo/pix/'.$pixname.'.png';
        }
        if (file_exists($_configuration['root_sys'].'/plugin/vchamilo/pix/'.$pixname.'.jpg')){
            return $_configuration['root_web'].'/plugin/vchamilo/pix/'.$pixname.'.jpg';
        }
        if (file_exists($_configuration['root_sys'].'/plugin/vchamilo/pix/'.$pixname.'.gif')){
            return $_configuration['root_web'].'/plugin/vchamilo/pix/'.$pixname.'.gif';
        }

        return $_configuration['root_web'].'/main/img/icons/'.$size.'/'.$pixname.'.png';
    }
}