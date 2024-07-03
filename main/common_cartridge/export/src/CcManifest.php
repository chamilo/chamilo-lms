<?php
/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/cc_lib/cc_manifest.php under GNU/GPL license */

class CcManifest extends XMLGenericDocument implements CcIManifest
{
    private $ccversion = null;
    private $ccobj = null;
    private $rootmanifest = null;
    private $activemanifest = null;
    private $parentmanifest = null;
    private $parentparentmanifest = null;
    private $ares = [];
    private $mainidentifier = null;

    public function __construct($ccver = 13, $activemanifest = null,
                        $parentmanifest = null, $parentparentmanifest = null)
    {
        $this->ccversion = $ccver;
        $this->ccobj = new CcVersion13();
        parent::__construct('UTF-8', true);
    }

    /**
     * Register Namespace for use XPATH.
     */
    public function registerNamespacesForXpath()
    {
        $scnam = $this->activemanifest->getCcNamespaces();
        foreach ($scnam as $key => $value) {
            $this->registerNS($key, $value);
        }
    }

    /**
     * Add Metadata For Manifest.
     */
    public function addMetadataManifest(CcIMetadataManifest $met)
    {
        $metanode = $this->node("//imscc:manifest[@identifier='".
                                $this->activemanifest->manifestID().
                                "']/imscc:metadata");
        $nmeta = $this->activemanifest->createMetadataNode($met, $this->doc, $metanode);
        $metanode->appendChild($nmeta);
    }

    /**
     * Add Metadata For Resource.
     *
     * @param string $identifier
     */
    public function addMetadataResource(CcIMetadataResource $met, $identifier)
    {
        $metanode = $this->node("//imscc:resource".
            "[@identifier='".
            $identifier.
            "']");
        $metanode2 = $this->node("//imscc:resource".
            "[@identifier='".
            $identifier.
            "']/imscc:file");
        $nspaces = $this->activemanifest->getCcNamespaces();
        $dnode = $this->appendNewElementNs($metanode2, $nspaces['imscc'], 'metadata');
        $this->activemanifest->createMetadataResourceNode($met, $this->doc, $dnode);
    }

    /**
     * Add Metadata For File.
     *
     * @param string $identifier
     * @param string $filename
     */
    public function addMetadataFile(CcIMetadataFile $met, $identifier, $filename)
    {
        if (empty($met) || empty($identifier) || empty($filename)) {
            throw new Exception('Try to add a metadata file with nulls values given!');
        }

        $metanode = $this->node("//imscc:resource".
            "[@identifier='".
            $identifier.
            "']/imscc:file".
            "[@href='".
            $filename.
            "']");

        $nspaces = $this->activemanifest->getCcNamespaces();
        $dnode = $this->doc->createElementNS($nspaces['imscc'], "metadata");

        $metanode->appendChild($dnode);

        $this->activemanifest->createMetadataFileNode($met, $this->doc, $dnode);
    }

    public function onCreate()
    {
        $this->activemanifest = new CcVersion13();
        $this->rootmanifest = $this->activemanifest;
        $result = $this->activemanifest->createManifest($this->doc);
        $this->registerNamespacesForXpath();

        return $result;
    }

    public function getRelativeBasePath()
    {
        return $this->activemanifest->base();
    }

    public function parentManifest()
    {
        return new CcManifest($this, $this->parentmanifest, $this->parentparentmanifest);
    }

    public function rootManifest()
    {
        return new CcManifest($this, $this->rootmanifest);
    }

    public function manifestID()
    {
        return $this->activemanifest->manifestID();
    }

    public function getManifestNamespaces()
    {
        return $this->rootmanifest->getCcNamespaces();
    }

    /**
     * Add a new organization.
     */
    public function addNewOrganization(CcIOrganization &$org)
    {
        $norg = $this->activemanifest->createOrganizationNode($org, $this->doc);
        $orgnode = $this->node("//imscc:manifest[@identifier='".
            $this->activemanifest->manifestID().
            "']/imscc:organizations");
        $orgnode->appendChild($norg);
    }

    public function getResources($searchspecific = '')
    {
        $reslist = $this->getResourceList($searchspecific);
        $resourcelist = [];
        foreach ($reslist as $resourceitem) {
            $resourcelist[] = new CcResources($this, $resourceitem);
        }

        return $resourcelist;
    }

    public function getCcNamespacePath($nsname)
    {
        if (is_string($nsname) && (!empty($nsname))) {
            $scnam = $this->activemanifest->getCcNamespaces();

            return $scnam[$nsname];
        }

        return null;
    }

