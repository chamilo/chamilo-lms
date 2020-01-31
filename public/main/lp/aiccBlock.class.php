<?php
/* For licensing terms, see /license.txt */

/**
 * Class aiccBlock
 * Class defining the Block elements in an AICC Course Structure file.
 *
 * Container for the aiccResource class that deals with elemens from AICC Course Structure file
 *
 * @author  Yannick Warnier <ywarnier@beeznest.org>
 * @license GNU/GPL
 */
class aiccBlock extends learnpathItem
{
    public $identifier = '';
    public $members = [];

    /**
     * Class constructor. Depending of the type of construction called ('db' or 'manifest'), will create a scormResource
     * object from database records or from the array given as second param.
     *
     * @param string $type   Type of construction needed ('db' or 'config', default = 'config')
     * @param mixed  $params Depending on the type given, DB id for the lp_item or parameters array
     */
    public function __construct($type = 'config', $params)
    {
        if (isset($params)) {
            switch ($type) {
                case 'db':
                    //TODO: Implement this way of object creation.
                    break;
                case 'config': // Do the same as the default.
                default:
                    foreach ($params as $a => $value) {
                        switch ($a) {
                            case 'system_id':
                                $this->identifier = strtolower($value);
                                break;
                            case 'member':
                                if (false !== strstr($value, ',')) {
                                    $temp = explode(',', $value);
                                    foreach ($temp as $val) {
                                        if (!empty($val)) {
                                            $this->members[] = $val;
                                        }
                                    }
                                }
                                break;
                        }
                    }
            }
        }
    }
}
