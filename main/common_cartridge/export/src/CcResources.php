<?php
/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/cc_lib/cc_resources.php under GNU/GPL license */

class CcResources implements CcIResource
{
    public $identifier = null;
    public $type = null;
    public $dependency = [];
    public $identifierref = null;
    public $href = null;
    public $base = null;
    public $persiststate = null;
    public $metadata = [];
    public $filename = null;
    public $files = [];
    public $isempty = null;
    public $manifestroot = null;
    public $folder = null;
    public $instructoronly = false;

    private $throwonerror = true;

    public function __construct($manifest, $file, $folder = '', $throwonerror = true)
    {
        $this->throwonerror = $throwonerror;
        if (is_string($manifest)) {
            $this->folder = $folder;
            $this->processResource($manifest, $file, $folder);
            $this->manifestroot = $manifest;
        } elseif (is_object($manifest)) {
            $this->importResource($file, $manifest);
        }
    }

    /**
     * Add resource.
     *
     * @param string $fname
     * @param string $location
     */
    public function addResource($fname, $location = '')
    {
        $this->processResource($fname, $location, null);
    }

    /**
     * Import a resource.
     */
    public function importResource(DOMElement &$node, CcIManifest &$doc)
    {
        $searchstr = "//imscc:manifest[@identifier='".$doc->manifestID().
                     "']/imscc:resources/imscc:resource";
        $this->identifier = $this->getAttrValue($node, "identifier");
        $this->type = $this->getAttrValue($node, "type");
        $this->href = $this->getAttrValue($node, "href");
        $this->base = $this->getAttrValue($node, "base");
        $this->persiststate = null;
        $nodo = $doc->nodeList($searchstr."[@identifier='".
                              $this->identifier."']/metadata/@href");
        $this->metadata = $nodo->nodeValue;
        $this->filename = $this->href;
        $nlist = $doc->nodeList($searchstr."[@identifier='".
                              $this->identifier."']/imscc:file/@href");
        $this->files = [];
        foreach ($nlist as $file) {
            $this->files[] = $file->nodeValue;
        }
        $nlist = $doc->nodeList($searchstr."[@identifier='".
                              $this->identifier."']/imscc:dependency/@identifierref");
        $this->dependency = [];
        foreach ($nlist as $dependency) {
            $this->dependency[] = $dependency->nodeValue;
        }
        $this->isempty = false;
    }

    /**
     * Get a attribute value.
     *
     * @param DOMElement $nod
     * @param string     $name
     * @param string     $ns
     *
     * @return string
     */
    public function getAttrValue(&$nod, $name, $ns = null)
    {
        if (is_null($ns)) {
            return $nod->hasAttribute($name) ? $nod->getAttribute($name) : null;
        }

        return $nod->hasAttributeNS($ns, $name) ? $nod->getAttributeNS($ns, $name) : null;
    }

    /**
     * Process a resource.
     *
     * @param string $manifestroot
     * @param string $fname
     * @param string $folder
     */
    public function processResource($manifestroot, &$fname, $folder)
    {
        $file = empty($folder) ? $manifestroot.'/'.$fname : $manifestroot.'/'.$folder.'/'.$fname;

        if (!file_exists($file) && $this->throwonerror) {
            throw new Exception('The file doesnt exist!');
        }

        getDepFiles($manifestroot, $fname, $this->folder, $this->files);
        array_unshift($this->files, $folder.$fname);
        $this->initEmptyNew();
        $this->href = $folder.$fname;
        $this->identifierref = $folder.$fname;
        $this->filename = $fname;
        $this->isempty = false;
        $this->folder = $folder;
    }

    public function adjustPath($mroot, $fname)
    {
        $result = null;
        if (file_exists($fname->filename)) {
            $result = pathDiff($fname->filename, $mroot);
        } elseif (file_exists($mroot.$fname->filename) || file_exists($mroot.DIRECTORY_SEPARATOR.$fname->filename)) {
            $result = $fname->filename;
            toUrlPath($result);
            $result = trim($result, "/");
        }

        return $result;
    }

    public function initClean()
    {
        $this->identifier = null;
        $this->type = null;
        $this->href = null;
        $this->base = null;
        $this->metadata = [];
        $this->dependency = [];
        $this->identifierref = null;
        $this->persiststate = null;
        $this->filename = '';
        $this->files = [];
        $this->isempty = true;
    }

    public function initEmptyNew()
    {
        $this->identifier = CcHelpers::uuidgen('I_', '_R');
        $this->type = null;
        $this->href = null;
        $this->persiststate = null;
        $this->filename = null;
        $this->isempty = false;
        $this->identifierref = null;
    }

    public function getManifestroot()
    {
        return $this->manifestroot;
    }
}