    public function getResourceList($searchspecific = '')
    {
        return $this->nodeList("//imscc:manifest[@identifier='".
                            $this->activemanifest->manifestID().
                            "']/imscc:resources/imscc:resource".$searchspecific);
    }

    public function onLoad()
    {
        $this->registerNamespacesForXpath();
        $this->fillManifest();

        return true;
    }

    public function onSave()
    {
        return true;
    }

    /**
     * Add a resource to the manifest.
     *
     * @param string $identifier
     * @param string $type
     *
     * @return array
     */
    public function addResource(CcIResource $res, $identifier = null, $type = 'webcontent')
    {
        if (!$this->ccobj->valid($type)) {
            throw new Exception("Type invalid...");
        }

        if ($res == null) {
            throw new Exception('Invalid Resource or dont give it');
        }
        $rst = $res;

        // TODO: This has to be reviewed since it does not handle multiple files properly.
        // Dependencies.
        if (is_object($identifier)) {
            $this->activemanifest->createResourceNode($rst, $this->doc, $identifier);
        } else {
            $nresnode = null;

            $rst->type = $type;
            if (!CcHelpers::isHtml($rst->filename)) {
                $rst->href = null;
            }

            $this->activemanifest->createResourceNode($rst, $this->doc, $nresnode);
            foreach ($rst->files as $file) {
                $ident = $this->getIdentifierByFilename($file);
                if ($ident == null) {
                    $newres = new CcResources($rst->manifestroot, $file);
                    if (!CcHelpers::isHtml($file)) {
                        $newres->href = null;
                    }
                    $newres->type = 'webcontent';
                    $this->activemanifest->createResourceNode($newres, $this->doc, $nresnode);
                }
            }
        }

        $tmparray = [$rst->identifier, $rst->files[0]];

        return $tmparray;
    }

    public function updateInstructoronly($identifier, $value = false)
    {
        if (isset($this->activemanifest->resources[$identifier])) {
            $resource = $this->activemanifest->resources[$identifier];
            $resource->instructoronly = $value;
        }
    }

    /**
     * Append the resources nodes in the Manifest.
     *
     * @return DOMNode
     */
    public function putNodes()
    {
        $resnodestr = "//imscc:manifest[@identifier='".$this->activemanifest->manifestID().
            "']/imscc:resources";
        $resnode = $this->node($resnodestr);

        foreach ($this->activemanifest->resources as $k => $v) {
            ($k);
            $depen = $this->checkIfExistInOther($v->files[0], $v->identifier);
            if (!empty($depen)) {
                $this->replaceFileXDependency($depen, $v->files[0]);
                $v->type = 'webcontent';
            }
        }

        foreach ($this->activemanifest->resources as $node) {
            $rnode = $this->activemanifest->createResourceNode($node, $this->doc, null);
            $resnode->appendChild($rnode);
            if ($node->instructoronly) {
                $metafileceduc = new CcMetadataResourceEducational();
                $metafileceduc->setValue(intended_user_role::INSTRUCTOR);
                $metafile = new CcMetadataResource();
                $metafile->addMetadataResourceEducational($metafileceduc);
                $this->activemanifest->createMetadataEducational($metafile, $this->doc, $rnode);
            }
        }

        return $resnode;
    }

    /**
     * TODO - implement this method - critical.
     */
    private function fillManifest()
    {
    }

    private function checkIfExistInOther($name, $identifier)
    {
        $status = [];
        foreach ($this->activemanifest->resources as $value) {
            if (($value->identifier != $identifier) && isset($value->files[$name])) {
                $status[] = $value->identifier;
            }
        }

        return $status;
    }

    private function replaceFileXDependency($depen, $name)
    {
        foreach ($depen as $key => $value) {
            ($key);
            $ident = $this->getIdentifierByFilename($name);
            $this->activemanifest->resources[$value]->files =
                $this->arrayRemoveByValue($this->activemanifest->resources[$value]->files, $name);
            if (!in_array($ident, $this->activemanifest->resources[$value]->dependency)) {
                array_push($this->activemanifest->resources[$value]->dependency, $ident);
            }
        }

        return true;
    }

    private function getIdentifierByFilename($name)
    {
        $result = null;
        if (isset($this->activemanifest->resourcesInd[$name])) {
            $result = $this->activemanifest->resourcesInd[$name];
        }

        return $result;
    }

    private function arrayRemoveByValue($arr, $value)
    {
        return array_values(array_diff($arr, [$value]));
    }

    private function arrayRemoveByKey($arr, $key)
    {
        return array_values(array_diff_key($arr, [$key]));
    }
}
