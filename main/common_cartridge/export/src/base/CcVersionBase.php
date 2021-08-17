<?php
/* For licensing terms, see /license.txt */


/**
 * Abstract Version Base class
 *
 */
abstract class CcVersionBase
{

    protected $_generator = null;
    protected $ccnamespaces = array();
    protected $isrootmanifest = false;
    protected $manifestID = null;
    protected $organizationid = null;
    public $resources = null;
    public $resources_ind = null;
    protected $metadata = null;
    public $organizations = null;
    protected $base = null;
    public $ccversion = null;
    public $camversion = null;


    abstract protected function on_create(DOMDocument &$doc, $rootmanifestnode = null, $nmanifestID = null);

    abstract protected function create_metadata_manifest(CcIMetadataManifest $met, DOMDocument &$doc, $xmlnode = null);

    abstract protected function create_metadata_resource(CcIMetadataResource $met, DOMDocument &$doc, $xmlnode = null);

    abstract protected function create_metadata_file(CcIMetadataFile $met, DOMDocument &$doc, $xmlnode = null);

    abstract protected function create_resource(CcIResource &$res, DOMDocument &$doc, $xmlnode=null);

    abstract protected function create_organization(CcIOrganization &$org, DOMDocument &$doc, $xmlnode=null);

    public function get_cc_namespaces()
    {
        return $this->ccnamespaces;
    }

    public function create_manifest(DOMDocument &$doc, $rootmanifestnode = null)
    {
        return $this->on_create($doc, $rootmanifestnode);
    }

    public function create_resource_node(CcIResource &$res, DOMDocument &$doc, $xmlnode = null)
    {
        return $this->create_resource($res, $doc, $xmlnode);
    }


    public function create_metadata_node(&$met, DOMDocument &$doc, $xmlnode = null)
    {
        return $this->create_metadata_manifest($met, $doc, $xmlnode);
    }

    public function create_metadata_resource_node(&$met, DOMDocument &$doc, $xmlnode = null)
    {
        return $this->create_metadata_resource($met, $doc, $xmlnode);
    }

    public function create_metadata_file_node(&$met, DOMDocument &$doc, $xmlnode = null)
    {
        return $this->create_metadata_file($met, $doc, $xmlnode);
    }

    public function create_organization_node(CcIOrganization &$org, DOMDocument &$doc, $xmlnode = null)
    {
        return $this->create_organization($org, $doc, $xmlnode);
    }

    public function manifestID()
    {
        return $this->manifestID;
    }

    public function set_manifestID($id)
    {
        $this->manifestID = $id;
    }

    public function get_base()
    {
        return $this->base;
    }

    public function set_base($baseval)
    {
        $this->base = $baseval;
    }

    public function import_resources(DOMElement &$node, CcIManifest &$doc)
    {
        if (is_null($this->resources)) {
            $this->resources = array();
        }
        $nlist = $node->getElementsByTagNameNS($this->ccnamespaces['imscc'], 'resource');
        if (is_object($nlist)) {
            foreach ($nlist as $nd) {
                $sc = new CcResource($doc, $nd);
                $this->resources[$sc->identifier] = $sc;
            }
        }
    }

    public function import_organization_items(DOMElement &$node, CcIManifest &$doc)
    {
        if (is_null($this->organizations)) {
            $this->organizations = array();
        }
        $nlist = $node->getElementsByTagNameNS($this->ccnamespaces['imscc'], 'organization');
        if (is_object($nlist)) {
            foreach ($nlist as $nd) {
                $sc = new CcOrganization($nd, $doc);
                $this->organizations[$sc->identifier] = $sc;
            }
        }
    }

    public function set_generator($value)
    {
        $this->_generator = $value;
    }
}
