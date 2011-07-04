<?php
/* For licensing terms, see /license.txt */

/**
 * Container for the scormResource class
 * @package chamilo.learnpath.scorm
 * @author	Yannick Warnier <ywarnier@beeznest.org>
 */

/**
 * Class defining the <resource> tag in an imsmanifest.xml file
 *
 */
class scormResource {
    public $identifier = '';
    public $type = 'webcontent';
    //public $identifierref = '';
    public $scormtype = 'sco'; // Fix problems with ENI content where asset is not defined.
    public $base = '';
    public $href = '';
    public $metadata;
    //public $file_href;
    //public $file_metadata;
    public $files = array();
    public $dependencies = array();

    /**
     * Class constructor. Depending of the type of construction called ('db' or 'manifest'), will create a scormResource
     * object from database records or from the DOM element given as parameter
     * @param	string	Type of construction needed ('db' or 'manifest', default = 'manifest')
     * @param	mixed	Depending on the type given, DB id for the lp_item or reference to the DOM element
     */
    public function __construct($type = 'manifest', &$element) {
        /*
        echo "<pre>Analysing resource:<br />\n";
        var_dump($element);
        echo "</pre><br />\n";
        */
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
                    if (is_array($children)) {
                        foreach ($children as $child) {
                            switch ($child->nodeType) {
                                case XML_ELEMENT_NODE:
                                    switch ($child->tagName) {
                                        case 'file':
                                            //echo "Child is a file tag<br />\n";
                                            $this->files[] = $child->getAttribute('href');
                                            break;
                                        case 'metadata':
                                            //echo "Child is a metadata tag<br />\n";
                                            $this->metadata = new scormMetadata('manifest', $child);
                                            break;
                                        case 'dependency':
                                            // Need to get identifierref attribute inside dependency node.
                                            // dependencies[] array represents all <dependency identifierref='x'> tags united.
                                            $this->dependencies[] = $child->getAttribute('identifierref');
                                            break;
                                    }
                                    break;
                            }
                        }
                    }
                    //$keep_href = '';
                    if ($element->hasAttributes()){ //in some cases we get here with an empty attributes array
                    // TODO: Find when and why we get such a case (empty array).
                        $attributes = $element->attributes;
                        foreach ($attributes as $attrib) {
                            switch ($attrib->name) {
                                case 'identifier':
                                    $this->identifier = $attrib->value;
                                    break;
                                case 'type':
                                    if (!empty($attrib->value)) {
                                        $this->type = $attrib->value;
                                    }
                                    break;
                                case 'scormtype':
                                    if (!empty($attrib->value)) {
                                        $this->scormtype = $attrib->value;
                                    }
                                    break;
                                case 'base':
                                    $this->base = $attrib->value;
                                    break;
                                case 'href':
                                    $this->href = $attrib->value;
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
     * Path getter
     * @return	string	Path for this resource
     */
    public function get_path() {
        if (!empty($this->href)) {
            require_once 'learnpath.class.php';
            return learnpath::escape_string($this->href);
        } else {
            return '';
        }
    }

    /**
     * Scorm type getter
     * @return	string	generally 'asset' or 'sco' as these are the only two values defined in SCORM 1.2
     */
    public function get_scorm_type() {
        if (!empty($this->scormtype)){
            require_once 'learnpath.class.php';
            return learnpath::escape_string($this->scormtype);
        } else {
            return '';
        }
    }
}
