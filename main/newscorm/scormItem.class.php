<?php
/* For licensing terms, see /license.txt */

/**
 * Container for the scormItem class that deals with <item> elements in an imsmanifest file
 * @package	chamilo.learnpath.scorm
 * @author	Yannick Warnier	<ywarnier@beeznest.org>
 */

/**
 * This class handles the <item> elements from an imsmanifest file.
 */
require_once 'learnpathItem.class.php';
class scormItem extends learnpathItem {
    public $identifier = '';
    public $identifierref = '';
    public $isvisible = '';
    public $parameters = '';
    public $title = '';
    public $sub_items = array();
    public $metadata;
    //public $prerequisites = ''; - defined in learnpathItem.class.php
    // Modified by Ivan Tcholakov, 06-FEB-2010.
    //public $max_time_allowed = ''; //should be something like HHHH:MM:SS.SS
    public $max_time_allowed = '00:00:00';
    //
    public $timelimitaction = '';
    public $datafromlms = '';
    public $mastery_score = '';
    public $scorm_contact;

    /**
     * Class constructor. Depending of the type of construction called ('db' or 'manifest'), will create a scormItem
     * object from database records or from the DOM element given as parameter
     * @param	string	Type of construction needed ('db' or 'manifest', default = 'manifest')
     * @param	mixed	Depending on the type given, DB id for the lp_item or reference to the DOM element
     */
    public function __construct($type = 'manifest', &$element, $course_id = '') {
        if (isset($element)) {

            // Parsing using PHP5 DOMXML methods.

            switch ($type) {
                case 'db':
                    parent::__construct($element,api_get_user_id(), $course_id);
                    $this->scorm_contact = false;
                    // TODO: Implement this way of metadata object creation.
                    return false;
                case 'manifest': // Do the same as the default.
                default:
                     //if ($first_item->type == XML_ELEMENT_NODE) this is already check prior to the call to this function.
                     $children = $element->childNodes;
                    foreach ($children as $child) {
                         switch ($child->nodeType) {
                             case XML_ELEMENT_NODE:
                                switch ($child->tagName) {
                                     case 'title':
                                         $tmp_children = $child->childNodes;
                                         if ($tmp_children->length == 1 && $child->firstChild->nodeValue != '') {
                                             $this->title = $child->firstChild->nodeValue;
                                         }
                                         break;
                                     case 'max_score':
                                         if ($tmp_children->length == 1 && $child->firstChild->nodeValue != '') {
                                             $this->max_score = $child->firstChild->nodeValue;
                                         }
                                         break;
                                     case 'maxtimeallowed':
                                     case 'adlcp:maxtimeallowed':
                                         $tmp_children = $child->childNodes;
                                         if ($tmp_children->length == 1 && $child->firstChild->nodeValue != '') {
                                             $this->max_time_allowed = $child->firstChild->nodeValue;
                                         }
                                         break;
                                    case 'prerequisites':
                                    case 'adlcp:prerequisites':
                                         $tmp_children = $child->childNodes;
                                         if ($tmp_children->length == 1 && $child->firstChild->nodeValue != '') {
                                             $this->prereq_string = $child->firstChild->nodeValue;
                                         }
                                         break;
                                    case 'timelimitaction':
                                    case 'adlcp:timelimitaction':
                                         $tmp_children = $child->childNodes;
                                         if ($tmp_children->length == 1 && $child->firstChild->nodeValue != '') {
                                             $this->timelimitaction = $child->firstChild->nodeValue;
                                         }
                                         break;
                                    case 'datafromlms':
                                    case 'adlcp:datafromlms':
                                    case 'adlcp:launchdata': //in some cases (Wouters)
                                         $tmp_children = $child->childNodes;
                                         if ($tmp_children->length == 1 && $child->firstChild->nodeValue != '') {
                                             $this->datafromlms = $child->firstChild->nodeValue;
                                         }
                                         break;
                                    case 'masteryscore':
                                    case 'adlcp:masteryscore':
                                         $tmp_children = $child->childNodes;
                                         if ($tmp_children->length == 1 && $child->firstChild->nodeValue != '') {
                                             $this->mastery_score = $child->firstChild->nodeValue;
                                         }
                                         break;
                                     case 'item':
                                         $oItem = new scormItem('manifest',$child);
                                         if ($oItem->identifier != '') {
                                             $this->sub_items[$oItem->identifier] = $oItem;
                                         }
                                        break;
                                     case 'metadata':
                                         $this->metadata = new scormMetadata('manifest', $child);
                                         break;
                                 }
                                 break;
                             case XML_TEXT_NODE:
                                 // This case is actually treated by looking into ELEMENT_NODEs above.
                                 break;
                         }
                     }
                     if ($element->hasAttributes()) {
                         $attributes = $element->attributes;
                         //$keep_href = '';
                         foreach ($attributes as $attrib) {
                             switch($attrib->name){
                                 case 'identifier':
                                     $this->identifier = $attrib->value;
                                     break;
                                 case 'identifierref':
                                     $this->identifierref = $attrib->value;
                                     break;
                                 case 'isvisible':
                                     $this->isvisible = $attrib->value;
                                     break;
                                 case 'parameters':
                                     $this->parameters = $attrib->value;
                                     break;
                             }
                         }
                     }
                    return true;
            }

            // End parsing using PHP5 DOMXML methods.

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
    public function get_flat_list(&$list, &$abs_order, $rel_order = 1, $level = 0) {
        $list[] = array(
            'abs_order' => $abs_order,
            'datafromlms' => $this->datafromlms,
            'identifier' => $this->identifier,
            'identifierref' => $this->identifierref,
            'isvisible' => $this->isvisible,
            'level' => $level,
            'masteryscore' => $this->mastery_score,
            'maxtimeallowed' => $this->max_time_allowed,
            'metadata' => $this->metadata,
            'parameters' => $this->parameters,
            'prerequisites' => (!empty($this->prereq_string) ? $this->prereq_string : ''),
            'rel_order' => $rel_order,
            'timelimitaction' => $this->timelimitaction,
            'title' => $this->title,
            'max_score' => $this->max_score
        );
        $abs_order++;
        $i = 1;
        foreach($this->sub_items as $id => $dummy) {
            $oSubitem =& $this->sub_items[$id];
            $oSubitem->get_flat_list($list, $abs_order, $i, $level + 1);
            $i++;
        }
    }

    /**
     * Save function. Uses the parent save function and adds a layer for SCORM.
     * @param	boolean	Save from URL params (1) or from object attributes (0)
     */
    public function save($from_outside = true, $prereqs_complete = false) {
        parent::save($from_outside, $prereqs_complete);
        // Under certain conditions, the scorm_contact should not be set, because no scorm signal was sent.
        $this->scorm_contact = true;
        if (!$this->scorm_contact){
            //error_log('New LP - was expecting SCORM message but none received', 0);
        }
    }
}
