<?php
/* For licensing terms, see /license.txt */

/**
 * Class defining the elements from an AICC Descriptor file.
 * Container for the aiccResource class that deals with elemens from AICC Descriptor file.
 *
 * @package chamilo.learnpath
 *
 * @author  Yannick Warnier <ywarnier@beeznest.org>
 * @license GNU/GPL
 */
class aiccResource
{
    public $identifier = '';
    public $title = '';
    public $description = '';
    public $developer_id = '';

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
                    // TODO: Implement this way of object creation.
                    break;
                case 'config': // Do the same as the default.
                default:
                    foreach ($params as $a => $value) {
                        switch ($a) {
                            case 'system_id':
                                $this->identifier = strtolower($value);
                                break;
                            case 'title':
                                $this->title = $value;
                                // no break - @todo check this, not sure the intention is to have description=title
                            case 'description':
                                $this->description = $value;
                                break;
                            case 'developer_id':
                                $this->developer_id = $value;
                                break;
                        }
                    }
            }
        }
    }
}
