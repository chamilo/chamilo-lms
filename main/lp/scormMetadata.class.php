<?php
/* For licensing terms, see /license.txt */

/**
 * Container for the scormMetadata class, setup to hold information about the <metadata> element in imsmanifest files.
 */

/**
 * scormMetadata class, handling each <metadata> element found in an imsmanifest file.
 */
class scormMetadata
{
    public $lom = '';
    public $schema = '';
    public $schemaversion = '';
    public $location = '';
    public $text = '';
    public $attribs = [];

    /**
     * Class constructor. Works in two different ways defined by the first element, being 'db' or 'manifest'.
     * If 'db', then it is built using the information available in the Chamilo database. If 'manifest', then it
     * is built using the element given as a parameter, expecting it to be a <metadata> element pointer from the
     * DOM parser.
     *
     * @param	string	Type of creation required. Can be 'db' or 'manifest' (default)
     * @param	mixed	Depending on the type, can be the DB ID of the learnpath item or
     * the pointer to the <metadata> element in the imsmanifest.xml file
     */
    public function __construct($type, &$element)
    {
        if (isset($element)) {
            // Parsing using PHP5 DOMXML methods.
            switch ($type) {
                case 'db':
                    // TODO: Implement this way of metadata object creation.
                    break;
                    //break;
                case 'manifest': // Do the same as the default.
                    $children = $element->childNodes;
                    foreach ($children as $child) {
                        switch ($child->nodeType) {
                            case XML_ELEMENT_NODE:
                                // Could be 'lom', 'schema', 'schemaversion' or 'location'.
                                switch ($child->tagName) {
                                    case 'lom':
                                        $childchildren = $child->childNodes;
                                        foreach ($childchildren as $childchild) {
                                            $this->lom = $childchild->nodeValue;
                                        }
                                        break;
                                    case 'schema':
                                        $childchildren = $child->childNodes;
                                        foreach ($childchildren as $childchild) {
                                            // There is generally only one child here.
                                            $this->schema = $childchild->nodeValue;
                                        }
                                        break;
                                    case 'schemaversion':
                                        $childchildren = $child->childNodes;
                                        foreach ($childchildren as $childchild) {
                                            // There is generally only one child here.
                                            $this->schemaversion = $childchild->nodeValue;
                                        }
                                        break;
                                    case 'location':
                                        $childchildren = $child->childNodes;
                                        foreach ($childchildren as $childchild) {
                                            // There is generally only one child here.
                                            $this->location = $childchild->nodeValue;
                                        }
                                        break;
                                }
                                break;
                            case XML_TEXT_NODE:
                                if ('' != trim($child->textContent)) {
                                    if (count(1 == $children)) {
                                        // If this is the only child at this level and it is a content... save differently.
                                        $this->text = $child->textContent;
                                    } else {
                                        $this->text[$element->tagName] = $child->textContent;
                                    }
                                }
                                break;
                        }
                    }
                    $attributes = $element->attributes;
                    //$keep_href = '';
                    if (is_array($attributes)) {
                        foreach ($attributes as $attrib) {
                            if ('' != trim($attrib->value)) {
                                $this->attribs[$attrib->name] = $attrib->value;
                            }
                        }
                    }
                //break;
            }
            // End parsing using PHP5 DOMXML methods.
        }
    }
}
