<?php
/* For licensing terms, see /license.txt */

/**
 * Container for the scormOrganization class
 * @package chamilo.learnpath.scorm
 * @author	Yannick Warnier <ywarnier@beeznest.org>
 */

/**
 * Class defining the <organization> tag in an imsmanifest.xml file
 */
class scormOrganization {
    public $identifier = '';
    public $structure = '';
    public $title = '';
    public $items = array();
    public $metadata;

    /**
     * Class constructor. Depending of the type of construction called ('db' or 'manifest'), will create a scormOrganization
     * object from database records or from the DOM element given as parameter
     * @param	string	Type of construction needed ('db' or 'manifest', default = 'manifest')
     * @param	mixed	Depending on the type given, DB id for the lp_item or reference to the DOM element
     */
    public function __construct($type = 'manifest', &$element, $scorm_charset = 'UTF-8') {
        if (isset($element)) {

            // Parsing using PHP5 DOMXML methods.

            switch ($type) {
                case 'db':
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
                                     case 'item':
                                         $oItem = new scormItem('manifest', $child);
                                         if ($oItem->identifier != '') {
                                            $this->items[$oItem->identifier] = $oItem;
                                         }
                                        break;
                                     case 'metadata':
                                         $this->metadata = new scormMetadata('manifest', $child);
                                         break;
                                     case 'title':
                                         $tmp_children = $child->childNodes;
                                         if ($tmp_children->length == 1 && $child->firstChild->nodeValue != '') {
                                             $this->title = api_utf8_decode(api_html_entity_decode($child->firstChild->nodeValue, ENT_QUOTES, 'UTF-8'));
                                         }
                                         break;
                                 }
                                 break;
                             case XML_TEXT_NODE:
                                 break;
                         }
                     }
                    if ($element->hasAttributes()) {
                         $attributes = $element->attributes;
                         //$keep_href = '';
                         foreach ($attributes as $attrib) {
                             switch ($attrib->name) {
                                 case 'identifier':
                                     $this->identifier = $attrib->value;
                                     break;
                                 case 'structure':
                                     $this->structure = $attrib->value;
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
     * Get a flat list of items in the organization
     * @return	array	Array containing an ordered list of all items with their level and all information related to each item
     */
    public function get_flat_items_list() {
        $list = array();
        $i = 1;
        foreach ($this->items as $id => $dummy) {
            $abs_order = 0;
            $this->items[$id]->get_flat_list($list,$abs_order, $i, 0); // Passes the array as a pointer so it is modified in $list directly.
            $i++;
        }
        return $list;
    }

    /**
     * Name getter
     * @return	string	Name or empty string
     */
    public function get_name() {
        if (!empty($this->title)) {
            return Database::escape_string($this->title);
        } else {
            return '';
        }
    }

    /**
     * Reference identifier getter
     * @return	string	Identifier or empty string
     */
    public function get_ref() {
        if (!empty($this->identifier)) {
            return Database::escape_string($this->identifier);
        } else {
            return '';
        }
    }

    /**
     * Sets the title element
     * @param	string	New title to set
     */
    public function set_name($title) {
        if (!empty($title)) {
            $this->title = Database::escape_string($title);
        }
    }
}
