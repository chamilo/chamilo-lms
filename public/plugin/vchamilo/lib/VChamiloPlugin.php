<?php
/* For licensing terms, see /license.txt */

/**
 * Description of VChamilo.
 *
 * @copyright (c) 2014 VF Consulting
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author Valery Fremaux <valery.fremaux@gmail.com>
 * @author Julio Montoya
 */
class VChamiloPlugin extends Plugin
{
    /**
     * VChamiloPlugin constructor.
     */
    public function __construct()
    {
        parent::__construct('1.4', 'Valery Fremaux, Julio Montoya');
    }

    /**
     * @return VChamiloPlugin
     */
    public static function create()
    {
        static $result = null;

        return $result ? $result : $result = new self();
    }

    /**
     * @return string
     */
    public function get_name()
    {
        return 'vchamilo';
    }
}
