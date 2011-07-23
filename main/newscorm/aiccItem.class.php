<?php
/* For licensing terms, see /license.txt */

/**
 * Container for the aiccItem class that deals with AICC Assignable Units (AUs)
 * @package	chamilo.learnpath
 * @author	Yannick Warnier	<ywarnier@beeznest.org>
 * @license	GNU/GPL
 */
/**
 * Code
 */
require_once 'learnpathItem.class.php';
/**
 * This class handles the elements from an AICC Descriptor file.
 * @package	chamilo.learnpath
 */
class aiccItem extends learnpathItem {
    public $identifier = ''; // AICC AU's system_id
    public $identifierref = '';
    public $parameters = ''; // AICC AU's web_launch
    public $title = ''; // no AICC equivalent
    public $sub_items = array(); // AICC elements (des)
    //public $prerequisites = ''; // defined in learnpathItem.class.php
    //public $max_score = ''; // defined in learnpathItem
    //public $path = ''; // defined in learnpathItem
    public $maxtimeallowed = '00:00:00'; // AICC AU's max_time_allowed
    public $timelimitaction = ''; // AICC AU's time_limit_action
    public $masteryscore = ''; // AICC AU's mastery_score
    public $core_vendor = ''; // AICC AU's core_vendor
    public $system_vendor = ''; // AICC AU's system_vendor
    public $au_type = ''; // AICC AU's type
    public $command_line = ''; // AICC AU's command_line
    public $debug = 0;

    /**
     * Class constructor. Depending of the type of construction called ('db' or 'manifest'), will create a scormItem
     * object from database records or from the array given as second parameter
     * @param	string	Type of construction needed ('db' or 'config', default = 'config')
     * @param	mixed	Depending on the type given, DB id for the lp_item or parameters array
     */
    public function aiccItem($type = 'config', $params, $course_db = '') {
        if (isset($params)) {
            switch ($type) {
                case 'db':
                    parent::__construct($params,api_get_user_id(), $course_db);
                    $this->aicc_contact = false;
                    //TODO: Implement this way of metadata object creation.
                    return false;
                case 'config': // Do the same as the default.
                default:
                     //if($first_item->type == XML_ELEMENT_NODE) this is already check prior to the call to this function
                     foreach ($params as $a => $value) {
                         switch ($a) {
                            case 'system_id':
                                $this->identifier = Database::escape_string(strtolower($value));
                                break;
                            case 'type':
                                $this->au_type = Database::escape_string($value);
                                break;
                            case 'command_line':
                                $this->command_line = Database::escape_string($value);
                                break;
                            case 'max_time_allowed':
                                $this->maxtimeallowed = Database::escape_string($value);
                                break;
                            case 'time_limit_action':
                                $this->timelimitaction = Database::escape_string($value);
                                break;
                            case 'max_score':
                                $this->max_score = Database::escape_string($value);
                                break;
                            case 'core_vendor':
                                $this->core_vendor = Database::escape_string($value);
                                break;
                            case 'system_vendor':
                                $this->system_vendor = Database::escape_string($value);
                                break;
                            case 'file_name':
                                $this->path = Database::escape_string($value);
                                break;
                            case 'mastery_score':
                                $this->masteryscore = Database::escape_string($value);
                                break;
                            case 'web_launch':
                                $this->parameters = Database::escape_string($value);
                                break;
                         }
                     }
                    return true;
            }
        }
        return false;
    }

    /**
     * Builds a flat list with the current item and calls itself recursively on all children
     * @param	array	Reference to the array to complete with the current item
     * @param	integer	Optional absolute order (pointer) of the item in this learning path
     * @param	integer	Optional relative order of the item at this level
     * @param	integer	Optional level. If not given, assumes it's level 0
     */
    function get_flat_list(&$list, &$abs_order, $rel_order = 1, $level = 0) {
        $list[] = array(
            'au_type' => $this->au_type,
            'command_line' => $this->command_line,
            'core_vendor' => $this->core_vendor,
            'identifier' => $this->identifier,
            'identifierref' => $this->identifierref,
            'masteryscore' => $this->masteryscore,
            'maxtimeallowed' => $this->maxtimeallowed,
            'level' => $level,
            'parameters' => $this->parameters,
            'prerequisites' => (!empty($this->prereq_string) ? $this->prereq_string : ''),
            'timelimitaction' => $this->timelimitaction,
        );
        $abs_order++;
        $i = 1;
        foreach ($this->sub_items as $id => $dummy) {
            $oSubitem =& $this->sub_items[$id];
            $oSubitem->get_flat_list($list, $abs_order, $i, $level + 1);
            $i++;
        }
    }

    /**
     * Save function. Uses the parent save function and adds a layer for AICC.
     * @param	boolean	Save from URL params (1) or from object attributes (0)
     */
    function save($from_outside = true, $prereqs_complete = false) {
        parent::save($from_outside, $prereqs_complete = false);
        // Under certain conditions, the scorm_contact should not be set, because no scorm signal was sent.
        $this->aicc_contact = true;
        if (!$this->aicc_contact) {
            //error_log('New LP - was expecting SCORM message but none received', 0);
        }
    }
}
