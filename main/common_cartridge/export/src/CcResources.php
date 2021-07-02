<?php
/* For licensing terms, see /license.txt */

class CcResources implements CcIResource
{
    public $identifier     = null;
    public $type           = null;
    public $dependency     = array();
    public $identifierref  = null;
    public $href           = null;
    public $base           = null;
    public $persiststate   = null;
    public $metadata       = array();
    public $filename       = null;
    public $files          = array();
    public $isempty        = null;
    public $manifestroot   = null;
    public $folder         = null;
    public $instructoronly = false;

    private $throwonerror   = true;

    public function __construct($manifest, $file, $folder='', $throwonerror = true) {
        $this->throwonerror = $throwonerror;
        if (is_string($manifest)) {
            $this->folder = $folder;
            $this->process_resource($manifest, $file, $folder);
            $this->manifestroot = $manifest;
        } else if (is_object($manifest)) {
            $this->import_resource($file, $manifest);
        }
    }

    /**
     * Add resource
     *
     * @param string $fname
     * @param string $location
     */
    public function add_resource ($fname, $location ='') {
        $this->process_resource($fname, $location, null);
    }

    /**
     * Import a resource
     *
     * @param DOMElement $node
     * @param CcIManifest $doc
     */
    public function import_resource(DOMElement &$node, CcIManifest &$doc) {

        $searchstr = "//imscc:manifest[@identifier='".$doc->manifestID().
                     "']/imscc:resources/imscc:resource";
        $this->identifier   = $this->get_attr_value($node, "identifier");
        $this->type         = $this->get_attr_value($node, "type");
        $this->href         = $this->get_attr_value($node, "href");
        $this->base         = $this->get_attr_value($node, "base");
        $this->persiststate = null;
        $nodo               = $doc->nodeList($searchstr."[@identifier='".
                              $this->identifier."']/metadata/@href");
        $this->metadata     = $nodo->nodeValue;
        $this->filename     = $this->href;
        $nlist              = $doc->nodeList($searchstr."[@identifier='".
                              $this->identifier."']/imscc:file/@href");
        $this->files        = array();
        foreach ($nlist as $file) {
            $this->files[]  = $file->nodeValue;
        }
        $nlist              = $doc->nodeList($searchstr."[@identifier='".
                              $this->identifier."']/imscc:dependency/@identifierref");
        $this->dependency   = array();
        foreach ($nlist as $dependency) {
            $this->dependency[]  = $dependency->nodeValue;
        }
        $this->isempty      = false;
    }

    /**
     * Get a attribute value
     *
     * @param DOMElement $nod
     * @param string $name
     * @param string $ns
     * @return string
     */
    public function get_attr_value(&$nod, $name, $ns=null) {
        if (is_null($ns)) {
            return ($nod->hasAttribute($name) ? $nod->getAttribute($name) : null);
        }
        return ($nod->hasAttributeNS($ns, $name) ? $nod->getAttributeNS($ns, $name) : null);
    }

    /**
     * Process a resource
     *
     * @param string $manifestroot
     * @param string $fname
     * @param string $folder
     */
    public function process_resource($manifestroot, &$fname, $folder) {
        $file = empty($folder) ? $manifestroot.'/'.$fname : $manifestroot.'/'.$folder.'/'.$fname;

        if (!file_exists($file) && $this->throwonerror) {
            throw new Exception('The file doesnt exist!');
        }

        getDepFiles($manifestroot, $fname, $this->folder, $this->files);
        array_unshift($this->files, $folder.$fname);
        $this->init_empty_new();
        $this->href             = $folder.$fname;
        $this->identifierref    = $folder.$fname;
        $this->filename         = $fname;
        $this->isempty          = false;
        $this->folder           = $folder;
    }

    public function adjust_path($mroot, $fname) {
        $result = null;
        if (file_exists($fname->filename)) {
            $result = pathDiff($fname->filename, $mroot);

        } else if (file_exists($mroot.$fname->filename) || file_exists($mroot.DIRECTORY_SEPARATOR.$fname->filename)) {
            $result = $fname->filename;
            toUrlPath($result);
            $result = trim($result, "/");
        }
        return $result;
    }

    public function init_clean() {
        $this->identifier    = null;
        $this->type          = null;
        $this->href          = null;
        $this->base          = null;
        $this->metadata      = array();
        $this->dependency    = array();
        $this->identifierref = null;
        $this->persiststate  = null;
        $this->filename      = '';
        $this->files         = array();
        $this->isempty       = true;
    }

    public function init_empty_new() {
        $this->identifier    = CcHelpers::uuidgen('I_', '_R');
        $this->type          = null;
        $this->href          = null;
        $this->persiststate  = null;
        $this->filename      = null;
        $this->isempty       = false;
        $this->identifierref = null;
    }

    public function get_manifestroot() {
        return $this->manifestroot;
    }
}

